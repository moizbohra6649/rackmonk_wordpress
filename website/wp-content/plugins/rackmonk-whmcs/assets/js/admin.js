(() => {
    'use strict';

    const dashboard = document.querySelector('.rackmonk-admin-wrap');
    if (!dashboard) return;

    const search = dashboard.querySelector('#rm-plan-search');
    const category = dashboard.querySelector('#rm-category-filter');
    const rows = Array.from(dashboard.querySelectorAll('[data-rm-plan]'));
    const filterPlans = () => {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        rows.forEach((row) => {
            const matches = (!category.value || row.dataset.gid === category.value)
                && row.dataset.search.toLocaleLowerCase().includes(query);
            row.hidden = !matches;
            if (matches) visible++;
        });
        dashboard.querySelector('#rm-empty-state').hidden = visible > 0;
        dashboard.querySelector('#rm-results-count').textContent = `Showing ${visible} of ${rows.length} plans`;
    };
    search.addEventListener('input', filterPlans);
    category.addEventListener('change', filterPlans);

    ['in_', 'in_usd_'].forEach((prefix) => {
        const monthly = dashboard.querySelector(`#${prefix}monthly`);
        if (!monthly) return;
        monthly.addEventListener('input', () => {
            const price = parseFloat(monthly.value);
            [['annually', 12, 0.60], ['biennially', 24, 0.45], ['triennially', 48, 0.35]].forEach(([term, months, rate]) => {
                dashboard.querySelector(`#${prefix}${term}`).placeholder = price > 0
                    ? (price * months * rate).toFixed(2)
                    : 'Auto-calculated';
            });
        });
    });

    dashboard.querySelectorAll('[data-rm-delete]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!window.confirm('Are you sure you want to delete this plan from WHMCS?')) event.preventDefault();
        });
    });

    dashboard.querySelectorAll('[data-rm-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const code = document.getElementById(button.dataset.rmCopy);
            const status = dashboard.querySelector('#rm-copy-status');
            try {
                if (!navigator.clipboard || !window.isSecureContext) throw new Error('Clipboard unavailable');
                await navigator.clipboard.writeText(code.textContent.trim());
                status.textContent = 'Shortcode copied to clipboard.';
            } catch (error) {
                const selection = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(code);
                selection.removeAllRanges();
                selection.addRange(range);
                status.textContent = 'Shortcode selected. Press Ctrl+C (Cmd+C on Mac) to copy.';
            }
        });
    });

    const drafts = new WeakSet();
    const markDraft = (target) => {
        const form = target.closest?.('form[method="post"]');
        if (form) drafts.add(form);
    };
    const hasDraft = (root) => Array.from(root.querySelectorAll('form[method="post"]')).some((form) => drafts.has(form));
    dashboard.addEventListener('input', (event) => markDraft(event.target));
    dashboard.addEventListener('change', (event) => markDraft(event.target));

    const initializeResources = (scope) => scope.querySelectorAll('[data-resource]').forEach((card) => {
        const rows = card.querySelector('[data-resource-rows]');
        const addButton = card.querySelector('[data-add-choice]');
        const limit = Number(card.dataset.maxChoices) || 30;
        const refreshLimit = () => { addButton.disabled = rows.children.length >= limit; };
        addButton.addEventListener('click', () => {
            if (rows.children.length >= limit) return;
            const row = rows.firstElementChild.cloneNode(true);
            const index = Number(card.dataset.nextIndex);
            card.dataset.nextIndex = String(index + 1);
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                input.value = input.type === 'hidden' ? '0' : '';
            });
            rows.appendChild(row);
            markDraft(card);
            row.querySelector('input[type="text"]').focus();
            refreshLimit();
        });
        rows.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove-choice]');
            if (!removeButton) return;
            markDraft(card);
            const row = removeButton.closest('.rm-resource-choice');
            if (rows.children.length === 1) {
                row.querySelectorAll('input').forEach((input) => { input.value = input.type === 'hidden' ? '0' : ''; });
            } else {
                row.remove();
            }
            refreshLimit();
        });
        refreshLimit();
    });
    initializeResources(dashboard);

    let releaseNative = () => {};
    const initializeNative = () => {
        releaseNative();
        let detail = dashboard.querySelector('[data-native-detail]');
        if (!detail) return;
        let committed = { option: detail.dataset.nativeOption, choice: detail.dataset.nativeChoice };
        let sequence = 0;
        let active = null;
        let pending = null;
        let dirty = hasDraft(detail);
        let retry = null;
        const restoreSelection = () => {
            const option = detail.querySelector('#rm-native-option');
            const choice = detail.querySelector('#rm-native-choice');
            if (option) option.value = committed.option;
            if (choice) choice.value = committed.choice;
        };
        const showStatus = (message, failed = false) => {
            const status = detail.querySelector('.rm-native-load-status');
            status.hidden = false;
            status.classList.toggle('is-error', failed);
            status.setAttribute('role', failed ? 'alert' : 'status');
            status.querySelector('[data-native-load-message]').textContent = message;
            status.querySelector('[data-native-retry]').hidden = !failed;
        };
        const loadSelection = async (option, choice, focusID) => {
            if (dirty && !window.confirm('Discard unsaved changes and load the selected option or choice?')) {
                restoreSelection();
                return;
            }
            if (!pending) {
                pending = { dirty, controls: Array.from(detail.querySelectorAll('.rm-native-choice-editor input, .rm-native-choice-editor button, #rm-native-choice')).map((node) => ({ node, disabled: node.disabled })) };
            }
            dirty = false;
            const current = ++sequence;
            if (active) active.abort();
            const request = new AbortController();
            active = request;
            const timeout = setTimeout(() => request.abort(), 20000);
            pending.controls.forEach(({ node }) => { node.disabled = node.id !== 'rm-native-choice' || option !== committed.option; });
            detail.setAttribute('aria-busy', 'true');
            showStatus('Loading selected settings and INR / USD prices...');
            try {
                const body = new URLSearchParams({
                    action: 'rackmonk_native_selection', nonce: detail.dataset.nativeNonce,
                    native_group: detail.dataset.nativeGroup, native_option: option, native_choice: choice
                });
                const response = await fetch(detail.dataset.nativeUrl, { method: 'POST', credentials: 'same-origin', body, signal: request.signal });
                const result = await response.json();
                if (!response.ok || !result?.success || typeof result.data?.html !== 'string') {
                    throw new Error(result?.data?.message || 'Settings could not be loaded. Refresh if your admin session expired.');
                }
                if (current !== sequence) return;
                const template = document.createElement('template');
                template.innerHTML = result.data.html.trim();
                const replacement = template.content.firstElementChild;
                if (!replacement?.matches('[data-native-detail]') || replacement.dataset.nativeGroup !== detail.dataset.nativeGroup || replacement.dataset.nativeOption !== option || replacement.dataset.nativeChoice !== String(result.data.choice) || (choice !== '0' && replacement.dataset.nativeChoice !== choice)) {
                    throw new Error('The returned settings do not match your selection. Please try again.');
                }
                detail.replaceWith(replacement);
                detail = replacement;
                committed = { option: detail.dataset.nativeOption, choice: detail.dataset.nativeChoice };
                pending = null;
                retry = null;
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('native_group', detail.dataset.nativeGroup);
                    url.searchParams.set('native_option', committed.option);
                    if (committed.choice !== '0') url.searchParams.set('native_choice', committed.choice);
                    else url.searchParams.delete('native_choice');
                    url.searchParams.delete('status');
                    url.searchParams.delete('msg');
                    window.history.replaceState(null, '', url);
                } catch (error) { /* History changes are optional; the form already contains the selected IDs. */ }
                detail.querySelector(`#${focusID}`)?.focus({ preventScroll: true });
            } catch (error) {
                if (current !== sequence) return;
                if (pending) {
                    pending.controls.forEach(({ node, disabled }) => { node.disabled = disabled; });
                    dirty = pending.dirty;
                    pending = null;
                }
                restoreSelection();
                retry = { option, choice, focusID };
                showStatus(error.name === 'AbortError' ? 'Loading timed out. Your previous settings are still available.' : error.message, true);
            } finally {
                clearTimeout(timeout);
                if (current === sequence) {
                    active = null;
                    detail.removeAttribute('aria-busy');
                }
            }
        };
        const onInput = (event) => {
            if (event.target.closest('.rm-native-choice-editor')) dirty = true;
        };
        const onChange = (event) => {
            if (event.target.closest('.rm-native-choice-editor')) dirty = true;
            const select = event.target.closest('[data-native-select]');
            if (!select) return;
            const option = detail.querySelector('#rm-native-option').value;
            const choice = select.dataset.nativeSelect === 'option' ? '0' : select.value;
            loadSelection(option, choice, select.id);
        };
        const onClick = (event) => {
            if (event.target.closest('[data-native-retry]') && retry) loadSelection(retry.option, retry.choice, retry.focusID);
        };
        dashboard.addEventListener('input', onInput);
        dashboard.addEventListener('change', onChange);
        dashboard.addEventListener('click', onClick);
        releaseNative = () => {
            sequence++;
            if (active) active.abort();
            if (pending) pending.controls.forEach(({ node, disabled }) => { node.disabled = disabled; });
            restoreSelection();
            detail.removeAttribute('aria-busy');
            detail.querySelector('.rm-native-load-status').hidden = true;
            dashboard.removeEventListener('input', onInput);
            dashboard.removeEventListener('change', onChange);
            dashboard.removeEventListener('click', onClick);
            releaseNative = () => {};
        };
    };
    initializeNative();

    const sections = new Map();
    const selectionValues = (root) => Object.fromEntries(Array.from(root.querySelectorAll('[data-admin-select]')).map((select) => [select.name, select.value]));
    dashboard.querySelectorAll('[data-admin-section]').forEach((root) => sections.set(root.dataset.adminSection, { root, kind: root.dataset.adminSection, committed: selectionValues(root), sequence: 0, active: null, pending: null, retry: null }));
    const restoreSection = (state) => state.root.querySelectorAll('[data-admin-select]').forEach((select) => { select.value = state.committed[select.name]; });
    const loadSection = async (state, selection, focusID) => {
        if (!state.pending && hasDraft(state.root) && !window.confirm('Discard unsaved changes and load the selected settings?')) {
            restoreSection(state);
            return;
        }
        if (state.kind === 'groups') releaseNative();
        if (!state.pending) state.pending = Array.from(state.root.querySelectorAll('input, select, textarea, button')).map((node) => ({ node, disabled: node.disabled }));
        const current = ++state.sequence;
        if (state.active) state.active.abort();
        const request = new AbortController();
        state.active = request;
        const timeout = setTimeout(() => request.abort(), 20000);
        state.pending.forEach(({ node, disabled }) => {
            const dependent = state.kind === 'groups' && ((node.name === 'config_plan' && focusID === 'rm-config-category') || (node.name === 'native_group' && ['rm-config-category', 'rm-config-plan'].includes(focusID)));
            node.disabled = disabled || !node.matches('[data-admin-select]') || dependent;
        });
        const showStatus = (message, failed = false) => {
            const status = state.root.querySelector('[data-admin-status]');
            status.hidden = false;
            status.classList.toggle('is-error', failed);
            status.setAttribute('role', failed ? 'alert' : 'status');
            status.querySelector('[data-admin-message]').textContent = message;
            status.querySelector('[data-admin-retry]').hidden = !failed;
        };
        state.root.setAttribute('aria-busy', 'true');
        showStatus('Loading selected settings...');
        try {
            const body = new URLSearchParams({ action: 'rackmonk_admin_section', nonce: state.root.dataset.adminNonce, section: state.kind, ...selection });
            const response = await fetch(state.root.dataset.adminUrl, { method: 'POST', credentials: 'same-origin', signal: request.signal, body });
            const result = await response.json();
            if (!response.ok || !result?.success || result.data?.section !== state.kind || typeof result.data?.html !== 'string') throw new Error(result?.data?.message || 'Settings could not be loaded. Refresh if your admin session expired.');
            if (current !== state.sequence) return;
            const template = document.createElement('template');
            template.innerHTML = result.data.html.trim();
            const replacement = template.content.firstElementChild;
            if (!replacement?.matches('[data-admin-section]') || replacement.dataset.adminSection !== state.kind || replacement.id !== state.root.id) throw new Error('The returned section does not match your selection.');
            const next = selectionValues(replacement);
            const sameSelection = Object.entries(selection).every(([key, value]) => (state.kind === 'groups' && key === 'native_group' && value === '0') || next[key] === value);
            if (!sameSelection) throw new Error('The returned settings do not match your selection.');
            state.root.replaceWith(replacement);
            state.root = replacement;
            state.committed = next;
            state.pending = null;
            state.retry = null;
            if (state.kind === 'resources') initializeResources(replacement);
            if (state.kind === 'groups') initializeNative();
            try {
                const url = new URL(window.location.href);
                Object.entries(next).forEach(([key, value]) => url.searchParams.set(key, value));
                if (state.kind === 'groups') {
                    const native = replacement.querySelector('[data-native-detail]');
                    for (const key of ['option', 'choice']) {
                        const value = native?.dataset[key === 'option' ? 'nativeOption' : 'nativeChoice'];
                        if (value && value !== '0') url.searchParams.set('native_' + key, value);
                        else url.searchParams.delete('native_' + key);
                    }
                }
                url.searchParams.delete('status'); url.searchParams.delete('msg');
                window.history.replaceState(null, '', url);
            } catch (error) { /* The selected IDs in each save form remain authoritative. */ }
            replacement.querySelector(`#${focusID}`)?.focus({ preventScroll: true });
        } catch (error) {
            if (current !== state.sequence) return;
            if (state.pending) state.pending.forEach(({ node, disabled }) => { node.disabled = disabled; });
            state.pending = null;
            restoreSection(state);
            state.retry = { selection, focusID };
            showStatus(error.name === 'AbortError' ? 'Loading timed out. Your previous settings are still available.' : error.message, true);
            if (state.kind === 'groups') initializeNative();
        } finally {
            clearTimeout(timeout);
            if (current === state.sequence) { state.active = null; state.root.removeAttribute('aria-busy'); }
        }
    };
    dashboard.addEventListener('change', (event) => {
        const select = event.target.closest('[data-admin-select]');
        if (!select) return;
        const root = select.closest('[data-admin-section]');
        const state = sections.get(root.dataset.adminSection);
        const selection = selectionValues(root);
        if (select.name === 'config_category') selection.config_plan = '0';
        if (['config_category', 'config_plan'].includes(select.name)) selection.native_group = '0';
        loadSection(state, selection, select.id);
    });
    dashboard.addEventListener('click', (event) => {
        const retry = event.target.closest('[data-admin-retry]');
        if (retry) {
            const state = sections.get(retry.closest('[data-admin-section]').dataset.adminSection);
            if (state.retry) loadSection(state, state.retry.selection, state.retry.focusID);
        }
        const link = event.target.closest('[data-admin-group]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        const state = sections.get('groups');
        loadSection(state, { ...selectionValues(state.root), native_group: link.dataset.adminGroup }, 'rm-config-group');
    });
})();
