/* =========================================================================
   Events Shortcodes & Blocks — onboarding wizard page logic.
   Extracted verbatim from the inline <script> blocks of
   wizard-shortcodes-blocks.html, execution order preserved:

     1. __ECT shared helpers + UTM appender
     2. Step 2 (Layout & Style) behaviour
     3. Step 3 (Events Query) behaviour
     4. Step 4 (Done) behaviour
     5. Step 1 (Editor select) behaviour   ← was after the ect-wizard.js
                                              include, and still is.

   NOT moved here (still inline in the HTML, before the ect-wizard.js
   include): the window.ECT_WIZARD page config and the persisted-state
   bootstrap IIFE. Both have a hard early-run requirement — ECT_WIZARD must
   exist when ect-wizard.js evaluates, and the bootstrap restores
   body[data-editor-selected] / [data-display-mode] / [data-page-created]
   from localStorage before first paint to avoid a flash of wrong state.
   ========================================================================= */

/* =========================================================================
   CONFIG — every external URL, YouTube endpoint and UTM string constant
   used by this file, in one place. Authoritative values from the product
   owner (eventscalendaraddons.com). wordpress.org links NEVER get UTM
   parameters (and none exist on this page).

   UTM scheme:
     utm_source   = ect_plugin  (this wizard's plugin code — ALWAYS, even
                                 when a link points at a sibling addon page)
     utm_medium   = inside
     utm_campaign = destination: demo | get_pro | docs | get_bundle
                    ('policy' is used for the coolplugins.net terms page,
                    which sits outside the eventscalendaraddons.com scheme)
     utm_content  = onboarding_step{N} — the wizard step hosting the link

   The walkthrough video ids live on the Step-1 editor cards in the HTML
   (data-youtube-id) and are owner-canonical:
     block / shortcode / bricks / wpbakery → uL3ToWGncbM  (Events Shortcodes & Blocks)
     elementor                  → 2m74nSrEo0g  (Events Widgets for Elementor)
     divi                       → Z9s-7RgxZu8  (Events Calendar Modules for Divi)
   ========================================================================= */
var ECT_ONBOARDING_CONFIG = {
  // ----- UTM parameter values -----
  UTM_SOURCE: 'ect_plugin',
  UTM_MEDIUM: 'inside',
  CAMPAIGN_DEMO: 'demo',
  CAMPAIGN_GET_PRO: 'get_pro',
  CAMPAIGN_DOCS: 'docs',
  CAMPAIGN_GET_BUNDLE: 'get_bundle',
  CAMPAIGN_POLICY: 'policy',
  CONTENT_STEP_PREFIX: 'onboarding_step',   // + step number (1-based)
  CONTENT_FALLBACK: 'onboarding',           // link outside any wizard step

  // ----- Hosts that receive UTM parameters (wordpress.org intentionally
  //       absent — its links must stay UTM-free) -----
  UTM_HOSTS: ['eventscalendaraddons.com', 'coolplugins.net'],

  // ----- Destination detection (drives utm_campaign) -----
  RX_DEMO:   /eventscalendaraddons\.com\/(shortcode|demos|bricks)\//,
  RX_POLICY: /coolplugins\.net\/terms\/usage-tracking/,
  RX_BUNDLE: /eventscalendaraddons\.com\/pricing/,
  RX_DOCS:   /eventscalendaraddons\.com\/docs\//,
  RX_PRO:    /eventscalendaraddons\.com\/(get-?pro|plugin\/)/,

  // ----- Sibling wizard routes (Step-1 promo Install / Open buttons) -----
  URL_WIZARD_WIDGETS: 'wizard-events-widgets.html',
  URL_WIZARD_DIVI:    'wizard-events-modules-divi.html',

  // ----- YouTube endpoints (ids come from data-youtube-id in the HTML) -----
  YT_THUMB_BASE: 'https://img.youtube.com/vi/',
  YT_EMBED_BASE: 'https://www.youtube.com/embed/'
};

// Shared helpers used by every step script. Kept minimal so the file
// doesn't accumulate boilerplate.
//
//   __ECT.BUILDER_LABEL   — human-friendly names for the editor value
//   __ECT.setDisplayMode  — flips body[data-display-mode] AND persists
//                            to localStorage so a page reload doesn't
//                            lose the user's "shortcode inside builder"
//                            preference.
window.__ECT = {
  BUILDER_LABEL: {
    block:     'Block Editor',
    bricks:    'Bricks Builder',
    wpbakery:  'WPBakery',
    elementor: 'Elementor',
    divi:      'Divi',
    shortcode: 'Shortcode'
  },
  setDisplayMode: function (mode) {
    var slug = window.ECT_WIZARD.slug;
    var key = 'ect:wizard:' + slug + ':displayMode';
    if (mode === 'shortcode') {
      document.body.setAttribute('data-display-mode', 'shortcode');
      try { localStorage.setItem(key, 'shortcode'); } catch (_) {}
    } else {
      document.body.removeAttribute('data-display-mode');
      try { localStorage.removeItem(key); } catch (_) {}
    }
  },

  /**
   * Query-step primary CTA creates a draft (with loader) for native block /
   * bricks / WPBakery and for the Shortcode editor option — same UX as block.
   */
  step3WillCreateDraft: function () {
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    if (displayMode === 'shortcode') {
      return false;
    }
    if (builder === 'shortcode') {
      return true;
    }
    return builder === 'block' || builder === 'bricks' || builder === 'wpbakery';
  },

  // Track whether the draft page has been created. Flips the Step 4 button
  // set from "Create Draft Page" to "Preview / Edit / Change styling" and
  // marks the progress-strip Done step in success green. Persisted so a
  // reload on Step 4 keeps the post-create state.
  setPageCreated: function (created) {
    var slug = window.ECT_WIZARD.slug;
    var key = 'ect:wizard:' + slug + ':pageCreated';
    if (created) {
      document.body.setAttribute('data-page-created', 'true');
      try { localStorage.setItem(key, '1'); } catch (_) {}
    } else {
      document.body.removeAttribute('data-page-created');
      try { localStorage.removeItem(key); } catch (_) {}
    }
  },

  // Persist draft page URLs returned by the create-page AJAX endpoint.
  setDraftPage: function (draft) {
    var slug = window.ECT_WIZARD && window.ECT_WIZARD.slug;
    if (!slug) return;
    var key = 'ect:wizard:' + slug + ':draftPage';
    if (!draft || !draft.postId) {
      try { localStorage.removeItem(key); } catch (_) {}
      return;
    }
    try { localStorage.setItem(key, JSON.stringify(draft)); } catch (_) {}
  },

  getDraftPage: function () {
    var slug = window.ECT_WIZARD && window.ECT_WIZARD.slug;
    if (!slug) return null;
    try {
      var raw = localStorage.getItem('ect:wizard:' + slug + ':draftPage');
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  },

  // Editor icon URLs — server-localized in ECT_WIZARD.editorIcons (ship path).
  getEditorIcon: function (editorKey) {
    var icons = window.ECT_WIZARD && window.ECT_WIZARD.editorIcons;
    return icons && icons[editorKey] ? icons[editorKey] : '';
  },

  // ---------------- UTM appender ---------------------------------------
  // Walks every outgoing link to eventscalendaraddons.com / coolplugins.net
  // and appends utm_source=ect_plugin&utm_medium=inside plus:
  //   utm_campaign — by destination:
  //     /shortcode/… or /demos/…  → demo
  //     /docs/…                   → docs
  //     /pricing                  → get_bundle
  //     /plugin/… (Pro pages)     → get_pro
  //     coolplugins.net policy    → policy
  //   utm_content  — onboarding_step{N}, derived from the wizard step
  //                  (.ect-wizard-step[data-step]) hosting the link.
  // Explicit campaign override via data-utm="{campaign}" on the link.
  // wordpress.org links are never touched (host not in UTM_HOSTS).
  applyUtm: function () {
    var CONFIG = ECT_ONBOARDING_CONFIG;

    function isTarget(url) {
      return CONFIG.UTM_HOSTS.some(function (h) { return url.indexOf(h) !== -1; });
    }
    function detectCampaign(url, el) {
      if (el && el.dataset.utm) return el.dataset.utm;
      if (CONFIG.RX_DEMO.test(url)) return CONFIG.CAMPAIGN_DEMO;
      if (CONFIG.RX_POLICY.test(url)) return CONFIG.CAMPAIGN_POLICY;
      if (CONFIG.RX_BUNDLE.test(url)) return CONFIG.CAMPAIGN_GET_BUNDLE;
      if (CONFIG.RX_DOCS.test(url)) return CONFIG.CAMPAIGN_DOCS;
      if (CONFIG.RX_PRO.test(url)) return CONFIG.CAMPAIGN_GET_PRO;
      return null;
    }
    // utm_content = onboarding_step{N} for links inside a wizard step —
    // step number comes from the ECT_WIZARD.steps order (1-based).
    function detectContent(el) {
      var stepEl = el && el.closest ? el.closest('.ect-wizard-step') : null;
      if (stepEl) {
        var stepId = stepEl.getAttribute('data-step');
        var steps = (window.ECT_WIZARD && window.ECT_WIZARD.steps) || [];
        for (var i = 0; i < steps.length; i++) {
          if (steps[i].id === stepId) return CONFIG.CONTENT_STEP_PREFIX + (i + 1);
        }
      }
      return CONFIG.CONTENT_FALLBACK;
    }
    function append(url, campaign, content) {
      if (!url || !campaign || url.indexOf('utm_source=') !== -1) return url;
      var params = { utm_source: CONFIG.UTM_SOURCE, utm_medium: CONFIG.UTM_MEDIUM,
                     utm_campaign: campaign, utm_content: content };
      var qs = Object.keys(params).map(function (k) {
        return k + '=' + encodeURIComponent(params[k]);
      }).join('&');
      var sep = url.indexOf('?') === -1 ? '?' : '&';
      var hashIdx = url.indexOf('#');
      if (hashIdx === -1) return url + sep + qs;
      return url.slice(0, hashIdx) + sep + qs + url.slice(hashIdx);
    }

    document.querySelectorAll('a[href]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || !isTarget(href)) return;
      var campaign = detectCampaign(href, a);
      if (campaign) a.setAttribute('href', append(href, campaign, detectContent(a)));
    });
    document.querySelectorAll('[data-demo-url]').forEach(function (el) {
      var url = el.getAttribute('data-demo-url');
      if (!url || !isTarget(url)) return;
      var campaign = detectCampaign(url, el);
      if (campaign) el.setAttribute('data-demo-url', append(url, campaign, detectContent(el)));
    });
  }
};

// Run UTM appender once, after the DOM is parsed. It's idempotent
// (skips URLs that already have utm_source), so late-added links stay safe.
if (document.readyState !== 'loading') window.__ECT.applyUtm();
else document.addEventListener('DOMContentLoaded', window.__ECT.applyUtm);

/* =========================================================================
   Step 2 behaviour: editor-conditional visibility, Pro detection →
   notice + nav swap, filter-bar demo link toggle, view-demo external
   link handling. No live preview column — each option owns its own
   "view demo" trigger that opens a live example in a new tab.
   ========================================================================= */
