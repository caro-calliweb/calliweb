<?php
/**
 * ECT onboarding wizard markup (admin view).
 *
 * Expected variables (set by ECT_Onboarding_Page::get_wizard_markup()):
 * @var string               $dashboard_url
 * @var string               $icons
 * @var array<string,string> $editor_icons
 * @var string               $category_options
 * @var string               $preview_state
 * @var string               $default_editor
 * @var array<string,bool>   $detected_editors
 * @var bool                 $show_telemetry
 * @var bool                 $classic_editor
 * @var string               $wizard_tier
 *
 * @package Template_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ect-onboarding-admin" data-tier="<?php echo esc_attr( $wizard_tier ); ?>" data-preview-state="<?php echo esc_attr( $preview_state ); ?>" data-classic-editor="<?php echo ! empty( $classic_editor ) ? 'true' : 'false'; ?>" data-editor-selected="<?php echo esc_attr( $default_editor ); ?>">
<div class="ect-wizard-shell">

  <!-- ============================================================
       Header — brand (left) + progress steps (center) + Exit (right)
       Replaces the admin bar + old sticky progress strip.
       ============================================================ -->
  <header class="ect-wizard-header">
    <div class="ect-wizard-header__brand">
      <img src="<?php echo esc_url( $icons ); ?>events-shortcodes-icon.svg" alt="" class="ect-wizard-header__brand-icon">
      <span class="ect-wizard-header__brand-name">
        <strong>Events Shortcodes</strong>
        <em>&amp; Blocks</em>
      </span>
    </div>

    <ol class="ect-wizard-header__steps" data-wizard-progress></ol>

    <a href="<?php echo esc_url( $dashboard_url ); ?>" data-wizard-finish class="ect-wizard-header__exit">
      <span>Exit Setup</span>
      <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
    </a>
  </header>

  <!-- ============================================================
       Main content — card holds each step with inline nav.
       ============================================================ -->
  <main class="ect-wizard-main">
    <div class="ect-wizard-card">

      <!-- ============================================================
           STEP 1 — Select your editor
           ============================================================ -->
      <section class="ect-wizard-step is-active" data-step="editor">
        <header class="ect-wizard-card__heading">
          <h1 class="ect-wizard-card__title">Select your editor or way to create your events listing</h1>
          <p class="ect-wizard-card__desc">Pick your editor or method to start. Layout, style and event query options will come next.</p>
        </header>

        <div class="ect-editor-selector">
          <!-- Left: editor options grid (anchors — not overridable button styles) -->
          <div class="ect-editor-selector__options" data-required-selection="editor">

            <a href="#" role="button" class="ect-editor-option" data-value="block" data-video-label="Events Block" data-youtube-id="uL3ToWGncbM">
              <img src="<?php echo esc_url( $editor_icons['block'] ); ?>" alt="" class="ect-editor-option__icon">
              <span class="ect-editor-option__text">
                <span class="ect-editor-option__name">Block Editor</span>
                <span class="ect-editor-option__sub">Use the native Gutenberg block to create events listing.</span>
              </span>
              <span class="ect-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>

            <a href="#" role="button" class="ect-editor-option" data-value="shortcode" data-video-label="Events Shortcode" data-youtube-id="uL3ToWGncbM">
              <img src="<?php echo esc_url( $editor_icons['shortcode'] ); ?>" alt="" class="ect-editor-option__icon">
              <span class="ect-editor-option__text">
                <span class="ect-editor-option__name">Shortcode</span>
                <span class="ect-editor-option__sub">Use a shortcode to show your events listing anywhere.</span>
              </span>
              <span class="ect-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>

            <?php if ( ! empty( $detected_editors['elementor'] ) ) : ?>
            <a href="#" role="button" class="ect-editor-option" data-value="elementor" data-video-label="The Events Calendar + Elementor" data-youtube-id="2m74nSrEo0g">
              <img src="<?php echo esc_url( $editor_icons['elementor'] ); ?>" alt="" class="ect-editor-option__icon">
              <span class="ect-editor-option__text">
                <span class="ect-editor-option__name">Elementor</span>
                <span class="ect-editor-option__sub">Use events widgets inside Elementor builder.</span>
              </span>
              <span class="ect-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>
            <?php endif; ?>

            <?php if ( ! empty( $detected_editors['divi'] ) ) : ?>
            <a href="#" role="button" class="ect-editor-option" data-value="divi" data-video-label="The Events Calendar + Divi" data-youtube-id="Z9s-7RgxZu8">
              <img src="<?php echo esc_url( $editor_icons['divi'] ); ?>" alt="" class="ect-editor-option__icon">
              <span class="ect-editor-option__text">
                <span class="ect-editor-option__name">Divi</span>
                <span class="ect-editor-option__sub">Use events calendar modules inside Divi builder.</span>
              </span>
              <span class="ect-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>
            <?php endif; ?>

            <?php if ( ! empty( $detected_editors['bricks'] ) ) : ?>
            <a href="#" role="button" class="ect-editor-option" data-value="bricks" data-video-label="The Events Calendar + Bricks Builder" data-youtube-id="uL3ToWGncbM">
              <img src="<?php echo esc_url( $editor_icons['bricks'] ); ?>" alt="" class="ect-editor-option__icon">
              <span class="ect-editor-option__text">
                <span class="ect-editor-option__name">Bricks Builder</span>
                <span class="ect-editor-option__sub">Create your events listing with the native Bricks element.</span>
              </span>
              <span class="ect-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>
            <?php endif; ?>

            <?php if ( ! empty( $detected_editors['wpbakery'] ) ) : ?>
            <a href="#" role="button" class="ect-editor-option" data-value="wpbakery" data-video-label="The Events Calendar + WPBakery" data-youtube-id="uL3ToWGncbM">
              <img src="<?php echo esc_url( $editor_icons['wpbakery'] ); ?>" alt="" class="ect-editor-option__icon">
              <span class="ect-editor-option__text">
                <span class="ect-editor-option__name">WPBakery</span>
                <span class="ect-editor-option__sub">Use the Events Calendar shortcode generator module inside WPBakery.</span>
              </span>
              <span class="ect-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>
            <?php endif; ?>

          </div>

          <!-- Right: YouTube-style dummy video preview (swaps per selection) -->
          <div class="ect-editor-selector__video" data-editor-video>
            <img src="" alt="" class="ect-editor-selector__video-watermark" data-editor-video-watermark hidden>
            <span class="ect-editor-selector__video-waves" aria-hidden="true"></span>
            <a href="#" class="ect-editor-selector__video-play" aria-label="Play walkthrough video" data-editor-video-play></a>
            <span class="ect-editor-selector__video-label">
              <span class="dashicons dashicons-video-alt3" aria-hidden="true"></span>
              <span data-editor-video-label>Pick an editor to see a walkthrough</span>
            </span>
            <!-- Iframe container (populated when Play is clicked) -->
            <div class="ect-editor-selector__video-frame" data-video-frame></div>
          </div>
        </div>

        <!-- Sibling promo (compact: icon + title + desc, no video, no buttons here) -->
        <div class="ect-editor-promo" data-editor-promo hidden></div>

        <!-- Inline nav — no Skip on step 1; Next hidden when promo is active -->
        <div class="ect-wizard-card__nav">
          <a href="#" role="button" class="ect-btn-ghost" data-wizard-back>
            <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
            <span>Back</span>
          </a>
          <div class="ect-wizard-card__nav-right" data-nav-right data-nav-mode="default">
            <!-- Default: single Continue button -->
            <a href="#" role="button" class="ect-btn-disabled" data-wizard-next data-nav-variant="default" aria-disabled="true">
              <span data-wizard-next-label>Continue</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>

            <!-- Promo variant: sibling addon NOT installed -->
            <a href="#" role="button" class="ect-btn-primary" data-nav-variant="promo-install" data-promo-install>
              <span class="dashicons dashicons-download" aria-hidden="true"></span>
              <span data-promo-install-label>Install <span data-addon-name>Events Widgets</span></span>
            </a>

            <!-- Promo variant: sibling installed but inactive -->
            <a href="#" role="button" class="ect-btn-primary" data-nav-variant="promo-activate" data-promo-activate>
              <span class="dashicons dashicons-plugins-checked" aria-hidden="true"></span>
              <span data-promo-activate-label>Activate <span data-addon-name>Events Widgets</span></span>
            </a>

            <!-- Promo variant: sibling addon ACTIVE — open plugins / setup -->
            <a href="#" role="button" class="ect-btn-primary" data-nav-variant="promo-open" data-promo-open>
              <span>Open <span data-addon-name>Events Widgets</span> setup</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>

            <!-- Shared dismiss (both promo variants) — records a "use shortcode
                 in this builder" preference and auto-advances. Step 1 selection
                 stays intact so the final step knows the page needs to be built
                 in Elementor / Divi. -->
            <a href="#" role="button" class="ect-btn-secondary" data-nav-variant="promo-dismiss" data-editor-promo-dismiss>
              <span data-editor-promo-dismiss-label>No, I'll use shortcode</span>
            </a>
          </div>
        </div>
      </section>

      <!-- ============================================================
           STEP 2 — Layout & Style (row-based settings panel)
           Each row is a 35/65 split: icon + title + one-line description
           on the left, the actual options on the right. Every option has
           its own "view demo" link that opens a live example in a new tab.
           Sections vary by Step 1 selection (block / shortcode / bricks).
           Elementor & Divi never reach this step (routed to sibling
           wizards from Step 1). If somehow they do, we fall back to
           Shortcode's section set via data-editor-selected.
           ============================================================ -->
      <section class="ect-wizard-step" data-step="layout-style">
        <header class="ect-wizard-card__heading">
          <h1 class="ect-wizard-card__title">Layout &amp; style for your events listing</h1>
          <p class="ect-wizard-card__desc" data-step2-desc>Pick a layout and style for how you want to show events from The Events Calendar plugin on your website.</p>
        </header>

        <div class="ect-settings-list">

          <!-- LAYOUT ROW ------------------------------------------------ -->
          <div class="ect-settings-row" data-section="layout">
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-layout"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">Layout</h3>
                <p class="ect-settings-row__desc">How events are arranged on the page.</p>
                <a class="ect-demo-link ect-settings-row__demo" href="https://eventscalendaraddons.com/demos/events-shortcodes-pro/" data-demo-href-default="https://eventscalendaraddons.com/demos/events-shortcodes-pro/" data-demo-href-bricks="https://eventscalendaraddons.com/bricks/grid/" data-utm="demo" target="_blank" rel="noopener">
                  <span class="dashicons dashicons-external" aria-hidden="true"></span>
                  <span>View All Layouts Demos</span>
                </a>
              </div>
            </div>
            <div class="ect-settings-row__body">

              <!-- Shortcode layouts (6) -->
              <div class="ect-layout-grid" data-editors-visible="shortcode wpbakery" data-required-selection="layout" hidden>
                <a href="#" role="button" class="ect-layout-card" data-value="default">
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-list-view"></span></span>
                  <span class="ect-layout-card__name">Default (List)</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="timeline-view">
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-clock"></span></span>
                  <span class="ect-layout-card__name">Timeline</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="minimal-list">
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-menu-alt3"></span></span>
                  <span class="ect-layout-card__name">Minimal list</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="grid-view" data-pro="true">
                  <span class="ect-layout-card__pro">Pro</span>
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-grid-view"></span></span>
                  <span class="ect-layout-card__name">Grid</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="carousel-view" data-pro="true">
                  <span class="ect-layout-card__pro">Pro</span>
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-images-alt2"></span></span>
                  <span class="ect-layout-card__name">Carousel</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="highlighted-layout" data-pro="true">
                  <span class="ect-layout-card__pro">Pro</span>
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-star-filled"></span></span>
                  <span class="ect-layout-card__name">Highlighted</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>

              <!-- Block layouts (ebec/event-list: default | minimal) -->
              <div class="ect-layout-grid ect-layout-grid--2col" data-editor-only="block" data-required-selection="layout" hidden>
                <a href="#" role="button" class="ect-layout-card" data-value="default">
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-list-view"></span></span>
                  <span class="ect-layout-card__name">Default (List)</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="minimal">
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-menu-alt3"></span></span>
                  <span class="ect-layout-card__name">Minimal</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>

              <!-- Bricks layouts: List (free) + Grid (Pro). No carousel. -->
              <div class="ect-layout-grid ect-layout-grid--2col" data-editor-only="bricks" data-required-selection="layout" hidden>
                <a href="#" role="button" class="ect-layout-card" data-value="list">
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-editor-justify"></span></span>
                  <span class="ect-layout-card__name">List</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-layout-card" data-value="grid" data-pro="true">
                  <span class="ect-layout-card__pro">Pro</span>
                  <span class="ect-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-grid-view"></span></span>
                  <span class="ect-layout-card__name">Grid</span>
                  <span class="ect-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>
            </div>
          </div>

          <!-- STYLE ROW (shortcode + bricks) ---------------------------- -->
          <div class="ect-settings-row" data-section="style" data-editors-visible="shortcode bricks wpbakery" hidden>
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-admin-appearance"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">Style</h3>
                <p class="ect-settings-row__desc">Choose a design variation for the selected layout.</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <div class="ect-style-row" data-required-selection="style">
                <a href="#" role="button" class="ect-style-card" data-value="style-1" data-bricks-layouts="list grid">
                  <span class="ect-style-card__name">Style 1</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-style-card" data-value="style-2" data-bricks-layouts="list">
                  <span class="ect-style-card__name">Style 2</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-style-card" data-value="style-3" data-pro-for="bricks" data-hide-for="bricks">
                  <span class="ect-style-card__pro" data-pro-badge-for="bricks" hidden>Pro</span>
                  <span class="ect-style-card__name">Style 3</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-style-card" data-value="style-4" data-pro="true" data-hide-for="bricks">
                  <span class="ect-style-card__pro">Pro</span>
                  <span class="ect-style-card__name">Style 4</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>
            </div>
          </div>

          <!-- FILTER BAR ROW (shortcode only, Yes is Pro) --------------- -->
          <div class="ect-settings-row" data-section="filter-bar" data-editors-visible="shortcode wpbakery" hidden>
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-filter"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">
                  Show Filter Bar
                  <span class="ect-settings-row__badge ect-pill ect-pill-pro">Pro</span>
                </h3>
                <p class="ect-settings-row__desc">Let visitors filter by category, date or venue.</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <div class="ect-toggle-cluster">
                <div class="ect-toggle-row" data-required-selection="filter-bar">
                  <a href="#" role="button" class="ect-toggle-option is-selected" data-value="no">
                    <span class="ect-toggle-option__name">No</span>
                    <span class="ect-toggle-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                  </a>
                  <a href="#" role="button" class="ect-toggle-option" data-value="yes" data-pro="true">
                    <span class="ect-toggle-option__pro">Pro</span>
                    <span class="ect-toggle-option__name">Yes</span>
                    <span class="ect-toggle-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                  </a>
                </div>
                <a class="ect-demo-link" href="https://eventscalendaraddons.com/shortcode/filter-bar/" target="_blank" rel="noopener" data-demo-link="filter-bar" hidden>
                  <span class="dashicons dashicons-external" aria-hidden="true"></span>
                  <span>See it live</span>
                </a>
              </div>
            </div>
          </div>

          <!-- DATE FORMAT ROW (all editors) ----------------------------- -->
          <div class="ect-settings-row" data-section="date-format">
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-calendar-alt"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">Date Format</h3>
                <p class="ect-settings-row__desc">How event dates appear in your listing.</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <select class="ect-date-format" data-wizard-input="date-format">
                <option value="default">Default (01 January 2026)</option>
                <option value="md-y">Md,Y (Jan 01, 2026)</option>
                <option value="fd-y">Fd,Y (January 01, 2026)</option>
                <option value="dm">dM (01 Jan)</option>
                <option value="dml">dML (01 Jan Monday)</option>
                <option value="df">dF (01 January)</option>
                <option value="md">Md (Jan 01)</option>
                <option value="fd">Fd (January 01)</option>
                <option value="md-yt">Md,YT (Jan 01, 2026 8:00am-5:00pm)</option>
                <option value="full">Full (01 January 2026 8:00am-5:00pm)</option>
                <option value="jml">jMl (1 Jan Monday)</option>
                <option value="d-fy">d.FY (01. January 2026)</option>
                <option value="d-f">d.F (01. January)</option>
                <option value="ldf">ldF (Monday 01 January)</option>
                <option value="mdl">Mdl (Jan 01 Monday)</option>
                <option value="d-ml">d.Ml (01. Jan Monday)</option>
                <option value="dft">dFT (01 January 8:00am-5:00pm)</option>
              </select>
            </div>
          </div>

        </div>

        <!-- PRO NOTICE (conditional on any Pro option selected) -->
        <div class="ect-pro-notice" data-pro-notice hidden>
          <span class="ect-pro-notice__icon" aria-hidden="true">
            <span class="dashicons dashicons-star-filled"></span>
          </span>
          <div class="ect-pro-notice__body">
            <h4 class="ect-pro-notice__title">You've picked <span data-pro-count>1</span> Pro feature<span data-pro-plural>s</span></h4>
            <p class="ect-pro-notice__desc">Upgrade to Events Shortcodes &amp; Blocks Pro to unlock everything you've selected. Or go back and pick free options to keep going.</p>
          </div>
        </div>

        <!-- NAV — Continue swaps to Upgrade to Pro when any Pro option is picked -->
        <div class="ect-wizard-card__nav">
          <a href="#" role="button" class="ect-btn-ghost" data-wizard-back>
            <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
            <span>Back</span>
          </a>
          <div class="ect-wizard-card__nav-right" data-step2-nav-right data-nav-mode="default">
            <a href="#" role="button" class="ect-btn-primary" data-wizard-next data-step2-nav-variant="default">
              <span>Continue</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>
            <a href="https://eventscalendaraddons.com/plugin/events-shortcodes-pro/" role="button" class="ect-btn-accent" data-step2-nav-variant="upgrade" target="_blank" rel="noopener">
              <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
              <span>Upgrade to Pro</span>
            </a>
          </div>
        </div>

        <!-- Editor-switch bar — Block / Bricks users only. Two modes:
             • "to-shortcode" (native display active) → suggest using shortcode
               inside the chosen builder for more layouts + styles.
             • "to-native" (shortcode display active) → give the user a way
               back to the builder's native block / widget.
             Neither variant changes Step 1's builder selection; both flip
             <body data-display-mode> so the final step knows what to insert. -->
        <div class="ect-editor-switch" data-editor-switch data-mode="to-shortcode" hidden>
          <div class="ect-editor-switch__info">
            <span class="ect-editor-switch__icon" aria-hidden="true">
              <span class="dashicons dashicons-lightbulb" data-editor-switch-icon></span>
            </span>
            <div class="ect-editor-switch__text">
              <strong data-editor-switch-title>Shortcode method has more style options</strong>
              <span data-editor-switch-desc>6 layouts, 4 style variants and a Pro filter bar &mdash; the widest choice.</span>
            </div>
          </div>
          <a href="#" role="button" class="ect-btn-secondary ect-editor-switch__cta" data-editor-switch-cta>
            <span data-editor-switch-cta-label>OK, I'll use Shortcode</span>
            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true" data-editor-switch-cta-icon></span>
          </a>
        </div>
      </section>

      <!-- ============================================================
           STEP 3 — Events Query
           Which events to load from The Events Calendar plugin. Same
           settings-row pattern as Step 2. Categories use a Select2-style
           multiselect; specific-timeframe rows are shortcode-only. All
           values persist via hidden inputs so wizard.js can restore state
           on Back navigation.
           ============================================================ -->
      <section class="ect-wizard-step" data-step="query">
        <header class="ect-wizard-card__heading">
          <h1 class="ect-wizard-card__title">Events query &mdash; which events to load?</h1>
          <p class="ect-wizard-card__desc" data-step3-desc>Choose your events query to load specific events from The Events Calendar inside your events list, grid or carousel etc.</p>
        </header>

        <div class="ect-settings-list">

          <!-- CATEGORY ROW (all editors) — Select2-style multiselect ---- -->
          <div class="ect-settings-row" data-section="category">
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-category"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">Events Category</h3>
                <p class="ect-settings-row__desc">Show events from all categories or a specific list.</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <!-- Multiselect widget. On the WP side, populate the option
                   anchors dynamically from the site's Events categories.
                   Selected values live in the hidden input as CSV so wizard
                   state persistence works with no extra plumbing. -->
              <div class="ect-multiselect" data-multiselect data-multiselect-name="category">
                <div class="ect-multiselect__control" data-multiselect-control tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                  <span class="ect-multiselect__chips" data-multiselect-chips></span>
                  <span class="ect-multiselect__placeholder" data-multiselect-placeholder>Choose categories</span>
                  <span class="ect-multiselect__caret" aria-hidden="true"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
                </div>
                <div class="ect-multiselect__dropdown" data-multiselect-dropdown role="listbox" hidden>
                  <?php echo $category_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built ?>
                </div>
                <input type="hidden" data-wizard-input="category" value="all">
              </div>
            </div>
          </div>

          <!-- EVENTS STATUS ROW (all editors) — 3 cards ------------------ -->
          <div class="ect-settings-row" data-section="time">
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-clock"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">Events Status</h3>
                <p class="ect-settings-row__desc">Show only upcoming, past or all events.</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <div class="ect-style-row ect-style-row--3col" data-required-selection="time">
                <a href="#" role="button" class="ect-style-card" data-value="upcoming">
                  <span class="ect-style-card__name">Upcoming Events</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-style-card" data-value="past">
                  <span class="ect-style-card__name">Past Events</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ect-style-card" data-value="both">
                  <span class="ect-style-card__name">Both / Any</span>
                  <span class="ect-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>
            </div>
          </div>

          <!-- FEATURED EVENTS (not Block native — shortcode / WPBakery / Bricks) -->
          <div class="ect-settings-row" data-section="featured" data-editors-visible="shortcode wpbakery bricks" hidden>
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-star-filled"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">
                  Show Only Featured Events
                  <span class="ect-settings-row__badge ect-pill ect-pill-pro">Pro</span>
                </h3>
                <p class="ect-settings-row__desc">Restrict the listing to events marked "featured".</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <div class="ect-toggle-cluster">
                <div class="ect-toggle-row" data-required-selection="featured">
                  <a href="#" role="button" class="ect-toggle-option is-selected" data-value="no">
                    <span class="ect-toggle-option__name">No</span>
                    <span class="ect-toggle-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                  </a>
                  <a href="#" role="button" class="ect-toggle-option" data-value="yes" data-pro="true">
                    <span class="ect-toggle-option__pro">Pro</span>
                    <span class="ect-toggle-option__name">Yes</span>
                    <span class="ect-toggle-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                  </a>
                </div>
                <a class="ect-demo-link" href="https://eventscalendaraddons.com/shortcode/featured-events/" data-demo-href-default="https://eventscalendaraddons.com/shortcode/featured-events/" data-demo-href-bricks="https://eventscalendaraddons.com/bricks/grid/" data-utm="demo" target="_blank" rel="noopener" data-demo-link="featured" hidden>
                  <span class="dashicons dashicons-external" aria-hidden="true"></span>
                  <span>See it live</span>
                </a>
              </div>
            </div>
          </div>

          <!-- ORGANISER / VENUE / TAG (shortcode only — free-tier upsell) -->
          <div class="ect-settings-row" data-section="taxonomy" data-editors-visible="shortcode wpbakery" hidden>
            <div class="ect-settings-row__info">
              <span class="ect-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-tag"></span>
              </span>
              <div class="ect-settings-row__meta">
                <h3 class="ect-settings-row__title">
                  Filter by Organiser, Venue or Tag
                  <span class="ect-settings-row__badge ect-pill ect-pill-pro">Pro</span>
                </h3>
                <p class="ect-settings-row__desc">Limit events to specific organiser, venue or tag.</p>
              </div>
            </div>
            <div class="ect-settings-row__body">
              <div class="ect-toggle-cluster">
                <div class="ect-toggle-row" data-required-selection="taxonomy">
                  <a href="#" role="button" class="ect-toggle-option is-selected" data-value="no">
                    <span class="ect-toggle-option__name">No</span>
                    <span class="ect-toggle-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                  </a>
                  <a href="#" role="button" class="ect-toggle-option" data-value="yes" data-pro="true">
                    <span class="ect-toggle-option__pro">Pro</span>
                    <span class="ect-toggle-option__name">Yes</span>
                    <span class="ect-toggle-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                  </a>
                </div>
                <a class="ect-demo-link" href="https://eventscalendaraddons.com/shortcode/events-by-venue/" target="_blank" rel="noopener" data-demo-link="taxonomy" hidden>
                  <span class="dashicons dashicons-external" aria-hidden="true"></span>
                  <span>See it live</span>
                </a>
              </div>
            </div>
          </div>

        </div>

        <!-- PRO NOTICE (per-step, mirrors Step 2 pattern) -->
        <div class="ect-pro-notice" data-pro-notice hidden>
          <span class="ect-pro-notice__icon" aria-hidden="true">
            <span class="dashicons dashicons-star-filled"></span>
          </span>
          <div class="ect-pro-notice__body">
            <h4 class="ect-pro-notice__title">You've picked <span data-pro-count>1</span> Pro feature<span data-pro-plural>s</span></h4>
            <p class="ect-pro-notice__desc">Upgrade to Events Shortcodes &amp; Blocks Pro to unlock everything you've selected. Or go back and pick free options to keep going.</p>
          </div>
        </div>

        <!-- Telemetry consent — moved from the removed Review step; sits at
             the bottom of the final settings step so it's the last thing users
             see before creating (or continuing to) their draft page.
             Container is a <div> (not a <label>) so only the checkbox area
             toggles state — clicking the description won't accidentally
             uncheck. -->
        <div class="ect-telemetry"<?php echo $show_telemetry ? '' : ' hidden'; ?>>
          <label class="ect-telemetry__checkbox-wrap">
            <input type="checkbox" class="ect-telemetry__checkbox" data-wizard-telemetry checked>
            <span class="ect-telemetry__mark" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
          </label>
          <div class="ect-telemetry__body">
            <strong class="ect-telemetry__title">Help improve Events Calendar Addons</strong>
            <span class="ect-telemetry__desc">Share non-sensitive usage data &mdash; WP version, addon versions, active builder, theme name. No personal data or event content. Change anytime in Settings. <a class="ect-telemetry__policy" href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank" rel="noopener">View policy<span class="dashicons dashicons-external" aria-hidden="true"></span></a></span>
          </div>
        </div>

        <!-- NAV — Continue label is dynamic:
             • Shortcode users → "Continue" (page created later on Step 4, so
               users who just want to copy the shortcode aren't forced into
               page creation).
             • Block / Bricks users → "Create Draft Page" (they arrive on
               Step 4 already in the post-create state).
             Also swaps to "Upgrade to Pro" when a Pro option is selected. -->
        <div class="ect-wizard-card__nav">
          <a href="#" role="button" class="ect-btn-ghost" data-wizard-back>
            <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
            <span>Back</span>
          </a>
          <div class="ect-wizard-card__nav-right" data-step3-nav-right data-nav-mode="default">
            <!-- Small builder-aware hint that only appears for Block / Bricks
                 users just before the "Create Draft Page" button so they know
                 exactly what will be inserted. Hidden by refreshStep3() for
                 shortcode users. -->
            <span class="ect-wizard-card__nav-hint" data-step3-create-hint hidden></span>
            <a href="#" role="button" class="ect-btn-primary" data-wizard-next data-step3-nav-variant="default" data-step3-primary-cta>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true" data-step3-primary-icon></span>
              <span data-step3-primary-label>Continue</span>
            </a>
            <a href="https://eventscalendaraddons.com/plugin/events-shortcodes-pro/" role="button" class="ect-btn-accent" data-step3-nav-variant="upgrade" target="_blank" rel="noopener">
              <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
              <span>Upgrade to Pro</span>
            </a>
          </div>
        </div>
      </section>

      <!-- ============================================================
           STEP 4 — Done
           Two visual states driven by body[data-page-created]:
             pre-create : shortcode users who continued from Step 3 — CTA
                          is "Create Draft Page". Copy the shortcode and
                          finish, OR create a draft.
             post-create: block/bricks users (auto-created on Step 3) OR
                          shortcode users after they clicked "Create Draft
                          Page" here — CTAs become Preview / Edit /
                          Change Colors & Typography (shortcode only) /
                          Recreate / Finish.
           ============================================================ -->
      <section class="ect-wizard-step" data-step="success" data-always-valid="true">
        <div class="ect-wizard-success">
          <span class="ect-wizard-success__icon ect-wizard-success__icon--lg" aria-hidden="true">
            <span class="dashicons dashicons-yes"></span>
          </span>
          <h2 class="ect-wizard-success__title" data-step5-title>All done!</h2>
          <p class="ect-wizard-success__lede" data-step5-lede>Your events listing draft page is ready to preview.</p>
        </div>

        <div class="ect-review-recap">

          <!-- Shortcode recap (shortcode users) -->
          <div class="ect-review-panel" data-review-panel="shortcode-recap" hidden>
            <div class="ect-review-panel__header">
              <span class="ect-review-panel__icon" aria-hidden="true"><span class="dashicons dashicons-editor-code"></span></span>
              <h3 class="ect-review-panel__title">Your events shortcode</h3>
              <a href="#" role="button" class="ect-code-block__copy" data-shortcode-copy>
                <span class="dashicons dashicons-admin-page" aria-hidden="true" data-shortcode-copy-icon></span>
                <span data-shortcode-copy-label>Copy</span>
              </a>
            </div>
            <pre class="ect-code-block"><code data-shortcode-code></code></pre>
            <p class="ect-review-panel__hint">Save this shortcode for later &mdash; paste it anywhere to display events.</p>
          </div>

          <!-- Builder recap (block / bricks users). No panel header —
               the success-header ("Your Events {Layout} is ready!") already
               communicates the created state. Just the logo + one-liner. -->
          <div class="ect-review-panel ect-review-panel--builder" data-review-panel="builder-recap" hidden>
            <div class="ect-review-builder">
              <img class="ect-review-builder__logo" data-review-builder-logo src="<?php echo esc_url( $editor_icons['block'] ); ?>" alt="">
              <div>
                <strong class="ect-review-builder__title" data-review-builder-title>Events block inserted in Block Editor</strong>
                <p class="ect-review-builder__desc" data-review-builder-desc>Preview or open the draft to fine-tune anything.</p>
              </div>
            </div>
          </div>

        </div>

        <!-- Success actions — two states via body[data-page-created]:
             • pre-create : [Create Draft Page] + [Change Colors & Typography]
                            (shortcode users only — block/bricks users are
                            already in the post-create state)
             • post-create: [Preview Page] + [Edit Page] +
                            [Change Colors & Typography] (shortcode-only)
             Finish is always visible (green, exits to the ECA dashboard).
             `data-success-shortcode-only` hides Change Colors for block /
             bricks users — their styling lives inside the block / builder. -->
        <div class="ect-wizard-success__actions">

          <!-- Pre-create (shortcode users only) -->
          <a href="#" role="button" class="ect-btn-primary" data-page-create data-page-cta="pre">
            <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
            <span>Create Draft Page</span>
          </a>

          <!-- Post-create — hrefs filled by JS after draft creation -->
          <a href="#" target="_blank" rel="noopener" class="ect-btn-primary" data-page-cta="post" data-page-preview>
            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            <span>Preview Page</span>
          </a>
          <a href="#" target="_blank" rel="noopener" class="ect-btn-secondary" data-page-cta="post" data-page-edit>
            <span class="dashicons dashicons-edit" aria-hidden="true"></span>
            <span>Edit Page</span>
          </a>

          <!-- Always visible for shortcode users. Opens the shortcode styling
               settings page in a new tab — not connected to draft creation,
               so it makes sense both before AND after the page is created. -->
          <a href="#" target="_blank" rel="noopener" class="ect-btn-secondary" data-success-shortcode-only data-page-settings>
            <span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span>
            <span>Improve Colors &amp; Typography</span>
          </a>

          <!-- Finish — only visible AFTER the draft page has been created.
               Otherwise users would click Finish and exit without ever
               creating a page. Exit Setup (header) remains the escape hatch
               for anyone who really doesn't want a draft page. -->
          <a href="<?php echo esc_url( $dashboard_url ); ?>" data-wizard-finish class="ect-btn-success" data-page-cta="post">
            <span>DONE</span>
            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
          </a>
        </div>

        <!-- Cross-sell — Single Page Builder promo -->
        <div class="ect-cross-sell" data-spb-promo>
          <span class="ect-cross-sell__icon" aria-hidden="true">
            <img src="<?php echo esc_url( $icons ); ?>event-single-page-icon.svg" alt="">
          </span>
          <div class="ect-cross-sell__body">
            <strong class="ect-cross-sell__title">Design your single event pages too?</strong>
            <span class="ect-cross-sell__desc">Events Single Page Builder makes each event page stand out with pre-built templates.</span>
          </div>
          <div class="ect-cross-sell__actions">
            <a href="#" role="button" class="ect-btn-primary" data-spb-install>
              <span class="dashicons dashicons-download" aria-hidden="true"></span>
              <span data-spb-label>Install &amp; set up</span>
            </a>
            <a href="<?php echo esc_url( $dashboard_url ); ?>" data-wizard-finish class="ect-btn-ghost">Not now</a>
          </div>
        </div>
      </section>

    </div>
  </main>

</div>
</div>
