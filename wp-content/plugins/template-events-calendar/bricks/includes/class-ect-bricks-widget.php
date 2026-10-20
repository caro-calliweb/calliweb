<?php
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace ECT\Bricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECT_Bricks_Widget extends \Bricks\Element {


	public $category = 'general';
	public $name     = 'ect-bricks-events-loop';
	public $icon     = 'ect-bricks-eicon-events-widget';
	public $nestable = false;

	public function get_label() {
		return esc_html__( 'Events Widget', 'template-events-calendar' );
	}

	public function get_keywords() {
		return array( 'event', 'events', 'loop', 'query', 'tec', 'tribe', 'widget', 'calendar' );
	}

	public function set_control_groups() {
		// Order: Layouts → Events Query → Elements → Dynamic Messages → Pagination.
		$this->control_groups['layouts'] = array(
			'title' => esc_html__( 'Layouts', 'template-events-calendar' ),
			'tab'   => 'content',
		);

		$this->control_groups['event_query'] = array(
			'title' => esc_html__( 'Events Query', 'template-events-calendar' ),
			'tab'   => 'content',
		);

		$this->control_groups['elements'] = array(
			'title' => esc_html__( 'Elements', 'template-events-calendar' ),
			'tab'   => 'content',
		);

		$this->control_groups['dynamic_messages'] = array(
			'title' => esc_html__( 'Dynamic Messages', 'template-events-calendar' ),
			'tab'   => 'content',
		);

		$this->control_groups['pagination'] = array(
			'title' => esc_html__( 'Pagination', 'template-events-calendar' ),
			'tab'   => 'content',
		);

		$this->control_groups['events_card'] = array(
			'title' => esc_html__( 'Events Cards', 'template-events-calendar' ),
			'tab'   => 'style',
		);

		$this->control_groups['style1_date'] = array(
			'title'    => esc_html__( 'Date', 'template-events-calendar' ),
			'tab'      => 'style',
			// Style 1 date column only — hide the whole group for Style 2.
			'required' => array(
				array( 'layout_template', '=', array( 'list', '' ) ),
				array( 'list_item_style', '=', array( 'style-1', '' ) ),
				array( 'list1_show_date_column', '!=', true ),
			),
		);

		$this->control_groups['featured_image'] = array(
			'title' => esc_html__( 'Featured image', 'template-events-calendar' ),
			'tab'   => 'style',
		);
	}

	/**
	 * Empty-state copy (Dynamic Messages → No events found).
	 *
	 * @return string
	 */
	private function ect_bricks_get_no_events_message() {
		$text = isset( $this->settings['no_events_text'] ) ? trim( (string) $this->settings['no_events_text'] ) : '';
		if ( $text === '' ) {
			return __( 'No events found', 'template-events-calendar' );
		}
		return $text;
	}

	/**
	 * Markup when the query returns zero events (front end + builder).
	 *
	 * @return void
	 */
	private function ect_bricks_render_no_events_message() {
		$tag = isset( $this->settings['no_events_tag'] ) ? (string) $this->settings['no_events_tag'] : 'h2';
		$tag = in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ), true ) ? $tag : 'h2';

		echo '<div class="ect-ev__empty" role="status">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag is allow-listed.
		echo '<' . tag_escape( $tag ) . ' class="ect-ev__empty-message">' . esc_html( $this->ect_bricks_get_no_events_message() ) . '</' . tag_escape( $tag ) . '>';
		echo '</div>';
	}

	/**
	 * Resolve the active list style from element settings.
	 *
	 * @return string style-1|style-2
	 */
	private function ect_bricks_get_list_item_style() {
		$settings = is_array( $this->settings ) ? $this->settings : array();
		return \ECT_Bricks_Plugin::ect_bricks_sanitize_list_style( $settings['list_item_style'] ?? 'style-1' );
	}

	/**
	 * Load helpers for the current path (render stack, or builder-only for controls).
	 *
	 * @param string|null $list_item_style style-1|style-2|null (null = all layouts, for controls)
	 * @param bool        $builder_only    When true, skip query/markup and load styles+layouts+controls.
	 * @return void
	 */
	private function ect_bricks_ensure_widget_dependencies( $list_item_style = null, $builder_only = false ) {
		if ( ! class_exists( '\ECT_Bricks_Plugin', false ) ) {
			return;
		}
		if ( $builder_only ) {
			\ECT_Bricks_Plugin::ect_bricks_load_builder_dependencies();
			return;
		}
		\ECT_Bricks_Plugin::ect_bricks_load_render_dependencies();
		\ECT_Bricks_Plugin::ect_bricks_load_layouts( $list_item_style );
	}

	/**
	 * Ensure render helpers are loaded before calling ECT_Bricks_Markup methods.
	 *
	 * @return bool
	 */
	private function ect_bricks_has_markup_dependencies() {
		$this->ect_bricks_ensure_widget_dependencies( $this->ect_bricks_get_list_item_style() );
		return class_exists( 'ECT_Bricks_Markup', false );
	}

	/**
	 * Builder-only placeholder when core render helpers are unavailable.
	 *
	 * @return void
	 */
	private function ect_bricks_render_missing_dependency_notice() {
		if ( \Bricks\Capabilities::current_user_can_use_builder() ) {
			echo '<div class="ect-placeholder">' . esc_html__( 'Events Widget dependencies could not be loaded.', 'template-events-calendar' ) . '</div>';
		}
	}

	/**
	 * Whether featured-image shell CSS should load for this element.
	 *
	 * @return bool
	 */
	private function ect_bricks_needs_featured_image_styles() {
		$settings = is_array( $this->settings ) ? $this->settings : array();
		if ( class_exists( 'ECT_Bricks_Markup', false ) ) {
			return \ECT_Bricks_Markup::ect_bricks_show_event_image( $settings );
		}
		if ( ! class_exists( 'ECT_Bricks_Value_Utils', false ) ) {
			require_once ECT_BRICKS_DIR . 'includes/markup/ect-bricks-value-utils.php';
		}

		return ! \ECT_Bricks_Value_Utils::ect_bricks_parse_bricks_checkbox( $settings['hide_event_image'] ?? false );
	}

	/**
	 * Bricks calls this when the element is on the page (front end or builder iframe).
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! class_exists( '\ECT_Bricks_Plugin', false ) ) {
			return;
		}

		// Builder iframe: load both list skins once so Style 1 ↔ Style 2 switches without refresh.
		$list_style = ( function_exists( 'bricks_is_builder_iframe' ) && bricks_is_builder_iframe() )
			? null
			: $this->ect_bricks_get_list_item_style();

		\ECT_Bricks_Plugin::ect_bricks_enqueue_events_widget_styles(
			$list_style,
			$this->ect_bricks_needs_featured_image_styles()
		);
	}

	public function set_controls() {
		// Builder stack only (styles + list defaults + controls) — not layout PHP.
		$this->ect_bricks_ensure_widget_dependencies( null, true );
		if ( class_exists( 'ECT_Bricks_Controls', false ) ) {
			\ECT_Bricks_Controls::ect_bricks_register_controls( $this );
		}
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $item
	 * @param int      $idx
	 * @param string   $skin
	 * @return void
	 */
	private function ect_bricks_render_part( $post, $item, $idx = 0, $skin = '' ) {
		$html = \ECT_Bricks_Part_Renderer::ect_bricks_render_part_ext(
			$post,
			is_array( $item ) ? $item : array(),
			absint( $idx ),
			(string) $skin,
			is_array( $this->settings ) ? $this->settings : array()
		);
		if ( $html === false || $html === '' ) {
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Part_Renderer.
	}

	/**
	 * @return array{
	 *     template:string,
	 *     item_chrome:string,
	 *     use_style1_shell:bool,
	 *     use_style2_shell:bool,
	 *     item_classes:string
	 * }
	 */
	private function ect_bricks_get_layout_context() {
		$layout = \ECT_Bricks_Markup::ect_bricks_sanitize_layout_template(
			is_array( $this->settings ) ? $this->settings : array()
		);
		$chrome = $layout['item_chrome'];

		return array(
			'template'         => $layout['template'],
			'item_chrome'      => $chrome,
			'use_style1_shell' => ( $chrome === 'style-1' ),
			'use_style2_shell' => ( $chrome === 'style-2' ),
			'item_classes'     => 'ect-ev__item ect-ev__item--' . $chrome . ' repeater-item',
		);
	}

	/**
	 * @param array<string,mixed> $layout
	 * @return array<int,array<string,mixed>>
	 */
	private function ect_bricks_get_parts_effective( array $layout ) {
		$parts_effective = \ECT_Bricks_Markup::ect_bricks_resolve_parts(
			$this->settings,
			$layout['item_chrome']
		);

		foreach ( $this->ect_bricks_layout_shell_map() as $flag => [ $class ] ) {
			if ( $layout[ $flag ] && class_exists( $class, false ) ) {
				return $class::ect_bricks_norm_parts( $parts_effective );
			}
		}

		if (
			$parts_effective === array()
			|| \ECT_Bricks_Markup::ect_bricks_parts_is_empty( $parts_effective )
		) {
			return \ECT_Bricks_List_Defaults::ect_bricks_minimal_parts();
		}

		return is_array( $parts_effective ) ? $parts_effective : array();
	}

	/**
	 * @param \WP_Post[]                     $events
	 * @param array<int,array<string,mixed>> $parts_effective
	 * @param array<string,mixed>            $layout
	 * @param array<string,mixed>            $settings Normalized element settings from {@see render()}.
	 * @param string                         $item_shell_attr Precomputed shell style attribute (may be empty).
	 * @return void
	 */
	private function ect_bricks_render_event_items( array $events, array $parts_effective, array $layout, array $settings, $item_shell_attr = '' ) {
		global $post;
		$original_post = $post ?? null;

		$list_class = 'ect-ev__list ect-ev__list--' . $layout['template'];
		if ( $layout['use_style1_shell'] ) {
			$list_class .= ' event-list';
		}

		echo '<div class="' . esc_attr( $list_class ) . '">';

		foreach ( $events as $event_post ) {
			if ( ! $event_post instanceof \WP_Post ) {
				continue;
			}

			$post = $event_post;
			setup_postdata( $post );

			echo '<div class="' . esc_attr( $layout['item_classes'] ) . '"' . $item_shell_attr . '>';//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$this->ect_bricks_render_event_item_inner( $post, $parts_effective, $layout, $settings );
			echo '</div>';
		}

		echo '</div>';

		wp_reset_postdata();
		$post = $original_post;
	}

	/**
	 * @param \WP_Post                       $post
	 * @param array<int,array<string,mixed>> $parts_effective
	 * @param array<string,mixed>            $layout
	 * @return void
	 */
	private function ect_bricks_render_event_item_inner( $post, array $parts_effective, array $layout, array $settings = array() ) {
		if ( $settings === array() ) {
			$settings = $this->settings;
		}

		foreach ( $this->ect_bricks_layout_shell_map() as $flag => [ $class, $skin ] ) {
			if ( ! $layout[ $flag ] || ! class_exists( $class, false ) ) {
				continue;
			}
			$this->ect_bricks_render_layout_shell_item( $class, $post, $parts_effective, $settings, $skin );
			return;
		}

		$this->ect_bricks_render_plain_event_item_parts( $post, $parts_effective );
	}

	private function ect_bricks_layout_shell_map() {
		return array(
			'use_style1_shell' => array( 'ECT_Bricks_List_1', 'style1' ),
			'use_style2_shell' => array( 'ECT_Bricks_List_2', 'style2' ),
		);
	}

	private function ect_bricks_render_layout_shell_item( $class, $post, array $parts_effective, array $settings, $skin ) {
		$widget = $this;
		$emit   = static function ( $ev, $item, $idx ) use ( $widget, $skin ) {
			$widget->ect_bricks_render_part( $ev, $item, $idx, $skin );
		};
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $class::ect_bricks_item_inner( $post, $parts_effective, $emit, $settings );
	}

	private function ect_bricks_render_plain_event_item_parts( $post, array $parts_effective ) {
		$part_idx = 0;
		foreach ( $parts_effective as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$this->ect_bricks_render_part( $post, $item, $part_idx );
			++$part_idx;
		}
	}

	public function render() {
		if ( ! $this->ect_bricks_has_markup_dependencies() ) {
			$this->set_attribute( '_root', 'class', 'ect-ev' );
			echo '<div ' . $this->render_attributes( '_root' ) . '>';//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$this->ect_bricks_render_missing_dependency_notice();
			echo '</div>';
			return;
		}

		$this->settings = \ECT_Bricks_Markup::ect_bricks_norm_widget_settings( is_array( $this->settings ) ? $this->settings : array() );

		$this->set_attribute( '_root', 'class', 'ect-ev' );

		$settings = $this->settings;
		$vignette = isset( $settings['ect_bricks_featured_image_vignette'] ) ? (string) $settings['ect_bricks_featured_image_vignette'] : 'none';
		if ( $vignette !== '' && $vignette !== 'none' ) {
			$this->set_attribute( '_root', 'class', 'ect-vig--' . sanitize_html_class( $vignette ) );
		}

		foreach ( \ECT_Bricks_Markup::ect_bricks_shell_style_root_classes( $settings ) as $shell_class ) {
			$this->set_attribute( '_root', 'class', $shell_class );
		}

		$shell_cat_style = class_exists( 'ECT_Bricks_Part_Chrome', false )
			? \ECT_Bricks_Part_Chrome::ect_bricks_shell_category_root_style_decls( $settings )
			: '';
		$item_shell_attr = '';
		if ( $shell_cat_style !== '' ) {
			$this->set_attribute( '_root', 'style', $shell_cat_style );
			$item_shell_attr = ' style="' . esc_attr( $shell_cat_style ) . '"';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks render_attributes() returns the element's escaped attribute string.
		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		if ( ! function_exists( 'tribe_get_events' ) ) {
			if ( \Bricks\Capabilities::current_user_can_use_builder() ) {
				echo '<div class="ect-placeholder">' . esc_html__( 'The Events Calendar is required to render Events Widget.', 'template-events-calendar' ) . '</div>';
			}
			echo '</div>';
			return;
		}

		$layout = $this->ect_bricks_get_layout_context();
		$parts  = $this->ect_bricks_get_parts_effective( $layout );
		$events = class_exists( 'ECT_Bricks_Query', false )
			? \ECT_Bricks_Query::ect_bricks_fetch_events( $settings )
			: array();

		if ( empty( $events ) ) {
			$this->ect_bricks_render_no_events_message();
			echo '</div>';
			return;
		}

		$this->ect_bricks_render_event_items( $events, $parts, $layout, $settings, $item_shell_attr );

		echo '</div>';
	}
}

/**
 * Bricks registration alias for {@see ECT_Bricks_Widget}.
 *
 * Bricks' Elements::register_element() expects an Element_-prefixed class name.
 * All widget logic lives on ECT_Bricks_Widget; this empty subclass is the handle
 * passed to Bricks ({@see ECT_Bricks_Plugin::ect_bricks_register_elements()}).
 */
class Element_ECT_Bricks_Events_Widget extends ECT_Bricks_Widget {}
