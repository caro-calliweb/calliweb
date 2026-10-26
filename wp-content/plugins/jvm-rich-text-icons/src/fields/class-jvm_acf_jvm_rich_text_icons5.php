<?php
// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// check if class already exists
if( !class_exists('JVM_acf_field_jvm_rich_text_icons') ) {

class JVM_acf_field_jvm_rich_text_icons extends acf_field {
    /**
     * Settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
     * @var array
     */
    public $settings;
    
    /*
    *  __construct
    *
    *  This function will setup the field type data
    *
    *  @type    function
    *  @date    5/03/2014
    *  @since   5.0.0
    *
    *  @param   n/a
    *  @return  n/a
    */
    
    public function __construct( $settings ) { 
        /*
        *  name (string) Single word, no spaces. Underscores allowed
        */
        $this->name = 'jvm_rich_text_icon';
        
        
        /*
        *  label (string) Multiple words, can include spaces, visible when selecting a field type
        */
        $this->label = __('JVM Icon', 'jvm-rich-text-icons');
        
        
        /*
        *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
        */
        $this->category = 'content';
        
        
        /*
        *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
        */        
        $this->defaults = array(
                'allow_null'        =>  0,
                'default_value'     => '',
                'output_format'     => 'element'
            );

        /*
        *  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
        */
        $this->settings = $settings;
        
        // do not delete!
        parent::__construct();        
    }
    
    
    /*
    *  render_field_settings()
    *
    *  Create extra settings for your field. These are visible when editing a field
    *
    *  @type    action
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $field (array) the $field being edited
    *  @return  n/a
    */
    
    public function render_field_settings( $field ) {
        acf_render_field_setting( $field, array(
            'label'         => __('Default icon','jvm-rich-text-icons'),
            'type'          => 'select',
            'name'          => 'default_value',
            'class'         => 'select2-jvm-rich-text-icon jvm-rich-text-icon-create',
            'choices'       => isset( $field['default_value'] ) ? [  $field['default_value'] => html_entity_decode( $field['default_value'] ) ] : [],
            'value'         => isset($field['default_value']) ? $field['default_value'] : '',
            'placeholder'   => __('Choose a default icon (optional)','jvm-rich-text-icons'),
            'ui'            => 1,
            'allow_null'    => 1,
            'ajax'          => 1,
            'ajax_action'   => 'acf/fields/jvm-richtext-insert-icons/query'
        ));

        acf_render_field_setting( $field, array(
            'label'         => __( 'Return Value', 'jvm-rich-text-icons' ),
            'instructions'  => __( 'Specify the returned value on front end', 'jvm-rich-text-icons' ),
            'type'          => 'radio',
            'name'          => 'output_format',
            'choices'   =>  array(
                'element'   =>  __( 'Icon Element', 'jvm-rich-text-icons' ),
                'class'     =>  __( 'Icon Class', 'jvm-rich-text-icons' )
            )
        ));

        acf_render_field_setting( $field, array(
            'label'         => __( 'Allow Null?', 'jvm-rich-text-icons' ),
            'instructions'  => '',
            'type'          => 'radio',
            'name'          => 'allow_null',
            'choices'   =>  array(
                1   =>  __( 'Yes', 'jvm-rich-text-icons' ),
                0   =>  __( 'No', 'jvm-rich-text-icons' )
            )
        ));
    }
    
    
    
    /*
    *  render_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param   $field (array) the $field being rendered
    *
    *  @type    action
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $field (array) the $field being edited
    *  @return  n/a
    */
    
    function render_field( $field ) {  
        if ( $field['allow_null'] ) {
            $select_value = $field['value'];
        } else {
            $select_value = ( 'null' != $field['value'] ) ? $field['value'] : $field['default_value'];
        }

        $field['type'] = 'select';
        $field['ui'] = 1;
        $field['ajax'] = 1;
        $field['ajax_action'] = 'acf/fields/jvm-richtext-insert-icons/query';
        $field['choices'] = [];
        $field['multiple'] = false;
        $field['class'] = ' select2-jvm-rich-text-icon select2-jvm-rich-text-icon-edit';

        $icons = JVM_Richtext_icons::get_icons();
        if ( $select_value && in_array($select_value, $icons) ) {
            $field['choices'][ $select_value ] = htmlentities( $select_value );
        }

        acf_render_field( $field );
    }

    
    /*
    *  format_value()
    *
    *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
    *
    *  @type    filter
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $value (mixed) the value which was loaded from the database
    *  @param   $post_id (mixed) the $post_id from which the value was loaded
    *  @param   $field (array) the field array holding all the field options
    *
    *  @return  $value (mixed) the modified value
    */    
    public function format_value( $value, $post_id, $field ) {
        if ( 'null' == $value ) {
            return false;
        }

        if ( empty( $value ) ) {
            return $value;
        }
        
        $icons = JVM_Richtext_icons::get_icons();
        $css_class = JVM_Richtext_icons::get_class_prefix();
        $icon = in_array($value, $icons);

        if ($icon) {
            switch ( $field['output_format'] ) {
                case 'element':
                return '<i class="' . $css_class . ' ' . $value . '" aria-hidden="true"></i>';
                break;
            }
        }
        
        // return
        return $value;
    } 
}


// initialize
new JVM_acf_field_jvm_rich_text_icons( $this->settings );

// class_exists check
}