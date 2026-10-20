<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * This file is used to share events.
 * 
 * @package the-events-calendar-templates-and-shortcode/includes
 */
//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function ect_share_button($event_id){
  $ect_sharecontent = '';
  $ect_geturl = esc_url_raw(get_permalink($event_id));
  $ect_gettitle = get_the_title( $event_id );
  $subject      = sanitize_text_field( $ect_gettitle );
  // Construct sharing URL
    $ect_twitterURL = 'https://twitter.com/intent/tweet?text='.rawurlencode($ect_gettitle).'&amp;url='.rawurlencode($ect_geturl).'';
    $ect_whatsappURL = 'https://web.whatsapp.com/send/?text='.rawurlencode($ect_gettitle) . ' ' . rawurlencode($ect_geturl);
    $ect_facebookurl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($ect_geturl).'';
    $ect_emailUrl = 'mailto:?Subject='.rawurlencode($subject).'&Body='.rawurlencode($ect_geturl).'';
    $ect_linkedinUrl ="http://www.linkedin.com/shareArticle?mini=true&amp;url=".rawurlencode($ect_geturl);
    // Add sharing button at the end of page/page content
    $ect_sharecontent .= '<div class="ect-share-wrapper">';
    $ect_sharecontent .= '<i class="ect-icon-share"></i>';
    $ect_sharecontent .= '<div class="ect-social-share-list">';
    $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_facebookurl).'" target="_blank" title="Facebook" aria-haspopup="true"><i class="ect-icon-facebook"></i></a>';
    $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_twitterURL).'" target="_blank" title="Twitter" aria-haspopup="true"><i class="ect-icon-twitter"></i></a>';
    $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_linkedinUrl).'" target="_blank" title="Linkedin" aria-haspopup="true"><i class="ect-icon-linkedin"></i></a>';
    $ect_sharecontent .= '<a class="ect-email" href="'.esc_url($ect_emailUrl).'"><i class="ect-icon-mail"></i></a>';
    $ect_sharecontent .= '<a class="ect-share-link" href="'.esc_url($ect_whatsappURL).'" target="_blank" title="WhatsApp" aria-haspopup="true"><i class="ect-icon-whatsapp"></i></a>';
    $ect_sharecontent .= '</div></div>';
    return $ect_sharecontent;
}
