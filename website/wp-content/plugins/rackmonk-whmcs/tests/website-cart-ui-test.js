'use strict';

// Dependency-free behavioral harness, not a browser/layout test.
// Run from any directory: node --test path/to/tests/website-cart-ui-test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '../assets/js/website-cart.js'), 'utf8');
const STORAGE_KEY = 'rackmonk_website_cart_v1';

class MockElement {
    constructor(tagName, ownerDocument) {
        this.tagName = tagName;
        this.ownerDocument = ownerDocument;
        this.children = [];
        this.attributes = {};
        this.dataset = {};
        this.events = {};
        this.className = '';
        this._text = '';
        this._value = undefined;
        this.disabled = false;
        this.hidden = false;
        this.classList = {
            add: (name) => { this.className = [...new Set([...this.className.split(' '), name])].join(' '); },
            remove: (name) => { this.className = this.className.split(' ').filter((item) => item !== name).join(' '); }
        };
    }

    appendChild(node) {
        this.children.push(node);
        node.parentNode = this;
        return node;
    }

    replaceChildren() {
        this.children.forEach((node) => { node.parentNode = null; });
        this.children = [];
        this._text = '';
    }

    set textContent(value) { this.replaceChildren(); this._text = String(value); }
    get textContent() { return this._text + this.children.map((node) => node.textContent).join(' '); }
    set value(value) { this._value = String(value); }
    get value() { return this._value === undefined ? (this.tagName === 'select' && this.children[0] ? this.children[0].value : '') : this._value; }
    setAttribute(key, value) { this.attributes[key] = String(value); }
    getAttribute(key) { return this.attributes[key] ?? null; }
    addEventListener(type, callback) { (this.events[type] ||= []).push(callback); }
    dispatch(type, event = {}) { (this.events[type] || []).forEach((callback) => callback(event)); }
    click() { if (!this.disabled) this.dispatch('click'); }
    focus() { this.ownerDocument.activeElement = this; }
    all() { return this.children.flatMap((node) => [node, ...node.all()]); }

    matches(selector) {
        if (selector.includes(',')) return selector.split(',').some((part) => this.matches(part.trim()));
        if (selector.startsWith('.')) return this.className.split(' ').includes(selector.slice(1));
        if (selector.startsWith('#')) return this.id === selector.slice(1);
        if (selector === 'script[type="application/json"][data-rm-cart-data]') {
            return this.tagName === 'script' && this.attributes.type === 'application/json' && this.attributes['data-rm-cart-data'] !== undefined;
        }
        return this.tagName === selector;
    }

    querySelectorAll(selector) { return this.all().filter((node) => node.matches(selector)); }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
}

// Virtual time makes debounce, timeout, and out-of-order responses reproducible.
class Clock {
    constructor() { this.now = 0; this.id = 0; this.timers = new Map(); }
    setTimeout(callback, delay = 0) {
        const id = ++this.id;
        this.timers.set(id, { at: this.now + delay, callback });
        return id;
    }
    clearTimeout(id) { this.timers.delete(id); }
    async flush() { for (let index = 0; index < 12; index++) await Promise.resolve(); }
    async advance(milliseconds = 0) {
        const end = this.now + milliseconds;
        await this.flush();
        while (true) {
            const next = [...this.timers.entries()].filter(([, timer]) => timer.at <= end).sort((a, b) => a[1].at - b[1].at)[0];
            if (!next) break;
            this.now = next[1].at;
            this.timers.delete(next[0]);
            next[1].callback();
            await this.flush();
        }
        this.now = end;
        await this.flush();
    }
}

function savedItem(overrides = {}) {
    return { key: 'saved-1', pid: 1, months: 1, choices: { cpu: 'cpu-2', antivirus: 'av-no' }, revision: 'r1', ...overrides };
}

