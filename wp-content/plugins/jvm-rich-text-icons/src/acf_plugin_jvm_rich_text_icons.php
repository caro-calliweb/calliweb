<?php
// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('JVM_acf_plugin_jvm_rich_text_icons') )  {

class JVM_acf_plugin_jvm_rich_text_icons {

    // vars
    public $settings;


    /*
    *  __construct
    *
    *  This function will setup the class functionality
    *
    *  @type    function
    *  @since   1.0.0
    *
    *  @param   void
    *  @return  void
    */

    public function __construct() {

        // settings
        // - these will be passed into the field class.
        $this->settings = array(
            'version'   => '1.0.5',
            'url'       => plugin_dir_url( __FILE__ ),
            'path'      => plugin_dir_path( __FILE__ )
        );


        // include field
        add_action('acf/include_field_types',   array($this, 'include_field')); // v5

        add_action( 'admin_init', array( $this, 'load_admin_assets') );
        add_action( 'wp_ajax_acf/fields/jvm-richtext-insert-icons/query', array( $this, 'select2_ajax_request' ) );
        add_action( 'acf/input/admin_footer', array( $this, 'fix_select2_html') );
    }


    public function select2_ajax_request () {
        if ( ! acf_verify_ajax() ) {
            die();
        }

        $icons = JVM_Richtext_icons::get_icons();
        $css_class = JVM_Richtext_icons::get_class_prefix();

        $s = false;
        if (isset($_POST['s'])) {
            if (!empty($_POST['s'])) {
                $s = $_POST['s'];
            }
        }

        $icons_out = [];
        foreach ($icons as $ic) {
            $pos = empty($s) ? 1 : strpos($ic, $s);
            if (($pos === 0 || $pos >= 1)) {
                $icons_out[] = [
                    'id' => $ic,
                    'text' => '<i class="'.$css_class.' '.$ic. '" aria-hidden="true"></i> '.$ic
                ];
            }
        }

        $response = [
            'more' => false,
            'limit' => 0,
            'results' => $icons_out,
        ];

        acf_send_ajax_results( $response );
    }

    public function fix_select2_html () {
        if (!is_admin()) {
            return;
        }

        $css_class = JVM_Richtext_icons::get_class_prefix();

        echo '<style>
          .select2-jvm-rich-text-icon-edit + .select2 .select2-selection__rendered i,
          .select2-jvm-rich-text-icon-edit + .select2 .select2-results__option i {
            width: 1em;
            height: 1em;
            margin-right: 4px;
            vertical-align: -0.125em;
          }
        </style>
        <script>
          if (typeof acf === \'undefined\') { /* ACF not loaded on this screen */ } else {
          var jvmIconCssClass = ' . wp_json_encode( $css_class ) . ';

          acf.add_filter(\'select2_args\', function(args, $select) {
            if ($select && $select.hasClass(\'select2-jvm-rich-text-icon\')) {
              args.escapeMarkup = function(markup) {
                return markup;
              };
            }
            return args;
          });

          acf.add_action(\'ready_field/type=jvm_rich_text_icon append_field/type=jvm_rich_text_icon\', function($field) {
            setTimeout(function() {
              var $select = $field.find(\'select.select2-jvm-rich-text-icon\');
              if (!$select.length) return;

              function renderSelection(value) {
                if (!value || value === \'null\') return;
                var icon = \'<i class="\' + jvmIconCssClass + \' \' + value + \'" aria-hidden="true"></i> \';
                $field.find(\'.select2-selection__rendered\').html(icon + value);
              }

              renderSelection($select.val());

              $select.on(\'select2:select\', function(e) {
                var id = e.params && e.params.data && e.params.data.id;
                if (id && typeof id === \'string\') {
                  setTimeout(function() {
                    renderSelection(id);
                  }, 0);
                }
              });
            }, 50);
          }, null, 20);
          }
        </script>';
    }


    public function load_admin_assets() {
        $js_file = apply_filters('jvm_richtext_icons_editor_js_file', plugins_url( '/dist/acf.js', dirname( __FILE__ ) ));

        wp_enqueue_script(
            'jvm-rich-text-icons-acf', // Handle.
            $js_file, // Block.build.js: We register the block here. Built with Webpack.
            //array( 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
            array(),
            null, // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
            true // Enqueue the script in the footer.
        );

        JVM_Richtext_icons::load_css();
    }

    /*
    *  include_field
    *
    *  This function will include the field type class
    *
    *  @type    function
    *  @date    17/02/2016
    *  @since   1.0.0
    *
    *  @param   $version (int) major ACF version. Defaults to false
    *  @return  void
    */

    public function include_field( $version = false ) {

        // support empty $version
        if( !$version ) $version = 5;


        // load textdomain
        load_plugin_textdomain( 'jvm-richtext-insert-icons', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );


        // include
        include_once('fields/class-jvm_acf_jvm_rich_text_icons' . $version . '.php');
    }

}


// initialize
new JVM_acf_plugin_jvm_rich_text_icons();

// class_exists check
}
