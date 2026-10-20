<form method="post" action="options.php">
    <?php settings_fields("jvm-rich-text-icons"); ?>
    <input type="hidden" name="jvm-rich-text-icons[_tab]" value="tools">

    <fieldset class="jvm-rti-fieldset">
        <legend><?php _e("SVG Uploads", "jvm-rich-text-icons"); ?></legend>
        <p><?php _e(
            "All SVG uploads are sanitized for security and can undergo additional processing. This is especially useful when using the Inline SVG render technology.",
            "jvm-rich-text-icons",
        ); ?></p>
        <?php do_settings_sections("jvm-rich-text-icons-tools"); ?>
    </fieldset>

    <?php submit_button(__("Save Settings", "jvm-rich-text-icons")); ?>
</form>

<hr>

<div id="jvm-rti-bulk-sanitize-section">
    <h2><?php _e("Bulk Sanitize SVGs", "jvm-rich-text-icons"); ?></h2>
    <?php
    $svg_dir = JVM_Richtext_icons::get_svg_directory();
    if (!is_writable($svg_dir)): ?>
        <div class="notice notice-error inline">
            <p><?php printf(
                __("The SVG uploads directory is not writable. Bulk sanitize will not work until permissions are fixed: %s", "jvm-rich-text-icons"),
                "<code>" . esc_html($svg_dir) . "</code>",
            ); ?></p>
        </div>
    <?php endif;
    ?>
    <p><?php _e(
        "Re-process all uploaded SVG icons through the sanitizer settings above. Files are updated in place. <br /><strong>Please note: </strong> Sanitation is irreversible. It is advised you have a backup of the wp-content/uploads/jvm-rich-text-icons just in case icons break.",
        "jvm-rich-text-icons",
    ); ?></p>

    <div id="jvm-rti-bulk-progress" style="display:none;">
        <div class="jvm-rti-bulk-bar-wrap">
            <div id="jvm-rti-bulk-bar-inner"></div>
        </div>
        <p id="jvm-rti-bulk-status"></p>
    </div>

    <div id="jvm-rti-bulk-errors" style="display:none;"></div>

    <button type="button" id="jvm-rti-bulk-sanitize-btn" class="button button-secondary">
        <?php _e("Sanitize All Icons", "jvm-rich-text-icons"); ?>
    </button>
</div>