function setup(options = {}) {
    const firstPlan = {
        pid: 1, name: 'Starter <img src=x onerror=alert(1)>', currency: 'INR', symbol: 'Rs', base_price: '299.00', revision: 'r1',
        demo_pricing: options.demo || false,
        resources: {
            cpu: { label: 'CPU', type: 'select', choices: [{ id: 'cpu-2', label: '2 vCPU', price: '0.00' }, { id: 'cpu-4', label: '4 vCPU', price: '100.00' }] },
            antivirus: { label: 'Premium Antivirus', type: 'checkbox', choices: [{ id: 'av-no', label: 'No', price: '0.00' }, { id: 'av-yes', label: 'Enterprise', price: '50.00' }] }
        }
    };
    const catalog = options.catalog ?? [firstPlan, { ...firstPlan, pid: 2, name: 'Dollar plan', currency: 'USD' }];
    const document = new MockElement('document');
    document.ownerDocument = document;
    document.readyState = 'complete';
    document.createElement = (tagName) => new MockElement(tagName, document);
    document.createTextNode = (text) => { const node = new MockElement('#text', document); node.textContent = text; return node; };
    document.getElementById = (id) => document.all().find((node) => node.id === id) || null;
    const root = document.createElement('div');
    root.className = 'rm-website-cart';
    document.appendChild(root);
    const script = document.createElement('script');
    script.setAttribute('type', 'application/json');
    script.setAttribute('data-rm-cart-data', '');
    script.textContent = JSON.stringify({ catalog, content: options.content, homeUrl: 'https://rackmonk.test/', requestedPlan: options.requestedPlan ?? 1, requestedTerm: options.requestedTerm ?? 12, quoteUrl: '/quote', nonce: 'test-nonce', editItem: options.editItem, editError: options.editError, ...(options.checkout ? { checkoutUrl: '/checkout-sync', checkoutNonce: 'checkout-nonce', cartUrl: '/cart/' } : {}) });
    root.appendChild(script);
    const values = new Map([[STORAGE_KEY, JSON.stringify(options.saved ?? [])]]);
    const calls = [];
    const listeners = {};
    const clock = new Clock();
    let handler = options.handler;
    let uniqueId = 0;
    const window = {
        location: { href: 'https://rackmonk.test/hosting-cart/', origin: 'https://rackmonk.test', assign(url) { this.destination = url; } },
        localStorage: {
            getItem(key) { if (options.storageFails) throw new Error('Storage denied'); return values.get(key) ?? null; },
            setItem(key, value) { if (options.storageFails || options.storageWriteFails) throw new Error('Storage denied'); values.set(key, value); }
        },
        crypto: { randomUUID: () => 'new-item-' + (++uniqueId) },
        addEventListener: (type, callback) => { listeners[type] = callback; }
    };
    const errorResponse = (message, status = 409) => ({ ok: false, json: async () => ({ message, code: 'quote_error', data: { status } }) });
    const response = (body) => {
        const plan = catalog.find((item) => item.pid === body.pid);
        if (!plan) return errorResponse('This plan is disabled.', 404);
        if (body.revision !== plan.revision) return errorResponse('Saved configuration is stale.');
        const selection = [];
        for (const [key, id] of Object.entries(body.choices)) {
            const resource = plan.resources[key];
            const choice = resource?.choices.find((item) => item.id === id);
            if (!choice) return errorResponse('Unknown resource choice.');
            selection.push({ key, label: resource.label, choice: choice.label, price: choice.price, id });
        }
        const extras = selection.reduce((sum, item) => sum + Number(item.price), 0);
        const monthly = Number(plan.base_price) + extras;
        return { ok: true, json: async () => ({ pid: plan.pid, name: plan.name, currency: plan.currency, symbol: plan.symbol, months: body.months, base_monthly: Number(plan.base_price), resources_monthly: extras, monthly, total: monthly * body.months, selection, choices: body.choices, revision: plan.revision }) };
    };
    const fetch = async (url, request) => {
        if (url === '/checkout-sync') {
            const body = Object.fromEntries(new URLSearchParams(request.body));
            calls.push({ url, request, body });
            return options.checkoutHandler ? options.checkoutHandler(body) : { ok: true, json: async () => ({ success: true, data: { redirect: body.edit_key ? 'https://rackmonk.test/cart/' : 'https://rackmonk.test/checkout/' } }) };
        }
        const body = JSON.parse(request.body);
        calls.push({ url, request, body });
        return handler ? handler(body, request, response) : response(body);
    };
    vm.runInNewContext(source, {
        document, window, fetch, AbortController, Intl, URL, URLSearchParams,
        setTimeout: clock.setTimeout.bind(clock), clearTimeout: clock.clearTimeout.bind(clock)
    }, { filename: 'website-cart.js' });

    return {
        root, document, window, values, calls, clock, listeners, errorResponse,
        setHandler(callback) { handler = callback; },
        find(className) { return root.querySelector('.' + className); },
        field(suffix) { return document.getElementById('rm-cart-1-' + suffix); },
        button(text) { return root.querySelectorAll('button').find((node) => node.textContent === text); },
        add() { return root.querySelector('.rm-cart-summary')?.querySelector('.rm-cart-primary') || null; },
        saved() { return JSON.parse(values.get(STORAGE_KEY)); },
        change(suffix, value) { const field = this.field(suffix); assert.ok(field, suffix + ' exists'); field.value = value; field.dispatch('change'); }
    };
}

