<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table  = $wpdb->prefix . 'bdp_analytics';

// --- 1) Verileri Hesapla ---
$total_events      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
$total_clicks      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_type = %s", 'click' ) );
$total_views       = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_type = %s", 'view' ) );
$total_submissions = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_type = %s", 'submit' ) );

// --- 2) Kutucuk Tanımları ---
$stats = [
    'total_events'    => [
        'label' => __( 'Total Events', 'blitz-dock' ),
        'icon'  => 'analytics-total-events.png',
        'count' => $total_events,
    ],
    'total_clicks'    => [
        'label' => __( 'Clicks', 'blitz-dock' ),
        'icon'  => 'analytics-total-clicks.png',
        'count' => $total_clicks,
    ],
    'views'           => [
        'label' => __( 'Views', 'blitz-dock' ),
        'icon'  => 'analytics-views.png',
        'count' => $total_views,
    ],
    'submissions'     => [
        'label' => __( 'Submissions', 'blitz-dock' ),
        'icon'  => 'analytics-submissions.png',
        'count' => $total_submissions,
    ],
    // Mevcut kutucuklar
    'social_links'    => [ 'label' => __( 'Social Links',    'blitz-dock' ), 'icon' => 'analytics-social-links.png' ],
    'ecomm_links'     => [ 'label' => __( 'Online Stores',   'blitz-dock' ), 'icon' => 'analytics-online-stores.png' ],
    'faq'             => [ 'label' => __( 'FAQ',              'blitz-dock' ), 'icon' => 'analytics-faq.png' ],
    'location'        => [ 'label' => __( 'Location',        'blitz-dock' ), 'icon' => 'analytics-location.png' ],
    'message'         => [ 'label' => __( 'Leave a Message', 'blitz-dock' ), 'icon' => 'analytics-messages.png' ],
];

// click event topic bazlı sayıları da doldur
$results = $wpdb->get_results( "SELECT event_topic, COUNT(*) AS c FROM {$table} WHERE event_type = 'click' GROUP BY event_topic", OBJECT_K );
foreach ( $stats as $slug => $info ) {
    if ( ! isset( $info['count'] ) && isset( $results[ $slug ] ) ) {
        $stats[ $slug ]['count'] = (int) $results[ $slug ]->c;
    }
}

// distinct topic listesi
$topics    = $wpdb->get_col( "SELECT DISTINCT event_topic FROM {$table}" );
$icons_url = defined( 'BLITZ_DOCK_URL' ) ? BLITZ_DOCK_URL . 'assets/icons/analytics/' : '';
?>
<div class="wrap">
  <h1><?php esc_html_e( 'Blitz Dock Analytics', 'blitz-dock' ); ?></h1>

  <!-- 3) İkonlu Stat Box’lar -->
  <div class="bdp-stat-boxes">
    <?php foreach ( $stats as $data ) : ?>
      <div class="bdp-stat-box">
        <img src="<?php echo esc_url( "{$icons_url}{$data['icon']}" ); ?>"
             width="40" height="40"
             alt="<?php echo esc_attr( $data['label'] ); ?>">
        <div class="bdp-stat-label"><?php echo esc_html( $data['label'] ); ?></div>
        <div class="bdp-stat-value"><?php echo esc_html( number_format_i18n( $data['count'] ) ); ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- 4) Filtreleme Alanı (Değişmedi) -->
  <div class="bdp-analytics-filters">
    <input type="date" id="bdp-start-date" placeholder="Start date">
    <input type="date" id="bdp-end-date"   placeholder="End date">
    <select id="bdp-topic-filter">
      <option value="all"><?php esc_html_e( 'All Topics', 'blitz-dock' ); ?></option>
      <?php foreach ( $topics as $t ) : ?>
        <option value="<?php echo esc_attr( $t ); ?>">
          <?php echo esc_html( $t ); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select id="bdp-subtype-filter">
      <option value=""><?php esc_html_e( 'All Subtypes', 'blitz-dock' ); ?></option>
    </select>
  </div>

  <!-- 5) Alt Kartlar (Total Events / Clicks / Views / Submissions) KALDIRILDI -->

  <!-- 6) Grafik Alanları -->
  <div class="bdp-analytics-cards">
    <div class="bdp-analytics-card"><canvas id="bdp-topic-chart"   height="180"></canvas></div>
    <div class="bdp-analytics-card"><canvas id="bdp-subtype-chart" height="180"></canvas></div>
  </div>

  <!-- 7) Recent Events Tablosu -->
  <h2><?php esc_html_e( 'Recent Events', 'blitz-dock' ); ?></h2>
  <table class="widefat fixed" id="bdp-analytics-table">
    <thead>
      <tr>
        <th><?php esc_html_e( 'Time',    'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Type',    'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Topic',   'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Target',  'blitz-dock' ); ?></th>
        <th><?php esc_html_e( 'Subtype', 'blitz-dock' ); ?></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div id="bdp-analytics-pagination"></div>
</div>