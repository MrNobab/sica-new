/* ═══════════════════════════════════════════════════════════
   ARTIGO — Interactions
═══════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    /* ── Sticky Sidebar ──────────────────────────────────────
       position:sticky breaks when overflow-x:hidden is on
       <body>/<html>. Fix: keep the outer <aside> in grid flow
       (preserves column width) and fix the inner wrapper.
    ──────────────────────────────────────────────────────── */
    function initStickySidebar() {
        var inner  = document.querySelector('.art-sidebar__sticky');
        var outer  = document.querySelector('.art-sidebar');   /* grid column anchor */
        var layout = document.querySelector('.art-layout');
        if (!inner || !outer || !layout) return;

        var TOP = 88;          /* px from top of viewport (below navbar) */
        var isFixed = false;
        var natLeft = 0;
        var natWidth = 0;

        function measure() {
            /* Reset first so we read the natural in-flow values */
            inner.style.cssText = '';
            isFixed = false;
            var r = outer.getBoundingClientRect();
            natLeft  = r.left;
            natWidth = r.width;
        }

        function update() {
            /* Skip on narrow screens — CSS handles layout there */
            if (window.innerWidth <= 1040) {
                if (isFixed) { inner.style.cssText = ''; isFixed = false; }
                return;
            }
            var layoutRect = layout.getBoundingClientRect();
            var innerH     = inner.offsetHeight;
            /* When the layout top has scrolled past the threshold → fix */
            var shouldFix  = layoutRect.top < TOP;
            /* Stop fixing before inner would overflow below layout */
            var hasRoom    = layoutRect.bottom - innerH > TOP;

            if (shouldFix && hasRoom) {
                if (!isFixed) {
                    inner.style.position = 'fixed';
                    inner.style.top      = TOP + 'px';
                    inner.style.left     = natLeft + 'px';
                    inner.style.width    = natWidth + 'px';
                    inner.style.zIndex   = '10';
                    isFixed = true;
                }
            } else {
                if (isFixed) {
                    inner.style.cssText = '';
                    isFixed = false;
                }
            }
        }

        measure();
        update();

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', function () { measure(); update(); }, { passive: true });
    }

    initStickySidebar();

    /* ── TOC: highlight active section on scroll ─────────── */
    var tocLinks = document.querySelectorAll('.art-toc__link');
    var sections = [];

    tocLinks.forEach(function (link) {
        var id = link.getAttribute('href').replace('#', '');
        var el = document.getElementById(id);
        if (el) sections.push({ el: el, link: link });
    });

    function onScroll() {
        var scrollY = window.scrollY + 120;
        var active = sections[0];
        sections.forEach(function (item) {
            if (item.el.offsetTop <= scrollY) active = item;
        });
        tocLinks.forEach(function (l) { l.classList.remove('is-active'); });
        if (active) active.link.classList.add('is-active');
    }

    if (sections.length) {
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ── TOC: smooth scroll ──────────────────────────────── */
    tocLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            var id = link.getAttribute('href').replace('#', '');
            var target = document.getElementById(id);
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    /* ── Copy link button ────────────────────────────────── */
    var copyBtn = document.getElementById('copyLinkBtn');
    if (copyBtn && navigator.clipboard) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(window.location.href).then(function () {
                var orig = copyBtn.innerHTML;
                copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
                setTimeout(function () { copyBtn.innerHTML = orig; }, 2000);
            });
        });
    }

    /* ── Newsletter form ─────────────────────────────────── */
    var form = document.getElementById('newsletterForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input[type="email"]');
            var btn   = form.querySelector('button[type="submit"]');
            if (!input || !input.value) return;
            btn.textContent = 'Subscrito ✓';
            btn.disabled = true;
            input.disabled = true;
        });
    }

})();