test('admin copy controls configurator labels and CTAs without interpreting HTML', async () => {
    const ui = setup({ content: {
        cart_plan_label: 'Select your Rackmonk plan', cart_duration_label: 'Choose your term',
        cart_title: '<img src=x onerror=alert(1)>', cart_add_cta: 'Save my Rackmonk plan',
        cart_base_month_label: 'Package rate'
    } });
    await ui.clock.advance();
    assert.equal(ui.field('plan').parentNode.querySelector('label').textContent, 'Select your Rackmonk plan');
    assert.equal(ui.field('duration').parentNode.querySelector('label').textContent, 'Choose your term');
    assert.equal(ui.find('rm-cart-title').textContent, '<img src=x onerror=alert(1)>');
    assert.equal(ui.root.querySelectorAll('img').length, 0);
    assert.equal(ui.add().textContent, 'Save my Rackmonk plan');
    assert.match(ui.find('rm-cart-summary').textContent, /Package rate/);
    ui.add().click();
    assert.equal(ui.saved().length, 1);
});

test('configurator leaves branding and footer to the shared theme', async () => {
    const ui = setup({ checkout: true });
    await ui.clock.advance();
    assert.equal(ui.find('rm-cart-topbar'), null);
    assert.equal(ui.find('rm-cart-brand'), null);
    assert.equal(ui.find('rm-cart-disclaimer'), null);
    assert.doesNotMatch(ui.root.textContent, /Open checkout cart|Billing details, taxes/);
    assert.ok(ui.find('rm-cart-configure'));
});

test('checkout transfers only selections with nonce then clears local cart after handoff', async () => {
    const ui = setup({ checkout: true, saved: [savedItem()] });
    await ui.clock.advance();
    ui.button('Your cart (1)').click();
    await ui.clock.advance();
    ui.button('Proceed to checkout').click();
    await ui.clock.flush();
    const call = ui.calls.find((item) => item.url === '/checkout-sync');
    assert.equal(call.body.nonce, 'checkout-nonce');
    assert.deepEqual(JSON.parse(call.body.items), [savedItem()]);
    assert.equal(ui.window.location.destination, 'https://rackmonk.test/checkout/');
    assert.deepEqual(ui.saved(), []);
});

test('demo prices are visibly identified and zero-cost resource choices say Included', async () => {
    const ui = setup({ demo: true });
    await ui.clock.advance();
    assert.match(ui.find('rm-cart-demo-notice').textContent, /Demo upgrade prices/);
    assert.match(ui.field('resource-0').children[0].textContent, /Included/);
    assert.match(ui.field('resource-0').children[1].textContent, /100\.00\/mo/);
});

