<?php
declare(strict_types=1);

require_once __DIR__ . '/menu.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    app_log('WEBHOOK_RECEIVE', 'Invalid HTTP method for webhook receiver', ['method' => $_SERVER['REQUEST_METHOD'] ?? null], 'webhook-received.log');
    respond_json([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
    ], 405);
}

$rawBody = file_get_contents('php://input');
$payload = json_decode((string) $rawBody, true);

if (!is_array($payload)) {
    app_log('WEBHOOK_RECEIVE', 'Invalid webhook JSON payload', ['raw_body' => $rawBody], 'webhook-received.log');
    respond_json([
        'success' => false,
        'message' => 'Invalid JSON payload.',
    ], 400);
}

$normalizedPayload = [
    'event_type' => (string) ($payload['event_type'] ?? ''),
    'event_id' => (string) ($payload['event_id'] ?? ''),
    'event_time' => (int) ($payload['event_time'] ?? 0),
    'meta' => [
        'resource_id' => (string) ($payload['meta']['resource_id'] ?? ''),
        'status' => (string) ($payload['meta']['status'] ?? ''),
        'user_id' => (string) ($payload['meta']['user_id'] ?? ''),
    ],
    'resource_href' => (string) ($payload['resource_href'] ?? ''),
];

if (
    $normalizedPayload['event_type'] === '' ||
    $normalizedPayload['event_id'] === '' ||
    $normalizedPayload['event_time'] <= 0 ||
    $normalizedPayload['meta']['resource_id'] === '' ||
    $normalizedPayload['meta']['status'] === '' ||
    $normalizedPayload['meta']['user_id'] === '' ||
    $normalizedPayload['resource_href'] === ''
) {
    app_log('WEBHOOK_RECEIVE', 'Webhook missing required strict fields', ['payload' => $payload], 'webhook-received.log');
    respond_json([
        'success' => false,
        'message' => 'Missing required webhook fields.',
    ], 400);
}

app_log('WEBHOOK_RECEIVE', 'Webhook payload received (strict normalized)', ['payload' => $normalizedPayload], 'webhook-received.log');

$eventsPath = __DIR__ . DIRECTORY_SEPARATOR . 'webhook-events.json';
$events = [];

if (file_exists($eventsPath)) {
    $existing = json_decode((string) file_get_contents($eventsPath), true);
    if (is_array($existing)) {
        $events = $existing;
    }
}

$events[] = $normalizedPayload;

file_put_contents($eventsPath, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

respond_json($normalizedPayload);
