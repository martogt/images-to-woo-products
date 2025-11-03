<?php
if ( ! function_exists( 'itpwc_get_page_by_title_safe' ) ) {
    /**
     * Replacement for deprecated get_page_by_title() using WP_Query.
     * Returns a WP_Post object (when $output is OBJECT) or null.
     *
     * @param string $title
     * @param string $output Only OBJECT is supported by this shim.
     * @param string $post_type
     * @return WP_Post|null
     */
    function itpwc_get_page_by_title_safe( $title, $output = OBJECT, $post_type = 'page' ) {
        $slug = sanitize_title( (string) $title );
        if ( '' === $slug ) {
            return null;
        }
        $q = new WP_Query( array(
            'post_type'              => $post_type,
            'name'                   => $slug,
            'posts_per_page'         => 1,
            'post_status'            => 'any',
            'no_found_rows'          => true,
            'fields'                 => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ) );
        if ( empty( $q->posts ) ) {
            return null;
        }
        $post = get_post( (int) $q->posts[0] );
        return ( $output === OBJECT ) ? $post : $post;
    }
}
