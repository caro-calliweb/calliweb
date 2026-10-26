<?php
$files = JVM_Richtext_icons::get_svg_file_list();
?>
<div id="svg-file-list"<?php echo empty($files) ? ' style="display:none;"' : '';?>>
<?php
    $css_class = JVM_Richtext_icons::get_class_prefix();
    $nonce = wp_create_nonce( 'jvm-rich-text-icons-delete-icon' );
    foreach ($files as $file) {
        $pi = pathinfo($file);

        $icon_class = sanitize_title($pi['filename']);
        $svg_content = file_get_contents($file);
        if (class_exists('JVM_RTI_Renderer')) {
            $svg_content = JVM_RTI_Renderer::clean_svg($svg_content);
        }

        echo '<a id="icon-dialog-link-'.$icon_class.'" href="#icon-dialog" class="icon-dialog-link icon" data-icon-class-full="'.$css_class . ' ' . $icon_class .'" data-icon-class="'. $icon_class .'" data-file="'.esc_js(basename($file)).'" data-nonce="'.$nonce.'">';
        echo '<span class="icon-dialog-svg" aria-hidden="true">' . $svg_content . '</span>';
        echo '<span class="icon-dialog-label">' . esc_html($icon_class) . '</span>';
        echo '</a>'."\n";
    }
?>
</div>
<div id="icon-dialog" >
    <div style="font-size:72px;text-align:center;">
        <span id="icon-dialog-preview" aria-hidden="true"></span>
    </div>
</div>
<p id="svg-file-list-empty" <?php echo empty($files) ? '' : 'style="display:none;"';?>><?php _e('No custom icons have been uploaded. Please upload some SVG files to create your custom icon set.', 'jvm-rich-text-icons');?></p>
