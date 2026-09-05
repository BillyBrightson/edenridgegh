/* Eden Ridge — public site behaviour.
   Vanilla ES, no build step. Everything here is an enhancement: the page
   reads and the enquiry form submits with JavaScript disabled. */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------------------------------------------------------------- nav */
  var nav = document.getElementById('siteNav');
  if (nav && nav.dataset.sticky !== '0') {
    var onScroll = function () {
      nav.classList.toggle('scrolled', window.scrollY > 40);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* --------------------------------------------------------- mobile menu */
  var burger = document.getElementById('burgerBtn');
  var mobileMenu = document.getElementById('mobileMenu');
  var mobileClose = document.getElementById('mobileClose');
  var lastFocus = null;

  function trapFocus(container, event) {
    var focusable = container.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (!focusable.length) return;
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function openMenu() {
    if (!mobileMenu) return;
    lastFocus = document.activeElement;
    mobileMenu.classList.add('open');
    mobileMenu.removeAttribute('aria-hidden');
    if (burger) burger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    var target = mobileMenu.querySelector('a, button');
    if (target) target.focus();
  }

  function closeMenu() {
    if (!mobileMenu) return;
    mobileMenu.classList.remove('open');
    mobileMenu.setAttribute('aria-hidden', 'true');
    if (burger) burger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }

  if (burger) burger.addEventListener('click', openMenu);
  if (mobileClose) mobileClose.addEventListener('click', closeMenu);
  if (mobileMenu) {
    mobileMenu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', closeMenu);
    });
    mobileMenu.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMenu();
      if (e.key === 'Tab') trapFocus(mobileMenu, e);
    });
  }

  /* ------------------------------------------------------ reveal on scroll */
  var revealEls = document.querySelectorAll('.reveal');
  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  /* -------------------------------------------------------- gallery filter */
  var filterBtns = document.querySelectorAll('.filter-btn');
  var items = document.querySelectorAll('.masonry-item');
  filterBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      filterBtns.forEach(function (b) {
        b.classList.remove('active');
        b.setAttribute('aria-pressed', 'false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-pressed', 'true');
      var filter = btn.dataset.filter;
      items.forEach(function (item) {
        var show = filter === 'all' || item.dataset.cat === filter;
        if (show) {
          item.hidden = false;
          requestAnimationFrame(function () { item.classList.remove('is-hiding'); });
        } else {
          item.classList.add('is-hiding');
          if (reduceMotion) {
            item.hidden = true;
          } else {
            window.setTimeout(function () {
              if (item.classList.contains('is-hiding')) item.hidden = true;
            }, 300);
          }
        }
      });
      track('gallery_filter', { filter: filter });
    });
  });

  /* -------------------------------------------------------------- lightbox */
  var lightbox = document.getElementById('lightbox');
  var lbImg = document.getElementById('lbImg');
  var lbCap = document.getElementById('lbCap');
  var galleryList = [];
  var galleryIndex = 0;
  var lbLastFocus = null;

  function buildGalleryList() {
    galleryList = Array.prototype.map.call(
      document.querySelectorAll('.masonry-item'),
      function (item) {
        var img = item.querySelector('img');
        return {
          src: item.dataset.full || (img ? img.currentSrc || img.src : ''),
          cap: img ? img.alt : ''
        };
      }
    );
  }
  buildGalleryList();

  function showLightboxAt(index) {
    if (!galleryList.length || !lightbox) return;
    galleryIndex = (index + galleryList.length) % galleryList.length;
    lbImg.src = galleryList[galleryIndex].src;
    lbImg.alt = galleryList[galleryIndex].cap;
    lbCap.textContent = galleryList[galleryIndex].cap;
    openLightboxShell();
  }

  function openLightboxShell() {
    lbLastFocus = document.activeElement;
    lightbox.classList.add('open');
    lightbox.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
    var closeBtn = document.getElementById('lbClose');
    if (closeBtn) closeBtn.focus();
    track('gallery_open', {});
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (lbLastFocus) lbLastFocus.focus();
  }

  window.openLightbox = function (src, cap) {
    galleryList = [{ src: src, cap: cap }];
    galleryIndex = 0;
    lbImg.src = src;
    lbImg.alt = cap;
    lbCap.textContent = cap;
    openLightboxShell();
  };

  document.querySelectorAll('.masonry-item').forEach(function (item, index) {
    item.addEventListener('click', function () {
      buildGalleryList();
      showLightboxAt(index);
    });
    item.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        buildGalleryList();
        showLightboxAt(index);
      }
    });
  });

  document.querySelectorAll('[data-lightbox]').forEach(function (el) {
    el.addEventListener('click', function () {
      window.openLightbox(el.dataset.lightbox, el.dataset.lightboxCaption || el.alt || '');
    });
  });

  if (lightbox) {
    var bind = function (id, handler) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('click', handler);
    };
    bind('lbClose', closeLightbox);
    bind('lbPrev', function () { showLightboxAt(galleryIndex - 1); });
    bind('lbNext', function () { showLightboxAt(galleryIndex + 1); });
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') showLightboxAt(galleryIndex + 1);
      if (e.key === 'ArrowLeft') showLightboxAt(galleryIndex - 1);
      if (e.key === 'Tab') trapFocus(lightbox, e);
    });

    // Swipe
    var touchX = null;
    lightbox.addEventListener('touchstart', function (e) {
      touchX = e.changedTouches[0].clientX;
    }, { passive: true });
    lightbox.addEventListener('touchend', function (e) {
      if (touchX === null) return;
      var delta = e.changedTouches[0].clientX - touchX;
      if (Math.abs(delta) > 50) showLightboxAt(galleryIndex + (delta < 0 ? 1 : -1));
      touchX = null;
    }, { passive: true });
  }

  /* ------------------------------------------------------ floor plan tabs */
  var planBtns = document.querySelectorAll('.plan-btn');
  var planPanels = document.querySelectorAll('.plan-panel');
  planBtns.forEach(function (btn, index) {
    btn.addEventListener('click', function () {
      planBtns.forEach(function (b) {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
        b.tabIndex = -1;
      });
      planPanels.forEach(function (p) {
        p.classList.remove('active');
        p.hidden = true;
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      btn.tabIndex = 0;
      var panel = document.getElementById(btn.dataset.plan);
      if (panel) {
        panel.classList.add('active');
        panel.hidden = false;
      }
      track('floorplan_view', { plan: btn.textContent.trim() });
    });
    btn.addEventListener('keydown', function (e) {
      var next = null;
      if (e.key === 'ArrowRight') next = planBtns[(index + 1) % planBtns.length];
      if (e.key === 'ArrowLeft') next = planBtns[(index - 1 + planBtns.length) % planBtns.length];
      if (next) {
        e.preventDefault();
        next.focus();
        next.click();
      }
    });
  });

  /* ------------------------------------------------------------------ FAQ */
  function closeFaq(item) {
    item.classList.remove('open');
    var q = item.querySelector('.faq-q');
    var a = item.querySelector('.faq-a');
    if (q) q.setAttribute('aria-expanded', 'false');
    if (a) a.style.maxHeight = null;
  }

  function openFaq(item) {
    item.classList.add('open');
    var q = item.querySelector('.faq-q');
    var a = item.querySelector('.faq-a');
    if (q) q.setAttribute('aria-expanded', 'true');
    if (a) a.style.maxHeight = a.scrollHeight + 'px';
  }

  var faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(function (item) {
    var q = item.querySelector('.faq-q');
    if (!q) return;
    q.addEventListener('click', function () {
      var wasOpen = item.classList.contains('open');
      faqItems.forEach(closeFaq);
      if (!wasOpen) openFaq(item);
    });
  });
  var firstOpen = document.querySelector('.faq-item[data-open="1"]');
  if (firstOpen) openFaq(firstOpen);
  window.addEventListener('resize', function () {
    var open = document.querySelector('.faq-item.open .faq-a');
    if (open) open.style.maxHeight = open.scrollHeight + 'px';
  });

  /* ------------------------------------------------------- enquiry form */
  function serialise(form) {
    return new URLSearchParams(new FormData(form)).toString();
  }

  function clearErrors(form) {
    form.querySelectorAll('.form-row.has-error').forEach(function (row) {
      row.classList.remove('has-error');
    });
    form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
    var alert = form.querySelector('.form-alert');
    if (alert) alert.remove();
  }

  function showFieldError(form, field, message) {
    var input = form.querySelector('[name="' + field + '"]');
    if (!input) return showFormAlert(form, message);
    var row = input.closest('.form-row') || input.parentNode;
    row.classList.add('has-error');
    input.setAttribute('aria-invalid', 'true');
    var span = document.createElement('span');
    span.className = 'field-error';
    span.textContent = message;
    row.appendChild(span);
  }

  function showFormAlert(form, message) {
    var box = document.createElement('div');
    box.className = 'form-alert';
    box.setAttribute('role', 'alert');
    box.textContent = message;
    form.appendChild(box);
  }

  /**
   * A Turnstile token is single-use. Without this, a visitor who trips a
   * validation error — a mistyped email, say — fixes it, submits again and is
   * told the anti-spam check failed, with no way forward but a page reload.
   */
  function resetTurnstile(form) {
    if (typeof window.turnstile === 'undefined') return;
    var widget = form.querySelector('.cf-turnstile');
    if (widget) {
      try { window.turnstile.reset(widget); } catch (e) { /* widget not rendered yet */ }
    }
  }

  document.querySelectorAll('form[data-enquiry]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.fetch) return; // Fall back to a normal POST.
      e.preventDefault();
      clearErrors(form);
      var button = form.querySelector('[type="submit"]');
      if (button) {
        button.setAttribute('aria-busy', 'true');
        button.dataset.label = button.textContent;
        button.textContent = 'Sending…';
      }
      fetch(form.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: serialise(form)
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (button) {
            button.removeAttribute('aria-busy');
            button.textContent = button.dataset.label || 'Send Enquiry';
          }
          if (data.ok) {
            var card = form.closest('.form-card') || form.parentNode;
            var success = document.createElement('div');
            success.className = 'form-success';
            success.setAttribute('role', 'status');
            success.innerHTML = '<h4>Thank you</h4><p></p>';
            success.querySelector('p').textContent = data.message || 'Your enquiry has been received.';
            form.replaceWith(success);
            var note = card.querySelector('.form-note');
            if (note) note.remove();
            track('enquiry_submit', { interest: data.interest || '' });
            if (data.video_url) revealVideo(data.video_url);
          } else if (data.errors) {
            resetTurnstile(form);
            Object.keys(data.errors).forEach(function (field) {
              showFieldError(form, field, data.errors[field]);
            });
            var firstBad = form.querySelector('.has-error input, .has-error select, .has-error textarea');
            if (firstBad) firstBad.focus();
          } else {
            resetTurnstile(form);
            showFormAlert(form, data.error || 'Something went wrong. Please try again.');
          }
        })
        .catch(function () {
          if (button) {
            button.removeAttribute('aria-busy');
            button.textContent = button.dataset.label || 'Send Enquiry';
          }
          showFormAlert(form, 'We could not reach the server. Please check your connection and try again.');
        });
    });
  });

  /* -------------------------------------------------------- video gate */
  var videoModal = document.getElementById('videoModal');

  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('open');
    modal.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
    var focusable = modal.querySelector('input, button');
    if (focusable) focusable.focus();
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-video-request]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      openModal(videoModal);
      track('video_request_open', {});
    });
  });

  if (videoModal) {
    videoModal.addEventListener('click', function (e) {
      if (e.target === videoModal || e.target.hasAttribute('data-modal-close')) closeModal(videoModal);
    });
    videoModal.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal(videoModal);
      if (e.key === 'Tab') trapFocus(videoModal, e);
    });
  }

  function revealVideo(url) {
    var frame = document.getElementById('videoFrame');
    closeModal(videoModal);
    if (!frame || !url) return;
    frame.hidden = false;
    frame.innerHTML = '';
    var iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.title = 'Eden Ridge video walkthrough';
    iframe.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture';
    iframe.allowFullscreen = true;
    frame.appendChild(iframe);
    frame.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
    track('video_request', {});
  }

  // Poster facade: load the third-party player only once the visitor asks.
  document.querySelectorAll('[data-video-play]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      revealVideo(btn.dataset.videoPlay);
    });
  });

  /* ------------------------------------------------- analytics & consent */
  function analyticsAllowed() {
    if (document.body.dataset.consent !== '1') return true;
    return window.localStorage.getItem('eden_consent') === 'granted';
  }

  function track(event, params) {
    if (!analyticsAllowed()) return;
    if (typeof window.gtag === 'function') window.gtag('event', event, params || {});
    if (typeof window.fbq === 'function') window.fbq('trackCustom', event, params || {});
  }
  window.edenTrack = track;

  document.querySelectorAll('a[href^="https://wa.me"]').forEach(function (a) {
    a.addEventListener('click', function () { track('whatsapp_click', {}); });
  });
  document.querySelectorAll('a[href^="tel:"]').forEach(function (a) {
    a.addEventListener('click', function () { track('phone_click', {}); });
  });

  var consent = document.getElementById('consentBar');
  if (consent) {
    if (window.localStorage.getItem('eden_consent')) {
      consent.hidden = true;
    } else {
      consent.hidden = false;
    }
    consent.querySelectorAll('[data-consent]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        window.localStorage.setItem('eden_consent', btn.dataset.consent);
        consent.hidden = true;
        if (btn.dataset.consent === 'granted') window.location.reload();
      });
    });
  }
})();
