<?php
namespace BlitzDock\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

    public static function init() {
        // Admin CSS yükle
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_styles' ] );
        // Menu ve alt menüleri kaydet
        add_action( 'admin_menu',           [ __CLASS__, 'add_settings_page'   ] );
       // “Delete” işlemini yakala
         add_action( 'admin_init',           [ __CLASS__, 'maybe_handle_message_delete' ] );
        // AJAX: update message status
        add_action( 'wp_ajax_bdp_update_status', [ __CLASS__, 'ajax_update_status' ] );
        // AJAX: filter messages
        add_action( 'wp_ajax_bdp_filter_messages', [ __CLASS__, 'ajax_filter_messages' ] );
        // AJAX: log analytics event
        add_action( 'wp_ajax_bdp_log_event', [ __CLASS__, 'ajax_log_event' ] );
        // AJAX: filter analytics data
        add_action( 'wp_ajax_bdp_filter_analytics', [ __CLASS__, 'ajax_filter_analytics' ] );
    }

    public static function add_settings_page() {
        add_menu_page(
            __( 'Blitz Dock Settings', 'blitz-dock' ),
            __( 'Blitz Dock',          'blitz-dock' ),
            'manage_options',
            'blitz-dock',
            [ __CLASS__, 'render_settings_page' ],
            'dashicons-admin-generic',
            80
        );

        add_submenu_page(
            'blitz-dock',
            __( 'Messages', 'blitz-dock' ),
            __( 'Messages', 'blitz-dock' ),
            'manage_options',
            'blitz-dock-messages',
            [ __CLASS__, 'render_messages_page' ]
        );

        add_submenu_page(
            'blitz-dock',
            __( 'Analytics', 'blitz-dock' ),
            __( 'Analytics', 'blitz-dock' ),
            'manage_options',
            'blitz-dock-analytics',
            [ __CLASS__, 'render_analytics_page' ]
        );
    }

    public static function render_settings_page() {
        $file = BLITZ_DOCK_PATH . 'includes/AdminUI/settings-page.php';

        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="notice notice-error"><p>'
               . esc_html__( 'Settings page file not found.', 'blitz-dock' )
               . '</p></div>';
        }
    }

    public static function render_messages_page() {
        $file = BLITZ_DOCK_PATH . 'includes/AdminUI/messages-page.php';

        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="notice notice-error"><p>'
               . esc_html__( 'Messages page file not found.', 'blitz-dock' )
               . '</p></div>';
        }
    }

    public static function render_analytics_page() {
        $file = BLITZ_DOCK_PATH . 'includes/AdminUI/analytics-page.php';

        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="notice notice-error"><p>'
               . esc_html__( 'Analytics page file not found.', 'blitz-dock' )
               . '</p></div>';
        }
    }

    /**
     * Handle the “Delete Message” action securely on the Messages page.
     */
    public static function maybe_handle_message_delete() {
        // Sadece blitz-dock-messages sayfasındayken çalışsın
        if ( empty( $_GET['page'] ) || 'blitz-dock-messages' !== sanitize_key( $_GET['page'] ) ) {
            return;
        }

        // Sadece bizim özel action’ımızı işleyelim
        if ( empty( $_GET['action'] ) || 'bdp_delete_message' !== sanitize_key( $_GET['action'] ) ) {
            return;
        }

        // Yeterli yetki kontrolü
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to delete messages.', 'blitz-dock' ) );
        }

        // Mesaj ID’sini sanitize et ve doğrula
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) {
            wp_safe_redirect( admin_url( 'admin.php?page=blitz-dock-messages' ) );
            exit;
        }

        // Nonce doğrulaması
        check_admin_referer( 'bdp_delete_message_' . $id );

        // Veritabanından sil
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'bdp_messages',
            [ 'id' => $id ],
            [ '%d' ]
        );

        // Başarı mesajı ile geri yönlendir
        wp_safe_redirect( admin_url( 'admin.php?page=blitz-dock-messages&deleted=1' ) );
        exit;
    }

    /**
     * Enqueue the admin CSS.
     */
   public static function enqueue_admin_styles() {
        $css_url = plugin_dir_url( __FILE__ ) . '../../assets/css/admin.css';
        wp_enqueue_style(
            'blitz-dock-admin-style',
            $css_url,
            [],
            defined( 'BLITZ_DOCK_VERSION' ) ? BLITZ_DOCK_VERSION : '1.0'
        );
    }

  /**
     * AJAX handler to update message status.
     */
    public static function ajax_update_status() {
        check_ajax_referer( 'bdp_update_status', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'permission' );
        }

        $id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $status = sanitize_text_field( $_POST['status'] ?? '' );
        if ( ! $id || ! in_array( $status, [ 'pending', 'completed', 'canceled' ], true ) ) {
            wp_send_json_error( 'invalid' );
        }

        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'bdp_messages',
            [ 'status' => $status ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            wp_send_json_error( 'db' );
        }

       wp_send_json_success( [ 'css' => 'status-' . $status ] );
    }

    /**
     * AJAX handler to filter messages by status.
     */
    public static function ajax_filter_messages() {
       check_ajax_referer( 'bdp_filter_messages', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'permission' );
        }

        $status = sanitize_text_field( $_POST['status_filter'] ?? '' );
        $page   = max( 1, absint( $_POST['page'] ?? 1 ) );

        $valid_status = [ 'pending', 'completed', 'canceled' ];
        $where        = '';
        if ( $status && in_array( $status, $valid_status, true ) ) {
            global $wpdb;
            $where = $wpdb->prepare( 'WHERE status = %s', $status );
        } else {
            $status = '';
        }

        global $wpdb;
        $table    = $wpdb->prefix . 'bdp_messages';
        $per_page = 20;
        $offset   = ( $page - 1 ) * $per_page;

        $total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where} ORDER BY date_submitted DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        ob_start();
        $rows_file = BLITZ_DOCK_PATH . 'includes/AdminUI/messages-table.php';
        if ( file_exists( $rows_file ) ) {
            include $rows_file;
        }
        $rows = ob_get_clean();

        $base = admin_url( 'admin.php?page=blitz-dock-messages' );
        if ( $status ) {
            $base = add_query_arg( 'status_filter', $status, $base );
        }
        $pagination = paginate_links( [
            'base'      => add_query_arg( 'paged', '%#%', $base ),
            'format'    => '',
            'current'   => $page,
            'total'     => max( 1, ceil( $total / $per_page ) ),
            'prev_text' => esc_html__( '&laquo; Previous', 'blitz-dock' ),
            'next_text' => esc_html__( 'Next &raquo;', 'blitz-dock' ),
            'type'      => 'plain',
        ] );

        wp_send_json_success(
            [
                'rows'       => $rows,
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * AJAX: log analytics event
     */
    public static function ajax_log_event() {
        check_ajax_referer( 'bdp_log_event', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'permission' );
        }

        $t = sanitize_text_field( $_POST['event_type']   ?? '' );
        $o = sanitize_text_field( $_POST['event_topic']  ?? '' );
        $g = sanitize_text_field( $_POST['event_target'] ?? '' );
        $s = sanitize_text_field( $_POST['event_subtype']?? '' );

        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}bdp_analytics",
            compact( 't', 'o', 'g', 's' ) + [ 'created_at' => current_time( 'mysql' ) ],
            [ '%s', '%s', '%s', '%s', '%s' ]
        );

        wp_send_json_success();
    }

    /**
     * AJAX: filter analytics data
     */
    public static function ajax_filter_analytics() {
        check_ajax_referer( 'bdp_filter_analytics', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'permission' );
        }

       $start  = sanitize_text_field( $_POST['start_date'] ?? '' );
        $end    = sanitize_text_field( $_POST['end_date']   ?? '' );
        $topic  = sanitize_text_field( $_POST['topic']      ?? '' );
        $sub    = sanitize_text_field( $_POST['subtype']    ?? '' );
        $page   = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = 20;
        $offset = ( $page - 1 ) * $per_page;

        global $wpdb;
        $table = $wpdb->prefix . 'bdp_analytics';

        $where = [];
        if ( $start )  $where[] = $wpdb->prepare( 'DATE(created_at) >= %s', $start );
        if ( $end )    $where[] = $wpdb->prepare( 'DATE(created_at) <= %s', $end );
        if ( $topic && 'all' !== $topic ) $where[] = $wpdb->prepare( 'event_topic = %s', $topic );
        if ( $sub )    $where[] = $wpdb->prepare( 'event_subtype = %s', $sub );
        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

      $total      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where_sql}" );
        $total_pages = max( 1, ceil( $total / $per_page ) );
        $topic_rows  = $wpdb->get_results( "SELECT event_topic AS topic, COUNT(*) AS c FROM {$table} {$where_sql} GROUP BY event_topic" );
        $type_rows   = $wpdb->get_results( "SELECT event_type AS type, COUNT(*) AS c FROM {$table} {$where_sql} GROUP BY event_type" );
        $sub_where   = $where;
        $sub_where[] = "event_subtype <> ''";
        $sub_where_sql = 'WHERE ' . implode( ' AND ', $sub_where );
        $sub_rows    = $wpdb->get_results( "SELECT event_subtype AS subtype, COUNT(*) AS c FROM {$table} {$sub_where_sql} GROUP BY event_subtype" );

        $subtypes = [];
        if ( $topic && 'all' !== $topic ) {
            $subtypes = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT event_subtype FROM {$table} WHERE event_topic = %s AND event_subtype <> ''", $topic ) );
        }

        wp_send_json_success(
            [
              'events'      => $events,
                'total'       => $total,
                'topics'      => $topic_rows,
                'subtypes'    => $subtypes,
                'sub_counts'  => $sub_rows,
                'type_counts' => $type_rows,
                'page'        => $page,
                'total_pages' => $total_pages,
            ]
        );
    }
}