(function () {
  var step2 = document.querySelector('.ect-wizard-step[data-step="layout-style"]');
  if (!step2) return;

  // ----- View-demo icon → open live example in a new tab --------------
  // Registered in capture phase so it fires before wizard.js's own click
  // handler picks up the card selection. stopImmediatePropagation stops
  // any other document-level handlers from running for this click.
  function openDemo(el) {
    var url = el.getAttribute('data-demo-url');
    if (url) window.open(url, '_blank', 'noopener');
  }
  document.addEventListener('click', function (e) {
    var demo = e.target.closest('[data-demo-url]');
    if (!demo) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    openDemo(demo);
  }, true);
  // Keyboard: span role="button" needs Enter/Space
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var demo = e.target.closest('[data-demo-url]');
    if (!demo) return;
    e.preventDefault();
    openDemo(demo);
  });

  var BUILDER_LABEL = window.__ECT.BUILDER_LABEL;

  // ----- Editor-conditional visibility --------------------------------
  // data-editor-only="shortcode"      → visible only when that editor selected
  // data-editors-visible="a b c"      → visible when one of those editors selected
  // If body[data-display-mode="shortcode"] is set, the user has opted into
  // shortcode display INSIDE their chosen builder — we force the shortcode
  // section-set regardless of what they picked in Step 1. Their Step 1
  // selection is preserved so the final step can create the correct page.
  function applyEditorVisibility() {
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    var editor = builder;
    if (displayMode === 'shortcode') editor = 'shortcode';
    // Elementor/Divi never reach this step in native mode — they're routed
    // to sibling wizards from Step 1. If they somehow do, fall back to
    // shortcode to keep the step usable.
    if (editor === 'elementor' || editor === 'divi') editor = 'shortcode';
    if (editor === 'wpbakery') editor = 'shortcode';

    step2.querySelectorAll('[data-editor-only]').forEach(function (el) {
      el.hidden = el.dataset.editorOnly !== editor;
    });
    step2.querySelectorAll('[data-editors-visible]').forEach(function (el) {
      var list = el.dataset.editorsVisible.split(/\s+/);
      el.hidden = list.indexOf(editor) === -1;
    });

    // Style-3 is Free for shortcode but Pro for bricks — flip its Pro badge
    var style3 = step2.querySelector('.ect-style-card[data-value="style-3"]');
    if (style3) {
      var badge = style3.querySelector('[data-pro-badge-for="bricks"]');
      if (editor === 'bricks') {
        style3.setAttribute('data-pro', 'true');
        if (badge) badge.hidden = false;
      } else {
        style3.removeAttribute('data-pro');
        if (badge) badge.hidden = true;
      }
    }

    updateLayoutDemoLink(editor);
    applyBricksLayoutStyles(editor);
  }

  function updateLayoutDemoLink(editor) {
    var link = step2.querySelector('.ect-settings-row__demo');
    if (!link) return;
    var bricksUrl = link.getAttribute('data-demo-href-bricks');
    var defaultUrl = link.getAttribute('data-demo-href-default');
    if (!bricksUrl || !defaultUrl) return;
    var next = editor === 'bricks' ? bricksUrl : defaultUrl;
    var currentBase = (link.getAttribute('href') || '').split('?')[0].replace(/\/$/, '');
    var nextBase = next.split('?')[0].replace(/\/$/, '');
    if (currentBase === nextBase) return;
    link.setAttribute('href', next);
    if (window.__ECT && window.__ECT.applyUtm) window.__ECT.applyUtm();
  }

  // Bricks-only: List has Style 1 + 2; Grid has Style 1. Do not mutate other editors.
  function applyBricksLayoutStyles(editor) {
    var cards = step2.querySelectorAll('.ect-style-card');
    if (editor !== 'bricks') {
      cards.forEach(function (card) {
        if (!card.hasAttribute('data-ect-bricks-hidden')) return;
        card.hidden = false;
        card.removeAttribute('data-ect-bricks-hidden');
      });
      return;
    }
    var layoutCard = step2.querySelector('[data-editor-only="bricks"] .ect-layout-card.is-selected');
    var layout = layoutCard ? layoutCard.dataset.value : 'list';
    cards.forEach(function (card) {
      var hideFor = (card.getAttribute('data-hide-for') || '').split(/\s+/).filter(Boolean);
      var bricksLayouts = (card.getAttribute('data-bricks-layouts') || '').split(/\s+/).filter(Boolean);
      var hide = hideFor.indexOf('bricks') !== -1
        || (bricksLayouts.length > 0 && bricksLayouts.indexOf(layout) === -1);
      if (hide) {
        card.hidden = true;
        card.setAttribute('data-ect-bricks-hidden', '1');
        card.classList.remove('is-selected');
      } else {
        card.hidden = false;
        card.removeAttribute('data-ect-bricks-hidden');
      }
    });
    var styleGroup = step2.querySelector('[data-required-selection="style"]');
    if (!styleGroup || styleGroup.hidden || styleGroup.closest('[hidden]')) return;
    if (styleGroup.querySelector('.ect-style-card.is-selected:not([hidden])')) return;
    var first = styleGroup.querySelector('.ect-style-card:not([hidden]):not([data-pro])')
      || styleGroup.querySelector('.ect-style-card:not([hidden])');
    if (first) first.click();
  }

  function updateEditorSelectedBodyAttr() {
    var opt = document.querySelector('.ect-editor-option.is-selected');
    if (opt) document.body.setAttribute('data-editor-selected', opt.dataset.value);
    applyEditorVisibility();
  }

  // ----- Pro detection → notice + nav variant swap --------------------
  // Only visible selections count — a Pro option selected in a hidden
  // grid (e.g. user picked Grid in shortcode, then switched to Block)
  // shouldn't trigger the notice while it's off-screen.
  function updateProState() {
    var count = 0;
    step2.querySelectorAll('.is-selected[data-pro="true"]').forEach(function (el) {
      if (el.offsetParent !== null) count++;
    });
    if (document.body.getAttribute('data-tier') === 'pro') count = 0;
    var notice = step2.querySelector('[data-pro-notice]');
    var countEl = step2.querySelector('[data-pro-count]');
    var plural = step2.querySelector('[data-pro-plural]');
    var navRight = step2.querySelector('[data-step2-nav-right]');
    if (notice) notice.hidden = count === 0;
    if (countEl) countEl.textContent = count;
    if (plural) plural.textContent = count === 1 ? '' : 's';
    if (navRight) navRight.setAttribute('data-nav-mode', count > 0 ? 'upgrade' : 'default');
  }

  // ----- "See it live" link next to filter bar toggle -----------------
  // Only visible when Yes is selected (i.e. user wants a filter bar).
  function updateFilterBarDemoLink() {
    var link = step2.querySelector('[data-demo-link="filter-bar"]');
    if (!link) return;
    var yes = step2.querySelector('.ect-toggle-option[data-value="yes"].is-selected');
    link.hidden = !yes;
  }

  // ----- Editor-switch CTA bar ----------------------------------------
  // Two modes, both scoped to Block / Bricks builders (Elementor / Divi
  // make the choice via Step 1 promo):
  //   • to-shortcode: display-mode is native → offer shortcode-inside-builder
  //   • to-native:    display-mode is shortcode → let user revert to native
  // Neither variant touches Step 1's builder selection; both just flip
  // <body data-display-mode> and refresh Step 2.
  function updateEditorSwitchBar() {
    var bar = step2.querySelector('[data-editor-switch]');
    if (!bar) return;
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    var eligible = (builder === 'block' || builder === 'bricks');
    bar.hidden = !eligible;
    if (!eligible) return;

    var builderLabel = BUILDER_LABEL[builder] || builder;
    var nativeThing = (builder === 'block') ? 'events block' : 'events module';
    var title = bar.querySelector('[data-editor-switch-title]');
    var desc  = bar.querySelector('[data-editor-switch-desc]');
    var label = bar.querySelector('[data-editor-switch-cta-label]');
    var icon  = bar.querySelector('[data-editor-switch-icon]');
    var ctaIcon = bar.querySelector('[data-editor-switch-cta-icon]');

    if (displayMode === 'shortcode') {
      bar.setAttribute('data-mode', 'to-native');
      if (title) title.textContent = 'Prefer the native ' + builderLabel + ' method?';
      if (desc)  desc.textContent  = 'Switch back to the native ' + nativeThing + ' — fewer style options but a tighter builder integration.';
      if (label) label.textContent = 'Switch to native ' + builderLabel;
      if (icon)  icon.className    = 'dashicons dashicons-image-rotate';
      if (ctaIcon) ctaIcon.className = 'dashicons dashicons-controls-back';
    } else {
      bar.setAttribute('data-mode', 'to-shortcode');
      if (title) title.textContent = 'Shortcode method has more style options';
      if (desc)  desc.textContent  = '6 layouts, 4 style variants and a Pro filter bar — the widest choice.';
      if (label) label.textContent = "OK, I'll use Shortcode in " + builderLabel;
      if (icon)  icon.className    = 'dashicons dashicons-lightbulb';
      if (ctaIcon) ctaIcon.className = 'dashicons dashicons-arrow-right-alt2';
    }
  }
  step2.addEventListener('click', function (e) {
    var cta = e.target.closest('[data-editor-switch-cta]');
    if (!cta) return;
    e.preventDefault();
    var bar = cta.closest('[data-editor-switch]');
    var mode = bar && bar.getAttribute('data-mode');
    window.__ECT.setDisplayMode(mode === 'to-native' ? null : 'shortcode');
    refreshStep2();
  });

  // ----- Auto-select first non-Pro option in each visible group -------
  // Runs on step-entry so Continue enables without the user having to
  // touch every setting.
  function autoSelectDefaults() {
    step2.querySelectorAll('[data-required-selection]').forEach(function (group) {
      if (group.hidden || group.closest('[hidden]')) return;
      if (group.querySelector('.is-selected')) return;
      var first = group.querySelector(
        '.ect-layout-card:not([data-pro]), .ect-style-card:not([data-pro]), .ect-toggle-option:not([data-pro])'
      );
      if (first) first.click();
    });
  }

  // Native Events Block — align persisted layout with block options (default | minimal).
  function normalizeBlockLayoutSelection() {
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    if (builder !== 'block' || displayMode === 'shortcode') return;
    var blockGrid = step2.querySelector('[data-editor-only="block"][data-required-selection="layout"]');
    if (!blockGrid || blockGrid.hidden) return;
    var layoutVal = null;
    try {
      var raw = localStorage.getItem('ect:wizard:' + window.ECT_WIZARD.slug + ':state');
      if (raw) {
        var s = JSON.parse(raw);
        layoutVal = s && s.selections && s.selections.layout;
      }
    } catch (_) {}
    if (layoutVal === 'minimal-list') layoutVal = 'minimal';
    var allowed = ['default', 'minimal'];
    if (!layoutVal || allowed.indexOf(layoutVal) === -1) layoutVal = 'default';
    var card = blockGrid.querySelector('.ect-layout-card[data-value="' + layoutVal + '"]');
    if (card && !card.classList.contains('is-selected')) card.click();
  }

  // ----- React to any selection change inside this step ---------------
  // wizard.js already handles the actual is-selected toggle. We just
  // refresh derived state (Pro notice, filter-bar demo link).
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.ect-layout-card, .ect-style-card, .ect-toggle-option, .ect-editor-option')) return;
    requestAnimationFrame(function () {
      var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
      var displayMode = document.body.getAttribute('data-display-mode') || 'native';
      var editor = displayMode === 'shortcode' ? 'shortcode' : builder;
      if (editor === 'elementor' || editor === 'divi' || editor === 'wpbakery') editor = 'shortcode';
      if (editor === 'bricks' && e.target.closest('.ect-layout-card')) {
        applyBricksLayoutStyles(editor);
      }
      updateProState();
      updateFilterBarDemoLink();
    });
  });

  // ----- Dynamic Step 2 description -----------------------------------
  // Copy mentions the method the user picked so the setup path is obvious.
  // Elementor / Divi in native mode never reach this step.
  function updateStep2Desc() {
    var el = step2.querySelector('[data-step2-desc]');
    if (!el) return;
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    var via;
    if (displayMode === 'shortcode') {
      via = (builder === 'shortcode')
        ? 'via shortcode'
        : ('via shortcode inside ' + (BUILDER_LABEL[builder] || builder));
    } else if (builder === 'block') {
      via = 'via the Events Block in Block Editor';
    } else if (builder === 'bricks') {
      via = 'via the Events element in Bricks Builder';
    } else if (builder === 'wpbakery') {
      via = 'via the Events Calendar Shortcode module in WPBakery';
    } else {
      via = 'via shortcode';
    }
    el.textContent = 'Pick a layout and style for how you want to show events from The Events Calendar plugin ' + via + '.';
  }

  // ----- Boot on step-enter -------------------------------------------
  function refreshStep2() {
    updateEditorSelectedBodyAttr();
    updateEditorSwitchBar();
    updateStep2Desc();
    autoSelectDefaults();
    normalizeBlockLayoutSelection();
    updateProState();
    updateFilterBarDemoLink();
  }

  document.addEventListener('ect:wizard-step', function (e) {
    if (e.detail.stepId === 'layout-style') {
      requestAnimationFrame(refreshStep2);
    }
  });

  // Also refresh when Step 1 selection changes (user goes Back → picks
  // a different editor → Continue).
  document.addEventListener('click', function (e) {
    if (e.target.closest('.ect-editor-option')) {
      requestAnimationFrame(refreshStep2);
    }
  });

  // No DOMContentLoaded initial refresh — that would fire BEFORE wizard.js
  // restores state, and autoSelectDefaults would overwrite persisted Pro
  // selections with the first non-Pro option. The ect:wizard-step listener
  // above handles activation (RAF-scheduled after wizard.js's render).
})();

