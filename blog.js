/* ═══════════════════════════════════════════════════════════
   BLOG — Interactions
═══════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    /* ── Helpers ─────────────────────────────────────────── */
    function hide(el) { el.setAttribute('data-hidden', ''); }
    function show(el) { el.removeAttribute('data-hidden'); }
    function isHidden(el) { return el.hasAttribute('data-hidden'); }

    /* ── DOM refs ────────────────────────────────────────── */
    var catBtns       = document.querySelectorAll('.blog-cat');
    var cards         = document.querySelectorAll('.blog-card');
    var featPrimary   = document.querySelector('.blog-featured__primary');
    var miniItems     = document.querySelectorAll('.blog-featured__mini');
    var topicSections = document.querySelectorAll('.blog-topic');
    var searchInput   = document.getElementById('blogSearchInput');

    var activeCat = 'todos';
    var searchQuery = '';

    /* ── Filter logic ─────────────────────────────────────── */
    function applyFilters() {
        /* Featured primary */
        if (featPrimary) {
            var fCat = featPrimary.dataset.cat;
            var catMatch = activeCat === 'todos' || fCat === activeCat;
            var textMatch = matchesSearch(featPrimary);
            if (catMatch && textMatch) { show(featPrimary); } else { hide(featPrimary); }
        }

        /* Featured mini rail items */
        miniItems.forEach(function (mini) {
            var catMatch = activeCat === 'todos' || mini.dataset.cat === activeCat;
            var textMatch = matchesSearch(mini);
            if (catMatch && textMatch) { show(mini); } else { hide(mini); }
        });

        /* Topic section cards */
        cards.forEach(function (card) {
            var catMatch = activeCat === 'todos' || card.dataset.cat === activeCat;
            var textMatch = matchesSearch(card);
            if (catMatch && textMatch) { show(card); } else { hide(card); }
        });

        /* Hide/show topic sections based on whether they have visible cards */
        topicSections.forEach(function (section) {
            var sectionCards = section.querySelectorAll('.blog-card');
            var hasVisible = false;
            sectionCards.forEach(function (c) { if (!isHidden(c)) { hasVisible = true; } });
            if (hasVisible) {
                section.classList.remove('is-empty');
            } else {
                section.classList.add('is-empty');
            }
        });
    }

    /* Returns true if the element's text content matches the current search query */
    function matchesSearch(el) {
        if (!searchQuery) return true;
        var text = el.textContent.toLowerCase();
        return text.indexOf(searchQuery) !== -1;
    }

    /* ── Category pills ──────────────────────────────────── */
    catBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeCat = btn.dataset.cat;

            catBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            applyFilters();
        });
    });

    /* ── Search (debounced) ──────────────────────────────── */
    var searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchQuery = searchInput.value.trim().toLowerCase();
                applyFilters();
            }, 220);
        });

        /* Clear on Escape */
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchQuery = '';
                applyFilters();
            }
        });
    }

    /* ── Newsletter form ─────────────────────────────────── */
    var newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var emailInput = newsletterForm.querySelector('input[type="email"]');
            var btn = newsletterForm.querySelector('button[type="submit"]');
            if (!emailInput || !emailInput.value) return;

            btn.textContent = 'Subscrito ✓';
            btn.disabled = true;
            emailInput.disabled = true;
        });
    }

})();
