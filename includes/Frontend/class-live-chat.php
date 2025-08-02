<?php
namespace BlitzDock\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

class Live_Chat {

    public static function init() {
        // visitor endpoints
        add_action( 'wp_ajax_blitz_dock_live_chat_request', [ __CLASS__, 'handle_request' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_live_chat_request', [ __CLASS__, 'handle_request' ] );
        add_action( 'wp_ajax_blitz_dock_live_chat_send', [ __CLASS__, 'handle_send' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_live_chat_send', [ __CLASS__, 'handle_send' ] );
        add_action( 'wp_ajax_blitz_dock_live_chat_poll', [ __CLASS__, 'handle_poll' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_live_chat_poll', [ __CLASS__, 'handle_poll' ] );

        // allow visitors to close their chat session
        add_action( 'wp_ajax_blitz_dock_close_chat_visitor', [ __CLASS__, 'visitor_close_chat' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_close_chat_visitor', [ __CLASS__, 'visitor_close_chat' ] );

       // admin endpoints
        add_action( 'wp_ajax_blitz_dock_admin_send_message', [ __CLASS__, 'admin_send_message' ] );
        add_action( 'wp_ajax_blitz_dock_accept_chat', [ __CLASS__, 'accept_chat' ] );
        add_action( 'wp_ajax_blitz_dock_close_chat', [ __CLASS__, 'close_chat' ] );
        add_action( 'wp_ajax_blitz_dock_check_chats', [ __CLASS__, 'check_chats' ] );

        // rating endpoint
        add_action( 'wp_ajax_blitz_dock_submit_rating', [ __CLASS__, 'submit_rating' ] );
        add_action( 'wp_ajax_nopriv_blitz_dock_submit_rating', [ __CLASS__, 'submit_rating' ] );
    }


    private static function sessions_table() {
        global $wpdb;
        return $wpdb->prefix . 'bdp_live_chat_sessions';
    }

   private static function messages_table() {
        global $wpdb;
        return $wpdb->prefix . 'bdp_live_chat_messages';
    }

   private static function feedback_table() {
        global $wpdb;
        return $wpdb->prefix . 'bdp_chat_feedback';
    }

    private static function verify_nonce() {
        if ( ! check_ajax_referer( 'bdp_message_nonce', 'nonce', false ) ) {
            wp_send_json( [ 'success' => false, 'message' => 'Session expired. Please refresh and try again.' ] );
        }
    }


    public static function handle_request() {
        self::verify_nonce();

        $name    = sanitize_text_field( $_POST['name'] ?? '' );
        $email   = sanitize_email( $_POST['email'] ?? '' );
        $phone   = sanitize_text_field( $_POST['phone'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        if ( ! $name || ! $email || ! is_email( $email ) || ! $message ) {
            wp_send_json_error( __( 'Please fill required fields.', 'blitz-dock' ) );
        }

        global $wpdb;
        $table = self::sessions_table();
     $wpdb->insert( $table, [
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'status'     => 'pending',
            'created_at' => current_time( 'mysql' ),
        ], [ '%s','%s','%s','%s','%s' ] );
        $chat_id = $wpdb->insert_id;

        // save the first message
        $messages_table = self::messages_table();
        $wpdb->insert( $messages_table, [
            'chat_id'     => $chat_id,
            'sender_type' => 'visitor',
            'sender_name' => $name,
            'message'     => $message,
            'status'      => 'sent',
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d','%s','%s','%s','%s','%s' ] );

        // notify admin by email
        $admin_email = get_option( 'admin_email' );
        wp_mail( $admin_email, __( 'New Live Chat Request', 'blitz-dock' ), "Name: {$name}\nEmail: {$email}\nMessage:\n{$message}" );

        wp_send_json_success( [ 'chat_id' => $chat_id ] );
    }

    public static function handle_send() {
        self::verify_nonce();

         $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        $name    = sanitize_text_field( $_POST['name'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( ! $chat_id || ! $message ) {
            wp_send_json_error();
        }

        global $wpdb;
        $table = self::messages_table();
        $session_table = self::sessions_table();

        $status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$session_table} WHERE id=%d", $chat_id ) );
        if ( 'closed' === $status ) {
            wp_send_json_error();
        }

        $last = $wpdb->get_row( $wpdb->prepare( "SELECT message, created_at FROM {$table} WHERE chat_id=%d AND sender_type='visitor' ORDER BY id DESC LIMIT 1", $chat_id ), ARRAY_A );
        if ( $last && $last['message'] === $message && ( strtotime( current_time( 'mysql' ) ) - strtotime( $last['created_at'] ) ) < 2 ) {
            wp_send_json_error();
        }
        $wpdb->insert( $table, [
            'chat_id'     => $chat_id,
            'sender_type' => 'visitor',
            'sender_name' => $name,
            'message'     => $message,
            'status'      => 'sent',
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d','%s','%s','%s','%s','%s' ] );

        wp_send_json_success();
    }

    public static function handle_poll() {
        self::verify_nonce();

        $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        $last_id = (int) ( $_POST['last_id'] ?? 0 );
        if ( ! $chat_id ) {
            wp_send_json_error();
        }

        global $wpdb;
        $session_table  = self::sessions_table();
        $status         = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$session_table} WHERE id=%d", $chat_id ) );

        $messages_table = self::messages_table();
        $msgs = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, sender_type, sender_name, message, created_at FROM {$messages_table} WHERE chat_id=%d AND id>%d ORDER BY id ASC",
            $chat_id, $last_id
        ), ARRAY_A );

        wp_send_json_success( [ 'status' => $status, 'messages' => $msgs ] );
    }

    public static function admin_send_message() {
        self::verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        $name    = wp_get_current_user()->display_name ?: 'Admin';

        if ( ! $chat_id || ! $message ) {
            wp_send_json_error();
        }

        global $wpdb;
        $table = self::messages_table();
        $wpdb->insert( $table, [
            'chat_id'     => $chat_id,
            'sender_type' => 'admin',
            'sender_name' => $name,
            'message'     => $message,
            'status'      => 'sent',
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d','%s','%s','%s','%s','%s' ] );

        wp_send_json_success();
    }

         public static function accept_chat() {
        self::verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        global $wpdb;
       $table   = self::sessions_table();
        $wpdb->update( $table, [ 'status' => 'active' ], [ 'id' => $chat_id ], [ '%s' ], [ '%d' ] );
        $status  = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$table} WHERE id=%d", $chat_id ) );
        wp_send_json_success( [ 'status' => $status ] );
    }

        public static function close_chat() {
        self::verify_nonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        if ( ! $chat_id ) {
            wp_send_json_error();
        }
       global $wpdb;
        $table = self::sessions_table();
        $wpdb->update( $table, [ 'status' => 'closed' ], [ 'id' => $chat_id ], [ '%s' ], [ '%d' ] );

        $msg_table = self::messages_table();
        $wpdb->insert( $msg_table, [
            'chat_id'     => $chat_id,
            'sender_type' => 'system',
            'sender_name' => '',
            'message'     => __( 'Chat ended by admin', 'blitz-dock' ),
            'status'      => 'sent',
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d','%s','%s','%s','%s','%s' ] );

        wp_send_json_success();
    }


    // Visitor triggered chat close
     public static function visitor_close_chat() {
         self::verify_nonce();
        $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        if ( ! $chat_id ) {
            wp_send_json_error();
        }
       global $wpdb;
        $table = self::sessions_table();
        $wpdb->update( $table, [ 'status' => 'closed' ], [ 'id' => $chat_id ], [ '%s' ], [ '%d' ] );

        $msg_table = self::messages_table();
        $wpdb->insert( $msg_table, [
            'chat_id'     => $chat_id,
            'sender_type' => 'system',
            'sender_name' => '',
            'message'     => __( 'Chat ended by visitor', 'blitz-dock' ),
            'status'      => 'sent',
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d','%s','%s','%s','%s','%s' ] );

        wp_send_json_success();
    }

     public static function submit_rating() {
        self::verify_nonce();

        $chat_id = (int) ( $_POST['chat_id'] ?? 0 );
        $rating  = (int) ( $_POST['rating'] ?? 0 );
        $comment = sanitize_textarea_field( $_POST['comment'] ?? '' );

        if ( ! $chat_id || $rating < 1 || $rating > 5 ) {
            wp_send_json_error();
        }

        global $wpdb;
        $table = self::feedback_table();
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE chat_id=%d", $chat_id ) );
        if ( $exists ) {
            wp_send_json_error();
        }

        $wpdb->insert( $table, [
            'chat_id'      => $chat_id,
            'rating'       => $rating,
            'comment'      => $comment,
            'submitted_at' => current_time( 'mysql' ),
        ], [ '%d', '%d', '%s', '%s' ] );

        wp_send_json_success();
    }

    public static function check_chats() {
        check_ajax_referer( 'bdp_message_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        global $wpdb;
        $sessions_table = self::sessions_table();
        $messages_table = self::messages_table();

        $pending = $wpdb->get_results(
            "SELECT s.*, (SELECT message FROM {$messages_table} WHERE chat_id=s.id ORDER BY id ASC LIMIT 1) AS first_message FROM {$sessions_table} s WHERE s.status='pending' ORDER BY s.created_at DESC",
            ARRAY_A
        );

        $active = $wpdb->get_results(
            "SELECT id, name, email, phone, created_at FROM {$sessions_table} WHERE status='active' ORDER BY created_at DESC",
            ARRAY_A
        );

        wp_send_json_success( [ 'pending' => $pending, 'active' => $active ] );
    }
}