/* =========================================================================
   Step 3 behaviour: Select2-style multiselect + editor-conditional
   visibility + Pro detection → notice + nav swap + demo link toggles.
   ========================================================================= */
(function () {
  var step3 = document.querySelector('.ect-wizard-step[data-step="query"]');
  if (!step3) return;

  // ----- Multiselect widget (Select2-style) ---------------------------
  // Stores selected values in a hidden input as CSV so wizard.js can
  // persist / restore state with no extra plumbing. "all" is a mutually-
  // exclusive value: picking All clears specific selections; picking a
  // specific category removes All. Chip removes fall back to All if the
  // list becomes empty.
  function initMultiselect(root) {
    var control    = root.querySelector('[data-multiselect-control]');
    var dropdown   = root.querySelector('[data-multiselect-dropdown]');
    var chips      = root.querySelector('[data-multiselect-chips]');
    var placeholder= root.querySelector('[data-multiselect-placeholder]');
    var input      = root.querySelector('input[type="hidden"]');
    var options    = root.querySelectorAll('[data-multiselect-option]');

    function getValues() { return (input.value || '').split(',').filter(Boolean); }
    function setValues(vals) { input.value = vals.join(','); render(); }

    // Drop persisted values that are no longer in the live option list
    // (e.g. prototype demo slugs after switching to real TEC categories).
    (function sanitizePersisted() {
      var valid = {};
      options.forEach(function (opt) { valid[opt.dataset.value] = true; });
      var cleaned = getValues().filter(function (v) { return valid[v]; });
      if (cleaned.length === 0) cleaned = ['all'];
      if (cleaned.join(',') !== getValues().join(',')) input.value = cleaned.join(',');
    })();

    function render() {
      var vals = getValues();
      chips.innerHTML = '';
      vals.forEach(function (v) {
        var opt = root.querySelector('[data-multiselect-option][data-value="' + v + '"]');
        if (!opt) return;
        var label = opt.querySelector('.ect-multiselect__label').textContent;
        var chip = document.createElement('span');
        chip.className = 'ect-multiselect__chip';
        chip.innerHTML =
          '<span>' + label + '</span>' +
          '<span class="ect-multiselect__chip-remove" role="button" tabindex="0" ' +
            'data-multiselect-remove="' + v + '" aria-label="Remove ' + label + '">' +
            '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>' +
          '</span>';
        chips.appendChild(chip);
      });
      if (placeholder) placeholder.hidden = vals.length > 0;
      options.forEach(function (opt) {
        opt.classList.toggle('is-selected', vals.indexOf(opt.dataset.value) >= 0);
      });
    }

    function toggleValue(value) {
      var vals = getValues();
      if (value === 'all') { setValues(['all']); return; }
      var idx = vals.indexOf(value);
      if (idx >= 0) vals.splice(idx, 1);
      else { vals = vals.filter(function (v) { return v !== 'all'; }); vals.push(value); }
      if (vals.length === 0) vals = ['all'];
      setValues(vals);
    }

    function removeValue(value) {
      var vals = getValues().filter(function (v) { return v !== value; });
      if (vals.length === 0) vals = ['all'];
      setValues(vals);
    }

    function openDropdown()  { dropdown.hidden = false; root.classList.add('is-open');    control.setAttribute('aria-expanded', 'true'); }
    function closeDropdown() { dropdown.hidden = true;  root.classList.remove('is-open'); control.setAttribute('aria-expanded', 'false'); }

    control.addEventListener('click', function (e) {
      if (e.target.closest('[data-multiselect-remove]')) return;
      e.preventDefault();
      dropdown.hidden ? openDropdown() : closeDropdown();
    });
    control.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dropdown.hidden ? openDropdown() : closeDropdown();
      } else if (e.key === 'Escape') closeDropdown();
    });
    dropdown.addEventListener('click', function (e) {
      var opt = e.target.closest('[data-multiselect-option]');
      if (!opt) return;
      e.preventDefault();
      toggleValue(opt.dataset.value);
    });
    chips.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-multiselect-remove]');
      if (!rm) return;
      e.preventDefault();
      e.stopPropagation();
      removeValue(rm.dataset.multiselectRemove);
    });
    chips.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var rm = e.target.closest('[data-multiselect-remove]');
      if (!rm) return;
      e.preventDefault();
      e.stopPropagation();
      removeValue(rm.dataset.multiselectRemove);
    });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) closeDropdown();
    });

    // Expose render so refreshStep3 can re-sync the widget after wizard.js
    // restores the hidden input value on Back navigation.
    root._ecaMultiselectRender = render;
    render();
  }

  step3.querySelectorAll('[data-multiselect]').forEach(initMultiselect);

  function rerenderMultiselects() {
    step3.querySelectorAll('[data-multiselect]').forEach(function (el) {
      if (el._ecaMultiselectRender) el._ecaMultiselectRender();
    });
  }

  // ----- Editor-conditional visibility (mirrors Step 2) ---------------
  function applyEditorVisibility() {
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    var editor = builder;
    if (displayMode === 'shortcode') editor = 'shortcode';
    if (editor === 'elementor' || editor === 'divi') editor = 'shortcode';
    if (editor === 'wpbakery') editor = 'shortcode';

    step3.querySelectorAll('[data-editor-only]').forEach(function (el) {
      el.hidden = el.dataset.editorOnly !== editor;
    });
    step3.querySelectorAll('[data-editors-visible]').forEach(function (el) {
      var list = el.dataset.editorsVisible.split(/\s+/);
      el.hidden = list.indexOf(editor) === -1;
    });
  }

  // ----- Auto-select first non-Pro option in each visible group -------
  function autoSelectDefaults() {
    step3.querySelectorAll('[data-required-selection]').forEach(function (group) {
      if (group.hidden || group.closest('[hidden]')) return;
      if (group.querySelector('.is-selected')) return;
      var first = group.querySelector(
        '.ect-style-card:not([data-pro]), .ect-toggle-option:not([data-pro])'
      );
      if (first) first.click();
    });
  }

  // ----- Pro detection → notice + nav swap (only visible selections) --
  function updateProState() {
    var count = 0;
    step3.querySelectorAll('.is-selected[data-pro="true"]').forEach(function (el) {
      if (el.offsetParent !== null) count++;
    });
    if (document.body.getAttribute('data-tier') === 'pro') count = 0;
    var notice = step3.querySelector('[data-pro-notice]');
    var countEl = step3.querySelector('[data-pro-count]');
    var plural = step3.querySelector('[data-pro-plural]');
    var navRight = step3.querySelector('[data-step3-nav-right]');
    if (notice) notice.hidden = count === 0;
    if (countEl) countEl.textContent = count;
    if (plural) plural.textContent = count === 1 ? '' : 's';
    if (navRight) navRight.setAttribute('data-nav-mode', count > 0 ? 'upgrade' : 'default');
  }

  // ----- Demo links (Featured / Taxonomy) toggle on Yes selection -----
  function updateDemoLinks() {
    var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
    var displayMode = document.body.getAttribute('data-display-mode') || 'native';
    var editor = displayMode === 'shortcode' ? 'shortcode' : builder;
    ['featured', 'taxonomy'].forEach(function (key) {
      var link = step3.querySelector('[data-demo-link="' + key + '"]');
      if (!link) return;
      var group = step3.querySelector('[data-required-selection="' + key + '"]');
      if (!group) return;
      var yes = group.querySelector('.ect-toggle-option[data-value="yes"].is-selected');
      link.hidden = !yes;
      if (key !== 'featured') return;
      var bricksUrl = link.getAttribute('data-demo-href-bricks');
      var defaultUrl = link.getAttribute('data-demo-href-default');
      if (!bricksUrl || !defaultUrl) return;
      var next = editor === 'bricks' ? bricksUrl : defaultUrl;
      var currentBase = (link.getAttribute('href') || '').split('?')[0].replace(/\/$/, '');
      var nextBase = next.split('?')[0].replace(/\/$/, '');
      if (currentBase === nextBase) return;
      link.setAttribute('href', next);
      if (window.__ECT && window.__ECT.applyUtm) window.__ECT.applyUtm();
    });
  }

  // ----- Dynamic primary CTA label + builder-aware hint ---------------
  // Shortcode users get "Continue" (they may just want to copy the code
  // and never create a page). Block / Bricks users get "Create Draft
  // Page" — clicking it also flips body[data-page-created] so Step 4
  // opens in the post-create state. A small hint appears to the LEFT of
  // the button explaining what will be inserted ("Insert Events Block in
  // Block Editor" / "Insert Events Element in Bricks Builder").
  function updateStep3PrimaryCta() {
    var label = step3.querySelector('[data-step3-primary-label]');
    var icon  = step3.querySelector('[data-step3-primary-icon]');
    var hint  = step3.querySelector('[data-step3-create-hint]');
    if (!label || !icon) return;
    var willCreate = window.__ECT.step3WillCreateDraft();
    if (willCreate) {
      label.textContent = 'Create Draft Page';
      icon.className = 'dashicons dashicons-plus-alt2';
      if (hint) {
        var builder = document.body.getAttribute('data-editor-selected') || 'shortcode';
        if (builder === 'block') {
          hint.textContent = 'Insert Events Block in Block Editor';
        } else if (builder === 'wpbakery') {
          hint.textContent = 'Insert Events Shortcode module in WPBakery';
        } else if (builder === 'shortcode') {
          hint.textContent = 'Create a draft page with your events shortcode';
        } else {
          hint.textContent = 'Insert Events Element in Bricks Builder';
        }
        hint.hidden = false;
      }
    } else {
      label.textContent = 'Continue';
      icon.className = 'dashicons dashicons-arrow-right-alt';
      if (hint) hint.hidden = true;
    }
  }

  // Click on the Step 3 primary CTA — draft creation + busy state run in
  // ECT_WIZARD_RUNTIME.beforeNext when advancing from the query step.

  // ----- Boot on step-enter -------------------------------------------
  function refreshStep3() {
    applyEditorVisibility();
    rerenderMultiselects();
    autoSelectDefaults();
    updateProState();
    updateDemoLinks();
    updateStep3PrimaryCta();
  }

  document.addEventListener('ect:wizard-step', function (e) {
    if (e.detail.stepId === 'query') requestAnimationFrame(refreshStep3);
  });

  // Refresh state when user clicks a card / toggle inside Step 3
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.ect-style-card, .ect-toggle-option')) return;
    requestAnimationFrame(function () {
      updateProState();
      updateDemoLinks();
    });
  });

  // Step 1 editor swap → re-render Step 3 visibility next time it opens
  document.addEventListener('click', function (e) {
    if (e.target.closest('.ect-editor-option')) {
      requestAnimationFrame(refreshStep3);
    }
  });

  // No DOMContentLoaded initial refresh (same reasoning as Step 2 — would
  // overwrite persisted state before wizard.js's render() runs).
})();

