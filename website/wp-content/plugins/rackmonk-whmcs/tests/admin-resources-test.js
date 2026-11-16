// Minimal DOM doubles for resource editor events; no browser or live writes required.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const element = (properties = {}) => ({
    value: '', events: {}, textContent: '', hidden: false,
    addEventListener(type, callback) { this.events[type] = callback; },
    ...properties
});
const fields = Object.fromEntries(['rm-plan-search', 'rm-category-filter', 'rm-empty-state', 'rm-results-count', 'in_monthly', 'in_annually', 'in_biennially', 'in_triennially', 'in_usd_monthly', 'in_usd_annually', 'in_usd_biennially', 'in_usd_triennially'].map(id => ['#' + id, element()]));
const plans = [element({ dataset: { gid: '1', search: 'Shared Starter SSL' } }), element({ dataset: { gid: '2', search: 'Cloud Pro NVMe' } })];
const rows = element({ children: [], get firstElementChild() { return this.children[0]; }, appendChild(row) { this.children.push(row); } });
// Object spread evaluates accessors; restore the dynamic getter for this DOM double.
Object.defineProperty(rows, 'firstElementChild', { get() { return this.children[0]; } });
function makeRow(index, id = '10', label = '2 vCPU', price = '100') {
    const inputs = [['hidden', 'id', id], ['text', 'label', label], ['number', 'price', price]].map(([type, key, value]) => ({
        type, name: `resources[cpu][${index}][${key}]`, value, focus() { this.focused = true; }
    }));
    return {
        inputs,
        querySelectorAll() { return inputs; },
        querySelector() { return inputs[1]; },
        cloneNode() { return makeRow(index, inputs[0].value, inputs[1].value, inputs[2].value); },
        remove() { rows.children.splice(rows.children.indexOf(this), 1); }
    };
}
rows.children.push(makeRow(0));
const add = element();
const card = { dataset: { resource: 'cpu', nextIndex: '1' }, querySelector(selector) { return selector === '[data-resource-rows]' ? rows : add; } };
const dashboard = {
    addEventListener() {},
    querySelector(selector) { return fields[selector]; },
    querySelectorAll(selector) { return ({ '[data-rm-plan]': plans, '[data-rm-delete]': [], '[data-rm-copy]': [], '[data-resource]': [card] })[selector] || []; }
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../assets/js/admin.js'), 'utf8'), { document: { querySelector() { return dashboard; } } });

add.events.click();
assert.equal(rows.children.length, 2);
assert.deepEqual(rows.children[1].inputs.map(input => input.value), ['0', '', '']);
assert.equal(rows.children[1].inputs[1].name, 'resources[cpu][1][label]');
assert.equal(rows.children[1].inputs[1].focused, true);
for (let i = 2; i < 30; i++) add.events.click();
assert.equal(rows.children.length, 30);
assert.equal(add.disabled, true);
add.events.click();
assert.equal(rows.children.length, 30);
function remove(row) { rows.events.click({ target: { closest() { return { closest() { return row; } }; } } }); }
remove(rows.children[29]);
assert.equal(add.disabled, false);
add.events.click();
assert.equal(rows.children[29].inputs[1].name, 'resources[cpu][30][label]');
while (rows.children.length > 1) remove(rows.children[1]);
remove(rows.children[0]);
assert.equal(rows.children.length, 1);
assert.deepEqual(rows.children[0].inputs.map(input => input.value), ['0', '', '']);
add.events.click();
assert.equal(rows.children.length, 2);

fields['#rm-plan-search'].value = 'ssl';
fields['#rm-plan-search'].events.input();
assert.equal(plans[0].hidden, false);
assert.equal(plans[1].hidden, true);
fields['#rm-category-filter'].value = '2';
fields['#rm-category-filter'].events.change();
assert.equal(fields['#rm-empty-state'].hidden, false);
fields['#in_monthly'].value = '299';
fields['#in_monthly'].events.input();
assert.equal(fields['#in_annually'].placeholder, '2152.80');
fields['#in_usd_monthly'].value = '9.99';
fields['#in_usd_monthly'].events.input();
assert.equal(fields['#in_usd_annually'].placeholder, '71.93');
assert.equal(fields['#in_annually'].placeholder, '2152.80', 'USD input never replaces an INR amount');
assert.equal(fields['#in_monthly'].value, '299');
console.log('Resource add/remove, ID resets, unique indices, limits, focus and existing search/pricing interactions passed.');
console.log('VERDICT: PASS');
