<?php
use BlitzDock\Reports\Dashboard_Metrics;

defined('ABSPATH') || exit;

$card = Dashboard_Metrics::social_links_overview(7);
?>
<div class="bdp-cards" role="region" aria-label="<?php esc_attr_e('Dashboard KPIs','blitz-dock'); ?>">
  <article class="bdp-card" aria-label="<?php esc_attr_e('Social Links Overview','blitz-dock'); ?>">
    <header class="bdp-card__head">
      <span class="bdp-card__icon">
        <img src="<?php echo esc_url( BLITZ_DOCK_URL . 'assets/icons/menu/menu-social-media.png' ); ?>"
             alt="" aria-hidden="true" />
      </span>
      <div class="bdp-card__title">
        <div class="bdp-card__value"><?php echo esc_html( $card['active'] . '/' . $card['total'] ); ?></div>
        <div class="bdp-card__subtitle"><?php esc_html_e('Social Links','blitz-dock'); ?></div>
      </div>
      <button type="button" class="bdp-card__more" aria-label="<?php esc_attr_e('More options','blitz-dock'); ?>">⋮</button>
    </header>

    <footer class="bdp-card__foot">
      <span class="bdp-card__label"><?php esc_html_e('Clicks (last 7 days):','blitz-dock'); ?></span>
      <span class="bdp-card__stat"><?php echo number_format_i18n( (int) $card['clicks'] ); ?></span>
      <div class="bdp-card__bar"><span class="bdp-card__bar__fill" style="width: <?php echo (int) $card['coverage']; ?>%"></span></div>
    </footer>
  </article>

  <!-- Future cards (2, 3, 4) will be added here using the same structure -->
</div>

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