/* =========================================================================
   Step 4 (Done) behaviour: read wizard state from localStorage, compose
   the shortcode, and drive the pre-create / post-create UI. Method
   (shortcode vs block vs bricks) chooses shortcode card vs builder card;
   body[data-page-created] flips the button set and success-header copy.
   ========================================================================= */
(function () {
  var stepSuccess = document.querySelector('.ect-wizard-step[data-step="success"]');
  if (!stepSuccess) return;

  // Shortcode-attribute mapping tables. Kept next to composeShortcode so
  // the WP integration point is obvious later — the wizard already knows
  // every value, this is just the translation layer.
  var TIME_MAP = { upcoming: 'future', past: 'past', both: 'all' };
  var DATE_FORMAT_MAP = {
    'default': 'default', 'md-y': 'MD,Y', 'fd-y': 'FD,Y', 'dm': 'DM',
    'dml': 'DML', 'df': 'DF', 'md': 'MD', 'fd': 'FD', 'md-yt': 'MD,YT',
    'full': 'full', 'jml': 'jMl', 'd-fy': 'd.FY', 'd-f': 'd.F',
    'ldf': 'ldF', 'mdl': 'Mdl', 'd-ml': 'd.Ml', 'dft': 'dFT'
  };
  var BUILDER_INFO = {
    block:     { label: 'Block Editor',    thing: 'block',             logo: __ECT.getEditorIcon('block') },
    bricks:    { label: 'Bricks Builder',  thing: 'element',           logo: __ECT.getEditorIcon('bricks') },
    wpbakery:  { label: 'WPBakery',        thing: 'shortcode module',  logo: __ECT.getEditorIcon('wpbakery') },
    elementor: { label: 'Elementor',       thing: 'shortcode',         logo: __ECT.getEditorIcon('elementor') },
    divi:      { label: 'Divi',            thing: 'shortcode module',  logo: __ECT.getEditorIcon('divi') }
  };

  // ----- State collection ---------------------------------------------
  // On F5 with Step 4 or 5 active, wizard.js's restoreSelectionsForCurrentStep()
  // only restores the ACTIVE step's cards and inputs — Step 2 and Step 3
  // stay unmarked in the DOM. That used to leave layout/style/time/etc.
  // missing from the summary and the dynamic title falling back to the
  // generic "events listing".
  //
  // Fix: read state directly from localStorage (the true source of truth
  // that wizard.js writes on every change) and use the DOM only for
  // looking up display labels for each persisted value. Falling back to
  // DOM `.is-selected` gives us Step 1 → Step 4 forward navigation the
  // same way (both paths agree).
  function collectState() {
    var persisted = {};
    try {
      var raw = localStorage.getItem('ect:wizard:' + window.ECT_WIZARD.slug + ':state');
      if (raw) {
        var s = JSON.parse(raw);
        if (s && s.selections) persisted = s.selections;
      }
    } catch (_) {}

    var state = {};

    // Builder (Step 1). Prefer persisted state; fall back to DOM class.
    var editorFromDom = document.querySelector('.ect-editor-option.is-selected');
    state.builder = persisted.editor
                 || (editorFromDom && editorFromDom.dataset.value)
                 || 'shortcode';
    state.displayMode = document.body.getAttribute('data-display-mode') || 'native';

    // Required-selection groups (Steps 2 + 3). For each persisted value,
    // look up ANY matching card in the DOM to grab its label and Pro flag —
    // hidden cards are fine, they still expose textContent + data-pro.
    ['layout', 'style', 'filter-bar', 'time', 'featured', 'taxonomy'].forEach(function (name) {
      var value = persisted[name];
      if (value == null) {
        // Fall back to DOM if not in localStorage yet (fresh forward nav).
        var group = document.querySelector(
          '.ect-wizard-step [data-required-selection="' + name + '"]:not([hidden]) .is-selected'
        );
        if (!group) return;
        value = group.dataset.value;
      }
      var cards = document.querySelectorAll(
        '.ect-wizard-step [data-required-selection="' + name + '"] [data-value="' + value + '"]'
      );
      var card = null;
      for (var i = 0; i < cards.length; i++) {
        if (!cards[i].closest('[hidden]')) { card = cards[i]; break; }
      }
      if (!card && cards.length) card = cards[0];
      var labelEl = card && card.querySelector('.ect-layout-card__name, .ect-style-card__name');
      state[name] = {
        value: value,
        label: labelEl ? labelEl.textContent.trim() : (card ? card.textContent.trim() : value),
        isPro: card ? card.dataset.pro === 'true' : false
      };
    });

    // Native block: map shortcode slug minimal-list → ebec `minimal`; drop unsupported layouts.
    if (
      state.builder === 'block'
      && state.displayMode !== 'shortcode'
      && state.layout
    ) {
      var blockLayout = state.layout.value;
      if (blockLayout === 'minimal-list') blockLayout = 'minimal';
      if (blockLayout !== 'default' && blockLayout !== 'minimal') blockLayout = 'default';
      if (blockLayout !== state.layout.value) {
        state.layout = {
          value: blockLayout,
          label: blockLayout === 'minimal' ? 'Minimal' : 'Default (List)',
          isPro: false
        };
      }
    }

    // Hidden inputs (category CSV, date-format). Persisted state wins;
    // fall back to DOM value if the wizard hasn't been saved yet.
    state.category = persisted.category
      || (document.querySelector('[data-wizard-input="category"]') || {}).value
      || 'all';

    var dfValue = persisted['date-format']
      || (document.querySelector('.ect-date-format') || {}).value;
    if (dfValue) {
      state.dateFormatValue = dfValue;
      // Look up the display label from the select options
      var dfSelect = document.querySelector('.ect-date-format');
      if (dfSelect) {
        for (var j = 0; j < dfSelect.options.length; j++) {
          if (dfSelect.options[j].value === dfValue) {
            state.dateFormatLabel = dfSelect.options[j].text;
            break;
          }
        }
      }
      if (!state.dateFormatLabel) state.dateFormatLabel = dfValue;
    }

    return state;
  }

  // ----- Method (drives which left-hand panel shows) ------------------
  function detectMethod(state) {
    if (state.displayMode === 'shortcode') return 'shortcode';
    if (state.builder === 'shortcode' || state.builder === 'elementor' || state.builder === 'divi') return 'shortcode';
    return state.builder; // block, bricks, or wpbakery
  }

  // ----- Shortcode composition ----------------------------------------
  function composeShortcode(state) {
    var vals = state.category ? state.category.split(',').filter(Boolean) : ['all'];
    var category = vals.length ? vals.join(',') : 'all';
    var layout = state.layout ? state.layout.value : 'default';
    var style  = state.style  ? state.style.value  : 'style-1';
    var dateFormat = DATE_FORMAT_MAP[state.dateFormatValue] || 'default';
    var time = state.time ? TIME_MAP[state.time.value] || 'future' : 'future';

    if (document.body.getAttribute('data-tier') === 'pro') {
      var featuredOnly = (state.featured && state.featured.value === 'yes') ? 'true' : 'false';
      var filterbar = (state['filter-bar'] && state['filter-bar'].value === 'yes') ? 'yes' : 'no';
      return '[events-calendar-templates' +
        ' template="' + layout + '"' +
        ' style="' + style + '"' +
        ' category="' + category + '"' +
        ' date_format="' + dateFormat + '"' +
        ' start_date="" end_date=""' +
        ' limit="10" order="ASC" hide-venue="no"' +
        ' time="' + time + '"' +
        ' date-filter="all_default"' +
        ' featured-only="' + featuredOnly + '"' +
        ' columns="3"' +
        ' autoplay="true"' +
        ' tags="" venues="" organizers="" venuecountry="" venuecity=""' +
        ' socialshare="no"' +
        ' filterbar="' + filterbar + '"' +
        ' filterbarstyle="both"' +
      ']';
    }

    return '[events-calendar-templates' +
      ' category="' + category + '"' +
      ' template="' + layout + '"' +
      ' style="' + style + '"' +
      ' date_format="' + dateFormat + '"' +
      ' start_date=""' +
      ' end_date=""' +
      ' limit="10"' +
      ' order="ASC"' +
      ' hide_venue="no"' +
      ' socialshare="no"' +
      ' time="' + time + '"' +
    ']';
  }

  // Escape any HTML-special char so it renders as literal text.
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // Light syntax accents for the code display. Tokenises the shortcode
  // (tag opener → attr="val" pairs → tag closer) and wraps each token
  // separately — this avoids the regex ever running over span markup we
  // just inserted (which was corrupting the previous output).
  //
  // Brackets are emitted as &#91; / &#93; so if this markup ever ends up
  // inside a WordPress page body, do_shortcode() won't recognise the
  // pattern and try to execute it. textContent still decodes entities to
  // literal [ and ] for the Copy button.
  function highlightShortcode(sc) {
    var m = sc.match(/^\[([a-z0-9_-]+)([\s\S]*)\]$/i);
    if (!m) return escapeHtml(sc);
    var tagName = m[1];
    var body    = m[2];

    var parts = ['<span class="code-tag">&#91;' + escapeHtml(tagName) + '</span>'];
    var re = /([a-z_]+)="([^"]*)"/gi;
    var last = 0, attr;
    while ((attr = re.exec(body)) !== null) {
      if (attr.index > last) parts.push(escapeHtml(body.slice(last, attr.index)));
      parts.push('<span class="code-attr">' + escapeHtml(attr[1]) + '</span>');
      parts.push('=<span class="code-val">&quot;' + escapeHtml(attr[2]) + '&quot;</span>');
      last = attr.index + attr[0].length;
    }
    if (last < body.length) parts.push(escapeHtml(body.slice(last)));
    parts.push('<span class="code-tag">&#93;</span>');
    return parts.join('');
  }

  // ----- Refresh Step 4 (Done) ----------------------------------------
  // Two visual states driven by body[data-page-created]:
  //
  //   pre-create  : shortcode users who continued from Step 3. Title and
  //                 lede tell them the setup is ready and they can create
  //                 a draft page or just copy the shortcode and finish.
  //   post-create : block / bricks users (auto-marked pre-created on Step 3)
  //                 OR shortcode users who clicked "Create Draft Page" here.
  //                 Title celebrates completion; lede points at Preview.
  //
  // Also shows/hides the "Change Colors & Typography" button for shortcode
  // users only (block / bricks styling lives inside the block / builder).
  function applyDraftLinks(stepEl) {
    var draft = window.__ECT.getDraftPage();
    var preview = stepEl.querySelector('[data-page-preview]');
    var edit = stepEl.querySelector('[data-page-edit]');
    var settings = stepEl.querySelector('[data-page-settings]');
    if (preview) {
      preview.href = (draft && (draft.previewUrl || draft.viewUrl)) || '#';
      if (!draft) preview.removeAttribute('target');
      else preview.setAttribute('target', '_blank');
    }
    if (edit) {
      edit.href = (draft && draft.editUrl) || '#';
      if (!draft) edit.removeAttribute('target');
      else edit.setAttribute('target', '_blank');
    }
    if (settings && window.ECT_ONBOARDING && window.ECT_ONBOARDING.settingsUrl) {
      settings.href = window.ECT_ONBOARDING.settingsUrl;
    }
  }

  function buildCreatePayload(state) {
    var method = detectMethod(state);
    // Bricks uses native Events Widget settings via selections.editor;
    // WPBakery still wraps the composed shortcode in vc_row/vc_column.
    if (method === 'wpbakery') method = 'shortcode';
    var layoutLabel = (state.layout && state.layout.label) || 'Events';
    return {
      // block → ebec; shortcode on Block Editor → ect/shortcode (Events Shortcodes)
      method: method === 'block' ? 'block' : 'shortcode',
      title: 'Events — ' + layoutLabel,
      shortcode: composeShortcode(state),
      selections: {
        editor: state.builder,
        displayMode: state.displayMode,
        layout: state.layout || null,
        style: state.style || null,
        time: state.time || null,
        featured: state.featured || null,
        'filter-bar': state['filter-bar'] || null,
        category: state.category || 'all',
        dateFormatValue: state.dateFormatValue || 'default'
      }
    };
  }

  function createDraftPage(state) {
    return new Promise(function (resolve, reject) {
      if (!window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
        reject(new Error('Missing ECT_ONBOARDING'));
        return;
      }
      var payload = buildCreatePayload(state);
      var fd = new FormData();
      fd.append('action', 'ect_onboarding_create_page');
      fd.append('nonce', window.ECT_ONBOARDING.nonceCreatePage);
      fd.append('method', payload.method);
      fd.append('title', payload.title);
      fd.append('shortcode', payload.shortcode);
      fd.append('selections', JSON.stringify(payload.selections));

      fetch(window.ECT_ONBOARDING.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.success || !res.data) {
            reject(new Error((res && res.data && res.data.message) || 'Create failed'));
            return;
          }
          window.__ECT.setDraftPage(res.data);
          window.__ECT.setPageCreated(true);
          resolve(res.data);
        })
        .catch(reject);
    });
  }

  function refreshStep(stepEl) {
    if (!stepEl) return;
    var state = collectState();
    var method = detectMethod(state);
    var scPanel  = stepEl.querySelector('[data-review-panel="shortcode-recap"]');
    var bldrPanel= stepEl.querySelector('[data-review-panel="builder-recap"]');
    var isShortcode = method === 'shortcode';
    var pageCreated = document.body.getAttribute('data-page-created') === 'true';

    if (scPanel)   scPanel.hidden   = !isShortcode;
    if (bldrPanel) bldrPanel.hidden = isShortcode;

    // Composed shortcode + builder-aware hint
    var classicEditor = document.body.getAttribute('data-classic-editor') === 'true';
    var bInfo = BUILDER_INFO[state.builder];
    var plainSc = state.builder === 'shortcode' || !bInfo;
    var targetPage = plainSc
      ? (classicEditor ? 'Classic Editor' : 'Block Editor')
      : bInfo.label;
    var layoutName = (state.layout && state.layout.label) || 'events listing';

    if (isShortcode && scPanel) {
      var codeEl = scPanel.querySelector('[data-shortcode-code]');
      if (codeEl) codeEl.innerHTML = highlightShortcode(composeShortcode(state));
      var hintEl = scPanel.querySelector('.ect-review-panel__hint');
      if (hintEl) {
        var classicEditor = document.body.getAttribute('data-classic-editor') === 'true';
        var hintTarget = classicEditor
          ? 'this shortcode'
          : (state.builder === 'elementor'
            ? 'an Elementor Shortcode widget'
            : (state.builder === 'divi'
              ? 'a Divi Code module'
              : 'the Events Shortcodes block'));
        hintEl.textContent = pageCreated
          ? ('Draft ' + targetPage + ' page created with ' + hintTarget + '.')
          : 'Copy the shortcode and paste it anywhere — or create a draft page for you below.';
      }
    } else if (bldrPanel) {
      var info = BUILDER_INFO[state.builder] || BUILDER_INFO.block;
      var logo = bldrPanel.querySelector('[data-review-builder-logo]');
      var title= bldrPanel.querySelector('[data-review-builder-title]');
      var desc = bldrPanel.querySelector('[data-review-builder-desc]');
      if (logo) logo.src = info.logo;
      if (title) title.textContent = 'Events ' + info.thing + ' inserted in ' + info.label;
      if (desc) desc.textContent = 'Preview or open the draft to fine-tune anything.';
    }

    // Dynamic success-header copy
    var t5 = stepEl.querySelector('[data-step5-title]');
    var l5 = stepEl.querySelector('[data-step5-lede]');
    if (t5) t5.textContent = pageCreated
      ? ('Preview Your Events ' + layoutName + ' layout!')
      : ('Your events listing setup is ready.');
    if (l5) l5.textContent = pageCreated
      ? ('Draft ' + targetPage + ' page created — preview or edit it to fine-tune anything.')
      : ('Click "Create Draft Page" to add it to your site as a draft ' + targetPage + ' page — or copy the shortcode and use it anywhere.');

    // Change Colors & Typography — shortcode users only.
    stepEl.querySelectorAll('[data-success-shortcode-only]').forEach(function (el) {
      el.hidden = !isShortcode;
    });

    applyDraftLinks(stepEl);
  }

  // ----- Copy shortcode with feedback ---------------------------------
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-shortcode-copy]');
    if (!btn) return;
    e.preventDefault();
    var panel = btn.closest('.ect-review-panel');
    var code = panel && panel.querySelector('[data-shortcode-code]');
    if (!code) return;
    var text = code.textContent;
    var done = function () {
      var label = btn.querySelector('[data-shortcode-copy-label]');
      var icon  = btn.querySelector('[data-shortcode-copy-icon]');
      if (label) label.textContent = 'Copied!';
      if (icon)  icon.className = 'dashicons dashicons-yes';
      btn.classList.add('is-copied');
      setTimeout(function () {
        if (label) label.textContent = 'Copy';
        if (icon)  icon.className = 'dashicons dashicons-admin-page';
        btn.classList.remove('is-copied');
      }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done).catch(function () {
        fallbackCopy(text); done();
      });
    } else {
      fallbackCopy(text); done();
    }
  });

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.top = '-1000px';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (_) {}
    document.body.removeChild(ta);
  }

  // ----- Step-enter refresh + auto-create for Gutenberg/block ---------
  // Block users click "Create Draft Page" on Step 3 which only marks the
  // flag; the actual WP draft (with ebec/event-list block) is created here
  // when Step 4 opens, if it does not already exist.
  var creatingDraft = false;
  var draftPending = [];

  function setPageBusy(el, busy, label) {
    if (!el) return;
    if (busy) {
      if (!el._ectHtml) el._ectHtml = el.innerHTML;
      el.classList.add('is-busy');
      el.setAttribute('aria-busy', 'true');
      el.setAttribute('aria-disabled', 'true');
      el.setAttribute('data-ect-busy', '1');
      if (label) {
        el.innerHTML = '<span class="dashicons dashicons-update" aria-hidden="true"></span> <span>' + label + '</span>';
      }
    } else {
      el.classList.remove('is-busy');
      el.removeAttribute('aria-busy');
      el.removeAttribute('aria-disabled');
      el.removeAttribute('data-ect-busy');
      if (el._ectHtml) {
        el.innerHTML = el._ectHtml;
        el._ectHtml = null;
      }
    }
  }

  function setPageFailed(el, message) {
    if (!el) return;
    el.classList.remove('is-busy');
    el.removeAttribute('aria-busy');
    el.removeAttribute('aria-disabled');
    el.removeAttribute('data-ect-busy');
    el._ectHtml = null;
    el.innerHTML = '<span class="dashicons dashicons-warning" aria-hidden="true"></span> <span>' +
      (message || 'Failed — retry') + '</span>';
  }

  function runCreateDraft(state) {
    return createDraftPage(state || collectState());
  }

  function queueCreateDraft(state) {
    return new Promise(function (resolve, reject) {
      if (creatingDraft) {
        draftPending.push({ resolve: resolve, reject: reject });
        return;
      }
      creatingDraft = true;
      runCreateDraft(state)
        .then(function (data) {
          creatingDraft = false;
          resolve(data);
          var q = draftPending; draftPending = [];
          q.forEach(function (p) { p.resolve(data); });
        })
        .catch(function (err) {
          creatingDraft = false;
          reject(err);
          var q = draftPending; draftPending = [];
          q.forEach(function (p) { p.reject(err); });
        });
    });
  }

  function busyCreateTargets(on, label) {
    var preview = stepSuccess.querySelector('[data-page-preview]');
    var edit = stepSuccess.querySelector('[data-page-edit]');
    var createBtn = stepSuccess.querySelector('[data-page-create]');
    setPageBusy(preview, on, on ? label : null);
    setPageBusy(edit, on, on ? label : null);
    if (createBtn && createBtn.offsetParent !== null) {
      setPageBusy(createBtn, on, on ? (label || createVerb()) : null);
    }
  }

  function i18n(key, fallback) {
    var map = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.i18n) || {};
    return map[key] || fallback;
  }

  function createVerb() {
    var p = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.page) || {};
    return (p.exists && p.isDraft) ? i18n('updating', 'Updating page…') : i18n('creating', 'Creating page…');
  }

  function refreshCreateButtonLabel() {
    var btn = stepSuccess.querySelector('[data-page-create]');
    if (!btn || btn.getAttribute('data-ect-busy') === '1') return;
    var p = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.page) || {};
    var label = btn.querySelector('span:not(.dashicons)');
    if (!label) return;
    if (p.exists && p.isDraft) {
      label.textContent = i18n('updateDraft', 'Update Draft Page');
    } else if (p.exists && p.isPublished) {
      label.textContent = i18n('createNew', 'Create New Draft');
    } else {
      label.textContent = i18n('createDraft', 'Create Draft Page');
    }
  }

  function syncPageStateFromResult(data) {
    if (!window.ECT_ONBOARDING) return;
    window.ECT_ONBOARDING.page = {
      exists: true,
      isDraft: true,
      isPublished: false,
      id: (data && data.postId) || 0
    };
    refreshCreateButtonLabel();
  }

  document.addEventListener('ect:wizard-step', function (e) {
    if (e.detail.stepId !== 'success') return;
    requestAnimationFrame(function () {
      refreshStep(stepSuccess);
      applySpbPromo();
      refreshCreateButtonLabel();
      var state = collectState();
      var method = detectMethod(state);
      var draft = window.__ECT.getDraftPage();
      var wantsCreate = (method === 'block' || method === 'bricks' || method === 'wpbakery')
        && document.body.getAttribute('data-page-created') === 'true'
        && !draft
        && window.ECT_ONBOARDING
        && window.ECT_ONBOARDING.ajaxUrl;
      if (!wantsCreate) return;
      busyCreateTargets(true, createVerb());
      queueCreateDraft(state)
        .then(function (data) {
          syncPageStateFromResult(data);
          refreshStep(stepSuccess);
        })
        .catch(function () { window.__ECT.setPageCreated(false); refreshStep(stepSuccess); })
        .then(function () { busyCreateTargets(false); refreshCreateButtonLabel(); });
    });
  });

  // Preview / Edit: create on demand when href is still a placeholder.
  stepSuccess.addEventListener('click', function (e) {
    var link = e.target.closest('[data-page-preview], [data-page-edit]');
    if (!link) return;
    if (link.getAttribute('data-ect-busy') === '1' || link.classList.contains('is-busy')) {
      e.preventDefault();
      return;
    }
    var href = link.getAttribute('href') || '';
    if (href && href !== '#') return;
    e.preventDefault();
    if (!window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) return;
    var openEdit = link.hasAttribute('data-page-edit');
    busyCreateTargets(true, createVerb());
    queueCreateDraft(collectState())
      .then(function (data) {
        syncPageStateFromResult(data);
        refreshStep(stepSuccess);
        var url = data && (openEdit ? data.editUrl : data.viewUrl);
        if (url) window.open(url, '_blank', 'noopener');
      })
      .catch(function () {
        window.__ECT.setPageCreated(false);
        refreshStep(stepSuccess);
        setPageFailed(link, 'Failed — retry');
      })
      .then(function () {
        if (link.isConnected && link.getAttribute('data-ect-busy') === '1') {
          busyCreateTargets(false);
        }
        refreshCreateButtonLabel();
      });
  });

  // ----- "Create Draft Page" click on Step 4 --------------------------
  stepSuccess.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-page-create]');
    if (!btn) return;
    e.preventDefault();
    if (btn.getAttribute('data-ect-busy') === '1' || creatingDraft) return;
    if (!window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
      window.__ECT.setPageCreated(true);
      refreshStep(stepSuccess);
      return;
    }
    setPageBusy(btn, true, createVerb());
    queueCreateDraft(collectState())
      .then(function (data) {
        syncPageStateFromResult(data);
        refreshStep(stepSuccess);
      })
      .catch(function () { setPageFailed(btn, 'Failed — retry'); })
      .then(function () {
        if (btn.isConnected && btn.getAttribute('data-ect-busy') === '1') {
          setPageBusy(btn, false);
        }
        refreshCreateButtonLabel();
      });
  });

  // SPB cross-sell Install/Activate on finish step.
  stepSuccess.addEventListener('click', function (e) {
    var spbBtn = e.target.closest('[data-spb-install]');
    if (!spbBtn) return;
    e.preventDefault();
    if (spbBtn.getAttribute('data-ect-busy') === '1') return;
    var spb = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.spb) || {};
    if (!spb.slug || !window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
      if (spb.setupUrl) window.location.href = spb.setupUrl;
      return;
    }
    var wasInactive = spb.state === 'inactive';
    setPageBusy(spbBtn, true, wasInactive ? 'Activating…' : 'Installing…');
    var work;
    if (wasInactive) {
      work = fetch(window.ECT_ONBOARDING.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: (function () {
          var fd = new FormData();
          fd.append('action', 'ect_onboarding_plugin_activate');
          fd.append('security', window.ECT_ONBOARDING.nonceActivate);
          fd.append('init', spb.init || '');
          return fd;
        }())
      }).then(function (r) { return r.json(); });
    } else {
      work = fetch(window.ECT_ONBOARDING.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: (function () {
          var fd = new FormData();
          fd.append('action', 'ect_onboarding_plugin_install');
          fd.append('slug', spb.slug);
          fd.append('_ajax_nonce', window.ECT_ONBOARDING.nonceInstall);
          return fd;
        }())
      }).then(function (r) { return r.json(); }).then(function (res) {
        if (!res || !res.success) {
          throw new Error((res && res.data && (res.data.errorMessage || res.data.message)) || 'Install failed');
        }
        var init = (res.data && res.data.plugin) || spb.init;
        var fd = new FormData();
        fd.append('action', 'ect_onboarding_plugin_activate');
        fd.append('security', window.ECT_ONBOARDING.nonceActivate);
        fd.append('init', init);
        return fetch(window.ECT_ONBOARDING.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); });
      });
    }
    work.then(function (res) {
      if (!res || !res.success) {
        throw new Error((res && res.data && res.data.message) || 'Failed — retry');
      }
      var promo = spbBtn.closest('[data-spb-promo]');
      var desc = promo && promo.querySelector('.ect-cross-sell__desc');
      var setupHref = spb.setupUrl || 'edit.php?post_type=epta';
      spbBtn.outerHTML = '<a data-spb-setup data-wizard-finish href="' + setupHref.replace(/"/g, '&quot;') + '" class="ect-btn-primary">' +
        '<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span> <span>' + i18n('setup', 'Setup') + '</span></a>';
      // Keep Setup + Not now on one row — put status copy in the desc, not actions.
      if (desc) {
        desc.textContent = wasInactive
          ? i18n('spbActivated', 'Events Single Page Builder activated — set it up now.')
          : i18n('spbInstalled', 'Events Single Page Builder installed — set it up now.');
      }
      if (window.ECT_ONBOARDING.spb) window.ECT_ONBOARDING.spb.state = 'active';
    }).catch(function () {
      setPageFailed(spbBtn, 'Failed — retry');
    });
  });

  function applySpbPromo() {
    var spb = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.spb) || {};
    var promo = document.querySelector('[data-spb-promo]');
    if (!promo) return;
    if (spb.state === 'active') {
      promo.hidden = true;
      return;
    }
    promo.hidden = false;
    var label = promo.querySelector('[data-spb-label]');
    var icon = promo.querySelector('[data-spb-install] .dashicons');
    if (label) {
      label.textContent = (spb.state === 'inactive')
        ? i18n('activate', 'Activate')
        : i18n('installActivate', 'Install & Activate');
    }
    if (icon) {
      icon.className = 'dashicons ' + (spb.state === 'inactive' ? 'dashicons-yes' : 'dashicons-download');
    }
  }
  applySpbPromo();
  refreshCreateButtonLabel();

  // ----- Finish / Recreate: clear persisted state ---------------------
  // Both actions kill the wizard's state key, display-mode key, AND the
  // page-created flag so the next visit starts genuinely fresh. Finish
  // lets the anchor navigate to the hub; Recreate hard-reloads the wizard.
  function clearPersistedState() {
    var slug = window.ECT_WIZARD && window.ECT_WIZARD.slug;
    if (!slug) return;
    try {
      localStorage.removeItem('ect:wizard:' + slug + ':state');
      localStorage.removeItem('ect:wizard:' + slug + ':displayMode');
      localStorage.removeItem('ect:wizard:' + slug + ':pageCreated');
      localStorage.removeItem('ect:wizard:' + slug + ':draftPage');
    } catch (_) {}
    document.body.removeAttribute('data-page-created');
  }
  // Both Finish (bottom of Step 4) and Exit Setup (top header) share the
  // same behaviour: clear ALL persisted wizard keys and let the anchor
  // navigate to the ECA dashboard.
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-wizard-finish]')) return;
    document.body.classList.add('ect-finishing');
    clearPersistedState();
    try { localStorage.setItem('ect:wizard:' + window.ECT_WIZARD.slug + ':completed', '1'); } catch (_) {}
    if (window.ECT_ONBOARDING && window.ECT_ONBOARDING.ajaxUrl) {
      var fd = new FormData();
      fd.append('action', 'ect_onboarding_complete');
      fd.append('nonce', window.ECT_ONBOARDING.nonceComplete);
      var selected = document.querySelector('.ect-editor-option.is-selected');
      if (selected && selected.dataset.value) {
        fd.append('editor', selected.dataset.value);
      }
      fetch(window.ECT_ONBOARDING.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
    }
  });

  window.__ECT.setButtonBusy = setPageBusy;
  window.__ECT.setButtonFailed = setPageFailed;
  window.__ECT.queueCreateDraft = queueCreateDraft;
  window.__ECT.createDraftLabel = createVerb;
  window.__ECT.syncPageStateFromResult = syncPageStateFromResult;
})();

