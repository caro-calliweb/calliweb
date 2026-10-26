<?php
$settings    = JVM_Richtext_icons::get_settings();
$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'icon-set';
if ( ! in_array( $current_tab, [ 'icon-set', 'tools' ], true ) ) {
    $current_tab = 'icon-set';
}
$base_url = admin_url( 'options-general.php?page=jvm-rich-text-icons' );
?>
<div class="wrap">
    <h1><?php _e( 'Rich Text Icons Settings', 'jvm-rich-text-icons' ); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( $base_url . '&tab=icon-set' ); ?>"
           class="nav-tab<?php echo $current_tab === 'icon-set' ? ' nav-tab-active' : ''; ?>">
            <?php _e( 'Icon Set', 'jvm-rich-text-icons' ); ?>
        </a>
        <a href="<?php echo esc_url( $base_url . '&tab=tools' ); ?>"
           class="nav-tab<?php echo $current_tab === 'tools' ? ' nav-tab-active' : ''; ?>">
            <?php _e( 'Tools &amp; Settings', 'jvm-rich-text-icons' ); ?>
        </a>
    </nav>

    <?php if ( $current_tab === 'icon-set' ) : ?>

        <?php if ( in_array( $settings['icon_set'], [ 'default', 'fa-5', 'fa-6' ], true ) ) : ?>
            <style>.form-table tr.jvm-rti-technology-row { display: none; }</style>
        <?php endif; ?>

        <form id="jvm-rich-text-icons-settings-form" method="post" action="options.php">
            <?php settings_fields( 'jvm-rich-text-icons' ); ?>
            <input type="hidden" name="jvm-rich-text-icons[_tab]" value="icon-set">
            <?php
                do_settings_sections( 'jvm-rich-text-icons' );
                submit_button();
            ?>
        </form>

        <?php do_action( 'jvm_richtext_icons_settings_after_form', $settings ); ?>

        <div id="jvm-rich-text-icons_custom_icon_generator"<?php echo $settings['icon_set'] !== 'custom-svg' ? ' style="display:none;"' : ''; ?>>
            <h2 class="title"><?php _e( 'My SVG uploads', 'jvm-rich-text-icons' ); ?></h2>
            <a id="add_icon_btn" href="#" class="page-title-action"><?php _e( 'Add Icon', 'jvm-rich-text-icons' ); ?></a>
            <?php
            $svg_dir = JVM_Richtext_icons::get_svg_directory();
            if ( ! is_writable( $svg_dir ) ) :
            ?>
                <div class="notice notice-error inline" style="margin: 12px 0;">
                    <p><?php printf(
                        __( 'The SVG uploads directory is not writable. Uploading and deleting icons will not work until permissions are fixed: %s', 'jvm-rich-text-icons' ),
                        '<code>' . esc_html( $svg_dir ) . '</code>'
                    ); ?></p>
                </div>
            <?php endif; ?>
            <?php
                echo JVM_Richtext_icons::render_view( 'uploader.php' );
                echo JVM_Richtext_icons::render_view( 'icon-list.php' );
            ?>
        </div>

        <?php do_action( 'jvm_richtext_icons_settings_after_icons', $settings ); ?>

        <?php if ( apply_filters( 'jvm_richtext_icons_show_donate', true ) ) : ?>
        <form action="https://www.paypal.com/donate" method="post" target="_top">
            <p style="max-width: 500px;">
                I am Joris van Montfort and I created and maintain the free plugin <strong>JVM Rich Text Icons</strong> for you. If you like using this plugin and want to keep this plugin free, please donate a small amount of money. Thank you!
            </p>
            <input type="hidden" name="hosted_button_id" value="VXZJG9GC34JJU" />
            <input type="image" src="https://www.paypalobjects.com/en_US/NL/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
            <img alt="" border="0" src="https://www.paypal.com/en_NL/i/scr/pixel.gif" width="1" height="1" />
        </form>
        <?php endif; ?>

    <?php elseif ( $current_tab === 'tools' ) : ?>

        <?php echo JVM_Richtext_icons::render_view( 'settings-tools.php' ); ?>

    <?php endif; ?>
</div>
