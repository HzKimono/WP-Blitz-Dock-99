<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$sessions_table  = $wpdb->prefix . 'bdp_live_chat_sessions';
$messages_table  = $wpdb->prefix . 'bdp_live_chat_messages';

// Accept chat via link
if ( isset( $_GET['accept'] ) && current_user_can( 'manage_options' ) ) {
    $id = (int) $_GET['accept'];
    check_admin_referer( 'bdp_accept_' . $id );
    $wpdb->update( $sessions_table, [ 'status' => 'active' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
    wp_safe_redirect( admin_url( 'admin.php?page=blitz-dock-live-chat&chat_id=' . $id ) );
    exit;
}

// Close chat via link
if ( isset( $_GET['close'] ) && current_user_can( 'manage_options' ) ) {
    $id = (int) $_GET['close'];
    check_admin_referer( 'bdp_close_' . $id );
    $wpdb->update( $sessions_table, [ 'status' => 'closed' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
    wp_safe_redirect( admin_url( 'admin.php?page=blitz-dock-live-chat' ) );
    exit;
}

$chat_id = isset( $_GET['chat_id'] ) ? (int) $_GET['chat_id'] : 0;

if ( $chat_id ) {
    $session  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$sessions_table} WHERE id=%d", $chat_id ) );
    $messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$messages_table} WHERE chat_id=%d ORDER BY id ASC", $chat_id ) );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( sprintf( __( 'Chat with %s', 'blitz-dock' ), $session->name ) ); ?><?php if ( $session->status === 'closed' ) echo ' - ' . esc_html__( 'Closed', 'blitz-dock' ); ?></h1>
        <div id="bdp-admin-chat-history" style="border:1px solid #ddd; padding:10px; height:300px; overflow-y:auto; background:#fff;">
            <?php foreach ( $messages as $msg ) : ?>
                <p><strong><?php echo esc_html( ( $msg->sender_type === 'admin' ) ? __( '🛠️ Support Team:', 'blitz-dock' ) : '👤 ' . $msg->sender_name . ':' ); ?></strong> <?php echo esc_html( $msg->message ); ?></p>
            <?php endforeach; ?>
        </div>
        <?php if ( $session->status !== 'closed' ) : ?>
        <form id="bdp-admin-chat-form" data-chat-id="<?php echo esc_attr( $chat_id ); ?>" style="margin-top:1em;">
            <?php wp_nonce_field( 'bdp_message_nonce', 'bdp_admin_chat_nonce' ); ?>
            <input type="text" name="message" style="width:80%;" />
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Send', 'blitz-dock' ); ?></button>
            <?php $close = wp_nonce_url( admin_url( 'admin.php?page=blitz-dock-live-chat&close=' . $chat_id ), 'bdp_close_' . $chat_id ); ?>
            <a class="button" href="<?php echo esc_url( $close ); ?>" style="margin-left:8px;"><?php esc_html_e( 'Close Chat', 'blitz-dock' ); ?></a>
        </form>
        <?php endif; ?>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=blitz-dock-live-chat' ) ); ?>" style="margin-top:1em;display:inline-block;"><?php esc_html_e( 'Back', 'blitz-dock' ); ?></a>
    </div>
    <?php
    return;
}

$pending = $wpdb->get_results( "SELECT s.*, (SELECT message FROM {$messages_table} WHERE chat_id=s.id ORDER BY id ASC LIMIT 1) AS first_message FROM {$sessions_table} s WHERE s.status='pending' ORDER BY s.created_at DESC" );
$active  = $wpdb->get_results( "SELECT * FROM {$sessions_table} WHERE status='active' ORDER BY created_at DESC" );
$closed  = $wpdb->get_results( "SELECT * FROM {$sessions_table} WHERE status='closed' ORDER BY created_at DESC" );
$feedback_table = $wpdb->prefix . 'bdp_chat_feedback';
$avg_rating = $wpdb->get_var( "SELECT AVG(rating) FROM {$feedback_table}" );
?>
<div class="wrap">
  <h1><?php esc_html_e( 'Live Chat Sessions', 'blitz-dock' ); ?></h1>
  <h2><?php esc_html_e( 'Pending Requests', 'blitz-dock' ); ?></h2>
   <table class="widefat">
    <thead>
      <tr>
        <th><?php esc_html_e( 'Name', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Email', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Phone', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'First Message', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Date', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Actions', 'blitz-dock' ); ?></th>
      </tr>
    </thead>
    <tbody id="bdp-pending-chats">
     <?php if ( $pending ) : foreach ( $pending as $p ) : ?>
      <tr>
        <td><?php echo esc_html( $p->name ); ?></td>
        <td><?php echo esc_html( $p->email ); ?></td>
        <td><?php echo esc_html( $p->phone ); ?></td>
        <td><?php echo esc_html( mb_strimwidth( $p->first_message, 0, 40, '...' ) ); ?></td>
        <td><?php echo esc_html( $p->created_at ); ?></td>
        <td>
          <button type="button" class="button bdp-accept-chat" data-id="<?php echo esc_attr( $p->id ); ?>">📩 <?php esc_html_e( 'Accept', 'blitz-dock' ); ?></button>
        </td>
      </tr>
      <?php endforeach; else : ?>
      <tr><td colspan="6"><?php esc_html_e( 'No pending requests.', 'blitz-dock' ); ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>

 <h2 style="margin-top:2em;"><?php esc_html_e( 'Active Chats', 'blitz-dock' ); ?></h2>
  <table class="widefat">
    <thead>
      <tr>
        <th><?php esc_html_e( 'Name', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Email', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Phone', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Date', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Actions', 'blitz-dock' ); ?></th>
      </tr>
    </thead>
    <tbody id="bdp-active-chats">
      <?php if ( $active ) : foreach ( $active as $p ) : ?>
      <tr>
        <td><?php echo esc_html( $p->name ); ?></td>
        <td><?php echo esc_html( $p->email ); ?></td>
        <td><?php echo esc_html( $p->phone ); ?></td>
        <td><?php echo esc_html( $p->created_at ); ?></td>
        <td>
          <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=blitz-dock-live-chat&chat_id=' . $p->id ) ); ?>"><?php esc_html_e( 'Open', 'blitz-dock' ); ?></a>
          <?php $close = wp_nonce_url( admin_url( 'admin.php?page=blitz-dock-live-chat&close=' . $p->id ), 'bdp_close_' . $p->id ); ?>
          <a class="button" href="<?php echo esc_url( $close ); ?>" style="margin-left:4px;"><?php esc_html_e( 'Close', 'blitz-dock' ); ?></a>
        </td>
      </tr>
      <?php endforeach; else : ?>
      <tr><td colspan="5"><?php esc_html_e( 'No active chats.', 'blitz-dock' ); ?></td></tr>
      <?php endif; ?>
    </tbody>
 </table>

  <h2 style="margin-top:2em;"><?php esc_html_e( 'Closed Chats', 'blitz-dock' ); ?></h2>
  <?php if ( $avg_rating ) : ?>
    <p><?php echo esc_html( sprintf( __( 'Average Rating: %.1f / 5', 'blitz-dock' ), $avg_rating ) ); ?></p>
  <?php endif; ?>
  <table class="widefat">
    <thead>
      <tr>
        <th><?php esc_html_e( 'Name', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Email', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Date', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Messages', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Duration', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Feedback', 'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Actions', 'blitz-dock' ); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if ( $closed ) : foreach ( $closed as $c ) :
        $first_time = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$messages_table} WHERE chat_id=%d ORDER BY id ASC LIMIT 1", $c->id ) );
        $last_time  = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$messages_table} WHERE chat_id=%d ORDER BY id DESC LIMIT 1", $c->id ) );
        $msg_count  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$messages_table} WHERE chat_id=%d", $c->id ) );
        $rating_row = $wpdb->get_row( $wpdb->prepare( "SELECT rating, comment FROM {$feedback_table} WHERE chat_id=%d", $c->id ) );
        $duration   = ( $first_time && $last_time ) ? human_time_diff( strtotime( $first_time ), strtotime( $last_time ) ) : '';
      ?>
      <tr>
        <td><?php echo esc_html( $c->name ); ?></td>
        <td><?php echo esc_html( $c->email ); ?></td>
        <td><?php echo esc_html( $c->created_at ); ?></td>
        <td><?php echo esc_html( $msg_count ); ?></td>
        <td><?php echo esc_html( $duration ); ?></td>
        <td><?php if ( $rating_row ) { echo str_repeat( '⭐', (int) $rating_row->rating ); if ( $rating_row->comment ) echo ' <span title="' . esc_attr( $rating_row->comment ) . '">📝</span>'; } else { echo '&#8211;'; } ?></td>
        <td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=blitz-dock-live-chat&chat_id=' . $c->id ) ); ?>"><?php esc_html_e( 'View', 'blitz-dock' ); ?></a></td>
      </tr>
      <?php endforeach; else : ?>
      <tr><td colspan="7"><?php esc_html_e( 'No closed chats.', 'blitz-dock' ); ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>