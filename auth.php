<?php
declare(strict_types=1);

require_once __DIR__ . '/menu.php';

const AUTH_SCOPE = 'eats.store';
const AUTH_TOKEN_TTL_SECONDS = 2592000;

function get_configured_client_id(): string
{
    return env_value('CLIENT_ID');
}

function get_configured_client_secret(): string
{
    return env_value('CLIENT_SECRET');
}

function auth_tokens_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'auth_tokens.json';
}

function load_auth_tokens(): array
{
    $path = auth_tokens_path();

    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    $tokens = json_decode((string) $raw, true);

    return is_array($tokens) ? $tokens : [];
}

function save_auth_tokens(array $tokens)
{
    file_put_contents(
        auth_tokens_path(),
        json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function issue_access_token(string $scope = AUTH_SCOPE): array
{
    $token = 'IA.' . bin2hex(random_bytes(48));
    $issuedAt = time();
    $expiresAt = $issuedAt + AUTH_TOKEN_TTL_SECONDS;

    $tokens = load_auth_tokens();
    $tokens[$token] = [
        'scope' => $scope,
        'issued_at' => $issuedAt,
        'expires_at' => $expiresAt,
        'token_type' => 'Bearer',
    ];

    save_auth_tokens($tokens);

    return [
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => AUTH_TOKEN_TTL_SECONDS,
        'scope' => $scope,
    ];
}

function get_authorization_header(): string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($header !== '') {
        return $header;
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            return (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
        }
    }

    return '';
}

function get_bearer_token_from_request(): string
{
    $header = trim(get_authorization_header());

    if ($header === '' || stripos($header, 'Bearer ') !== 0) {
        return '';
    }

    return trim(substr($header, 7));
}

function validate_access_token(string $token, string $requiredScope = AUTH_SCOPE): bool
{
    if ($token === '') {
        return false;
    }

    $tokens = load_auth_tokens();
    $entry = $tokens[$token] ?? null;

    if (!is_array($entry)) {
        return false;
    }

    if ((int) ($entry['expires_at'] ?? 0) < time()) {
        return false;
    }

    $scope = (string) ($entry['scope'] ?? '');
    return in_array($requiredScope, preg_split('/\s+/', trim($scope)) ?: [], true);
}

function require_bearer_scope(string $scope = AUTH_SCOPE)
{
    $token = get_bearer_token_from_request();

    if (!validate_access_token($token, $scope)) {
        respond_json([
            'error' => 'invalid_token',
            'error_description' => 'A valid Bearer token with eats.store scope is required.',
        ], 401);
    }
}
