(function () {
    'use strict';

    var STORAGE_KEY = 'rackmonk_website_cart_v1';
    var MAX_ITEMS = 20;
    var instance = 0;
    var memoryCart = [];

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function init(root) {
        if (root.dataset.rmCartReady) return;
        root.dataset.rmCartReady = 'true';
        var source = root.querySelector('script[type="application/json"][data-rm-cart-data]');
        var data;
        try {
            data = JSON.parse(source ? source.textContent : 'null');
            if (!data || !Array.isArray(data.catalog)) throw new Error('Invalid catalog');
        } catch (error) {
            root.appendChild(element('p', 'rm-cart-alert', 'The hosting configurator could not load. Please refresh the page.'));
            return;
        }

        function text(key, fallback) {
            return data.content && typeof data.content[key] === 'string' ? data.content[key] : fallback;
        }

        var prefix = 'rm-cart-' + (++instance) + '-';
        var catalog = data.catalog.filter(function (plan) {
            return plan && Number.isInteger(plan.pid) && plan.pid > 0 && plan.resources &&
                typeof plan.resources === 'object' && typeof plan.revision === 'string';
        });
        var cart = [];
        var quotes = new Map();
        var cartControllers = new Map();
        var storageAvailable = true;
        var activePlan = null;
        var requestedPlan = Number(data.requestedPlan) || 0;
        var months = [1, 12, 24, 36, 48].indexOf(Number(data.requestedTerm)) >= 0 ? Number(data.requestedTerm) : 1;
        var choices = Object.create(null);
        var confirmed = null;
        var pending = false;
        var editKey = null;
        var sessionEdit = data.editItem && validItem(data.editItem) && typeof data.editItem.token === 'string' ? data.editItem : null;
        var editError = data.editError || (data.editItem && !sessionEdit ? 'This cart configuration could not be loaded. Open your cart and try again.' : '');
        if (editError) catalog = [];
        var timer;
        var controller;
        var generation = 0;
        var tab = 'configure';

        var shell = element('div', 'rm-cart-shell');
        var header = element('header', 'rm-cart-header');
        header.appendChild(element('span', 'rm-cart-eyebrow', text('cart_eyebrow', 'HOSTING PLAN CONFIGURATOR')));
        header.appendChild(element('h2', 'rm-cart-title', sessionEdit ? text('cart_edit_title', 'Customize your cart plan') : text('cart_title', 'Configure Your Hosting Plan')));
        header.appendChild(element('p', 'rm-cart-intro', text('cart_intro', 'Choose your ideal specifications, operating system, and security add-ons with live transparent pricing.')));
        var trustBar = element('div', 'rm-cart-trust-bar');
        [
            text('cart_trust_activation', 'Activation after order review'),
            text('cart_trust_resources', 'Choose your resources'),
            text('cart_trust_pricing', 'Review live plan pricing'),
            text('cart_trust_support', 'Contact us for plan support')
        ].forEach(function (badgeText) {
            trustBar.appendChild(element('span', 'rm-cart-trust-badge', badgeText));
        });
        header.appendChild(trustBar);
        shell.appendChild(header);
        if (sessionEdit || editError) {
            var editNotice = element('p', 'rm-cart-alert rm-cart-session-notice', editError || 'Editing an existing checkout cart item (quantity ' + sessionEdit.quantity + '). Saving replaces only this configuration; other cart items stay unchanged.');
            editNotice.setAttribute('role', editError ? 'alert' : 'status');
            shell.appendChild(editNotice);
        }
        var notice = element('p', 'rm-cart-alert rm-cart-storage');
        notice.hidden = true;
        notice.setAttribute('role', 'status');
        shell.appendChild(notice);
        var tabs = element('div', 'rm-cart-tabs');
        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', 'Hosting cart');
        var configureTab = button(text('cart_configure_tab', '01 Configure'), 'rm-cart-tab', function () { setTab('configure'); });
        var cartTab = button(text('cart_cart_tab', 'Your cart') + ' (0)', 'rm-cart-tab', function () { setTab('cart'); });
        cartTab.hidden = Boolean(sessionEdit);
        [configureTab, cartTab].forEach(function (node, index) {
            node.id = prefix + 'tab-' + index;
            node.setAttribute('role', 'tab');
            node.setAttribute('aria-controls', prefix + 'panel-' + index);
            node.addEventListener('keydown', function (event) {
                if (sessionEdit) return;
                if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) < 0) return;
                event.preventDefault();
                var target = event.key === 'Home' ? configureTab : event.key === 'End' ? cartTab : node === configureTab ? cartTab : configureTab;
                target.click();
                target.focus();
            });
            tabs.appendChild(node);
        });
        shell.appendChild(tabs);
        var configurePanel = element('div', 'rm-cart-configure');
        var cartPanel = element('div', 'rm-cart-cart');
        [configurePanel, cartPanel].forEach(function (node, index) {
            node.id = prefix + 'panel-' + index;
            node.setAttribute('role', 'tabpanel');
            node.setAttribute('aria-labelledby', prefix + 'tab-' + index);
            shell.appendChild(node);
        });
        root.appendChild(shell);

        function button(text, className, onClick) {
            var node = element('button', className, text);
            node.type = 'button';
            node.addEventListener('click', onClick);
            return node;
        }

        function storageNotice() {
            notice.hidden = false;
            notice.textContent = 'Browser storage is unavailable. Your cart works in memory for this page only and may be lost when you leave.';
        }

        function validItem(item) {
            return item && typeof item.key === 'string' && item.key.length > 0 && item.key.length <= 160 &&
                Number.isInteger(item.pid) && item.pid > 0 && [1, 12, 24, 36, 48].indexOf(item.months) >= 0 &&
                typeof item.revision === 'string' && item.revision.length <= 200 && item.choices &&
                typeof item.choices === 'object' && !Array.isArray(item.choices) &&
                Object.keys(item.choices).length <= 100 && Object.keys(item.choices).every(function (key) {
                    return typeof item.choices[key] === 'string' && item.choices[key].length <= 200;
                });
        }

        function storedItem(item) {
            return { key: item.key, pid: item.pid, months: item.months, choices: Object.assign({}, item.choices), revision: item.revision };
        }

        function readCart() {
            try {
                var raw = window.localStorage.getItem(STORAGE_KEY);
                var saved = raw ? JSON.parse(raw) : [];
                if (!Array.isArray(saved) || saved.length > MAX_ITEMS || !saved.every(validItem) ||
                    new Set(saved.map(function (item) { return item.key; })).size !== saved.length) {
                    throw new Error('Invalid saved cart');
                }
                cart = saved.map(storedItem);
                memoryCart = cart.map(storedItem);
            } catch (error) {
                storageAvailable = false;
                cart = memoryCart.map(storedItem);
                storageNotice();
            }
        }

        function saveCart() {
            memoryCart = cart.map(storedItem);
            if (!storageAvailable) return;
            try {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(memoryCart));
            } catch (error) {
                storageAvailable = false;
                storageNotice();
            }
        }

        function money(value, quote) {
            var currency = quote.currency || 'INR';
            try {
                return new Intl.NumberFormat('en-IN', { style: 'currency', currency: currency, maximumFractionDigits: 2 }).format(Number(value));
            } catch (error) {
                return (quote.symbol || currency + ' ') + Number(value).toFixed(2);
            }
        }

        function resourceKeys() { return Object.keys(activePlan.resources); }

        function nativeChoiceText(choice, plan) {
            var price = choice.prices && choice.prices[String(months)];
            if (!price) return choice.label + ' (Unavailable for this term)';
            var details = Number(price.recurring) ? money(price.recurring, plan) + ' / term' : 'Included';
            if (Number(price.setup)) details += ' + ' + money(price.setup, plan) + ' setup';
            return choice.label + ' (' + details + ')';
        }

        function planChoices(plan, saved) {
            var result = Object.create(null);
            Object.keys(plan.resources).forEach(function (key) {
                var resource = plan.resources[key];
                var options = Array.isArray(resource.choices) ? resource.choices : [];
                var available = plan.native_pricing ? options.filter(function (option) { return option.prices && option.prices[String(months)]; }) : options;
                var selected = available.find(function (option) { return saved && option.id === saved[key]; }) || available[0];
                if (selected) result[key] = selected.id;
            });
            return result;
        }

        async function requestQuote(item, signal) {
            if (!data.quoteUrl) throw new Error('The pricing service is not configured. Please try again later.');
            var response = await fetch(data.quoteUrl, {
                method: 'POST', credentials: 'same-origin', signal: signal,
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.nonce || '' },
                body: JSON.stringify({ pid: item.pid, months: item.months, choices: item.choices, revision: item.revision })
            });
            var quote;
            try { quote = await response.json(); } catch (error) { throw new Error('The pricing service returned an unreadable response. Please retry.'); }
            if (!response.ok) throw new Error(quote && typeof quote.message === 'string' ? quote.message : 'Pricing is currently unavailable. Please retry.');
            var amounts = ['base_monthly', 'resources_monthly', 'monthly', 'total'];
            var quotedPlan = catalog.find(function (plan) { return plan.pid === item.pid; });
            if (!quote || quote.pid !== item.pid || quote.months !== item.months || quote.revision !== item.revision ||
                (quotedPlan && quotedPlan.native_pricing && !quote.native_pricing) ||
                typeof quote.name !== 'string' || typeof quote.currency !== 'string' || !/^[A-Z]{3}$/.test(quote.currency) ||
                !Array.isArray(quote.selection) || !quote.choices ||
                Object.keys(quote.choices).length !== Object.keys(item.choices).length ||
                !Object.keys(item.choices).every(function (key) { return quote.choices[key] === item.choices[key]; }) ||
                !amounts.every(function (key) { return validAmount(quote[key]); }) ||
                (quote.native_pricing && (!['base_term', 'resources_term', 'setup', 'recurring'].every(function (key) { return validAmount(quote[key]); }) ||
                    !quote.selection.every(function (selection) { return validAmount(selection.term_price) && validAmount(selection.setup); }))) ||
                !quote.selection.every(function (selection) {
                    return selection && typeof selection.label === 'string' && typeof selection.choice === 'string' && validAmount(selection.price);
                })) throw new Error('Pricing could not be verified. Please refresh the page and try again.');
            return quote;
        }

        function validAmount(value) {
            return (typeof value === 'number' || (typeof value === 'string' && /^\d+(\.\d+)?$/.test(value))) &&
                Number.isFinite(Number(value)) && Number(value) >= 0;
        }

        var form;
        var summary;
        var quoteStatus;
        var addButton;
        var retryButton;
        var feedback;

        function buildConfigurator() {
            configurePanel.replaceChildren();
            if (!catalog.length) {
                configurePanel.appendChild(element('p', 'rm-cart-empty', (requestedPlan ? 'The requested hosting plan is unavailable. ' : '') + text('cart_unavailable', 'No hosting plans are available right now. Your saved cart can still be reviewed.')));
                return;
            }
            form = element('div', 'rm-cart-form');
            var heading = element('div', 'rm-cart-section-heading');
            heading.appendChild(element('span', 'rm-cart-step', '01'));
            heading.appendChild(element('h3', '', text('cart_configure_heading', 'Make it yours')));
            form.appendChild(heading);
            var basics = element('div', 'rm-cart-basics');
            var planSelect = selectField(basics, text('cart_plan_label', 'Hosting plan'), 'plan');
            if (!activePlan) {
                var placeholder = option('', text('cart_plan_placeholder', 'Choose an available hosting plan'));
                placeholder.disabled = true;
                planSelect.appendChild(placeholder);
            }
            catalog.forEach(function (plan) { planSelect.appendChild(option(plan.pid, plan.name)); });
            planSelect.value = activePlan ? String(activePlan.pid) : '';
            planSelect.addEventListener('change', function () {
                var selected = catalog.find(function (plan) { return plan.pid === Number(planSelect.value); });
                if (!selected) return;
                activePlan = selected;
                choices = planChoices(activePlan);
                buildConfigurator();
                scheduleQuote(0);
                document.getElementById(prefix + 'plan').focus();
            });
            form.appendChild(basics);
            if (!activePlan) {
                var unavailable = element('p', 'rm-cart-alert', 'The requested hosting plan is unavailable. Choose an available plan to continue; no replacement has been selected.');
                unavailable.id = prefix + 'unavailable';
                unavailable.setAttribute('role', 'alert');
                planSelect.setAttribute('aria-describedby', unavailable.id);
                configurePanel.appendChild(unavailable);
                configurePanel.appendChild(form);
                return;
            }
            var duration = selectField(basics, text('cart_duration_label', 'Billing duration'), 'duration');
            [1, 12, 24, 36, 48].forEach(function (term) {
                var item = option(term, term === 1 ? '1 month' : term + ' months');
                if (activePlan.native_pricing && (!(activePlan.prices && activePlan.prices[String(term)]) || !Object.keys(activePlan.resources).every(function (key) {
                    return activePlan.resources[key].choices.some(function (choice) { return choice.prices && choice.prices[String(term)]; });
                }))) item.disabled = true;
                duration.appendChild(item);
            });
            duration.value = String(months);
            duration.addEventListener('change', function () {
                months = Number(duration.value);
                if (activePlan.native_pricing) { choices = planChoices(activePlan, choices); buildConfigurator(); }
                scheduleQuote(250);
            });
            if (activePlan.native_pricing && months === 48) {
                var manualTerm = element('p', 'rm-cart-alert', '48-month one-time packages are purchased directly through WHMCS from the pricing page. Website resource selections cannot be transferred.');
                manualTerm.setAttribute('role', 'note');
                form.appendChild(manualTerm);
            }
            if (activePlan.demo_pricing) {
                var demo = element('p', 'rm-cart-alert rm-cart-demo-notice', 'Demo upgrade prices and sample resource choices. Confirm availability, compatibility and final rates before production use.');
                demo.setAttribute('role', 'note');
                form.appendChild(demo);
            }
            form.appendChild(element('h4', 'rm-cart-resource-heading', text('cart_resources_heading', 'Choose your resources')));
            var grid = element('div', 'rm-cart-resources');
            resourceKeys().forEach(function (key, index) {
                var resource = activePlan.resources[key];
                var options = Array.isArray(resource.choices) ? resource.choices : [];
                var field = element('div', 'rm-cart-field rm-cart-resource');
                field.setAttribute('data-resource-key', key);
                var id = prefix + 'resource-' + index;
                if (!activePlan.native_pricing && resource.type === 'checkbox' && options.length === 2) {
                    field.classList.add('rm-cart-toggle-field');
                    if (choices[key] === options[1].id) field.classList.add('is-checked');
                    var label = element('label', 'rm-cart-toggle-label');
                    label.htmlFor = id;
                    var copy = element('span', 'rm-cart-toggle-copy');
                    copy.appendChild(element('span', 'rm-cart-label', resource.label));
                    copy.appendChild(element('span', 'rm-cart-field-hint', options[1].label + ' (+' + money(options[1].price, activePlan) + '/mo)'));
                    var checkbox = element('input', 'rm-cart-checkbox');
                    checkbox.type = 'checkbox';
                    checkbox.id = id;
                    checkbox.checked = choices[key] === options[1].id;
                    checkbox.addEventListener('change', function () {
                        if (checkbox.checked) field.classList.add('is-checked');
                        else field.classList.remove('is-checked');
                        choices[key] = options[checkbox.checked ? 1 : 0].id;
                        scheduleQuote(300);
                    });
                    label.appendChild(copy);
                    label.appendChild(checkbox);
                    field.appendChild(label);
                } else {
                    var select = selectField(field, resource.label, 'resource-' + index);
                    options.forEach(function (choice) {
                        var item = option(choice.id, activePlan.native_pricing ? nativeChoiceText(choice, activePlan) : choice.label + (Number(choice.price) === 0 ? ' (Included)' : ' (+' + money(choice.price, activePlan) + '/mo)'));
                        if (activePlan.native_pricing && !(choice.prices && choice.prices[String(months)])) item.disabled = true;
                        select.appendChild(item);
                    });
                    select.value = choices[key] || '';
                    select.disabled = !options.length;
                    var updateFieldHighlight = function () {
                        var curChoice = options.find(function (c) { return c.id === select.value; });
                        if (curChoice && Number(curChoice.price) > 0) field.classList.add('is-upgraded');
                        else field.classList.remove('is-upgraded');
                    };
                    updateFieldHighlight();
                    select.addEventListener('change', function () {
                        choices[key] = select.value;
                        updateFieldHighlight();
                        scheduleQuote(300);
                    });
                }
                grid.appendChild(field);
            });
            form.appendChild(grid);
            configurePanel.appendChild(form);
            var aside = element('aside', 'rm-cart-summary');
            aside.setAttribute('aria-label', 'Plan summary');
            aside.appendChild(element('span', 'rm-cart-eyebrow', text('cart_summary_eyebrow', 'YOUR CONFIGURATION')));
            summary = element('div', 'rm-cart-summary-content');
            aside.appendChild(summary);
            quoteStatus = element('p', 'rm-cart-quote-status');
            quoteStatus.setAttribute('role', 'status');
            quoteStatus.setAttribute('aria-live', 'polite');
            aside.appendChild(quoteStatus);
            retryButton = button(text('cart_retry_cta', 'Retry pricing'), 'rm-cart-link', function () { scheduleQuote(0); });
            retryButton.hidden = true;
            aside.appendChild(retryButton);
            addButton = button(sessionEdit ? text('cart_update_cta', 'Update cart configuration') : editKey ? text('cart_save_cta', 'Save cart changes') : text('cart_add_cta', 'Add to your cart'), 'rm-cart-button rm-cart-primary', addToCart);
            addButton.disabled = true;
            aside.appendChild(addButton);
            if (editKey) aside.appendChild(button(text('cart_cancel_cta', 'Cancel editing'), 'rm-cart-button rm-cart-secondary', cancelEdit));
            if (sessionEdit && data.cartUrl) {
                var back = element('a', 'rm-cart-button rm-cart-secondary', text('cart_return_cta', 'Cancel and return to cart'));
                back.href = data.cartUrl;
                aside.appendChild(back);
            }
            feedback = element('p', 'rm-cart-feedback');
            feedback.setAttribute('role', 'status');
            aside.appendChild(feedback);
            aside.appendChild(element('p', 'rm-cart-summary-note', activePlan.native_pricing ? text('cart_pricing_note_native', 'Prices exclude taxes. Configurable terms and setup fees come from WHMCS.') : text('cart_pricing_note', 'Prices exclude taxes. No discounts applied. Resource prices are added to the base monthly price.')));
            var trustBox = element('div', 'rm-cart-summary-guarantees');
            [
                text('cart_checkout_badge', 'Review your order before payment'),
                text('cart_activation_badge', 'Server activation after order review'),
                text('cart_terms_badge', 'Terms depend on your selected plan')
            ].forEach(function (itemText) {
                trustBox.appendChild(element('div', 'rm-cart-summary-guarantee-item', itemText));
            });
            aside.appendChild(trustBox);
            configurePanel.appendChild(aside);
            renderSummary(null);
        }

        function selectField(parent, labelText, suffix) {
            var wrapper = element('div', 'rm-cart-field');
            var label = element('label', 'rm-cart-label', labelText);
            label.htmlFor = prefix + suffix;
            var select = element('select', 'rm-cart-select ignore');
            select.id = label.htmlFor;
            wrapper.appendChild(label);
            wrapper.appendChild(select);
            parent.appendChild(wrapper);
            return select;
        }

        function option(value, text) {
            var node = element('option', '', text);
            node.value = String(value);
            return node;
        }

        function row(parent, label, value, className) {
            var line = element('div', 'rm-cart-summary-row' + (className ? ' ' + className : ''));
            line.appendChild(element('span', '', label));
            line.appendChild(element('strong', '', value));
            parent.appendChild(line);
        }

        function renderSummary(quote) {
            if (!summary || !activePlan) return;
            summary.replaceChildren();
            summary.appendChild(element('h3', 'rm-cart-plan-name', quote ? quote.name : activePlan.name));
            summary.appendChild(element('p', 'rm-cart-term-label', months + (months === 1 ? ' month' : ' months') + ' of hosting'));
            var pricing = quote || activePlan;
            var selections = quote ? quote.selection : resourceKeys().map(function (key) {
                var resource = activePlan.resources[key];
                var choice = (resource.choices || []).find(function (item) { return item.id === choices[key]; });
                return { label: resource.label, choice: choice ? choice.label : 'Not available', price: choice ? choice.price : 0 };
            });
            var list = element('div', 'rm-cart-selection-list');
            selections.forEach(function (selection) {
                var itemRow = element('div', 'rm-cart-summary-row rm-cart-selection-row');
                var isAddon = Number(activePlan.native_pricing && quote ? selection.term_price : selection.price) > 0 || Number(selection.setup || 0) > 0;
                if (isAddon) itemRow.classList.add('rm-is-addon');

                var meta = element('div', 'rm-cart-selection-meta');
                meta.appendChild(element('span', 'rm-cart-selection-label', selection.label));
                meta.appendChild(element('strong', 'rm-cart-selection-choice', selection.choice));
                itemRow.appendChild(meta);

                var priceTag = element('span', 'rm-cart-selection-price' + (isAddon ? ' is-addon' : ' is-included'));
                if (activePlan.native_pricing) {
                    priceTag.textContent = quote ? (isAddon ? '+ ' + money(selection.term_price, pricing) + ' / term' + (Number(selection.setup) ? ' + ' + money(selection.setup, pricing) + ' setup' : '') : 'Included') : 'See quote';
                } else {
                    priceTag.textContent = isAddon ? ('+ ' + money(selection.price, pricing) + '/mo') : 'Included';
                }
                itemRow.appendChild(priceTag);

                list.appendChild(itemRow);
            });
            summary.appendChild(list);
            if (activePlan.native_pricing) {
                if (!quote) {
                    summary.appendChild(element('p', 'rm-cart-pricing-pending', 'Checking current pricing for this term...'));
                    return;
                }
                row(summary, text('cart_base_term_label', 'Base plan / term'), money(quote.base_term, pricing));
                row(summary, text('cart_resources_term_label', 'Resources / term'), money(quote.resources_term, pricing));
                row(summary, text('cart_setup_label', 'Setup fees'), money(quote.setup, pricing));
                row(summary, 'Total for ' + months + (months === 1 ? ' month' : ' months'), money(quote.total, pricing), 'rm-cart-total');
                return;
            }
            var base = quote ? Number(quote.base_monthly) : Number(activePlan.base_price);
            var extras = quote ? Number(quote.resources_monthly) : selections.reduce(function (sum, item) { return sum + Number(item.price); }, 0);
            var monthly = quote ? Number(quote.monthly) : base + extras;
            row(summary, text('cart_base_month_label', 'Base plan / month'), money(base, pricing));
            row(summary, text('cart_resources_month_label', 'Resources / month'), (extras > 0 ? '+ ' : '') + money(extras, pricing), extras > 0 ? 'rm-cart-extras-active' : '');
            row(summary, quote ? text('cart_monthly_label', 'Monthly price') : text('cart_estimate_label', 'Estimated monthly price'), money(monthly, pricing), 'rm-cart-monthly');
            row(summary, (quote ? 'Total for ' : 'Estimated total for ') + months + (months === 1 ? ' month' : ' months'), money(quote ? quote.total : monthly * months, pricing), 'rm-cart-total');
        }

        function cartBlocked(ignoreKey) {
            return cart.some(function (item) {
                if (item.key === ignoreKey) return false;
                var state = quotes.get(item.key);
                return !state || state.pending || !state.quote;
            });
        }

        function updateAddButton() {
            if (!addButton) return;
            addButton.disabled = pending || !confirmed || (!sessionEdit && (cartBlocked(editKey) || (!editKey && cart.length >= MAX_ITEMS)));
            if (feedback && !pending && confirmed) {
                feedback.textContent = sessionEdit ? 'Review all resources, then update your existing cart item.' : cartBlocked(editKey) ? 'Resolve or remove unverified items in Your cart before adding a plan.' :
                    !editKey && cart.length >= MAX_ITEMS ? 'Your cart has reached its 20-plan limit.' : '';
            }
        }

        function scheduleQuote(delay) {
            clearTimeout(timer);
            if (controller) controller.abort();
            generation += 1;
            confirmed = null;
            pending = true;
            if (!activePlan) return;
            renderSummary(null);
            quoteStatus.textContent = 'Checking live pricing. Estimates are not confirmed.';
            quoteStatus.classList.remove('rm-cart-error-text');
            retryButton.hidden = true;
            feedback.textContent = '';
            updateAddButton();
            var version = generation;
            timer = setTimeout(function () { quoteConfiguration(version); }, delay);
        }

        async function quoteConfiguration(version) {
            var currentController = new AbortController();
            controller = currentController;
            var timeout = setTimeout(function () { currentController.abort(); }, 20000);
            try {
                var quote = await requestQuote({ pid: activePlan.pid, months: months, choices: Object.assign({}, choices), revision: activePlan.revision }, currentController.signal);
                if (generation !== version) return;
                confirmed = quote;
                renderSummary(quote);
                quoteStatus.textContent = 'Live price confirmed. Ready for your cart.';
            } catch (error) {
                if (generation !== version) return;
                confirmed = null;
                quoteStatus.textContent = error.name === 'AbortError' ? 'Pricing request timed out. Please retry.' : error.message;
                quoteStatus.classList.add('rm-cart-error-text');
                retryButton.hidden = false;
            } finally {
                clearTimeout(timeout);
                if (generation === version) { pending = false; updateAddButton(); }
            }
        }

        function addToCart() {
            if (sessionEdit) {
                if (pending || !confirmed) return;
                startCheckout(addButton, quoteStatus, { pid: activePlan.pid, months: months, choices: Object.assign({}, choices), revision: confirmed.revision });
                return;
            }
            if (pending || !confirmed || cartBlocked(editKey) || (!editKey && cart.length >= MAX_ITEMS)) return;
            var mixed = cart.some(function (item) {
                return item.key !== editKey && quotes.get(item.key).quote.currency !== confirmed.currency;
            });
            if (mixed) {
                feedback.textContent = 'Your cart uses another currency. Remove those plans before adding this currency.';
                return;
            }
            var key = editKey || (window.crypto && typeof window.crypto.randomUUID === 'function' ? window.crypto.randomUUID() : Date.now().toString(36) + '-' + Math.random().toString(36).slice(2));
            var item = { key: key, pid: activePlan.pid, months: months, choices: Object.assign({}, choices), revision: confirmed.revision };
            if (editKey) cart = cart.map(function (old) { return old.key === editKey ? item : old; });
            else cart.push(item);
            quotes.set(key, { quote: confirmed, pending: false });
            editKey = null;
            addButton.textContent = text('cart_add_cta', 'Add to your cart');
            saveCart();
            buildConfigurator();
            renderSummary(confirmed);
            pending = false;
            quoteStatus.textContent = 'Live price confirmed.';
            updateAddButton();
            setTab('cart', false);
            cartTab.focus();
        }

        function cancelEdit() {
            editKey = null;
            buildConfigurator();
            scheduleQuote(0);
        }

        function editItem(item) {
            var plan = catalog.find(function (candidate) { return candidate.pid === item.pid; });
            if (!plan) return;
            editKey = item.key;
            activePlan = plan;
            months = item.months;
            choices = planChoices(plan, item.choices);
            buildConfigurator();
            scheduleQuote(0);
            setTab('configure');
            feedback.textContent = 'Editing your saved plan. Current resource options and prices apply; review before saving.';
            document.getElementById(prefix + 'plan').focus();
        }

        function removeItem(key) {
            if (cartControllers.has(key)) cartControllers.get(key).abort();
            cart = cart.filter(function (item) { return item.key !== key; });
            quotes.delete(key);
            if (editKey === key) cancelEdit();
            saveCart();
            renderCart();
            updateAddButton();
            var target = cartPanel.querySelector('button');
            if (target) target.focus();
        }

        function setTab(next, revalidate) {
            var enteringCart = next === 'cart' && tab !== 'cart';
            tab = next;
            configurePanel.hidden = next !== 'configure';
            cartPanel.hidden = next !== 'cart';
            configureTab.setAttribute('aria-selected', String(next === 'configure'));
            cartTab.setAttribute('aria-selected', String(next === 'cart'));
            configureTab.tabIndex = next === 'configure' ? 0 : -1;
            cartTab.tabIndex = next === 'cart' ? 0 : -1;
            if (enteringCart && revalidate !== false) revalidateCart();
            else renderCart();
        }

        function revalidateCart() {
            cartControllers.forEach(function (request) { request.abort(); });
            cartControllers.clear();
            quotes.clear();
            cart.forEach(function (item) { quotes.set(item.key, { pending: true }); });
            renderCart();
            updateAddButton();
            cart.forEach(function (item) { quoteCartItem(item); });
        }

        async function quoteCartItem(item) {
            var request = new AbortController();
            cartControllers.set(item.key, request);
            var timeout = setTimeout(function () { request.abort(); }, 20000);
            try {
                var quote = await requestQuote(item, request.signal);
                if (cartControllers.get(item.key) !== request || !cart.some(function (saved) { return saved.key === item.key; })) return;
                quotes.set(item.key, { quote: quote, pending: false });
            } catch (error) {
                if (cartControllers.get(item.key) !== request || !cart.some(function (saved) { return saved.key === item.key; })) return;
                quotes.set(item.key, { pending: false, error: error.name === 'AbortError' ? 'Pricing request timed out. Retry to verify this plan.' : error.message });
            } finally {
                clearTimeout(timeout);
                if (cartControllers.get(item.key) === request) {
                    cartControllers.delete(item.key);
                    renderCart();
                    updateAddButton();
                }
            }
        }

        function renderCart() {
            cartTab.textContent = text('cart_cart_tab', 'Your cart') + ' (' + cart.length + ')';
            cartPanel.replaceChildren();
            var heading = element('div', 'rm-cart-cart-heading');
            heading.appendChild(element('h3', '', text('cart_lineup_heading', 'Your hosting lineup')));
            if (cart.length) {
                var refresh = button(text('cart_refresh_cta', 'Refresh prices'), 'rm-cart-link', revalidateCart);
                refresh.disabled = cart.some(function (item) { return quotes.get(item.key) && quotes.get(item.key).pending; });
                heading.appendChild(refresh);
                heading.appendChild(button(text('cart_clear_cta', 'Clear cart'), 'rm-cart-link rm-cart-danger', function () {
                    cartControllers.forEach(function (request) { request.abort(); });
                    cartControllers.clear();
                    cart = [];
                    quotes.clear();
                    if (editKey) cancelEdit();
                    saveCart();
                    renderCart();
                    updateAddButton();
                    cartPanel.querySelector('button').focus();
                }));
            }
            cartPanel.appendChild(heading);
            if (!cart.length) {
                var empty = element('div', 'rm-cart-empty');
                empty.appendChild(element('h4', '', text('cart_empty_title', 'A fresh start for your next project')));
                empty.appendChild(element('p', '', text('cart_empty_intro', 'Configure a hosting plan and add it here. Your choices stay in this browser.')));
                empty.appendChild(button(text('cart_empty_cta', 'Configure a plan'), 'rm-cart-button rm-cart-primary', function () { setTab('configure'); configureTab.focus(); }));
                cartPanel.appendChild(empty);
                return;
            }
            var list = element('div', 'rm-cart-items');
            var groups = new Map();
            cart.forEach(function (item, index) {
                var state = quotes.get(item.key) || { pending: true };
                var quote = state.quote;
                var plan = catalog.find(function (candidate) { return candidate.pid === item.pid; });
                var card = element('article', 'rm-cart-item' + (state.error ? ' rm-cart-item-error' : ''));
                card.setAttribute('aria-busy', String(state.pending === true));
                card.appendChild(element('span', 'rm-cart-eyebrow', 'PLAN ' + String(index + 1).padStart(2, '0')));
                card.appendChild(element('h4', '', quote ? quote.name : plan ? plan.name : 'Saved plan #' + item.pid));
                card.appendChild(element('p', 'rm-cart-term-label', item.months + (item.months === 1 ? ' month' : ' months')));
                if (quote) {
                    var selections = element('div', 'rm-cart-item-selections');
                    quote.selection.forEach(function (selection) {
                        var itemRow = element('div', 'rm-cart-summary-row rm-cart-selection-row');
                        var isAddon = Number(quote.native_pricing ? selection.term_price : selection.price) > 0 || Number(selection.setup || 0) > 0;
                        if (isAddon) itemRow.classList.add('rm-is-addon');
                        var meta = element('div', 'rm-cart-selection-meta');
                        meta.appendChild(element('span', 'rm-cart-selection-label', selection.label));
                        meta.appendChild(element('strong', 'rm-cart-selection-choice', selection.choice));
                        itemRow.appendChild(meta);
                        var priceTag = element('span', 'rm-cart-selection-price' + (isAddon ? ' is-addon' : ' is-included'));
                        priceTag.textContent = isAddon ? (quote.native_pricing ? '+ ' + money(selection.term_price, quote) + ' / term' + (Number(selection.setup) ? ' + ' + money(selection.setup, quote) + ' setup' : '') : '+ ' + money(selection.price, quote) + '/mo') : 'Included';
                        itemRow.appendChild(priceTag);
                        selections.appendChild(itemRow);
                    });
                    card.appendChild(selections);
                    if (quote.native_pricing) row(card, 'Setup fees', money(quote.setup, quote));
                    else row(card, 'Monthly price', money(quote.monthly, quote));
                    row(card, 'Term total', money(quote.total, quote), 'rm-cart-total');
                    var group = groups.get(quote.currency) || { total: 0, quote: quote };
                    group.total += Number(quote.total);
                    groups.set(quote.currency, group);
                } else {
                    var status = element('p', state.error ? 'rm-cart-alert' : 'rm-cart-checking', state.error || 'Verifying current pricing...');
                    status.setAttribute('role', 'status');
                    card.appendChild(status);
                    if (state.error) card.appendChild(element('p', 'rm-cart-field-hint', 'No price is counted for this plan. Edit to review current options, retry pricing, or remove it.'));
                }
                var actions = element('div', 'rm-cart-item-actions');
                var edit = button(text('cart_edit_cta', 'Edit plan'), 'rm-cart-button rm-cart-secondary', function () { editItem(item); });
                edit.disabled = state.pending === true || !plan;
                edit.setAttribute('aria-label', 'Edit plan ' + (index + 1));
                actions.appendChild(edit);
                var remove = button(text('cart_remove_cta', 'Remove'), 'rm-cart-link rm-cart-danger', function () { removeItem(item.key); });
                remove.setAttribute('aria-label', 'Remove plan ' + (index + 1));
                actions.appendChild(remove);
                if (state.error) actions.appendChild(button(text('cart_retry_cta', 'Retry pricing'), 'rm-cart-link', function () {
                    quotes.set(item.key, { pending: true });
                    renderCart();
                    updateAddButton();
                    quoteCartItem(item);
                }));
                card.appendChild(actions);
                list.appendChild(card);
            });
            cartPanel.appendChild(list);
            var totals = element('div', 'rm-cart-cart-total');
            totals.setAttribute('role', 'status');
            if (cartBlocked()) {
                totals.appendChild(element('strong', '', 'Cart total unavailable'));
                totals.appendChild(element('p', '', 'Every plan needs a confirmed current price. Resolve pending or failed quotes to see your cart total.'));
            } else {
                groups.forEach(function (group, currency) { row(totals, groups.size > 1 ? 'Cart total (' + currency + ')' : 'Cart total', money(group.total, group.quote), 'rm-cart-total'); });
                totals.appendChild(element('p', '', groups.size > 1 ? 'Totals exclude taxes. Currencies are shown separately; they are never combined.' : text('cart_totals_note', 'Includes all selected terms and resources. Totals exclude taxes. Taxes and available payment methods are shown at checkout.')));
            }
            if (data.checkoutUrl) {
                var checkout = button(text('cart_checkout_cta', 'Proceed to checkout'), 'rm-cart-button rm-cart-primary', function () { startCheckout(checkout, checkoutStatus); });
                checkout.disabled = cartBlocked() || groups.size !== 1;
                totals.appendChild(checkout);
                var checkoutStatus = element('p', 'rm-cart-checkout-status');
                checkoutStatus.setAttribute('role', 'status');
                checkoutStatus.setAttribute('aria-live', 'polite');
                totals.appendChild(checkoutStatus);
            }
            cartPanel.appendChild(totals);
        }

        async function startCheckout(checkout, status, editedItem) {
            if (!editedItem && (!cart.length || cartBlocked())) return;
            var controls = Array.from(shell.querySelectorAll('button, select, input')).map(function (node) {
                var state = { node: node, disabled: node.disabled };
                node.disabled = true;
                return state;
            });
            checkout.disabled = true;
            status.textContent = editedItem ? 'Verifying resources and updating your cart item...' : 'Verifying your plans and preparing checkout...';
            var request = new AbortController();
            var timeout = setTimeout(function () { request.abort(); }, 30000);
            try {
                var body = new URLSearchParams();
                body.set('nonce', data.checkoutNonce || '');
                body.set('items', JSON.stringify(editedItem ? [editedItem] : cart.map(storedItem)));
                if (editedItem) {
                    body.set('edit_key', sessionEdit.key);
                    body.set('edit_token', sessionEdit.token);
                }
                var response = await fetch(data.checkoutUrl, {
                    method: 'POST', credentials: 'same-origin', signal: request.signal,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString()
                });
                var result = await response.json();
                if (!response.ok || !result.success || !result.data || typeof result.data.redirect !== 'string') {
                    throw new Error(result.data && result.data.message ? result.data.message : 'Checkout could not be prepared. Please try again.');
                }
                var destination = new URL(result.data.redirect, window.location.href);
                if (destination.origin !== window.location.origin) throw new Error('The checkout address could not be verified.');
                // Editing a session item must not discard unrelated browser-cart drafts.
                if (!editedItem) {
                    cart = [];
                    saveCart();
                }
                window.location.assign(destination.href);
            } catch (error) {
                status.textContent = error.name === 'AbortError' ? 'Checkout took too long. Please try again; your cart is saved.' : error.message;
            } finally {
                clearTimeout(timeout);
                controls.forEach(function (state) { state.node.disabled = state.disabled; });
                checkout.disabled = false;
            }
        }

        readCart();
        activePlan = catalog.find(function (plan) { return plan.pid === (sessionEdit ? sessionEdit.pid : requestedPlan); }) || (!requestedPlan && !sessionEdit ? catalog[0] : null) || null;
        if (sessionEdit) months = sessionEdit.months;
        if (activePlan) choices = planChoices(activePlan, sessionEdit ? sessionEdit.choices : null);
        buildConfigurator();
        setTab('configure');
        revalidateCart();
        if (activePlan) scheduleQuote(0);

        window.addEventListener('storage', function (event) {
            if (!storageAvailable || (event.key !== STORAGE_KEY && event.key !== null)) return;
            readCart();
            if (editKey && !cart.some(function (item) { return item.key === editKey; })) cancelEdit();
            revalidateCart();
        });
    }

    function boot() { document.querySelectorAll('.rm-website-cart').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}());
