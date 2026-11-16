'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');

// Read-only browser exercise: never submit forms; intercept non-GET and external requests.
(async function () {
    const base = 'http://localhost/rackmonk_wordpress/website';
    const screenshotDirectory = 'C:\\Users\\DELL\\AppData\\Local\\Temp\\kilo';
    assert.ok(fs.statSync(screenshotDirectory).isDirectory(), 'Approved screenshot directory must exist');
    const target = await (await fetch('http://127.0.0.1:9222/json/new?about:blank', { method: 'PUT' })).json();
    const socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true });
        socket.addEventListener('error', reject, { once: true });
    });
    let next = 0;
    const requests = new Map();
    const blocked = [];
    const failures = [];
    const interceptionErrors = [];
    let checks = 0;
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.method === 'Fetch.requestPaused') {
            const request = message.params.request;
            const allowed = request.method === 'GET' && new URL(request.url).origin === new URL(base).origin;
            if (!allowed) blocked.push({ method: request.method, url: request.url });
            command(allowed ? 'Fetch.continueRequest' : 'Fetch.failRequest', {
                requestId: message.params.requestId,
                ...(allowed ? {} : { errorReason: 'BlockedByClient' })
            }).catch(error => interceptionErrors.push(error.message));
        }
        if (!requests.has(message.id)) return;
        const { resolve, reject, timer } = requests.get(message.id);
        requests.delete(message.id);
        clearTimeout(timer);
        message.error ? reject(new Error(message.error.message)) : resolve(message.result);
    });
    function command(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++next;
            const timer = setTimeout(() => {
                requests.delete(id);
                reject(new Error(method + ' timeout'));
            }, 25000);
            requests.set(id, { resolve, reject, timer });
            socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async function evaluate(expression) {
        const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
    function check(condition, label, details) {
        checks++;
        if (!condition) failures.push(label);
        console.log((condition ? 'PASS: ' : 'FAIL: ') + label + (details ? ' ' + JSON.stringify(details) : ''));
    }
    async function resize(width) {
        await command('Emulation.setDeviceMetricsOverride', { width, height: width === 1440 ? 1200 : 1000, deviceScaleFactor: 1, mobile: width < 680 });
        await evaluate('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
    }
    async function navigate(path, mode) {
        const url = base + path;
        await command('Page.navigate', { url });
        const start = Date.now();
        while (true) {
            try {
                if (await evaluate(`location.href === ${JSON.stringify(url)} && document.readyState === 'complete' && document.querySelector('.rm-auth')?.dataset.authMode === ${JSON.stringify(mode)} && document.querySelector('.rm-auth')?.dataset.rmAuthReady === 'true'`)) break;
            } catch (error) {
                if (Date.now() - start > 20000) throw error;
            }
            if (Date.now() - start > 20000) throw new Error('Page not ready: ' + path);
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        await evaluate('document.fonts.ready.then(() => true)');
        await evaluate(`new Promise((resolve, reject) => {
            const start = Date.now();
            function ready() {
                const loader = document.getElementById('preloader');
                if (!loader || getComputedStyle(loader).display === 'none') {
                    scrollTo(0, 0);
                    return resolve(true);
                }
                if (Date.now() - start > 10000) return reject(new Error('Theme preloader did not disappear'));
                requestAnimationFrame(ready);
            }
            ready();
        })`);
    }
    async function layout(mode, width) {
        await resize(width);
        const result = await evaluate(`(() => {
            const root = document.querySelector('.rm-auth');
            const story = root.querySelector('.rm-auth-story').getBoundingClientRect();
            const panel = root.querySelector('.rm-auth-form-panel').getBoundingClientRect();
            const controls = [...root.querySelectorAll('input, select, textarea, button')].filter(el => el.getClientRects().length);
            return {
                viewport: innerWidth, documentWidth: document.documentElement.scrollWidth,
                columns: getComputedStyle(root).gridTemplateColumns,
                sideBySide: story.right <= panel.left + 1 && Math.abs(story.top - panel.top) < 2,
                stacked: panel.top >= story.bottom - 1,
                badControls: controls.filter(el => {
                    const r = el.getBoundingClientRect(), s = getComputedStyle(el);
                    if (el.matches('[type=checkbox], [type=radio]')) return r.width < 16 || r.height < 16;
                    if (el.matches('[data-toggle-password]')) return r.width < 40 || r.height < 40;
                    return r.width < 100 || r.height < 44 || (el.tagName !== 'BUTTON' && parseFloat(s.fontSize) < 16) || r.right > innerWidth + 1 || r.left < 0;
                }).map(el => el.id || el.name || el.className),
                overflowElements: [...document.querySelectorAll('body *')].filter(el => {
                    const r = el.getBoundingClientRect();
                    return r.width > 0 && r.right > innerWidth + 2 && getComputedStyle(el).position !== 'fixed';
                }).slice(0, 8).map(el => el.tagName + '.' + el.className)
            };
        })()`);
        check(result.documentWidth <= result.viewport + 1, mode + ' ' + width + 'px has no horizontal overflow', result);
        check(result.badControls.length === 0, mode + ' ' + width + 'px controls are readable and fit', result.badControls);
        check(width <= 680 ? result.stacked : result.sideBySide, mode + ' ' + width + 'px responsive column arrangement');
    }
    async function screenshot(filename, fullPage) {
        const metrics = await command('Page.getLayoutMetrics');
        const content = metrics.cssContentSize;
        const result = await command('Page.captureScreenshot', {
            format: 'png', captureBeyondViewport: fullPage,
            ...(fullPage ? { clip: { x: 0, y: 0, width: content.width, height: content.height, scale: 1 } } : {})
        });
        const path = screenshotDirectory + '\\' + filename;
        fs.writeFileSync(path, Buffer.from(result.data, 'base64'));
        console.log('SCREENSHOT: ' + path);
    }
    try {
        await command('Page.enable');
        await command('Fetch.enable', { patterns: [{ urlPattern: '*', requestStage: 'Request' }] });
        await resize(1440);
        await navigate('/my-account/', 'login');
        const login = await evaluate(`(() => {
            const root = document.querySelector('.rm-auth');
            const header = document.querySelector('.rm-site-header');
            const client = [...header.querySelectorAll('a')].find(a => a.textContent.trim() === 'Client Area');
            return {
                guest: !document.body.classList.contains('logged-in'),
                header: client?.href,
                register: root.querySelector('.rm-auth-switch a')?.href,
                forgot: root.querySelector('.rm-auth-options a')?.href,
                fields: ['rm_username', 'rm_password'].every(id => document.getElementById(id)?.required),
                nonce: !!root.querySelector('[name=woocommerce-login-nonce]'),
                invalid: !root.querySelector('form').checkValidity()
            };
        })()`);
        check(login.guest && login.header === base + '/my-account/', 'Guest header Client Area links to native login', login);
        check(login.register === base + '/register/' && login.forgot === base + '/my-account/lost-password/', 'Login register and forgot links');
        check(login.fields && login.nonce && login.invalid, 'Login required fields and native browser validation');
        const toggle = await evaluate(`(() => {
            const input = document.getElementById('rm_password');
            const button = document.querySelector('[data-toggle-password="rm_password"]');
            const initial = input.type === 'password' && button.type === 'button' && button.getAttribute('aria-label') === 'Show password' && button.getAttribute('aria-pressed') === 'false' && button.getAttribute('aria-controls') === input.id;
            button.click();
            const shown = input.type === 'text' && button.getAttribute('aria-label') === 'Hide password' && button.getAttribute('aria-pressed') === 'true';
            button.click();
            return initial && shown && input.type === 'password' && button.getAttribute('aria-pressed') === 'false';
        })()`);
        check(toggle, 'Password show/hide has accessible labels, controls and pressed state');
        for (const width of [1440, 820, 390]) await layout('login', width);
        await resize(1440);
        await screenshot('rackmonk-login-desktop.png', false);

        await navigate('/register/', 'register');
        const registration = await evaluate(`(() => {
            const form = document.querySelector('.rm-auth-register-form');
            const names = ['firstname', 'lastname', 'email', 'phonenumber', 'companyname', 'address1', 'address2', 'city', 'state', 'country', 'postcode', 'currency', 'password', 'password_confirm', 'terms'];
            const required = [...form.querySelectorAll('[required]')];
            return {
                missing: names.filter(name => !form.elements.namedItem(name)),
                required: required.map(el => el.name),
                inaccessible: required.filter(el => !el.getClientRects().length || !(el.labels?.length)).map(el => el.name),
                custom: form.querySelectorAll('[name^="customfield"]').length,
                securityQuestions: form.querySelector('[name=securityqid]')?.options.length - 1 || 0,
                currencies: form.querySelector('[name=currency]').options.length,
                terms: form.elements.terms.required && form.elements.terms.type === 'checkbox' && form.elements.terms.closest('label').querySelectorAll('a').length === 2,
                invalid: !form.checkValidity(),
                signIn: document.querySelector('.rm-auth-switch a').href,
                honeypotHidden: !form.elements.rm_company_url.getClientRects().length
            };
        })()`);
        check(!registration.missing.length && !registration.inaccessible.length && registration.terms && registration.honeypotHidden, 'Registration fields, labels and required terms exposed; honeypot hidden', registration);
        check(registration.custom === 0 && registration.securityQuestions === 0 && registration.currencies === 2, 'Native WHMCS schema: 0 custom fields, 0 questions, 2 currencies');
        check(registration.invalid && registration.signIn === base + '/my-account/', 'Registration empty browser validation and sign-in link');
        const stateResult = await evaluate(`(() => {
            const country = document.getElementById('rm_country');
            function change(value, code) {
                country.value = value; country.dispatchEvent(new Event('change', { bubbles: true }));
                const state = document.getElementById('rm_state');
                return state.tagName === 'SELECT' && state.name === 'state' && [...state.options].some(option => option.value === code) && !!state.labels.length && state.required === (state.dataset.required !== '0' && state.dataset.required !== 'false');
            }
            return { initialIndia: change('IN', 'MP'), unitedStates: change('US', 'CA'), backIndia: change('IN', 'MP') };
        })()`);
        check(Object.values(stateResult).every(Boolean), 'Country IN to US (CA) to IN (MP) rebuilds state and retains semantics', stateResult);
        const validation = await evaluate(`(() => {
            const form = document.querySelector('.rm-auth-register-form');
            const required = [...form.querySelectorAll('[required]')];
            const missing = required.filter(el => el.tagName !== 'SELECT' && el.type !== 'checkbox').every(el => el.validity.valueMissing);
            for (const el of required) {
                if (el.type === 'checkbox') continue;
                if (el.tagName === 'SELECT') el.value = [...el.options].find(option => option.value)?.value || '';
                else el.value = el.type === 'email' ? 'browser-check@example.test' : el.type === 'password' ? 'Local-only-passphrase-2026!' : el.type === 'tel' ? '+919876543210' : 'Browser check';
            }
            const termsBlocks = !form.checkValidity() && form.elements.terms.validity.valueMissing;
            form.elements.terms.checked = true;
            const valid = form.checkValidity();
            form.elements.email.value = 'not-an-email';
            const badEmail = form.elements.email.validity.typeMismatch && !form.checkValidity();
            form.reset();
            return { missing, termsBlocks, valid, badEmail };
        })()`);
        check(Object.values(validation).every(Boolean), 'Native required, email and terms validation without submitting', validation);
        for (const width of [1440, 820, 390]) await layout('register', width);
        await evaluate('scrollTo(0, 0)');
        await screenshot('rackmonk-register-mobile.png', true);

        await navigate('/my-account/lost-password/', 'forgot');
        const forgot = await evaluate(`(() => {
            const root = document.querySelector('.rm-auth');
            const input = document.getElementById('rm_user_login');
            return !!input && input.name === 'user_login' && input.required && !!input.labels.length && !root.querySelector('form').checkValidity() && !!root.querySelector('[name=woocommerce-lost-password-nonce]') && root.querySelector('.rm-auth-switch a').href === ${JSON.stringify(base + '/my-account/')};
        })()`);
        check(forgot, 'Forgot page uses native user_login, nonce, validation and back-to-sign-in link');
        for (const width of [1440, 820, 390]) await layout('forgot', width);
        await navigate('/my-account/lost-password/?reset-link-sent=true', 'sent');
        const sent = await evaluate(`(() => {
            const root = document.querySelector('.rm-auth');
            return !root.querySelector('form') && !!root.querySelector('.rm-auth-heading').textContent.trim() && root.querySelector('.rm-auth-switch a').href === ${JSON.stringify(base + '/my-account/')};
        })()`);
        check(sent, 'Reset-link-sent confirmation renders without submitting or sending email');
        for (const width of [1440, 820, 390]) await layout('sent', width);
        check(blocked.every(request => request.method === 'GET') && interceptionErrors.length === 0, 'No attempted non-GET requests; request interception remained active', { blocked, interceptionErrors });
        console.log('RESULT: ' + (checks - failures.length) + '/' + checks + ' checks passed');
        assert.equal(failures.length, 0, failures.join('; '));
        console.log('VERDICT: PASS');
    } finally {
        try { await command('Page.close'); } finally { socket.close(); }
    }
})().catch(error => { console.error('VERDICT: FAIL -- ' + error.message); process.exitCode = 1; });
