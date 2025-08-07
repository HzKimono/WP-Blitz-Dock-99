<?php
/**
 * Outputs rows for the messages table.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $messages ) ) :
?>
<tr class="bdp-row">
    <td colspan="6"><?php esc_html_e( 'No messages found.', 'blitz-dock' ); ?></td>
</tr>
<?php
else :
    foreach ( $messages as $msg ) :
        $delete_url = wp_nonce_url(
            admin_url( 'admin.php?page=blitz-dock-messages&action=bdp_delete_message&id=' . absint( $msg->id ) ),
            'bdp_delete_message_' . absint( $msg->id )
        );
        ?>
<?php $status = esc_attr( $msg->status ); ?>
<tr class="bdp-row status-<?php echo $status; ?>">
    <td><?php echo esc_html( $msg->date_submitted ); ?></td>
    <td><?php echo esc_html( $msg->name ); ?></td>
    <td><?php echo esc_html( $msg->email ); ?></td>
    <td><?php echo esc_html( wp_trim_words( $msg->message, 10, '...' ) ); ?></td>
    <td>
        <select class="bdp-status-select" data-id="<?php echo esc_attr( $msg->id ); ?>">
            <option value="pending"   <?php selected( $msg->status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'blitz-dock' ); ?></option>
            <option value="completed" <?php selected( $msg->status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'blitz-dock' ); ?></option>
            <option value="canceled"  <?php selected( $msg->status, 'canceled' ); ?>><?php esc_html_e( 'Canceled', 'blitz-dock' ); ?></option>
        </select>
    </td>
    <td>
        <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( esc_html__( 'Are you sure you want to delete this message?', 'blitz-dock' ) ); ?>');">
            <?php esc_html_e( 'Delete', 'blitz-dock' ); ?>
        </a>
    </td>
</tr>
<?php
    endforeach;
endif;