/* =========================================================================
   Telemetry / Cool Events opt-in — cross-plugin consent + preference save
   on Step 3 (query) Continue. Driven by ECT_ONBOARDING.telemetry from PHP.
   ========================================================================= */
(function () {
  function applyTelemetryFromPhp() {
    var t = window.ECT_ONBOARDING && window.ECT_ONBOARDING.telemetry;
    if (!t) return;

    var wrap = document.querySelector('.ect-telemetry');
    var box = document.querySelector('[data-wizard-telemetry]');

    if (!t.show) {
      if (wrap) wrap.hidden = true;
      if (window.ECT_WIZARD_RUNTIME && typeof window.ECT_WIZARD_RUNTIME.setTelemetryAccepted === 'function') {
        window.ECT_WIZARD_RUNTIME.setTelemetryAccepted(true);
      } else if (box) {
        box.checked = true;
      }
      return;
    }

    if (wrap) wrap.hidden = false;
    // Always default-checked when shown (including after a prior "no").
    if (window.ECT_WIZARD_RUNTIME && typeof window.ECT_WIZARD_RUNTIME.setTelemetryAccepted === 'function') {
      window.ECT_WIZARD_RUNTIME.setTelemetryAccepted(!!t.checked);
    } else if (box) {
      box.checked = !!t.checked;
    }
  }

  function buildSelectionsPayload() {
    var selections = {};
    try {
      if (window.ECT_WIZARD_RUNTIME && typeof window.ECT_WIZARD_RUNTIME.getState === 'function') {
        var st = window.ECT_WIZARD_RUNTIME.getState();
        if (st && st.selections) {
          selections = Object.assign({}, st.selections);
        }
      }
    } catch (_) {}

    var dm = document.body.getAttribute('data-display-mode');
    if (dm) selections.displayMode = dm;
    return selections;
  }

  function isTelemetryAccepted() {
    var t = window.ECT_ONBOARDING && window.ECT_ONBOARDING.telemetry;
    if (t && !t.show) return true;

    var wrap = document.querySelector('.ect-telemetry');
    if (wrap && wrap.hidden) return true;

    var box = document.querySelector('[data-wizard-telemetry]');
    if (box) return !!box.checked;

    try {
      if (window.ECT_WIZARD_RUNTIME && typeof window.ECT_WIZARD_RUNTIME.getState === 'function') {
        var st = window.ECT_WIZARD_RUNTIME.getState();
        if (st) return !!st.telemetryAccepted;
      }
    } catch (_) {}

    return true;
  }

  function saveOnboardingPreferences() {
    if (!window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl || !window.ECT_ONBOARDING.nonceSavePrefs) {
      return Promise.resolve();
    }

    var fd = new FormData();
    fd.append('action', 'ect_onboarding_save_preferences');
    fd.append('nonce', window.ECT_ONBOARDING.nonceSavePrefs);
    fd.append('telemetry', isTelemetryAccepted() ? '1' : '0');
    fd.append('selections', JSON.stringify(buildSelectionsPayload()));

    return fetch(window.ECT_ONBOARDING.ajaxUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success && res.data && res.data.telemetry) {
          window.ECT_ONBOARDING.telemetry = res.data.telemetry;
          applyTelemetryFromPhp();
        }
      })
      .catch(function () { /* non-blocking — still advance wizard */ });
  }

  if (window.ECT_WIZARD_RUNTIME) {
    window.ECT_WIZARD_RUNTIME.beforeNext = function (stepId, proceed) {
      if (stepId !== 'query') return;

      function draftLabel() {
        if (typeof window.__ECT.createDraftLabel === 'function') {
          return window.__ECT.createDraftLabel();
        }
        var map = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.i18n) || {};
        return map.creating || 'Creating page…';
      }

      var step3 = document.querySelector('.ect-wizard-step[data-step="query"]');
      var cta = step3 && step3.querySelector('[data-step3-primary-cta]');
      var willCreate = window.__ECT.step3WillCreateDraft();
      var needsDraftAjax = willCreate
        && window.ECT_ONBOARDING
        && window.ECT_ONBOARDING.ajaxUrl
        && !window.__ECT.getDraftPage()
        && typeof window.__ECT.queueCreateDraft === 'function';

      if (needsDraftAjax && cta) {
        window.__ECT.setButtonBusy(cta, true, draftLabel());
      }

      function afterPreferences() {
        if (!willCreate || !window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
          proceed();
          return;
        }
        if (window.__ECT.getDraftPage()) {
          window.__ECT.setPageCreated(true);
          proceed();
          return;
        }
        if (typeof window.__ECT.queueCreateDraft !== 'function') {
          proceed();
          return;
        }

        if (cta && cta.getAttribute('data-ect-busy') !== '1') {
          window.__ECT.setButtonBusy(cta, true, draftLabel());
        }
        window.__ECT.queueCreateDraft()
          .then(function (data) {
            if (typeof window.__ECT.syncPageStateFromResult === 'function') {
              window.__ECT.syncPageStateFromResult(data);
            }
            window.__ECT.setPageCreated(true);
            proceed();
          })
          .catch(function () {
            window.__ECT.setPageCreated(false);
            window.__ECT.setButtonFailed(cta, 'Failed — retry');
          })
          .then(function () {
            if (cta && cta.isConnected && cta.getAttribute('data-ect-busy') === '1') {
              window.__ECT.setButtonBusy(cta, false);
            }
          });
      }

      saveOnboardingPreferences().then(afterPreferences).catch(function () { afterPreferences(); });
      return false;
    };
  }

  function bootTelemetry() {
    applyTelemetryFromPhp();
  }
  if (document.readyState !== 'loading') bootTelemetry();
  else document.addEventListener('DOMContentLoaded', bootTelemetry);

  // Re-apply when returning to the query step (localStorage restore may race).
  document.addEventListener('ect:wizard-step', function (e) {
    if (e.detail && e.detail.stepId === 'query') {
      applyTelemetryFromPhp();
    }
  });
})();

