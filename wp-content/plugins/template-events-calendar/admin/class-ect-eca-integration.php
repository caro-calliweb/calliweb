<?php
/**
 * Boots the shared ECA dashboard module for Events Shortcodes & Blocks.
 *
 * @package Template_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_ECA_Integration' ) ) {

	/**
	 * Single entry point for ECA dashboard integration.
	 */
	final class ECT_ECA_Integration {

		const DASHBOARD_PAGE_SLUG = 'cool-plugins-events-addon';
		const DASHBOARD_VERSION   = '1.0.0';

		/**
		 * Admin page slugs used by CPFM notices and notice-hiding rules.
		 *
		 * @return string[]
		 */
		public static function admin_page_slugs() {
			return array(
				self::DASHBOARD_PAGE_SLUG,
				'cool-events-registration',
				ECT_Onboarding_Page::PAGE_SLUG,
				ECT_Onboarding_Page::LEGACY_PAGE_SLUG,
			);
		}

		/**
		 * Load dashboard classes and register this addon as the ESB host.
		 */
		public static function boot_admin() {
			require_once ECT_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-dashboard-registry.php';
			require_once ECT_PLUGIN_DIR . 'admin/ect-onboarding/class-ect-onboarding-page.php';

			ECA_Dashboard_Registry::submit( self::DASHBOARD_VERSION, ECT_PLUGIN_DIR . 'admin/eca-dashboard/' );
			ECA_Dashboard_Registry::register_addon(
				array(
					'slug'          => 'esb',
					'host_slug'     => 'esb',
					'text_domain'   => 'template-events-calendar',
					'dashboard_url' => ECT_PLUGIN_URL . 'admin/eca-dashboard/',
					'admin_urls'    => array(
						// Driver keys → More Addons / shared deep-links.
						'esb'       => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
						'ect'       => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
						'divi'      => admin_url( 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG ),
						'widgets'   => admin_url( 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG ),
						'spb'       => admin_url( 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG ),
						'speakers'  => admin_url( 'admin.php?page=esas-speaker-sponsor-settings' ),
						'countdown' => admin_url( 'admin.php?page=countdown_for_the_events_calendar' ),
						// Workflow method-tab ids → "Open settings" (only tabs listed here).
						// Block shares the esb driver but is intentionally omitted.
						'shortcode' => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
					),
					'menu'          => array(
						'slug'     => self::DASHBOARD_PAGE_SLUG,
						// Titles translated in ECA_Dashboard_Page::register_menus() on admin_menu (WP 6.7+).
						'position' => 9,
					),
				)
			);

			add_action( 'plugins_loaded', array( 'ECA_Dashboard_Registry', 'boot' ), 20 );
			ECT_Onboarding_Page::init();
			// Activation redirect: fresh → onboarding, reactivation → dashboard
			// (scheduled in activate(), consumed via ECT_Onboarding_Page::maybe_redirect).
		}
	}
}
