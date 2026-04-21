<?php
declare(strict_types=1);

function load_env_file(string $envPath)
{
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        $value = trim($value, "\"'");
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return (string) $value;
}

load_env_file(__DIR__ . DIRECTORY_SEPARATOR . '.env');

const STORE_ID = 'store_001';
const STORE_NAME = 'Uber Eats Demo Store';
const CURRENCY_CODE = 'LKR';

function respond_json(array $payload, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function app_log(string $step, string $message, array $context = [], string $fileName = 'debug.log')
{
    $logPath = __DIR__ . DIRECTORY_SEPARATOR . $fileName;
    $line = sprintf(
        "[%s] [%s] %s | context=%s\n",
        date('Y-m-d H:i:s'),
        $step,
        $message,
        json_encode($context, JSON_UNESCAPED_SLASHES)
    );

    file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}

function generate_uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function get_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($scriptDir === '.' || $scriptDir === '/') {
        $scriptDir = '';
    }

    return $scheme . '://' . $host . $scriptDir;
}

function load_menu(): array
{
    $menuPath = __DIR__ . DIRECTORY_SEPARATOR . 'menu.json';

    if (!file_exists($menuPath)) {
        app_log('MENU_LOAD', 'menu.json not found', ['path' => $menuPath]);
        throw new RuntimeException('Menu file not found.');
    }

    $raw = file_get_contents($menuPath);
    $menu = json_decode((string) $raw, true);

    if (!is_array($menu)) {
        app_log('MENU_LOAD', 'menu.json is invalid', ['raw' => $raw]);
        throw new RuntimeException('Invalid menu.json format.');
    }

    return $menu;
}

