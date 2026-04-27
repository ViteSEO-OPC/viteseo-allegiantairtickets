/**
 * Contact Form 7 — toast notifications.
 * Listens for CF7's built-in DOM events and shows a non-blocking toast.
 *
 * Events: wpcf7mailsent | wpcf7mailfailed | wpcf7invalid | wpcf7spam
 */
(function () {
  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  if (window.__ilegCf7ToastBound) return;
  window.__ilegCf7ToastBound = true;

  /* ---- i18n ---- */
  var lang = (document.documentElement.lang || 'en').toLowerCase().slice(0, 2);
  if (location.pathname.indexOf('/ko/') === 0) lang = 'ko';

  var T = {
    en: {
      sent: { title: 'Message sent', body: "Thanks! We'll get back to you shortly." },
      failed: { title: 'Send failed', body: "We couldn't send your message. Please try again or email us directly." },
      invalid: { title: 'Check your form', body: 'Some fields need attention. Please review and try again.' },
      spam: { title: 'Submission blocked', body: 'Your message was flagged as spam. If this is a mistake, please contact us directly.' },
      close: 'Dismiss'
    },
    ko: {
      sent: { title: '메시지가 전송되었습니다', body: '감사합니다! 곧 회신 드리겠습니다.' },
      failed: { title: '전송 실패', body: '메시지를 전송하지 못했습니다. 다시 시도하거나 이메일로 직접 보내주세요.' },
      invalid: { title: '입력 내용을 확인해 주세요', body: '일부 항목을 확인하고 다시 제출해 주세요.' },
      spam: { title: '제출이 차단되었습니다', body: '스팸으로 표시되었습니다. 오류라면 직접 연락 주세요.' },
      close: '닫기'
    }
  };
  var L = T[lang] || T.en;

  /* ---- styles (injected once) ---- */
  function injectStyles() {
    if (document.getElementById('ileg-cf7-toast-styles')) return;
    var css =
      '.ileg-toast-stack{position:fixed;right:1rem;bottom:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;max-width:min(380px,calc(100vw - 2rem));pointer-events:none;}' +
      '.ileg-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:.75rem;background:#ffffff;border-radius:14px;padding:.85rem 1rem;box-shadow:0 12px 32px rgba(22,50,79,.18);border-left:4px solid #FD593C;font-family:"Outfit",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#16324f;opacity:0;transform:translateY(8px);transition:opacity .22s ease,transform .22s ease;}' +
      '.ileg-toast.is-visible{opacity:1;transform:translateY(0);}' +
      '.ileg-toast.is-leaving{opacity:0;transform:translateY(8px);}' +
      '.ileg-toast--success{border-left-color:#16a34a;}' +
      '.ileg-toast--error{border-left-color:#dc2626;}' +
      '.ileg-toast--warning{border-left-color:#f59e0b;}' +
      '.ileg-toast__icon{flex-shrink:0;width:28px;height:28px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:.95rem;color:#ffffff;}' +
      '.ileg-toast--success .ileg-toast__icon{background:#16a34a;}' +
      '.ileg-toast--error   .ileg-toast__icon{background:#dc2626;}' +
      '.ileg-toast--warning .ileg-toast__icon{background:#f59e0b;}' +
      '.ileg-toast__body{flex:1;min-width:0;}' +
      '.ileg-toast__title{font-weight:700;font-size:.95rem;line-height:1.3;margin:0 0 .15rem;}' +
      '.ileg-toast__msg{font-size:.88rem;line-height:1.45;margin:0;color:#44546a;}' +
      '.ileg-toast__close{flex-shrink:0;background:transparent;border:none;color:#94a3b8;font-size:1.1rem;line-height:1;padding:.15rem .35rem;cursor:pointer;border-radius:6px;}' +
      '.ileg-toast__close:hover{background:rgba(22,50,79,.08);color:#16324f;}' +
      '@media (max-width:520px){.ileg-toast-stack{left:1rem;right:1rem;max-width:none;}}' +
      '@media (prefers-reduced-motion:reduce){.ileg-toast{transition:none;}}';
    var style = document.createElement('style');
    style.id = 'ileg-cf7-toast-styles';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  }

  /* ---- stack container ---- */
  function getStack() {
    var stack = document.querySelector('.ileg-toast-stack');
    if (stack) return stack;
    stack = document.createElement('div');
    stack.className = 'ileg-toast-stack';
    stack.setAttribute('role', 'region');
    stack.setAttribute('aria-live', 'polite');
    stack.setAttribute('aria-label', 'Notifications');
    document.body.appendChild(stack);
    return stack;
  }

  /* ---- icon glyphs (FA6 already enqueued globally) ---- */
  var ICONS = {
    success: 'fa-solid fa-check',
    error: 'fa-solid fa-xmark',
    warning: 'fa-solid fa-triangle-exclamation'
  };

  function showToast(variant, title, message) {
    injectStyles();
    var stack = getStack();

    var toast = document.createElement('div');
    toast.className = 'ileg-toast ileg-toast--' + variant;
    toast.setAttribute('role', variant === 'error' ? 'alert' : 'status');

    var iconWrap = document.createElement('span');
    iconWrap.className = 'ileg-toast__icon';
    var icon = document.createElement('i');
    icon.className = ICONS[variant] || ICONS.success;
    icon.setAttribute('aria-hidden', 'true');
    iconWrap.appendChild(icon);

    var body = document.createElement('div');
    body.className = 'ileg-toast__body';
    var h = document.createElement('p');
    h.className = 'ileg-toast__title';
    h.textContent = title;
    var m = document.createElement('p');
    m.className = 'ileg-toast__msg';
    m.textContent = message;
    body.appendChild(h);
    body.appendChild(m);

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'ileg-toast__close';
    closeBtn.setAttribute('aria-label', L.close);
    closeBtn.innerHTML = '&times;';

    toast.appendChild(iconWrap);
    toast.appendChild(body);
    toast.appendChild(closeBtn);
    stack.appendChild(toast);

    requestAnimationFrame(function () {
      toast.classList.add('is-visible');
    });

    var dismissTimer = setTimeout(dismiss, variant === 'error' ? 7000 : 5000);
    closeBtn.addEventListener('click', function () {
      clearTimeout(dismissTimer);
      dismiss();
    });

    function dismiss() {
      toast.classList.add('is-leaving');
      toast.classList.remove('is-visible');
      setTimeout(function () {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 240);
    }
  }

  /* ---- bind CF7 events ---- */
  document.addEventListener('wpcf7mailsent', function () {
    showToast('success', L.sent.title, L.sent.body);
  }, false);

  document.addEventListener('wpcf7mailfailed', function () {
    showToast('error', L.failed.title, L.failed.body);
  }, false);

  document.addEventListener('wpcf7invalid', function () {
    showToast('warning', L.invalid.title, L.invalid.body);
  }, false);

  document.addEventListener('wpcf7spam', function () {
    showToast('warning', L.spam.title, L.spam.body);
  }, false);
})();
