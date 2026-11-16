'use strict';

// Node 22+, Chrome CDP on port 9222. Run only after navigation metadata is restored.
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const path = require('node:path');

const base = 'http://localhost/rackmonk_wordpress/website/';
const screenshotDirectory = 'C:\\Users\\DELL\\AppData\\Local\\Temp\\kilo';
const topLabels = ['Home', 'Hosting', 'About Us', 'Contact Us', 'Client Area'];
const cardLabels = ['Shared Hosting', 'Cloud Hosting', 'VPS Hosting', 'WordPress Hosting', 'Migration'];
const cardSlugs = ['shared-hosting-2', 'cloud-hosting', 'vps-hosting', 'wp-hosting', 'migration'];

(async function () {
    const versionResponse = await fetch('http://127.0.0.1:9222/json/version');
    assert.ok(versionResponse.ok, 'Chrome CDP must be available on port 9222');
    const version = await versionResponse.json();
    const socket = new WebSocket(version.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true });
        socket.addEventListener('error', reject, { once: true });
    });
    let next = 0;
    let sessionId;
    let targetId;
    let browserContextId;
    const requests = new Map();
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        const pending = requests.get(message.id);
        if (!pending) return;
        requests.delete(message.id);
        clearTimeout(pending.timer);
        if (message.error) pending.reject(new Error(message.error.message));
        else pending.resolve(message.result);
    });
    function command(method, params = {}, browser = false) {
        return new Promise((resolve, reject) => {
            const id = ++next;
            const timer = setTimeout(() => {
                requests.delete(id);
                reject(new Error(method + ' timeout'));
            }, 25000);
            requests.set(id, { resolve, reject, timer });
            socket.send(JSON.stringify({ id, method, params, ...(!browser && sessionId ? { sessionId } : {}) }));
        });
    }
    async function evaluate(expression) {
        const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        assert.ok(!result.exceptionDetails, JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
    function read(expression) {
        return evaluate(`(() => {
            const header = document.querySelector('.rm-site-header');
            const nav = header.querySelector('.rm-site-nav');
            const menu = nav.querySelector('.rm-site-menu');
            const hosting = [...menu.children].find(li => li.querySelector(':scope > a .rm-nav-label')?.textContent.trim() === 'Hosting');
            const link = hosting.querySelector(':scope > a');
            const button = hosting.querySelector(':scope > .rm-submenu-toggle');
            const panel = hosting.querySelector(':scope > .rm-mega-panel');
            const grid = panel.querySelector('.rm-mega-grid');
            return (${expression});
        })()`);
    }
    // Frame-based waits account for layout and transitions without fixed sleeps.
    async function waitFor(condition, label, timeout = 1000) {
        await evaluate(`new Promise((resolve, reject) => {
            const start = performance.now();
            let consecutive = 0;
            function check() {
                try {
                    consecutive = (${condition}) ? consecutive + 1 : 0;
                    if (consecutive >= 2) return resolve(true);
                    if (performance.now() - start > ${timeout}) return reject(new Error(${JSON.stringify(label)}));
                    requestAnimationFrame(check);
                } catch (error) { reject(error); }
            }
            requestAnimationFrame(check);
        })`);
    }
    async function frames() {
        await evaluate('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(() => resolve(true))))');
    }
    async function panelState(open) {
        await waitFor(`(() => {
            const panel = document.querySelector('.rm-mega-panel');
            const style = getComputedStyle(panel);
            return ${open
                ? "style.display !== 'none' && style.visibility === 'visible' && Number(style.opacity) === 1 && style.pointerEvents !== 'none'"
                : "style.display === 'none' || (style.visibility === 'hidden' && Number(style.opacity) === 0 && style.pointerEvents === 'none')"};
        })()`, 'Panel did not become ' + (open ? 'visible' : 'hidden'));
    }
    async function resize(width, height) {
        await command('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
        await frames();
        assert.equal(await evaluate('innerWidth'), width, 'CSS viewport width');
    }
    async function escape() {
        await command('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
        await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
        await frames();
    }
    async function mouse(x, y, click = false) {
        await command('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y });
        if (click) {
            await command('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 });
            await command('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 });
        }
    }
    async function clickArrow() {
        const point = await read(`(() => { const r = button.getBoundingClientRect(); return {x: r.x + r.width / 2, y: r.y + r.height / 2}; })()`);
        await mouse(point.x, point.y, true);
    }
    async function screenshot(name) {
        assert.ok((await fs.stat(screenshotDirectory)).isDirectory(), 'Screenshot directory must already exist');
        await frames();
        const result = await command('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false, fromSurface: true });
        const filename = path.join(screenshotDirectory, name);
        await fs.writeFile(filename, Buffer.from(result.data, 'base64'));
        console.log('SCREENSHOT: ' + filename);
    }
    async function noOverflow() {
        const sizes = await read(`({header: header.scrollWidth, document: document.documentElement.scrollWidth, viewport: innerWidth})`);
        assert.ok(sizes.header <= sizes.viewport + 1, 'Header horizontal overflow: ' + JSON.stringify(sizes));
        assert.ok(sizes.document <= sizes.viewport + 1, 'Document horizontal overflow: ' + JSON.stringify(sizes));
    }
    async function checkMenu() {
        const structure = await read(`({
            guest: !document.body.classList.contains('logged-in') && !document.querySelector('#wpadminbar'),
            menuId: menu.id,
            labels: [...menu.children].map(li => li.querySelector(':scope > a .rm-nav-label')?.textContent.trim()),
            icons: [...menu.children].map(li => !!li.querySelector(':scope > a .rm-nav-icon svg')),
            topLinks: [...menu.children].map(li => li.querySelector(':scope > a').href),
            allLinks: [...nav.querySelectorAll('a')].map(a => a.href),
            clientColor: getComputedStyle(menu.querySelector('.rm-menu-login > a')).color,
            cards: [...grid.querySelectorAll('a.rm-mega-link')].map(a => ({
                label: a.querySelector('.rm-nav-label')?.textContent.trim(), href: a.href,
                description: a.querySelector('.rm-nav-description')?.textContent.trim(),
                icon: a.dataset.menuIcon, svg: !!a.querySelector('.rm-nav-icon svg')
            })),
            descriptions: grid.querySelectorAll('.rm-nav-description').length,
            overview: panel.querySelector('.rm-mega-overview')?.href,
            help: panel.querySelector('.rm-mega-footer a')?.href,
            controls: button.getAttribute('aria-controls') === panel.id,
            parent: panel.parentElement === hosting
        })`);
        assert.ok(structure.guest, 'Homepage must be tested as a guest');
        assert.match(structure.menuId, /^menu-\S+$/, 'Real WordPress menu ID');
        assert.deepEqual(structure.labels, topLabels, 'Domain/Pages must remain absent from the visible menu');
        assert.ok(structure.icons.every(Boolean), 'Every top-level item has an SVG icon');
        assert.equal(structure.clientColor, 'rgb(255, 255, 255)', 'Client Area text stays readable against its dark CTA background');
        assert.ok(structure.parent && structure.controls, 'Hosting owns its panel and aria-controls target');
        assert.deepEqual(structure.cards.map(card => card.label), cardLabels);
        assert.deepEqual(structure.cards.map(card => card.href), cardSlugs.map(slug => base + slug + '/'));
        assert.deepEqual(structure.cards.map(card => card.icon), ['layers', 'cloud', 'server', 'wordpress', 'migration']);
        assert.equal(structure.descriptions, 5);
        assert.ok(structure.cards.every(card => card.description && card.svg), 'All cards have descriptions and icons');
        assert.ok([base, base + 'home2/'].includes(structure.topLinks[0]), 'Home remains a native homepage link');
        assert.deepEqual(structure.topLinks.slice(1), ['pricing/', 'about-us/', 'contact-us/', 'my-account/'].map(slug => base + slug));
        assert.equal(structure.overview, base + 'pricing/');
        assert.equal(structure.help, base + 'contact-us/');
        for (const href of structure.allLinks) {
            const url = new URL(href);
            assert.equal(url.origin, new URL(base).origin, 'No external/WHMCS destination: ' + href);
            assert.ok(url.pathname.startsWith(new URL(base).pathname), 'Link stays inside the native website: ' + href);
        }
    }
    try {
        // A separate context avoids clearing cookies or interfering with another test tab.
        ({ browserContextId } = await command('Target.createBrowserContext', {}, true));
        ({ targetId } = await command('Target.createTarget', { url: 'about:blank', browserContextId }, true));
        ({ sessionId } = await command('Target.attachToTarget', { targetId, flatten: true }, true));
        await command('Page.enable');
        await resize(1440, 1000);
        const navigation = await command('Page.navigate', { url: base });
        assert.ok(!navigation.errorText, navigation.errorText);
        await waitFor(`(() => {
            const header = document.querySelector('.rm-site-header.rm-nav-ready');
            const logo = header?.querySelector('img.custom-logo');
            const preloader = document.querySelector('#preloader');
            const style = preloader && getComputedStyle(preloader);
            return document.readyState === 'complete' && logo?.complete && logo.naturalWidth > 0 &&
                header.querySelector('.rm-mega-panel') && (!preloader || style.display === 'none' ||
                style.visibility === 'hidden' || (Number(style.opacity) === 0 && style.pointerEvents === 'none'));
        })()`, 'Homepage/header/logo/preloader not ready', 20000);

        for (const width of [1440, 1024]) {
            await resize(width, 1000);
            await mouse(width - 4, 900);
            await read('link.focus()');
            await panelState(true);
            await checkMenu();
            const geometry = await read(`(() => {
                const r = panel.getBoundingClientRect();
                const card = grid.querySelector('.rm-mega-link');
                const c = card.getBoundingClientRect();
                return {left: r.left, right: r.right, width: r.width, viewport: innerWidth,
                    columns: getComputedStyle(grid).gridTemplateColumns.split(' ').length,
                    hit: card.contains(document.elementFromPoint(c.x + c.width / 2, c.y + c.height / 2)),
                    toggle: getComputedStyle(button).display, expanded: button.getAttribute('aria-expanded')};
            })()`);
            assert.equal(geometry.expanded, 'true');
            assert.notEqual(geometry.toggle, 'none', 'Desktop submenu toggle is available');
            assert.equal(geometry.columns, 2);
            assert.ok(geometry.left >= 15 && geometry.right <= geometry.viewport - 15, 'Panel is clamped inside viewport: ' + JSON.stringify(geometry));
            assert.ok(Math.abs(geometry.width - 680) <= 1, 'Desktop panel is 680px');
            assert.ok(geometry.hit, 'First card must not be occluded by the hero');
            await noOverflow();
            if (width === 1440) await screenshot('rackmonk-mega-menu-desktop.png');

            await escape();
            await panelState(false);
            assert.deepEqual(await read(`({expanded: button.getAttribute('aria-expanded'), focused: document.activeElement === link, closed: hosting.classList.contains('rm-dropdown-closed')})`),
                { expanded: 'false', focused: true, closed: true }, 'Escape overrides focus-within');
            await read("menu.querySelector(':scope > li > a').focus()");
            await frames();
            await read('link.focus()');
            await panelState(true);
            await clickArrow();
            await panelState(false);
            assert.equal(await read("button.getAttribute('aria-expanded')"), 'false');
            await clickArrow();
            await panelState(true);
            assert.equal(await read("button.getAttribute('aria-expanded')"), 'true');

            const outside = await read(`(() => {
                for (let y = Math.max(header.getBoundingClientRect().bottom + 30, 650); y < innerHeight - 20; y += 30) {
                    for (const x of [20, innerWidth - 20, innerWidth / 2]) {
                        const hit = document.elementFromPoint(x, y);
                        if (hit && !header.contains(hit) && !hit.closest('a, button, input, select, textarea, form, [role="button"], [onclick]')) return {x, y};
                    }
                }
                return null;
            })()`);
            assert.ok(outside, 'Safe noninteractive outside-click point exists');
            await mouse(outside.x, outside.y, true);
            await panelState(false);
            assert.equal(await read("button.getAttribute('aria-expanded')"), 'false');

            // Exercise the actual pointer path through the 14px hover bridge.
            await evaluate('document.activeElement.blur()');
            const hover = await read(`(() => {
                const l = link.getBoundingClientRect();
                const p = panel.getBoundingClientRect();
                const h = hosting.getBoundingClientRect();
                return {x: l.x + l.width / 2, y: l.y + l.height / 2, bottom: h.bottom, top: p.top};
            })()`);
            await mouse(hover.x, hover.y);
            await panelState(true);
            await mouse(hover.x, (hover.bottom + hover.top) / 2);
            await frames();
            await panelState(true);
            const cardPoint = await read(`(() => { const r = grid.querySelector('.rm-mega-link').getBoundingClientRect(); return {x: r.x + r.width / 2, y: r.y + r.height / 2}; })()`);
            await mouse(cardPoint.x, cardPoint.y);
            await panelState(true);
            await mouse(outside.x, outside.y, true);
            console.log('PASS: ' + width + 'px desktop structure, destinations, icons, geometry, hit-testing, hover bridge, Escape, arrow toggle and outside dismissal');
        }

        for (const width of [390, 820]) {
            await resize(width, 780);
            await evaluate('window.scrollTo(0, 0)');
            await frames();
            assert.equal(await read('getComputedStyle(nav).display'), 'none', 'Mobile navigation starts closed');
            await read("header.querySelector('.rm-menu-toggle').click()");
            await frames();
            assert.equal(await read("header.querySelector('.rm-menu-toggle').getAttribute('aria-expanded')"), 'true');
            await read('(button.focus(), button.click())');
            await panelState(true);
            await checkMenu();
            const mobile = await read(`(() => {
                const style = getComputedStyle(nav);
                const p = panel.getBoundingClientRect();
                const n = nav.getBoundingClientRect();
                return {position: getComputedStyle(panel).position, transform: getComputedStyle(panel).transform,
                    columns: getComputedStyle(grid).gridTemplateColumns.split(' ').length,
                    overflow: style.overflowY, maxHeight: parseFloat(style.maxHeight), expectedMax: innerHeight - 96,
                    scrollHeight: nav.scrollHeight, clientHeight: nav.clientHeight,
                    fits: p.left >= n.left - 1 && p.right <= n.right + 1,
                    buttonWidth: button.getBoundingClientRect().width, buttonHeight: button.getBoundingClientRect().height};
            })()`);
            assert.equal(mobile.position, 'static');
            assert.equal(mobile.transform, 'none');
            assert.equal(mobile.columns, 1);
            assert.equal(mobile.overflow, 'auto', 'Mobile nav scrolls internally');
            assert.ok(Math.abs(mobile.maxHeight - mobile.expectedMax) <= 1, 'Mobile max-height is 100dvh - 96px');
            assert.ok(mobile.scrollHeight > mobile.clientHeight, 'Expanded mobile menu requires internal scrolling');
            assert.ok(mobile.fits && mobile.buttonWidth >= 44 && mobile.buttonHeight >= 44);
            await noOverflow();
            if (width === 390) await screenshot('rackmonk-mega-menu-mobile.png');

            const scroll = await read(`(() => {
                const before = window.scrollY;
                nav.scrollTop = nav.scrollHeight;
                return {before};
            })()`);
            await frames();
            const client = await read(`(() => {
                const a = menu.querySelector('.rm-menu-login > a');
                const r = a.getBoundingClientRect();
                const n = nav.getBoundingClientRect();
                return {scrollTop: nav.scrollTop, documentY: window.scrollY,
                    visible: r.top >= n.top - 1 && r.bottom <= n.bottom + 1 && r.bottom <= innerHeight,
                    hit: a.contains(document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2)),
                    overlayHidden: !document.querySelector('.scroll__top') || getComputedStyle(document.querySelector('.scroll__top')).display === 'none'};
            })()`);
            assert.ok(client.scrollTop > 0 && client.visible && client.hit, 'Client Area is reachable inside the scrolling menu');
            assert.ok(client.overlayHidden, 'Floating page controls do not overlap the open mobile menu');
            assert.equal(client.documentY, scroll.before, 'Internal navigation scroll must not scroll the document');
            await noOverflow();
            await read("menu.querySelector('.rm-menu-login > a').focus({preventScroll: true})");
            await escape();
            assert.deepEqual(await read(`({expanded: header.querySelector('.rm-menu-toggle').getAttribute('aria-expanded'), display: getComputedStyle(nav).display, submenu: button.getAttribute('aria-expanded'), focus: document.activeElement === header.querySelector('.rm-menu-toggle')})`),
                { expanded: 'false', display: 'none', submenu: 'false', focus: true });
            console.log('PASS: ' + width + 'px mobile/tablet static one-column panel, touch target, internal scroll, Client Area reachability, native links and whole-menu Escape');
        }
        console.log('VERDICT: PASS');
    } finally {
        try {
            if (targetId) await command('Target.closeTarget', { targetId }, true);
        } finally {
            try {
                if (browserContextId) await command('Target.disposeBrowserContext', { browserContextId }, true);
            } finally {
                for (const pending of requests.values()) clearTimeout(pending.timer);
                socket.close();
            }
        }
    }
})().catch(error => {
    console.error('VERDICT: FAIL -- ' + (error.stack || error.message));
    process.exitCode = 1;
});
