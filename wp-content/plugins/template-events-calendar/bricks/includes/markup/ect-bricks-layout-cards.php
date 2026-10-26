<?php
/**
 * List layout shell markup (dates, images, meta lists).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Layout_Shell', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Layout_Shell {
		/** Event start Unix timestamp. */
		public static function ect_bricks_event_start_timestamp( $post_id ) {
			list( $start_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( $post_id );
			return $start_ts ? (int) $start_ts : false;
		}

		public static function ect_bricks_list1_date_column( $post, $settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = self::ect_bricks_event_start_timestamp( $post->ID );
			if ( ! $start_ts ) {
				return '<div class="ect-list__date"></div>';
			}
			$order = ECT_Bricks_Settings_Normalizer::ect_bricks_list1_date_column_order( is_array( $settings ) ? $settings : array() );
			$day   = '<span class="ect-list__day">' . esc_html( date_i18n( 'd', $start_ts ) ) . '</span>';
			$month = '<span class="ect-list__month">' . esc_html( date_i18n( 'M', $start_ts ) ) . '</span>';
			$inner = ( $order === 'day_month' ) ? $day . $month : $month . $day;
			return '<div class="ect-list__date ect-list__date--' . esc_attr( $order ) . '">'
				. '<div class="ect-list__date-inner">' . $inner . '</div>'
				. '</div>';
		}

		public static function ect_bricks_list2_date_badge( $post, $settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$start_ts = self::ect_bricks_event_start_timestamp( $post->ID );
			if ( ! $start_ts ) {
				return '';
			}
			$order      = ECT_Bricks_Settings_Normalizer::ect_bricks_style2_date_badge_order( is_array( $settings ) ? $settings : array() );
			$cls        = 'ect-card__date-badge ect-card__date-badge--' . $order;
			$date_label = date_i18n( 'F j, Y', $start_ts );
			$month_abbr = date_i18n( 'M', $start_ts );
			$day_num    = date_i18n( 'd', $start_ts );
			$month      = '<span>' . esc_html( $month_abbr ) . '</span>';
			$day        = '<strong>' . esc_html( $day_num ) . '</strong>';
			$inner      = ( $order === 'day_month' ) ? $day . $month : $month . $day;

			return '<time class="' . esc_attr( $cls ) . '" datetime="' . esc_attr( wp_date( 'Y-m-d', $start_ts ) ) . '" aria-label="' . esc_attr( $date_label ) . '">'
				. $inner
				. '</time>';
		}

		public static function ect_bricks_date_range_text( $post, array $item = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( $post->ID );
			if ( ! $start_ts ) {
				return '';
			}

			$sed = ECT_Bricks_Date_Formatter::ect_bricks_sed_preset_range_text( $post->ID, $item );
			if ( $sed !== '' ) {
				return $sed;
			}

			$php = ECT_Bricks_Date_Formatter::ect_bricks_part_date_php_fmt( 'event_date', $item );
			if ( $php === '' ) {
				$php = 'd M, Y';
			}

			if ( date_i18n( 'Ymd', $start_ts ) === date_i18n( 'Ymd', $end_ts ) ) {
				return date_i18n( $php, $start_ts );
			}
			return date_i18n( $php, $start_ts ) . ' - ' . date_i18n( $php, $end_ts );
		}

		public static function ect_bricks_render_date_range( $post, array $item, $idx, $skin = '' ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = self::ect_bricks_date_range_text( $post, $item );
			if ( $text === '' ) {
				return '';
			}
			$wrap = esc_attr(
				ECT_Bricks_Part_Chrome::ect_bricks_part_classes( 'date', $idx, $skin, $item )
			);
			$attr = ECT_Bricks_Part_Chrome::ect_bricks_part_wrap_attrs( $item, $idx );
			return '<div class="' . $wrap . '"' . $attr . '><span class="ect-fld__plain">' . esc_html( $text ) . '</span></div>';
		}

		/**
		 * @param \WP_Term[] $terms
		 */
		private static function ect_bricks_render_layout_category_row( $post, array $item, $idx, $skin, $wrap_prefix ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = self::ect_bricks_event_category_terms( $post->ID );
			if ( $terms === array() ) {
				return '';
			}

			$skin = (string) $skin;
			$attr = ECT_Bricks_Part_Chrome::ect_bricks_part_wrap_attrs( $item, $idx );
			$wrap = esc_attr(
				trim(
					(string) $wrap_prefix . ' '
					. ECT_Bricks_Part_Chrome::ect_bricks_part_classes( 'categories', $idx, $skin, $item )
				)
			);

			$links = ECT_Bricks_Part_Chrome::ect_bricks_term_anchor_links( $terms, 'ect-card__cat' );
			if ( $links === array() ) {
				return '';
			}

			return '<div class="' . $wrap . '"' . $attr . '>' . implode( '', $links ) . '</div>';
		}

		public static function ect_bricks_render_style2_category( $post, array $item, $idx, $skin ) {
			return self::ect_bricks_render_layout_category_row( $post, $item, $idx, $skin, 'ect-card__top' );
		}

		public static function ect_bricks_render_layout_read_more( $post, array $item, $idx, $skin ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$label = isset( $item['read_more_text'] ) ? trim( (string) $item['read_more_text'] ) : '';
			if ( $label === '' ) {
				$label = __( 'View Details', 'template-events-calendar' );
			} else {
				$label = sanitize_text_field( $label );
			}

			$skin      = (string) $skin;
			$wrap      = esc_attr( ECT_Bricks_Part_Chrome::ect_bricks_part_classes( 'read_more', $idx, $skin, $item ) );
			$part_attr = ECT_Bricks_Part_Chrome::ect_bricks_part_wrap_attrs( $item, $idx );
			$url       = get_permalink( $post->ID );
			$link_cls  = esc_attr( trim( 'ect-fld__link ' . ECT_Bricks_Part_Chrome::ect_bricks_layout_read_more_btn_class( $skin ) ) );
			$link_attr = ECT_Bricks_Part_Chrome::ect_bricks_part_link_typography_attr( $item );
			$link      = '<a href="' . esc_url( $url ) . '" class="' . $link_cls . '"' . $link_attr . '>' . esc_html( $label ) . '</a>';
			$block     = '<div class="' . $wrap . '"' . $part_attr . '>' . $link . '</div>';

			if ( $skin === 'style2' ) {
				return self::ect_bricks_wrap_style2_footer( $block );
			}

			return $block;
		}

		/**
		 * Style 2 divider + footer wrapper around CTA / read-more markup.
		 *
		 * @param string $html Inner HTML.
		 * @return string
		 */
		private static function ect_bricks_wrap_style2_footer( $html ) {
			return '<div class="ect-card__divider"></div><div class="ect-card__footer">' . $html . '</div>';
		}

		/**
		 * Shell read-more chrome around standard repeater part output (btn_style / hover / Style tab).
		 *
		 * @param \WP_Post $post
		 * @param array    $item
		 * @param int      $idx
		 * @param string   $skin
		 * @param callable $emit_part
		 * @return string
		 */
		public static function ect_bricks_render_layout_read_more_shell( $post, array $item, $idx, $skin, callable $emit_part ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$skin = (string) $skin;

			ob_start();
			$emit_part( $post, $item, $idx );
			$html = trim( (string) ob_get_clean() );
			if ( $html === '' ) {
				return self::ect_bricks_render_layout_read_more( $post, $item, $idx, $skin );
			}

			$html = ECT_Bricks_Part_Chrome::ect_bricks_read_more_merge_link_classes(
				$html,
				ECT_Bricks_Part_Chrome::ect_bricks_layout_read_more_btn_class( $skin )
			);

			if ( $skin === 'style2' ) {
				return self::ect_bricks_wrap_style2_footer( $html );
			}

			return $html;
		}

		/**
		 * Render repeater rows in saved order (respects drag-and-drop + mixed flow/meta rows).
		 *
		 * @param \WP_Post $post
		 * @param array    $parts
		 * @param string   $skin   style1|style2
		 * @param callable $emit_part  function( $post, $item, $idx )
		 * @param callable $emit_meta  function( $post, $item, $idx, $price )
		 * @return void
		 */
		public static function ect_bricks_render_layout_parts_sequence( $post, array $parts, $skin, callable $emit_part, callable $emit_meta ) {
			if ( ! $post instanceof \WP_Post ) {
				return;
			}

			$meta_rows = array();

			$flush_meta = static function () use ( $post, $skin, &$meta_rows, $emit_meta ) {
				if ( $meta_rows === array() ) {
					return;
				}
				self::ect_bricks_flush_meta_rows( $post, $meta_rows, $skin, $emit_meta );
				$meta_rows = array();
			};

			foreach ( $parts as $i => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$ui = (string) ( $item['part'] ?? '' );
				if ( $ui === '' ) {
					continue;
				}

				if ( $ui === 'read_more' ) {
					$flush_meta();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::ect_bricks_render_layout_read_more_shell( $post, $item, (int) $i, (string) $skin, $emit_part );
					continue;
				}

				if ( self::ect_bricks_shell_skip_part( $ui ) ) {
					continue;
				}

				if ( $ui === 'categories' && $skin === 'style2' ) {
					$flush_meta();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::ect_bricks_render_style2_category( $post, $item, (int) $i, $skin );
					continue;
				}

				$meta_row = self::ect_bricks_resolve_layout_meta_row( $item );
				if ( $meta_row !== null ) {
					$meta_rows[] = array(
						'idx'   => (int) $i,
						'item'  => $meta_row['item'],
						'price' => self::ect_bricks_meta_row_is_price( $ui, $meta_row['slug'] ),
					);
					continue;
				}

				$flush_meta();
				$emit_part( $post, $item, (int) $i );
			}

			$flush_meta();
		}

		public static function ect_bricks_render_meta_li( $post, array $item, $idx, $skin, $price = false, array $widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$html = ECT_Bricks_Part_Renderer::ect_bricks_render_part_ext( $post, $item, $idx, $skin, $widget_settings );
			if ( $html === '' || $html === false ) {
				return '';
			}

			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( empty( $item['_ect_bricks_clean'] ) && class_exists( 'ECT_Bricks_Styles', false ) ) {
				$row  = \ECT_Bricks_Styles::ect_bricks_clean_part( $item );
				$part = (string) ( $row['part'] ?? $part );
			}
			$icon = self::ect_bricks_part_uses_composite_inline_meta_icons( $part )
				? ''
				: self::ect_bricks_meta_icon_for_part( $part );

			if ( $icon !== '' ) {
				$html = self::ect_bricks_prepend_icon_to_part_wrap( $html, $icon );
			}

			$li_classes = ( 'style2' === $skin )
				? array( 'ect-card__meta-item' )
				: array( 'ect-meta-row' );
			if ( 'style2' !== $skin && $price ) {
				$li_classes[] = 'price';
			}
			if ( class_exists( 'ECT_Bricks_Part_Chrome', false ) ) {
				$hover = ECT_Bricks_Part_Chrome::ect_bricks_row_hover_state( $item );
				if ( $hover['has_fg'] ) {
					$li_classes[] = 'ect-has-hover-fg';
				}
				if ( $hover['has_bg'] ) {
					$li_classes[] = 'ect-has-hover-bg';
				}

				$meta_chrome = ECT_Bricks_Part_Chrome::ect_bricks_part_meta_li_chrome( $item );
				if ( $meta_chrome['classes'] !== array() ) {
					$li_classes = array_merge( $li_classes, $meta_chrome['classes'] );
				}
			}

			$li_attrs = ' class="' . esc_attr( implode( ' ', array_unique( $li_classes ) ) ) . '"';
			if ( class_exists( 'ECT_Bricks_Part_Chrome', false ) ) {
				$li_attrs .= ECT_Bricks_Part_Chrome::ect_bricks_part_hover_vars_attr( $item );
				if ( isset( $meta_chrome ) && $meta_chrome['style'] !== '' ) {
					$li_attrs .= $meta_chrome['style'];
				}
			}
			return '<li' . $li_attrs . '>' . $html . '</li>';
		}

		/**
		 * Style 1 meta lists: keep repeater drag order; only split adjacent primary vs price runs.
		 *
		 * @param \WP_Post                                        $post
		 * @param array<int,array{idx:int,item:array,price:bool}> $rows
		 * @param callable                                        $emit_li function( $post, $item, $idx, $is_price )
		 * @return void
		 */
		public static function ect_bricks_render_style1_meta_lists( $post, array $rows, callable $emit_li ) {
			if ( $rows === array() ) {
				return;
			}

			$segments = array();
			$current  = null;

			foreach ( $rows as $row ) {
				$is_price = ! empty( $row['price'] );
				if ( $current === null || $current['price'] !== $is_price ) {
					if ( $current !== null ) {
						$segments[] = $current;
					}
					$current = array(
						'price' => $is_price,
						'rows'  => array( $row ),
					);
					continue;
				}
				$current['rows'][] = $row;
			}

			if ( $current !== null ) {
				$segments[] = $current;
			}

			foreach ( $segments as $segment ) {
				$ul_class = 'event-meta event-meta--list';
				if ( $segment['price'] ) {
					$ul_class .= ' event-meta--list-price';
				}
				echo '<ul class="' . esc_attr( $ul_class ) . '">';
				foreach ( $segment['rows'] as $row ) {
					$emit_li( $post, $row['item'], $row['idx'], $segment['price'] );
				}
				echo '</ul>';
			}
		}

		public static function ect_bricks_render_meta_lists( $post, array $rows, callable $emit_li ) {
			if ( $rows === array() ) {
				return;
			}

			echo '<ul class="ect-card__meta">';
			foreach ( $rows as $row ) {
				$is_price = ! empty( $row['price'] );
				$emit_li( $post, $row['item'], $row['idx'], $is_price );
			}
			echo '</ul>';
		}

		/**
		 * @param array<int,array{idx:int,item:array,price:bool}> $rows
		 */
		public static function ect_bricks_flush_meta_rows( $post, array $rows, $layout, callable $emit_meta ) {
			if ( $rows === array() ) {
				return;
			}

			$emit_li = static function ( $ev, $item, $idx, $is_price ) use ( $emit_meta ) {
				$emit_meta( $ev, $item, $idx, (bool) $is_price );
			};

			if ( $layout === 'style1' ) {
				self::ect_bricks_render_style1_meta_lists( $post, $rows, $emit_li );
				return;
			}

			self::ect_bricks_render_meta_lists( $post, $rows, $emit_li );
		}

		public static function ect_bricks_shell_featured_image( $post, $layout, $link = true, $include_wrap = true ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$thumb_id = self::ect_bricks_event_thumbnail_id( $post->ID );

			static $map = array(
				'style1' => array(
					'wrap' => 'ect-list__img-wrap',
					'link' => 'ect-list__img-link',
					'img'  => 'ect-list__img',
				),
				'style2' => array(
					'wrap' => 'ect-card__img-wrap',
					'link' => 'ect-card__img-link',
					'img'  => 'ect-card__img',
				),
			);
			if ( ! isset( $map[ $layout ] ) ) {
				return '';
			}
			$cls = $map[ $layout ];

			$img = '';
			if ( $thumb_id > 0 ) {
				$img = wp_get_attachment_image(
					$thumb_id,
					'large',
					false,
					array(
						'class'    => $cls['img'],
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => wp_strip_all_tags( get_the_title( $post->ID ) ),
					)
				);
			}
			if ( ! is_string( $img ) || $img === '' ) {
				$img = self::ect_bricks_fallback_event_image_tag( $post, $cls['img'], 'large' );
			}
			if ( $img === '' ) {
				return $include_wrap ? '<div class="' . esc_attr( $cls['wrap'] ) . '"></div>' : '';
			}

			$url   = get_permalink( $post->ID );
			$inner = $link
				? '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $cls['link'] ) . '">' . $img . '</a>'
				: $img;
			return $include_wrap
				? '<div class="' . esc_attr( $cls['wrap'] ) . '">' . $inner . '</div>'
				: $inner;
		}

		/**
		 * Fallback <img> when wp_get_attachment_image() is unavailable.
		 *
		 * @param \WP_Post              $post         Event post.
		 * @param string                $img_class    Image class attribute.
		 * @param string                $size         Image size slug.
		 * @param array<string,string>  $extra_attrs  Extra attributes (e.g. style).
		 * @return string
		 */
		public static function ect_bricks_fallback_event_image_tag( $post, $img_class, $size = 'large', array $extra_attrs = array() ) {
			if ( ! $post instanceof \WP_Post || ! function_exists( 'ect_get_event_image' ) ) {
				return '';
			}

			$url = ect_get_event_image( $post->ID, $size );
			if ( ! is_string( $url ) || $url === '' ) {
				return '';
			}

			$class = trim( $img_class . ' ect-event-img--default' );
			$style = isset( $extra_attrs['style'] ) ? ' style="' . esc_attr( (string) $extra_attrs['style'] ) . '"' : '';

			return '<img src="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '" loading="lazy" decoding="async" alt="'
				. esc_attr( wp_strip_all_tags( get_the_title( $post->ID ) ) ) . '"' . $style . ' />';
		}

		public static function ect_bricks_shell_category_badge( $post, array $settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = self::ect_bricks_event_category_terms( $post->ID );
			if ( $terms === array() ) {
				return '';
			}
			$badge_decls = ECT_Bricks_Part_Chrome::ect_bricks_typography_style_decls_from_settings(
				$settings,
				'ect_bricks_shell_category_typography'
			);
			$links       = ECT_Bricks_Part_Chrome::ect_bricks_term_anchor_links( $terms, 'event-badge--blue', $badge_decls );
			if ( $links === array() ) {
				return '';
			}
			return '<div class="event-badge">' . implode( '', $links ) . '</div>';
		}

		/**
		 * Featured image attachment ID for an event (WP thumbnail + TEC fallback).
		 *
		 * @param int $post_id Event post ID.
		 * @return int Attachment ID or 0.
		 */
		public static function ect_bricks_event_thumbnail_id( $post_id ) {
			$post_id  = absint( $post_id );
			$thumb_id = (int) get_post_thumbnail_id( $post_id );
			if ( $thumb_id > 0 ) {
				return $thumb_id;
			}
			if ( function_exists( 'tribe_get_event' ) ) {
				$event = tribe_get_event( $post_id );
				if ( $event && ! empty( $event->thumbnail_id ) ) {
					return (int) $event->thumbnail_id;
				}
			}
			return 0;
		}

		public static function ect_bricks_event_category_terms( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return array();
			}

			static $cache = array();
			if ( array_key_exists( $post_id, $cache ) ) {
				return $cache[ $post_id ];
			}

			$by_id = self::ect_bricks_category_terms_from_taxonomies( $post_id );
			if ( $by_id === array() ) {
				$by_id = self::ect_bricks_category_terms_from_tribe_event( $post_id );
			}

			$cache[ $post_id ] = $by_id === array()
				? array()
				: wp_list_sort( array_values( $by_id ), 'name', 'ASC' );

			return $cache[ $post_id ];
		}

		/**
		 * Collect category terms from configured taxonomies.
		 *
		 * @param int $post_id Event post ID.
		 * @return array<int,\WP_Term> Terms keyed by term_id.
		 */
		private static function ect_bricks_category_terms_from_taxonomies( $post_id ) {
			$by_id      = array();
			$taxonomies = apply_filters( 'ect_bricks_event_category_taxonomies', array( 'tribe_events_cat' ), $post_id );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			foreach ( (array) $taxonomies as $taxonomy ) {
				if ( ! is_string( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}
				$raw = get_the_terms( $post_id, $taxonomy );
				if ( is_wp_error( $raw ) || ! is_array( $raw ) || $raw === array() ) {
					continue;
				}
				foreach ( $raw as $term ) {
					if ( $term instanceof \WP_Term ) {
						$by_id[ (int) $term->term_id ] = $term;
					}
				}
			}

			return $by_id;
		}

		/**
		 * Fallback category terms from tribe_get_event()->categories.
		 *
		 * @param int $post_id Event post ID.
		 * @return array<int,\WP_Term> Terms keyed by term_id.
		 */
		private static function ect_bricks_category_terms_from_tribe_event( $post_id ) {
			if ( ! function_exists( 'tribe_get_event' ) ) {
				return array();
			}

			$event = tribe_get_event( $post_id );
			if ( ! $event || empty( $event->categories ) || ! is_array( $event->categories ) ) {
				return array();
			}

			$by_id       = array();
			$pending_ids = array();

			foreach ( $event->categories as $term ) {
				if ( $term instanceof \WP_Term ) {
					$by_id[ (int) $term->term_id ] = $term;
					continue;
				}
				if ( ! is_object( $term ) || empty( $term->term_id ) ) {
					continue;
				}
				$term_taxonomy = ( ! empty( $term->taxonomy ) && is_string( $term->taxonomy ) )
					? $term->taxonomy
					: 'tribe_events_cat';
				if ( ! isset( $pending_ids[ $term_taxonomy ] ) ) {
					$pending_ids[ $term_taxonomy ] = array();
				}
				$pending_ids[ $term_taxonomy ][ (int) $term->term_id ] = true;
			}

			if ( $pending_ids !== array() ) {
				self::ect_bricks_resolve_pending_term_ids( $pending_ids, $by_id );
			}

			return $by_id;
		}

		/**
		 * Hydrate pending term IDs into WP_Term objects.
		 *
		 * @param array<string,array<int,bool>> $pending_ids Taxonomy => term_id => true.
		 * @param array<int,\WP_Term>            $by_id       Accumulator (by reference).
		 * @return void
		 */
		private static function ect_bricks_resolve_pending_term_ids( array $pending_ids, array &$by_id ) {
			foreach ( $pending_ids as $taxonomy => $ids ) {
				if ( ! is_string( $taxonomy ) || ! taxonomy_exists( $taxonomy ) || $ids === array() ) {
					continue;
				}
				$loaded_terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'include'    => array_keys( $ids ),
						'hide_empty' => false,
					)
				);
				if ( is_wp_error( $loaded_terms ) || ! is_array( $loaded_terms ) ) {
					continue;
				}
				foreach ( $loaded_terms as $loaded ) {
					if ( $loaded instanceof \WP_Term ) {
						$by_id[ (int) $loaded->term_id ] = $loaded;
					}
				}
			}
		}

		/**
		 * Event tag terms (request-cached).
		 *
		 * @param int $post_id Event post ID.
		 * @return \WP_Term[]
		 */
		public static function ect_bricks_event_tag_terms( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return array();
			}

			static $cache = array();
			if ( array_key_exists( $post_id, $cache ) ) {
				return $cache[ $post_id ];
			}

			$raw = get_the_terms( $post_id, 'post_tag' );
			if ( is_wp_error( $raw ) || ! is_array( $raw ) || $raw === array() ) {
				$cache[ $post_id ] = array();
				return array();
			}

			$terms = array();
			foreach ( $raw as $term ) {
				if ( $term instanceof \WP_Term ) {
					$terms[] = $term;
				}
			}

			$cache[ $post_id ] = wp_list_sort( $terms, 'name', 'ASC' );
			return $cache[ $post_id ];
		}

		/**
		 * Plain-text event description from full post content.
		 *
		 * @param \WP_Post $post Event post.
		 * @return string
		 */
		public static function ect_bricks_description_plain_text( $post ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$post_id      = (int) $post->ID;
			static $cache = array();
			if ( $post_id > 0 && array_key_exists( $post_id, $cache ) ) {
				return $cache[ $post_id ];
			}

			$raw = (string) $post->post_content;
			if ( $raw === '' ) {
				if ( $post_id > 0 ) {
					$cache[ $post_id ] = '';
				}
				return '';
			}

			$html  = has_blocks( $raw ) ? do_blocks( $raw ) : wpautop( $raw );
			$html  = do_shortcode( $html );
			$plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );

			if ( $post_id > 0 ) {
				$cache[ $post_id ] = $plain;
			}

			return $plain;
		}

		public static function ect_bricks_layout_surface_class( $part, $skin ) {
			$part       = (string) $part;
			$skin       = (string) $skin;
			static $map = array(
				'style1' => array(
					'title'       => 'ect-list__title',
					'description' => 'ect-list__desc',
				),
				'style2' => array(
					'title'       => 'ect-card__title',
					'description' => 'ect-card__desc',
					'categories'  => 'ect-card__cat',
				),
			);
			return isset( $map[ $skin ][ $part ] ) ? $map[ $skin ][ $part ] : '';
		}

		public static function ect_bricks_shell_skip_part( $slug ) {
			return in_array( (string) $slug, array( 'read_more', 'event_date', 'event_day' ), true );
		}

		/**
		 * Base meta-list part slugs (excluding meta combo permutations).
		 *
		 * @return string[]
		 */
		private static function ect_bricks_base_meta_row_part_slugs() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_base_meta_row_part_slugs()
				: array();
		}

		/**
		 * Part slugs that render inside layout meta lists.
		 *
		 * @return string[]
		 */
		public static function ect_bricks_meta_row_part_slugs() {
			static $slugs = null;
			if ( is_array( $slugs ) ) {
				return $slugs;
			}

			$slugs = array_merge(
				self::ect_bricks_base_meta_row_part_slugs(),
				ECT_Bricks_Settings_Normalizer::ect_bricks_meta_combo_slugs_or_fallback()
			);

			return $slugs;
		}

		/**
		 * @param string $ui_part   UI part slug from repeater row.
		 * @param string $clean_slug Cleaned part slug.
		 * @return bool
		 */
		private static function ect_bricks_meta_row_is_price( $ui_part, $clean_slug ) {
			return ! self::ect_bricks_part_uses_composite_inline_meta_icons( (string) $ui_part )
				&& ! self::ect_bricks_part_uses_composite_inline_meta_icons( (string) $clean_slug )
				&& ( (string) $ui_part === 'event_cost' || (string) $clean_slug === 'event_cost' );
		}

		/**
		 * Whether a repeater row should render inside a layout meta list.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return array{slug:string,item:array<string,mixed>}|null
		 */
		private static function ect_bricks_resolve_layout_meta_row( array $item ) {
			$ui = (string) ( $item['part'] ?? '' );
			if ( in_array( $ui, array( 'title', 'description', 'read_more', 'image', 'categories' ), true ) ) {
				return null;
			}

			$row   = class_exists( 'ECT_Bricks_Styles', false ) ? \ECT_Bricks_Styles::ect_bricks_clean_part( $item ) : $item;
			$slug  = (string) ( $row['part'] ?? $ui );
			$slugs = self::ect_bricks_meta_row_part_slugs();

			if ( ! in_array( $slug, $slugs, true ) && ! in_array( $ui, $slugs, true ) ) {
				return null;
			}

			return array(
				'slug' => $slug,
				'item' => $row,
			);
		}

		public static function ect_bricks_meta_icon( $type ) {
			$type        = (string) $type;
			static $svgs = array(
				'clock' => '<path d="M12 7v5l3.4 2.2" /><circle cx="12" cy="12" r="8" />',
				'pin'   => '<path d="M12 21s6-5.1 6-11a6 6 0 0 0-12 0c0 5.9 6 11 6 11Z" /><circle cx="12" cy="10" r="2.4" />',
				'cost'  => '<path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v1.2a2.5 2.5 0 0 0-.9 4.8V15a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-1.2a2.5 2.5 0 0 0-.9-4.8V9Z" /><path d="M13 5v14" />',
				'user'  => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />',
				'tag'   => '<g transform="translate(12 12) scale(0.82) translate(-12 -12)"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0l-7.17-7.17a2 2 0 0 1-.59-1.41V6a2 2 0 0 1 2-2h7.17a2 2 0 0 1 1.41.59l7.17 7.17a2 2 0 0 1 0 2.82Z" /><circle cx="7" cy="7" r="1" /></g>',
			);
			if ( ! in_array( $type, array( 'clock', 'pin', 'cost', 'user', 'tag' ), true ) ) {
				$type = 'clock';
			}
			$inner = $svgs[ $type ];
			return '<span class="ect-card__meta-icon" aria-hidden="true"><svg viewBox="0 0 24 24">' . $inner . '</svg></span>';
		}

		public static function ect_bricks_meta_icon_for_part( $slug ) {
			$slug = (string) $slug;
			if ( self::ect_bricks_part_uses_cost_icon( $slug ) ) {
				return self::ect_bricks_meta_icon( 'cost' );
			}
			if ( self::ect_bricks_part_uses_organizer_icon( $slug ) ) {
				return self::ect_bricks_meta_icon( 'user' );
			}
			if ( self::ect_bricks_part_uses_tag_icon( $slug ) ) {
				return self::ect_bricks_meta_icon( 'tag' );
			}
			if ( self::ect_bricks_part_uses_venue_icon( $slug ) ) {
				return self::ect_bricks_meta_icon( 'pin' );
			}
			return self::ect_bricks_meta_icon( 'clock' );
		}

		/**
		 * @param string $slug Part slug.
		 * @return bool
		 */
		private static function ect_bricks_part_uses_cost_icon( $slug ) {
			return ECT_Bricks_Settings_Normalizer::ect_bricks_part_has_cost_segment( $slug );
		}

		/**
		 * @param string $slug Part slug.
		 * @return bool
		 */
		private static function ect_bricks_part_uses_venue_icon( $slug ) {
			return ECT_Bricks_Settings_Normalizer::ect_bricks_part_has_venue_segment( $slug );
		}

		/**
		 * @param string $slug Part slug.
		 * @return bool
		 */
		private static function ect_bricks_part_uses_organizer_icon( $slug ) {
			return ECT_Bricks_Settings_Normalizer::ect_bricks_part_has_organizer_segment( $slug );
		}

		/**
		 * @param string $slug Part slug.
		 * @return bool
		 */
		private static function ect_bricks_part_uses_tag_icon( $slug ) {
			return (string) $slug === 'tags';
		}

		/**
		 * Place a meta icon inside the part wrapper so typography / background /
		 * spacing from the repeater row apply to icon + text as one unit.
		 *
		 * @param string $html Part markup beginning with the wrapper element.
		 * @param string $icon Meta icon HTML.
		 * @return string
		 */
		private static function ect_bricks_prepend_icon_to_part_wrap( $html, $icon ) {
			$html = (string) $html;
			$icon = (string) $icon;
			if ( $html === '' || $icon === '' ) {
				return $html;
			}

			// Match the wrapper open tag via quoted attributes (not the first ">"), since
			// part shells are built in ect_bricks_wrap_part() with esc_attr() values.
			if ( preg_match( '/^<[a-z][a-z0-9]*\b(?:\s[-a-z0-9:]+="[^"]*")*>/i', $html, $matches ) ) {
				$open_tag = $matches[0];
				return $open_tag . $icon . substr( $html, strlen( $open_tag ) );
			}

			return $icon . $html;
		}

		/**
		 * Composite meta rows render per-segment icons inside the part wrapper.
		 *
		 * @param string $part_slug Cleaned part slug.
		 * @return bool
		 */
		private static function ect_bricks_part_uses_composite_inline_meta_icons( $part_slug ) {
			return class_exists( 'ECT_Bricks_Styles', false )
				? \ECT_Bricks_Styles::ect_bricks_is_meta_combo_slug( (string) $part_slug )
				: in_array( (string) $part_slug, ECT_Bricks_Settings_Normalizer::ect_bricks_meta_combo_slugs_or_fallback(), true );
		}
	}
}
