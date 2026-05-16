(function () {
  'use strict';

  var STORAGE_KEY = 'sica_cookie_consent';
  var PURPLE = '#34224D';
  var PURPLE_LIGHT = '#6F5AA3';
  var GA4_ID = 'G-XXXXXXXXXX';
  var GADS_ID = 'AW-XXXXXXXXX';
  var META_ID = 'XXXXXXXXXXXXXXX';

  /* ── Helpers ── */

  function getConsent() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) { return null; }
  }

  function saveConsent(prefs) {
    prefs.ts = Date.now();
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs)); } catch (e) {}
  }

  function lockScroll() {
    document.documentElement.style.overflow = 'hidden';
  }

  function unlockScroll() {
    document.documentElement.style.overflow = '';
  }

  /* ── Script injection ── */

  function injectGA4() {
    if (window.__sica_ga4_loaded) return;
    window.__sica_ga4_loaded = true;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA4_ID;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;
    gtag('js', new Date());
    gtag('config', GA4_ID);
  }

  function injectGoogleAds() {
    if (window.__sica_gads_loaded) return;
    window.__sica_gads_loaded = true;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GADS_ID;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;
    gtag('js', new Date());
    gtag('config', GADS_ID);
  }

  function injectMetaPixel() {
    if (window.__sica_meta_loaded) return;
    window.__sica_meta_loaded = true;
    /* eslint-disable */
    !function(f,b,e,v,n,t,s){
      if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)
    }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */
    window.fbq('init', META_ID);
    window.fbq('track', 'PageView');
  }

  function applyConsent(prefs) {
    if (prefs.estatisticas) injectGA4();
    if (prefs.marketing) {
      injectGoogleAds();
      injectMetaPixel();
    }
  }

  /* ── Styles ── */

  function injectStyles() {
    var css = [
      '#sica-cc-backdrop{position:fixed;inset:0;background:rgba(15,10,30,0.82);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box}',
      '#sica-cc-modal{background:#fff;border-radius:14px;max-width:480px;width:100%;padding:28px;box-shadow:0 8px 40px rgba(15,10,30,0.18);position:relative;font-family:\'Inter\',sans-serif;box-sizing:border-box}',
      '#sica-cc-close{position:absolute;top:14px;right:16px;background:none;border:none;cursor:pointer;font-size:18px;color:#888;line-height:1;padding:4px 6px;border-radius:6px;transition:color 0.15s,background 0.15s}',
      '#sica-cc-close:hover{color:#333;background:#f0f0f0}',
      '#sica-cc-title{text-align:center;font-size:1.05rem;font-weight:700;color:' + PURPLE + ';margin:0 0 12px;letter-spacing:-0.01em}',
      '#sica-cc-desc{font-size:13px;color:#555;line-height:1.55;margin:0 0 8px;text-align:center}',
      '#sica-cc-policy-link{display:block;text-align:center;font-size:13px;color:' + PURPLE + ';text-decoration:none;margin-bottom:20px;font-weight:500}',
      '#sica-cc-policy-link:hover{text-decoration:underline}',
      '#sica-cc-btns{display:flex;gap:8px;align-items:center}',
      '#sica-cc-btn-accept{flex:1.4;background:' + PURPLE + ';color:#fff;border:none;border-radius:8px;padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:opacity 0.15s,transform 0.15s}',
      '#sica-cc-btn-accept:hover{opacity:0.88;transform:translateY(-1px)}',
      '.sica-cc-outline{flex:1;background:#fff;color:' + PURPLE + ';border:1.5px solid ' + PURPLE + ';border-radius:8px;padding:10px 10px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s}',
      '.sica-cc-outline:hover{background:' + PURPLE + ';color:#fff}',
      '#sica-cc-prefs{display:none;margin-top:4px}',
      '.sica-cc-row{display:flex;align-items:flex-start;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f2;gap:12px}',
      '.sica-cc-row:last-child{border-bottom:none}',
      '.sica-cc-row-label{font-size:13px;font-weight:600;color:#1a1a2e;margin:0 0 2px}',
      '.sica-cc-row-desc{font-size:12px;color:#888;margin:0}',
      '.sica-cc-row input[type=checkbox]{width:16px;height:16px;accent-color:' + PURPLE + ';cursor:pointer;flex-shrink:0;margin-top:2px}',
      '.sica-cc-badge{font-size:10px;font-weight:700;background:#f3f0f8;color:' + PURPLE + ';border-radius:4px;padding:2px 6px;white-space:nowrap}',
      '#sica-cc-prefs-btns{display:flex;gap:8px;margin-top:16px}',
      '#sica-cc-btn-save{flex:1.4;background:' + PURPLE + ';color:#fff;border:none;border-radius:8px;padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:opacity 0.15s,transform 0.15s}',
      '#sica-cc-btn-save:hover{opacity:0.88;transform:translateY(-1px)}',
    ].join('');

    var style = document.createElement('style');
    style.id = 'sica-cc-styles';
    style.textContent = css;
    document.head.appendChild(style);
  }

  /* ── Modal ── */

  function createModal() {
    var backdrop = document.createElement('div');
    backdrop.id = 'sica-cc-backdrop';

    backdrop.innerHTML = [
      '<div id="sica-cc-modal" role="dialog" aria-modal="true" aria-labelledby="sica-cc-title">',
        '<button id="sica-cc-close" aria-label="Fechar">&#x2715;</button>',
        '<p id="sica-cc-title">Gerir Consentimento</p>',

        '<!-- default state -->',
        '<div id="sica-cc-default">',
          '<p id="sica-cc-desc">Usamos cookies para melhorar a sua experiência, medir a audiência e apoiar as nossas campanhas. Pode gerir as suas preferências abaixo.</p>',
          '<a id="sica-cc-policy-link" href="/politica-cookies.html">Política de Cookies &rarr;</a>',
          '<div id="sica-cc-btns">',
            '<button id="sica-cc-btn-accept">Aceitar</button>',
            '<button class="sica-cc-outline" id="sica-cc-btn-refuse">Recusar</button>',
            '<button class="sica-cc-outline" id="sica-cc-btn-prefs-toggle">Ver Prefer&ecirc;ncias</button>',
          '</div>',
        '</div>',

        '<!-- preferences state -->',
        '<div id="sica-cc-prefs">',
          '<div class="sica-cc-row">',
            '<div><p class="sica-cc-row-label">Essenciais</p><p class="sica-cc-row-desc">Necess&aacute;rios para o funcionamento do site</p></div>',
            '<div style="display:flex;align-items:center;gap:6px"><span class="sica-cc-badge">Sempre ativo</span><input type="checkbox" id="sica-chk-essential" checked disabled></div>',
          '</div>',
          '<div class="sica-cc-row">',
            '<div><p class="sica-cc-row-label">Estat&iacute;sticas</p><p class="sica-cc-row-desc">Google Analytics — ajuda-nos a perceber como o site &eacute; usado</p></div>',
            '<input type="checkbox" id="sica-chk-stats">',
          '</div>',
          '<div class="sica-cc-row">',
            '<div><p class="sica-cc-row-label">Marketing</p><p class="sica-cc-row-desc">Google Ads &amp; Meta Pixel — personalize an&uacute;ncios e meça campanhas</p></div>',
            '<input type="checkbox" id="sica-chk-marketing">',
          '</div>',
          '<div class="sica-cc-row">',
            '<div><p class="sica-cc-row-label">Terceiros</p><p class="sica-cc-row-desc">Outros servi&ccedil;os externos (ex: Calendly, chatbots)</p></div>',
            '<input type="checkbox" id="sica-chk-third">',
          '</div>',
          '<div id="sica-cc-prefs-btns">',
            '<button id="sica-cc-btn-save">Guardar prefer&ecirc;ncias</button>',
            '<button class="sica-cc-outline" id="sica-cc-btn-back">&larr; Voltar</button>',
          '</div>',
        '</div>',
      '</div>',
    ].join('');

    return backdrop;
  }

  function dismiss(backdrop) {
    backdrop.remove();
    var style = document.getElementById('sica-cc-styles');
    if (style) style.remove();
    unlockScroll();
  }

  function showModal() {
    injectStyles();
    var backdrop = createModal();
    document.body.appendChild(backdrop);
    lockScroll();

    var defaultPane = document.getElementById('sica-cc-default');
    var prefsPane   = document.getElementById('sica-cc-prefs');

    function showPrefs() {
      defaultPane.style.display = 'none';
      prefsPane.style.display   = 'block';
    }

    function showDefault() {
      prefsPane.style.display   = 'none';
      defaultPane.style.display = 'block';
    }

    /* close button → refuse (essentials only) */
    document.getElementById('sica-cc-close').addEventListener('click', function () {
      saveConsent({ essencial: true, estatisticas: false, marketing: false, terceiros: false });
      dismiss(backdrop);
    });

    /* Accept all */
    document.getElementById('sica-cc-btn-accept').addEventListener('click', function () {
      var prefs = { essencial: true, estatisticas: true, marketing: true, terceiros: true };
      saveConsent(prefs);
      applyConsent(prefs);
      dismiss(backdrop);
    });

    /* Refuse → essentials only */
    document.getElementById('sica-cc-btn-refuse').addEventListener('click', function () {
      saveConsent({ essencial: true, estatisticas: false, marketing: false, terceiros: false });
      dismiss(backdrop);
    });

    /* Toggle to preferences panel */
    document.getElementById('sica-cc-btn-prefs-toggle').addEventListener('click', showPrefs);

    /* Back to default */
    document.getElementById('sica-cc-btn-back').addEventListener('click', showDefault);

    /* Save preferences */
    document.getElementById('sica-cc-btn-save').addEventListener('click', function () {
      var prefs = {
        essencial:   true,
        estatisticas: document.getElementById('sica-chk-stats').checked,
        marketing:    document.getElementById('sica-chk-marketing').checked,
        terceiros:    document.getElementById('sica-chk-third').checked,
      };
      saveConsent(prefs);
      applyConsent(prefs);
      dismiss(backdrop);
    });
  }

  /* ── Init ── */

  var consent = getConsent();
  if (consent) {
    applyConsent(consent);
  } else {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', showModal);
    } else {
      showModal();
    }
  }

}());
