<?php

class Widgets_For_Google_Reviews_And_Ratings_i18n {

    public function load_plugin_textdomain() {

        $domain = 'widgets-for-google-reviews-and-ratings';
        $locale = determine_locale();

        // Prefer the translate.wordpress.org translation per-string and fall back to the
        // bundled one for the rest. WordPress resolves a string from the first loaded file
        // that has it, so the community file must load before the bundled file. (Order matters:
        // since WP 6.7 load_plugin_textdomain() only registers the path, so we load explicitly.)

        // translate.wordpress.org translation (load_textdomain() prefers the .l10n.php variant).
        $community = WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale;
        if (is_readable($community . '.l10n.php') || is_readable($community . '.mo')) {
            load_textdomain($domain, $community . '.mo', $locale);
        }

        // Register the plugin's own /languages dir for just-in-time loading.
        load_plugin_textdomain(
                $domain,
                false,
                plugin_basename(dirname(WGRR_PLUGIN_FILE)) . '/languages'
        );

        // Bundled translation: fallback for any string the community file lacks.
        $bundled = WGRR_PLUGIN_DIR . '/languages/' . $domain . '-' . $locale . '.mo';
        if (is_readable($bundled)) {
            load_textdomain($domain, $bundled, $locale);
        }
    }
}
