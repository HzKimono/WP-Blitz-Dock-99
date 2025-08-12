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

    $platforms = \BlitzDock\Core\Catalog::get_social_platforms();
    return $platforms;
}