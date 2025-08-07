<?php
namespace BlitzDock;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return associative array of available social platforms.
 * Keys are icon filenames (without extension), values are labels.
 */
function get_social_platforms() {
    static $platforms = null;
    if ( null !== $platforms ) {
        return $platforms;
    }

    $exclude = [
        'social-media','working-hours','world',
        'amazon','hepsiburada','sahibinden','shopify','temu','trendyol',
    ];

    $dir  = trailingslashit( BLITZ_DOCK_PATH . 'assets/icons' );
    $files = glob( $dir . '*.png' );

    $platforms = [];
    if ( $files ) {
        foreach ( $files as $file ) {
            $name = basename( $file, '.png' );
            if ( in_array( $name, $exclude, true ) ) {
                continue;
            }
            $label = ucwords( str_replace( ['-', '_'], ' ', $name ) );
            $platforms[ $name ] = $label;
        }
        ksort( $platforms );
    }

    return $platforms;
}