test('native cart edit restores selections, updates one session item and preserves browser drafts', async () => {
    const editItem = savedItem({ key: 'a'.repeat(32), months: 24, choices: { cpu: 'cpu-4', antivirus: 'av-yes' }, quantity: 3, token: 'token-1' });
    const ui = setup({ checkout: true, editItem, saved: [savedItem({ revision: 'old' })] });
    await ui.clock.advance();
    assert.equal(ui.field('duration').value, '24');
    assert.equal(ui.field('resource-0').value, 'cpu-4');
    assert.equal(ui.find('rm-cart-checkbox').checked, true);
    assert.equal(ui.button('Update cart configuration').disabled, false, 'Unrelated stale browser draft does not block a native cart edit');
    ui.change('resource-0', 'cpu-2');
    await ui.clock.advance(300);
    ui.button('Update cart configuration').click();
    await ui.clock.flush();
    const call = ui.calls.find((item) => item.url === '/checkout-sync');
    assert.equal(call.body.edit_key, editItem.key);
    assert.equal(call.body.edit_token, 'token-1');
    assert.deepEqual(JSON.parse(call.body.items), [{ pid: 1, months: 24, choices: { cpu: 'cpu-2', antivirus: 'av-yes' }, revision: 'r1' }]);
    assert.deepEqual(ui.saved(), [savedItem({ revision: 'old' })]);
    assert.equal(ui.window.location.destination, 'https://rackmonk.test/cart/');
});

test('failed session edit keeps original draft and allows retry; unavailable item never falls back to adding', async () => {
    const ui = setup({ checkout: true, editItem: savedItem({ key: 'b'.repeat(32), quantity: 1, token: 'stale' }), checkoutHandler: () => ({ ok: false, json: async () => ({ success: false, data: { message: 'Cart item changed in another tab.' } }) }) });
    await ui.clock.advance();
    ui.button('Update cart configuration').click();
    await ui.clock.flush();
    assert.match(ui.find('rm-cart-quote-status').textContent, /another tab/);
    assert.equal(ui.button('Update cart configuration').disabled, false);
    assert.equal(ui.window.location.destination, undefined);
    const missing = setup({ checkout: true, editError: 'This plan is no longer in your cart.' });
    await missing.clock.advance();
    assert.match(missing.find('rm-cart-session-notice').textContent, /no longer/);
    assert.equal(missing.add(), null);
    assert.equal(missing.calls.length, 0);
});

test('checkout failure preserves saved cart and permits retry', async () => {
    const ui = setup({ checkout: true, saved: [savedItem()], checkoutHandler: () => ({ ok: false, json: async () => ({ success: false, data: { message: 'Plan changed. Review resources.' } }) }) });
    await ui.clock.advance();
    ui.button('Your cart (1)').click();
    await ui.clock.advance();
    ui.button('Proceed to checkout').click();
    await ui.clock.flush();
    assert.match(ui.find('rm-cart-checkout-status').textContent, /Plan changed/);
    assert.deepEqual(ui.saved(), [savedItem()]);
    assert.equal(ui.button('Proceed to checkout').disabled, false);
    assert.equal(ui.window.location.destination, undefined);
});

test('checkout blocks unverified plans, mixed currencies and cross-origin redirects', async () => {
    const mixed = setup({ checkout: true, saved: [savedItem(), savedItem({ key: 'usd', pid: 2 })] });
    await mixed.clock.advance();
    assert.equal(mixed.button('Proceed to checkout').disabled, true);
    const stale = setup({ checkout: true, saved: [savedItem({ revision: 'old' })] });
    await stale.clock.advance();
    assert.equal(stale.button('Proceed to checkout').disabled, true);
    const ui = setup({ checkout: true, saved: [savedItem()], checkoutHandler: () => ({ ok: true, json: async () => ({ success: true, data: { redirect: 'https://untrusted.test/pay' } }) }) });
    await ui.clock.advance();
    ui.button('Your cart (1)').click();
    await ui.clock.advance();
    ui.button('Proceed to checkout').click();
    await ui.clock.flush();
    assert.deepEqual(ui.saved(), [savedItem()]);
    assert.match(ui.find('rm-cart-checkout-status').textContent, /address could not be verified/);
    assert.equal(ui.window.location.destination, undefined);
});

