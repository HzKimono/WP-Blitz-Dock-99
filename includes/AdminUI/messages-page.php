<?php
/**
 * Admin Messages Page Template
 *
 * Lists all messages submitted via Blitz Dock and provides a secure Delete action.
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$table = $wpdb->prefix . 'bdp_messages';

// Show success notice if a message was deleted.
if ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) {
    echo '<div class="notice notice-success is-dismissible"><p>'
       . esc_html__( 'Message deleted successfully.', 'blitz-dock' )
       . '</p></div>';
}

// Pagination setup
$per_page = 20;
$page     = max( 1, absint( $_GET['paged'] ?? 1 ) );
$offset   = ( $page - 1 ) * $per_page;

// Status filter
$status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( $_GET['status_filter'] ) : '';
$valid_status  = [ 'pending', 'completed', 'canceled' ];
$where         = '';
if ( $status_filter && in_array( $status_filter, $valid_status, true ) ) {
    $where = $wpdb->prepare( 'WHERE status = %s', $status_filter );
} else {
    $status_filter = '';
}

// Total messages count
$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );

// Fetch messages for current page
$messages = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY date_submitted DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Blitz Dock Messages', 'blitz-dock' ); ?></h1>

     <form id="bdp-filter-form" method="post" style="margin-bottom:10px;">
        <?php wp_nonce_field( 'bdp_filter_messages' ); ?>
        <label for="bdp_filter_status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'blitz-dock' ); ?></label>
        <select id="bdp_filter_status" name="status_filter">
            <option value="" <?php selected( $status_filter, '' ); ?>><?php esc_html_e( 'All', 'blitz-dock' ); ?></option>
            <option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending', 'blitz-dock' ); ?></option>
            <option value="completed" <?php selected( $status_filter, 'completed' ); ?>><?php esc_html_e( 'Completed', 'blitz-dock' ); ?></option>
            <option value="canceled" <?php selected( $status_filter, 'canceled' ); ?>><?php esc_html_e( 'Canceled', 'blitz-dock' ); ?></option>
        </select>
    </form>

    <div id="messages-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Date', 'blitz-dock' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Name', 'blitz-dock' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Email', 'blitz-dock' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Message', 'blitz-dock' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Status', 'blitz-dock' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Actions', 'blitz-dock' ); ?></th>
                </tr>
            </thead>
            <tbody id="bdp-messages-body">
                <?php
                $rows_file = BLITZ_DOCK_PATH . 'includes/AdminUI/messages-table.php';
                if ( file_exists( $rows_file ) ) {
                    include $rows_file;
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="messages-pagination">
        <?php
        $base = admin_url( 'admin.php?page=blitz-dock-messages' );
        if ( $status_filter ) {
            $base = add_query_arg( 'status_filter', $status_filter, $base );
        }
        echo paginate_links( [
            'base'      => add_query_arg( 'paged', '%#%', $base ),
            'format'    => '',
            'current'   => $page,
            'total'     => max( 1, ceil( $total / $per_page ) ),
            'prev_text' => esc_html__( '&laquo; Previous', 'blitz-dock' ),
            'next_text' => esc_html__( 'Next &raquo;', 'blitz-dock' ),
        ] );
        ?>
    </div>
</div>