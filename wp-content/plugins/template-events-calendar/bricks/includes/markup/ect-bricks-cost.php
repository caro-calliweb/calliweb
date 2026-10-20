<?php
/**
 * Event cost labels.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Cost_Formatter', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Cost_Formatter {

		/** Normalize raw cost text for comparisons and display. */
		private static function ect_bricks_normalize_cost_text( $value ): string {
			return trim( wp_strip_all_tags( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) ) );
		}

		/** True when normalized cost text is zero or a common “free” label. */
		private static function ect_bricks_cost_text_is_free( string $text ): bool {
			if ( $text === '' ) {
				return false;
			}
			if ( in_array( strtolower( $text ), array( 'free', 'gratis', 'no cost', 'nocost', 'included' ), true ) ) {
				return true;
			}
			$num = self::ect_bricks_strip_normalized_cost_symbols( $text );
			return $num !== '' && (bool) preg_match( '/^0(?:\.0+)?$/', $num );
		}

		/** Strip currency symbols and grouping chars so numeric free-check can run on the amount. */
		private static function ect_bricks_strip_normalized_cost_symbols( string $text ): string {
			$text = trim( $text );
			if ( $text === '' ) {
				return '';
			}

			$plain = preg_replace( '/[^0-9.\-]/', '', str_replace( ',', '', $text ) );

			return is_string( $plain ) ? trim( $plain ) : '';
		}

		/**
		 * Plain-text cost for an event (Free / amount / range) using the site default symbol.
		 *
		 * @param int   $post_id         Event post ID.
		 * @param array $item            Repeater row (unused; kept for signature parity).
		 * @param array $widget_settings Widget settings (unused; kept for signature parity).
		 * @return string
		 */
		public static function ect_bricks_format_cost_display( $post_id, array $item = array(), array $widget_settings = array() ): string {
			unset( $item, $widget_settings );
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return '';
			}

			$cost = '';
			if ( function_exists( 'tribe_get_cost' ) ) {
				$cost = self::ect_bricks_normalize_cost_text( tribe_get_cost( $post_id, true ) );
			}
			if ( $cost === '' ) {
				$cost = self::ect_bricks_normalize_cost_text( get_post_meta( $post_id, '_EventCost', true ) );
			}
			if ( $cost === '' || self::ect_bricks_cost_text_is_free( $cost ) ) {
				return __( 'Free', 'template-events-calendar' );
			}

			return $cost;
		}

		/** List-style cost label (no “From” prefix; TEC ranges use a hyphen). */
		public static function ect_bricks_layout_cost_label( $post_id, array $item = array(), array $widget_settings = array() ): string {
			$cost = self::ect_bricks_format_cost_display( $post_id, $item, $widget_settings );
			if ( $cost === '' ) {
				return '';
			}

			return str_replace( ' – ', '-', $cost );
		}
	}
}
