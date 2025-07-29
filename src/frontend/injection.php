<?php
/**
 * Performs the injections
 *
 * @package usc_injector
 */

// check if WordPress is loaded.
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

/**
 * Tries to inject content into wp_head or wp_footer
 * 
 * @wp-hook wp_head
 * @wp-hook wp_footer
 * 
 * @return  void
 */
function usci_maybe_inject_head_footer_content(): void {
    global $post;

    // get injections
    $injections = get_posts( [
        'post_type'      => 'usc_injection',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'   => 'injection_position',
                'value' => 'wp_head',
            ],
            [
                'key'   => 'injection_position',
                'value' => 'wp_footer',
            ],
        ],
    ] );

    foreach ( $injections as $injection ) {
		$injection_post_type = get_post_meta( $injection->ID, 'injection_post_type', true );
		if ( $injection_post_type !== '-1' && $post->post_type !== $injection_post_type ) {
			continue;
		}

		$content = get_post_meta( $injection->ID, 'injection_content', true );
		echo $content;
	}
}

/**
 * Filters post content to inject custom content defined via USC Injection posts.
 * Applies rules based on post type, position, tag selectors, and exclusion containers.
 *
 * @wp-hook	the_content
 * 
 * @param	string $content The original post content.
 * @return	string Modified content with injection if applicable.
 */
function usci_maybe_inject_the_content( string $content ): string {
    global $post;

    if ( ! is_singular() || ! is_main_query() ) {
        return $content;
    }

    $injections = get_posts( [
        'post_type'      => 'usc_injection',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [ 'key' => 'injection_position', 'value' => 'the_content' ],
        ],
    ] );

    foreach ( $injections as $injection ) {
        $target_type = get_post_meta( $injection->ID, 'injection_post_type', true );
        if ( $target_type !== '-1' && $post->post_type !== $target_type ) {
            continue;
        }

        $inject_content   = get_post_meta( $injection->ID, 'injection_content', true );
        $formatting       = get_post_meta( $injection->ID, 'injection_formatting', true );
        $content_position = get_post_meta( $injection->ID, 'injection_content_position', true );
        $tag_rules        = get_post_meta( $injection->ID, 'injection_tag', true );
        $exclude_rules    = get_post_meta( $injection->ID, 'injection_exclude', true );

        if ( $formatting === 'on' ) {
            remove_filter( 'the_content', 'usci_maybe_inject_the_content', 2 );
            $inject_content = wpautop( $inject_content );
            $inject_content = apply_filters( 'the_content', $inject_content );
            add_filter( 'the_content', 'usci_maybe_inject_the_content', 2 );
        }

        if ( $content_position === 'before_content' ) {
            $content = $inject_content . $content;
        } elseif ( $content_position === 'after_content' ) {
            $content .= $inject_content;
        } else {
            $content = usci_dom_inject( $content, $inject_content, $tag_rules, $content_position, $exclude_rules );
        }
    }

    return $content;
}

/**
 * Injects content before or after a specific HTML tag using DOM and XPath.
 * Skips tags inside excluded containers defined by ID or class.
 *
 * @param string $html      The original HTML content.
 * @param string $injection The HTML content to inject.
 * @param string $ruleset   Comma-separated tag rules (e.g., 'h2:nth-of-type(2), h3').
 * @param string $mode      'specific_tag' or 'before_specific_tag'.
 * @param string $excludes  Comma-separated selectors to exclude (e.g., '#comments, .no-inject').
 *
 * @return string The HTML content with injection applied, or original HTML.
 */
