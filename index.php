<?php
declare(strict_types=1);

require_once __DIR__ . '/menu.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uber Eats Demo Order Flow</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap');

        :root {
            --bg: #f6f1e8;
            --panel: #fffdf8;
            --ink: #222018;
            --muted: #6d6757;
            --brand: #127a5f;
            --brand-2: #e88f2a;
            --line: #d9cfbd;
            --danger: #c53a2f;
            --shadow: 0 18px 35px rgba(34, 32, 24, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 18%, rgba(232, 143, 42, 0.25), transparent 34%),
                radial-gradient(circle at 84% 12%, rgba(18, 122, 95, 0.26), transparent 36%),
                linear-gradient(120deg, #f8f4ea, #efe4d1 60%, #f6efdf);
            min-height: 100vh;
            padding: 28px 16px 44px;
        }

        .container {
            max-width: 1120px;
            margin: 0 auto;
            display: grid;
            gap: 16px;
            animation: rise 420ms ease-out;
        }

        .hero {
            background: linear-gradient(148deg, #fffaf0, #fff1dc 45%, #f7e5ca);
            border: 2px solid #e6d5bd;
            border-radius: 18px;
            padding: 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            width: 170px;
            height: 170px;
            position: absolute;
            right: -45px;
            top: -45px;
            border-radius: 50%;
            background: rgba(18, 122, 95, 0.12);
        }

        h1 {
            margin: 0 0 8px;
            font-size: clamp(1.5rem, 3.2vw, 2.2rem);
            line-height: 1.15;
            letter-spacing: 0.01em;
        }

        .sub {
            margin: 0;
            color: var(--muted);
            max-width: 740px;
        }

        .layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .menu-panel {
            padding: 16px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
        }

        .item {
            border: 1px solid #e8decd;
            background: #fffcf6;
            border-radius: 14px;
            padding: 12px;
            display: grid;
            gap: 10px;
            animation: rise 380ms ease-out;
        }

        .item-title {
            margin: 0;
            font-weight: 700;
            font-size: 1rem;
        }

        .item-id {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.76rem;
            color: var(--muted);
            word-break: break-all;
        }

        .item-price {
            font-weight: 600;
            color: #453e33;
            font-size: 0.93rem;
        }

        .item-desc {
            margin: 0;
            font-size: 0.85rem;
            color: #5b5448;
        }

        .item-tax {
            margin: 0;
            font-size: 0.8rem;
            color: var(--muted);
            font-family: 'IBM Plex Mono', monospace;
        }

        .mods {
            display: grid;
            gap: 8px;
            border-top: 1px dashed #d7ccb9;
            padding-top: 8px;
        }

        .mods-title {
            margin: 0;
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #7a725f;
            font-weight: 700;
        }

        .mod-group {
            border: 1px solid #ebdfca;
            border-radius: 10px;
            padding: 8px;
            background: #fffefb;
            display: grid;
            gap: 6px;
        }

        .mod-group-title {
            font-size: 0.83rem;
            font-weight: 700;
            color: #3c362c;
            margin: 0;
        }

        .mod-option {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.78rem;
            color: #5f5849;
            font-family: 'IBM Plex Mono', monospace;
        }

        .mod-select {
            display: grid;
            gap: 6px;
        }

        .mod-choice {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 0.79rem;
            color: #4c4539;
            font-family: 'Space Grotesk', sans-serif;
        }

        .mod-choice input {
            margin-top: 2px;
        }

        .mod-choice span {
            display: block;
        }

        .mod-hint {
            font-size: 0.74rem;
            color: #7a725f;
            font-family: 'IBM Plex Mono', monospace;
        }

        .qty-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qty-row label {
            font-size: 0.86rem;
            color: var(--muted);
        }

        input[type='number'] {
            width: 74px;
            border: 1px solid #ccbda6;
            border-radius: 10px;
            padding: 8px;
            font: inherit;
            background: #fff;
        }

        .actions {
            padding: 16px;
            display: grid;
            gap: 12px;
            align-content: start;
            position: sticky;
            top: 16px;
        }

        button {
            border: 0;
            border-radius: 12px;
            padding: 11px 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
        }

        button:hover {
            transform: translateY(-1px);
        }

        .primary {
            background: linear-gradient(120deg, var(--brand), #0d664f);
            color: #fff;
            box-shadow: 0 10px 18px rgba(18, 122, 95, 0.28);
        }

        .secondary {
            background: linear-gradient(120deg, var(--brand-2), #d47b1a);
            color: #fff;
            box-shadow: 0 10px 18px rgba(212, 123, 26, 0.26);
        }

        .status {
            border-radius: 12px;
            padding: 10px;
            font-size: 0.9rem;
            border: 1px solid;
            background: #fcfbf7;
        }

        .status.ok {
            border-color: #9cc8bd;
            color: #0d664f;
            background: #eef8f5;
        }

        .status.err {
            border-color: #e7b4af;
            color: var(--danger);
            background: #fdf0ef;
        }

        .mono {
            white-space: pre-wrap;
            word-break: break-word;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            line-height: 1.45;
            background: #111;
            color: #e6f0ea;
            border-radius: 10px;
            padding: 10px;
            margin: 0;
            max-height: 340px;
            overflow: auto;
        }

        .meta {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .totals-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff9ef;
            padding: 10px;
            display: grid;
            gap: 8px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.88rem;
            color: #4d463a;
        }

        .totals-row strong {
            color: #2d281f;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 920px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .actions {
                position: static;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="hero">
            <h1>Customer Order Screen</h1>
            <p class="sub">Select quantities for any menu item, place the order, trigger webhook notification, then fetch order details by order ID from the backend.</p>
        </section>

        <section class="layout">
            <article class="panel menu-panel">
                <p class="meta" id="menuCount">Loading menu...</p>
                <div class="menu-grid" id="menuGrid"></div>
            </article>

            <aside class="panel actions">
                <button id="placeOrderBtn" class="primary" type="button">Place Order</button>
                <button id="fetchOrderBtn" class="secondary" type="button">Fetch Latest Order Details</button>
                <div id="statusBox" class="status">Ready.</div>
                <p class="meta" id="orderMeta">Latest order ID: none</p>
                <div class="totals-card" id="totalsCard">
                    <div class="totals-row"><span>Total items</span><strong id="totalItemsValue">0</strong></div>
                    <div class="totals-row"><span>Total item price (without tax)</span><strong id="subtotalValue">LKR 0.00</strong></div>
                    <div class="totals-row"><span>Total tax</span><strong id="taxValue">LKR 0.00</strong></div>
                    <div class="totals-row"><span>Total item price (with tax)</span><strong id="totalWithTaxValue">LKR 0.00</strong></div>
                </div>
                <p class="meta">Create response:</p>
                <pre class="mono" id="createResponse">No order submitted yet.</pre>
                <p class="meta">Get-order response:</p>
                <pre class="mono" id="orderResponse">No order fetched yet.</pre>
            </aside>
        </section>
    </main>

    <script>
        const menuGrid = document.getElementById('menuGrid');
        const menuCount = document.getElementById('menuCount');
        const statusBox = document.getElementById('statusBox');
        const createResponse = document.getElementById('createResponse');
        const orderResponse = document.getElementById('orderResponse');
        const orderMeta = document.getElementById('orderMeta');
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        const fetchOrderBtn = document.getElementById('fetchOrderBtn');
        const totalItemsValue = document.getElementById('totalItemsValue');
        const subtotalValue = document.getElementById('subtotalValue');
        const taxValue = document.getElementById('taxValue');
        const totalWithTaxValue = document.getElementById('totalWithTaxValue');
        const TOKEN_ENDPOINT = 'oauth/v2/token/';
        const MENU_ENDPOINT = 'eats/stores/store_001/menus/';
        const OAUTH_CLIENT_ID = <?php echo json_encode(env_value('CLIENT_ID')); ?>;
        const OAUTH_CLIENT_SECRET = <?php echo json_encode(env_value('CLIENT_SECRET')); ?>;

        let latestOrderId = '';
        let menuState = {
            itemMap: new Map(),
            modifierGroupMap: new Map(),
        };

        function setStatus(message, ok = true) {
            statusBox.textContent = message;
            statusBox.className = ok ? 'status ok' : 'status err';
        }

        function formatCurrency(cents) {
            return 'LKR ' + ((Number(cents || 0) / 100).toFixed(2));
        }

        function amountE5ToMinor(amountE5) {
            const raw = Number(amountE5 || 0);
            if (!Number.isFinite(raw)) {
                return 0;
            }
            return Math.round(raw / 1000);
        }

        function getNestedNumber(root, path) {
            let current = root;
            for (const key of path) {
                if (!current || typeof current !== 'object' || !(key in current)) {
                    return null;
                }
                current = current[key];
            }

            const parsed = Number(current);
            return Number.isFinite(parsed) ? parsed : null;
        }

        function getOrderTotals(data) {
            const paymentDetail = data && data.order && data.order.payment ? data.order.payment.payment_detail : null;

            const subtotalE5 = getNestedNumber(paymentDetail, ['item_charges', 'total', 'net', 'amount_e5'])
                ?? getNestedNumber(paymentDetail, ['order_total', 'net', 'amount_e5'])
                ?? 0;
            const taxE5 = getNestedNumber(paymentDetail, ['item_charges', 'total', 'tax', 'amount_e5'])
                ?? getNestedNumber(paymentDetail, ['order_total', 'tax', 'amount_e5'])
                ?? 0;
            const totalWithTaxE5 = getNestedNumber(paymentDetail, ['item_charges', 'total', 'gross', 'amount_e5'])
                ?? getNestedNumber(paymentDetail, ['order_total', 'gross', 'amount_e5'])
                ?? (subtotalE5 + taxE5);

            let totalItems = 0;
            const carts = data && data.order && Array.isArray(data.order.carts) ? data.order.carts : [];
            carts.forEach((cart) => {
                const items = Array.isArray(cart.items) ? cart.items : [];
                items.forEach((item) => {
                    const quantityAmount = getNestedNumber(item, ['quantity', 'amount']);
                    totalItems += quantityAmount !== null ? quantityAmount : 0;
                });
            });

            return {
                totalItems,
                subtotalMinor: amountE5ToMinor(subtotalE5),
                taxMinor: amountE5ToMinor(taxE5),
                totalWithTaxMinor: amountE5ToMinor(totalWithTaxE5),
            };
        }

        function renderOrderTotals(data) {
            const totals = getOrderTotals(data);
            totalItemsValue.textContent = String(totals.totalItems);
            subtotalValue.textContent = formatCurrency(totals.subtotalMinor);
            taxValue.textContent = formatCurrency(totals.taxMinor);
            totalWithTaxValue.textContent = formatCurrency(totals.totalWithTaxMinor);
        }

        function pretty(value) {
            return JSON.stringify(value, null, 2);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getTranslation(entity, fallback = '') {
            if (!entity || !entity.translations) {
                return fallback;
            }
            return entity.translations.en || Object.values(entity.translations)[0] || fallback;
        }

        function parseTaxRatePercent(taxInfo) {
            if (!taxInfo || Array.isArray(taxInfo) || typeof taxInfo !== 'object') {
                return 0;
            }

            if (Number.isFinite(Number(taxInfo.tax_rate))) {
                return Number(taxInfo.tax_rate);
            }

            if (Number.isFinite(Number(taxInfo.vat_rate_percentage))) {
                return Number(taxInfo.vat_rate_percentage);
            }

            if (Number.isFinite(Number(taxInfo.mx_ieps_rate))) {
                return Number(taxInfo.mx_ieps_rate);
            }

            return 0;
        }

        function getDisplayTaxCents(priceCents = 0, taxInfo = null) {
            const ratePercent = parseTaxRatePercent(taxInfo);
            const price = Number(priceCents || 0);

            if (ratePercent <= 0) {
                return 0;
            }

            // tax_rate is tax-exclusive (add on top), VAT is tax-inclusive.
            if (taxInfo && typeof taxInfo === 'object' && Number.isFinite(Number(taxInfo.vat_rate_percentage))) {
                return Math.round(price - (price / (1 + (ratePercent / 100))));
            }

            return Math.round(price * (ratePercent / 100));
        }

        function buildModifierSection(item, modifierGroupMap, itemMap) {
            const groupIds = item.modifier_group_ids && Array.isArray(item.modifier_group_ids.ids)
                ? item.modifier_group_ids.ids
                : [];

            if (groupIds.length === 0) {
                return '<div class="mods"><p class="mods-title">Modifiers</p><div class="item-tax">None</div></div>';
            }

            const groupsHtml = groupIds.map((groupId) => {
                const group = modifierGroupMap.get(groupId);
                if (!group) {
                    return '';
                }

                const groupTitle = escapeHtml(getTranslation(group.title, groupId));
                const quantityInfo = group.quantity_info && group.quantity_info.quantity ? group.quantity_info.quantity : {};
                const minUnique = Number(quantityInfo.min_permitted_unique ?? quantityInfo.min_permitted ?? 0);
                const maxUnique = Number(quantityInfo.max_permitted_unique ?? quantityInfo.max_permitted ?? 0);
                const inputType = maxUnique === 1 ? 'radio' : 'checkbox';
                const options = Array.isArray(group.modifier_options) ? group.modifier_options : [];
                const optionsHtml = options.map((option) => {
                    const optionItem = itemMap.get(option.id);
                    const optionTitle = optionItem ? getTranslation(optionItem.title, option.id) : option.id;
                    const optionPrice = optionItem && optionItem.price_info ? Number(optionItem.price_info.price || 0) : 0;
                    const optionTaxCents = getDisplayTaxCents(optionPrice, optionItem ? optionItem.tax_info : null);
                    const inputName = 'modifier-' + item.id + '-' + groupId;
                    const inputId = 'modifier-' + item.id + '-' + groupId + '-' + option.id;

                    return '<label class="mod-choice" for="' + escapeHtml(inputId) + '">' +
                        '<input type="' + escapeHtml(inputType) + '" ' +
                        'id="' + escapeHtml(inputId) + '" ' +
                        'name="' + escapeHtml(inputName) + '" ' +
                        'data-item-id="' + escapeHtml(item.id) + '" ' +
                        'data-group-id="' + escapeHtml(groupId) + '" ' +
                        'data-option-id="' + escapeHtml(option.id) + '">' +
                        '<span>' + escapeHtml(optionTitle) + '</span>' +
                        '<span class="mod-hint">' + escapeHtml('Price ' + formatCurrency(optionPrice) + ' | Tax ' + formatCurrency(optionTaxCents)) + '</span>' +
                        '</label>';
                }).join('');

                return '<div class="mod-group">' +
                    '<p class="mod-group-title">' + groupTitle + '</p>' +
                    '<div class="mod-hint">min ' + escapeHtml(String(minUnique)) + ' | max ' + escapeHtml(String(maxUnique)) + '</div>' +
                    '<div class="mod-select">' + optionsHtml + '</div>' +
                    '</div>';
            }).join('');

            return '<div class="mods"><p class="mods-title">Modifiers</p>' + groupsHtml + '</div>';
        }

        function getItemModifierRequirements(itemId) {
            const item = menuState.itemMap.get(itemId);
            if (!item) {
                return [];
            }

            const groupIds = item.modifier_group_ids && Array.isArray(item.modifier_group_ids.ids)
                ? item.modifier_group_ids.ids
                : [];

            return groupIds.map((groupId) => {
                const group = menuState.modifierGroupMap.get(groupId);
                if (!group) {
                    return null;
                }

                const quantityInfo = group.quantity_info && group.quantity_info.quantity ? group.quantity_info.quantity : {};
                const minUnique = Number(quantityInfo.min_permitted_unique ?? quantityInfo.min_permitted ?? 0);
                const maxUnique = Number(quantityInfo.max_permitted_unique ?? quantityInfo.max_permitted ?? 0);

                return {
                    id: groupId,
                    title: getTranslation(group.title, groupId),
                    minUnique,
                    maxUnique,
                };
            }).filter(Boolean);
        }

        function getSellableMap(menuData) {
            const ids = new Set();
            (menuData.categories || []).forEach((category) => {
                (category.entities || []).forEach((entity) => {
                    if (entity.type === 'ITEM' && entity.id) {
                        ids.add(entity.id);
                    }
                });
            });

            const map = new Map();
            (menuData.items || []).forEach((item) => {
                if (item.id && ids.has(item.id)) {
                    map.set(item.id, item);
                }
            });
            return map;
        }

        async function fetchAccessToken() {
            const form = new URLSearchParams();
            form.append('client_id', OAUTH_CLIENT_ID);
            form.append('client_secret', OAUTH_CLIENT_SECRET);
            form.append('grant_type', 'client_credentials');
            form.append('scope', 'eats.store');

            const res = await fetch(TOKEN_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: form.toString(),
            });

            const data = await res.json();
            if (!res.ok || !data.access_token) {
                throw new Error('Failed to fetch access token');
            }

            return data.access_token;
        }

        async function fetchMenuConfiguration() {
            const token = await fetchAccessToken();
            const res = await fetch(MENU_ENDPOINT + '?t=' + Date.now(), {
                method: 'GET',
                headers: {
                    Authorization: 'Bearer ' + token,
                },
                cache: 'no-store',
            });

            if (!res.ok) {
                throw new Error('Failed to load menu from GET endpoint');
            }

            return await res.json();
        }

        async function loadMenu() {
            try {
                const menu = await fetchMenuConfiguration();
                const sellableMap = getSellableMap(menu);
                const sellableItems = Array.from(sellableMap.values());
                const itemMap = new Map((menu.items || []).map((item) => [item.id, item]));
                const modifierGroupMap = new Map((menu.modifier_groups || []).map((group) => [group.id, group]));
                menuState = { itemMap, modifierGroupMap };

                sellableItems.forEach((item) => {
                    const price = Number(item.price_info && item.price_info.price ? item.price_info.price : 0);
                    const title = getTranslation(item.title, item.id);
                    const description = getTranslation(item.description, 'No description available.');
                    const itemTaxCents = getDisplayTaxCents(price, item.tax_info);
                    const modifierHtml = buildModifierSection(item, modifierGroupMap, itemMap);

                    const card = document.createElement('section');
                    card.className = 'item';
                    card.innerHTML = `
                        <h3 class="item-title">${escapeHtml(title)}</h3>
                        <div class="item-id">${escapeHtml(item.id)}</div>
                        <p class="item-desc">${escapeHtml(description)}</p>
                        <div class="item-price">Price: ${formatCurrency(price)}</div>
                        <p class="item-tax">Tax: ${formatCurrency(itemTaxCents)}</p>
                        ${modifierHtml}
                        <div class="qty-row">
                            <label for="qty-${item.id}">Qty</label>
                            <input id="qty-${item.id}" type="number" min="0" step="1" value="0" data-item-id="${item.id}">
                        </div>
                    `;
                    menuGrid.appendChild(card);
                });

                const menuName = (menu.menus && menu.menus[0] && menu.menus[0].title && menu.menus[0].title.translations && menu.menus[0].title.translations.en)
                    ? menu.menus[0].title.translations.en
                    : 'Main Menu';
                menuCount.textContent = `Loaded ${sellableItems.length} sellable items from ${menuName}.`;
                setStatus('Menu loaded. Set quantities and place order.', true);
            } catch (error) {
                setStatus('Failed to load menu: ' + error.message, false);
            }
        }

        function collectSelectedItems() {
            const inputs = menuGrid.querySelectorAll('input[data-item-id]');
            const items = [];
            const validationErrors = [];

            inputs.forEach((input) => {
                const quantity = Number(input.value || 0);
                if (quantity > 0 && input.type === 'number') {
                    const modifierInputs = menuGrid.querySelectorAll(
                        'input[data-item-id="' + input.dataset.itemId + '"][data-group-id][data-option-id]:checked'
                    );
                    const modifierMap = new Map();

                    modifierInputs.forEach((modifierInput) => {
                        const groupId = modifierInput.dataset.groupId;
                        const optionId = modifierInput.dataset.optionId;
                        if (!modifierMap.has(groupId)) {
                            modifierMap.set(groupId, []);
                        }
                        modifierMap.get(groupId).push(optionId);
                    });

                    const modifiers = Array.from(modifierMap.entries()).map(([groupId, optionIds]) => ({
                        group_id: groupId,
                        option_ids: optionIds,
                    }));

                    const requirements = getItemModifierRequirements(input.dataset.itemId);
                    requirements.forEach((requirement) => {
                        const selected = modifierMap.get(requirement.id) || [];
                        if (selected.length < requirement.minUnique) {
                            validationErrors.push(
                                getTranslation(menuState.itemMap.get(input.dataset.itemId).title, input.dataset.itemId) +
                                ': select at least ' + requirement.minUnique + ' option(s) for ' + requirement.title
                            );
                        }
                        if (requirement.maxUnique > 0 && selected.length > requirement.maxUnique) {
                            validationErrors.push(
                                getTranslation(menuState.itemMap.get(input.dataset.itemId).title, input.dataset.itemId) +
                                ': select at most ' + requirement.maxUnique + ' option(s) for ' + requirement.title
                            );
                        }
                    });

                    items.push({
                        id: input.dataset.itemId,
                        quantity,
                        modifiers,
                    });
                }
            });

            return {
                items,
                validationErrors,
            };
        }

        async function placeOrder() {
            const selection = collectSelectedItems();
            const items = selection.items;
            if (items.length === 0) {
                setStatus('Select at least one item quantity greater than zero.', false);
                return;
            }

            if (selection.validationErrors.length > 0) {
                createResponse.textContent = pretty({ errors: selection.validationErrors });
                setStatus(selection.validationErrors[0], false);
                return;
            }

            setStatus('Creating order and triggering webhook...', true);
            placeOrderBtn.disabled = true;

            try {
                const res = await fetch('create-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ items }),
                });

                const data = await res.json();
                createResponse.textContent = pretty(data);

                if (!res.ok || !data.meta || !data.meta.resource_id) {
                    setStatus('Create order failed. See response.', false);
                    return;
                }

                latestOrderId = data.meta.resource_id || '';
                orderMeta.textContent = 'Latest order ID: ' + (latestOrderId || 'none');

                setStatus('Order created and webhook payload generated.', true);

                if (latestOrderId) {
                    await fetchOrderDetails();
                }
            } catch (error) {
                setStatus('Create order request error: ' + error.message, false);
            } finally {
                placeOrderBtn.disabled = false;
            }
        }

        async function fetchOrderDetails() {
            if (!latestOrderId) {
                setStatus('No order ID available yet. Place an order first.', false);
                return;
            }

            fetchOrderBtn.disabled = true;
            try {
                const res = await fetch('get-order.php?order_id=' + encodeURIComponent(latestOrderId));
                const data = await res.json();
                orderResponse.textContent = pretty(data);

                if (!res.ok) {
                    setStatus('Get-order failed for latest ID.', false);
                    return;
                }

                renderOrderTotals(data);

                setStatus('Order details fetched successfully.', true);
            } catch (error) {
                setStatus('Get-order request error: ' + error.message, false);
            } finally {
                fetchOrderBtn.disabled = false;
            }
        }

        placeOrderBtn.addEventListener('click', placeOrder);
        fetchOrderBtn.addEventListener('click', fetchOrderDetails);
        loadMenu();
    </script>
</body>
</html>
