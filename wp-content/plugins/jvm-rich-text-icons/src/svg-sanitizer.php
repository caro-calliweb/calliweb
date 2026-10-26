<?php
/**
 * SVG Sanitizer
 *
 * Security cleaning (always runs):
 *   - Strips XSS vectors: script, iframe, object, embed, link, style, foreignObject
 *   - Removes event-handler attributes (on*)
 *   - Strips editor namespace cruft (Inkscape, Sodipodi, dc, cc, rdf, xodm)
 *   - Removes metadata tags (metadata, title, desc)
 *   - Removes unsafe external references and href attributes
 *
 * Normalization (runs by default, disable with security_only option):
 *   - White-as-mask detection: converts dark+white sibling patterns to proper <mask> elements
 *   - Color normalization: replaces hardcoded fill/stroke with currentColor
 *   - ViewBox normalization: derives viewBox from width/height, strips width/height
 *   - Optional square viewBox padding
 *   - ID cleanup: removes or prefixes IDs to prevent DOM collisions
 *   - Empty group removal
 *
 * Usage:
 *   $sanitizer = new JVM_RTI_SVG_Sanitizer(['square_viewbox' => true]);
 *   $result    = $sanitizer->process($raw_svg_string);
 *
 *   if ($result->has_error()) {
 *       $error = $result->get_error(); // WP_Error
 *   } else {
 *       echo $result->svg;               // clean SVG string
 *       print_r($result->warnings);      // array of warning strings
 *       echo $result->masks_converted;   // number of white-mask conversions
 *   }
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------------

class JVM_RTI_SVG_Sanitizer_Result
{
    public string $svg            = '';
    public array  $warnings       = [];
    public int    $masks_converted = 0;

    private ?\WP_Error $error = null;

    public static function from_error( \WP_Error $error ): static
    {
        $r        = new static();
        $r->error = $error;
        return $r;
    }

    public function has_error(): bool       { return $this->error !== null; }
    public function get_error(): ?\WP_Error { return $this->error; }

    public function add_warning( string $message ): void { $this->warnings[] = $message; }
    public function has_warnings(): bool                 { return ! empty( $this->warnings ); }
}

// ---------------------------------------------------------------------------
// Sanitizer
// ---------------------------------------------------------------------------

class JVM_RTI_SVG_Sanitizer
{
    /** Tags removed for security. */
    protected array $forbidden_tags = [
        'script', 'iframe', 'object', 'embed', 'link', 'style', 'foreignObject',
    ];

    /** Attributes removed from every element. */
    protected array $forbidden_attributes = [
        'onload', 'onclick', 'onmouseover', 'onmouseout', 'onmousemove',
        'onfocus', 'onblur', 'onchange', 'oninput', 'onsubmit',
        'onkeydown', 'onkeyup', 'onkeypress', 'onerror', 'onabort',
        'onactivate', 'onbegin', 'onend', 'onrepeat', 'onzoom',
        'formaction', 'href',
    ];

    /** Editor namespaces to strip. */
    protected array $strip_namespaces = [ 'sodipodi', 'inkscape', 'dc', 'cc', 'rdf', 'xodm' ];

    /** Color values treated as potential mask colors (never → currentColor). */
    private array $white_values = [
        'white', '#fff', '#ffffff',
        'rgb(255,255,255)', 'rgb(255, 255, 255)',
        'rgba(255,255,255,1)', 'rgba(255, 255, 255, 1)',
    ];

    /** Color values considered foreground/dark shapes. */
    private array $dark_values = [
        'black', '#000', '#000000',
        'rgb(0,0,0)', 'rgb(0, 0, 0)',
        'rgba(0,0,0,1)', 'rgba(0, 0, 0, 1)',
    ];

    private int $mask_counter = 0;

    /**
     * Options:
     *
     *   security_only        Only run security passes, skip all normalization.
     *   normalize_colors     Replace hardcoded fill/stroke with currentColor.
     *   normalize_viewbox    Derive viewBox from width/height; strip width/height.
     *   square_viewbox       Pad non-square viewBoxes to a square canvas.
     *   remove_ids           Strip id attributes (except mask ids). Default true.
     *   prefix_ids           Prefix ids with this string instead of removing them.
     *                        Only used when remove_ids is false.
     *   convert_white_masks  Auto-convert white-as-mask patterns to <mask> elements.
     *                        When false, a warning is added instead.
     */
    protected array $options = [
        'security_only'       => false,
        'normalize_colors'    => true,
        'normalize_viewbox'   => true,
        'square_viewbox'      => false,
        'remove_ids'          => true,
        'prefix_ids'          => '',
        'convert_white_masks' => true,
    ];

    public function __construct( array $options = [] )
    {
        $this->options = array_merge( $this->options, $options );
    }

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    public function process( string $raw ): JVM_RTI_SVG_Sanitizer_Result
    {
        $raw = trim( $raw );
        if ( $raw === '' ) {
            return JVM_RTI_SVG_Sanitizer_Result::from_error(
                new \WP_Error( 'empty_svg', __( 'SVG input is empty.', 'jvm-rich-text-icons' ) )
            );
        }

        [ $dom, $parse_error ] = $this->parse( $raw );
        if ( $dom === null ) {
            return JVM_RTI_SVG_Sanitizer_Result::from_error(
                new \WP_Error( 'parse_error', $parse_error ?? __( 'Failed to parse SVG.', 'jvm-rich-text-icons' ) )
            );
        }

        $svg_el = $dom->documentElement;
        if ( strtolower( $svg_el->nodeName ) !== 'svg' ) {
            return JVM_RTI_SVG_Sanitizer_Result::from_error(
                new \WP_Error( 'invalid_svg', __( 'Root element is not <svg>.', 'jvm-rich-text-icons' ) )
            );
        }

        // Security passes — always run.
        $this->remove_comments( $dom );
        $this->remove_processing_instructions( $dom );
        $this->remove_forbidden_tags( $dom );
        $this->remove_namespace_elements( $dom );
        $this->remove_metadata( $dom );
        $this->remove_forbidden_attributes( $dom );
        $this->remove_external_references( $dom );

        $result = new JVM_RTI_SVG_Sanitizer_Result();

        if ( ! $this->options['security_only'] ) {
            // White-mask detection must run before color normalization so
            // white values are still identifiable.
            if ( $this->options['normalize_colors'] ) {
                $this->handle_white_masks( $dom, $result );
                $this->normalize_colors( $dom );
            }
            if ( $this->options['normalize_viewbox'] ) {
                $this->normalize_viewbox( $svg_el );
            }
            $this->handle_ids( $dom );
            $this->remove_empty_groups( $dom );
        }

        $result->svg = $this->serialize( $dom );
        return $result;
    }

    public function process_file( string $path ): JVM_RTI_SVG_Sanitizer_Result
    {
        if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
            return JVM_RTI_SVG_Sanitizer_Result::from_error(
                new \WP_Error( 'file_not_found', __( 'SVG file not found or not readable.', 'jvm-rich-text-icons' ) )
            );
        }
        $raw = file_get_contents( $path );
        if ( $raw === false ) {
            return JVM_RTI_SVG_Sanitizer_Result::from_error(
                new \WP_Error( 'file_read_error', __( 'Could not read SVG file.', 'jvm-rich-text-icons' ) )
            );
        }
        return $this->process( $raw );
    }

    // -----------------------------------------------------------------------
    // Parsing & serialization
    // -----------------------------------------------------------------------

    protected function parse( string $raw ): array // [?DOMDocument, ?string error]
    {
        $prev = libxml_use_internal_errors( true );
        libxml_clear_errors();

        $dom = new \DOMDocument( '1.0', 'UTF-8' );
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;

        $loaded = $dom->loadXML( $raw, LIBXML_NONET | LIBXML_NOBLANKS );

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors( $prev );

        if ( ! $loaded ) {
            $msg = ! empty( $errors ) ? trim( $errors[0]->message ) : 'Unknown XML parse error';
            return [ null, $msg ];
        }

        return [ $dom, null ];
    }

    protected function serialize( \DOMDocument $dom ): string
    {
        $output = $dom->saveXML( $dom->documentElement );
        $output = preg_replace( '/^<\?xml[^?]*\?>\s*/i', '', (string) $output );
        return trim( (string) $output );
    }

    // -----------------------------------------------------------------------
    // Security cleaning
    // -----------------------------------------------------------------------

    protected function remove_comments( \DOMDocument $dom ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( iterator_to_array( $xpath->query( '//comment()' ) ) as $node ) {
            $node->parentNode?->removeChild( $node );
        }
    }

    protected function remove_processing_instructions( \DOMDocument $dom ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( iterator_to_array( $xpath->query( '//processing-instruction()' ) ) as $node ) {
            $node->parentNode?->removeChild( $node );
        }
    }

    protected function remove_forbidden_tags( \DOMDocument $dom ): void
    {
        // getElementsByTagName() is case-sensitive for XML documents, so a
        // mixed/upper-case tag like <SCRIPT> would not match the lowercase
        // entries below and would survive sanitization — only to execute
        // once re-parsed by an HTML5 parser (which lowercases tag names).
        // Compare lower-cased local names via XPath instead.
        $forbidden = array_map( 'strtolower', $this->forbidden_tags );
        $xpath     = new \DOMXPath( $dom );
        foreach ( iterator_to_array( $xpath->query( '//*' ) ) as $node ) {
            if ( $node instanceof \DOMElement && in_array( strtolower( $node->localName ), $forbidden, true ) ) {
                $node->parentNode?->removeChild( $node );
            }
        }
    }

    protected function remove_namespace_elements( \DOMDocument $dom ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( $this->strip_namespaces as $ns ) {
            foreach ( iterator_to_array( $xpath->query( "//*[starts-with(name(), '{$ns}:')]" ) ) as $node ) {
                $node->parentNode?->removeChild( $node );
            }
        }
        $this->remove_namespace_attributes( $dom->documentElement );
    }

    protected function remove_namespace_attributes( \DOMElement $element ): void
    {
        $to_remove = [];
        foreach ( $element->attributes as $attr ) {
            $name = $attr->nodeName;
            foreach ( $this->strip_namespaces as $ns ) {
                if ( $name === "xmlns:{$ns}" || str_starts_with( $name, "{$ns}:" ) ) {
                    $to_remove[] = $name;
                }
            }
        }
        foreach ( $to_remove as $name ) {
            $element->removeAttribute( $name );
        }
        foreach ( $element->childNodes as $child ) {
            if ( $child instanceof \DOMElement ) {
                $this->remove_namespace_attributes( $child );
            }
        }
    }

    protected function remove_metadata( \DOMDocument $dom ): void
    {
        foreach ( [ 'metadata', 'title', 'desc' ] as $tag ) {
            foreach ( iterator_to_array( $dom->getElementsByTagName( $tag ) ) as $node ) {
                $node->parentNode?->removeChild( $node );
            }
        }
    }

    protected function remove_forbidden_attributes( \DOMDocument $dom ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( $xpath->query( '//*' ) as $element ) {
            if ( ! ( $element instanceof \DOMElement ) ) {
                continue;
            }
            $to_remove = [];
            foreach ( $element->attributes as $attr ) {
                $name = strtolower( $attr->nodeName );
                if ( str_starts_with( $name, 'on' ) ) {
                    $to_remove[] = $attr->nodeName;
                    continue;
                }
                if ( in_array( $name, $this->forbidden_attributes, true ) ) {
                    $tag = strtolower( $element->nodeName );
                    if ( $name === 'href' && in_array( $tag, [ 'a', 'use', 'image' ], true ) ) {
                        if ( ! $this->is_safe_href( $attr->value ) ) {
                            $to_remove[] = $attr->nodeName;
                        }
                        continue;
                    }
                    $to_remove[] = $attr->nodeName;
                }
            }
            foreach ( $to_remove as $name ) {
                $element->removeAttribute( $name );
            }
        }
    }

    protected function is_safe_href( string $value ): bool
    {
        $v = trim( strtolower( $value ) );
        return ! str_starts_with( $v, 'javascript:' ) && ! str_starts_with( $v, 'data:' );
    }

    protected function remove_external_references( \DOMDocument $dom ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( $xpath->query( '//*' ) as $element ) {
            if ( ! ( $element instanceof \DOMElement ) ) {
                continue;
            }
            $to_remove = [];
            foreach ( $element->attributes as $attr ) {
                if ( $attr->localName !== 'href' ) {
                    continue;
                }
                $val = $attr->value;
                $tag = strtolower( $element->nodeName );
                if ( ! $this->is_safe_href( $val ) || ( ! str_starts_with( trim( $val ), '#' ) && trim( $val ) !== '' ) ) {
                    if ( $tag !== 'a' || ! $this->is_safe_href( $val ) ) {
                        $to_remove[] = $attr;
                    }
                }
            }
            foreach ( $to_remove as $attr ) {
                $element->removeAttributeNode( $attr );
            }
        }
        foreach ( iterator_to_array( $dom->getElementsByTagName( 'image' ) ) as $img ) {
            $href = $img->getAttribute( 'href' ) ?: $img->getAttributeNS( 'http://www.w3.org/1999/xlink', 'href' );
            if ( $href && ! str_starts_with( trim( $href ), '#' ) ) {
                $img->parentNode?->removeChild( $img );
            }
        }
    }

    // -----------------------------------------------------------------------
    // White-as-mask detection & conversion
    // -----------------------------------------------------------------------

    /**
     * Scan containers for dark+white sibling shape patterns and either convert
     * them to proper SVG <mask> elements or add a warning.
     *
     * SVG mask semantics: white in mask = show, black = cut out.
     *
     * Before:
     *   <circle fill="#000000"/>   <- dark base shape
     *   <path   fill="#ffffff"/>   <- white cutout
     *
     * After:
     *   <defs>
     *     <mask id="jvm-mask-1">
     *       <rect fill="white" .../>   <- show entire canvas
     *       <path fill="black" .../>   <- cut out (was white, inverted)
     *     </mask>
     *   </defs>
     *   <g mask="url(#jvm-mask-1)">
     *     <circle fill="currentColor"/>
     *   </g>
     */
    private function handle_white_masks( \DOMDocument $dom, JVM_RTI_SVG_Sanitizer_Result $result ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( $xpath->query( '//svg | //g | //symbol' ) as $parent ) {
            if ( ! ( $parent instanceof \DOMElement ) ) {
                continue;
            }
            $analysis = $this->analyse_siblings( $parent );
            if ( $analysis['pattern'] === 'none' ) {
                continue;
            }
            if ( $analysis['pattern'] === 'clear' ) {
                if ( $this->options['convert_white_masks'] ) {
                    if ( $this->convert_to_mask( $dom, $parent, $analysis ) ) {
                        $result->masks_converted++;
                    }
                } else {
                    $result->add_warning( __( 'Icon contains white shapes that appear to be used as masks. Consider reviewing the SVG manually.', 'jvm-rich-text-icons' ) );
                }
            } elseif ( $analysis['pattern'] === 'ambiguous' ) {
                $result->add_warning( __( 'Icon contains white-filled elements. These have been left as-is — verify the icon renders correctly.', 'jvm-rich-text-icons' ) );
            }
        }
    }

    private function analyse_siblings( \DOMElement $parent ): array
    {
        $shape_tags = [ 'path', 'rect', 'circle', 'ellipse', 'polygon', 'polyline', 'line', 'use', 'g' ];
        $dark = $white = $other = [];

        foreach ( $parent->childNodes as $child ) {
            if ( ! ( $child instanceof \DOMElement ) ) {
                continue;
            }
            if ( ! in_array( strtolower( $child->nodeName ), $shape_tags, true ) ) {
                continue;
            }
            $fill = $this->get_effective_fill( $child );
            if ( $fill === null ) {
                continue;
            }
            if ( $this->is_white( $fill ) ) {
                $white[] = $child;
            } elseif ( $this->is_dark( $fill ) ) {
                $dark[] = $child;
            } else {
                $other[] = $child;
            }
        }

        if ( empty( $white ) ) {
            return [ 'pattern' => 'none', 'dark_elements' => [], 'white_elements' => [] ];
        }
        if ( ! empty( $dark ) && empty( $other ) ) {
            return [ 'pattern' => 'clear', 'dark_elements' => $dark, 'white_elements' => $white ];
        }
        return [ 'pattern' => 'ambiguous', 'dark_elements' => $dark, 'white_elements' => $white ];
    }

    private function get_effective_fill( \DOMElement $element ): ?string
    {
        if ( $element->hasAttribute( 'style' ) ) {
            $fill = $this->extract_style_property( $element->getAttribute( 'style' ), 'fill' );
            if ( $fill !== null ) {
                return strtolower( trim( $fill ) );
            }
        }
        if ( $element->hasAttribute( 'fill' ) ) {
            return strtolower( trim( $element->getAttribute( 'fill' ) ) );
        }
        return null;
    }

    private function extract_style_property( string $style, string $property ): ?string
    {
        foreach ( array_filter( array_map( 'trim', explode( ';', $style ) ) ) as $decl ) {
            if ( ! str_contains( $decl, ':' ) ) {
                continue;
            }
            [ $prop, $val ] = array_map( 'trim', explode( ':', $decl, 2 ) );
            if ( strtolower( $prop ) === $property ) {
                return $val;
            }
        }
        return null;
    }

    private function is_white( string $value ): bool
    {
        return in_array( strtolower( $value ), $this->white_values, true );
    }

    private function is_dark( string $value ): bool
    {
        return in_array( strtolower( $value ), $this->dark_values, true );
    }

    private function convert_to_mask( \DOMDocument $dom, \DOMElement $parent, array $analysis ): bool
    {
        $this->mask_counter++;
        $mask_id  = 'jvm-mask-' . $this->mask_counter;
        $svg_root = $dom->documentElement;
        $defs     = $this->get_or_create_defs( $dom, $svg_root );

        $mask = $dom->createElement( 'mask' );
        $mask->setAttribute( 'id', $mask_id );
        $mask->appendChild( $this->create_mask_background_rect( $dom, $svg_root->getAttribute( 'viewBox' ) ) );

        foreach ( $analysis['white_elements'] as $white_el ) {
            $clone = $white_el->cloneNode( true );
            $this->set_fill( $clone, 'black' );
            $this->set_stroke( $clone, 'none' );
            $mask->appendChild( $clone );
        }

        $defs->appendChild( $mask );

        if ( strtolower( $parent->nodeName ) === 'g' ) {
            $parent->setAttribute( 'mask', "url(#{$mask_id})" );
        } else {
            $wrapper = $dom->createElement( 'g' );
            $wrapper->setAttribute( 'mask', "url(#{$mask_id})" );
            $parent->insertBefore( $wrapper, $analysis['dark_elements'][0] );
            foreach ( $analysis['dark_elements'] as $dark_el ) {
                $wrapper->appendChild( $dark_el );
            }
        }

        foreach ( $analysis['white_elements'] as $white_el ) {
            $white_el->parentNode?->removeChild( $white_el );
        }
        foreach ( $analysis['dark_elements'] as $dark_el ) {
            $this->set_fill( $dark_el, 'currentColor' );
            $this->set_stroke( $dark_el, 'currentColor' );
        }

        return true;
    }

    private function get_or_create_defs( \DOMDocument $dom, \DOMElement $svg_root ): \DOMElement
    {
        $list = $svg_root->getElementsByTagName( 'defs' );
        if ( $list->length > 0 ) {
            return $list->item( 0 );
        }
        $defs = $dom->createElement( 'defs' );
        if ( $svg_root->firstChild ) {
            $svg_root->insertBefore( $defs, $svg_root->firstChild );
        } else {
            $svg_root->appendChild( $defs );
        }
        return $defs;
    }

    private function create_mask_background_rect( \DOMDocument $dom, string $viewbox ): \DOMElement
    {
        $rect  = $dom->createElement( 'rect' );
        $rect->setAttribute( 'fill', 'white' );
        $parts = preg_split( '/[\s,]+/', trim( $viewbox ) );
        if ( count( $parts ) === 4 ) {
            $rect->setAttribute( 'x',      $parts[0] );
            $rect->setAttribute( 'y',      $parts[1] );
            $rect->setAttribute( 'width',  $parts[2] );
            $rect->setAttribute( 'height', $parts[3] );
        } else {
            $rect->setAttribute( 'x',      '-9999' );
            $rect->setAttribute( 'y',      '-9999' );
            $rect->setAttribute( 'width',  '99999' );
            $rect->setAttribute( 'height', '99999' );
        }
        return $rect;
    }

    private function set_fill( \DOMElement $element, string $value ): void
    {
        if ( $element->hasAttribute( 'style' ) ) {
            $style = $element->getAttribute( 'style' );
            if ( $this->extract_style_property( $style, 'fill' ) !== null ) {
                $element->setAttribute( 'style', $this->set_style_property( $style, 'fill', $value ) );
                return;
            }
        }
        $element->setAttribute( 'fill', $value );
    }

    private function set_stroke( \DOMElement $element, string $value ): void
    {
        if ( $element->hasAttribute( 'style' ) ) {
            $style = $element->getAttribute( 'style' );
            if ( $this->extract_style_property( $style, 'stroke' ) !== null ) {
                $element->setAttribute( 'style', $this->set_style_property( $style, 'stroke', $value ) );
                return;
            }
        }
        if ( $element->hasAttribute( 'stroke' ) ) {
            $element->setAttribute( 'stroke', $value );
        } elseif ( $value === 'none' ) {
            $element->setAttribute( 'stroke', 'none' );
        }
    }

    private function set_style_property( string $style, string $property, string $value ): string
    {
        $decls  = array_filter( array_map( 'trim', explode( ';', $style ) ) );
        $result = [];
        $found  = false;
        foreach ( $decls as $decl ) {
            if ( ! str_contains( $decl, ':' ) ) {
                $result[] = $decl;
                continue;
            }
            [ $prop, $val ] = array_map( 'trim', explode( ':', $decl, 2 ) );
            if ( strtolower( $prop ) === $property ) {
                $result[] = "{$prop}:{$value}";
                $found    = true;
            } else {
                $result[] = "{$prop}:{$val}";
            }
        }
        if ( ! $found ) {
            $result[] = "{$property}:{$value}";
        }
        return implode( ';', $result );
    }

    // -----------------------------------------------------------------------
    // Color normalization
    // -----------------------------------------------------------------------

    private function normalize_colors( \DOMDocument $dom ): void
    {
        $xpath = new \DOMXPath( $dom );
        foreach ( $xpath->query( '//*' ) as $element ) {
            if ( ! ( $element instanceof \DOMElement ) ) {
                continue;
            }
            if ( $this->is_inside_mask( $element ) ) {
                continue; // mask colors are structural — don't touch
            }
            foreach ( [ 'fill', 'stroke' ] as $attr ) {
                if ( $element->hasAttribute( $attr ) ) {
                    $val = trim( $element->getAttribute( $attr ) );
                    if ( $this->should_replace_color( $val ) ) {
                        $element->setAttribute( $attr, 'currentColor' );
                    }
                }
            }
            if ( $element->hasAttribute( 'style' ) ) {
                $style = $this->normalize_style_colors( $element->getAttribute( 'style' ) );
                if ( $style === '' ) {
                    $element->removeAttribute( 'style' );
                } else {
                    $element->setAttribute( 'style', $style );
                }
            }
        }
    }

    private function is_inside_mask( \DOMElement $element ): bool
    {
        $parent = $element->parentNode;
        while ( $parent instanceof \DOMElement ) {
            if ( strtolower( $parent->nodeName ) === 'mask' ) {
                return true;
            }
            $parent = $parent->parentNode;
        }
        return false;
    }

    private function should_replace_color( string $value ): bool
    {
        $lower = strtolower( $value );
        if ( str_starts_with( $lower, 'url(' ) ) {
            return false; // gradient/pattern references — leave intact
        }
        $skip = array_merge(
            [ '', 'none', 'inherit', 'transparent', 'currentcolor' ],
            array_map( 'strtolower', $this->white_values )
        );
        return ! in_array( $lower, $skip, true );
    }

    private function normalize_style_colors( string $style ): string
    {
        $decls  = array_filter( array_map( 'trim', explode( ';', $style ) ) );
        $result = [];
        foreach ( $decls as $decl ) {
            if ( ! str_contains( $decl, ':' ) ) {
                $result[] = $decl;
                continue;
            }
            [ $prop, $val ] = array_map( 'trim', explode( ':', $decl, 2 ) );
            if ( in_array( strtolower( $prop ), [ 'fill', 'stroke' ], true ) && $this->should_replace_color( $val ) ) {
                $val = 'currentColor';
            }
            $result[] = "{$prop}:{$val}";
        }
        return implode( ';', $result );
    }

    // -----------------------------------------------------------------------
    // ViewBox normalization
    // -----------------------------------------------------------------------

    private function normalize_viewbox( \DOMElement $svg ): void
    {
        $has_vb = $svg->hasAttribute( 'viewBox' );
        $width  = $svg->getAttribute( 'width' );
        $height = $svg->getAttribute( 'height' );

        if ( ! $has_vb && $width && $height ) {
            $w = $this->parse_length( $width );
            $h = $this->parse_length( $height );
            if ( $w > 0 && $h > 0 ) {
                $svg->setAttribute( 'viewBox', "0 0 {$w} {$h}" );
                $has_vb = true;
            }
        }

        $svg->removeAttribute( 'width' );
        $svg->removeAttribute( 'height' );

        if ( $has_vb && $this->options['square_viewbox'] ) {
            $this->square_viewbox( $svg );
        }

        if ( ! $svg->hasAttribute( 'preserveAspectRatio' ) ) {
            $svg->setAttribute( 'preserveAspectRatio', 'xMidYMid meet' );
        }
    }

    private function square_viewbox( \DOMElement $svg ): void
    {
        $vb    = $svg->getAttribute( 'viewBox' );
        $parts = preg_split( '/[\s,]+/', trim( $vb ) );
        if ( count( $parts ) !== 4 ) {
            return;
        }
        [ $min_x, $min_y, $vw, $vh ] = array_map( 'floatval', $parts );
        if ( abs( $vw - $vh ) < 0.01 ) {
            return;
        }
        $size      = max( $vw, $vh );
        $new_min_x = $min_x - ( $size - $vw ) / 2;
        $new_min_y = $min_y - ( $size - $vh ) / 2;
        $svg->setAttribute( 'viewBox', "{$new_min_x} {$new_min_y} {$size} {$size}" );
    }

    private function parse_length( string $value ): float
    {
        return (float) preg_replace( '/[^0-9.]/', '', $value );
    }

    // -----------------------------------------------------------------------
    // ID handling
    // -----------------------------------------------------------------------

    private function handle_ids( \DOMDocument $dom ): void
    {
        $xpath    = new \DOMXPath( $dom );
        $id_nodes = $xpath->query( '//@id' );

        if ( $this->options['remove_ids'] ) {
            foreach ( iterator_to_array( $id_nodes ) as $attr ) {
                if ( ! ( $attr instanceof \DOMAttr ) ) {
                    continue;
                }
                $owner = $attr->ownerElement;
                if ( $owner && strtolower( $owner->nodeName ) === 'mask' ) {
                    continue; // always preserve mask IDs
                }
                $owner?->removeAttribute( 'id' );
            }
            return;
        }

        if ( ! empty( $this->options['prefix_ids'] ) ) {
            $prefix = preg_replace( '/[^a-z0-9-]/', '', strtolower( $this->options['prefix_ids'] ) ) . '-';
            $id_map = [];
            foreach ( $id_nodes as $attr ) {
                if ( ! ( $attr instanceof \DOMAttr ) ) {
                    continue;
                }
                $old_id          = $attr->value;
                $new_id          = $prefix . $old_id;
                $id_map[$old_id] = $new_id;
                $attr->ownerElement?->setAttribute( 'id', $new_id );
            }
            foreach ( $xpath->query( '//*' ) as $element ) {
                if ( ! ( $element instanceof \DOMElement ) ) {
                    continue;
                }
                foreach ( $element->attributes as $attr ) {
                    $val = preg_replace_callback(
                        '/url\(#([^)]+)\)/',
                        fn( $m ) => 'url(#' . ( $id_map[ $m[1] ] ?? $m[1] ) . ')',
                        $attr->value
                    );
                    if ( str_starts_with( $val, '#' ) ) {
                        $ref = substr( $val, 1 );
                        $val = '#' . ( $id_map[ $ref ] ?? $ref );
                    }
                    $attr->value = $val;
                }
            }
        }
    }

    // -----------------------------------------------------------------------
    // Empty group removal
    // -----------------------------------------------------------------------

    private function remove_empty_groups( \DOMDocument $dom ): void
    {
        for ( $i = 0; $i < 5; $i++ ) {
            if ( ! $this->remove_empty_groups_pass( $dom ) ) {
                break;
            }
        }
    }

    private function remove_empty_groups_pass( \DOMDocument $dom ): bool
    {
        $removed = false;
        foreach ( iterator_to_array( ( new \DOMXPath( $dom ) )->query( '//g' ) ) as $g ) {
            if ( ! $g->hasChildNodes() || $this->has_only_whitespace( $g ) ) {
                $g->parentNode?->removeChild( $g );
                $removed = true;
            }
        }
        return $removed;
    }

    private function has_only_whitespace( \DOMElement $element ): bool
    {
        foreach ( $element->childNodes as $child ) {
            if ( $child->nodeType === XML_ELEMENT_NODE ) {
                return false;
            }
            if ( $child->nodeType === XML_TEXT_NODE && trim( (string) $child->nodeValue ) !== '' ) {
                return false;
            }
        }
        return true;
    }
}