function usci_dom_inject( string $html, string $injection, string $ruleset, string $mode, string $excludes = '' ): string {

    if ( empty( $ruleset ) ) return $html;

    $rules   = array_map( 'trim', explode( ',', $ruleset ) );
    $exclude = array_map( 'trim', explode( ',', $excludes ) );

	$html = preg_replace(
		'/(?<=\s|\A)(\[.*?\])(?=\s|\z)/',
		'<span data-shortcode="true">$1</span>',
		$html
	);
	$wrapped_html = '<div id="usci-wrapper">' . $html . '</div>';

    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
	$loaded = $dom->loadHTML( '<?xml encoding="UTF-8">' . $wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    if ( ! $loaded ) {
        error_log( '[USCI] Failed to load HTML content.' );
        return $html;
    }

    error_log( '[USCI] DOM loaded successfully.' );
    $xpath = new DOMXPath( $dom );

    foreach ( $rules as $rule ) {
        error_log( '[USCI] Processing rule: ' . $rule );

        $selector = trim( $rule );
        $position = 1;

        if ( preg_match( '#:nth-of-type\((\d+)\)$#i', $selector, $m ) ) {
            $position = (int) $m[1];
            $selector = preg_replace( '#:nth-of-type\(\d+\)$#i', '', $selector );
        }

        $selector_xpath = usci_selector_to_xpath( $selector );
        $nodes = $xpath->query( $selector_xpath );
        error_log( '[USCI] Found ' . $nodes->length . ' nodes for selector.' );

        $count = 0;
        foreach ( $nodes as $node ) {
            $skip = false;
            foreach ( $exclude as $sel ) {
                if ( empty( $sel ) ) continue;
                $check = null;
                if ( str_starts_with( $sel, '#' ) ) {
                    $id = substr( $sel, 1 );
                    $check = "ancestor::*[@id='$id']";
                } elseif ( str_starts_with( $sel, '.' ) ) {
                    $cls = substr( $sel, 1 );
                    $check = "ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' $cls ')]";
                }
                if ( $check ) {
                    $result = $xpath->evaluate( "boolean($check)", $node );
                    error_log( '[USCI] Checking exclusion "' . $sel . '": ' . ( $result ? 'MATCHED' : 'not matched' ) );
                    if ( $result ) {
                        $skip = true;
                        break;
                    }
                }
            }
            if ( $skip ) {
                error_log( '[USCI] Skipped node due to exclusion rule.' );
                continue;
            }

            $count++;
            if ( $count !== $position ) continue;

            error_log( '[USCI] Will inject after node: ' . $node->nodeName );

            $frag = $dom->createDocumentFragment();
			$wrapped = '<div>' . $injection . '</div>';
			$frag->appendXML( $wrapped );

            if ( $mode === 'before_specific_tag' ) {
                $node->parentNode->insertBefore( $frag, $node );
            } else {
				if ( $node->nextSibling ) {
                    $node->parentNode->insertBefore( $frag, $node->nextSibling );
                } else {
                    $node->parentNode->appendChild( $frag );
                }
            }

            $wrapper = $dom->getElementById( 'usci-wrapper' );
			if ( $wrapper ) {
				$output = '';
				foreach ( $wrapper->childNodes as $child ) {
					$output .= $dom->saveHTML( $child );
				}
				$output = preg_replace_callback(
					'#<span[^>]*data-shortcode="true"[^>]*>(.*?)</span>#s',
					function ( $matches ) {
						return $matches[1];
					},
					$output
				);
				return $output;
			}
        }
    }

    return $html;
}

/**
 * Converts a simplified CSS selector to an XPath expression.
 * Supports: tag, .class, #id, tag.class, tag#id.
 * 
 * @param	string $selector
 * 
 * @return string 
 */
function usci_selector_to_xpath( string $selector ): string {
    $tag = '*';
    $predicates = [];

    if ( preg_match( '#^([a-z0-9]+)#i', $selector, $m ) ) {
        $tag = $m[1];
    }
    if ( preg_match( '#\.([a-zA-Z0-9_-]+)#', $selector, $m ) ) {
        $cls = $m[1];
        $predicates[] = "contains(concat(' ', normalize-space(@class), ' '), ' $cls ')";
    }
    if ( preg_match( '#\#([a-zA-Z0-9_-]+)#', $selector, $m ) ) {
        $id = $m[1];
        $predicates[] = "@id='$id'";
    }

    $predicate = '';
    if ( $predicates ) {
        $predicate = '[' . implode( ' and ', $predicates ) . ']';
    }

    return '//' . $tag . $predicate;
}
