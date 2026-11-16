'use strict';
// Dependency-free behavioral harness for the existing jQuery pricing handlers.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/pricing.js'), 'utf8');

function harness() {
    const handlers = {};
    const document = {};
    const window = {};
    const node = (attrs = {}, children = {}) => ({attrs, children, classes: new Set(), text: ''});
    const cycles = Object.fromEntries(['48', '24', '12', '1'].map(term => [term, {
        price_per_month: 50, regular_price: 100, discount_text: 'SAVE 50%', billing_note: term + ' months',
        order_url: term === '48' ? 'https://billing.test/cart.php?a=add&pid=14&currency=2' : 'https://website.test/cart/?plan=encrypted-1&term=' + term
    }]));
    const link = node();
    const card = node({'data-cycles': JSON.stringify(cycles)}, {
        '.order-link-btn': [link], '.price-val': [node()], '.reg-price-val': [node()], '.discount-val': [node()], '.note-val': [node()]
    });
    const container = node({}, {'.rackmonk-plan-card': [card], '.rackmonk-cycle-btn': []});
    const button = node(); button.container = container;
    const widgets = [1, 2, 3, 4].map(style => {
        const price = node(); const cta = node(); const note = node();
        const box = node({}, {'.pricing__price': [price], '.pricing__btn a': [cta], '.pricing__price p': [note], '.price-off': [node()]});
        const widget = node({'data-rm-hostinkar-cycles': JSON.stringify([cycles])}, {'.pricing__box, .pricing__box-four': [box]});
        return {style, widget, price, cta, note};
    });
    function $(value) {
        let nodes = typeof value === 'string' ? (value === '[data-rm-hostinkar-cycles]' ? widgets.map(w => w.widget) : []) : Array.isArray(value) ? value : [value];
        return {
            ready(fn) { fn(); return this; },
            on(event, selector, fn) { handlers[selector] = fn || selector; return this; },
            each(fn) { nodes.forEach((n, i) => fn.call(n, i)); return this; },
            find(selector) { return $(nodes.flatMap(n => n.children?.[selector] || [])); },
            closest() { return $(nodes[0].container); },
            attr(key, value) { if (value === undefined) return nodes[0]?.attrs[key]; nodes.forEach(n => n.attrs[key] = value); return this; },
            removeAttr(key) { nodes.forEach(n => delete n.attrs[key]); return this; },
            data(key) { return nodes[0].attrs['data-' + key]; },
            text(value) { nodes.forEach(n => n.text = value); return this; },
            addClass(name) { nodes.forEach(n => n.classes.add(name)); return this; },
            removeClass(name) { nodes.forEach(n => n.classes.delete(name)); return this; },
            hasClass(name) { return !!nodes[0]?.classes.has(name); },
            show() { return this; }, hide() { return this; }
        };
    }
    vm.runInNewContext(source, {jQuery: $, document, window, console});
    return {cycles, card, link, widgets, switchTerm(term) {
        button.attrs['data-cycle'] = term;
        handlers['.rackmonk-cycle-btn'].call(button, {preventDefault() {}});
    }, syncHostinkar() { handlers['.pricing-tab-switcher, .tab-btn'](); }};
}

test('shortcode 48 -> 12 -> 24 -> 1 -> 48 uses authoritative URLs without rewriting duration', () => {
    const h = harness();
    for (const term of ['48', '12', '24', '1', '48']) {
        h.switchTerm(term);
        assert.equal(h.link.attrs.href, h.cycles[term].order_url);
        assert.equal(h.card.children['.note-val'][0].text, term + ' months');
        assert.ok(!h.link.attrs.href.includes('term=36'));
    }
});
test('unavailable mapping removes stale CTA and shorter term restores it', () => {
    const h = harness();
    h.cycles['48'].available = false; h.cycles['48'].order_url = '';
    h.card.attrs['data-cycles'] = JSON.stringify(h.cycles);
    h.switchTerm('12'); h.switchTerm('48');
    assert.equal(h.link.attrs.href, undefined);
    assert.equal(h.link.attrs['aria-disabled'], 'true');
    assert.equal(h.card.children['.price-val'][0].text, 'Unavailable');
    h.switchTerm('1');
    assert.equal(h.link.attrs.href, h.cycles['1'].order_url);
    assert.equal(h.link.attrs['aria-disabled'], undefined);
});
test('all Hostinkar styles sync initial native URL and repeated annual/default toggles', () => {
    const h = harness();
    for (const widget of h.widgets) assert.equal(widget.cta.attrs.href, h.cycles['48'].order_url);
    for (const annual of [true, false, true, false]) {
        h.widgets.forEach(w => annual ? w.price.classes.add('change-subs-duration') : w.price.classes.delete('change-subs-duration'));
        h.syncHostinkar();
        for (const widget of h.widgets) {
            assert.equal(widget.cta.attrs.href, h.cycles[annual ? '12' : '48'].order_url, 'style ' + widget.style);
            assert.equal(widget.note.text, (annual ? '12' : '48') + ' months');
        }
    }
});