test('quote lifecycle: authenticated POST, debounce, resource prices, and term totals', async () => {
    const ui = setup();
    assert.equal(ui.add().disabled, true);
    assert.equal(ui.calls.length, 0);
    await ui.clock.advance();
    assert.equal(ui.add().disabled, false);
    const { url, request, body } = ui.calls[0];
    assert.equal(url, '/quote');
    assert.equal(request.method, 'POST');
    assert.equal(request.credentials, 'same-origin');
    assert.equal(request.headers['X-WP-Nonce'], 'test-nonce');
    assert.equal(request.headers['Content-Type'], 'application/json');
    assert.deepEqual(body, { pid: 1, months: 12, choices: { cpu: 'cpu-2', antivirus: 'av-no' }, revision: 'r1' });
    assert.match(ui.find('rm-cart-summary').textContent, /3,588\.00/);
    ui.change('resource-0', 'cpu-4');
    assert.equal(ui.add().disabled, true);
    await ui.clock.advance(150);
    ui.change('resource-0', 'cpu-2');
    await ui.clock.advance(299);
    assert.equal(ui.calls.length, 1);
    await ui.clock.advance(1);
    assert.equal(ui.calls.length, 2);
    const antivirus = ui.find('rm-cart-checkbox');
    antivirus.checked = true;
    antivirus.dispatch('change');
    await ui.clock.advance(300);
    assert.equal(ui.calls.at(-1).body.choices.antivirus, 'av-yes');
    assert.match(ui.find('rm-cart-summary').textContent, /4,188\.00/);
    ui.change('duration', '36');
    await ui.clock.advance(250);
    assert.equal(ui.calls.at(-1).body.months, 36);
    assert.match(ui.find('rm-cart-summary').textContent, /12,564\.00/);
});

test('failed quotes immediately invalidate old prices and cannot add until retry succeeds', async () => {
    const ui = setup();
    await ui.clock.advance();
    ui.setHandler(() => ui.errorResponse('Pricing is offline.', 503));
    ui.change('resource-0', 'cpu-4');
    ui.add().click();
    assert.equal(ui.saved().length, 0);
    await ui.clock.advance(300);
    assert.equal(ui.add().disabled, true);
    assert.equal(ui.find('rm-cart-quote-status').textContent, 'Pricing is offline.');
    ui.add().click();
    assert.equal(ui.saved().length, 0);
    ui.setHandler(null);
    ui.button('Retry pricing').click();
    await ui.clock.advance();
    assert.equal(ui.add().disabled, false);
    assert.match(ui.find('rm-cart-summary').textContent, /4,788\.00/);
});

test('aborted late responses cannot replace the latest quote or enable Add while it is pending', async () => {
    const ui = setup();
    await ui.clock.advance();
    let finishOld;
    ui.setHandler((body, request, response) => new Promise((resolve) => { finishOld = () => resolve(response(body)); }));
    ui.change('resource-0', 'cpu-4');
    await ui.clock.advance(300);
    const oldSignal = ui.calls.at(-1).request.signal;
    let finishNew;
    ui.setHandler((body, request, response) => new Promise((resolve) => { finishNew = () => resolve(response(body)); }));
    ui.change('resource-0', 'cpu-2');
    assert.equal(oldSignal.aborted, true);
    await ui.clock.advance(300);
    finishOld();
    await ui.clock.flush();
    assert.equal(ui.add().disabled, true);
    assert.equal(ui.saved().length, 0);
    finishNew();
    await ui.clock.flush();
    assert.equal(ui.add().disabled, false);
    assert.match(ui.find('rm-cart-summary').textContent, /3,588\.00/);
    assert.doesNotMatch(ui.find('rm-cart-summary').textContent, /4,788\.00/);
});

