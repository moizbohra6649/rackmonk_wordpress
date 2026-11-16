'use strict';

// Opt-in native WHMCS test: only a guest configuration session, never checkout/orders.
const assert = require('node:assert/strict');
const test = require('node:test');

test('native WHMCS exposes twelve linked resource options with screenshot OS choices', {
    skip: process.env.RACKMONK_NATIVE_TESTS !== '1', timeout: 150000
}, async () => {
    const base = process.env.RACKMONK_WHMCS_URL || 'http://localhost/rackmonk_wordpress/whmcs/';
    const cookies = new Map();
    async function request(url, options = {}, redirects = 0) {
        assert.ok(redirects < 8, 'Bound native redirects');
        const response = await fetch(new URL(url, base), {
            ...options, redirect: 'manual', signal: AbortSignal.timeout(45000),
            headers: { Cookie: [...cookies].map(([key, value]) => key + '=' + value).join('; '), ...options.headers }
        });
        for (const value of response.headers.getSetCookie()) {
            const pair = value.split(';')[0];
            const index = pair.indexOf('=');
            cookies.set(pair.slice(0, index), pair.slice(index + 1));
        }
        if ([301, 302, 303].includes(response.status)) {
            const destination = new URL(response.headers.get('location'), response.url);
            assert.equal(destination.origin, new URL(base).origin, 'Redirect stays on local WHMCS');
            await response.body.cancel();
            return request(destination.href, {}, redirects + 1);
        }
        return response;
    }
    const domainPage = await request('cart.php?a=add&pid=7&carttpl=standard_cart&billingcycle=monthly');
    assert.equal(domainPage.status, 200);
    const domainHtml = await domainPage.text();
    const csrf = domainHtml.match(/csrfToken\s*=\s*['"]([^'"]+)['"]/);
    assert.ok(csrf, 'WHMCS issues a real session CSRF token');
    // Using an existing reserved example domain avoids registration/availability side effects.
    const configured = await request('cart.php?a=add&pid=7&domainselect=1&carttpl=standard_cart', {
        method: 'POST', body: new URLSearchParams({ token: csrf[1], domainoption: 'owndomain', 'domains[]': 'example.com', billingcycle: 'monthly' })
    });
    assert.equal(configured.status, 200);
    const html = await configured.text();
    const selects = [...html.matchAll(/<select\b[^>]*name=["']configoption\[(\d+)\]["'][^>]*>([\s\S]*?)<\/select>/g)];
    assert.equal(selects.length, 12, 'Native configure-product page renders all twelve dropdowns');
    assert.ok(selects[0][2].includes('Ubuntu 22.04 (64-bit)') && selects[0][2].includes('Ubuntu 20.04 (64-bit)'), 'Both screenshot Ubuntu versions are available');
    assert.ok(!selects[0][2].includes('Linux (64-bit)') && !selects[0][2].includes('Windows'), 'Hidden Linux/Windows choices stay out of the client dropdown');
    for (const label of ['Operating System', 'Memory', 'Primary Disk', 'Secondary Disk', 'Backup Disk (Recommend)', 'RAID Configuration', 'Control Panel', 'Premium Antivirus', 'Firewall', 'IP Addresses', 'Port Speed', 'DataTransfer']) {
        assert.ok(html.includes(label), 'Native field label: ' + label);
    }
    assert.ok(!/Fatal error|Uncaught (?:Error|Exception)/.test(html));
    const admin = await request('admin/configproductoptions.php');
    assert.ok(admin.url.includes('/admin/login.php'), 'Native admin remains protected by WHMCS login');
    const importer = await fetch(new URL('../website/wp-content/plugins/rackmonk-whmcs/sync-native-options.php', base));
    assert.equal(importer.status, 404, 'CLI importer is not executable from the web');
    console.log('Native WHMCS product configuration renders twelve linked fields; OS visibility, session handling, protected admin and CLI-only import verified. No order/payment submitted.');
    console.log('VERDICT: PASS');
});
