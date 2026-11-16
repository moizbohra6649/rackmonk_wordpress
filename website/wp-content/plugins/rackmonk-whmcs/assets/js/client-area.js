(function () {
    'use strict';

    function init(root) {
        if (root.dataset.rmAuthReady) return;
        root.dataset.rmAuthReady = 'true';

        root.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            var input = document.getElementById(button.dataset.togglePassword);
            if (!input || !root.contains(input) || input.tagName !== 'INPUT' || input.type !== 'password') return;
            button.type = 'button';
            button.setAttribute('aria-controls', input.id);

            function updateToggle() {
                var visible = input.type === 'text';
                button.textContent = visible ? 'Hide' : 'Show';
                button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', visible ? 'true' : 'false');
            }

            updateToggle();
            button.addEventListener('click', function () {
                input.type = input.type === 'password' ? 'text' : 'password';
                updateToggle();
            });
        });

        root.querySelectorAll('output[data-password-strength]').forEach(function (output, index) {
            var input = document.getElementById(output.dataset.passwordStrength);
            if (!input || !root.contains(input) || input.tagName !== 'INPUT') return;
            if (!output.id) {
                var id = input.id + '-hint-' + index;
                while (document.getElementById(id)) id += '-hint';
                output.id = id;
            }
            var descriptions = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
            if (descriptions.indexOf(output.id) === -1) descriptions.push(output.id);
            input.setAttribute('aria-describedby', descriptions.join(' '));
            output.setAttribute('aria-live', 'polite');
            output.setAttribute('aria-atomic', 'true');

            // Advice only: WooCommerce remains responsible for password validation.
            function updateHint() {
                var length = input.value.length;
                var level = length === 0 ? 'empty' : length < 12 ? 'short' : 'long';
                if (output.dataset.strength === level) return;
                output.dataset.strength = level;
                output.textContent = level === 'empty' ? '' : level === 'short'
                    ? 'Try a longer, unique passphrase with at least 12 characters. This is a hint, not a security check.'
                    : 'A longer passphrase is a good start. Keep it unique; length alone does not guarantee strength.';
            }

            input.addEventListener('input', updateHint);
            updateHint();
        });

        var country = root.querySelector('select#rm_country');
        var wrapper = root.querySelector('#rm_state_field');
        var source = document.getElementById('rm-account-states');
        if (!country || !wrapper || !source) return;
        var states;
        try {
            states = JSON.parse(source.textContent);
            if (!states || typeof states !== 'object' || Array.isArray(states)) return;
        } catch (error) {
            // Leave the server-rendered control intact if its optional map is unavailable.
            return;
        }

        function updateState() {
            var previous = wrapper.querySelector('[name="state"]');
            if (!previous) return;
            var value = previous.value;
            var options = Object.prototype.hasOwnProperty.call(states, country.value) ? states[country.value] : null;
            var keys = options && typeof options === 'object' && !Array.isArray(options) ? Object.keys(options) : [];
            var control = document.createElement(keys.length ? 'select' : 'input');
            control.id = 'rm_state';
            control.name = 'state';
            control.className = previous.className;
            control.dataset.required = previous.dataset.required === undefined ? '1' : previous.dataset.required;
            control.required = control.dataset.required !== '0' && control.dataset.required !== 'false';
            control.setAttribute('aria-required', control.required ? 'true' : 'false');
            control.disabled = previous.disabled;
            ['aria-describedby', 'aria-invalid', 'autocomplete', 'form'].forEach(function (attribute) {
                if (previous.hasAttribute(attribute)) control.setAttribute(attribute, previous.getAttribute(attribute));
            });
            if (!control.hasAttribute('autocomplete')) control.setAttribute('autocomplete', 'address-level1');
            if (keys.length) {
                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select a state / province';
                control.appendChild(placeholder);
                keys.forEach(function (key) {
                    var option = document.createElement('option');
                    option.value = key;
                    option.textContent = String(options[key]);
                    control.appendChild(option);
                });
                control.value = Object.prototype.hasOwnProperty.call(options, value) ? value : '';
            } else {
                control.type = 'text';
                control.value = value;
                if (previous.hasAttribute('placeholder')) control.setAttribute('placeholder', previous.getAttribute('placeholder'));
            }
            previous.replaceWith(control);
        }

        country.addEventListener('change', updateState);
        updateState();
    }

    function ready() {
        document.querySelectorAll('.rm-auth').forEach(init);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();
}());
