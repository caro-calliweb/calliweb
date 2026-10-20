<?php
/**
 * Events Widget — List template / Style 2 (ect-card reference layout).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_List_2', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_List_2 extends ECT_Bricks_Layout_Base {

		public static function ect_bricks_part_class( $part ) {
			$part = sanitize_key( (string) $part );
			return 'ect-s2-' . str_replace( '_', '-', $part );
		}

		protected static function ect_bricks_layout_key() {
			return 'style2';
		}

		protected static function ect_bricks_skin_config() {
			return array(
				'card_base_class'  => 'ect-card ect-s2 ect-ev__item-inner ect-ev__item-inner--style2',
				'no_image_class'   => 'ect-card--no-image',
				'image_wrap_class' => 'ect-card__img-wrap',
				'featured_skin'    => 'style2',
				'content_open'     => '<div class="ect-card__content">',
				'content_close'    => '</div>',
			);
		}

		protected static function ect_bricks_empty_parts_fallback( array $parts ) {
			return \ECT_Bricks_Markup::ect_bricks_parts_preserve_bricks_rows( $parts, self::ect_bricks_default_parts() );
		}

		protected static function ect_bricks_image_shell_badge_html( $post, array $settings ) {
			if ( ! \ECT_Bricks_Markup::ect_bricks_show_style2_date_badge( $settings ) ) {
				return '';
			}
			return \ECT_Bricks_Markup::ect_bricks_list2_date_badge( $post, $settings );
		}
	}
}
