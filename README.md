# Uber Eats Sandbox Simulator

A PHP-based Uber Eats sandbox simulator that provides:
- OAuth client-credentials token generation
- Auth-protected menu APIs
- Order creation and retrieval flows
- Webhook dispatch + webhook receiver
- A browser frontend for selecting items and placing/fetching orders

## Features

- OAuth token endpoint:
  - `POST /oauth/v2/token/`
  - Validates `client_id`, `client_secret`, `grant_type=client_credentials`, `scope=eats.store`
  - Issues Bearer tokens and stores them in `auth_tokens.json`

- Menu API with Bearer auth:
  - `GET /eats/stores/store_001/menus/` returns current menu
  - `POST /eats/stores/store_001/menus/` upserts menu (`menus`, `categories`, `items`, `modifier_groups`)

- Order creation:
  - `POST /create-order.php`
  - Accepts selected items + modifiers from frontend
  - Validates modifier requirements using menu definitions
  - Calculates subtotal, tax (10%), total
  - Stores order in `orders.json`
  - Sends webhook payload to configured webhook targets

- Order fetch/details:
  - `GET /get-order.php?order_id=<id>`
  - Returns Uber-style order details payload including payment blocks

- Webhook receiver:
  - `POST /webhook.php`
  - Strictly validates required webhook fields
  - Persists normalized events into `webhook-events.json`

- Frontend order UI (`index.php`):
  - Loads menu through OAuth + menu API
  - Lets user choose quantities and modifiers
  - Creates order and fetches latest order details
  - Displays totals section:
    - Total items
    - Total item price (without tax)
    - Total tax
    - Total item price (with tax)

## Project Structure

- `index.php`: Frontend UI and browser-side API flow
- `menu.php`: Shared helpers (env loading, JSON responses, logging, menu/order helpers)
- `auth.php`: Token issuance and Bearer validation helpers
- `oauth/v2/token/index.php`: OAuth token endpoint
- `eats/stores/store_001/menus/index.php`: Auth-protected menu endpoint
- `create-order.php`: Creates order + dispatches webhooks
- `get-order.php`: Returns order details payload
- `webhook.php`: Receives webhook payloads
- `menu.json`: Menu source data
- `orders.json`: Persisted orders
- `webhook-events.json`: Persisted inbound webhook events

## Prerequisites

- PHP 8.1+ (recommended 8.2)
- Git
- A terminal (PowerShell on Windows works)

## Setup

1. Clone repository:

```bash
git clone https://github.com/JanaBan99/uber-eats-sandbox-simulator-.git
cd uber-eats-sandbox-simulator-
```

2. Create local environment file:

```bash
cp .env.example .env
```

If `cp` is not available on Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

3. Edit `.env` values:
- `CLIENT_ID`: OAuth client id accepted by token endpoint
- `CLIENT_SECRET`: OAuth client secret accepted by token endpoint
- `WEBHOOK_URL`: primary webhook callback URL
- `EXTERNAL_WEBHOOK_URL`: optional second callback URL (leave empty if not needed)

## Running the Project

From project root:

```bash
php -S localhost:8080
```

Open:
- `http://localhost:8080/` for frontend

## End-to-End Flow (Frontend)

1. Frontend requests OAuth token from `POST /oauth/v2/token/`
2. Frontend fetches menu from `GET /eats/stores/store_001/menus/` with Bearer token
3. User selects item quantities and modifiers
4. Frontend posts selection to `POST /create-order.php`
5. Backend stores order and dispatches webhook payload
6. Frontend calls `GET /get-order.php?order_id=<latest_id>`
7. Frontend displays fetched order JSON and totals summary

## API Quick Reference

### 1) Get access token

Request:

```http
POST /oauth/v2/token/
Content-Type: application/x-www-form-urlencoded

client_id=<CLIENT_ID>&client_secret=<CLIENT_SECRET>&grant_type=client_credentials&scope=eats.store
```

Success response:

```json
{
  "access_token": "IA...",
  "token_type": "Bearer",
  "expires_in": 2592000,
  "scope": "eats.store"
}
```

### 2) Fetch menu

```http
GET /eats/stores/store_001/menus/
Authorization: Bearer <access_token>
```

### 3) Upsert menu

```http
POST /eats/stores/store_001/menus/
Authorization: Bearer <access_token>
Content-Type: application/json
```

Body must include arrays: `menus`, `categories`, `items`, `modifier_groups`.

### 4) Create order

```http
POST /create-order.php
Content-Type: application/json
```

Example body:

```json
{
  "items": [
    {
      "id": "pizza_cheese",
      "quantity": 2,
      "modifiers": [
        {
          "group_id": "18_pizza_toppings",
          "option_ids": ["topping_olives"]
        }
      ]
    }
  ]
}
```

### 5) Get order details

```http
GET /get-order.php?order_id=<order_id>
```

### 6) Webhook receiver

```http
POST /webhook.php
Content-Type: application/json
```

Required payload fields:
- `event_type`
- `event_id`
- `event_time`
- `meta.resource_id`
- `meta.status`
- `meta.user_id`
- `resource_href`

## Data and Logs

- `orders.json`: created orders
- `auth_tokens.json`: issued access tokens
- `webhook-events.json`: received webhook events
- `debug.log`, `auth.log`, `webhook-send.log`, `webhook-received.log`: execution logs

## Security Notes

- `.env` is ignored by git to prevent accidental secret commits
- Use `.env.example` as template for required config
- If secrets were previously committed, rotate them immediately

## Troubleshooting

- `server_misconfigured` from token endpoint:
  - Ensure `.env` exists and contains `CLIENT_ID` + `CLIENT_SECRET`

- `invalid_client`:
  - Check submitted credentials match `.env`

- `invalid_token` on menu endpoint:
  - Request a new Bearer token and pass `Authorization: Bearer <token>`

- Empty/failed order creation:
  - Ensure `menu.json` has sellable category-linked items

- Webhook not received:
  - Verify `WEBHOOK_URL` and `EXTERNAL_WEBHOOK_URL`
  - Check `webhook-send.log` and `webhook-received.log`

## Development Notes

- Currency is currently `LKR`
- Tax is simulated at a fixed 10% in `create-order.php`
- Order payload from `get-order.php` is intentionally Uber-like for integration simulation

## License

Use for sandbox/testing and internal integration experimentation.
