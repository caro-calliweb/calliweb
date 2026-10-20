<?php if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.
/**
 *
 * Field: code_editor
 *
 * @since 1.0.0
 * @version 1.0.0
 */
//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
if ( ! class_exists( 'CSF_Field_code_editor' ) ) {
	class CSF_Field_code_editor extends CSF_Fields {

		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		public function render() {

			$default_settings = array(
				'tabSize'     => 2,
				'lineNumbers' => true,
				'theme'       => 'default',
				'mode'        => 'htmlmixed',
			);

			$settings = ( ! empty( $this->field['settings'] ) ) ? $this->field['settings'] : array();
			$settings = wp_parse_args( $settings, $default_settings );

			$field_id   = ( ! empty( $this->field['id'] ) ) ? $this->field['id'] : '';
			$editor_id  = 'csf-code-editor-' . preg_replace( '/[^a-zA-Z0-9_-]/', '-', ( $this->unique . '-' . $field_id ) );

			$attributes = array( 'id' => $editor_id );
			$custom_atts = ( ! empty( $this->field['attributes'] ) ) ? $this->field['attributes'] : array();
			$attributes = wp_parse_args( $custom_atts, $attributes );
			$atts = '';
			foreach ( $attributes as $key => $value ) {
				$atts .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}

			echo $this->field_before();
			echo '<textarea name="' . esc_attr( $this->field_name() ) . '"' . $this->field_attributes() . $atts . ' data-editor="' . esc_attr( wp_json_encode( $settings ) ) . '">' . $this->value . '</textarea>';
			echo $this->field_after();

		}

		public function enqueue() {

			$page = ( ! empty( $_GET['page'] ) ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			// Do not loads CodeMirror in revslider page.
			if ( in_array( $page, array( 'revslider' ) ) ) {
				return; }

		}

	}
}
