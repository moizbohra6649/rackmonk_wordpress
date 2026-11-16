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
            const timer = setTimeout(() => reject(new Error(method + ' timeout')), 20000);
            requests.set(id, { resolve, reject, timer });
            socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async function evaluate(expression) {
        const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
    try {
        await command('Page.enable');
        await command('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
        await command('Page.navigate', { url: 'http://localhost/rackmonk_wordpress/website/' });
        await evaluate(`new Promise((resolve, reject) => {
            const started = Date.now();
            function ready() {
                if (document.querySelector('.rackmonk-hosting-active')?.swiper) return resolve(true);
                if (Date.now() - started > 15000) return reject(new Error('Carousel not initialized'));
                requestAnimationFrame(ready);
            }
            ready();
        })`);
        const desktop = await evaluate(`(() => {
            const node = document.querySelector('.rackmonk-hosting-active');
            return { titles: Array.from(node.querySelectorAll('.title')).map(n => n.textContent), count: node.swiper.params.slidesPerView, width: node.getBoundingClientRect().width };
        })()`);
        assert.deepEqual(desktop.titles, ['Shared Hosting', 'Cloud Hosting', 'VPS Hosting', 'WordPress Hosting']);
        assert.equal(desktop.count, 4);
        assert.ok(desktop.width > 0);
        console.log('PASS: Desktop shows four live catalog cards, Swiper initialized');
        await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
        const mobile = await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(() => {
            const node = document.querySelector('.rackmonk-hosting-active');
            node.swiper.update();
            resolve({ count: node.swiper.params.slidesPerView, width: node.querySelector('.swiper-slide').getBoundingClientRect().width, viewport: innerWidth });
        }))))`);
        assert.equal(mobile.count, 1);
        assert.ok(mobile.width > 200 && mobile.width <= mobile.viewport);
        console.log('PASS: Mobile shows one readable card within viewport');
        console.log('VERDICT: PASS');
    } finally {
        await command('Page.close');
        socket.close();
    }
})().catch(error => { console.error('VERDICT: FAIL -- ' + error.message); process.exitCode = 1; });
