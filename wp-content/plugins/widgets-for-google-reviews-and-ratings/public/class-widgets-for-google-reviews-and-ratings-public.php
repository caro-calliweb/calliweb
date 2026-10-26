<?php

class Widgets_For_Google_Reviews_And_Ratings_Public {

    private $plugin_name;
    private $version;
    public $place_details;
    public $is_min;
    public $get_review_url;
    public $hide_date;
    public $hide_profile_picture;
    public $hide_google_logo;
    public $repocean_hide_rating_text;
    public $update_review_count;
    public $star_color;
    public $hide_prev_next_buttons;
    public $hide_review_photos;
    public $show_verified_symbol;
    public $shorten_reviewer_names;
    public $enable_dark_mode;
    public $enable_dark_mode_class;
    public $review_url;
    public $bg_color;
    public $border_color;
    public $card_border_radius;
    public $min_star_rating;
    public $hide_empty_reviews;
    public $hide_review_us_button;
    public $review_text_lines;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->place_details = get_option('wgrr_g_place_details', '');
        add_shortcode('repocean_reviews', array($this, 'repocean_reviews_shortcode'));
        $this->is_min = !( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG );
        $this->get_review_url = REPOCEAN_URL . 'reviews/getJson/' . ($this->place_details['place_id'] ?? '');
        $this->update_review_count = REPOCEAN_URL . 'reviews/update_review_count/' . ($this->place_details['place_id'] ?? '');
        $this->hide_date = 'yes' === get_option('repocean_hide_date', 'no');
        $this->hide_profile_picture = 'yes' === get_option('repocean_hide_profile_picture', 'no');
        $this->hide_google_logo = 'yes' === get_option('repocean_hide_google_logo', 'no');
        $this->repocean_hide_rating_text = 'yes' === get_option('repocean_hide_rating_text', 'no');
        $this->star_color = get_option('repocean_star_color', '#F6BB06');
        $this->hide_prev_next_buttons = 'yes' === get_option('repocean_hide_prev_next_buttons', 'no'); // New Option
        $this->hide_review_photos = 'yes' === get_option('repocean_hide_review_photos', 'no'); // New Option
        $repocean_show_verified_symbol_default_option = ( $this->repocean_get_user_status_4_july() === 'existing') ? 'no' : 'yes';
        $this->show_verified_symbol = 'yes' === get_option('repocean_show_verified_symbol', $repocean_show_verified_symbol_default_option); // New Option
        $this->shorten_reviewer_names = 'yes' === get_option('repocean_shorten_reviewer_names', 'no');
        $this->enable_dark_mode = 'yes' === get_option('repocean_enable_dark_mode', 'no');
        $this->bg_color         = get_option('repocean_bg_color', '#f6f6f6');
        $this->border_color     = get_option('repocean_border_color', '#f6f6f6');
        $this->card_border_radius = (int) get_option('repocean_card_border_radius', 12);
        $this->min_star_rating   = (int) get_option('repocean_min_star_rating', 5);
        $this->hide_empty_reviews = 'yes' === get_option('repocean_hide_empty_reviews', 'no');
        $this->hide_review_us_button = 'yes' === get_option('repocean_hide_review_us_button', 'no');
        $this->review_text_lines = (int) get_option('repocean_review_text_lines', 5);
        if ($this->review_text_lines < 2 || $this->review_text_lines > 10) {
            $this->review_text_lines = 5;
        }
        if ($this->enable_dark_mode) {
            $this->enable_dark_mode_class = 'dark';
        }
        $place_id = isset($this->place_details['place_id']) ? $this->place_details['place_id'] : '';
        $this->review_url = "https://search.google.com/local/writereview?placeid=" . $place_id;
        add_action('repocean_update_review_count', array($this, 'update_google_place_review_count'));
    }

    public function repocean_reviews_shortcode($atts) {
        $atts = shortcode_atts(
                array(
                    'layout' => 'slider',
                    'limit' => '100'
                ),
                $atts,
                'repocean_reviews'
        );
        $output = '';
        switch ($atts['layout']) {
            case 'slider':
                $output .= $this->repocean_reviews_slider_shortcode($atts);
                break;
            case 'slider_v1':
                $output .= $this->repocean_reviews_slider_v1_shortcode($atts);
                break;
            case 'slider_v2':
                $output .= $this->repocean_reviews_slider_v2_shortcode($atts);
                break;
            case 'slider_v3':
                $output .= $this->repocean_reviews_slider_v3_shortcode($atts);
                break;
            case 'slider_v4':
                $output .= $this->repocean_reviews_slider_v4_shortcode($atts);
                break;
            case 'grid':
                $output .= $this->repocean_reviews_grid_shortcode($atts);
                break;
            case 'list':
                $output .= $this->repocean_reviews_list_shortcode($atts);
                break;
            case 'sidebar':
                $output .= $this->repocean_reviews_sidebar_shortcode($atts);
                break;
            default:
                $output .= $this->repocean_reviews_slider_shortcode($atts);
                break;
        }
        return $output;
    }

    public function repocean_reviews_slider_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                wp_enqueue_script($this->plugin_name . 'slider', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-slider', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'slider', 'repocean_slider_js', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings'),
                    'arrowVisibility' => $this->hide_prev_next_buttons === false ? 'true' : 'false'
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-slider-main ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return $html . __('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html .= '<div class="repocean-content-wrapper" style="visibility:hidden;"><div class="repocean-slider-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = esc_html($review['rating'] ?? '');
                    $has_photos = !empty($this->repocean_get_review_photos($review));
                    if ($has_photos) {
                        $read_more_class = 'max1line';
                        $read_more_hide_show = !empty($text) ? 'show' : 'hide';
                    } else {
                        $read_more_class = 'max4line';
                        // Always render the button; the JS measures real overflow (scrollHeight
                        // vs clientHeight) and hides it when the text actually fits the card width.
                        $read_more_hide_show = 'show';
                    }
                    $html .= '<div class="slider-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="slider-box-inner" style="display:none;">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . $profile_photo_url . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="right-img">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--ratings: ' . $rating . '; --star-color: ' . $this->star_color . '">';
                    $html .= '<meta itemprop="ratingValue" content="' . $rating . '">';
                    $html .= '<meta itemprop="bestRating" content="5">';
                    $html .= '</div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div></div>';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="repocean-footer" style="display:none;">';
                    $html .= '<div class="repocean-rating-text" style="margin-bottom:15px;">';
                    $html .= '<span class="nowrap"><strong>' . __('Google', 'widgets-for-google-reviews-and-ratings') . '</strong> ' . __('Rating Score:', 'widgets-for-google-reviews-and-ratings') . ' </span>';
                    $html .= '<span class="nowrap"><strong>' . number_format($place_data['rating'] ?? 0, 1) . '</strong> ' . __('out of 5,', 'widgets-for-google-reviews-and-ratings') . ' </span>';
                    // Translators: %d represents the total number of user reviews.
                    $html .= '<span class="nowrap">' . __('based on', 'widgets-for-google-reviews-and-ratings') . ' <strong>' . ($place_data['user_ratings_total'] ?? 0) . ' ' . __('reviews', 'widgets-for-google-reviews-and-ratings') . '</strong></span>';
                    $html .= '</div>';
                    $html .= $this->repocean_display_verified_badge();
                    $html .= '</div>';
                }
                $html .= '</div>';
                return $html;
            } else {
                return "Your Google Place not connected";
            }
        } catch (Exception $ex) {
            
        }
    }

    /**
     * Returns the inline CSS custom-property fragment that controls how many
     * lines of review text are shown before "Read more". Applied to every
     * layout (default 5 lines, configurable 2-10 in the settings).
     *
     * Reviews with photos give up 3 lines to make room for the photo strip, so
     * they follow the same setting minus 3 (never below 1 line).
     */
    private function repocean_review_lines_style() {
        $lines = (int) $this->review_text_lines;
        $photo_lines = max(1, $lines - 3);
        return ' --repocean-lines:' . $lines . '; --repocean-lines-photo:' . $photo_lines . ';';
    }

    public function repocean_reviews_slider_v1_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                wp_enqueue_script($this->plugin_name . 'slider-v1', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider-v1.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-slider-v1', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider-v1.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'slider-v1', 'repocean_slider_js_v1', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings'),
                    'arrowVisibility' => $this->hide_prev_next_buttons === false ? 'true' : 'false'
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-slider-main-v1 ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return $html . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html .= '<div class="repocean-content-wrapper" style="visibility:hidden;">';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="review-sub-title" style="display:none;">';
                    $html .= '<div class="review-link">';
                    $html .= '<div class="excellent-text">';
                    $html .= '<strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong>';
                    $html .= '</div>';
                    $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--ratings: ' . $place_rating . '; --star-color: ' . $this->star_color . '"></div>';
                    $html .= '</div>';
                    $html .= '<div class="review-text excellent-text"><strong>' . esc_html($place_data['user_ratings_total'] ?? 0) . ' ' . __('reviews', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="gi-small-logo"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon" width="80"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '<div class="repocean-slider-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = esc_html($review['rating'] ?? '');
                    $has_photos = !empty($this->repocean_get_review_photos($review));
                    if ($has_photos) {
                        $read_more_class = 'max1line';
                        $read_more_hide_show = !empty($text) ? 'show' : 'hide';
                    } else {
                        $read_more_class = 'max4line';
                        // Always render the button; the JS measures real overflow (scrollHeight
                        // vs clientHeight) and hides it when the text actually fits the card width.
                        $read_more_hide_show = 'show';
                    }
                    $html .= '<div class="slider-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="slider-box-inner" style="display:none;">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . $profile_photo_url . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="right-img">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon" width: 20px; height: 20px;>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $rating . '; --star-color: ' . $this->star_color . '">';
                    $html .= '<meta itemprop="ratingValue" content="' . $rating . '">';
                    $html .= '<meta itemprop="bestRating" content="5">';
                    $html .= '</div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                if ($this->hide_review_us_button === false) {
                    $html .= '<div class="bottom-review-us"><a href="' . $this->review_url . '" class="bottom-review-us-btn" role="button" target="_blank" rel="noopener">' . __('Review us on Google', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                }
                $html .= $this->repocean_display_verified_badge();
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return __('Your Google Place not connected', 'widgets-for-google-reviews-and-ratings');
            }
        } catch (Exception $ex) {
            return __('An error occurred.', 'widgets-for-google-reviews-and-ratings');
        }
    }

    public function repocean_reviews_slider_v2_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                wp_enqueue_script($this->plugin_name . 'slider-v2', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider-v2.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-slider-v2', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider-v2.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'slider-v2', 'repocean_slider_js_v2', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings'),
                    'arrowVisibility' => $this->hide_prev_next_buttons === false ? 'true' : 'false'
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return '<div class="repocean-slider-main-v2">' . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html = '<div class="repocean-slider-main-v2 ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div class="repocean-content-wrapper" style="visibility:hidden;">';
                $html .= '<div class="slider-outer">';
                $html .= '<div class="SliderContentParent">';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="left">';
                    $html .= '<div class="left-inner" style="display:none;">';
                    $html .= '<div class="title"><strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    $html .= '<div class="star-content"><div class="review-box" style="--ratings: ' . esc_attr($place_data['rating'] ?? 0) . '; --star-color: ' . $this->star_color . '"></div></div>';
                    // Translators: %d represents the total number of user reviews.
                    $html .= '<div class="review-text"><p>' . sprintf(__('Based on <a href="#" class="review-link">%d reviews</a>', 'widgets-for-google-reviews-and-ratings'), esc_html($place_data['user_ratings_total'] ?? 0)) . '</p></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="bottom-logo"><div class="logo-wrapper"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-logo.svg" alt="Google_icon" width="150" height="25"></div></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    if ($this->hide_review_us_button === false) {
                        $html .= '<div class="bottom-review-us"><a href="' . $this->review_url . '" class="bottom-review-us-btn" role="button" target="_blank" rel="noopener">' . __('Review us on Google', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                    }
                    $html .= '</div></div>';
                    $html .= '<div class="right">';
                } else {
                    $html .= '<div class="right" style="width:100%;">';
                }


                $html .= '<div class="slider-box-parent slider-start">';

                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 18, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = esc_attr($review['rating'] ?? 0);
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $has_photos = !empty($this->repocean_get_review_photos($review));
                    if ($has_photos) {
                        $read_more_class = 'max1line';
                        $read_more_hide_show = !empty($text) ? 'show' : 'hide';
                    } else {
                        $read_more_class = 'max4line';
                        // Always render the button; the JS measures real overflow (scrollHeight
                        // vs clientHeight) and hides it when the text actually fits the card width.
                        $read_more_hide_show = 'show';
                    }
                    $html .= '<div class="slider-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="slider-box-inner" style="display:none;">';

                    // Reviewer info
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img"><img src="' . $profile_photo_url . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title"><h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6></div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="right-img"><img src="' . WGRR_ASSET_URL . 'public/css/images/Google_icon.svg" alt="Google_icon" width: 20px; height: 20px;></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div>'; // img-text-content
                    // Review content
                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $rating . '; --star-color: ' . $this->star_color . '">';
                    $html .= '<meta itemprop="ratingValue" content="' . $rating . '">';
                    $html .= '<meta itemprop="bestRating" content="5">';
                    $html .= '</div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div></div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="rep-button"><a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a></div></div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '</div>'; // slider-box-inner
                    $html .= '</div>'; // slider-box
                }

                $html .= '</div>'; // slider-box-parent
                $html .= '</div>'; // right
                $html .= '</div>'; // SliderContentParent
                $html .= '</div>'; // slider-outer
                $html .= $this->repocean_display_verified_badge();
                $html .= '</div>'; // repocean-content-wrapper
                $html .= '</div>'; // repocean-slider-main-v2

                return $html;
            } else {
                return '<div class="repocean-slider-main-v2">' . __('Your Google Place not connected', 'widgets-for-google-reviews-and-ratings') . '</div>';
            }
        } catch (Exception $ex) {
            return '<div class="repocean-slider-main-v2">' . __('An error occurred.', 'widgets-for-google-reviews-and-ratings') . '</div>';
        }
    }

    public function repocean_reviews_slider_v3_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                wp_enqueue_script($this->plugin_name . 'slider-v3', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider-v3.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-slider-v3', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider-v3.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'slider-v3', 'repocean_slider_js_v3', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings'),
                    'arrowVisibility' => $this->hide_prev_next_buttons === false ? 'true' : 'false'
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return '<div class="repocean-slider-main-v3">' . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html = '<div class="repocean-slider-main-v3 ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div class="repocean-content-wrapper" style="visibility:hidden;">';
                $html .= '<div class="slider-outer">';
                $html .= '<div class="SliderContentParent">';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="left">';
                    $html .= '<div class="left-inner" style="display:none;">';
                    $html .= '<div class="title"><strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    $html .= '<div class="star-content"><div class="review-box" style="--ratings: ' . esc_attr($place_data['rating'] ?? 0) . '; --star-color: ' . $this->star_color . '"></div></div>';
                    // Translators: %d represents the total number of user reviews.
                    $html .= '<div class="review-text"><p>' . sprintf(__('Based on <a href="#" class="review-link">%d reviews</a>', 'widgets-for-google-reviews-and-ratings'), esc_html($place_data['user_ratings_total'] ?? 0)) . '</p></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="bottom-logo"><div class="logo-wrapper"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-logo.svg" alt="Google_icon" width="150" height="25"></div></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div></div>';
                    $html .= '<div class="right">';
                } else {
                    $html .= '<div class="right" style="width=100%">';
                }

                // Right section (reviews slider)

                $html .= '<div class="slider-box-parent slider-start">';

                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = esc_attr($review['rating'] ?? 0);
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $has_photos = !empty($this->repocean_get_review_photos($review));
                    if ($has_photos) {
                        $read_more_class = 'max1line';
                        $read_more_hide_show = !empty($text) ? 'show' : 'hide';
                    } else {
                        $read_more_class = 'max4line';
                        // Always render the button; the JS measures real overflow (scrollHeight
                        // vs clientHeight) and hides it when the text actually fits the card width.
                        $read_more_hide_show = 'show';
                    }
                    $html .= '<div class="slider-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="slider-box-inner" style="display:none;">';

                    // Reviewer info
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img"><img src="' . $profile_photo_url . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title"><h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6></div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . esc_html($published_date) . '</p></div>';
                    }
                    $html .= '</div></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="right-img"><img src="' . WGRR_ASSET_URL . 'public/css/images/Google_icon.svg" alt="Google_icon" width: 20px; height: 20px;></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div>'; // img-text-content
                    // Review content
                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $rating . '; --star-color: ' . $this->star_color . '">';
                    $html .= '<meta itemprop="ratingValue" content="' . $rating . '">';
                    $html .= '<meta itemprop="bestRating" content="5">';
                    $html .= '</div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div></div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="rep-button"><a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a></div></div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '</div>'; // slider-box-inner
                    $html .= '</div>'; // slider-box
                }

                $html .= '</div>'; // slider-box-parent
                $html .= '</div>'; // right
                $html .= '</div>'; // SliderContentParent
                $html .= '</div>'; // slider-outer
                if ($this->hide_review_us_button === false) {
                    $html .= '<div class="bottom-review-us"><a href="' . $this->review_url . '" class="bottom-review-us-btn" role="button" target="_blank" rel="noopener">' . __('Review us on Google', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                }
                $html .= $this->repocean_display_verified_badge();
                $html .= '</div>'; // repocean-content-wrapper
                $html .= '</div>'; // repocean-slider-main-v3

                return $html;
            } else {
                return '<div class="repocean-slider-main-v3">' . __('Your Google Place not connected', 'widgets-for-google-reviews-and-ratings') . '</div>';
            }
        } catch (Exception $ex) {
            return '<div class="repocean-slider-main-v3">' . __('An error occurred.', 'widgets-for-google-reviews-and-ratings') . '</div>';
        }
    }

    public function repocean_reviews_slider_v4_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                wp_enqueue_script($this->plugin_name . 'slider-v4', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-slider-v4.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-slider-v4', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-slider-v4.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'slider-v4', 'repocean_slider_js_v4', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings'),
                    'arrowVisibility' => $this->hide_prev_next_buttons === false ? 'true' : 'false'
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return '<div class="slider-main repocean-slider-main-v4">' . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html = '<div class="slider-main repocean-slider-main-v4 ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div class="repocean-content-wrapper" style="visibility:hidden;">';
                if ($this->repocean_hide_rating_text === false) {
                    $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                    $html .= '<div class="review-sub-title" style="display:none;">';
                    $html .= '<div class="review-link">';
                    $html .= '<div class="excellent-text"><strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" style="--ratings: ' . $place_rating . '; --star-color: ' . $this->star_color . '"></div>';
                    $html .= '</div>';
                    $html .= '<div class="review-text excellent-text"><strong>' . esc_html($place_data['user_ratings_total'] ?? 0) . ' ' . __('reviews', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="gi-small-logo"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon" width="80"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '<div class="slider-outer">';
                $html .= '<div class="SliderContentParent">';
                $html .= '<div class="slider-box-parent slider-start">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = esc_attr($review['rating'] ?? 0);
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $has_photos = !empty($this->repocean_get_review_photos($review));
                    if ($has_photos) {
                        $read_more_class = 'max1line';
                        $read_more_hide_show = !empty($text) ? 'show' : 'hide';
                    } else {
                        $read_more_class = 'max4line';
                        // Always render the button; the JS measures real overflow (scrollHeight
                        // vs clientHeight) and hides it when the text actually fits the card width.
                        $read_more_hide_show = 'show';
                    }
                    $html .= '<div class="slider-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="slider-box-inner" style="display:none;">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img"><img src="' . $profile_photo_url . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title"><h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6></div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . esc_html($published_date) . '</p></div>';
                    }
                    $html .= '</div></div></div>'; // img-text-content
                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $rating . '; --star-color: ' . $this->star_color . '">';
                    $html .= '<meta itemprop="ratingValue" content="' . $rating . '">';
                    $html .= '<meta itemprop="bestRating" content="5">';
                    $html .= '</div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div></div>'; // review-content
                    // Description
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';

                    $html .= '<div class="rep-button"><a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                    $html .= '</div>'; // button-content
                    $html .= $this->repocean_render_review_photos($review);

                    $html .= '</div>'; // slider-box-inner
                    $html .= '</div>'; // slider-box
                }

                $html .= '</div>'; // slider-box-parent
                $html .= '</div>'; // SliderContentParent
                $html .= '</div>'; // slider-outer
                if ($this->hide_review_us_button === false) {
                    $html .= '<div class="bottom-review-us"><a href="' . $this->review_url . '" class="bottom-review-us-btn" role="button" target="_blank" rel="noopener">' . __('Review us on Google', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                }
                $html .= $this->repocean_display_verified_badge();
                $html .= '</div>'; // content-wrapper
                $html .= '</div>'; // slider-main

                return $html;
            } else {
                return '<div class="slider-main repocean-slider-main-v4">' . __('Your Google Place not connected', 'widgets-for-google-reviews-and-ratings') . '</div>';
            }
        } catch (Exception $ex) {
            return '<div class="slider-main slider-TopImg-main">' . __('An error occurred.', 'widgets-for-google-reviews-and-ratings') . '</div>';
        }
    }

    public function repocean_reviews_grid_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . 'grid', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-grid.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-grid', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-grid.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'grid', 'repocean_grid_js', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings')
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-grid-main ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return '<div class="repocean-grid-main">' . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html .= '<div class="content-wrapper">';
                $html .= '<div class="grid-outer">';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="review-sub-title">';
                    $html .= '<div class="review-link">';
                    $html .= '<div class="excellent-text">';
                    $html .= '<strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong>';
                    $html .= '</div>';
                    $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $place_rating . '; --star-color: ' . $this->star_color . '"></div>';

                    $html .= '</div>';
                    $html .= '<div class="review-text excellent-text"><strong>' . esc_html($place_data['user_ratings_total'] ?? 0) . ' ' . __('reviews', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="gi-small-logo"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon" width="80"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div>';
                    $html .= '</div>'; // review-sub-title
                }
                $html .= '<div class="grid-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $has_photos = !empty($this->repocean_get_review_photos($review));
                    if ($has_photos) {
                        $read_more_class = 'max1line';
                        $read_more_hide_show = !empty($text) ? 'show' : 'hide';
                    } else {
                        $read_more_class = 'max4line';
                        // Always render the button; the JS measures real overflow (scrollHeight
                        // vs clientHeight) and hides it when the text actually fits the card width.
                        $read_more_hide_show = 'show';
                    }
                    $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                    $html .= '<div class="grid-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="grid-box-inner">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . esc_url($profile_photo_url) . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="right-img">';
                        $html .= '<div class="right-img-wrapper">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon" width: 20px; height: 20px;>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" style="--rating: ' . $rating . '; --star-color: ' . $this->star_color . '"></div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '<button id="loadMore" class="load-more-btn">' . __('Load More', 'widgets-for-google-reviews-and-ratings') . '</button>';
                if ($this->hide_review_us_button === false) {
                    $html .= '<div class="bottom-review-us"><a href="' . $this->review_url . '" class="bottom-review-us-btn" role="button" target="_blank" rel="noopener">' . __('Review us on Google', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                }
                $html .= '</div>';
                $html .= $this->repocean_display_verified_badge();
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return __('Your Google Place not connected', 'widgets-for-google-reviews-and-ratings');
            }
        } catch (Exception $ex) {
            return __('An error occurred.', 'widgets-for-google-reviews-and-ratings');
        }
    }

    public function repocean_reviews_list_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . 'list', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-list.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-list', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-list.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . 'list', 'repocean_list_js', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings')
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-list-main ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return '<div class="repocean-list-main">' . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html .= '<div class="content-wrapper">';
                $html .= '<div class="list-outer">';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="review-sub-title">';
                    $html .= '<div class="review-link">';
                    $html .= '<div class="excellent-text">';
                    $html .= '<strong>' . __('EXCELLENT', 'widgets-for-google-reviews-and-ratings') . '</strong>';
                    $html .= '</div>';
                    $html .= '<div class="review-box-parent">';
                    $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $place_rating . '; --star-color: ' . $this->star_color . '"></div>';
                    $html .= '</div>';
                    $html .= '<div class="review-text excellent-text"><strong>' . esc_html($place_data['user_ratings_total'] ?? 0) . ' ' . __('reviews', 'widgets-for-google-reviews-and-ratings') . '</strong></div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="gi-small-logo"><img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon" width="80"></div>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    }
                    $html .= '</div>';
                    $html .= '</div>'; // review-sub-title
                }
                $html .= '<div class="list-box-parent">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    // Always render the button; the JS measures real overflow (scrollHeight
                    // vs clientHeight) and hides it when the text actually fits the collapsed box.
                    $read_more_hide_show = 'show';
                    $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                    // Reviews with photos show 3 fewer lines of text (see repocean_review_lines_style).
                    $photo_class = !empty($this->repocean_get_review_photos($review)) ? ' has-photos' : '';
                    $html .= '<div class="list-box" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="list-box-inner">';
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . esc_url($profile_photo_url) . '" alt="Reviewer Image" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date"><p>' . $published_date . '</p></div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    if ($this->hide_google_logo === false) {
                        $html .= '<div class="right-img">';
                        $html .= '<div class="right-img-wrapper">';
                        $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-icon.svg" alt="Google_icon" width: 20px; height: 20px;>'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="review-content">';
                    $html .= '<div class="review-box-parent">';
                    $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $rating . '; --star-color: ' . $this->star_color . '"></div>';
                    if ($this->show_verified_symbol) {
                        $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="description' . $photo_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '<button id="loadMoreList" class="load-more-btn">' . __('Load More', 'widgets-for-google-reviews-and-ratings') . '</button>';
                if ($this->hide_review_us_button === false) {
                    $html .= '<div class="bottom-review-us"><a href="' . $this->review_url . '" class="bottom-review-us-btn" role="button" target="_blank" rel="noopener">' . __('Review us on Google', 'widgets-for-google-reviews-and-ratings') . '</a></div>';
                }
                $html .= '</div>';
                $html .= $this->repocean_display_verified_badge();
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return __('Your Google Place not connected', 'widgets-for-google-reviews-and-ratings');
            }
        } catch (Exception $ex) {
            return __('An error occurred.', 'widgets-for-google-reviews-and-ratings');
        }
    }

    public function repocean_reviews_sidebar_shortcode($atts) {
        try {
            if (!empty($this->place_details)) {
                $limit = isset($atts['limit']) ? $atts['limit'] : 100;
                wp_enqueue_script($this->plugin_name . '-slick-carousel-js', plugin_dir_url(__FILE__) . 'js/slick.min.js', ['jquery'], '1.8.1', true);
                wp_enqueue_script($this->plugin_name . '-sidebar', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-sidebar.js', ['jquery'], $this->version, true);
                wp_enqueue_style($this->plugin_name . '-sidebar', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-sidebar.css', [], $this->version, 'all');
                wp_localize_script($this->plugin_name . '-sidebar', 'repocean_sidebar_js', array(
                    'hide' => __('Hide', 'widgets-for-google-reviews-and-ratings'),
                    'read_more' => __('Read more', 'widgets-for-google-reviews-and-ratings'),
                    'arrowVisibility' => $this->hide_prev_next_buttons === false ? 'true' : 'false'
                ));
                $place_data = $this->place_details;
                $address_components = $place_data['address_components'] ?? [];
                $streetAddress = '';
                $addressLocality = '';
                $addressRegion = '';
                $postalCode = '';
                $addressCountry = '';
                foreach ($address_components as $component) {
                    if (in_array('street_number', $component['types'])) {
                        $streetAddress = $component['long_name'];
                    }
                    if (in_array('route', $component['types'])) {
                        $streetAddress .= ' ' . $component['long_name'];
                    }
                    if (in_array('locality', $component['types'])) {
                        $addressLocality = $component['long_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $addressRegion = $component['short_name'];
                    }
                    if (in_array('postal_code', $component['types'])) {
                        $postalCode = $component['long_name'];
                    }
                    if (in_array('country', $component['types'])) {
                        $addressCountryFull = $component['long_name'];
                        $addressCountry = $this->get_country_code($addressCountryFull);
                    }
                }
                $formatted_phone_number = $place_data['formatted_phone_number'] ?? '';
                $html = '<div class="repocean-sidebar-main ' . esc_attr($this->enable_dark_mode_class) . '" itemscope itemtype="https://schema.org/LocalBusiness" style="--repocean-bg:' . esc_attr($this->bg_color) . '; --repocean-border:' . esc_attr($this->border_color) . '; --repocean-radius:' . (int) $this->card_border_radius . 'px;' . $this->repocean_review_lines_style() . '">';
                $html .= '<meta itemprop="name" content="' . esc_attr($place_data['name'] ?? '') . '">';
                $html .= '<meta itemprop="telephone" content="' . esc_attr($formatted_phone_number) . '">';
                $html .= '<meta itemprop="image" content="' . esc_attr($place_data['icon'] ?? '') . '">';
                $html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
                $html .= '<meta itemprop="streetAddress" content="' . esc_attr($streetAddress) . '">';
                $html .= '<meta itemprop="addressLocality" content="' . esc_attr($addressLocality) . '">';
                $html .= '<meta itemprop="addressRegion" content="' . esc_attr($addressRegion) . '">';
                $html .= '<meta itemprop="postalCode" content="' . esc_attr($postalCode) . '">';
                $html .= '<meta itemprop="addressCountry" content="' . esc_attr($addressCountry) . '">';
                $html .= '</div>';
                $html .= '<div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
                $html .= '<meta itemprop="latitude" content="' . esc_attr($place_data['geometry']['location']['lat'] ?? '') . '">';
                $html .= '<meta itemprop="longitude" content="' . esc_attr($place_data['geometry']['location']['lng'] ?? '') . '">';
                $html .= '</div>';
                $html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                $html .= '<meta itemprop="ratingValue" content="' . esc_attr($place_data['rating'] ?? '') . '">';
                $html .= '<meta itemprop="bestRating" content="5">';
                $html .= '<meta itemprop="reviewCount" content="' . esc_attr($place_data['user_ratings_total'] ?? '') . '">';
                $html .= '</div>';
                $reviews = $this->repocean_filter_reviews($this->repocean_get_google_reviews());
                if (empty($reviews)) {
                    return '<div class="repocean-sidebar-main">' . esc_html__('No reviews found.', 'widgets-for-google-reviews-and-ratings') . '</div>';
                }
                $html .= '<div class="content-wrapper">';
                $html .= '<div class="slider-outer">';
                $html .= '<div class="slider-box-parent">';
                $html .= '<div class="slider-box">';
                $html .= '<div class="slider-box-inner">';
                $html .= '<div class="top-part">';
                if ($this->repocean_hide_rating_text === false) {
                    $html .= '<div class="top-text-content">';
                    $html .= '<strong class="gi-rating"> ' . __('Excellent rating', 'widgets-for-google-reviews-and-ratings') . ' </strong>';
                    // Translators: %d represents the total number of user reviews.
                    $html .= '<span class="nowrap">' . sprintf(__('Based on %s reviews', 'widgets-for-google-reviews-and-ratings'), esc_attr($place_data['user_ratings_total'] ?? '0')) . '</span>';
                    $html .= '</div>';
                }
                $html .= '<div class="img-content">';
                if ($this->hide_google_logo === false) {
                    $html .= '<div class="google-logo">';
                    $html .= '<img src="' . WGRR_ASSET_URL . 'public/css/images/google-brand.png" alt="Google_icon">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                    $html .= '</div>';
                }
                $html .= '<div class="review-box-parent">';
                $place_rating = isset($place_data['rating']) ? floatval($place_data['rating']) : 0;
                $html .= '<div class="review-box" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="--rating: ' . $place_rating . '; --star-color: ' . $this->star_color . '"></div>';
                if ($this->show_verified_symbol) {
                    $html .= '<span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span></span>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="bottom-part slider-start">';
                $reviews = array_slice($reviews, 0, $limit);
                foreach ($reviews as $review) {
                    // Translators: %s represents the human-readable time difference.
                    $published_date = !empty($review['published_date']) ? sprintf(__('%s ago', 'widgets-for-google-reviews-and-ratings'), human_time_diff($review['published_date'] / 1000000)) : '';
                    $author_name = esc_html($review['author_name'] ?? '');
                    $author_name = $this->shorten_reviewer_name($author_name);
                    $author_name = mb_strimwidth($author_name, 0, 25, '...');
                    $profile_photo_url = esc_url($review['profile_photo_url'] ?? '');
                    $text = esc_html($review['text'] ?? '');
                    $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                    $read_more_class = (strlen($text) > 170) ? 'max63' : '';
                    // Reviews with photos show 3 fewer lines of text (see repocean_review_lines_style).
                    if (!empty($this->repocean_get_review_photos($review))) {
                        $read_more_class = trim($read_more_class . ' has-photos');
                    }
                    // Always render the button; the JS measures real overflow (scrollHeight
                    // vs clientHeight) and hides it when the text actually fits the collapsed box.
                    $read_more_hide_show = 'show';
                    $html .= '<div class="bottom-part-inner" itemprop="review" itemscope itemtype="https://schema.org/Review">';
                    $html .= '<div class="bottom-part-inner-second">';
                    $html .= '<div class="description ' . $read_more_class . '" itemprop="reviewBody">';
                    $html .= '<p>' . $text . '</p>';
                    $html .= '</div>';
                    $html .= '<div class="button-content ' . $read_more_hide_show . '">';
                    $html .= '<div class="readmore-button">';
                    $html .= '<a href="#">' . __('Read more', 'widgets-for-google-reviews-and-ratings') . '</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= $this->repocean_render_review_photos($review);
                    $html .= '<div class="img-text-content">';
                    $html .= '<div class="profile-img-info">';
                    if ($this->hide_profile_picture === false) {
                        $html .= '<div class="profile-img">';
                        $html .= '<img src="' . $profile_photo_url . '" alt="Profile image of ' . $author_name . '" itemprop="image" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( WGRR_ASSET_URL . 'admin/image/bussiness-logo.png' ) . '\'">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
                        $html .= '</div>';
                    }
                    $html .= '<div class="profile-info">';
                    $html .= '<div class="profile-title">';
                    $html .= '<h6 itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $author_name . '</span></h6>';
                    $html .= '</div>';
                    if ($this->hide_date === false) {
                        $html .= '<div class="profile-date">';
                        $html .= '<p>' . $published_date . '</p>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';

                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            } else {
                return __('Your Google Place is not connected.', 'widgets-for-google-reviews-and-ratings');
            }
        } catch (Exception $ex) {
            return __('An error occurred.', 'widgets-for-google-reviews-and-ratings');
        }
    }

    private function repocean_get_review_photos($review) {
        if ($this->hide_review_photos) {
            return array();
        }

        $review_photos = $review['review_photos'] ?? array();

        if (is_string($review_photos)) {
            $decoded_review_photos = json_decode($review_photos, true);
            $review_photos = is_array($decoded_review_photos) ? $decoded_review_photos : array();
        }

        if (!is_array($review_photos)) {
            return array();
        }

        $review_photos = array_map('esc_url', array_filter($review_photos));

        return array_slice($review_photos, 0, 4);
    }

    private function repocean_render_review_photos($review) {
        $review_photos = $this->repocean_get_review_photos($review);

        if (empty($review_photos)) {
            return '';
        }

        // Lightbox assets — enqueued only when a review actually has photos.
        wp_enqueue_style($this->plugin_name . '-lightbox', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-lightbox.css', [], $this->version, 'all');
        wp_enqueue_script($this->plugin_name . '-lightbox', plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-lightbox.js', ['jquery'], $this->version, true);

        $html = '<div class="repocean-review-photos">';

        foreach ($review_photos as $review_photo) {
           
            // If the photo URL fails to load, drop the element entirely so no broken-image
            // icon shows and it stays out of the lightbox (which reads thumbs at click time).
            $html .= '<img src="' . $review_photo . '" alt="Review photo" referrerpolicy="no-referrer" onerror="this.remove()">'; /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
            
        }

        $html .= '</div>';

        return $html;
    }

    public function update_google_place_review_count() {
        try {
            $response_review_count = wp_remote_get($this->update_review_count);
            if (!is_wp_error($response_review_count)) {
                $data_review_count = wp_remote_retrieve_body($response_review_count);
                $review_count_data = json_decode($data_review_count, true);
                if (isset($review_count_data['review_count'])) {
                    $this->place_details['user_ratings_total'] = $review_count_data['review_count'];
                    update_option('wgrr_g_place_details', $this->place_details);
                }
            }
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function get_country_code($countryName) {
        $countries = $this->repocean_get_country_shortcode();
        $countryName = strtolower($countryName);
        foreach ($countries as $name => $code) {
            if (strtolower($name) === $countryName) {
                return $code;
            }
        }

        return $countryName;
    }

    public function repocean_get_country_shortcode() {
        return array(
            'Afghanistan' => 'AF',
            'Åland Islands' => 'AX',
            'Albania' => 'AL',
            'Algeria' => 'DZ',
            'American Samoa' => 'AS',
            'Andorra' => 'AD',
            'Angola' => 'AO',
            'Anguilla' => 'AI',
            'Antarctica' => 'AQ',
            'Antigua and Barbuda' => 'AG',
            'Argentina' => 'AR',
            'Armenia' => 'AM',
            'Aruba' => 'AW',
            'Australia' => 'AU',
            'Austria' => 'AT',
            'Azerbaijan' => 'AZ',
            'Bahamas' => 'BS',
            'Bahrain' => 'BH',
            'Bangladesh' => 'BD',
            'Barbados' => 'BB',
            'Belarus' => 'BY',
            'Belgium' => 'BE',
            'Belau' => 'PW',
            'Belize' => 'BZ',
            'Benin' => 'BJ',
            'Bermuda' => 'BM',
            'Bhutan' => 'BT',
            'Bolivia' => 'BO',
            'Bonaire, Saint Eustatius and Saba' => 'BQ',
            'Bosnia and Herzegovina' => 'BA',
            'Botswana' => 'BW',
            'Bouvet Island' => 'BV',
            'Brazil' => 'BR',
            'British Indian Ocean Territory' => 'IO',
            'Brunei' => 'BN',
            'Bulgaria' => 'BG',
            'Burkina Faso' => 'BF',
            'Burundi' => 'BI',
            'Cambodia' => 'KH',
            'Cameroon' => 'CM',
            'Canada' => 'CA',
            'Cape Verde' => 'CV',
            'Cayman Islands' => 'KY',
            'Central African Republic' => 'CF',
            'Chad' => 'TD',
            'Chile' => 'CL',
            'China' => 'CN',
            'Christmas Island' => 'CX',
            'Cocos (Keeling) Islands' => 'CC',
            'Colombia' => 'CO',
            'Comoros' => 'KM',
            'Congo (Brazzaville)' => 'CG',
            'Congo (Kinshasa)' => 'CD',
            'Cook Islands' => 'CK',
            'Costa Rica' => 'CR',
            'Croatia' => 'HR',
            'Cuba' => 'CU',
            'Curaçao' => 'CW',
            'Cyprus' => 'CY',
            'Czech Republic' => 'CZ',
            'Denmark' => 'DK',
            'Djibouti' => 'DJ',
            'Dominica' => 'DM',
            'Dominican Republic' => 'DO',
            'Ecuador' => 'EC',
            'Egypt' => 'EG',
            'El Salvador' => 'SV',
            'Equatorial Guinea' => 'GQ',
            'Eritrea' => 'ER',
            'Estonia' => 'EE',
            'Ethiopia' => 'ET',
            'Falkland Islands' => 'FK',
            'Faroe Islands' => 'FO',
            'Fiji' => 'FJ',
            'Finland' => 'FI',
            'France' => 'FR',
            'French Guiana' => 'GF',
            'French Polynesia' => 'PF',
            'French Southern Territories' => 'TF',
            'Gabon' => 'GA',
            'Gambia' => 'GM',
            'Georgia' => 'GE',
            'Germany' => 'DE',
            'Ghana' => 'GH',
            'Gibraltar' => 'GI',
            'Greece' => 'GR',
            'Greenland' => 'GL',
            'Grenada' => 'GD',
            'Guadeloupe' => 'GP',
            'Guam' => 'GU',
            'Guatemala' => 'GT',
            'Guernsey' => 'GG',
            'Guinea' => 'GN',
            'Guinea-Bissau' => 'GW',
            'Guyana' => 'GY',
            'Haiti' => 'HT',
            'Heard Island and McDonald Islands' => 'HM',
            'Honduras' => 'HN',
            'Hong Kong' => 'HK',
            'Hungary' => 'HU',
            'Iceland' => 'IS',
            'India' => 'IN',
            'Indonesia' => 'ID',
            'Iran' => 'IR',
            'Iraq' => 'IQ',
            'Ireland' => 'IE',
            'Isle of Man' => 'IM',
            'Israel' => 'IL',
            'Italy' => 'IT',
            'Ivory Coast' => 'CI',
            'Jamaica' => 'JM',
            'Japan' => 'JP',
            'Jersey' => 'JE',
            'Jordan' => 'JO',
            'Kazakhstan' => 'KZ',
            'Kenya' => 'KE',
            'Kiribati' => 'KI',
            'Kuwait' => 'KW',
            'Kyrgyzstan' => 'KG',
            'Laos' => 'LA',
            'Latvia' => 'LV',
            'Lebanon' => 'LB',
            'Lesotho' => 'LS',
            'Liberia' => 'LR',
            'Libya' => 'LY',
            'Liechtenstein' => 'LI',
            'Lithuania' => 'LT',
            'Luxembourg' => 'LU',
            'Macao' => 'MO',
            'North Macedonia' => 'MK',
            'Madagascar' => 'MG',
            'Malawi' => 'MW',
            'Malaysia' => 'MY',
            'Maldives' => 'MV',
            'Mali' => 'ML',
            'Malta' => 'MT',
            'Marshall Islands' => 'MH',
            'Martinique' => 'MQ',
            'Mauritania' => 'MR',
            'Mauritius' => 'MU',
            'Mayotte' => 'YT',
            'Mexico' => 'MX',
            'Micronesia' => 'FM',
            'Moldova' => 'MD',
            'Monaco' => 'MC',
            'Mongolia' => 'MN',
            'Montenegro' => 'ME',
            'Montserrat' => 'MS',
            'Morocco' => 'MA',
            'Mozambique' => 'MZ',
            'Myanmar' => 'MM',
            'Namibia' => 'NA',
            'Nauru' => 'NR',
            'Nepal' => 'NP',
            'Netherlands' => 'NL',
            'New Caledonia' => 'NC',
            'New Zealand' => 'NZ',
            'Nicaragua' => 'NI',
            'Niger' => 'NE',
            'Nigeria' => 'NG',
            'Niue' => 'NU',
            'Norfolk Island' => 'NF',
            'Northern Mariana Islands' => 'MP',
            'North Korea' => 'KP',
            'Norway' => 'NO',
            'Oman' => 'OM',
            'Pakistan' => 'PK',
            'Palestinian Territory' => 'PS',
            'Panama' => 'PA',
            'Papua New Guinea' => 'PG',
            'Paraguay' => 'PY',
            'Peru' => 'PE',
            'Philippines' => 'PH',
            'Pitcairn' => 'PN',
            'Poland' => 'PL',
            'Portugal' => 'PT',
            'Puerto Rico' => 'PR',
            'Qatar' => 'QA',
            'Reunion' => 'RE',
            'Romania' => 'RO',
            'Russia' => 'RU',
            'Rwanda' => 'RW',
            'Saint Barthélemy' => 'BL',
            'Saint Helena' => 'SH',
            'Saint Kitts and Nevis' => 'KN',
            'Saint Lucia' => 'LC',
            'Saint Martin' => 'MF',
            'Saint Pierre and Miquelon' => 'PM',
            'Saint Vincent and the Grenadines' => 'VC',
            'San Marino' => 'SM',
            'São Tomé and Príncipe' => 'ST',
            'Saudi Arabia' => 'SA',
            'Senegal' => 'SN',
            'Serbia' => 'RS',
            'Seychelles' => 'SC',
            'Sierra Leone' => 'SL',
            'Singapore' => 'SG',
            'Slovakia' => 'SK',
            'Slovenia' => 'SI',
            'Solomon Islands' => 'SB',
            'Somalia' => 'SO',
            'South Africa' => 'ZA',
            'South Georgia and the South Sandwich Islands' => 'GS',
            'South Korea' => 'KR',
            'South Sudan' => 'SS',
            'Spain' => 'ES',
            'Sri Lanka' => 'LK',
            'Sudan' => 'SD',
            'Suriname' => 'SR',
            'Svalbard and Jan Mayen' => 'SJ',
            'Eswatini' => 'SZ',
            'Sweden' => 'SE',
            'Switzerland' => 'CH',
            'Syria' => 'SY',
            'Taiwan' => 'TW',
            'Tajikistan' => 'TJ',
            'Tanzania' => 'TZ',
            'Thailand' => 'TH',
            'Timor-Leste' => 'TL',
            'Togo' => 'TG',
            'Tokelau' => 'TK',
            'Tonga' => 'TO',
            'Trinidad and Tobago' => 'TT',
            'Tunisia' => 'TN',
            'Turkey' => 'TR',
            'Turkmenistan' => 'TM',
            'Turks and Caicos Islands' => 'TC',
            'Tuvalu' => 'TV',
            'Uganda' => 'UG',
            'Ukraine' => 'UA',
            'United Arab Emirates' => 'AE',
            'United Kingdom' => 'GB',
            'United Kingdom (UK)' => 'GB',
            'United States' => 'US',
            'United States of America' => 'US',
            'United States (US)' => 'US',
            'United States Minor Outlying Islands' => 'UM',
            'Uruguay' => 'UY',
            'Uzbekistan' => 'UZ',
            'Vanuatu' => 'VU',
            'Vatican' => 'VA',
            'Venezuela' => 'VE',
            'Vietnam' => 'VN',
            'Virgin Islands (British)' => 'VG',
            'Virgin Islands (US)' => 'VI',
            'Wallis and Futuna' => 'WF',
            'Western Sahara' => 'EH',
            'Samoa' => 'WS',
            'Yemen' => 'YE',
            'Zambia' => 'ZM',
            'Zimbabwe' => 'ZW',
        );
    }

    public function shorten_reviewer_name($full_name) {
        $short_name = trim($full_name);
        if ($this->shorten_reviewer_names) {
            $name_parts = explode(' ', $short_name);
            if (count($name_parts) > 1) {
                $short_name = $name_parts[0] . ' ' . substr(end($name_parts), 0, 1) . '.';
            }
        }
        return (strlen($short_name) > 22) ? substr($short_name, 0, 20) . '...' : $short_name;
    }
    
    private function repocean_filter_reviews($reviews) {
        return array_values(array_filter($reviews, function ($r) {
            if ($this->min_star_rating > 0 && floatval($r['rating'] ?? 0) < $this->min_star_rating) {
                return false;
            }
            if ($this->hide_empty_reviews && trim($r['text'] ?? '') === '') {
                return false;
            }
            return true;
        }));
    }

    public function repocean_get_google_reviews() {
        $transient_key = 'repocean_google_review';
        $option_key = 'repocean_google_review_fallback';
        $reviews = get_transient($transient_key);
        if ($reviews === false) {
            $response = wp_remote_get($this->get_review_url);
            if (is_wp_error($response)) {
                $reviews = get_option($option_key, []);
                if (empty($reviews)) {
                    return __('Unable to retrieve reviews at this time.', 'widgets-for-google-reviews-and-ratings');
                }
                return $reviews;
            }
            $data = wp_remote_retrieve_body($response);
            $reviews = json_decode($data, true);
            if (empty($reviews)) {
                $reviews = get_option($option_key, []);
                if (empty($reviews)) {
                    return __('No reviews found.', 'widgets-for-google-reviews-and-ratings');
                }
                return $reviews;
            }
            set_transient($transient_key, $reviews, 30 * MINUTE_IN_SECONDS);
            update_option($option_key, $reviews);
            do_action('repocean_update_review_count');
        }
        return $reviews;
    }

    public function repocean_display_verified_badge() {
        $repocean_show_verified_by_default_option = ($this->repocean_get_user_status() === 'existing') ? 'no' : 'yes';
        $repocean_show_verified_by = get_option('repocean_show_verified_by', $repocean_show_verified_by_default_option);
        if ($repocean_show_verified_by === 'yes') {
            wp_enqueue_style($this->plugin_name . '-verified-badge', plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-verified-badge.css', [], $this->version, 'all');
            return '<div class="rep-verified-badge-container">
            <div class="verified-badge">
                <img src="' . WGRR_ASSET_URL . 'public/css/images/rep-based-verified.svg" alt="' . esc_attr__('Verified', 'widgets-for-google-reviews-and-ratings') . '" width="18" height="18" class="verified-badge__icon">
                <span class="verified-badge__tooltip">' . __('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings') . '</span>
                <span><a target="_blank" href="https://repocean.com/" class="verified-badge__link">' . __('Verified by RepOcean', 'widgets-for-google-reviews-and-ratings') . '</a></span>
            </div>
        </div>';
        }
    }

    public function repocean_get_user_status() {
        $activation_time = get_option('repocean_activation_time');
        $release_date = strtotime('2025-03-19 00:00:00');
        if ($activation_time && $activation_time < $release_date) {
            return 'existing';
        } else {
            return 'new';
        }
    }
    
    public function repocean_get_user_status_4_july() {
        $activation_time = get_option('repocean_activation_time', false);
        $release_date = strtotime('2025-07-04 00:00:00');
        if (!$activation_time || $activation_time >= $release_date) {
            return 'new';
        }
        return 'existing';
    }
}