test('timeouts and malformed authoritative prices leave Add disabled', async () => {
    const ui = setup({ handler: (body, request) => new Promise((resolve, reject) => {
        request.signal.addEventListener('abort', () => { const error = new Error('Aborted'); error.name = 'AbortError'; reject(error); });
    }) });
    await ui.clock.advance();
    await ui.clock.advance(20000);
    assert.equal(ui.add().disabled, true);
    assert.match(ui.find('rm-cart-quote-status').textContent, /timed out/);
    ui.setHandler(async (body, request, response) => {
        const quote = await response(body).json();
        quote.monthly = -1;
        return { ok: true, json: async () => quote };
    });
    ui.button('Retry pricing').click();
    await ui.clock.advance();
    assert.equal(ui.add().disabled, true);
    assert.match(ui.find('rm-cart-quote-status').textContent, /could not be verified/);
});

test('late successful or failed old requests cannot overwrite a newer confirmed quote', async () => {
    for (const failOld of [false, true]) {
        const ui = setup();
        await ui.clock.advance();
        let finishOld;
        ui.setHandler((body, request, response) => new Promise((resolve, reject) => {
            finishOld = () => failOld ? reject(new Error('Old request failed')) : resolve(response(body));
        }));
        ui.change('resource-0', 'cpu-4');
        await ui.clock.advance(300);
        ui.setHandler(null);
        ui.change('resource-0', 'cpu-2');
        await ui.clock.advance(300);
        assert.equal(ui.add().disabled, false);
        const confirmedSummary = ui.find('rm-cart-summary').textContent;
        finishOld();
        await ui.clock.flush();
        assert.equal(ui.add().disabled, false);
        assert.equal(ui.find('rm-cart-summary').textContent, confirmedSummary);
        ui.add().click();
        assert.equal(ui.saved()[0].choices.cpu, 'cpu-2');
    }
});

test('cart saves only selections; reload re-quotes; edit, remove, and clear persist', async () => {
    const ui = setup();
    await ui.clock.advance();
    ui.add().click();
    const item = ui.saved()[0];
    assert.deepEqual(Object.keys(item).sort(), ['choices', 'key', 'months', 'pid', 'revision']);
    assert.equal(item.months, 12);
    assert.equal(ui.find('rm-cart-cart').hidden, false);
    assert.match(ui.find('rm-cart-cart-total').textContent, /3,588\.00/);
    assert.match(ui.find('rm-cart-cart-total').textContent, /Totals exclude taxes/);
    const reload = setup({ saved: ui.saved() });
    assert.match(reload.find('rm-cart-cart-total').textContent, /unavailable/);
    await reload.clock.advance();
    assert.ok(reload.calls.some((call) => call.body.months === item.months && call.body.pid === item.pid));
    reload.button('Your cart (1)').click();
    await reload.clock.advance();
    reload.button('Edit plan').click();
    await reload.clock.advance();
    reload.change('duration', '24');
    await reload.clock.advance(250);
    reload.button('Save cart changes').click();
    assert.equal(reload.saved().length, 1);
    assert.equal(reload.saved()[0].key, item.key);
    assert.equal(reload.saved()[0].months, 24);
    reload.button('Remove').click();
    assert.deepEqual(reload.saved(), []);
    reload.button('Configure a plan').click();
    reload.add().click();
    assert.equal(reload.saved().length, 1);
    reload.button('Clear cart').click();
    assert.deepEqual(reload.saved(), []);
});

test('stale saved items show no price and can be repaired through explicit editing', async () => {
    const ui = setup({ saved: [savedItem({ revision: 'old' })] });
    await ui.clock.advance();
    assert.equal(ui.add().disabled, true);
    assert.match(ui.find('rm-cart-cart-total').textContent, /unavailable/);
    assert.match(ui.find('rm-cart-item').textContent, /stale/);
    assert.doesNotMatch(ui.find('rm-cart-item').textContent, /299\.00/);
    ui.button('Your cart (1)').click();
    await ui.clock.advance();
    ui.button('Edit plan').click();
    await ui.clock.advance();
    assert.equal(ui.button('Save cart changes').disabled, false);
    ui.button('Save cart changes').click();
    assert.equal(ui.saved()[0].revision, 'r1');
    assert.match(ui.find('rm-cart-cart-total').textContent, /299\.00/);
});

