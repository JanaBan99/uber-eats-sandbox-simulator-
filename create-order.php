<?php
declare(strict_types=1);

require_once __DIR__ . '/menu.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    app_log('ORDER_CREATE', 'Invalid HTTP method for create-order', ['method' => $_SERVER['REQUEST_METHOD'] ?? null]);
    respond_json([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
    ], 405);
}

app_log('ORDER_CREATE', 'Create order request received');

try {
    $menu = load_menu();
    app_log('MENU_SELECTION', 'Menu loaded successfully', ['items_count' => count($menu['items'] ?? [])]);

    $rawBody = file_get_contents('php://input');
    $requestData = json_decode((string) $rawBody, true);
    $selectedItems = [];

    if (is_array($requestData) && isset($requestData['items'])) {
        if (!is_array($requestData['items'])) {
            respond_json([
                'success' => false,
                'message' => 'Invalid payload: items must be an array.',
            ], 400);
        }

        app_log('ORDER_CREATE', 'Customer-provided items payload received', ['items' => $requestData['items']]);
        $selectedItems = build_order_items_from_customer_request($menu, $requestData['items']);
        app_log('MENU_SELECTION', 'Customer-selected items accepted', ['selected_items' => $selectedItems]);
    } else {
        $selectedItems = select_random_order_items($menu);
        app_log('MENU_SELECTION', 'Random items selected (fallback mode)', ['selected_items' => $selectedItems]);
    }

    if (count($selectedItems) === 0) {
        respond_json([
            'success' => false,
            'message' => 'No sellable items available in menu.',
        ], 500);
    }

    $orderId = generate_uuid_v4();
    $storeId = STORE_ID;

    $subtotal = 0;
    foreach ($selectedItems as $item) {
        $subtotal += (int) $item['total_price'];
    }

    $dummyTaxRate = 0.10;
    $tax = (int) round($subtotal * $dummyTaxRate);
    $total = $subtotal + $tax;

    $order = [
        'order_id' => $orderId,
        'store_id' => $storeId,
        'items' => $selectedItems,
        'status' => 'CREATED',
        'payment_type' => 'CASH',
        'currency_code' => CURRENCY_CODE,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total,
        'created_at' => time(),
    ];

    store_order($order);
    app_log('ORDER_CREATE', 'Order stored successfully', ['order_id' => $orderId]);

    $baseUrl = get_base_url();
    $defaultWebhookUrl = $baseUrl . '/webhook.php';
    $webhookUrl = env_value('WEBHOOK_URL', $defaultWebhookUrl);
    $externalWebhookUrl = env_value('EXTERNAL_WEBHOOK_URL');

    $webhookPayload = [
        'event_type' => 'orders.notification',
        'event_id' => generate_uuid_v4(),
        'event_time' => time(),
        'meta' => [
            'resource_id' => $orderId,
            'status' => 'pos',
            'user_id' => $storeId,
        ],
        'resource_href' => 'https://api.uber.com/v2/eats/order/' . $orderId,
    ];

    $webhookTargets = array_values(array_unique(array_filter([$webhookUrl, $externalWebhookUrl], static function ($url) {
        return $url !== '';
    })));

    foreach ($webhookTargets as $targetUrl) {
        app_log('WEBHOOK_SEND', 'Dispatching webhook to target', ['target_url' => $targetUrl]);
        send_webhook($webhookPayload, $targetUrl);
    }

    respond_json($webhookPayload);
} catch (InvalidArgumentException $e) {
    app_log('ORDER_CREATE', 'Validation error during order creation', ['error' => $e->getMessage()]);

    respond_json([
        'success' => false,
        'message' => 'Invalid order payload.',
        'error' => $e->getMessage(),
    ], 400);
} catch (Throwable $e) {
    app_log('ORDER_CREATE', 'Unhandled exception during order creation', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    respond_json([
        'success' => false,
        'message' => 'Failed to create order.',
        'error' => $e->getMessage(),
    ], 500);
}
