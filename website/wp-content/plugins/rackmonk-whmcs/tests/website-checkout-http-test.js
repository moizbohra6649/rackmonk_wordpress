'use strict';

// Opt-in integration test: creates hidden hosting products and an isolated guest cart, never orders.
const assert = require('node:assert/strict');
const test = require('node:test');

test('live hosting quote -> WooCommerce guest session -> cart -> checkout', {
    skip: process.env.RACKMONK_HTTP_TESTS !== '1', timeout: 180000
}, async () => {
    const base = process.env.RACKMONK_TEST_URL || 'http://localhost/rackmonk_wordpress/website/';
    const cookies = new Map();
    async function request(url, options = {}) {
        const response = await fetch(new URL(url, base), {
            ...options, redirect: 'manual', signal: AbortSignal.timeout(45000),
            headers: { Cookie: [...cookies].map(([key, value]) => key + '=' + value).join('; '), ...options.headers }
        });
        for (const cookie of response.headers.getSetCookie()) {
            const [pair] = cookie.split(';');
            const index = pair.indexOf('=');
            cookies.set(pair.slice(0, index), pair.slice(index + 1));
        }
        return response;
    }
    const page = await request('hosting-cart/');
    assert.equal(page.status, 200);
    const html = await page.text();
    const match = html.match(/<script type="application\/json" data-rm-cart-data>(.*?)<\/script>/s);
    assert.ok(match, 'Configurator renders public catalog');
    const data = JSON.parse(match[1]);
    assert.ok(data.catalog.length > 0, 'Real plans are enabled');
    const plan = data.catalog[0];
    assert.deepEqual(Object.keys(plan.resources), ['os', 'ram', 'storage', 'secondary_disk', 'backup_disk', 'raid', 'control_panel', 'antivirus', 'firewall', 'ip_addresses', 'port_speed', 'data_transfer'], 'Plan exposes all screenshot resources in order');
    assert.equal(plan.demo_pricing, true);
    const choices = Object.fromEntries(Object.entries(plan.resources).map(([key, resource]) => [key, resource.choices[0].id]));
    const item = { pid: plan.pid, months: 12, choices, revision: plan.revision };
    const quoteResponse = await request(data.quoteUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(item) });
    assert.equal(quoteResponse.status, 200);
    const quote = await quoteResponse.json();
    assert.equal(quote.months, 12);
    const invalid = await request(data.checkoutUrl, { method: 'POST', body: new URLSearchParams({ nonce: 'invalid', items: JSON.stringify([item]) }) });
    assert.equal(invalid.status, 403, 'Checkout rejects invalid nonce');
    const stale = await request(data.checkoutUrl, { method: 'POST', body: new URLSearchParams({ nonce: data.checkoutNonce, items: JSON.stringify([{ ...item, revision: 'stale' }]) }) });
    assert.equal(stale.status, 409, 'Checkout rejects changed plans');
    const checkoutResponse = await request(data.checkoutUrl, {
        method: 'POST', body: new URLSearchParams({ nonce: data.checkoutNonce, items: JSON.stringify([{ ...item, total: 0.01 }, { ...item, months: 1 }]) })
    });
    assert.equal(checkoutResponse.status, 200);
    const result = await checkoutResponse.json();
    assert.equal(result.success, true, JSON.stringify(result));
    assert.ok([...cookies.keys()].some((name) => name.startsWith('wp_woocommerce_session_')), 'Guest session cookie persists');
    const cartResponse = await request(result.data.cartUrl);
    assert.equal(cartResponse.status, 200);
    const cart = await cartResponse.text();
    assert.ok(cart.includes('rm-commerce'), 'Cart renders commerce layout, not Coming Soon');
    assert.ok(cart.includes(plan.name), 'Cart shows selected hosting plan');
    assert.ok(cart.includes('12 month(s)'), 'Annual term is displayed');
    assert.ok(cart.includes('1 month(s)'), 'Monthly term is displayed');
    assert.ok(cart.includes('woocommerce-cart-form'), 'Native cart form is displayed');
    assert.ok(cart.includes('woocommerce-Price-currencySymbol'), 'Currency is displayed');
    const formatted = (amount) => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    assert.ok(cart.includes(formatted(quote.total)), 'Annual amount uses authoritative price');
    assert.ok(cart.includes(formatted(quote.monthly)), 'Same-plan monthly amount stays independent');
    assert.ok(cart.includes('Demo upgrade prices'), 'Demo prices are identified in the saved cart');
    const editLink = cart.match(/class="rm-edit-configuration" href="([^"]+)"/);
    assert.ok(editLink, 'Native cart offers explicit configuration editing');
    const keys = [...cart.matchAll(/name="cart\[([^\]]+)\]\[qty\]"/g)].map((match) => match[1]);
    const cartNonce = cart.match(/name="woocommerce-cart-nonce" value="([^"]+)"/);
    assert.equal(keys.length, 2);
    assert.ok(cartNonce, 'Quantity updates carry the native WooCommerce nonce');
    const quantityUpdate = await request(result.data.cartUrl, { method: 'POST', body: new URLSearchParams({ 'woocommerce-cart-nonce': cartNonce[1], update_cart: 'Update cart', ['cart[' + keys[0] + '][qty]']: '3', ['cart[' + keys[1] + '][qty]']: '1' }) });
    assert.ok([200, 302, 303].includes(quantityUpdate.status));
    const editPage = await request(editLink[1].replace(/&(?:amp|#0*38);/g, '&'));
    const editHtml = await editPage.text();
    const editData = JSON.parse(editHtml.match(/<script type="application\/json" data-rm-cart-data>(.*?)<\/script>/s)[1]);
    const editing = editData.editItem;
    assert.deepEqual(editing.choices, choices, 'Native cart edit restores saved resource IDs');
    assert.equal(editing.months, 12);
    assert.equal(editing.quantity, 3, 'Editor restores the native cart quantity');
    const updated = { ...item, months: 24, choices: { ...choices, ram: plan.resources.ram.choices[1].id, antivirus: plan.resources.antivirus.choices[1].id } };
    async function saveEdit(key, token) {
        return request(data.checkoutUrl, { method: 'POST', body: new URLSearchParams({ nonce: data.checkoutNonce, items: JSON.stringify([updated]), edit_key: key, edit_token: token }) });
    }
    assert.equal((await saveEdit('f'.repeat(32), editing.token)).status, 409, 'Cannot update a key outside the current guest cart');
    assert.equal((await saveEdit(editing.key, 'stale-token')).status, 409, 'Stale editor cannot overwrite a newer configuration');
    const savedEdit = await saveEdit(editing.key, editing.token);
    assert.equal(savedEdit.status, 200);
    const editResult = await savedEdit.json();
    assert.equal(editResult.data.redirect, result.data.cartUrl, 'Successful edit returns to the cart');
    assert.equal((await saveEdit(editing.key, editing.token)).status, 409, 'Repeated stale submission cannot create duplicate items');
    const updatedResponse = await request(result.data.cartUrl);
    const updatedCart = await updatedResponse.text();
    assert.equal((updatedCart.match(/class="woocommerce-cart-form__cart-item cart_item"/g) || []).length, 2, 'Updating one plan preserves the other plan');
    assert.ok(updatedCart.includes('24 month(s)') && updatedCart.includes('1 month(s)'), 'Edited and unedited terms remain distinct');
    const upgradedQuoteResponse = await request(data.quoteUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(updated) });
    const upgradedQuote = await upgradedQuoteResponse.json();
    assert.ok(upgradedQuote.total > quote.total && updatedCart.includes(formatted(upgradedQuote.total)), 'Native cart totals update from selected resources and new duration');
    assert.ok(updatedCart.includes('Premium server antivirus'), 'Cart retains the selected antivirus upgrade');
    const updatedLinks = [...updatedCart.matchAll(/class="rm-edit-configuration" href="([^"]+)"/g)];
    const reopened = await request(updatedLinks.at(-1)[1].replace(/&(?:amp|#0*38);/g, '&'));
    const reopenedHtml = await reopened.text();
    const reopenedData = JSON.parse(reopenedHtml.match(/<script type="application\/json" data-rm-cart-data>(.*?)<\/script>/s)[1]);
    assert.equal(reopenedData.editItem.quantity, 3, 'Saving resources preserves the quantity instead of resetting it');
    assert.deepEqual(reopenedData.editItem.choices, updated.choices, 'Saving resources persists the new choices');
    const billingResponse = await request(result.data.redirect);
    assert.equal(billingResponse.status, 200);
    const billing = await billingResponse.text();
    assert.ok(billing.includes('billing_first_name'), 'Billing name field renders');
    assert.ok(billing.includes('billing_email'), 'Billing email field renders');
    assert.ok(billing.includes('woocommerce-checkout-review-order'), 'Order summary renders');
    assert.ok(billing.includes(plan.name));
    assert.ok(!/Fatal error|Uncaught (?:Error|Exception)/.test(billing), 'No PHP runtime failure');
    // Removing a line via WooCommerce must retain the other configuration.
    const remove = updatedCart.match(/href="([^"]+)"[^>]+class="remove"/);
    assert.ok(remove, 'Native cart exposes remove action');
    const removed = await request(remove[1].replace(/&(?:amp|#0*38);/g, '&'), { headers: { Referer: result.data.cartUrl } });
    assert.ok([200, 302, 303].includes(removed.status), 'Remove action responds without error: ' + removed.status);
    const after = await request(result.data.cartUrl);
    const afterHtml = await after.text();
    assert.equal((afterHtml.match(/class="woocommerce-cart-form__cart-item cart_item"/g) || []).length, 1);
    console.log('Live twelve-resource quote, demo warnings, nonce/ownership/concurrency protection, saved selections, one-item edit without duplicates, updated totals, billing and removal verified. No order/payment submitted.');
    console.log('VERDICT: PASS');
});
