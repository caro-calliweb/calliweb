<?php
/**
 * Shared layout template behavior (default parts, normalization, card shell).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Layout_Base', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	abstract class ECT_Bricks_Layout_Base {

		/**
		 * Layout key (reserved for layout-specific filters).
		 *
		 * @return string grid|style1|style2
		 */
		protected static function ect_bricks_layout_key() {
			return '';
		}

		/**
		 * Per-row defaults after clean/upgrade.
		 *
		 * @param array<string,mixed> $row Repeater row.
		 * @return array<string,mixed>
		 */
		protected static function ect_bricks_normalize_row( array $row ) {
			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				return \ECT_Bricks_Styles::ect_bricks_normalize_layout_row( $row );
			}
			if ( class_exists( 'ECT_Bricks_Meta_Combo', false ) ) {
				return ECT_Bricks_Meta_Combo::ect_bricks_normalize_layout_row( $row );
			}

			return $row;
		}

		/**
		 * When clean yields no rows, return fallback parts stack.
		 *
		 * @param array<int,mixed> $parts Original parts passed to ect_bricks_norm_parts().
		 * @return array<int,array<string,mixed>>
		 */
		protected static function ect_bricks_empty_parts_fallback( array $parts ) {
			unset( $parts );
			return static::ect_bricks_default_parts();
		}

		/**
		 * Optional filter between clean and per-row normalization.
		 *
		 * @param array<int,mixed> $clean Cleaned repeater rows.
		 * @return array<int,mixed>
		 */
		protected static function ect_bricks_filter_parts( array $clean ) {
			return $clean;
		}

		/**
		 * Skin slug for part rendering and layout meta lists.
		 *
		 * @return string
		 */
		protected static function ect_bricks_skin() {
			return static::ect_bricks_layout_key();
		}

		/**
		 * Layout skin class/HTML defaults. Subclasses override this instead of many one-liners.
		 *
		 * @return array{
		 *     card_base_class?:string,
		 *     no_image_class?:string,
		 *     no_date_class?:string,
		 *     image_wrap_class?:string,
		 *     featured_skin?:string,
		 *     content_open?:string,
		 *     content_close?:string
		 * }
		 */
		protected static function ect_bricks_skin_config() {
			return array();
		}

		/** @param string $key Config key. @param string $default Fallback. @return string */
		private static function ect_bricks_skin_value( $key, $default = '' ) {
			$config = static::ect_bricks_skin_config();
			return isset( $config[ $key ] ) ? (string) $config[ $key ] : $default;
		}

		/**
		 * Card root class list (without --no-image modifier).
		 *
		 * @return string
		 */
		protected static function ect_bricks_card_base_class() {
			return static::ect_bricks_skin_value( 'card_base_class', 'ect-ev__item-inner' );
		}

		/**
		 * Modifier class when the featured image shell is hidden.
		 *
		 * @return string
		 */
		protected static function ect_bricks_no_image_class() {
			return static::ect_bricks_skin_value( 'no_image_class' );
		}

		/**
		 * Modifier class when the layout date column is hidden.
		 *
		 * @return string
		 */
		protected static function ect_bricks_no_date_class() {
			return static::ect_bricks_skin_value( 'no_date_class' );
		}

		/**
		 * Append layout-specific card modifier classes (e.g. Style 1 no-date column).
		 *
		 * @param string              $card_class Card root classes.
		 * @param array<string,mixed> $settings   Widget settings.
		 * @return string
		 */
		protected static function ect_bricks_append_card_classes( $card_class, array $settings ) {
			unset( $settings );
			return $card_class;
		}

		/**
		 * @return array<int,array<string,mixed>>
		 */
		public static function ect_bricks_default_parts() {
			$style = ( static::ect_bricks_layout_key() === 'style2' ) ? 'style-2' : 'style-1';

			return \ECT_Bricks_List_Defaults::ect_bricks_default_parts( $style );
		}

		/**
		 * @param array<int,mixed> $parts Repeater rows.
		 * @return array<int,mixed>
		 */
		public static function ect_bricks_norm_parts( array $parts ) {
			if ( ! class_exists( 'ECT_Bricks_Markup', false ) ) {
				return $parts;
			}

			$upgraded = \ECT_Bricks_Markup::ect_bricks_upgrade_layout_parts(
				$parts,
				static function () {
					return static::ect_bricks_default_parts();
				}
			);
			if ( is_array( $upgraded ) ) {
				$parts = $upgraded;
			}

			$clean = \ECT_Bricks_Markup::ect_bricks_parts_clean( $parts );
			if ( $clean === array() ) {
				return static::ect_bricks_empty_parts_fallback( $parts );
			}

			$clean = static::ect_bricks_filter_parts( $clean );

			$clean = array_map(
				static function ( $row ) {
					if ( ! is_array( $row ) ) {
						return $row;
					}

					return static::ect_bricks_normalize_row( $row );
				},
				$clean
			);

			return \ECT_Bricks_Markup::ect_bricks_parts_assign_ids( $clean );
		}

		/**
		 * @param \WP_Post $post       Event post.
		 * @param array    $parts      Repeater rows.
		 * @param callable $emit_part  Part renderer.
		 * @param array    $settings   Widget settings.
		 * @return string
		 */
		public static function ect_bricks_item_inner( $post, array $parts, callable $emit_part, $settings = array() ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				return '';
			}

			$settings   = is_array( $settings ) ? $settings : array();
			$show_image = class_exists( 'ECT_Bricks_Markup', false )
				? \ECT_Bricks_Markup::ect_bricks_show_event_image( $settings )
				: true;

			$card_class = static::ect_bricks_card_base_class();
			if ( ! $show_image ) {
				$no_image_class = static::ect_bricks_no_image_class();
				if ( $no_image_class !== '' ) {
					$card_class .= ' ' . $no_image_class;
				}
			}

			$card_class = static::ect_bricks_append_card_classes( $card_class, $settings );

			$html = '<div class="' . esc_attr( $card_class ) . '">';

			if ( $show_image && class_exists( 'ECT_Bricks_Markup', false ) ) {
				ob_start();
				static::ect_bricks_render_image_shell( $post, $settings );
				$html .= (string) ob_get_clean();
			}

			ob_start();
			static::ect_bricks_open_content( $post, $settings, $show_image );
			$html .= (string) ob_get_clean();

			if ( class_exists( 'ECT_Bricks_Markup', false ) ) {
				$skin         = static::ect_bricks_skin();
				$emit_meta_cb = static function ( $ev, $item, $idx, $price ) use ( $skin, $settings ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in ECT_Bricks_Markup::ect_bricks_render_meta_li().
					echo \ECT_Bricks_Markup::ect_bricks_render_meta_li( $ev, $item, $idx, $skin, (bool) $price, $settings );
				};
				ob_start();
				\ECT_Bricks_Markup::ect_bricks_render_layout_parts_sequence(
					$post,
					$parts,
					$skin,
					$emit_part,
					$emit_meta_cb
				);
				$html .= (string) ob_get_clean();
			}

			ob_start();
			static::ect_bricks_close_content();
			$html .= (string) ob_get_clean();

			$html .= '</div>';

			return $html;
		}

		/**
		 * Image shell wrapper class (empty in base = no image column).
		 *
		 * @return string
		 */
		protected static function ect_bricks_image_wrap_class() {
			return static::ect_bricks_skin_value( 'image_wrap_class' );
		}

		/**
		 * Optional badge HTML inside the image shell.
		 *
		 * @param \WP_Post            $post     Event post.
		 * @param array<string,mixed> $settings Widget settings.
		 * @return string
		 */
		protected static function ect_bricks_image_shell_badge_html( $post, array $settings ) {
			unset( $post, $settings );
			return '';
		}

		/**
		 * Skin key passed to ect_bricks_shell_featured_image().
		 *
		 * @return string
		 */
		protected static function ect_bricks_image_shell_featured_skin() {
			return static::ect_bricks_skin_value( 'featured_skin' );
		}

		/**
		 * Featured image column (badge + image).
		 *
		 * @param \WP_Post            $post     Event post.
		 * @param array<string,mixed> $settings Widget settings.
		 * @return void
		 */
		protected static function ect_bricks_render_image_shell( $post, array $settings ) {
			$wrap_class = static::ect_bricks_image_wrap_class();
			if ( $wrap_class === '' ) {
				return;
			}

			echo '<div class="' . esc_attr( $wrap_class ) . '">';
			$badge_html = static::ect_bricks_image_shell_badge_html( $post, $settings );
			if ( $badge_html !== '' ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $badge_html;
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \ECT_Bricks_Markup::ect_bricks_shell_featured_image( $post, static::ect_bricks_image_shell_featured_skin(), true, false );
			echo '</div>';
		}

		/**
		 * Open content wrappers before the parts sequence.
		 *
		 * @param \WP_Post            $post       Event post.
		 * @param array<string,mixed> $settings   Widget settings.
		 * @param bool                $show_image Whether the image shell is visible.
		 * @return void
		 */
		protected static function ect_bricks_open_content( $post, array $settings, $show_image ) {
			unset( $post, $settings, $show_image );
			$html = static::ect_bricks_skin_value( 'content_open' );
			if ( $html !== '' ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- layout template HTML.
				echo $html;
			}
		}

		/**
		 * Close content wrappers opened in ect_bricks_open_content().
		 *
		 * @return void
		 */
		protected static function ect_bricks_close_content() {
			$html = static::ect_bricks_skin_value( 'content_close' );
			if ( $html !== '' ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- layout template HTML.
				echo $html;
			}
		}
	}

}
