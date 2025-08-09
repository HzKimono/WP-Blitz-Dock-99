<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// Messages table
$msg_table        = $wpdb->prefix . 'bdp_messages';
$pending_count    = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$msg_table} WHERE status = %s",
    'pending'
) );
$completed_count  = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$msg_table} WHERE status = %s",
    'completed'
) );
$canceled_count   = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$msg_table} WHERE status = %s",
    'canceled'
) );
$since_7d = date('Y-m-d 00:00:00', strtotime('-6 days'));
$statuses = [ 'completed', 'pending', 'canceled' ];
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE(date_submitted) AS d, status, COUNT(*) as c FROM {$msg_table} WHERE date_submitted >= %s GROUP BY DATE(date_submitted), status",
        $since_7d
    ),
    ARRAY_A
);
$daily_counts = [];
for ( $i = 6; $i >= 0; $i-- ) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    foreach ( $statuses as $s ) {
        $daily_counts[ $s ][ $d ] = 0;
    }
}
foreach ( $rows as $r ) {
    $s = $r['status'];
    $daily_counts[ $s ][ $r['d'] ] = (int) $r['c'];
}
$chart_labels  = array_map( function( $d ) { return date( 'M j', strtotime( $d ) ); }, array_keys( $daily_counts['completed'] ) );
$completed_7d  = array_sum( $daily_counts['completed'] );
$pending_7d    = array_sum( $daily_counts['pending'] );
$canceled_7d   = array_sum( $daily_counts['canceled'] );
$total_7d      = $completed_7d + $pending_7d + $canceled_7d;
$completed_pct = $total_7d ? round( $completed_7d / $total_7d * 100 ) : 0;
$pending_pct   = $total_7d ? round( $pending_7d / $total_7d * 100 ) : 0;
$canceled_pct  = $total_7d ? round( $canceled_7d / $total_7d * 100 ) : 0;
$chart_values  = [
    'completed' => array_values( $daily_counts['completed'] ),
    'pending'   => array_values( $daily_counts['pending'] ),
    'canceled'  => array_values( $daily_counts['canceled'] ),
];
// Events table
$events_table     = $wpdb->prefix . 'bdp_analytics';
$total_events     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$events_table}" );

// Total messages sent (same as total rows in bdp_messages)
$total_messages   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$msg_table}" );

// Most clicked link (existing logic)
$most_clicked_row  = $wpdb->get_row( "SELECT event_target, COUNT(*) as c FROM {$events_table} WHERE event_type = 'click' AND event_target NOT IN ('social_links','ecomm_links') GROUP BY event_target ORDER BY c DESC LIMIT 1" );
$most_clicked_link = $most_clicked_row ? $most_clicked_row->event_target : '';

global $wpdb;

// — Stored options —
$social_links_raw   = get_option( 'bdp_social_links', [] );
$social_links       = (is_array( $social_links_raw ) && !empty($social_links_raw)) ? $social_links_raw : [];

$ecomm_links_raw    = get_option( 'bdp_ecomm_links', [] );
$ecomm_links        = is_array( $ecomm_links_raw ) ? $ecomm_links_raw : [ [ 'name' => '', 'url' => '' ] ];

$location_embed    = get_option( 'bdp_location_embed', '' );

$position           = get_option( 'bdp_dock_position', 'bottom_right' );
$visibility_matrix  = get_option( 'bdp_visibility_matrix', [] );
$theme_color        = get_option( 'bdp_theme_color', '#0073aa' );
$cta_message        = get_option( 'bdp_cta_message', '' ); // <== FIXED LINE
$header_title       = get_option( 'bdp_header_title', 'How can we help?' );
$custom_avatar      = get_option( 'bdp_custom_avatar_url', '' );
$selected_avatar    = get_option( 'bdp_selected_avatar', 'avatar1.png' );

$icons_url           = defined('BLITZ_DOCK_URL') ? BLITZ_DOCK_URL . 'assets/icons/' : '';
$avatars_url         = defined('BLITZ_DOCK_URL') ? BLITZ_DOCK_URL . 'assets/avatars/' : '';
$ecomm_icons_url     = defined('BLITZ_DOCK_URL') ? BLITZ_DOCK_URL . 'assets/ecomm-icons/' : '';
$menu_icons_url      = defined('BLITZ_DOCK_URL') ? BLITZ_DOCK_URL . 'assets/icons/menu/' : '';

