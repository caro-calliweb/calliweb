<?php
/**
 * Events Widget — List template / Style 1 (ect-list reference layout).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_List_1', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_List_1 extends ECT_Bricks_Layout_Base {

		protected static function ect_bricks_layout_key() {
			return 'style1';
		}

		protected static function ect_bricks_skin_config() {
			return array(
				'card_base_class'  => 'ect-list ect-ev__item-inner ect-ev__item-inner--style1',
				'no_image_class'   => 'ect-list--no-image',
				'no_date_class'    => 'ect-list--no-date',
				'image_wrap_class' => 'ect-list__img-wrap',
				'featured_skin'    => 'style1',
				'content_close'    => '</div></div>',
			);
		}

		protected static function ect_bricks_append_card_classes( $card_class, array $settings ) {
			$no_date_class = self::ect_bricks_no_date_class();
			if (
				$no_date_class !== ''
				&& class_exists( 'ECT_Bricks_Markup', false )
				&& ! \ECT_Bricks_Markup::ect_bricks_show_list1_date_column( $settings )
			) {
				$card_class .= ' ' . $no_date_class;
			}

			return $card_class;
		}

		protected static function ect_bricks_image_shell_badge_html( $post, array $settings ) {
			if ( ! \ECT_Bricks_Markup::ect_bricks_show_shell_category_badge( $settings ) ) {
				return '';
			}
			return \ECT_Bricks_Markup::ect_bricks_shell_category_badge( $post, $settings );
		}

		protected static function ect_bricks_open_content( $post, array $settings, $show_image ) {
			unset( $show_image );
			$show_date = \ECT_Bricks_Markup::ect_bricks_show_list1_date_column( $settings );

			echo '<div class="ect-list__content">';
			if ( $show_date ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \ECT_Bricks_Markup::ect_bricks_list1_date_column( $post, $settings );
			}
			echo '<div class="ect-list__body">';
		}
	}
}
