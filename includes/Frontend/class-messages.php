<?php
namespace BlitzDock\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Messages {
    public static function init() {
        add_action( 'wp_ajax_blitz_dock_submit_message', [ __CLASS__, 'handle_submit' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_submit_message', [ __CLASS__, 'handle_submit' ] );
    }

    private static function verify_nonce() {
        if ( ! check_ajax_referer( 'bdp_message_nonce', 'nonce', false ) ) {
            wp_send_json( [ 'success' => false, 'message' => 'Session expired. Please refresh and try again.' ] );
        }
    }

    public static function handle_submit() {
        self::verify_nonce();

        $name    = sanitize_text_field( $_POST['name'] ?? '' );
        $email   = sanitize_email( $_POST['email'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( empty( $name ) || empty( $email ) || empty( $message ) || ! is_email( $email ) ) {
            wp_send_json_error( __( 'Please complete all fields correctly.', 'blitz-dock' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bdp_messages';
        $inserted = $wpdb->insert(
            $table,
            [
                'name'           => $name,
                'email'          => $email,
                'message'        => $message,
                'date_submitted' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            wp_send_json_error( __( 'Could not save message.', 'blitz-dock' ) );
        }

        // Notify admin
        $admin_email = get_option( 'admin_email' );
        wp_mail( $admin_email, __( 'New Blitz Dock Message', 'blitz-dock' ), "Name: {$name}\nEmail: {$email}\nMessage:\n{$message}" );

        wp_send_json_success( __( 'Thank you for your message!', 'blitz-dock' ) );
    }
}