function save_menu(array $menu)
{
    $menuPath = __DIR__ . DIRECTORY_SEPARATOR . 'menu.json';
    file_put_contents($menuPath, json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function read_json_request_body(): array
{
    $rawBody = file_get_contents('php://input');
    $contentEncoding = strtolower((string) ($_SERVER['HTTP_CONTENT_ENCODING'] ?? ''));

    if ($contentEncoding === 'gzip') {
        $decoded = gzdecode((string) $rawBody);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid gzip request payload.');
        }
        $rawBody = $decoded;
    }

    $data = json_decode((string) $rawBody, true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON payload.');
    }

    return $data;
}

function get_sellable_menu_items(array $menu): array
{
    $categoryItemIds = [];

    foreach (($menu['categories'] ?? []) as $category) {
        foreach (($category['entities'] ?? []) as $entity) {
            if (($entity['type'] ?? '') === 'ITEM' && !empty($entity['id'])) {
                $categoryItemIds[$entity['id']] = true;
            }
        }
    }

    $sellableItems = [];

    foreach (($menu['items'] ?? []) as $item) {
        $itemId = $item['id'] ?? '';
        if ($itemId !== '' && isset($categoryItemIds[$itemId])) {
            $sellableItems[] = $item;
        }
    }

    return $sellableItems;
}

function get_menu_item_map(array $menu): array
{
    $map = [];

    foreach (($menu['items'] ?? []) as $item) {
        if (!empty($item['id'])) {
            $map[$item['id']] = $item;
        }
    }

    return $map;
}

function get_modifier_group_map(array $menu): array
{
    $map = [];

    foreach (($menu['modifier_groups'] ?? []) as $group) {
        if (!empty($group['id'])) {
            $map[$group['id']] = $group;
        }
    }

    return $map;
}

function get_translation(array $field, string $fallback = ''): string
{
    $translations = $field['translations'] ?? [];
    if (!is_array($translations) || count($translations) === 0) {
        return $fallback;
    }

    return (string) ($translations['en'] ?? reset($translations) ?: $fallback);
}

function build_selected_modifiers(array $requestedModifiers, array $menuItem, array $modifierGroupMap, array $itemMap): array
{
    $allowedGroupIds = $menuItem['modifier_group_ids']['ids'] ?? [];
    $allowedGroupIds = is_array($allowedGroupIds) ? $allowedGroupIds : [];
    $allowedLookup = array_fill_keys($allowedGroupIds, true);
    $modifierSelections = [];
    $modifierUnitTotal = 0;

    foreach ($requestedModifiers as $requestedModifier) {
        if (!is_array($requestedModifier)) {
            throw new InvalidArgumentException('Each modifier selection must be an object.');
        }

        $groupId = (string) ($requestedModifier['group_id'] ?? '');
        $optionIds = $requestedModifier['option_ids'] ?? [];

        if ($groupId === '' || !is_array($optionIds)) {
            throw new InvalidArgumentException('Each modifier selection must include group_id and option_ids array.');
        }

        if (!isset($allowedLookup[$groupId])) {
            throw new InvalidArgumentException('Modifier group not allowed for item: ' . $groupId);
        }

        if (!isset($modifierGroupMap[$groupId])) {
            throw new InvalidArgumentException('Modifier group not found in menu: ' . $groupId);
        }

        $group = $modifierGroupMap[$groupId];
        $quantityInfo = $group['quantity_info']['quantity'] ?? [];
        $minUnique = (int) ($quantityInfo['min_permitted_unique'] ?? $quantityInfo['min_permitted'] ?? 0);
        $maxUnique = (int) ($quantityInfo['max_permitted_unique'] ?? $quantityInfo['max_permitted'] ?? count($optionIds));
        $uniqueOptionIds = array_values(array_unique(array_map('strval', $optionIds)));

        if (count($uniqueOptionIds) < $minUnique) {
            throw new InvalidArgumentException('Not enough modifiers selected for group: ' . $groupId);
        }

        if ($maxUnique > 0 && count($uniqueOptionIds) > $maxUnique) {
            throw new InvalidArgumentException('Too many modifiers selected for group: ' . $groupId);
        }

        $allowedOptionLookup = [];
        foreach (($group['modifier_options'] ?? []) as $option) {
            if (!empty($option['id'])) {
                $allowedOptionLookup[$option['id']] = true;
            }
        }

        $selectedItems = [];
        foreach ($uniqueOptionIds as $optionId) {
            if (!isset($allowedOptionLookup[$optionId])) {
                throw new InvalidArgumentException('Modifier option not allowed in group: ' . $optionId);
            }

            if (!isset($itemMap[$optionId])) {
                throw new InvalidArgumentException('Modifier option not found in menu: ' . $optionId);
            }

            $modifierItem = $itemMap[$optionId];
            $modifierPrice = (int) ($modifierItem['price_info']['price'] ?? 0);
            $modifierUnitTotal += $modifierPrice;
            $selectedItems[] = [
                'id' => $optionId,
                'title' => get_translation($modifierItem['title'] ?? [], $optionId),
                'price' => $modifierPrice,
                'tax_info' => $modifierItem['tax_info'] ?? [],
            ];
        }

        $modifierSelections[] = [
            'id' => $groupId,
            'title' => get_translation($group['title'] ?? [], $groupId),
            'selected_items' => $selectedItems,
        ];
    }

    foreach ($allowedGroupIds as $groupId) {
        if (!isset($modifierGroupMap[$groupId])) {
            continue;
        }

        $group = $modifierGroupMap[$groupId];
        $quantityInfo = $group['quantity_info']['quantity'] ?? [];
        $minUnique = (int) ($quantityInfo['min_permitted_unique'] ?? $quantityInfo['min_permitted'] ?? 0);

        if ($minUnique < 1) {
            continue;
        }

        $isSelected = false;
        foreach ($modifierSelections as $selection) {
            if ($selection['id'] === $groupId) {
                $isSelected = true;
                break;
            }
        }

        if (!$isSelected) {
            throw new InvalidArgumentException('Required modifier group missing for item: ' . $groupId);
        }
    }

    return [
        'selected_modifier_groups' => $modifierSelections,
        'modifier_unit_total' => $modifierUnitTotal,
    ];
}

function select_random_order_items(array $menu): array
{
    $sellableItems = get_sellable_menu_items($menu);
    $available = count($sellableItems);

    if ($available === 0) {
        return [];
    }

    $itemsToPick = random_int(1, min(3, $available));
    $pickedIndexes = array_rand($sellableItems, $itemsToPick);
    $pickedIndexes = is_array($pickedIndexes) ? $pickedIndexes : [$pickedIndexes];

    $result = [];

    foreach ($pickedIndexes as $index) {
        $menuItem = $sellableItems[$index];
        $quantity = random_int(1, 2);
        $unitPrice = (int) ($menuItem['price_info']['price'] ?? 0);

        $result[] = [
            'id' => $menuItem['id'],
            'title' => $menuItem['title']['translations']['en'] ?? 'Unknown Item',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
        ];
    }

    return $result;
}

function build_order_items_from_customer_request(array $menu, array $requestedItems): array
{
    $sellableItems = get_sellable_menu_items($menu);
    $sellableMap = [];
    $itemMap = get_menu_item_map($menu);
    $modifierGroupMap = get_modifier_group_map($menu);

    foreach ($sellableItems as $item) {
        if (!empty($item['id'])) {
            $sellableMap[$item['id']] = $item;
        }
    }

    $result = [];

    foreach ($requestedItems as $index => $requestedItem) {
        if (!is_array($requestedItem)) {
            throw new InvalidArgumentException('Each requested item must be an object.');
        }

        $itemId = (string) ($requestedItem['id'] ?? '');
        $quantity = (int) ($requestedItem['quantity'] ?? 1);
        $requestedModifiers = $requestedItem['modifiers'] ?? [];

        if ($itemId === '') {
            throw new InvalidArgumentException('Each requested item must include id.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (!isset($sellableMap[$itemId])) {
            throw new InvalidArgumentException('Item not found in sellable menu: ' . $itemId);
        }

        $menuItem = $sellableMap[$itemId];
        $baseUnitPrice = (int) ($menuItem['price_info']['price'] ?? 0);
        $modifierData = build_selected_modifiers(
            is_array($requestedModifiers) ? $requestedModifiers : [],
            $menuItem,
            $modifierGroupMap,
            $itemMap
        );
        $unitPrice = $baseUnitPrice + (int) $modifierData['modifier_unit_total'];

        $result[] = [
            'id' => $menuItem['id'],
            'title' => get_translation($menuItem['title'] ?? [], 'Unknown Item'),
            'quantity' => $quantity,
            'base_unit_price' => $baseUnitPrice,
            'modifier_unit_total' => (int) $modifierData['modifier_unit_total'],
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'selected_modifier_groups' => $modifierData['selected_modifier_groups'],
        ];

        app_log('MENU_SELECTION', 'Customer item validated', [
            'line' => $index + 1,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'modifiers' => $modifierData['selected_modifier_groups'],
        ]);
    }

    if (count($result) === 0) {
        throw new InvalidArgumentException('At least one valid item is required.');
    }

    return $result;
}

function load_orders(): array
{
    $ordersPath = __DIR__ . DIRECTORY_SEPARATOR . 'orders.json';

    if (!file_exists($ordersPath)) {
        file_put_contents($ordersPath, "{}", LOCK_EX);
    }

    $raw = file_get_contents($ordersPath);
    $orders = json_decode((string) $raw, true);

    if (!is_array($orders)) {
        app_log('ORDER_STORAGE', 'orders.json invalid, resetting storage', ['raw' => $raw]);
        $orders = [];
    }

    return $orders;
}

function save_orders(array $orders)
{
    $ordersPath = __DIR__ . DIRECTORY_SEPARATOR . 'orders.json';
    file_put_contents($ordersPath, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function store_order(array $order)
{
    $orders = load_orders();
    $orders[$order['order_id']] = $order;
    save_orders($orders);
}

function get_order_by_id(string $orderId)
{
    $orders = load_orders();
    return $orders[$orderId] ?? null;
}

function send_webhook(array $payload, string $webhookUrl): array
{
    app_log('WEBHOOK_SEND', 'Sending webhook', ['url' => $webhookUrl, 'payload' => $payload], 'webhook-send.log');

    // Keep webhook fan-out best-effort so order creation does not stall under load.
    $connectTimeoutMs = 700;
    $requestTimeoutMs = 1500;

    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT_MS => $connectTimeoutMs,
        CURLOPT_TIMEOUT_MS => $requestTimeoutMs,
        CURLOPT_NOSIGNAL => true,
    ]);

    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError !== '') {
        app_log('WEBHOOK_SEND', 'Webhook delivery failed at cURL level', [
            'url' => $webhookUrl,
            'error' => $curlError,
        ], 'webhook-send.log');

        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => $curlError,
            'response_body' => $responseBody,
        ];
    }

    $success = $httpCode >= 200 && $httpCode < 300;

    app_log(
        'WEBHOOK_SEND',
        $success ? 'Webhook delivered successfully' : 'Webhook delivery returned non-2xx',
        ['http_code' => $httpCode, 'response_body' => $responseBody],
        'webhook-send.log'
    );

    return [
        'success' => $success,
        'http_code' => $httpCode,
        'error' => null,
        'response_body' => $responseBody,
    ];
}
