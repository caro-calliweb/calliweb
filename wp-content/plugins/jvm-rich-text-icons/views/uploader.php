<?php
       
if ( ! current_user_can( 'upload_files' ) ) {
    wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
}

//wp_enqueue_script( 'plupload-handlers' );

$form_class = 'media-upload-form type-form validate';
$nonce = wp_create_nonce( 'jvm-rich-text-icons-upload-icon' );

if ( get_user_setting( 'uploader' ) || isset( $_GET['browser-uploader'] ) ) {
    $form_class .= ' html-uploader';
}
?>
<div class="media-sidebar">
    <div id="media-uploader-status">
        <div id="upload-progess">
            <h2><?php _e('Uploading');?></h2>
            <div class="media-progress-bar">
                <div id="upload-progess-bar-inner" style="width:0%;"></div>
            </div>

            <div class="upload-details">
                <span class="upload-count">
                    <span id="upload-index">1</span> / <span id="upload-total">1</span>
                </span>
                <span class="upload-detail-separator">–</span>
                <span id="upload-filename"></span>
            </div>
        </div>
        <div id="upload-errors"></div>
        <button id="upload-dismiss-errors" type="button" class="button upload-dismiss-errors" style="display:none;"><?php _e('Dismiss errors');?></button>
    </div>
</div>
<form id="jvm-rich-text-icons_custom_icon_uploader" action="<?php echo admin_url( 'admin-ajax.php' );?>?action=jvm-rich-text-icons-upload-icon&nonce=<?php echo $nonce;?>" class="dropzone" style="display: none;">
    <div class="media-frame wp-core-ui mode-grid">
        <div class="uploader-inline">
            <button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e('Close uploader');?></span></button>
            
            <div class="uploader-inline-content no-upload-message">
                <div class="upload-ui">
                    <h2 class="upload-instructions drop-instructions"><?php _e('Drop SVG icons to upload', 'jvm-rich-text-icons');?></h2>
                    <p class="upload-instructions drop-instructions"><?php _e('or');?></p>
                    <button type="button" class="browser button button-hero" id="__wp-uploader-id-1" style="position: relative; z-index: 1;" aria-labelledby="__wp-uploader-id-1 post-upload-info"><?php _e('Select Files');?></button>
                </div>

                <div class="upload-inline-status"></div>

                
            </div>
        </div>
    </div>
</form>