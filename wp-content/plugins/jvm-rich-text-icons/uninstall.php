<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove any uploaded icon files
$upload = wp_upload_dir();
$upload_dir_files = $upload['basedir'].'/jvm-rich-text-icons/';

if (is_dir($upload_dir_files)) {
    $files = glob($upload_dir_files . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (!is_dir($file)) {
            unlink($file);
        }
    }
    rmdir($upload_dir_files);
}

// Delete the settings
delete_option('jvm-rich-text-icons');
delete_option('jvm_richtext_icons_activated');
delete_option('jvm_richtext_icons_review_dismissed');