/* =========================================================================
   Step 1 specific behavior: video swap + promo + nav-mode swap +
   auto-advance on dismiss.
   (This block ran AFTER the ect-wizard.js include in the original page —
   relative order is preserved because this whole file loads after it.)
   ========================================================================= */
(function () {
  var SIBLING_ICON_BASE = (window.ECT_WIZARD && window.ECT_WIZARD.siblingIconBase) || 'events-addons-icons/';
  // Fallback addon metadata (prototype / when PHP siblings are absent).
  var ADDON_MAP = {
    elementor: {
      addon: 'Events Widgets for Elementor',
      addonShort: 'Events Widgets',
      icon: SIBLING_ICON_BASE + 'events-widgets-icon.svg',
      wizardHref: ECT_ONBOARDING_CONFIG.URL_WIZARD_WIDGETS,
      desc: 'Get drag-drop event widgets directly inside Elementor with our dedicated addon.'
    },
    divi: {
      addon: 'Events Modules for Divi',
      addonShort: 'Events Modules',
      icon: SIBLING_ICON_BASE + 'events-calendar-modules-for-divi.svg',
      wizardHref: ECT_ONBOARDING_CONFIG.URL_WIZARD_DIVI,
      desc: 'Get native Divi modules for event listings and single event pages with our dedicated addon.'
    }
  };

  function getSibling(builder) {
    var siblings = window.ECT_ONBOARDING && window.ECT_ONBOARDING.siblings;
    if (siblings && siblings[builder]) {
      return siblings[builder];
    }
    return ADDON_MAP[builder] || null;
  }

  /** @returns {'absent'|'inactive'|'active'|null} */
  function getSiblingStatus(builder) {
    var sib = getSibling(builder);
    if (!sib) return null;
    if (sib.status === 'active' || sib.status === 'inactive' || sib.status === 'absent') {
      return sib.status;
    }
    // Prototype fallback: preview-state treated "installed" as active.
    var s = document.body.getAttribute('data-preview-state') || '';
    var elementorInstalled = ['elementor-installed', 'elementor-divi-installed', 'all-installed'];
    var diviInstalled      = ['divi-installed', 'elementor-divi-installed', 'all-installed'];
    if (builder === 'elementor' && elementorInstalled.indexOf(s) !== -1) return 'active';
    if (builder === 'divi' && diviInstalled.indexOf(s) !== -1) return 'active';
    return 'absent';
  }

  function updatePromo(editorValue) {
    var promo = document.querySelector('[data-editor-promo]');
    if (!promo) return;

    // Bricks + Block + Shortcode → no promo
    var info = getSibling(editorValue);
    if (!info || !ADDON_MAP[editorValue]) {
      promo.hidden = true;
      promo.innerHTML = '';
      promo.className = 'ect-editor-promo';
      setNavMode('default', null);
      return;
    }

    // Prefer PHP-localized display fields; fill gaps from ADDON_MAP.
    var base = ADDON_MAP[editorValue];
    info = {
      addon: info.addon || base.addon,
      addonShort: info.addonShort || base.addonShort,
      icon: info.icon || base.icon,
      desc: info.desc || base.desc,
      wizardHref: info.openUrl || base.wizardHref,
      slug: info.slug || '',
      init: info.init || '',
      status: getSiblingStatus(editorValue)
    };

    var status = info.status;
    var mod;
    var navMode;
    var kicker;
    var title;
    var desc;

    if (status === 'active') {
      mod = 'ect-editor-promo--ready';
      navMode = 'promo-ready';
      kicker = '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Already installed';
      title = info.addon + ' is active on your site';
      desc = 'Jump into its dedicated setup for a native flow, or continue here with shortcode.';
    } else if (status === 'inactive') {
      mod = 'ect-editor-promo--activate';
      navMode = 'promo-activate';
      kicker = '<span class="dashicons dashicons-warning" aria-hidden="true"></span> Installed but not active';
      title = 'Activate ' + info.addon;
      desc = 'This addon is on your site but not active yet. Activate it for a native ' +
        (editorValue === 'divi' ? 'Divi' : 'Elementor') + ' flow, or continue here with shortcode.';
    } else {
      mod = 'ect-editor-promo--suggest';
      navMode = 'promo-suggest';
      kicker = '<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span> Better integration available';
      title = 'Install ' + info.addon + ' (free)';
      desc = info.desc;
    }

    promo.className = 'ect-editor-promo ' + mod;
    promo.hidden = false;
    promo.innerHTML =
      '<span class="ect-editor-promo__icon">' +
        '<img src="' + info.icon + '" alt="">' +
      '</span>' +
      '<div class="ect-editor-promo__body">' +
        '<span class="ect-editor-promo__kicker">' + kicker + '</span>' +
        '<h3 class="ect-editor-promo__title">' + title + '</h3>' +
        '<p class="ect-editor-promo__desc">' + desc + '</p>' +
      '</div>';

    setNavMode(navMode, info);
  }

  var BUILDER_LABEL = window.__ECT.BUILDER_LABEL;

  function setNavMode(mode, info) {
    var navRight = document.querySelector('.ect-wizard-step.is-active [data-nav-right]');
    if (!navRight) return;
    navRight.setAttribute('data-nav-mode', mode);

    if (info) {
      navRight.querySelectorAll('[data-addon-name]').forEach(function (el) {
        el.textContent = info.addonShort;
      });
      var openBtn = navRight.querySelector('[data-promo-open]');
      var installBtn = navRight.querySelector('[data-promo-install]');
      var activateBtn = navRight.querySelector('[data-promo-activate]');
      var openHref = info.wizardHref || '#';
      if (openBtn) openBtn.setAttribute('href', openHref);
      // Install/Activate are AJAX in WordPress; keep # so they don't navigate away.
      if (installBtn) {
        installBtn.setAttribute('href', '#');
        installBtn.setAttribute('data-slug', info.slug || '');
        installBtn.setAttribute('data-init', info.init || '');
      }
      if (activateBtn) {
        activateBtn.setAttribute('href', '#');
        activateBtn.setAttribute('data-slug', info.slug || '');
        activateBtn.setAttribute('data-init', info.init || '');
      }

      var dismissLabel = navRight.querySelector('[data-editor-promo-dismiss-label]');
      var currentEditor = document.querySelector('.ect-editor-option.is-selected');
      var editorVal = currentEditor && currentEditor.dataset.value;
      if (dismissLabel && editorVal) {
        dismissLabel.textContent = "No, I'll use shortcode in " + (BUILDER_LABEL[editorVal] || editorVal);
      }
    }
  }

  function setBusy(btn, busy, label) {
    if (!btn) return;
    if (busy) {
      if (!btn._ectHtml) btn._ectHtml = btn.innerHTML;
      btn.classList.add('is-busy');
      btn.setAttribute('aria-busy', 'true');
      btn.setAttribute('aria-disabled', 'true');
      btn.setAttribute('data-ect-busy', '1');
      if (label) {
        btn.innerHTML = '<span class="dashicons dashicons-update" aria-hidden="true"></span> <span>' + label + '</span>';
      }
    } else {
      btn.classList.remove('is-busy');
      btn.removeAttribute('aria-busy');
      btn.removeAttribute('aria-disabled');
      btn.removeAttribute('data-ect-busy');
      if (btn._ectHtml) {
        btn.innerHTML = btn._ectHtml;
        btn._ectHtml = null;
      }
    }
  }

  function setFailed(btn, message) {
    if (!btn) return;
    btn.classList.remove('is-busy');
    btn.removeAttribute('aria-busy');
    btn.removeAttribute('aria-disabled');
    btn.removeAttribute('data-ect-busy');
    btn._ectHtml = null;
    btn.innerHTML = '<span class="dashicons dashicons-warning" aria-hidden="true"></span> <span>' +
      (message || 'Failed — retry') + '</span>';
  }

  function refreshSiblingsFromResponse(data) {
    if (data && data.siblings && window.ECT_ONBOARDING) {
      window.ECT_ONBOARDING.siblings = data.siblings;
    }
  }

  function activateSibling(init, btn) {
    if (!window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
      return Promise.reject(new Error('Missing ECT_ONBOARDING'));
    }
    var fd = new FormData();
    fd.append('action', 'ect_onboarding_plugin_activate');
    fd.append('security', window.ECT_ONBOARDING.nonceActivate);
    fd.append('init', init);
    return fetch(window.ECT_ONBOARDING.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          var msg = (res && res.data && res.data.message) || 'Activation failed. Please activate manually from Plugins.';
          throw new Error(msg);
        }
        refreshSiblingsFromResponse(res.data);
        return res;
      });
  }

  function installThenActivate(slug, init, btn) {
    if (!window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
      return Promise.reject(new Error('Missing ECT_ONBOARDING'));
    }
    var fd = new FormData();
    fd.append('action', 'ect_onboarding_plugin_install');
    fd.append('slug', slug);
    fd.append('_ajax_nonce', window.ECT_ONBOARDING.nonceInstall);
    return fetch(window.ECT_ONBOARDING.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          var msg = (res && res.data && res.data.errorMessage) ||
            (res && res.data && res.data.message) ||
            'Installation failed. Please install manually from Plugins.';
          throw new Error(typeof msg === 'string' ? msg : 'Installation failed.');
        }
        // Core installer returns the main plugin file on success.
        var pluginFile = (res.data && res.data.plugin) || init;
        setBusy(btn, true, 'Activating…');
        return activateSibling(pluginFile, btn);
      });
  }

  function afterSiblingReady() {
    var selected = document.querySelector('.ect-editor-option.is-selected');
    if (selected) updatePromo(selected.dataset.value);
    var ed = selected && selected.dataset.value;
    var confirmMap = (window.ECT_ONBOARDING && window.ECT_ONBOARDING.i18n && window.ECT_ONBOARDING.i18n.installConfirm) || {};
    var msg = ed && confirmMap[ed];
    if (!msg) return;
    var nav = document.querySelector('.ect-wizard-step[data-step="editor"] [data-nav-right]');
    if (!nav) return;
    var el = nav.querySelector('[data-ect-install-confirm]');
    if (!el) {
      el = document.createElement('span');
      el.setAttribute('data-ect-install-confirm', '');
      nav.insertBefore(el, nav.firstChild);
    }
    el.innerHTML = '<em>' + msg + '</em>';
    el.hidden = false;
  }

  function updateVideoPreview(editorValue, videoLabel) {
    var video = document.querySelector('[data-editor-video]');
    var label = document.querySelector('[data-editor-video-label]');
    var watermark = document.querySelector('[data-editor-video-watermark]');
    var frame = document.querySelector('[data-video-frame]');
    var selected = document.querySelector('.ect-editor-option.is-selected');

    // Reset any playing iframe when editor changes
    if (video) {
      video.classList.remove('is-playing');
      video.setAttribute('data-editor', editorValue || '');
    }
    if (frame) frame.innerHTML = '';

    if (label) label.textContent = videoLabel || 'Pick an editor to see a walkthrough';

    // Set the YouTube thumbnail as background. Preload maxresdefault (HD);
    // if it fails (some videos don't publish maxres), fall back to hqdefault
    // which is always available. Clear the image entirely if nothing selected.
    if (video) {
      var ytId = selected && selected.dataset.youtubeId;
      if (ytId) {
        var maxres = ECT_ONBOARDING_CONFIG.YT_THUMB_BASE + ytId + '/maxresdefault.jpg';
        var hq     = ECT_ONBOARDING_CONFIG.YT_THUMB_BASE + ytId + '/hqdefault.jpg';
        // Optimistically try maxres, swap to hq on error
        var probe = new Image();
        probe.onload = function () {
          // maxresdefault is 120×90 grey placeholder when unavailable — detect that
          if (this.naturalWidth < 200) {
            video.style.backgroundImage = 'url("' + hq + '")';
          } else {
            video.style.backgroundImage = 'url("' + maxres + '")';
          }
        };
        probe.onerror = function () {
          video.style.backgroundImage = 'url("' + hq + '")';
        };
        probe.src = maxres;
        // Set hq immediately as a first paint (in case probe is slow)
        video.style.backgroundImage = 'url("' + hq + '")';
      } else {
        video.style.backgroundImage = '';
      }
    }

    // Editor icon as watermark on top of the thumbnail
    var watermarkMap = {
      block:     __ECT.getEditorIcon('block'),
      shortcode: __ECT.getEditorIcon('shortcode'),
      elementor: __ECT.getEditorIcon('elementor'),
      divi:      __ECT.getEditorIcon('divi'),
      bricks:    __ECT.getEditorIcon('bricks'),
      wpbakery:  __ECT.getEditorIcon('wpbakery')
    };
    if (watermark) {
      if (watermarkMap[editorValue]) {
        watermark.src = watermarkMap[editorValue];
        watermark.hidden = false;
      } else {
        watermark.hidden = true;
      }
    }
  }

  function playVideo() {
    var video = document.querySelector('[data-editor-video]');
    var frame = document.querySelector('[data-video-frame]');
    var selected = document.querySelector('.ect-editor-option.is-selected');
    if (!video || !frame) return;
    var ytId = selected && selected.dataset.youtubeId;
    if (!ytId) return; // no editor picked yet
    frame.innerHTML = '<iframe src="' + ECT_ONBOARDING_CONFIG.YT_EMBED_BASE + ytId
      + '?autoplay=1&rel=0&modestbranding=1" title="Walkthrough video" '
      + 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; '
      + 'encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    video.classList.add('is-playing');
  }

  // Classic Editor guard — if the preview state indicates Classic Editor
  // is active, the Block editor card is dulled and clicks are swallowed.
  // Registered in capture phase so wizard.js's selection handler never sees
  // the click.
  document.addEventListener('click', function (e) {
    var block = e.target.closest('.ect-editor-option[data-value="block"]');
    if (!block) return;
    if (document.body.getAttribute('data-classic-editor') === 'true') {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true);

  document.addEventListener('click', function (e) {
    // Editor option selected → clear the "use shortcode inside builder"
    // preference (fresh start), swap video + update promo.
    var opt = e.target.closest('.ect-editor-option');
    if (opt && !opt.classList.contains('is-locked')) {
      window.__ECT.setDisplayMode(null);
      // Picking a different editor is a substantive change — reset the
      // pageCreated flag so Step 4 starts in the pre-create state again.
      window.__ECT.setPageCreated(false);
      var value = opt.dataset.value;
      var videoLabel = opt.dataset.videoLabel || '';
      requestAnimationFrame(function () {
        updateVideoPreview(value, videoLabel);
        updatePromo(value);
      });
      return;
    }

    // Play the walkthrough when the user clicks ANYWHERE on the video
    // thumbnail (not just the play button). Skip if we're already playing —
    // clicks inside the iframe are handled by YouTube itself.
    var video = e.target.closest('[data-editor-video]');
    if (video && !video.classList.contains('is-playing') && video.getAttribute('data-editor')) {
      e.preventDefault();
      playVideo();
      return;
    }

    // Install sibling via WP.org AJAX, then activate.
    var installBtn = e.target.closest('[data-promo-install]');
    if (installBtn) {
      e.preventDefault();
      if (installBtn.classList.contains('is-busy')) return;
      var slug = installBtn.getAttribute('data-slug') || '';
      var init = installBtn.getAttribute('data-init') || '';
      if (!slug || !window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
        // Prototype / missing bootstrap — fall through to href if set.
        var href = installBtn.getAttribute('href');
        if (href && href !== '#') window.location.href = href;
        return;
      }
      setBusy(installBtn, true, 'Installing…');
      installThenActivate(slug, init, installBtn)
        .then(function () { afterSiblingReady(); })
        .catch(function (err) {
          setFailed(installBtn, (err && err.message) || 'Failed — retry');
        })
        .then(function () {
          if (installBtn.isConnected && installBtn.getAttribute('data-ect-busy') === '1') {
            setBusy(installBtn, false);
          }
        });
      return;
    }

    // Activate an already-installed sibling.
    var activateBtn = e.target.closest('[data-promo-activate]');
    if (activateBtn) {
      e.preventDefault();
      if (activateBtn.classList.contains('is-busy') || activateBtn.getAttribute('data-ect-busy') === '1') return;
      var actInit = activateBtn.getAttribute('data-init') || '';
      if (!actInit || !window.ECT_ONBOARDING || !window.ECT_ONBOARDING.ajaxUrl) {
        return;
      }
      setBusy(activateBtn, true, 'Activating…');
      activateSibling(actInit, activateBtn)
        .then(function () { afterSiblingReady(); })
        .catch(function (err) {
          setFailed(activateBtn, (err && err.message) || 'Failed — retry');
        })
        .then(function () {
          if (activateBtn.isConnected && activateBtn.getAttribute('data-ect-busy') === '1') {
            setBusy(activateBtn, false);
          }
        });
      return;
    }

    // ESB Pro installed-but-inactive → Activate Pro on upgrade CTAs.
    var proUpgrade = e.target.closest('[data-step2-nav-variant="upgrade"], [data-step3-nav-variant="upgrade"]');
    if (proUpgrade && window.ECT_ONBOARDING && window.ECT_ONBOARDING.pro && window.ECT_ONBOARDING.pro.installedInactive) {
      e.preventDefault();
      if (proUpgrade.getAttribute('data-ect-busy') === '1') return;
      var proInit = window.ECT_ONBOARDING.pro.init || '';
      if (!proInit) return;
      setBusy(proUpgrade, true, 'Activating…');
      activateSibling(proInit, proUpgrade)
        .then(function () {
          window.ECT_ONBOARDING.pro.installedInactive = false;
          proUpgrade.classList.remove('is-busy');
          proUpgrade.removeAttribute('aria-busy');
          proUpgrade.removeAttribute('aria-disabled');
          proUpgrade.removeAttribute('data-ect-busy');
          proUpgrade._ectHtml = null;
          proUpgrade.setAttribute(
            'href',
            window.ECT_ONBOARDING.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=ect-onboarding')
          );
          proUpgrade.removeAttribute('target');
          proUpgrade.removeAttribute('rel');
          proUpgrade.innerHTML = '<span>Continue</span> <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>';
        })
        .catch(function (err) {
          setFailed(proUpgrade, (err && err.message) || 'Failed — retry');
        });
      return;
    }

    // "No, I'll use shortcode in <builder>" dismiss → record a display-mode
    // preference (body attribute) and auto-advance to Step 2. Step 1's
    // editor selection is intentionally NOT changed — the final step needs
    // to know the user still wants an Elementor / Divi page, they just want
    // shortcode inside it.
    var dismiss = e.target.closest('[data-editor-promo-dismiss]');
    if (dismiss) {
      e.preventDefault();
      window.__ECT.setDisplayMode('shortcode');
      requestAnimationFrame(function () {
        var nextBtn = document.querySelector('.ect-wizard-step.is-active [data-wizard-next]');
        if (nextBtn) nextBtn.click();
      });
    }
  });

  // Preferred pre-selection: detected builder first (in that visual order),
  // then Shortcode (has richer design options than Block right now),
  // then Block as fallback. Called on load and whenever the preview state
  // changes so a hidden selection auto-corrects to a visible default.
  var PREFERRED_ORDER = ['elementor', 'divi', 'bricks', 'wpbakery', 'shortcode', 'block'];

  function isVisible(el) {
    if (!el || el.offsetParent === null) return false;
    if (document.body.getAttribute('data-classic-editor') === 'true' && el.dataset.value === 'block') {
      return false;
    }
    return true;
  }

  // Step-1 editor options stay a single-column list (not a 2-col grid).
  function applyOptionColumns() {
    var container = document.querySelector('.ect-editor-selector__options');
    if (!container) return;
    container.setAttribute('data-cols', '1');
  }

  function autoSelectPreferred() {
    var current = document.querySelector('.ect-editor-option.is-selected');
    // If a visible option is already selected, keep it
    if (isVisible(current)) return;

    // Persisted state ALWAYS wins over a preferred-order fallback. Without
    // this guard, a reload with the preview state set to "no-builder" (or
    // any state that hides the user's chosen editor) would find the persisted
    // option display:none, mark it not-visible, and silently click Shortcode
    // instead — overwriting state.selections.editor and destroying the
    // Bricks / Block / Elementor pick.
    try {
      var slug = window.ECT_WIZARD.slug;
      var raw = localStorage.getItem('ect:wizard:' + slug + ':state');
      if (raw) {
        var s = JSON.parse(raw);
        if (s && s.selections && s.selections.editor) return;
      }
    } catch (_) {}

    // No persisted method — this is a fresh visit. Clear stale is-selected
    // (if any) and click the first visible option in preferred order.
    if (current) current.classList.remove('is-selected');
    for (var i = 0; i < PREFERRED_ORDER.length; i++) {
      var opt = document.querySelector('.ect-editor-option[data-value="' + PREFERRED_ORDER[i] + '"]');
      if (isVisible(opt)) { opt.click(); return; }
    }
  }

  // Preview state change → re-evaluate promo, or pick a new default if the
  // current selection got hidden by the new state.
  document.addEventListener('ect:preview-state', function () {
    applyOptionColumns();   // visible-option count may have changed
    var selected = document.querySelector('.ect-editor-option.is-selected');
    if (isVisible(selected)) {
      updatePromo(selected.dataset.value);
    } else {
      autoSelectPreferred();
    }
  });

  // Re-run when user navigates back to Step 1 from a later step
  document.addEventListener('ect:wizard-step', function (e) {
    if (e.detail.stepId !== 'editor') return;
    var selected = document.querySelector('.ect-editor-option.is-selected');
    if (isVisible(selected)) {
      updateVideoPreview(selected.dataset.value, selected.dataset.videoLabel || '');
      updatePromo(selected.dataset.value);
    } else {
      autoSelectPreferred();
    }
  });

  // ESB Pro installed but inactive → relabel Upgrade CTAs to Activate Pro.
  function applyEsbProUpgradeButtons() {
    var pro = window.ECT_ONBOARDING && window.ECT_ONBOARDING.pro;
    var inactive = !!(pro && pro.installedInactive && pro.init);
    document.querySelectorAll('[data-step2-nav-variant="upgrade"], [data-step3-nav-variant="upgrade"]').forEach(function (el) {
      var label = el.querySelector('span:not(.dashicons)');
      if (inactive) {
        el.setAttribute('href', '#');
        el.removeAttribute('target');
        el.removeAttribute('rel');
        if (label) label.textContent = 'Activate Pro';
      } else if (pro && pro.proUrl) {
        el.setAttribute('href', pro.proUrl);
        el.setAttribute('target', '_blank');
        el.setAttribute('rel', 'noopener');
        if (label) label.textContent = 'Upgrade to Pro';
      }
    });
  }

  // Boot — restore state on load OR pre-select preferred if no valid selection
  function bootstrap() {
    applyOptionColumns();
    applyEsbProUpgradeButtons();
    var selected = document.querySelector('.ect-editor-option.is-selected');
    if (isVisible(selected)) {
      updateVideoPreview(selected.dataset.value, selected.dataset.videoLabel || '');
      updatePromo(selected.dataset.value);
    } else {
      autoSelectPreferred();
    }
  }
  if (document.readyState !== 'loading') bootstrap();
  else document.addEventListener('DOMContentLoaded', bootstrap);
})();
