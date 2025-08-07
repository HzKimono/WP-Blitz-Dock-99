<?php
/**
 * Plugin Name:     Blitz Dock
 * Description:     CRM Destek Paneli ve Sohbet Sistemi
 * Version:         1.4
 * Author:          Prints & Blitz: Kimono
 */

defined( 'ABSPATH' ) || exit;

// -----------------------------------------------------------------------------
// 1) Sabitler
// -----------------------------------------------------------------------------
define( 'BLITZ_DOCK_VERSION', '1.4' );
define( 'BLITZ_DOCK_PATH',    plugin_dir_path( __FILE__ ) );
define( 'BLITZ_DOCK_URL',     plugin_dir_url( __FILE__ ) );

// -----------------------------------------------------------------------------
// 2) Activation Hook — mesaj tablosunu oluşturur
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, 'blitz_dock_activate' );
function blitz_dock_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $tables = [
        $wpdb->prefix . 'bdp_messages' => "
            CREATE TABLE %s (
                id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name           VARCHAR(191) NOT NULL,
                email          VARCHAR(191) NOT NULL,
                message        TEXT NOT NULL,
                date_submitted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$charset};",
       $wpdb->prefix . 'bdp_analytics' => "
            CREATE TABLE %s (
                id           INT NOT NULL AUTO_INCREMENT,
                event_type   VARCHAR(50) NOT NULL,
                event_topic  VARCHAR(50) NOT NULL,
                event_target VARCHAR(100),
                event_subtype VARCHAR(100),
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$charset};",
    ];

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
     foreach ( $tables as $table_name => $create_sql ) {
        $sql = sprintf( $create_sql, $table_name );
        dbDelta( $sql );
    }

    blitz_dock_maybe_add_status_column();
}

// -----------------------------------------------------------------------------
// 3) Admin Ayarları — FAQ listesi için register & sanitize
// -----------------------------------------------------------------------------
add_action( 'admin_init', 'blitz_dock_register_settings' );
function blitz_dock_register_settings() {
    register_setting(
        'blitz_dock_settings',
        'bdp_faq_items',
        'blitz_dock_sanitize_faq_items'
    );
    register_setting( 'blitz_dock_settings', 'bdp_visibility_matrix' );
}

function blitz_dock_sanitize_faq_items( $items ) {
    if ( ! is_array( $items ) ) {
        return [];
    }

    $out = [];
    foreach ( $items as $row ) {
        $q = sanitize_text_field( $row['question'] ?? '' );
        $a = wp_kses_post( $row['answer'] ?? '' );
        if ( $q && $a ) {
            $out[] = [
                'question' => $q,
                'answer'   => $a,
            ];
        }
    }
    return $out;
}

// -----------------------------------------------------------------------------
// 4) Core ve Modülleri Yükle
// -----------------------------------------------------------------------------
require_once BLITZ_DOCK_PATH . 'includes/helpers.php';
require_once BLITZ_DOCK_PATH . 'includes/Core/class-plugin.php';
BlitzDock\Core\Plugin::init();

// -----------------------------------------------------------------------------
// Database migration: ensure status column exists on messages table
// -----------------------------------------------------------------------------
add_action( 'admin_init', 'blitz_dock_maybe_add_status_column' );
function blitz_dock_maybe_add_status_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'bdp_messages';
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'status' ) );
    if ( null === $exists ) {
        $wpdb->query( "ALTER TABLE {$table} ADD status ENUM('pending','completed','canceled') NOT NULL DEFAULT 'pending'" );
    }
}

// -----------------------------------------------------------------------------
// Ensure analytics table has topic and subtype columns
// -----------------------------------------------------------------------------
add_action( 'admin_init', 'blitz_dock_maybe_upgrade_analytics_table' );
function blitz_dock_maybe_upgrade_analytics_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'bdp_analytics';
    $col = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'event_topic' ) );
    if ( null === $col ) {
        $wpdb->query( "ALTER TABLE {$table} ADD COLUMN event_topic VARCHAR(50) NOT NULL AFTER event_type" );
    }
    $col = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'event_subtype' ) );
    if ( null === $col ) {
        $wpdb->query( "ALTER TABLE {$table} ADD COLUMN event_subtype VARCHAR(100) NULL AFTER event_target" );
    }
}

// -----------------------------------------------------------------------------
// (Removed) Hide unused submenu pages
// -----------------------------------------------------------------------------
/*
add_action( 'admin_menu', function() {
    remove_submenu_page( 'blitz-dock', 'blitz-dock-messages' );
   remove_submenu_page( 'blitz-dock', 'blitz-dock-analytics' );
}, 999 );
*/