test('unknown saved choices and disabled plans show quote errors, never fabricated totals', async () => {
    const ui = setup({ saved: [savedItem({ choices: { cpu: 'deleted', antivirus: 'av-no' } }), savedItem({ key: 'disabled', pid: 999 })] });
    await ui.clock.advance();
    assert.match(ui.find('rm-cart-cart').textContent, /Unknown resource choice/);
    assert.match(ui.find('rm-cart-cart').textContent, /This plan is disabled/);
    assert.match(ui.find('rm-cart-cart-total').textContent, /unavailable/);
    assert.equal(ui.add().disabled, true);
});

test('unavailable requested plan requires explicit selection, with no default quote or Add', async () => {
    const ui = setup({ requestedPlan: 999 });
    await ui.clock.advance();
    assert.equal(ui.calls.length, 0);
    assert.equal(ui.field('plan').value, '');
    assert.equal(ui.add(), null);
    assert.match(ui.find('rm-cart-configure').textContent, /requested hosting plan is unavailable/);
    const warning = ui.find('rm-cart-configure').querySelector('.rm-cart-alert');
    assert.equal(warning.hidden, false);
    assert.equal(warning.getAttribute('role'), 'alert');
    assert.equal(ui.field('plan').getAttribute('aria-describedby'), warning.id);
    assert.equal(ui.field('plan').children[0].disabled, true);
    ui.change('plan', '');
    await ui.clock.advance();
    assert.equal(ui.calls.length, 0);
    ui.change('plan', '2');
    assert.equal(ui.add().disabled, true);
    await ui.clock.advance();
    assert.equal(ui.calls[0].body.pid, 2);
    assert.equal(ui.add().disabled, false);
    assert.equal(ui.document.activeElement, ui.field('plan'));
    ui.add().click();
    assert.equal(ui.saved()[0].pid, 2);
});

test('zero requested plan retains normal default; empty catalog stays usable for saved-cart review', async () => {
    const normal = setup({ requestedPlan: 0 });
    await normal.clock.advance();
    assert.equal(normal.calls[0].body.pid, 1);
    assert.equal(normal.add().disabled, false);
    const empty = setup({ requestedPlan: 999, catalog: [], saved: [savedItem()] });
    await empty.clock.advance();
    assert.match(empty.find('rm-cart-configure').textContent, /requested hosting plan is unavailable/);
    assert.equal(empty.add(), null);
    empty.button('Your cart (1)').click();
    await empty.clock.advance();
    assert.match(empty.find('rm-cart-cart-total').textContent, /unavailable/);
    assert.equal(empty.button('Edit plan').disabled, true);
    empty.button('Remove').click();
    assert.deepEqual(empty.saved(), []);
});

test('storage read/write failures show a notice while cart actions continue in memory', async () => {
    for (const failure of [{ storageFails: true }, { storageWriteFails: true }]) {
        const ui = setup(failure);
        await ui.clock.advance();
        ui.add().click();
        assert.equal(ui.find('rm-cart-storage').hidden, false);
        assert.match(ui.find('rm-cart-storage').textContent, /works in memory/);
        assert.ok(ui.button('Your cart (1)'));
        ui.button('Remove').click();
        assert.ok(ui.button('Your cart (0)'));
    }
});

test('mixed-currency additions are blocked and loaded currency totals stay separate', async () => {
    const ui = setup();
    await ui.clock.advance();
    ui.add().click();
    ui.button('01 Configure').click();
    ui.change('plan', '2');
    await ui.clock.advance();
    ui.add().click();
    assert.equal(ui.saved().length, 1);
    assert.match(ui.find('rm-cart-feedback').textContent, /another currency/);
    const loaded = setup({ saved: [savedItem(), savedItem({ key: 'usd', pid: 2 })] });
    await loaded.clock.advance();
    assert.match(loaded.find('rm-cart-cart-total').textContent, /Cart total \(INR\)/);
    assert.match(loaded.find('rm-cart-cart-total').textContent, /Cart total \(USD\)/);
});

