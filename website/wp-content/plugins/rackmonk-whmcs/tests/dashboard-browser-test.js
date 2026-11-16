'use strict';

// Tests an isolated PHP-rendered fixture, not an authenticated admin session or live writes.
const assert = require('node:assert/strict');
const test = require('node:test');
const fs = require('node:fs/promises');
const { pathToFileURL } = require('node:url');

test('admin layout and independent INR/USD inputs on desktop and mobile', {
    skip: !process.env.RACKMONK_DASHBOARD_SNAPSHOT, timeout: 90000
}, async () => {
    const target = await (await fetch('http://127.0.0.1:9222/json/new?about:blank', { method: 'PUT' })).json();
    const ws = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve) => ws.addEventListener('open', resolve, { once: true }));
    let id = 0;
    const pending = new Map();
    const errors = [];
    let pageLoads = 0;
    let loaded;
    ws.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.id && pending.has(message.id)) {
            const { resolve, reject, timer } = pending.get(message.id);
            pending.delete(message.id); clearTimeout(timer);
            message.error ? reject(new Error(message.error.message)) : resolve(message.result);
        }
        if (message.method === 'Page.loadEventFired') {
            pageLoads++;
            if (loaded) { loaded(); loaded = null; }
        }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
    });
    function command(method, params = {}) {
        return new Promise((resolve, reject) => {
            const next = ++id;
            const timer = setTimeout(() => { pending.delete(next); reject(new Error(method + ' timed out')); }, 20000);
            pending.set(next, { resolve, reject, timer }); ws.send(JSON.stringify({ id: next, method, params }));
        });
    }
    async function evaluate(expression) {
        const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, result.exceptionDetails?.text);
        return result.result.value;
    }
    async function screenshot(name) {
        if (!process.env.RACKMONK_SCREENSHOT_DIR) return;
        const result = await command('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        await fs.writeFile(process.env.RACKMONK_SCREENSHOT_DIR + '/' + name + '.png', Buffer.from(result.data, 'base64'));
    }
    try {
        await command('Page.enable'); await command('Runtime.enable');
        await command('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
        const pageLoaded = new Promise((resolve) => { loaded = resolve; });
        await command('Page.navigate', { url: pathToFileURL(process.env.RACKMONK_DASHBOARD_SNAPSHOT).href });
        await pageLoaded;
        const values = await evaluate('({inr:document.querySelector("[data-price-currency=INR] input[name=\\"plan_prices[monthly]\\"]").value,usd:document.querySelector("[data-price-currency=USD] input[name=\\"plan_prices[monthly]\\"]").value,groups:document.querySelectorAll("tr[data-native-group]").length,collapsed:!document.querySelector("#website-cart-settings").open})');
        assert.deepEqual(values, { inr: '999.00', usd: '9.99', groups: 5, collapsed: true });
        await evaluate('document.querySelector("#base-plan-prices").scrollIntoView()');
        await screenshot('rackmonk-admin-inr-usd-desktop');
        const placeholders = await evaluate('(() => { const inr=document.querySelector("#in_monthly"), usd=document.querySelector("#in_usd_monthly"); inr.value="299"; inr.dispatchEvent(new Event("input")); const before=document.querySelector("#in_annually").placeholder; usd.value="9.99"; usd.dispatchEvent(new Event("input")); return {before,after:document.querySelector("#in_annually").placeholder,usd:document.querySelector("#in_usd_annually").placeholder}; })()');
        assert.equal(placeholders.before, placeholders.after);
        assert.equal(placeholders.usd, '71.93');
        const nativeIDs = await evaluate(`(() => {
            const responses = JSON.parse(document.querySelector('#rm-native-test-responses').textContent);
            const sectionResponses = JSON.parse(document.querySelector('#rm-section-test-responses').textContent);
            window.nativeTest = { responses, sectionResponses, requests: [], pending: [], marker: 'page-stayed-loaded' };
            window.nativeTest.respond = async (url, request) => {
                const body = new URLSearchParams(request.body);
                let result;
                if (body.get('action') === 'rackmonk_native_selection') {
                    const key = body.get('native_group') + ':' + body.get('native_option') + ':' + body.get('native_choice');
                    result = responses[key];
                } else {
                    const kind = body.get('section');
                    const key = kind === 'groups' ? [kind, body.get('config_category'), body.get('config_plan'), body.get('native_group')].join(':') : kind + ':' + body.get(kind === 'prices' ? 'pricing_plan' : 'resource_plan');
                    result = sectionResponses[key];
                }
                window.nativeTest.requests.push({ action: body.get('action'), nonce: body.get('nonce'), credentials: request.credentials });
                return { ok: true, json: async () => result };
            };
            window.fetch = window.nativeTest.respond;
            window.chooseNative = (kind, value) => {
                const select = document.querySelector('#rm-native-' + kind);
                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            };
            window.waitNative = async (option, choice) => {
                for (let i = 0; i < 80; i++) {
                    const root = document.querySelector('[data-native-detail]');
                    if (root.dataset.nativeOption === option && (!choice || root.dataset.nativeChoice === choice) && !root.hasAttribute('aria-busy')) return true;
                    await new Promise(resolve => setTimeout(resolve, 25));
                }
                throw new Error('Native selection did not finish');
            };
            const select = document.querySelector('#rm-native-option');
            return { initial: select.value, other: Array.from(select.options).find(option => option.value !== select.value).value };
        })()`);
        await evaluate(`window.chooseNative('option', ${JSON.stringify(nativeIDs.other)})`);
        await evaluate(`window.waitNative(${JSON.stringify(nativeIDs.other)})`);
        const otherChoice = await evaluate('document.querySelector("#rm-native-choice").options[1].value');
        await evaluate(`window.chooseNative('choice', ${JSON.stringify(otherChoice)})`);
        await evaluate(`window.waitNative(${JSON.stringify(nativeIDs.other)}, ${JSON.stringify(otherChoice)})`);
        const selectionPrices = await evaluate(`({inr:document.querySelector('[name="native_settings[prices][1][monthly]"]').value,usd:document.querySelector('[name="native_settings[prices][2][monthly]"]').value,owner:document.querySelector('.rm-native-choice-editor [name="native_choice"]').value})`);
        assert.deepEqual(selectionPrices, { inr: '200.00', usd: '2.40', owner: otherChoice }, 'Choice change refreshes both currencies and save ownership');
        await evaluate(`window.fetch = (url, request) => new Promise(resolve => {
            const body = new URLSearchParams(request.body);
            const key = body.get('native_group') + ':' + body.get('native_option') + ':' + body.get('native_choice');
            window.nativeTest.pending.push(() => resolve({ok:true,json:async()=>window.nativeTest.responses[key]}));
        })`);
        await evaluate(`window.chooseNative('option', ${JSON.stringify(nativeIDs.initial)}); window.chooseNative('option', ${JSON.stringify(nativeIDs.other)})`);
        assert.equal(await evaluate('document.querySelector(".rm-native-choice-editor button[type=submit]").disabled'), true, 'Old editor cannot save while new data loads');
        await evaluate('window.nativeTest.pending[1]()');
        await evaluate(`window.waitNative(${JSON.stringify(nativeIDs.other)})`);
        const latestChoice = await evaluate('document.querySelector("[data-native-detail]").dataset.nativeChoice');
        await evaluate('window.nativeTest.pending[0](); new Promise(resolve=>setTimeout(resolve,25))');
        assert.equal(await evaluate('document.querySelector("[data-native-detail]").dataset.nativeOption'), nativeIDs.other, 'Late aborted response cannot replace the latest option');
        await evaluate('window.fetch = async () => { throw new Error("Simulated connection failure"); }');
        await evaluate(`window.chooseNative('choice', ${JSON.stringify(otherChoice)})`);
        await evaluate(`window.waitNative(${JSON.stringify(nativeIDs.other)}, ${JSON.stringify(latestChoice)})`);
        assert.equal(await evaluate('document.querySelector("#rm-native-choice").value'), latestChoice, 'Failure restores the confirmed selection');
        assert.equal(await evaluate('document.querySelector(".rm-native-choice-editor button[type=submit]").disabled'), false, 'Failure restores the correctly matched old editor');
        assert.equal(await evaluate('document.querySelector("[data-native-retry]").hidden'), false);
        await evaluate('window.fetch = window.nativeTest.respond; document.querySelector("[data-native-retry]").click()');
        await evaluate(`window.waitNative(${JSON.stringify(nativeIDs.other)}, ${JSON.stringify(otherChoice)})`);
        const beforeDirty = await evaluate('window.nativeTest.requests.length');
        await evaluate(`(() => { const field = document.querySelector('#rm-native-label'); field.value = 'Unsaved native draft'; field.dispatchEvent(new Event('input', {bubbles:true})); window.confirm = () => false; window.chooseNative('option', ${JSON.stringify(nativeIDs.initial)}); })()`);
        assert.equal(await evaluate('document.querySelector("#rm-native-label").value'), 'Unsaved native draft');
        assert.equal(await evaluate('document.querySelector("#rm-native-option").value'), nativeIDs.other);
        assert.equal(await evaluate('window.nativeTest.requests.length'), beforeDirty, 'Canceling discard sends no request');
        await evaluate(`window.confirm = () => true; window.chooseNative('option', ${JSON.stringify(nativeIDs.initial)})`);
        await evaluate(`window.waitNative(${JSON.stringify(nativeIDs.initial)})`);
        assert.equal(await evaluate('document.querySelector("#rm-native-label").value !== "Unsaved native draft"'), true);
        assert.equal(await evaluate('Array.from(document.querySelectorAll("button")).some(button => /^Load (option|choice)$/.test(button.textContent.trim()))'), false);
        assert.equal(await evaluate('window.nativeTest.requests.every(request => request.action === "rackmonk_native_selection" && request.nonce && request.credentials === "same-origin")'), true);

        await evaluate(`window.chooseAdmin = (id, value) => { const select = document.querySelector('#' + id); select.value = value; select.dispatchEvent(new Event('change', {bubbles:true})); };
            window.waitAdmin = async (kind, id, value) => {
                for (let i = 0; i < 100; i++) {
                    const section = document.querySelector('[data-admin-section="' + kind + '"]');
                    const select = section.querySelector('#' + id);
                    if (select && select.value === value && !section.hasAttribute('aria-busy')) return true;
                    await new Promise(resolve => setTimeout(resolve, 25));
                }
                throw new Error('Dashboard section did not finish loading: ' + kind);
            };`);
        await evaluate('window.chooseAdmin("rm-pricing-plan", "12")');
        await evaluate('window.waitAdmin("prices", "rm-pricing-plan", "12")');
        const newPrice = await evaluate(`({plan:document.querySelector('[data-price-currency="INR"] [name="pricing_plan"]').value,usd:document.querySelector('[data-price-currency="USD"] [name="plan_prices[monthly]"]').value})`);
        assert.deepEqual(newPrice, { plan: '12', usd: '' }, 'New plan gets its own save ID and missing USD stays blank');
        await evaluate(`(() => { const field = document.querySelector('[data-price-currency="INR"] [name="plan_prices[monthly]"]'); field.value='123.45'; field.dispatchEvent(new Event('input', {bubbles:true})); window.confirm=()=>false; window.chooseAdmin('rm-pricing-plan','11'); })()`);
        assert.equal(await evaluate('document.querySelector("#rm-pricing-plan").value'), '12', 'Cancel preserves selected price plan');
        assert.equal(await evaluate(`document.querySelector('[data-price-currency="INR"] [name="plan_prices[monthly]"]').value`), '123.45', 'Cancel preserves unsaved price edits');
        await evaluate('window.confirm=()=>true; window.chooseAdmin("rm-pricing-plan", "11")');
        await evaluate('window.waitAdmin("prices", "rm-pricing-plan", "11")');
        await evaluate(`window.fetch = (url, request) => new Promise(resolve => {
            const body=new URLSearchParams(request.body); const key=body.get('section')+':'+body.get('pricing_plan');
            window.nativeTest.pending.push(() => resolve({ok:true,json:async()=>window.nativeTest.sectionResponses[key]}));
        }); window.nativeTest.pending=[]; window.chooseAdmin('rm-pricing-plan','12'); window.chooseAdmin('rm-pricing-plan','11');`);
        assert.equal(await evaluate('document.querySelector("[data-price-currency=INR] button[type=submit]").disabled'), true, 'Prior plan cannot be saved while loading');
        await evaluate('window.nativeTest.pending[1]()');
        await evaluate('window.waitAdmin("prices", "rm-pricing-plan", "11")');
        await evaluate('window.nativeTest.pending[0](); new Promise(resolve=>setTimeout(resolve,25))');
        assert.equal(await evaluate('document.querySelector("#rm-pricing-plan").value'), '11', 'Late plan response cannot override newest selection');
        await evaluate('window.fetch = async () => { throw new Error("Connection failed"); }; window.chooseAdmin("rm-pricing-plan", "12")');
        await evaluate('window.waitAdmin("prices", "rm-pricing-plan", "11")');
        assert.equal(await evaluate('document.querySelector("[data-admin-section=prices] [data-admin-retry]").hidden'), false, 'Price error offers retry');
        await evaluate('window.fetch=window.nativeTest.respond; document.querySelector("[data-admin-section=prices] [data-admin-retry]").click()');
        await evaluate('window.waitAdmin("prices", "rm-pricing-plan", "12")');

        await evaluate('document.querySelector("#website-cart-settings").open=true; window.chooseAdmin("rm-resource-plan", "12")');
        await evaluate('window.waitAdmin("resources", "rm-resource-plan", "12")');
        assert.equal(await evaluate('document.querySelector(".rm-resource-editor [name=resource_pid]").value'), '12', 'Website settings use selected plan ID');
        const resourceCount = await evaluate('document.querySelector("[data-resource=ram] [data-resource-rows]").children.length');
        await evaluate('document.querySelector("[data-resource=ram] [data-add-choice]").click()');
        assert.equal(await evaluate('document.querySelector("[data-resource=ram] [data-resource-rows]").children.length'), resourceCount + 1, 'Newly loaded website editor rebinds add/remove controls');
        await evaluate('window.confirm=()=>false; window.chooseAdmin("rm-resource-plan", "11")');
        assert.equal(await evaluate('document.querySelector("#rm-resource-plan").value'), '12', 'Adding a resource marks the editor dirty');
        await evaluate('window.confirm=()=>true; window.chooseAdmin("rm-resource-plan", "11")');
        await evaluate('window.waitAdmin("resources", "rm-resource-plan", "11")');

        await evaluate('window.chooseAdmin("rm-config-category", "2")');
        await evaluate('window.waitAdmin("groups", "rm-config-category", "2")');
        assert.equal(await evaluate('Array.from(document.querySelector("#rm-config-plan").options).map(option=>option.value).join(",")'), '0,12', 'Category change resets and narrows plan choices');
        await evaluate('window.chooseAdmin("rm-config-plan", "12")');
        await evaluate('window.waitAdmin("groups", "rm-config-plan", "12")');
        assert.equal(await evaluate('document.querySelectorAll("tr[data-native-group]").length'), 1, 'Plan filter updates linked native groups');
        const reloadedNativeOption = await evaluate('document.querySelector("#rm-native-option").options[1].value');
        await evaluate(`window.chooseNative('option', ${JSON.stringify(reloadedNativeOption)})`);
        await evaluate(`window.waitNative(${JSON.stringify(reloadedNativeOption)})`);
        assert.equal(await evaluate('document.querySelector("#rm-native-option").value'), reloadedNativeOption, 'Native option loading is rebound after a group refresh');
        await evaluate(`(() => { const field=document.querySelector('#rm-native-label'); field.value='Keep this draft'; field.dispatchEvent(new Event('input',{bubbles:true})); window.confirm=()=>false; window.chooseAdmin('rm-config-category','0'); })()`);
        assert.equal(await evaluate('document.querySelector("#rm-config-category").value'), '2', 'Cancel preserves filtered group and native draft');
        assert.equal(await evaluate('document.querySelector("#rm-native-label").value'), 'Keep this draft');
        await evaluate('window.confirm=()=>true; window.chooseAdmin("rm-config-category", "0")');
        await evaluate('window.waitAdmin("groups", "rm-config-category", "0")');
        const nextGroup = await evaluate('document.querySelector("#rm-config-group").options[1].value');
        await evaluate(`window.chooseAdmin('rm-config-group', ${JSON.stringify(nextGroup)})`);
        await evaluate(`window.waitAdmin('groups','rm-config-group',${JSON.stringify(nextGroup)})`);
        assert.equal(await evaluate('document.querySelector("[data-native-detail]").dataset.nativeGroup'), nextGroup, 'Group dropdown loads its native details');
        const firstGroup = await evaluate('document.querySelector("[data-admin-group]").dataset.adminGroup');
        await evaluate('document.querySelector("[data-admin-group]").click()');
        await evaluate(`window.waitAdmin('groups','rm-config-group',${JSON.stringify(firstGroup)})`);
        assert.equal(await evaluate('document.querySelector("[data-native-detail]").dataset.nativeGroup'), firstGroup, 'Table group action also updates without navigation');
        await evaluate('window.chooseAdmin("rm-config-category", "3")');
        await evaluate('window.waitAdmin("groups", "rm-config-category", "3")');
        assert.equal(await evaluate('document.querySelector("#rm-config-group").value'), '0', 'An empty filter is handled without an invalid group');
        await evaluate('window.chooseAdmin("rm-config-category", "0")');
        await evaluate('window.waitAdmin("groups", "rm-config-category", "0")');
        assert.equal(await evaluate('Array.from(document.querySelectorAll("button")).some(button=>/^(Load (INR|resources|option|choice)|Filter groups)/.test(button.textContent.trim()))'), false);
        assert.equal(await evaluate('window.nativeTest.requests.filter(request=>request.action==="rackmonk_admin_section").every(request=>request.nonce && request.credentials==="same-origin")'), true);
        assert.equal(pageLoads, 1, 'Dropdown changes never reload the page');
        await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
        await evaluate('new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r)))');
        const overflow = await evaluate('document.documentElement.scrollWidth - innerWidth');
        assert.ok(overflow <= 1, 'Dashboard contains table scrolling without viewport overflow: ' + overflow);
        await evaluate('document.querySelector("#base-plan-prices").scrollIntoView()');
        await screenshot('rackmonk-admin-inr-usd-mobile');
        await evaluate('document.querySelector("#configuration-groups").scrollIntoView()');
        await screenshot('rackmonk-admin-groups-mobile');
        assert.deepEqual(errors, []);
        console.log('Offline fixture: automatic pricing, resources, group filters, option/choice loading; latest-response protection, failure/retry, draft preservation and responsive layouts passed without page reload. No admin login or live writes performed.');
        console.log('VERDICT: PASS');
    } finally {
        ws.close(); await fetch('http://127.0.0.1:9222/json/close/' + target.id);
    }
});
