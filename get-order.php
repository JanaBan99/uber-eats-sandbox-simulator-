<?php
declare(strict_types=1);

require_once __DIR__ . '/menu.php';

function empty_obj()
{
    return new stdClass();
}

function format_minor_amount(int $minorAmount, string $currencyCode): string
{
    return $currencyCode . ' ' . number_format($minorAmount / 100, 2, '.', '');
}

function money_block(int $minorAmount = 0, string $currencyCode = CURRENCY_CODE) : array
{
    return [
        'amount_e5' => $minorAmount * 1000,
        'currency_code' => $currencyCode,
        'formatted' => format_minor_amount($minorAmount, $currencyCode),
    ];
}

function amount_block(int $netMinor = 0, int $taxMinor = 0, string $currencyCode = CURRENCY_CODE): array
{
    $grossMinor = $netMinor + $taxMinor;

    return [
        'display_amount' => format_minor_amount($grossMinor, $currencyCode),
        'net' => money_block($netMinor, $currencyCode),
        'tax' => money_block($taxMinor, $currencyCode),
        'gross' => money_block($grossMinor, $currencyCode),
        'is_tax_inclusive' => true,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    app_log('ORDER_FETCH', 'Invalid HTTP method for get-order', ['method' => $_SERVER['REQUEST_METHOD'] ?? null]);
    respond_json([
        'success' => false,
        'message' => 'Method not allowed. Use GET.',
    ], 405);
}

$orderId = trim((string) ($_GET['order_id'] ?? ''));
app_log('ORDER_FETCH', 'Fetch order request received', ['order_id' => $orderId]);

if ($orderId === '') {
    respond_json([
        'success' => false,
        'message' => 'Missing required query param: order_id',
    ], 400);
}

$order = get_order_by_id($orderId);

if ($order === null) {
    app_log('ORDER_FETCH', 'Order not found', ['order_id' => $orderId]);
    respond_json([
        'success' => false,
        'message' => 'Order not found.',
        'order_id' => $orderId,
    ], 404);
}

$displayId = strtoupper(substr(str_replace('-', '', $orderId), -5));
$currencyCode = (string) ($order['currency_code'] ?? CURRENCY_CODE);
$subtotal = (int) ($order['subtotal'] ?? 0);
$tax = (int) ($order['tax'] ?? (int) round($subtotal * 0.10));
$total = (int) ($order['total'] ?? ($subtotal + $tax));

$cartItems = [];

foreach (($order['items'] ?? []) as $item) {
    $selectedModifierGroups = [];

    foreach (($item['selected_modifier_groups'] ?? []) as $group) {
        $selectedItems = [];

        foreach (($group['selected_items'] ?? []) as $selectedItem) {
            $selectedItems[] = [
                'id' => (string) ($selectedItem['id'] ?? ''),
                'title' => (string) ($selectedItem['title'] ?? ''),
                'external_data' => (string) ($selectedItem['id'] ?? ''),
            ];
        }

        $selectedModifierGroups[] = [
            'id' => (string) ($group['id'] ?? ''),
            'title' => (string) ($group['title'] ?? ''),
            'external_data' => (string) ($group['id'] ?? ''),
            'selected_items' => $selectedItems,
            'removed_items' => [],
        ];
    }

    $cartItems[] = [
        'id' => (string) ($item['id'] ?? 'pizza_cheese'),
        'cart_item_id' => generate_uuid_v4(),
        'customer_id' => '092400ec-ee7b-11ed-a05b-0242ac120003',
        'title' => (string) ($item['title'] ?? 'Cheese Pizza 18'),
        'external_data' => 'chz_piz_18',
        'quantity' => [
            'amount' => (int) ($item['quantity'] ?? 1),
            'in_sellable_unit' => [
                'measurement_unit' => [
                    'measurement_type' => 'MEASUREMENT_TYPE_COUNT',
                    'weight' => empty_obj(),
                ],
                'amount_e5' => 200000,
            ],
            'in_priceable_unit' => [
                'measurement_unit' => [
                    'measurement_type' => 'MEASUREMENT_TYPE_WEIGHT',
                    'weight' => empty_obj(),
                ],
                'amount_e5' => 140000,
            ],
        ],
        'default_quantity' => [
            'amount' => 1,
            'unit' => 'PIECE',
        ],
        'customer_request' => [
            'allergy' => [
                'allergens' => ['PEANUTS'],
                'instructions' => 'I am allergic to peanuts.',
            ],
            'special_instructions' => 'Add extra sauce',
        ],
        'selected_modifier_groups' => $selectedModifierGroups,
        'picture_url' => 'string',
        'fulfillment_action' => [
            'action_type' => 'REPLACE_FOR_ME',
            'item_substitutes' => [
                [
                    'id' => 'pizza_cheese',
                    'title' => 'Cheese Pizza 18',
                ],
            ],
        ],
    ];
}

if (count($cartItems) === 0) {
    $cartItems[] = [
        'id' => 'pizza_cheese',
        'cart_item_id' => 'c751e24c-ee7a-11ed-a05b-0242ac120003',
        'customer_id' => '092400ec-ee7b-11ed-a05b-0242ac120003',
        'title' => 'Cheese Pizza 18',
        'external_data' => 'chz_piz_18',
        'quantity' => [
            'amount' => 1,
            'in_sellable_unit' => [
                'measurement_unit' => [
                    'measurement_type' => 'MEASUREMENT_TYPE_COUNT',
                    'weight' => empty_obj(),
                ],
                'amount_e5' => 200000,
            ],
            'in_priceable_unit' => [
                'measurement_unit' => [
                    'measurement_type' => 'MEASUREMENT_TYPE_WEIGHT',
                    'weight' => empty_obj(),
                ],
                'amount_e5' => 140000,
            ],
        ],
        'default_quantity' => [
            'amount' => 1,
            'unit' => 'PIECE',
        ],
        'customer_request' => [
            'allergy' => [
                'allergens' => ['PEANUTS'],
                'instructions' => 'I am allergic to peanuts.',
            ],
            'special_instructions' => 'Add extra sauce',
        ],
        'selected_modifier_groups' => [
            [
                'id' => '18_pizza_toppings',
                'title' => 'Pizza Toppings for 18"',
                'external_data' => 'piz_top_18',
                'selected_items' => [empty_obj()],
                'removed_items' => [empty_obj()],
            ],
        ],
        'picture_url' => 'string',
        'fulfillment_action' => [
            'action_type' => 'REPLACE_FOR_ME',
            'item_substitutes' => [
                [
                    'id' => 'pizza_cheese',
                    'title' => 'Cheese Pizza 18',
                ],
            ],
        ],
    ];
}

$response = [
    'order' => [
        'id' => $order['order_id'],
        'display_id' => $displayId,
        'external_id' => 'UBER_EATS_ORDER_1',
        'state' => 'CREATED',
        'status' => 'SCHEDULED',
        'preparation_status' => 'PREPARING',
        'ordering_platform' => 'UBER_EATS',
        'fulfillment_type' => 'DELIVERY_BY_UBER',
        'scheduled_order_target_delivery_time_range' => [
            'start_time' => '2016-09-01T10:11:12.123456-0500',
            'end_time' => '2016-09-01T10:11:12.123456-0500',
        ],
        'store' => [
            'id' => 'bd1ed236-ee79-11ed-a05b-0242ac120003',
            'name' => 'Uber\'s Pizza Palace',
            'partner_identifiers' => [
                ['value' => 'store1', 'type' => 'MERCHANT_STORE_ID'],
            ],
            'uber_merchant_type' => ['type' => 'MERCHANT_TYPE_RESTAURANT'],
        ],
        'customers' => [
            [
                'id' => '9ae8779e-1cd7-5322-86b6-d7955afd051b',
                'name' => [
                    'display_name' => 'Uber L',
                    'first_name' => 'Uber',
                    'last_name' => 'L',
                ],
                'order_history' => ['past_order_count' => 3],
                'contact' => [
                    'phone' => [
                        'number' => '+1-800-999-9999',
                        'pin_code' => '888 52 337',
                        'country_iso2' => 'US',
                    ],
                ],
                'is_primary_customer' => true,
                'tax_profiles' => [
                    [
                        'tax_id' => '123abc',
                        'tax_id_type' => 'NIF',
                        'customer_full_name' => 'John Smith',
                        'email' => 'john@smith.com',
                        'legal_entity_name' => 'John Smith, LLC',
                        'billing_address' => '1 Broadway, New York, NY 11109',
                        'country' => 'United States',
                        'tax_profile_metadata' => [
                            'mobile_barcode' => 'string',
                            'digital_certificate_code' => 'string',
                            'donation_code' => 'string',
                        ],
                        'encrypted_tax_id' => [
                            'key' => 'string',
                            'cipher_text' => 'string',
                        ],
                    ],
                ],
                'can_respond_to_fulfillment_issues' => true,
            ],
        ],
        'deliveries' => [
            [
                'id' => '1676a555-1a6f-4d49-be91-c9bb8f94af49',
                'delivery_partner' => [
                    'id' => 'string',
                    'name' => ['display_name' => 'Jason'],
                    'vehicle' => [
                        'type' => 'CAR',
                        'make' => 'Honda',
                        'model' => 'Accord',
                        'color' => 'red',
                        'license_plate' => 'T124224',
                        'is_autonomous' => true,
                        'hand_off_instructions' => 'string',
                        'pass_code' => 'abc123',
                    ],
                    'picture_url' => 'https://d1w2poirtb3as9.cloudfront.net/image.jpeg',
                    'contact' => [
                        'phone' => [
                            'country_iso2' => 'US',
                            'number' => '+13127666835',
                            'pin_code' => '07357935',
                        ],
                    ],
                    'current_location' => [
                        'latitude' => '38.8951',
                        'longitude' => '-77.0364',
                    ],
                ],
                'status' => 'EN_ROUTE_TO_PICKUP',
                'location' => [
                    'type' => 'GOOGLE_PLACE',
                    'id' => '23119fca-ec44-5c6d-8dc9-2ac8cdde310d',
                    'street_address_line_one' => '175 Greenwich St',
                    'street_address_line_two' => '44-023',
                    'latitude' => '38.8951',
                    'longitude' => '-77.0364',
                    'unit_number' => '1',
                    'business_name' => 'Uber Technologies Inc.',
                    'city' => 'NY',
                    'country' => 'US',
                    'postal_code' => '10007',
                    'location_type_value' => 'asf-123ijfdishs_',
                    'client_provided_street_address_line_one' => 'string',
                ],
                'estimated_pick_up_time' => '2016-09-01T10:11:12.123456-0500',
                'interaction_type' => 'DELIVER_TO_DOOR',
                'delivery_partner_marked_not_ready_time' => '2016-09-01T10:11:12.123456-0500',
                'estimated_dropoff_time' => '2025-02-11T18:30:00.000Z',
                'instructions' => 'Please do not ring doorbell.',
            ],
        ],
        'carts' => [
            [
                'id' => 'string',
                'items' => $cartItems,
                'fulfillment_issues' => [
                    [
                        'issue_type' => 'OUT_OF_ITEM',
                        'action_type' => 'ASK_CUSTOMER',
                        'root_item' => [
                            'id' => 'pizza_cheese',
                            'cart_item_id' => 'c751e24c-ee7a-11ed-a05b-0242ac120003',
                            'customer_id' => '092400ec-ee7b-11ed-a05b-0242ac120003',
                            'title' => 'Cheese Pizza 18',
                            'quantity' => ['amount' => 1, 'unit' => 'PIECE'],
                            'picture_url' => 'string',
                        ],
                        'item_availability' => [
                            'items_available' => [
                                'amount' => 1,
                                'in_sellable_unit' => [
                                    'measurement_unit' => ['weight' => empty_obj()],
                                    'amount_e5' => 200000,
                                ],
                                'in_priceable_unit' => [
                                    'measurement_unit' => ['weight' => empty_obj()],
                                    'amount_e5' => 140000,
                                ],
                            ],
                        ],
                        'item_substitute' => [
                            'id' => 'pizza_cheese',
                            'quantity' => [
                                'amount' => 1,
                                'in_sellable_unit' => [
                                    'measurement_unit' => ['weight' => empty_obj()],
                                    'amount_e5' => 200000,
                                ],
                                'in_priceable_unit' => [
                                    'measurement_unit' => ['weight' => empty_obj()],
                                    'amount_e5' => 140000,
                                ],
                            ],
                        ],
                        'suspend_until' => '2016-09-01T10:11:12.12-0500',
                        'store_response' => 'The store ran out of stock.',
                        'customer_ack_type' => 'ACK_DISABLED',
                    ],
                ],
                'special_instructions' => 'Please add extra sauce.',
                'include_single_use_items' => true,
                'revision_id' => 'string',
                'restricted_items' => [
                    'alcohol' => ['contain_alcoholic_item' => false],
                    'tobacco' => ['contain_tobacco_product' => true],
                ],
            ],
        ],
        'payment' => [
            'payment_detail' => [
                'order_total' => amount_block($subtotal, $tax, $currencyCode),
                'item_charges' => [
                    'total' => amount_block($subtotal, $tax, $currencyCode),
                    'subtotal_including_promos' => amount_block($subtotal, $tax, $currencyCode),
                    'price_breakdown' => [
                        [
                            'cart_item_id' => 'c751e24c-ee7a-11ed-a05b-0242ac120003',
                            'price_type' => 'OPTION',
                            'quantity' => [
                                'amount' => 1,
                                'in_sellable_unit' => [
                                    'measurement_unit' => ['weight' => empty_obj()],
                                    'amount_e5' => 200000,
                                ],
                                'in_priceable_unit' => [
                                    'measurement_unit' => ['weight' => empty_obj()],
                                    'amount_e5' => 140000,
                                ],
                            ],
                            'total' => amount_block($subtotal, $tax, $currencyCode),
                            'discount' => [
                                'total' => [
                                    'display_amount' => 'string',
                                    'net' => empty_obj(),
                                    'tax' => empty_obj(),
                                    'gross' => empty_obj(),
                                    'is_tax_inclusive' => true,
                                ],
                                'quantity' => ['amount' => 1, 'unit' => 'PIECE'],
                            ],
                            'unit' => amount_block($subtotal, $tax, $currencyCode),
                            'base_non_loyalty_unit' => amount_block($subtotal, $tax, $currencyCode),
                        ],
                    ],
                ],
                'fees' => [
                    'total' => amount_block(0, 0, $currencyCode),
                    'details' => [
                        [
                            'id' => 'SMALL_ORDER_FEE',
                            'amount' => amount_block(0, 0, $currencyCode),
                        ],
                    ],
                ],
                'tips' => ['total' => amount_block(0, 0, $currencyCode)],
                'promotions' => [
                    'total' => amount_block(0, 0, $currencyCode),
                    'details' => [
                        [
                            'external_promotion_id' => 'string',
                            'type' => 'string',
                            'discount_value' => 'string',
                            'discount_percentage' => 'string',
                            'discount_delivery_fee_value' => 100,
                            'discount_items' => [
                                [
                                    'external_id' => 'promo_123',
                                    'discounted_quantity' => 1,
                                    'discount_amount_applied' => -5000,
                                ],
                            ],
                            'promotion_uuid' => 'string',
                            'promo_funding_splits' => [
                                [
                                    'funding_source' => 'string',
                                    'amount_paid' => [
                                        'net' => empty_obj(),
                                        'tax' => empty_obj(),
                                        'gross' => empty_obj(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'order_total_excluding_promos' => amount_block($subtotal, $tax, $currencyCode),
                ],
                'adjustment' => [
                    'total' => amount_block(0, 0, $currencyCode),
                    'details' => [
                        'amount' => amount_block(0, 0, $currencyCode),
                        'reason' => 'Customer requested extra meat on salad bowl.',
                    ],
                ],
                'currency_code' => $currencyCode,
                'cash_amount_due' => amount_block($subtotal, $tax, $currencyCode),
            ],
            'tax_reporting' => [
                'breakdown' => [
                    'items' => [
                        ['instance_id' => 'string', 'description' => 'DELIVERY_FEE', 'gross_amount' => money_block($total, $currencyCode), 'net_amount' => money_block($subtotal, $currencyCode)],
                    ],
                    'fees' => [
                        ['instance_id' => 'string', 'description' => 'DELIVERY_FEE', 'gross_amount' => money_block(0, $currencyCode), 'net_amount' => money_block(0, $currencyCode)],
                    ],
                    'promotions' => [
                        ['instance_id' => 'string', 'description' => 'DELIVERY_FEE', 'gross_amount' => money_block(0, $currencyCode), 'net_amount' => money_block(0, $currencyCode)],
                    ],
                ],
                'origin' => ['id' => 'string', 'country_iso2' => 'string', 'postal_code' => 'string'],
                'destination' => ['id' => 'string', 'country_iso2' => 'string', 'postal_code' => 'string'],
                'remittance_info' => [
                    [
                        'entity' => 'UBER',
                        'type' => 'SUBTOTAL',
                        'amount' => money_block($subtotal, $currencyCode),
                    ],
                ],
            ],
        ],
        'is_order_accuracy_risk' => true,
        'store_instructions' => 'add example ketchup',
        'preparation_time' => [
            'ready_for_pickup_time_secs' => 500,
            'source' => 'PREDICTED_BY_UBER',
            'ready_for_pickup_time' => '2016-09-01T10:11:12.123456-0500',
        ],
        'completed_time' => '2016-09-01T10:11:12.123456-0500',
        'action_eligibility' => [
            'adjust_ready_for_pickup_time' => ['is_eligible' => true, 'reason' => 'string'],
            'mark_out_of_item' => ['is_eligible' => true, 'reason' => 'string'],
            'cancel' => ['is_eligible' => true, 'reason' => 'string'],
            'mark_cannot_fulfill' => ['is_eligible' => true, 'reason' => 'string'],
        ],
        'failure_info' => [
            'reason' => 'POS_DENIED',
            'failure_attributed_to_party' => 'UNKNOWN',
            'will_merchant_be_paid' => true,
            'description' => 'string',
        ],
        'created_time' => '2016-09-01T10:11:12.123456-0500',
        'has_membership_pass' => true,
        'retailer_loyalty_info' => ['loyalty_number' => 1240822],
        'order_tracking_metadata' => [
            'url' => 'https://earn.sng.link/A3ir4p/eh3s?_dl=uberdriver://byoc-mode?code=fedbab8e-cbce-484e-9d90-067489017968&_smtype=3&pcn=byoc_ot&$deeplink_path=uberdriver://byoc-mode?code=fedbab8e-cbce-484e-9dh0-067489917961',
        ],
        'support_contact' => [
            'number' => 1234567890,
            'verification_pin' => 12345678,
            'verification_pin_expiry' => '2026-01-02T15:04:05Z07:00',
        ],
    ],
];

app_log('ORDER_FETCH', 'Order fetched successfully', ['order_id' => $orderId]);
respond_json($response, 200);