test('cart enforces twenty items and ignores authoritative-looking stored prices', async () => {
    const saved = Array.from({ length: 20 }, (_,index) => savedItem({ key: 'item-' + index, monthly: 1, total: 1 }));
    const ui = setup({ saved });
    await ui.clock.advance();
    assert.equal(ui.add().disabled, true);
    assert.match(ui.find('rm-cart-feedback').textContent, /20-plan limit/);
    assert.match(ui.find('rm-cart-cart-total').textContent, /5,980\.00/);
    ui.add().click();
    assert.equal(ui.saved().length, 20);
});

test('data is parsed without deletion, strings stay text, and tabs support keyboard navigation', async () => {
    const ui = setup();
    await ui.clock.advance();
    assert.ok(ui.root.querySelector('script'));
    assert.equal(ui.root.querySelector('img'), null);
    assert.match(ui.find('rm-cart-plan-name').textContent, /<img/);
    assert.match(ui.find('rm-cart-summary-note').textContent, /Prices exclude taxes/);
    assert.equal(ui.find('rm-cart-disclaimer'), null);
    const configure = ui.button('01 Configure');
    const cart = ui.button('Your cart (0)');
    let prevented = false;
    configure.dispatch('keydown', { key: 'ArrowRight', preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(cart.getAttribute('aria-selected'), 'true');
    assert.equal(ui.document.activeElement, cart);
    cart.dispatch('keydown', { key: 'Home', preventDefault() {} });
    assert.equal(configure.getAttribute('aria-selected'), 'true');
    assert.equal(ui.document.activeElement, configure);
});

test('native pricing shows actual term amounts and setup fees without monthly estimates', async () => {
    const prices = { 1: { recurring: '100.00', setup: '20.00' }, 12: { recurring: '900.00', setup: '30.00' }, 24: null, 36: null };
    const addon = { 1: { recurring: '50.00', setup: '5.00' }, 12: { recurring: '300.00', setup: '10.00' }, 24: null, 36: null };
    const plan = { pid: 1, name: 'Native', currency: 'INR', symbol: 'Rs', base_price: '100.00', native_pricing: true, prices, revision: 'r1', resources: { option_7: { label: 'Memory', type: 'select', choices: [{ id: '10', label: '8 GB', price: '50.00', prices: addon }] } } };
    const ui = setup({ catalog: [plan], requestedTerm: 12, handler: async body => ({ ok: true, json: async () => ({
        pid: 1, name: 'Native', currency: 'INR', months: body.months, revision: 'r1', native_pricing: true,
        choices: body.choices, selection: [{ label: 'Memory', choice: '8 GB', price: 25, term_price: 300, setup: 10 }],
        base_monthly: 75, resources_monthly: 25, monthly: 100, base_term: 900, resources_term: 300, recurring: 1200, setup: 40, total: 1240
    }) }) });
    assert.match(ui.find('rm-cart-summary').textContent, /Checking current pricing/);
    assert.doesNotMatch(ui.find('rm-cart-summary').textContent, /Estimated total/);
    await ui.clock.advance();
    assert.match(ui.find('rm-cart-summary').textContent, /Setup fees/);
    assert.match(ui.find('rm-cart-summary').textContent, /1,240\.00/);
    assert.match(ui.field('resource-0').children[0].textContent, /300\.00 \/ term/);
    assert.equal(ui.field('duration').children[2].disabled, true);
});

test('48-month duration stays native-only even when saved manual prices exist', async () => {
    const price = { recurring: '4800.00', setup: '0.00' };
    const plan = { pid: 1, name: 'Native', currency: 'INR', base_price: '100', native_pricing: true, prices: { 1: price, 48: price }, revision: 'r1', resources: { option_7: { label: 'Memory', type: 'select', choices: [{ id: '10', label: '8 GB', price: '0', prices: { 1: price } }] } } };
    const unavailable = setup({ catalog: [plan], requestedTerm: 1 });
    assert.equal(unavailable.field('duration').children[4].disabled, true);
    plan.resources.option_7.choices[0].prices[48] = price;
    const available = setup({ catalog: [plan], requestedTerm: 48 });
    assert.equal(available.field('duration').children[4].disabled, true);
    assert.match(available.find('rm-cart-configure').textContent, /purchased directly through WHMCS/);
});
