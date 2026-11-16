'use strict';

// Opt-in dependency-free browser test. Requires Chrome --remote-debugging-port=9222.
const assert = require('node:assert/strict');
const test = require('node:test');
const fs = require('node:fs/promises');

test('desktop and mobile browser hosting checkout flow', {
    skip: process.env.RACKMONK_BROWSER_TESTS !== '1', timeout: 180000
}, async () => {
    const base = process.env.RACKMONK_TEST_URL || 'http://localhost/rackmonk_wordpress/website/';
    const target = await (await fetch('http://127.0.0.1:9222/json/new?about:blank', { method: 'PUT' })).json();
    const socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve) => socket.addEventListener('open', resolve, { once: true }));
    let id = 0;
    const pending = new Map();
    const events = new Map();
    const errors = [];
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.id && pending.has(message.id)) {
            const task = pending.get(message.id);
            pending.delete(message.id);
            message.error ? task.reject(new Error(message.error.message)) : task.resolve(message.result);
        }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails);
        if (events.has(message.method)) {
            events.get(message.method)(message.params);
            events.delete(message.method);
        }
    });
    function command(method, params = {}) {
        return new Promise((resolve, reject) => { const next = ++id; pending.set(next, { resolve, reject }); socket.send(JSON.stringify({ id: next, method, params })); });
    }
    function event(method) {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => { events.delete(method); reject(new Error(method + ' timed out')); }, 60000);
            events.set(method, (value) => { clearTimeout(timeout); resolve(value); });
        });
    }
    async function evaluate(expression) {
        const result = await command('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.text + ': ' + result.exceptionDetails.exception?.description);
        return result.result.value;
    }
    async function waitFor(expression) {
        return evaluate(`(async () => { const start = Date.now(); while (!(${expression})) { if (Date.now() - start > 30000) throw new Error('UI wait timed out'); await new Promise(r => setTimeout(r, 100)); } return true; })()`);
    }
    async function navigate(url) {
        const loaded = event('Page.domContentEventFired');
        await command('Page.navigate', { url });
        await loaded;
    }
    async function screenshot(name) {
        if (!process.env.RACKMONK_SCREENSHOT_DIR) return;
        await waitFor('Array.from(document.querySelectorAll("#preloader, .blockUI")).every(node => getComputedStyle(node).display === "none" || Number(getComputedStyle(node).opacity) === 0)');
        const image = await command('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        await fs.writeFile(process.env.RACKMONK_SCREENSHOT_DIR + '/' + name + '.png', Buffer.from(image.data, 'base64'));
    }
    try {
        await command('Page.enable');
        await command('Runtime.enable');
        await command('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
        await navigate(base);
        await waitFor('document.querySelectorAll(".pricing__box").length === 12 && window.jQuery && jQuery.isReady');
        const publicPlans = await evaluate('({ prices: Array.from(document.querySelectorAll(".pricing__price h2.price")).map(node => node.textContent.trim()), resources: document.querySelectorAll(".rm-cart-resource").length, link: document.querySelector(".pricing__btn a").href })');
        assert.equal(publicPlans.prices.length, 24, 'All twelve WHMCS plans retain prices in both billing tabs');
        assert.ok(publicPlans.prices.every((price) => /[0-9]/.test(price) && !/Customize|Build your plan/.test(price)));
        assert.equal(publicPlans.resources, 0, 'Resource configuration stays out of public plan cards');
        assert.ok(publicPlans.link.includes('/hosting-cart/?plan='), 'Choose Plan still opens website cart');
        await evaluate('document.querySelector(".pricing-tab-switcher").click()');
        await waitFor('document.querySelector(".pricing__price").classList.contains("change-subs-duration")');
        assert.equal(await evaluate('Array.from(document.querySelectorAll(".annual_price")).every(node => /[0-9]/.test(node.textContent))'), true);
        await evaluate('document.querySelector(".pricing-tab-switcher").click(); document.querySelector(".pricing__item-wrap").scrollIntoView({ block: "start" })');
        await screenshot('rackmonk-homepage-base-prices');
        await navigate(base + 'hosting-cart/?plan=1&term=12');
        await waitFor('document.querySelector(".rm-cart-summary .rm-cart-primary") && !document.querySelector(".rm-cart-summary .rm-cart-primary").disabled');
        const resourceKeys = await evaluate('Array.from(document.querySelectorAll("[data-resource-key]")).map(node => node.dataset.resourceKey)');
        assert.deepEqual(resourceKeys, ['os', 'ram', 'storage', 'secondary_disk', 'backup_disk', 'raid', 'control_panel', 'antivirus', 'firewall', 'ip_addresses', 'port_speed', 'data_transfer']);
        assert.equal(await evaluate('Boolean(document.querySelector(".rm-cart-demo-notice"))'), true);
        await evaluate('(() => { const select = document.querySelector("[data-resource-key=ram] select"); select.selectedIndex = 1; select.dispatchEvent(new Event("change")); document.querySelector(".rm-cart-checkbox").click(); })()');
        await waitFor('!document.querySelector(".rm-cart-summary .rm-cart-primary").disabled');
        await screenshot('rackmonk-configurator-desktop');
        await evaluate('document.querySelector(".rm-cart-summary .rm-cart-primary").click()');
        await waitFor('document.querySelector(".rm-cart-cart-total .rm-cart-primary") && !document.querySelector(".rm-cart-cart-total .rm-cart-primary").disabled');
        await evaluate('document.querySelector(".rm-cart-item-actions .rm-cart-secondary").click()');
        await waitFor('document.querySelector(".rm-cart-summary .rm-cart-primary").textContent === "Save cart changes" && !document.querySelector(".rm-cart-summary .rm-cart-primary").disabled');
        await evaluate('(() => { const select = document.querySelector("[data-resource-key=port_speed] select"); select.selectedIndex = 1; select.dispatchEvent(new Event("change")); })()');
        await waitFor('!document.querySelector(".rm-cart-summary .rm-cart-primary").disabled');
        await evaluate('document.querySelector(".rm-cart-summary .rm-cart-primary").click()');
        assert.equal(await evaluate('document.querySelectorAll(".rm-cart-item").length'), 1, 'Browser cart edit does not add another plan');
        const checkoutLoaded = event('Page.domContentEventFired');
        await evaluate('document.querySelector(".rm-cart-cart-total .rm-cart-primary").click()');
        await checkoutLoaded;
        await waitFor('document.querySelector("#billing_email")');
        assert.equal(await evaluate('location.pathname.endsWith("/checkout/")'), true);
        assert.equal(await evaluate('getComputedStyle(document.querySelector("form.checkout")).display'), 'grid');
        await screenshot('rackmonk-checkout-desktop');
        await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
        await evaluate('new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))');
        const mobile = await evaluate('({ width: innerWidth, overflow: document.documentElement.scrollWidth - innerWidth, layout: getComputedStyle(document.querySelector("form.checkout")).display, input: document.querySelector("#billing_email").getBoundingClientRect().width })');
        assert.equal(mobile.layout, 'block');
        assert.ok(mobile.overflow <= 1, 'No horizontal overflow on mobile: ' + JSON.stringify(mobile));
        assert.ok(mobile.input > 200 && mobile.input < 390, 'Billing fields fit mobile viewport');
        await screenshot('rackmonk-checkout-mobile');
        await navigate(base + 'cart/');
        await waitFor('document.querySelector(".woocommerce-cart-form")');
        assert.equal(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), true);
        await screenshot('rackmonk-cart-mobile');
        const editLoaded = event('Page.domContentEventFired');
        await evaluate('document.querySelector(".rm-edit-configuration").click()');
        await editLoaded;
        await waitFor('document.querySelector(".rm-cart-summary .rm-cart-primary") && !document.querySelector(".rm-cart-summary .rm-cart-primary").disabled');
        assert.equal(await evaluate('document.querySelector(".rm-cart-summary .rm-cart-primary").textContent'), 'Update cart configuration');
        assert.equal(await evaluate('document.querySelector(".rm-cart-checkbox").checked'), true, 'Saved antivirus checkbox is restored');
        assert.equal(await evaluate('document.querySelector("[data-resource-key=ram] select").selectedIndex'), 1, 'Saved memory choice is restored');
        assert.equal(await evaluate('document.querySelector("[data-resource-key=port_speed] select").selectedIndex'), 1, 'Browser-cart network edit survives checkout');
        assert.equal(await evaluate('document.documentElement.scrollWidth <= innerWidth + 1'), true, 'Mobile editor does not overflow');
        await screenshot('rackmonk-configurator-mobile');
        await evaluate('(() => { const select = document.querySelector("[data-resource-key=ram] select"); select.selectedIndex = 2; select.dispatchEvent(new Event("change")); const duration = document.querySelector("select[id$=duration]"); duration.value = "24"; duration.dispatchEvent(new Event("change")); })()');
        await waitFor('!document.querySelector(".rm-cart-summary .rm-cart-primary").disabled');
        const cartLoaded = event('Page.domContentEventFired');
        await evaluate('document.querySelector(".rm-cart-summary .rm-cart-primary").click()');
        await cartLoaded;
        await waitFor('document.querySelector(".woocommerce-cart-form")');
        assert.equal(await evaluate('document.querySelectorAll(".cart_item").length'), 1, 'Native cart edit does not create duplicates');
        assert.equal(await evaluate('document.body.innerText.includes("24 month(s)") && document.body.innerText.includes("4 GB")'), true, 'Updated duration and memory persist');
        await screenshot('rackmonk-cart-customized-mobile');
        const ownErrors = errors.filter((error) => JSON.stringify(error).includes('rackmonk-whmcs'));
        assert.deepEqual(ownErrors, []);
        console.log('Chrome: twelve numeric WHMCS homepage plan cards, both public billing tabs, cart links, all twelve cart-only resource controls, browser/native-cart edits, checkout and responsive layouts passed. No order/payment submitted.');
        console.log('VERDICT: PASS');
    } finally {
        socket.close();
        await fetch('http://127.0.0.1:9222/json/close/' + target.id);
    }
});
