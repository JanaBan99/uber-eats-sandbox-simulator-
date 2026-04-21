<?php
declare(strict_types=1);

$rootPath = dirname(__DIR__, 3);
require_once $rootPath . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respond_json([
        'error' => 'method_not_allowed',
        'error_description' => 'Method not allowed. Use POST.',
    ], 405);
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$requestData = [];

if (strpos($contentType, 'application/json') !== false) {
    $requestData = read_json_request_body();
} else {
    $requestData = [
        'client_id' => (string) ($_POST['client_id'] ?? ''),
        'client_secret' => (string) ($_POST['client_secret'] ?? ''),
        'grant_type' => (string) ($_POST['grant_type'] ?? ''),
        'scope' => (string) ($_POST['scope'] ?? ''),
    ];
}

$clientId = (string) ($requestData['client_id'] ?? '');
$clientSecret = (string) ($requestData['client_secret'] ?? '');
$grantType = (string) ($requestData['grant_type'] ?? '');
$scope = (string) ($requestData['scope'] ?? '');
$configuredClientId = get_configured_client_id();
$configuredClientSecret = get_configured_client_secret();

if ($configuredClientId === '' || $configuredClientSecret === '') {
    respond_json([
        'error' => 'server_misconfigured',
        'error_description' => 'CLIENT_ID and CLIENT_SECRET must be configured in .env.',
    ], 500);
}

if (
    $clientId === '' ||
    $clientSecret === '' ||
    $grantType !== 'client_credentials' ||
    $scope !== AUTH_SCOPE
) {
    respond_json([
        'error' => 'invalid_request',
        'error_description' => 'client_id, client_secret, grant_type=client_credentials and scope=eats.store are required.',
    ], 400);
}

if ($clientId !== $configuredClientId || $clientSecret !== $configuredClientSecret) {
    respond_json([
        'error' => 'invalid_client',
        'error_description' => 'Invalid client credentials.',
    ], 401);
}

$tokenResponse = issue_access_token(AUTH_SCOPE);
app_log('AUTH', 'Access token generated', ['scope' => AUTH_SCOPE], 'auth.log');
respond_json($tokenResponse, 200);
