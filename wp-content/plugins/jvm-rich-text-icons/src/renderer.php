<?php
if (!defined("ABSPATH")) {
    exit();
}

/**
 * Central CSS renderer for icon mask-image styling.
 * Used by both the free plugin (custom SVG uploads) and the pro plugin (pro icon sets).
 *
 * All methods are static and do not use output buffering, making them safe
 * to call from inside ob_start() callbacks.
 */
class JVM_RTI_Renderer
{
    /**
     * Clean raw SVG for inline use: collapse whitespace and strip
     * width/height from the root <svg> tag only (preserving them on child elements).
     */
    public static function clean_svg($svg)
    {
        $svg = preg_replace("/\s+/", " ", $svg);
        $svg = trim($svg);
        $svg = preg_replace_callback(
            '/<svg\b[^>]*>/i',
            function ($m) {
                return preg_replace('/\s(?:width|height)="[^"]*"/', '', $m[0]);
            },
            $svg,
            1
        );
        return $svg;
    }

    /**
     * Generate base CSS for icon elements.
     *
     * @param string|null $technology  html-css | html-css-before | html-css-after (default: from settings)
     * @return string CSS
     */
    public static function base_css($technology = null)
    {
        if ($technology === null) {
            $settings = JVM_Richtext_icons::get_settings();
            $technology = isset($settings["technology"]) ? $settings["technology"] : "html-css";
        }

        $p = JVM_Richtext_icons::get_class_prefix();
        $css = ".wp-block {\n    /* Fixes the iframe site editor */\n}\n";
        if ($technology === "html-css-before" || $technology === "html-css-after") {
            $pseudo = ($technology === "html-css-before") ? "before" : "after";

            $css .= "i." . $p . " {\n";
            $css .= "    width: 1em;\n";
            $css .= "    display: inline-block;\n";
            $css .= "    height: 1em;\n";
            $css .= "    position: relative;\n";
            $css .= "    white-space: break-spaces;\n";
            $css .= "    line-height: 1;\n";
            $css .= "}\n";

            $css .= "i." . $p . ":" . $pseudo . " {\n";
            $css .= "    content: '';\n";
            $css .= "    position: absolute;\n";
            $css .= "    height: 100%;\n";
            $css .= "    width: 100%;\n";
            $css .= "    top: 0;\n";
            $css .= "    left: 0;\n";
            $css .= "    background-color: currentColor;\n";
            $css .= "    mask-repeat: no-repeat;\n";
            $css .= "    -webkit-mask-repeat: no-repeat;\n";
            $css .= "    mask-size: contain;\n";
            $css .= "    -webkit-mask-size: contain;\n";
            $css .= "    mask-position: 50% 50%;\n";
            $css .= "    -webkit-mask-position: 50% 50%;\n";
            $css .= "}\n";
        } else {
            $css .= "i." . $p . " {\n";
            $css .= "    width: 1em;\n";
            $css .= "    display: inline-block;\n";
            $css .= "    height: 1em;\n";
            $css .= "    background-color: currentColor;\n";
            $css .= "    mask-repeat: no-repeat;\n";
            $css .= "    -webkit-mask-repeat: no-repeat;\n";
            $css .= "    mask-size: contain;\n";
            $css .= "    -webkit-mask-size: contain;\n";
            $css .= "    mask-position: 0% 50%;\n";
            $css .= "    -webkit-mask-position: 0% 50%;\n";
            $css .= "    white-space: break-spaces;\n";
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Generate a CSS rule for a single icon.
     *
     * @param string      $class      CSS class or selector suffix.
     *                                 Simple class: "my-icon" → selector "i.icon.my-icon"
     *                                 Selector suffix (starts with "."): ".heroicons.solid.arrow-up" → "i.icon.heroicons.solid.arrow-up"
     * @param string      $svg        Raw SVG content
     * @param string|null $technology html-css | html-css-before | html-css-after (default: from settings)
     * @param float       $ratio      Width/height ratio (default 1, used for non-square custom SVGs)
     * @return string CSS rule
     */
    public static function icon_css($class, $svg, $technology = null, $ratio = 1)
    {
        if ($technology === null) {
            $settings = JVM_Richtext_icons::get_settings();
            $technology = isset($settings["technology"]) ? $settings["technology"] : "html-css";
        }

        $p = JVM_Richtext_icons::get_class_prefix();
        $b64 = base64_encode($svg);
        $css = "";

        // Build the selector: if $class starts with "." it's already a selector suffix,
        // otherwise treat it as a single class name.
        if (strpos($class, ".") === 0) {
            $selector = "i." . $p . $class;
        } else {
            $selector = "i." . $p . "." . $class;
        }

        if ($technology === "html-css-before" || $technology === "html-css-after") {
            $pseudo = ($technology === "html-css-before") ? "before" : "after";
            $css .= $selector . ":" . $pseudo . " {\n";
            $css .= "    --icon-bg: url(\"data:image/svg+xml;base64," . $b64 . "\");\n";
            $css .= "    -webkit-mask-image: var(--icon-bg);\n";
            $css .= "    mask-image: var(--icon-bg);\n";
            $css .= "}\n";
        } else {
            $css .= $selector . " {\n";
            if ($ratio != 1) {
                $css .= "    width: " . $ratio . "em;\n";
            }
            $css .= "    --icon-bg: url(\"data:image/svg+xml;base64," . $b64 . "\");\n";
            $css .= "    -webkit-mask-image: var(--icon-bg);\n";
            $css .= "    mask-image: var(--icon-bg);\n";
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Generate complete CSS: base styles + per-icon rules.
     *
     * @param array       $icons      [['class' => '...', 'svg' => '...', 'ratio' => 1.0], ...]
     *                                 'ratio' is optional, defaults to 1
     * @param string|null $technology html-css | html-css-before | html-css-after (default: from settings)
     * @return string Complete CSS
     */
    public static function generate_css($icons, $technology = null)
    {
        if ($technology === null) {
            $settings = JVM_Richtext_icons::get_settings();
            $technology = isset($settings["technology"]) ? $settings["technology"] : "html-css";
        }

        $css = self::base_css($technology);

        foreach ($icons as $icon) {
            $ratio = isset($icon["ratio"]) ? $icon["ratio"] : 1;
            $css .= self::icon_css($icon["class"], $icon["svg"], $technology, $ratio);
        }

        return $css;
    }
}