$tab_icons = [
  'dashboard' => 'menu-dashboard.png',
  'help'      => 'menu-faq.png',
  'social'    => 'menu-social-media.png',
  'settings'  => 'menu-dock-settings.png',
  'ecomm'     => 'menu-online-stores.png',
  'location'  => 'menu-location.png',
  'messages'  => 'menu-messages.png',
  'analytics' => 'menu-analytics.png',
];
$available_platforms = \BlitzDock\get_social_platforms();
$available_ecomm     = [ 'amazon','hepsiburada','sahibinden','shopify','temu','trendyol' ];
$available_pages     = [
  'front_page' => 'Front Page',
  'blog'       => 'Blog Posts',
  'pages'      => 'Pages',
  'products'   => 'Product Pages',
];

$faq_items_raw = get_option( 'bdp_faq_items', [] );
$social_count  = count( array_filter( $social_links, function( $r ) { return ! empty( $r['platform'] ) || ! empty( $r['url'] ); } ) );
$ecomm_count   = count( array_filter( $ecomm_links, function( $r ) { return ! empty( $r['name'] ) || ! empty( $r['url'] ); } ) );
$faq_count     = count( array_filter( $faq_items_raw, function( $r ) { return ! empty( $r['question'] ) || ! empty( $r['answer'] ); } ) );

$tabs = [
  'dashboard' => 'Dashboard',
  'help'      => 'SSS',
  'social'    => 'Sosyal Medya',
  'settings'  => 'Dock Ayarları',
  'ecomm'     => 'Online Mağazalar',
  'location'  => 'Location',
  'messages'  => 'Messages',
  'analytics' => 'Analytics',
];
$bdp_nav_items = [];
foreach ( $tabs as $slug => $label ) {
  $bdp_nav_items[] = [
    'slug'  => $slug,
    'label' => $label,
    'icon'  => $tab_icons[ $slug ] ?? '',
  ];
}
?>

