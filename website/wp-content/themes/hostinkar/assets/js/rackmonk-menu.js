(function () {
    'use strict';
    document.querySelectorAll('.rm-site-header').forEach(function (header, headerIndex) {
        var nav = header.querySelector('.rm-site-nav');
        var toggle = header.querySelector('.rm-menu-toggle');
        if (!nav || !toggle) return;
        header.classList.add('rm-nav-ready');
        var desktop = window.matchMedia('(min-width: 992px)');
        var dropdowns = [];
        function closeDropdowns() {
            dropdowns.forEach(function (entry) {
                entry.item.classList.remove('rm-submenu-open');
                entry.item.classList.add('rm-dropdown-closed');
                entry.button.setAttribute('aria-expanded', 'false');
            });
        }
        function close() {
            nav.classList.remove('is-open');
            document.body.classList.remove('rm-navigation-open');
            toggle.setAttribute('aria-expanded', 'false');
            closeDropdowns();
        }
        toggle.addEventListener('click', function () {
            var open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            nav.classList.toggle('is-open', open);
            document.body.classList.toggle('rm-navigation-open', open);
            if (!open) closeDropdowns();
        });
        nav.querySelectorAll('.menu-item-has-children').forEach(function (item, index) {
            var submenu = Array.from(item.children).find(function (child) { return child.classList.contains('rm-mega-panel') || child.classList.contains('sub-menu'); });
            var link = Array.from(item.children).find(function (child) { return child.tagName === 'A'; });
            if (!submenu || !link) return;
            submenu.id = 'rm-submenu-' + headerIndex + '-' + index;
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'rm-submenu-toggle';
            button.setAttribute('aria-label', link.textContent.trim() + ' submenu');
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-controls', submenu.id);
            button.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
            link.after(button);
            var touchFocus = false;
            function positionPanel() {
                if (!desktop.matches || !submenu.classList.contains('rm-mega-panel')) return;
                submenu.style.setProperty('--rm-panel-shift', '0px');
                var rect = submenu.getBoundingClientRect();
                var shift = Math.max(16 - rect.left, 0) - Math.max(rect.right - window.innerWidth + 16, 0);
                submenu.style.setProperty('--rm-panel-shift', shift + 'px');
            }
            dropdowns.push({ item: item, button: button, position: positionPanel });
            item.addEventListener('pointerdown', function (event) { touchFocus = event.pointerType === 'touch'; });
            button.addEventListener('click', function () {
                var open = button.getAttribute('aria-expanded') !== 'true';
                button.setAttribute('aria-expanded', String(open));
                item.classList.toggle('rm-submenu-open', open);
                item.classList.toggle('rm-dropdown-closed', !open);
                if (open) positionPanel();
                touchFocus = false;
            });
            item.addEventListener('pointerenter', function (event) {
                if (!desktop.matches || event.pointerType === 'touch') return;
                item.classList.remove('rm-dropdown-closed');
                button.setAttribute('aria-expanded', 'true');
                positionPanel();
            });
            item.addEventListener('pointerleave', function () {
                if (!desktop.matches || item.contains(document.activeElement)) return;
                item.classList.remove('rm-submenu-open', 'rm-dropdown-closed');
                button.setAttribute('aria-expanded', 'false');
            });
            item.addEventListener('focusin', function () {
                if (!desktop.matches || touchFocus) return;
                item.classList.remove('rm-dropdown-closed');
                button.setAttribute('aria-expanded', 'true');
                positionPanel();
            });
            item.addEventListener('focusout', function () {
                window.requestAnimationFrame(function () {
                    if (item.contains(document.activeElement) || item.matches(':hover')) return;
                    item.classList.remove('rm-submenu-open');
                    button.setAttribute('aria-expanded', 'false');
                });
            });
        });
        header.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            var owner = event.target.closest('.rm-site-menu > li');
            var focusTarget = desktop.matches && owner ? owner.querySelector('a') : toggle;
            if (focusTarget) focusTarget.focus();
            close();
        });
        document.addEventListener('click', function (event) { if (!header.contains(event.target)) close(); });
        nav.addEventListener('click', function (event) { if (!desktop.matches && event.target.closest('a')) close(); });
        desktop.addEventListener('change', function () { close(); dropdowns.forEach(function (entry) { entry.position(); }); });
        window.addEventListener('resize', function () { dropdowns.forEach(function (entry) { entry.position(); }); });
    });
})();
