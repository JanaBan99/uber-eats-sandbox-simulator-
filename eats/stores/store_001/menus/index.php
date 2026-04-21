<?php
declare(strict_types=1);

$rootPath = dirname(__DIR__, 4);
require_once $rootPath . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET' && $method !== 'POST') {
    respond_json([
        'error' => 'method_not_allowed',
        'error_description' => 'Method not allowed. Use GET or POST.',
    ], 405);
}

require_bearer_scope(AUTH_SCOPE);

if ($method === 'GET') {
    $menu = load_menu();
    app_log('MENU_API_GET', 'Menu fetched successfully', ['store_id' => STORE_ID]);
    respond_json($menu, 200);
}

try {
    $payload = read_json_request_body();

    foreach (['menus', 'categories', 'items', 'modifier_groups'] as $requiredKey) {
        if (!array_key_exists($requiredKey, $payload) || !is_array($payload[$requiredKey])) {
            throw new InvalidArgumentException('Invalid payload: ' . $requiredKey . ' must be an array.');
        }
    }

    save_menu($payload);

    app_log('MENU_API_POST', 'Menu upserted successfully', [
        'store_id' => STORE_ID,
        'menus' => count($payload['menus']),
        'categories' => count($payload['categories']),
        'items' => count($payload['items']),
        'modifier_groups' => count($payload['modifier_groups']),
    ]);

    http_response_code(204);
    exit;
} catch (InvalidArgumentException $e) {
    respond_json([
        'error' => 'invalid_payload',
        'error_description' => $e->getMessage(),
    ], 400);
} catch (Throwable $e) {
    app_log('MENU_API_POST', 'Unexpected error', ['error' => $e->getMessage()]);
    respond_json([
        'error' => 'internal_error',
        'error_description' => 'Unexpected error while saving menu.',
    ], 500);
}
