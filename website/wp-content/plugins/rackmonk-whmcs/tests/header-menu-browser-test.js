'use strict';
const assert = require('node:assert/strict');

(async function () {
    const target = await (await fetch('http://127.0.0.1:9222/json/new?about:blank', { method: 'PUT' })).json();
    const socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }));
    let next = 0;
    const requests = new Map();
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (!requests.has(message.id)) return;
        const { resolve, reject, timer } = requests.get(message.id);
        requests.delete(message.id);
        clearTimeout(timer);
        message.error ? reject(new Error(message.error.message)) : resolve(message.result);
    });
    function command(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++next;
            const timer = setTimeout(() => reject(new Error(method + ' timeout')), 25000);
            requests.set(id, { resolve, reject, timer });
            socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async function evaluate(expression) {
        const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
    async function resize(width) {
        await command('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width < 992 });
        await evaluate('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
    }
    try {
        await command('Page.enable');
        await resize(1440);
        await command('Page.navigate', { url: 'http://localhost/rackmonk_wordpress/website/' });
        await evaluate(`new Promise((resolve, reject) => {
            const start = Date.now();
            function check() {
                const header = document.querySelector('.rm-site-header.rm-nav-ready');
                const image = header?.querySelector('img.custom-logo');
                if (image?.complete && image.naturalWidth) return resolve(true);
                if (Date.now() - start > 20000) return reject(new Error('Header/logo not ready'));
                requestAnimationFrame(check);
            }
            check();
        })`);
        const desktop = await evaluate(`(() => {
            const header = document.querySelector('.rm-site-header');
            const menu = header.querySelector('.rm-site-menu');
            const logo = header.querySelector('.custom-logo');
            return {
                titles: Array.from(menu.children).map(li => li.querySelector('a').textContent.trim()),
                login: menu.querySelector('.rm-menu-login > a').href,
                width: header.scrollWidth, viewport: innerWidth,
                logoWidth: logo.getBoundingClientRect().width,
                toggle: getComputedStyle(header.querySelector('.rm-menu-toggle')).display
            };
        })()`);
        assert.deepEqual(desktop.titles, ['Home', 'Hosting', 'About Us', 'Contact Us', 'Client Area']);
        assert.ok(desktop.login.endsWith('/my-account/'));
        assert.ok(desktop.logoWidth >= 180 && desktop.width <= desktop.viewport);
        assert.equal(desktop.toggle, 'none');
        console.log('PASS: Desktop renders original menu structure, Rackmonk logo and local Login CTA');
        const dropdown = await evaluate(`(() => {
            const item = document.querySelector('.rm-site-menu > .menu-item-has-children');
            item.querySelector('a').focus();
            return {display: getComputedStyle(item.querySelector('.sub-menu')).display, count: item.querySelectorAll('.sub-menu > li').length};
        })()`);
        assert.notEqual(dropdown.display, 'none');
        assert.equal(dropdown.count, 5);
        console.log('PASS: Desktop Hosting dropdown supports keyboard focus and five website links');
        for (const width of [820, 390]) {
            await resize(width);
            const closed = await evaluate(`(() => {
                const header = document.querySelector('.rm-site-header');
                return {nav: getComputedStyle(header.querySelector('nav')).display, toggle: getComputedStyle(header.querySelector('.rm-menu-toggle')).display, width: header.scrollWidth, viewport: innerWidth};
            })()`);
            assert.equal(closed.nav, 'none');
            assert.notEqual(closed.toggle, 'none');
            assert.ok(closed.width <= closed.viewport);
            const opened = await evaluate(`(() => {
                const header = document.querySelector('.rm-site-header');
                header.querySelector('.rm-menu-toggle').click();
                const button = header.querySelector('.rm-submenu-toggle');
                button.focus();
                button.click();
                return {expanded: header.querySelector('.rm-menu-toggle').getAttribute('aria-expanded'), submenu: getComputedStyle(document.getElementById(button.getAttribute('aria-controls'))).display};
            })()`);
            assert.equal(opened.expanded, 'true');
            assert.notEqual(opened.submenu, 'none');
            await command('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
            const escaped = await evaluate(`document.querySelector('.rm-menu-toggle').getAttribute('aria-expanded')`);
            assert.equal(escaped, 'false');
            console.log('PASS: ' + width + 'px navigation opens, toggles dropdowns and closes with Escape without header overflow');
        }
        await command('Page.navigate', { url: desktop.login });
        await evaluate(`new Promise((resolve, reject) => {
            const start = Date.now();
            function check() {
                if (document.querySelector('form.woocommerce-form-login')) return resolve(true);
                if (Date.now() - start > 15000) return reject(new Error('Website login form missing'));
                requestAnimationFrame(check);
            }
            check();
        })`);
        console.log('PASS: Login opens the working website account login form');
        console.log('VERDICT: PASS');
    } finally {
        await command('Page.close');
        socket.close();
    }
})().catch(error => { console.error('VERDICT: FAIL -- ' + error.message); process.exitCode = 1; });