<div class="bdp-admin-layout">

  <aside class="bdp-sidebar" aria-label="Blitz Dock Navigation">
    <div class="bdp-sidebar__inner">
      <div class="bdp-header">
        <img class="bdp-header__logo"
             src="<?php echo esc_url( BLITZ_DOCK_URL . 'assets/images/blitz-dock.png' ); ?>"
             alt="Blitz Dock">
        <div class="bdp-header__caption">BLITZ DOCK VERSION <?php echo esc_html( BLITZ_DOCK_VERSION ); ?></div>
      </div>
     

      <nav>
        <ul class="bdp-tabs" role="tablist" aria-orientation="vertical">
          <?php
          foreach ( $bdp_nav_items as $i => $item ) :
            $slug   = $item['slug'];
            $label  = $item['label'];
            $icon   = $item['icon'];
            $active = ( 0 === $i ) ? 'true' : 'false';
          ?>
            <li role="presentation">
              <button
                type="button"
                id="tab-<?php echo esc_attr( $slug ); ?>"
                class="bdp-tab"
                role="tab"
                aria-controls="panel-<?php echo esc_attr( $slug ); ?>"
                aria-selected="<?php echo esc_attr( $active ); ?>"
                tabindex="<?php echo ( 'true' === $active ) ? '0' : '-1'; ?>">
                <img class="bdp-tab__icon"
                     src="<?php echo esc_url( BLITZ_DOCK_URL . 'assets/icons/menu/' . $icon ); ?>"
                     alt="" aria-hidden="true">
                <span class="bdp-tab__label"><?php echo esc_html( $label ); ?></span>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
  </aside>

  <main class="bdp-content" id="bdp-content" tabindex="-1">
    <div id="panel-dashboard" role="tabpanel" aria-labelledby="tab-dashboard">
      <?php
        $msg_table  = $wpdb->prefix . 'bdp_messages';
        $msg_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$msg_table}" );
      ?>
      <h2>Blitz Dock v<?php echo BLITZ_DOCK_VERSION; ?></h2>
      <div class="bdp-messages-card">
        <div class="bdp-card-header">
          <div>
            <h3>Total Messages</h3>
            <small>Last 7 days</small>
          </div>
        <div class="bdp-total-count"><?php echo esc_html( $total_7d ); ?></div>
        </div>
        <div class="bdp-chart-wrap">
          <canvas id="bdp-messages-chart" height="80"></canvas>
        </div>
        <div class="bdp-status-breakdown">
          <button type="button" class="bdp-status-label completed" data-status="completed" aria-pressed="false">Completed <?php echo esc_html( $completed_7d ); ?> (<?php echo esc_html( $completed_pct ); ?>%)</button>
          <button type="button" class="bdp-status-label pending" data-status="pending" aria-pressed="false">Pending <?php echo esc_html( $pending_7d ); ?> (<?php echo esc_html( $pending_pct ); ?>%)</button>
          <button type="button" class="bdp-status-label canceled" data-status="canceled" aria-pressed="false">Canceled <?php echo esc_html( $canceled_7d ); ?> (<?php echo esc_html( $canceled_pct ); ?>%)</button>
        </div>
      </div>
     <script>
        window.bdpDashboardData = <?php echo wp_json_encode( [
            'labels'    => $chart_labels,
            'completed' => $chart_values['completed'],
            'pending'   => $chart_values['pending'],
            'canceled'  => $chart_values['canceled'],
        ] ); ?>;
      </script>
    </div>

   <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" enctype="multipart/form-data">
      <?php wp_nonce_field( 'blitz_dock_settings_verify' ); ?>
      <input type="hidden" name="action" value="blitz_dock_save_settings">

      <!-- SSS – FAQ -->
      <div id="panel-help" role="tabpanel" aria-labelledby="tab-help" hidden>
        <h2><?php esc_html_e( 'SSS – Frequently Asked Questions', 'blitz-dock' ); ?></h2>
        <?php
       $faq_items = get_option( 'bdp_faq_items', [] );
        if ( empty( $faq_items ) ) $faq_items = [ [ 'question' => '', 'answer' => '' ] ];
        ?>
           <div id="bdp-faq-items">
          <?php foreach ( $faq_items as $i => $faq ) : ?>
            <?php if ( empty( $faq['question'] ) && empty( $faq['answer'] ) ) continue; ?>
            <div class="bdp-faq-card">
              <div class="bdp-faq-field">
                <label for="bdp_faq_question_<?php echo $i; ?>" class="bdp-faq-label"><?php esc_html_e( 'Add a Question', 'blitz-dock' ); ?></label>
                <input type="text"
                       id="bdp_faq_question_<?php echo $i; ?>"
                       name="bdp_faq_items[<?php echo $i; ?>][question]"
                       value="<?php echo esc_attr( $faq['question'] ); ?>"
                       placeholder="<?php esc_attr_e( 'Question', 'blitz-dock' ); ?>" />
              </div>
              <div class="bdp-faq-field">
                <label for="bdp_faq_answer_<?php echo $i; ?>" class="bdp-faq-label"><?php esc_html_e( 'Provide an Answer', 'blitz-dock' ); ?></label>
                <textarea id="bdp_faq_answer_<?php echo $i; ?>"
                          name="bdp_faq_items[<?php echo $i; ?>][answer]"
                          rows="3"
                          placeholder="<?php esc_attr_e( 'Answer', 'blitz-dock' ); ?>"><?php echo esc_textarea( $faq['answer'] ); ?></textarea>
              </div>
              <button type="button" class="button bdp-remove-faq"><?php esc_html_e( 'Remove', 'blitz-dock' ); ?></button>
            </div>
          <?php endforeach; ?>
        </div>
      <button type="button" class="button" id="bdp-add-faq"><?php esc_html_e( '+ Add FAQ Item', 'blitz-dock' ); ?></button>
      </div>

       <!-- Sosyal Medya -->
      <div id="panel-social" role="tabpanel" aria-labelledby="tab-social" hidden>
        <h2><?php esc_html_e( 'Sosyal Medya Bağlantıları', 'blitz-dock' ); ?></h2>
        <div id="bdp-social-links">
          <?php
          foreach ($social_links as $i => $row) :
            if (empty($row['platform']) && empty($row['url'])) continue;
            $platform = isset($row['platform']) ? esc_attr($row['platform']) : '';
            $url = isset($row['url']) ? esc_url($row['url']) : '';
          ?>
            <div class="bdp-social-row">
              <img src="<?php echo esc_url($icons_url . ($platform ? $platform : 'default') . '.png'); ?>" width="24" height="24">
              <select name="bdp_social_links[<?php echo $i; ?>][platform]">
                <option value=""><?php esc_html_e('Platform Seç', 'blitz-dock'); ?></option>
                <?php foreach ( $available_platforms as $slug => $label ) : ?>
                  <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $platform, $slug ); ?>>
                    <?php echo esc_html( $label ); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="url" name="bdp_social_links[<?php echo $i; ?>][url]" value="<?php echo $url; ?>" placeholder="https://">
              <button type="button" class="button bdp-remove-link">Sil</button>
            </div>
           <?php endforeach; ?>
        </div>
        <?php if (empty(array_filter($social_links, function($r){return !empty($r['platform']) || !empty($r['url']);}))) : ?>
          <div id="no-social-message" style="color:#888; margin:1em 0;">
            <?php esc_html_e('Henüz sosyal medya bağlantısı eklenmemiş. Eklemek için butona tıklayın.', 'blitz-dock'); ?>
          </div>
        <?php endif; ?>
        <button type="button" class="button" id="bdp-add-link"><?php esc_html_e( '+ Yeni Sosyal Medya Bağlantısı Ekle', 'blitz-dock' ); ?></button>
      </div>

      <!-- Dock Ayarları -->
      <div id="panel-settings" role="tabpanel" aria-labelledby="tab-settings" hidden>
        <h2><?php esc_html_e( 'Dock & Görünürlük Ayarları', 'blitz-dock' ); ?></h2>
        <table class="form-table">
          <tr>
            <th><?php esc_html_e( 'Dock Pozisyonu', 'blitz-dock' ); ?></th>
            <td>
              <select name="bdp_dock_position">
                <option value="bottom_right" <?php selected( $position, 'bottom_right' ); ?>><?php esc_html_e( 'Sağ Alt', 'blitz-dock' ); ?></option>
                <option value="bottom_left"  <?php selected( $position, 'bottom_left' );  ?>><?php esc_html_e( 'Sol Alt', 'blitz-dock' ); ?></option>
              </select>
            </td>
          </tr>
          <tr>
            <th><?php esc_html_e( 'Tema Rengi', 'blitz-dock' ); ?></th>
            <td><input type="color" name="bdp_theme_color" value="<?php echo esc_attr( $theme_color ); ?>" /></td>
          </tr>
          <tr>
            <th><?php esc_html_e( 'Sayfa Bazlı Görünürlük', 'blitz-dock' ); ?></th>
            <td>
              <?php foreach ( $available_pages as $key => $label ) : ?>
                <label style="display:block;margin-bottom:4px;">
                  <input type="checkbox" name="bdp_visibility_matrix[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( !empty( $visibility_matrix[ $key ] ) ); ?> />
                  <?php echo esc_html( $label ); ?>
                </label>
              <?php endforeach; ?>
            </td>
          </tr>
          <tr>
            <th><?php esc_html_e( 'Panel Başlığı', 'blitz-dock' ); ?></th>
            <td><input type="text" name="bdp_header_title" value="<?php echo esc_attr( $header_title ); ?>" /></td>
          </tr>
          <tr>
            <th><?php esc_html_e( 'Açılış Mesajı', 'blitz-dock' ); ?></th>
            <td><textarea name="bdp_cta_message" rows="2"><?php echo esc_textarea( $cta_message ); ?></textarea></td>
          </tr>
          <tr>
            <th><?php esc_html_e( 'Avatar', 'blitz-dock' ); ?></th>
            <td>
              <div id="bdp-avatar-picker">
                <?php $preview_src = $custom_avatar ? $custom_avatar : $avatars_url . $selected_avatar; ?>
                <img id="bdp-avatar-preview" src="<?php echo esc_url( $preview_src ); ?>" width="64" height="64" style="border-radius:50%;" alt="<?php esc_attr_e( 'Selected avatar', 'blitz-dock' ); ?>" />
                <input type="hidden" name="bdp_selected_avatar" id="bdp-selected-avatar" value="<?php echo esc_attr( $selected_avatar ); ?>" />
              </div>
            </td>
          </tr>
        </table>
        </table>

     <!-- Avatar Modal -->
        <div id="bdp-avatar-modal" class="bdp-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bdp-avatar-modal-title" tabindex="-1">
          <div class="bdp-modal-content">
            <button type="button" id="bdp-avatar-close" class="bdp-close" aria-label="<?php esc_attr_e( 'Close', 'blitz-dock' ); ?>">&times;</button>
            <h2 id="bdp-avatar-modal-title"><?php esc_html_e( 'Choose an Avatar', 'blitz-dock' ); ?></h2>
            <label for="bdp-avatar-search" class="screen-reader-text"><?php esc_html_e( 'Search avatars', 'blitz-dock' ); ?></label>
            <div class="bdp-avatar-grid" id="bdp-avatar-grid">
              <?php for ( $i = 1; $i <= 10; $i++ ) : $file = "avatar-{$i}.png"; ?>
                <button type="button" class="bdp-avatar-choice <?php echo $selected_avatar === $file ? 'selected' : ''; ?>" data-avatar="<?php echo esc_attr( $file ); ?>">
                  <img src="<?php echo esc_url( $avatars_url . $file ); ?>" alt="<?php printf( esc_attr__( 'Avatar %d', 'blitz-dock' ), $i ); ?>" width="64" height="64" />
                </button>
              <?php endfor; ?>
            </div>
            <div class="bdp-avatar-upload">
              <label>
                <?php esc_html_e( 'Upload custom avatar', 'blitz-dock' ); ?>
                <input type="file" name="bdp_custom_avatar" id="bdp-custom-avatar" accept="image/*" />
              </label>
            </div>
          </div>
        </div>
      </div>

  <!-- Online Mağazalar -->
      <div id="panel-ecomm" role="tabpanel" aria-labelledby="tab-ecomm" hidden>
        <h2><?php esc_html_e( 'Online Mağazalar Bağlantıları', 'blitz-dock' ); ?></h2>
        <div id="bdp-ecomm-items">
          <?php
          if ( empty( $ecomm_links ) ) $ecomm_links = [ [ 'name' => '', 'url' => '' ] ];
          foreach ( $ecomm_links as $i => $link ) :
          if(empty($link['name']) && empty($link['url'])) continue;
            $name = isset($link['name']) ? esc_attr($link['name']) : '';
            $url  = isset($link['url']) ? esc_url($link['url']) : '';
          ?>
            <div class="bdp-ecomm-row">
              <img src="<?php echo esc_url( $ecomm_icons_url . ($name ? $name : 'default') . '.png' ); ?>" width="24" height="24" class="bdp-ecomm-icon">
              <select name="bdp_ecomm_links[<?php echo $i; ?>][name]" class="bdp-ecomm-select">
                <option value=""><?php esc_html_e( 'Platform Seç', 'blitz-dock' ); ?></option>
                <?php foreach ( $available_ecomm as $slug ) : ?>
                  <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $name, $slug ); ?>>
                    <?php echo ucfirst( $slug ); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="url" name="bdp_ecomm_links[<?php echo $i; ?>][url]" value="<?php echo $url; ?>" placeholder="https://">
              <button type="button" class="button bdp-remove-ecomm">Sil</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="bdp-add-ecomm"><?php esc_html_e( '+ Yeni Bağlantı Ekle', 'blitz-dock' ); ?></button>
      </div>
   <!-- Google Map -->
      <div id="panel-location" role="tabpanel" aria-labelledby="tab-location" hidden>
        <h2><?php esc_html_e( 'Google Map', 'blitz-dock' ); ?></h2>

      <div class="bdp-map-card">
          <label for="bdp_location_embed" class="bdp-map-label">
            <?php esc_html_e( 'API Key', 'blitz-dock' ); ?>
          </label>
          <p class="description">
            <?php esc_html_e( 'Enter a Google Maps API key or paste your iframe embed code.', 'blitz-dock' ); ?>
            <a href="https://developers.google.com/maps" target="_blank" rel="noopener">
              <?php esc_html_e( 'Need help?', 'blitz-dock' ); ?>
            </a>
          </p>
          <textarea
            name="bdp_location_embed"
            id="bdp_location_embed"
            rows="3"
            placeholder="<?php esc_attr_e( 'Paste your API Key or iframe embed code here', 'blitz-dock' ); ?>"
          ><?php echo esc_textarea( $location_embed ); ?></textarea>

       <div
            id="bdp-map-preview"
            class="bdp-map-preview"
            data-placeholder="<?php echo esc_attr__( 'Enter your API Key or Google Maps embed code to preview the map.', 'blitz-dock' ); ?>"
            data-error="<?php echo esc_attr__( 'Unable to load map. Check your API key.', 'blitz-dock' ); ?>"
          >
            <?php if ( $location_embed ) : ?>
              <?php if ( 0 === strpos( trim( $location_embed ), '<iframe' ) ) : ?>
                <?php echo wp_kses_post( html_entity_decode( $location_embed ) ); ?>
              <?php else : ?>
                <div id="bdp-map-canvas"></div>
              <?php endif; ?>
            <?php else : ?>
              <p class="bdp-map-placeholder"><?php esc_html_e( 'Enter your API Key or Google Maps embed code to preview the map.', 'blitz-dock' ); ?></p>
            <?php endif; ?>
           </div>
        </div>
      </div>
      
      <?php submit_button(); ?>
      </form>

    <?php
    $extra_panels = [
      'messages'  => [ '\\BlitzDock\\Admin\\Admin', 'render_messages_page' ],
      'analytics' => [ '\\BlitzDock\\Admin\\Admin', 'render_analytics_page' ],
    ];
    foreach ( $extra_panels as $slug => $cb ) {
      echo '<div id="panel-' . esc_attr( $slug ) . '" role="tabpanel" aria-labelledby="tab-' . esc_attr( $slug ) . '" hidden>';
      call_user_func( $cb );
      echo '</div>';
    }
    ?>
  </main>

</div>