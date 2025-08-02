<?php
namespace BlitzDock\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

class Analytics {
    public static function init() {
        add_action( 'wp_ajax_blitz_dock_log_event', [ __CLASS__, 'log_event' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_log_event', [ __CLASS__, 'log_event' ] );
    }

    private static function verify_nonce() {
        if ( ! check_ajax_referer( 'blitz_dock_log_event', 'nonce', false ) ) {
            wp_send_json( [ 'success' => false, 'message' => 'Session expired. Please refresh and try again.' ] );
        }
    }

    public static function log_event() {
        self::verify_nonce();

         $type   = sanitize_text_field( $_POST['event_type']   ?? '' );
        $topic  = sanitize_text_field( $_POST['event_topic']  ?? '' );
        $target = sanitize_text_field( $_POST['event_target'] ?? '' );
        $sub    = sanitize_text_field( $_POST['event_subtype']?? '' );

        if ( empty( $type ) ) {
            wp_send_json_error();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bdp_analytics';
         $wpdb->insert(
            $table,
            [
                'event_type'   => $type,
                'event_topic'  => $topic,
                'event_target' => $target,
                'event_subtype'=> $sub,
                'created_at'   => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s' ]
        );

        wp_send_json_success();
    }
}