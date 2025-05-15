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
 * Filters post content for content injections.
 *
 * @wp-hook the_content
 *
 * @param  string $content
 * @return string
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
			[
				'key'   => 'injection_position',
				'value' => 'the_content',
			],
		],
	] );

	foreach ( $injections as $injection ) {
		$target_type = get_post_meta( $injection->ID, 'injection_post_type', true );
		if ( $target_type !== '-1' && $post->post_type !== $target_type ) {
			continue;
		}

		$inject_content   = get_post_meta( $injection->ID, 'injection_content', true );
		$content_position = get_post_meta( $injection->ID, 'injection_content_position', true );

		switch ( $content_position ) {
			case 'before_content':
				$content = $inject_content . $content;
				break;
			case 'after_content':
				$content .= $inject_content;
				break;
			case 'specific_tag':
			case 'before_specific_tag':
				$tag_rules = get_post_meta( $injection->ID, 'injection_tag', true );
				$content   = usci_inject_tag_ruleset( $content, $inject_content, $tag_rules, $content_position );
				break;
		}

        // check for formatting
        $formatting = get_post_meta( $injection->ID, 'injection_formatting', true );
        if ( $formatting === 'on' ) {
            remove_filter( 'the_content', 'usci_maybe_inject_the_content' );
			$content = wpautop( $content );
            $content = apply_filters( 'the_content', $content );
            add_filter( 'the_content', 'usci_maybe_inject_the_content' );
        }
	}

	return $content;
}

/**
 * Tries to inject content after the first matching rule in the ruleset.
 *
 * @param string $html              The original HTML content.
 * @param string $injection         The content to inject.
 * @param string $ruleset           Comma-separated tag rules.
 * @param string $content_position  Determines if the content is injected before or after the tag.
 *
 * @return string Modified HTML with injection or original if no match.
 */
function usci_inject_tag_ruleset( string $html, string $injection, string $ruleset, string $content_position ): string {
	if ( empty( $ruleset ) ) {
		return $html;
	}

	$rules = array_map( 'trim', explode( ',', $ruleset ) );

	foreach ( $rules as $rule ) {
		$updated = usci_try_inject_after_css_selector( $html, $injection, $rule, $content_position );
		if ( $updated !== $html ) {
			return $updated;
		}
	}

	return $html;
}

/**
 * Injects content before or after the specified tag rule.
 *
 * @param string $html
 * @param string $injection
 * @param string $rule             E.g., h2:nth-of-type(2)
 * @param string $position_mode    'specific_tag' or 'before_specific_tag'
 *
 * @return string
 */
function usci_try_inject_after_css_selector( string $html, string $injection, string $rule, string $position_mode ): string {
	if ( ! preg_match( '#^([a-z0-9]+)(?::nth-of-type\((\d+)\))?$#i', $rule, $matches ) ) {
		return $html;
	}

	$tag      = $matches[1];
	$position = isset( $matches[2] ) ? (int) $matches[2] : 1;

	$tag = preg_quote( $tag, '#' );

	$pattern = sprintf(
		'#(<%1$s(?:\s[^>]*)?>.*?</%1$s>)|(<%1$s(?:\s[^>]*)?/?>)#is',
		$tag
	);

	$count = 0;

	return preg_replace_callback( $pattern, function ( $match ) use ( $injection, $position, $position_mode, &$count ) {
		$count++;
		if ( $count === $position ) {
			if ( $position_mode === 'before_specific_tag' ) {
				return $injection . $match[0];
			}
			return $match[0] . $injection;
		}
		return $match[0];
	}, $html );
}
