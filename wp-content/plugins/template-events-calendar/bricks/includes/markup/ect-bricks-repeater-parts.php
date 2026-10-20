<?php
/**
 * HTML output for event repeater parts.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Part_Renderer', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Part_Renderer {

		/** Cast idx/skin and build wrapper attrs + class list for a part row. */
		private static function ect_bricks_part_shell( $part, array $item, $idx, $skin ) {
			$idx  = absint( $idx );
			$skin = (string) $skin;
			return array(
				'attr' => ECT_Bricks_Part_Chrome::ect_bricks_part_wrap_attrs( $item, $idx ),
				'wrap' => esc_attr( ECT_Bricks_Part_Chrome::ect_bricks_part_classes( $part, $idx, $skin, $item ) ),
			);
		}

		/** Wrap part inner markup in the standard repeater row div. */
		private static function ect_bricks_wrap_part( array $shell, $inner, $escape = true ) {
			if ( $escape ) {
				$content = '<span class="ect-fld__plain">' . esc_html( (string) $inner ) . '</span>';
			} else {
				$content = (string) $inner;
			}

			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . $content . '</div>';
		}

		/** Shared renderer for button-style action parts (read more). */
		private static function ect_bricks_render_action_part( $post, array $item, $idx, $skin, $part_slug, $label_key, $default_label, $url, array $attrs = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$url = is_string( $url ) ? trim( $url ) : '';
			if ( $url === '' ) {
				return '';
			}

			$label = isset( $item[ $label_key ] ) ? trim( (string) $item[ $label_key ] ) : '';
			$label = $label === '' ? $default_label : sanitize_text_field( $label );

			$shell    = self::ect_bricks_part_shell( $part_slug, $item, $idx, $skin );
			$inner_el = ECT_Bricks_Part_Chrome::ect_bricks_action_link_html( $item, $url, $label, $attrs );

			return self::ect_bricks_wrap_part( $shell, $inner_el, false );
		}

		/** Venue repeater row markup. */
		public static function ect_bricks_render_venue( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$shell = self::ect_bricks_part_shell( 'venue', $item, $idx, $skin );
			if ( ECT_Bricks_Event_Data::ect_bricks_venue_display_key( $item, $skin ) === 'website' ) {
				$url   = ECT_Bricks_Event_Data::ect_bricks_part_detail_text( $post->ID, 'venue_website' );
				$inner = self::ect_bricks_website_link_html( $url, $item, 'venue_website' );
				if ( $inner === '' ) {
					return '';
				}
				return self::ect_bricks_wrap_part( $shell, $inner, false );
			}

			$text = ECT_Bricks_Event_Data::ect_bricks_venue_text( $post->ID, $item, $skin );
			if ( $text === '' ) {
				return '';
			}
			return self::ect_bricks_wrap_part( $shell, $text );
		}

		/** Organizer repeater row markup. */
		public static function ect_bricks_render_organizer( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$text = ECT_Bricks_Event_Data::ect_bricks_organizer_text( $post->ID, $item );
			if ( $text === '' ) {
				return '';
			}
			$shell = self::ect_bricks_part_shell( 'organizer', $item, $idx, $skin );
			return self::ect_bricks_wrap_part( $shell, $text );
		}

		/** Featured image (single size; hover via premade animation). */
		public static function ect_bricks_render_featured_img( $thumb_id, array $item, $post = null ) {
			$thumb_id  = (int) $thumb_id;
			$size_base = ECT_Bricks_Part_Chrome::ect_bricks_sanitize_image_size( $item['image_size'] ?? '' );
			$img_attrs = array( 'class' => 'ect-fld__img' );
			$img_style = ECT_Bricks_Part_Chrome::ect_bricks_part_image_img_style_decls( $item );
			if ( $img_style !== array() ) {
				$img_attrs['style'] = implode( ';', $img_style ) . ';';
			}

			if ( $thumb_id > 0 ) {
				$html = wp_get_attachment_image( $thumb_id, $size_base, false, $img_attrs );
				if ( is_string( $html ) && $html !== '' ) {
					return $html;
				}
			}

			if ( $post instanceof \WP_Post && class_exists( 'ECT_Bricks_Layout_Shell', false ) ) {
				$extra = $img_attrs;
				unset( $extra['class'] );
				return \ECT_Bricks_Layout_Shell::ect_bricks_fallback_event_image_tag(
					$post,
					'ect-fld__img',
					$size_base,
					$extra
				);
			}

			return '';
		}

		/** Detail-field part slugs handled by ect_bricks_render_part_detail(). */
		private static function detail_slugs() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_detail_part_slugs()
				: array();
		}

		/** Part slug → render handler for ect_bricks_render_part_ext(). */
		private static function ext_dispatch() {
			static $map = null;
			if ( is_array( $map ) ) {
				return $map;
			}
			$map = array(
				'venue'         => array( self::class, 'ect_bricks_render_venue' ),
				'organizer'     => array( self::class, 'ect_bricks_render_organizer' ),
				'date'          => array( self::class, 'ect_bricks_render_part_date' ),
				'event_date'    => array( self::class, 'ect_bricks_render_part_event_date' ),
				'event_time'    => array( self::class, 'ect_bricks_render_part_event_time' ),
				'event_day'     => array( self::class, 'ect_bricks_render_part_event_day' ),
				'event_cost'    => array( self::class, 'ect_bricks_render_part_event_cost' ),
				'read_more'     => array( self::class, 'ect_bricks_render_part_read_more' ),
				'image'         => array( self::class, 'ect_bricks_render_part_image' ),
				'categories'    => array( self::class, 'ect_bricks_render_part_categories' ),
				'tags'          => array( self::class, 'ect_bricks_render_part_tags' ),
				'description'   => array( self::class, 'ect_bricks_render_part_description' ),
				'title'         => array( self::class, 'ect_bricks_render_part_title' ),
			);
			foreach ( ECT_Bricks_Settings_Normalizer::ect_bricks_meta_combo_slugs_or_fallback() as $combo_slug ) {
				$map[ $combo_slug ] = array( self::class, 'ect_bricks_render_meta_combo' );
			}
			$detail = array( self::class, 'ect_bricks_render_part_detail' );
			foreach ( self::detail_slugs() as $slug ) {
				$map[ $slug ] = $detail;
			}
			return $map;
		}

		/** Combined date part (day, time, day+time, or start–end range). */
		public static function ect_bricks_render_part_date( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			$fmt = isset( $item['date_display'] ) ? (string) $item['date_display'] : 'day_time_range';
			if ( $fmt === 'range' ) {
				return ECT_Bricks_Layout_Shell::ect_bricks_render_date_range( $post, $item, $idx, $skin );
			}

			$html = ECT_Bricks_Date_Formatter::ect_bricks_part_visibility_plain_text( $post->ID, $item );
			if ( $html === '' ) {
				return '';
			}

			$shell = self::ect_bricks_part_shell( 'date', $item, $idx, $skin );

			return self::ect_bricks_wrap_part( $shell, $html );
		}

		public static function ect_bricks_render_part_event_date( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			$shell     = self::ect_bricks_part_shell( 'event_date', $item, $idx, $skin );
			$date_item = array_merge( $item, array( 'date_display' => 'date' ) );
			$html      = ECT_Bricks_Date_Formatter::ect_bricks_part_visibility_plain_text( $post->ID, $date_item );
			if ( $html === '' ) {
				return '';
			}
			return self::ect_bricks_wrap_part( $shell, $html );
		}

		public static function ect_bricks_render_part_event_time( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			$shell = self::ect_bricks_part_shell( 'event_time', $item, $idx, $skin );
			$html  = self::ect_bricks_part_time_plain_text( $post, $item );
			if ( $html === '' ) {
				return '';
			}
			return self::ect_bricks_wrap_part( $shell, $html );
		}

		public static function ect_bricks_render_part_event_day( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			$shell = self::ect_bricks_part_shell( 'event_day', $item, $idx, $skin );
			$pr    = ECT_Bricks_Date_Formatter::ect_bricks_build_day_time_parts( $post->ID );
			$html  = isset( $pr['day'] ) ? trim( (string) $pr['day'] ) : '';

			if ( $html === '' ) {
				$raw  = ECT_Bricks_Event_Data::ect_bricks_event_start_date_raw( $post->ID );
				$ts   = $raw ? strtotime( $raw ) : false;
				$html = $ts ? trim( wp_strip_all_tags( date_i18n( 'l', $ts ) ) ) : '';
			}
			if ( $html === '' ) {
				return '';
			}
			return self::ect_bricks_wrap_part( $shell, $html );
		}

		/** Venue/organizer/event detail field with links where appropriate. */
		public static function ect_bricks_render_part_detail( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' || ! in_array( $part, self::detail_slugs(), true ) ) {
				return '';
			}
			$shell = self::ect_bricks_part_shell( $part, $item, $idx, $skin );
			$html  = ECT_Bricks_Event_Data::ect_bricks_part_detail_text( $post->ID, $part );

			if ( $part === 'organizer_email' ) {
				if ( $html === '' || ! is_email( $html ) ) {
					return '';
				}
				return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '><a class="ect-fld__link" href="' . esc_url( 'mailto:' . $html ) . '">' . esc_html( $html ) . '</a></div>';
			}

			$url_parts = array( 'venue_website', 'organizer_website' );
			if ( in_array( $part, $url_parts, true ) ) {
				return self::ect_bricks_render_website_detail( $shell, $html, $item, $part );
			}

			if ( $html === '' ) {
				return '';
			}

			return self::ect_bricks_wrap_part( $shell, $html );
		}

		private static function ect_bricks_render_website_detail( array $shell, $url, array $item, $part ) {
			$inner = self::ect_bricks_website_link_html( $url, $item, (string) $part );
			if ( $inner === '' ) {
				return '';
			}

			return '<div class="' . $shell['wrap'] . '"' . $shell['attr'] . '>' . $inner . '</div>';
		}

		/** Venue/organizer website anchor markup. */
		private static function ect_bricks_website_link_html( $url, array $item, $part ) {
			$url = trim( (string) $url );
			if ( $url === '' ) {
				return '';
			}
			if ( ! preg_match( '#^https?://#i', $url ) ) {
				$url = 'https://' . $url;
			}
			$href = esc_url( $url );
			if ( $href === '' ) {
				return '';
			}

			$key    = ( $part === 'organizer_website' ) ? 'organizer_website_link_text' : 'website_link_text';
			$custom = trim( (string) ( $item[ $key ] ?? $item['website_link_text'] ?? '' ) );
			$label  = $custom !== '' ? $custom : $url;

			return '<a class="ect-fld__link" href="' . $href . '" rel="noopener noreferrer" target="_blank">'
				. esc_html( $label ) . '</a>';
		}

		public static function ect_bricks_render_part_event_cost( $post, array $item, $idx, $skin = '', array $widget_settings = array() ) {
			$cost = ECT_Bricks_Cost_Formatter::ect_bricks_layout_cost_label( $post->ID, $item, $widget_settings );
			if ( $cost === '' ) {
				return '';
			}
			$shell = self::ect_bricks_part_shell( 'event_cost', $item, $idx, $skin );
			return self::ect_bricks_wrap_part( $shell, $cost );
		}

		/** Plain event time string (standalone rows and meta-combo time segments). */
		private static function ect_bricks_part_time_plain_text( $post, array $item ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$time_item = $item;
			if ( ! isset( $time_item['date_display'] ) || (string) $time_item['date_display'] === '' ) {
				$time_item['date_display'] = 'time';
			}

			return ECT_Bricks_Date_Formatter::ect_bricks_part_visibility_plain_text( $post->ID, $time_item );
		}

		/**
		 * Meta icon for a composite segment.
		 *
		 * @param string $type  Icon key.
		 * @param bool   $boxed Use full Style 2 meta icon chrome (not inline).
		 */
		private static function ect_bricks_composite_segment_icon( $type, $boxed = false ) {
			if ( ! class_exists( 'ECT_Bricks_Layout_Shell', false ) ) {
				return '';
			}
			$icon = ECT_Bricks_Layout_Shell::ect_bricks_meta_icon( (string) $type );
			if ( $icon === '' ) {
				return '';
			}
			if ( $boxed ) {
				return $icon;
			}

			return str_replace(
				'class="ect-card__meta-icon"',
				'class="ect-card__meta-icon ect-card__meta-icon--inline"',
				$icon
			);
		}

		/**
		 * @param array<int,array{class:string,text:string,icon?:string}> $segments
		 * @param string                                                  $skin     Layout skin (style1|style2).
		 */
		private static function ect_bricks_render_composite_meta_segments( array $segments, $skin = '' ) {
			$boxed_icons = ( (string) $skin === 'style2' );
			$chunks      = array();
			foreach ( $segments as $segment ) {
				$text = isset( $segment['text'] ) ? trim( (string) $segment['text'] ) : '';
				$html = isset( $segment['html'] ) ? trim( (string) $segment['html'] ) : '';
				if ( $text === '' && $html === '' ) {
					continue;
				}
				$class    = isset( $segment['class'] ) ? (string) $segment['class'] : 'ect-fld__meta-segment';
				$icon     = isset( $segment['icon'] ) ? self::ect_bricks_composite_segment_icon( (string) $segment['icon'], $boxed_icons ) : '';
				$chunks[] = '<span class="' . esc_attr( $class ) . ' ect-fld__meta-segment">'
					. $icon
					. '<span class="ect-fld__meta-text">' . ( $html !== '' ? $html : esc_html( $text ) ) . '</span>'
					. '</span>';
			}

			if ( $chunks === array() ) {
				return '';
			}

			return '<span class="ect-fld__meta-group">' . implode( '', $chunks ) . '</span>';
		}

		/** Composite meta row: venue, time, and/or cost in configurable order. */
		public static function ect_bricks_render_meta_combo( $post, array $item, $idx, $skin = '', array $widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$part_slug = isset( $item['part'] ) ? (string) $item['part'] : '';
			$order     = class_exists( 'ECT_Bricks_Styles', false )
				? \ECT_Bricks_Styles::ect_bricks_meta_combo_order( $part_slug )
				: array();
			if ( $order === array() ) {
				return '';
			}

			$venue_is_website = ECT_Bricks_Event_Data::ect_bricks_venue_display_key( $item, $skin ) === 'website';
			$venue_url        = $venue_is_website
				? ECT_Bricks_Event_Data::ect_bricks_part_detail_text( $post->ID, 'venue_website' )
				: '';
			$segment_map      = array(
				'venue' => array(
					'class' => 'ect-fld__meta-venue',
					'icon'  => 'pin',
					'text'  => $venue_is_website ? '' : ECT_Bricks_Event_Data::ect_bricks_venue_text( $post->ID, $item, $skin ),
					'html'  => $venue_is_website ? self::ect_bricks_website_link_html( $venue_url, $item, 'venue_website' ) : '',
				),
				'time'  => array(
					'class' => 'ect-fld__meta-time',
					'icon'  => 'clock',
					'text'  => self::ect_bricks_part_time_plain_text( $post, $item ),
				),
				'cost'  => array(
					'class' => 'ect-fld__meta-cost',
					'icon'  => 'cost',
					'text'  => ECT_Bricks_Cost_Formatter::ect_bricks_layout_cost_label( $post->ID, $item, $widget_settings ),
				),
			);

			$segments = array();
			foreach ( $order as $seg ) {
				if ( ! isset( $segment_map[ $seg ] ) ) {
					continue;
				}
				$segments[] = $segment_map[ $seg ];
			}

			$inner = self::ect_bricks_render_composite_meta_segments( $segments, $skin );
			if ( $inner === '' ) {
				return '';
			}

			$shell = self::ect_bricks_part_shell( $part_slug, $item, $idx, $skin );
			return self::ect_bricks_wrap_part( $shell, $inner, false );
		}

		public static function ect_bricks_render_part_read_more( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			return self::ect_bricks_render_action_part(
				$post,
				$item,
				$idx,
				$skin,
				'read_more',
				'read_more_text',
				__( 'View Details', 'template-events-calendar' ),
				get_permalink( $post->ID )
			);
		}

		/**
		 * Open a part wrapper with an allow-listed tag.
		 *
		 * @param string              $tag   div|h3|p
		 * @param string              $class Class attribute value (unescaped).
		 * @param array<string,mixed> $item  Repeater row.
		 * @param int                 $idx   Row index.
		 * @return string Opening tag HTML.
		 */
		private static function ect_bricks_part_open_tag( $tag, $class, array $item, $idx ) {
			$tag = in_array( $tag, array( 'div', 'h3', 'p' ), true ) ? $tag : 'div';
			return '<' . tag_escape( $tag ) . ' class="' . esc_attr( $class ) . '"'
				. ECT_Bricks_Part_Chrome::ect_bricks_part_wrap_attrs( $item, $idx ) . '>';
		}

		/** Featured image repeater row. */
		public static function ect_bricks_render_part_image( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$thumb_id = ECT_Bricks_Layout_Shell::ect_bricks_event_thumbnail_id( $post->ID );
			$image_html = self::ect_bricks_render_featured_img( $thumb_id, $item, $post );
			if ( $image_html === '' ) {
				return '';
			}

			$shell = self::ect_bricks_part_shell( 'image', $item, $idx, $skin );
			$link  = isset( $item['image_link'] ) ? (bool) $item['image_link'] : true;
			$inner = $link
				? '<a class="ect-fld__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">' . $image_html . '</a>'
				: $image_html;

			return self::ect_bricks_wrap_part( $shell, $inner, false );
		}

		/** Category terms repeater row. */
		public static function ect_bricks_render_part_categories( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			return self::ect_bricks_render_part_taxonomy( $post, $item, $idx, $skin, 'tribe_events_cat', 'categories' );
		}

		/** Tag terms repeater row. */
		public static function ect_bricks_render_part_tags( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			return self::ect_bricks_render_part_taxonomy( $post, $item, $idx, $skin, 'post_tag', 'tags' );
		}

		/**
		 * @param \WP_Post            $post
		 * @param array<string,mixed> $item
		 * @param int                 $idx
		 * @param string              $skin
		 * @param string              $taxonomy
		 * @param string              $part_key
		 * @return string
		 */
		private static function ect_bricks_render_part_taxonomy( $post, array $item, $idx, $skin, $taxonomy, $part_key ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}
			$terms = ( $taxonomy === 'tribe_events_cat' )
				? ECT_Bricks_Layout_Shell::ect_bricks_event_category_terms( $post->ID )
				: ( $taxonomy === 'post_tag'
					? ECT_Bricks_Layout_Shell::ect_bricks_event_tag_terms( $post->ID )
					: get_the_terms( $post->ID, $taxonomy ) );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return '';
			}

			$inner = ECT_Bricks_Part_Chrome::ect_bricks_terms_html( $terms, $item, $skin, $part_key );
			if ( $inner === '' ) {
				return '';
			}

			$shell = self::ect_bricks_part_shell( $part_key, $item, $idx, $skin );
			return self::ect_bricks_wrap_part( $shell, $inner, false );
		}

		/** Description repeater row. */
		public static function ect_bricks_render_part_description( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$layout_skin = (string) $skin !== '' ? (string) $skin : 'style1';
			$len_mode    = isset( $item['desc_length'] ) ? (string) $item['desc_length'] : 'short';
			$len_mode    = in_array( $len_mode, array( 'short', 'full', 'custom' ), true ) ? $len_mode : 'short';
			$words       = isset( $item['desc_words'] ) ? max( 5, (int) $item['desc_words'] ) : 55;

			$plain = ECT_Bricks_Layout_Shell::ect_bricks_description_plain_text( $post );
			if ( $plain === '' ) {
				return '';
			}

			if ( $len_mode === 'full' ) {
				$content = wpautop( $plain );
			} else {
				$limit   = $len_mode === 'custom' ? $words : 55;
				$content = wpautop( wp_trim_words( $plain, $limit ) );
			}

			$surface = trim( ECT_Bricks_Layout_Shell::ect_bricks_layout_surface_class( 'description', $layout_skin ) );
			$tag     = $surface !== '' ? 'div' : 'p';
			$wrap    = ECT_Bricks_Part_Chrome::ect_bricks_part_classes( 'description', absint( $idx ), $layout_skin, $item );
			$classes = trim( $wrap . ( $surface !== '' ? ' ' . $surface : '' ) );

			return self::ect_bricks_part_open_tag( $tag, $classes, $item, $idx )
				. wp_kses_post( $content )
				. '</' . tag_escape( $tag ) . '>';
		}

		/** Title repeater row (also used as fallback for unknown part slugs). */
		public static function ect_bricks_render_part_title( $post, array $item, $idx, $skin = '', array $_widget_settings = array() ) {
			if ( ! $post instanceof \WP_Post ) {
				return '';
			}

			$layout_skin = (string) $skin;
			$wrap        = ECT_Bricks_Part_Chrome::ect_bricks_part_classes( 'title', absint( $idx ), $layout_skin, $item );
			$surface     = trim( ECT_Bricks_Layout_Shell::ect_bricks_layout_surface_class( 'title', $layout_skin ) );
			$classes     = trim( $wrap . ( $surface !== '' ? ' ' . $surface : '' ) );
			$link        = ECT_Bricks_Part_Chrome::ect_bricks_title_link_active( $item );
			$inner       = $link
				? '<a class="ect-fld__link" href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( get_the_title( $post->ID ) ) . '</a>'
				: '<span class="ect-fld__title-text">' . esc_html( get_the_title( $post->ID ) ) . '</span>';

			return self::ect_bricks_part_open_tag( 'h3', $classes, $item, $idx ) . $inner . '</h3>';
		}

		/** Dispatch extended part slug to its renderer; unknown slugs render as title. */
		public static function ect_bricks_render_part_ext( $post, array $item, $idx, $skin = '', array $widget_settings = array() ) {
			if ( class_exists( 'ECT_Bricks_Styles', false ) && empty( $item['_ect_bricks_clean'] ) ) {
				$item = \ECT_Bricks_Styles::ect_bricks_clean_part( $item );
			}
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part === '' ) {
				return false;
			}
			$handlers = self::ext_dispatch();
			$handler  = $handlers[ $part ] ?? array( self::class, 'ect_bricks_render_part_title' );
			return call_user_func( $handler, $post, $item, $idx, $skin, $widget_settings );
		}
	}
}
