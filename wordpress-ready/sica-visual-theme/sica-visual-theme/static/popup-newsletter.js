(function () {
    'use strict';

    var POPUP_ENABLED = true; // <- false para desativar o popup

    var SESSION_KEY = 'sica_popup_shown';
    var ENDPOINT    = '/omnisend-proxy.php';

    var STRINGS = {
        badge:       'Recurso Gratuito',
        title:       'Receba o guia de marketing digital para PMEs',
        description: 'Estratégias práticas para gerar mais leads sem aumentar o investimento. Direto para o seu email.',
        namePlaceholder:  'O seu nome',
        emailPlaceholder: 'O seu email',
        submit:      'Quero receber o guia →',
        legal:       'Ao submeter, aceita a nossa <a href="/politica-cookies.html" target="_blank" rel="noopener">política de privacidade</a>.',
        successFn:   function (name, email) {
            return 'Obrigado, ' + name + '! O guia foi enviado para ' + email + '.';
        },
        errorGeneric:   'Ocorreu um erro. Tente novamente.',
        errorName:      'Por favor, introduza o seu nome.',
        errorEmail:     'Por favor, introduza um email válido.',
        errorConsent:   'Por favor, aceite os termos para continuar.',
        consentLabel:   'Aceito receber comunicações de marketing da SICA Creative. Posso cancelar a qualquer momento.'
    };

    function init() {
        if (!POPUP_ENABLED) return;
        if (window !== window.top) return;
        if (sessionStorage.getItem(SESSION_KEY)) return;
        setTimeout(show, 5000);
    }

    function show() {
        if (sessionStorage.getItem(SESSION_KEY)) return;
        sessionStorage.setItem(SESSION_KEY, '1');

        var overlay = document.createElement('div');
        overlay.id = 'sica-popup-overlay';
        overlay.innerHTML = buildHTML();
        overlay.style.cssText = [
            'position:fixed', 'inset:0', 'z-index:99999',
            'display:flex', 'align-items:center', 'justify-content:center',
            'background:rgba(52,34,77,0.55)', 'backdrop-filter:blur(4px)',
            'padding:16px', 'opacity:0', 'transition:opacity .25s ease'
        ].join(';');

        document.body.appendChild(overlay);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { overlay.style.opacity = '1'; });
        });

        overlay.querySelector('#sica-popup-close').addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        overlay.querySelector('#sica-popup-form').addEventListener('submit', handleSubmit);
        document.addEventListener('keydown', onEsc);
    }

    function buildHTML() {
        return '<div id="sica-popup-box" style="' + [
            'background:#FFFFFF', 'border-radius:16px',
            'box-shadow:0 24px 64px rgba(52,34,77,0.18)',
            'padding:36px 32px 28px', 'max-width:420px', 'width:100%',
            'position:relative', 'font-family:Inter,system-ui,sans-serif'
        ].join(';') + '">' +

        '<button id="sica-popup-close" aria-label="Fechar" style="' + [
            'position:absolute', 'top:14px', 'right:16px',
            'background:none', 'border:none', 'cursor:pointer',
            'font-size:20px', 'line-height:1', 'color:#6F5AA3',
            'padding:4px', 'opacity:.7'
        ].join(';') + '">&#x2715;</button>' +

        '<span style="' + [
            'display:inline-block', 'background:#c6db5b', 'color:#34224D',
            'font-size:11px', 'font-weight:700', 'letter-spacing:.06em',
            'text-transform:uppercase', 'border-radius:20px',
            'padding:4px 12px', 'margin-bottom:16px'
        ].join(';') + '">' + STRINGS.badge + '</span>' +

        '<h2 style="' + [
            'margin:0 0 12px', 'font-size:20px', 'font-weight:800',
            'line-height:1.25', 'color:#34224D'
        ].join(';') + '">' + STRINGS.title + '</h2>' +

        '<p style="' + [
            'margin:0 0 24px', 'font-size:14px', 'line-height:1.6',
            'color:#5a5a72'
        ].join(';') + '">' + STRINGS.description + '</p>' +

        '<form id="sica-popup-form" novalidate>' +

        '<input id="sica-popup-name" type="text" placeholder="' + STRINGS.namePlaceholder + '" autocomplete="given-name" style="' + fieldStyle() + '" />' +
        '<div id="sica-popup-name-err" style="' + errStyle() + '"></div>' +

        '<input id="sica-popup-email" type="email" placeholder="' + STRINGS.emailPlaceholder + '" autocomplete="email" style="' + fieldStyle() + 'margin-top:10px" />' +
        '<div id="sica-popup-email-err" style="' + errStyle() + '"></div>' +

        '<label style="display:flex;align-items:flex-start;gap:10px;margin-top:14px;cursor:pointer">' +
        '<input id="sica-popup-consent" type="checkbox" style="margin-top:2px;flex-shrink:0;accent-color:#6F5AA3;width:15px;height:15px;cursor:pointer" />' +
        '<span style="font-size:13px;color:#5a5a72;line-height:1.5">' + STRINGS.consentLabel + '</span>' +
        '</label>' +
        '<div id="sica-popup-consent-err" style="' + errStyle() + '"></div>' +

        '<div id="sica-popup-generic-err" style="' + errStyle() + 'margin-top:4px"></div>' +

        '<button type="submit" id="sica-popup-submit" style="' + [
            'margin-top:18px', 'width:100%', 'padding:14px 20px',
            'background:#c6db5b', 'color:#34224D', 'font-weight:700',
            'font-size:15px', 'border:none', 'border-radius:10px',
            'cursor:pointer', 'transition:transform .15s,box-shadow .15s',
            'font-family:inherit'
        ].join(';') + '" onmouseover="this.style.transform=\'scale(1.02)\';this.style.boxShadow=\'0 6px 20px rgba(198,219,91,.45)\'" onmouseout="this.style.transform=\'\';this.style.boxShadow=\'\'">' + STRINGS.submit + '</button>' +

        '</form>' +

        '<p style="' + [
            'margin:14px 0 0', 'font-size:11px', 'color:#9898a8', 'line-height:1.5'
        ].join(';') + '">' + STRINGS.legal + '</p>' +

        '<div id="sica-popup-success" style="display:none;text-align:center;padding:12px 0 4px">' +
        '<p style="font-size:15px;font-weight:600;color:#34224D;margin:0"></p>' +
        '</div>' +

        '</div>';
    }

    function fieldStyle() {
        return [
            'display:block', 'width:100%', 'box-sizing:border-box',
            'padding:12px 14px', 'border:1.5px solid #e2e2ec',
            'border-radius:8px', 'font-size:14px', 'color:#34224D',
            'font-family:inherit', 'outline:none',
            'transition:border-color .15s'
        ].join(';') + ';';
    }

    function errStyle() {
        return 'font-size:12px;color:#e05252;min-height:16px;margin-top:4px;';
    }

    function handleSubmit(e) {
        e.preventDefault();
        var nameEl   = document.getElementById('sica-popup-name');
        var emailEl  = document.getElementById('sica-popup-email');
        var nameErr    = document.getElementById('sica-popup-name-err');
        var emailErr   = document.getElementById('sica-popup-email-err');
        var consentErr = document.getElementById('sica-popup-consent-err');
        var genErr     = document.getElementById('sica-popup-generic-err');
        var submitBtn  = document.getElementById('sica-popup-submit');
        var consentEl  = document.getElementById('sica-popup-consent');

        nameErr.textContent    = '';
        emailErr.textContent   = '';
        consentErr.textContent = '';
        genErr.textContent     = '';

        var name  = nameEl.value.trim();
        var email = emailEl.value.trim();
        var valid = true;

        if (!name) {
            nameErr.textContent = STRINGS.errorName;
            valid = false;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailErr.textContent = STRINGS.errorEmail;
            valid = false;
        }
        if (!consentEl.checked) {
            consentErr.textContent = STRINGS.errorConsent;
            valid = false;
        }
        if (!valid) return;

        submitBtn.disabled   = true;
        submitBtn.textContent = '...';

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, email: email, source: 'popup' })
        })
        .then(function (res) {
            if (!res.ok) throw new Error('server');
            return res.json();
        })
        .then(function () {
            showSuccess(name, email);
        })
        .catch(function () {
            genErr.textContent  = STRINGS.errorGeneric;
            submitBtn.disabled  = false;
            submitBtn.textContent = STRINGS.submit;
        });
    }

    function showSuccess(name, email) {
        var form    = document.getElementById('sica-popup-form');
        var success = document.getElementById('sica-popup-success');
        form.style.display = 'none';
        success.style.display = 'block';
        success.querySelector('p').textContent = STRINGS.successFn(name, email);
        setTimeout(close, 3000);
    }

    function close() {
        var overlay = document.getElementById('sica-popup-overlay');
        if (!overlay) return;
        document.removeEventListener('keydown', onEsc);
        overlay.style.opacity = '0';
        setTimeout(function () { overlay.parentNode && overlay.parentNode.removeChild(overlay); }, 260);
    }

    function onEsc(e) {
        if (e.key === 'Escape') close();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
