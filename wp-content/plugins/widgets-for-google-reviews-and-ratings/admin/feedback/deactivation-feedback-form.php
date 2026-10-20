<?php
defined('ABSPATH') || die('Cheatin&#8217; uh?');
$deactivation_url = wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . rawurlencode(WGRR_PLUGIN_BASE_NAME), 'deactivate-plugin_' . WGRR_PLUGIN_BASE_NAME);
?>
<div class="wgrr-deactivation-Modal">
    <div class="wgrr-deactivation-Modal-header">
        <div>
            <button class="wgrr-deactivation-Modal-return deactivation-icon-chevron-left"><?php esc_html_e('Return', 'widgets-for-google-reviews-and-ratings'); ?></button>
            <h2><?php esc_html_e('We’re sorry to see you go! 💔', 'widgets-for-google-reviews-and-ratings'); ?></h2>
        </div>
        <button class="wgrr-deactivation-Modal-close deactivation-icon-close"><?php esc_html_e('Close', 'widgets-for-google-reviews-and-ratings'); ?></button>
    </div>
    <div class="wgrr-deactivation-Modal-content">
        <div class="wgrr-deactivation-Modal-question deactivation-isOpen">
            <p><?php esc_html_e('Can you please tell us why you’re deactivating the plugin? Your feedback helps us make it better.', 'widgets-for-google-reviews-and-ratings'); ?></p>
            <ul>
                <li>
                    <input type="radio" name="reason" id="reason-temporary" value="Temporary Deactivation">
                    <label for="reason-temporary"><?php esc_html_e('Temporary deactivation (troubleshooting)', 'widgets-for-google-reviews-and-ratings'); ?></label>
                </li>
                <li>
                    <input type="radio" name="reason" id="reason-broke" value="Broken Layout">
                    <label for="reason-broke"><?php esc_html_e('Compatibility issue', 'widgets-for-google-reviews-and-ratings'); ?></label>
                    <div class="wgrr-deactivation-Modal-fieldHidden">
                        <textarea placeholder="<?php esc_attr_e('Please describe what part of the layout or functionality was affected.', 'widgets-for-google-reviews-and-ratings'); ?>"></textarea>
                    </div>
                </li>
                <li>
                    <input type="radio" name="reason" id="reason-complicated" value="Complicated">
                    <label for="reason-complicated"><?php esc_html_e('Difficult to set up', 'widgets-for-google-reviews-and-ratings'); ?></label>
                    <div class="wgrr-deactivation-Modal-fieldHidden">
                        <textarea placeholder="<?php esc_attr_e('What part of the setup was confusing or unclear?', 'widgets-for-google-reviews-and-ratings'); ?>"></textarea>
                    </div>
                </li>
                <li>
                    <input type="radio" name="reason" id="not-provided" value="features not provided">
                    <label for="not-provided"><?php esc_html_e('Missing features', 'widgets-for-google-reviews-and-ratings'); ?></label>
                    <div class="wgrr-deactivation-Modal-fieldHidden">
                        <textarea placeholder="<?php esc_attr_e('Which features were you looking for?', 'widgets-for-google-reviews-and-ratings'); ?>"></textarea>
                    </div>
                </li>
                <li>
                    <input type="radio" name="reason" id="reason-other" value="Other">
                    <label for="reason-other"><?php esc_html_e('Other', 'widgets-for-google-reviews-and-ratings'); ?></label>
                    <div class="wgrr-deactivation-Modal-fieldHidden">
                        <textarea placeholder="<?php esc_attr_e('Please share why you’re deactivating the Google Business Reviews plugin so we can make improvements.', 'widgets-for-google-reviews-and-ratings'); ?>"></textarea>
                    </div>
                </li>
            </ul>
            <input id="deactivation-reason" type="hidden" value="">
            <input id="deactivation-details" type="hidden" value="">


            <input id="deactivation-reason" type="hidden" value="">
            <input id="deactivation-details" type="hidden" value="">
        </div>
        <p style="margin-top: 20px;">
            <?php esc_html_e('Your privacy is important to us. No personal data is collected with this form—just your valuable feedback and basic system information (such as WordPress and plugin versions) to help us improve our plugin.', 'widgets-for-google-reviews-and-ratings'); ?>
        </p>
    </div>

    <div class="wgrr-deactivation-Modal-footer">
        <a href="https://wordpress.org/support/plugin/widgets-for-google-reviews-and-ratings" class="button button-primary" target="_blank" title="<?php esc_attr_e('Visit our support page for assistance', 'widgets-for-google-reviews-and-ratings'); ?>"><?php esc_html_e('Get Support', 'widgets-for-google-reviews-and-ratings'); ?></a>
        <div>
            <a href="<?php echo esc_attr($deactivation_url); ?>" class="button button-primary deactivation-isDisabled" disabled id="wgrr-mixpanel-send-deactivation"><?php esc_html_e('Send & Deactivate', 'widgets-for-google-reviews-and-ratings'); ?></a>
        </div>
        <a id="wgrr-deactivation-no-reason" href="<?php echo esc_attr($deactivation_url); ?>" class=""><?php esc_html_e('I rather wouldn\'t say', 'widgets-for-google-reviews-and-ratings'); ?></a>
    </div>
</div>
<div class="wgrr-deactivation-Modal-overlay"></div>
