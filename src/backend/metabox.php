<?php
/**
 * Registers the metabox and save methods
 *
 * @package usc_injector
 */

// check if WordPress is loaded.
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

/**
 * Register injection metabox for injection.
 *
 * @wp-hook add_meta_boxes
 */
function uscn_register_injection_metabox(): void {
    add_meta_box(
        'usci_injection_base',
        __( 'Injection', 'usc-injector' ),
        'usci_render_metabox_content',
        'usc_injection',
        'normal',
        'default'
    );
}

/**
 * Render injection metabox content.
 *
 * @param WP_Post $post
 */
function usci_render_metabox_content( WP_Post $post ): void {
    $injection_content = get_post_meta( $post->ID, 'injection_content', TRUE );
    $injection_post_type = get_post_meta( $post->ID, 'injection_post_type', TRUE );
    $injection_position = get_post_meta( $post->ID, 'injection_position', TRUE );
    $injection_content_position = get_post_meta( $post->ID, 'injection_content_position', TRUE );
    $injection_tag = get_post_meta( $post->ID, 'injection_tag', TRUE );
    $injection_formatting = get_post_meta( $post->ID, 'injection_formatting', TRUE );

    wp_nonce_field( 'usci_save_injection_metabox', 'usci_injection_metabox_nonce' );
    ?>
    <div class="usci-tab-container" id="usci-injection-wrapper">
        <ul class="usci-tabs uscn-newsletter-tabs">
            <li class="active" data-tab="injection"><?php esc_html_e( 'Injection', 'usc-injector' ); ?></li>
            <li data-tab="rules"><?php esc_html_e( 'Rules', 'usc-injector' ); ?></li>
            <li data-tab="settings"><?php esc_html_e( 'Settings', 'usc-injector' ); ?></li>
        </ul>

        <div class="usci-tab-content active" data-tab-content="injection">
            <?php
            wp_editor(
                $injection_content ?? '',
                'usci_injection_content',
                [
                    'textarea_name' => "injection_content",
                    'media_buttons' => true,
                    'teeny'         => true,
                    'quicktags'     => true,
                    'tinymce'       => false,
                    'textarea_rows' => 14,
                ]
            );
            ?>
            <p><span class="description"><?php esc_html_e( 'You can insert whatever content you want.', 'usc-injector' ); ?></span></p>
        </div>

        <div class="usci-tab-content" data-tab-content="rules">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Post Type:', 'usc-injector' ); ?></th>
                    <td>
                        <select name="injection_post_type">
                            <option value="-1"><?php esc_html_e( 'All Post Types', 'usc-injector' ); ?></option>
                            <?php
                            $post_types = get_post_types( [ 'public' => true ], 'objects' );
                            foreach ( $post_types as $slug => $type ) :
                                ?>
                                <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $injection_post_type ?? '', $slug ); ?>>
                                    <?php echo esc_html( $type->labels->singular_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p><span class="description"><?php esc_html_e( 'Select the post type where the injection should be exectuted.', 'usc-injector' ); ?></span></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Position:', 'usc-injector' ); ?></th>
                    <td>
                        <select name="injection_position">
                            <option value="wp_head" <?php selected( $injection_position ?? '', 'wp_head' ); ?>><?php esc_html_e( 'In wp_head()', 'usc-injector' ); ?></option>
                            <option value="wp_footer" <?php selected( $injection_position ?? '', 'wp_footer' ); ?>><?php esc_html_e( 'In wp_footer()', 'usc-injector' ); ?></option>
                            <option value="the_content" <?php selected( $injection_position ?? '', 'the_content' ); ?>><?php esc_html_e( 'In the_content()', 'usc-injector' ); ?></option>
                        </select>
                        <p><span class="description"><?php esc_html_e( 'Select the general position of the injection.', 'usc-injector' ); ?></span></p>
                    </td>
                </tr>
                <tr id="row-injection-content-position">
                    <th scope="row"><?php esc_html_e( 'Content Position:', 'usc-injector' ); ?></th>
                    <td>
                        <select name="injection_content_position">
                            <option value="before_content" <?php selected( $injection_content_position ?? '', 'before_content' ); ?>><?php esc_html_e( 'Before post content', 'usc-injector' ); ?></option>
                            <option value="after_content" <?php selected( $injection_content_position ?? '', 'after_content' ); ?>><?php esc_html_e( 'After post content', 'usc-injector' ); ?></option>
                            <option value="specific_tag" <?php selected( $injection_content_position ?? '', 'specific_tag' ); ?>><?php esc_html_e( 'After specific tag', 'usc-injector' ); ?></option>
                            <option value="before_specific_tag" <?php selected( $injection_content_position ?? '', 'before_specific_tag' ); ?>><?php esc_html_e( 'Before specific tag', 'usc-injector' ); ?></option>
                        </select>
                        <p><span class="description"><?php esc_html_e( 'Select where the injection should appear.', 'usc-injector' ); ?></span></p>
                    </td>
                </tr>
                <tr id="row-injection-tag">
                    <th scope="row"><?php esc_html_e( 'HTML Tag Rules:', 'usc-injector' ); ?></th>
                    <td>
                        <input type="text" class="large-text" name="injection_tag" value="<?php echo $injection_tag; ?>" />
                        <p><span class="description"><?php echo sprintf( esc_html__( 'Set the HTML tag on which the injection should be exectuted. You can use hierarchical rules like %s. This example ruleset would inject the content after the 2nd h2-tag. If it couldnot find the tag, the content would be inserted after the 3rd h3 tag. If that didnot work, the content will be inserted after the first h3 tag.', 'usc-injector' ), '<code>h2:nth-of-type(2), h3:nth-of-type(3), h3</code>' ); ?></span></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="usci-tab-content" data-tab-content="settings">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Formatting:', 'usc-injector' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="injection_formatting" value="on" <?php checked( $injection_formatting ?? '', 'on' ); ?>; />
                            <?php esc_html_e( 'Enable standard WordPress formatting for this injection.', 'usc-injector' ); ?>
                        </label>
                        <p><span class="description"><?php esc_html_e( 'If this setting is active, the content of the injection will be formatted with the WordPress standard filters like automatically inserting p-tags.', 'usc-injector' ); ?></span></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Save injection data.
 *
 * @wp-hook save_post_usc_injection
 */
function usci_save_injection_metabox( int $post_id ): void {
    if (
        ! isset( $_POST['usci_injection_metabox_nonce'] ) ||
        ! wp_verify_nonce( $_POST['usci_injection_metabox_nonce'], 'usci_save_injection_metabox' )
    ) {
        return;
    }

    update_post_meta( $post_id, 'injection_content', $_POST['injection_content'] );
    update_post_meta( $post_id, 'injection_post_type', $_POST['injection_post_type'] );
    update_post_meta( $post_id, 'injection_position', $_POST['injection_position'] );
    update_post_meta( $post_id, 'injection_content_position', $_POST['injection_content_position'] );
    update_post_meta( $post_id, 'injection_tag', $_POST['injection_tag'] );
    update_post_meta( $post_id, 'injection_formatting', $_POST['injection_formatting'] );
}
