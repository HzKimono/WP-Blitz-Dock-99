<?php
use BlitzDock\Reports\Dashboard_Metrics;

defined('ABSPATH') || exit;

$kpi        = Dashboard_Metrics::social_links_overview(7);
$milestones = (array) apply_filters( 'blitz_dock_click_milestones', [100, 200, 300] );
$bar_class  = '';
$clicks     = (int) $kpi['clicks'];
if ( $clicks >= (int) ( $milestones[2] ?? 300 ) ) {
    $bar_class = 'is-tier-3';
} elseif ( $clicks >= (int) ( $milestones[1] ?? 200 ) ) {
    $bar_class = 'is-tier-2';
} elseif ( $clicks >= (int) ( $milestones[0] ?? 100 ) ) {
    $bar_class = 'is-tier-1';
}

$kpi_dock   = Dashboard_Metrics::dock_opens_overview( 7 );
$milestones = (array) apply_filters( 'blitz_dock_click_milestones', [100, 200, 300] );
$open_class = '';
if ( (int) $kpi_dock['opens'] >= ( $milestones[2] ?? 300 ) ) {
    $open_class = 'is-tier-3';
} elseif ( (int) $kpi_dock['opens'] >= ( $milestones[1] ?? 200 ) ) {
    $open_class = 'is-tier-2';
} elseif ( (int) $kpi_dock['opens'] >= ( $milestones[0] ?? 100 ) ) {
    $open_class = 'is-tier-1';
}
$kpi_msg = Dashboard_Metrics::messages_overview( 7 );
?>
<div class="bdp-cards" role="region" aria-label="<?php esc_attr_e('Dashboard KPIs','blitz-dock'); ?>">
  <article class="bdp-card" aria-label="<?php esc_attr_e('Social Links Overview','blitz-dock'); ?>">
    <header class="bdp-card__head">
      <span class="bdp-card__icon">
        <img src="<?php echo esc_url( BLITZ_DOCK_URL . 'assets/icons/menu/menu-social-media.png' ); ?>"
             alt="" aria-hidden="true" />
      </span>
      <div class="bdp-card__title">
        <div class="bdp-card__value"><?php echo esc_html( $kpi['active'] . '/' . $kpi['total'] ); ?></div>
        <div class="bdp-card__subtitle"><?php esc_html_e('Social Links','blitz-dock'); ?></div>
      </div>
      <button type="button" class="bdp-card__more" aria-label="<?php esc_attr_e('More options','blitz-dock'); ?>">⋮</button>
    </header>

    <footer class="bdp-card__foot">
      <span class="bdp-card__label"><?php esc_html_e('Clicks (last 7 days):','blitz-dock'); ?></span>
      <span class="bdp-card__stat"><?php echo number_format_i18n( (int) $kpi['clicks'] ); ?></span>
      <div class="bdp-card__bar <?php echo esc_attr( $bar_class ); ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $kpi['progress']; ?>" aria-label="<?php esc_attr_e('Clicks progress','blitz-dock'); ?>">
        <span class="bdp-card__bar__fill" style="width:<?php echo (int) $kpi['progress']; ?>%"></span>
      </div>
   </footer>
  </article>

    <article class="bdp-card" role="region" aria-label="<?php esc_attr_e('Dock Opens Overview','blitz-dock'); ?>">
    <header class="bdp-card__head">
      <span class="bdp-card__icon">
        <img src="<?php echo esc_url( BLITZ_DOCK_URL . 'assets/icons/menu/menu-analytics.png' ); ?>" alt="" aria-hidden="true" />
      </span>
      <div class="bdp-card__title">
        <div class="bdp-card__value"><?php echo number_format_i18n( (int) $kpi_dock['opens'] ); ?></div>
        <div class="bdp-card__subtitle"><?php esc_html_e('Dock Opens','blitz-dock'); ?></div>
      </div>
      <button type="button" class="bdp-card__more" aria-label="<?php esc_attr_e('More options','blitz-dock'); ?>">⋮</button>
    </header>
    <div class="bdp-card__foot">
      <span class="bdp-card__label"><?php esc_html_e('Opens (last 7 days):','blitz-dock'); ?></span>
      <span class="bdp-card__stat"><?php echo number_format_i18n( (int) $kpi_dock['opens'] ); ?></span>
      <div class="bdp-card__bar <?php echo esc_attr( $open_class ); ?>"
           role="progressbar"
           aria-valuemin="0" aria-valuemax="100"
           aria-valuenow="<?php echo (int) $kpi_dock['progress']; ?>"
           aria-label="<?php esc_attr_e('Dock opens progress','blitz-dock'); ?>">
        <span class="bdp-card__bar__fill" style="width:<?php echo (int) $kpi_dock['progress']; ?>%"></span>
      </div>
    </div>
 
 </article>
  <article class="bdp-card bdp-card--messages"
           role="region"
           aria-label="<?php esc_attr_e('Messages Overview','blitz-dock'); ?>"
           data-total="<?php echo (int) $kpi_msg['total']; ?>"
           data-completed="<?php echo (int) $kpi_msg['completed']; ?>"
           data-pending="<?php echo (int) $kpi_msg['pending']; ?>"
           data-canceled="<?php echo (int) $kpi_msg['canceled']; ?>">
    <header class="bdp-card__head">
      <span class="bdp-card__icon">
        <img src="<?php echo esc_url( BLITZ_DOCK_URL . 'assets/icons/menu/menu-messages.png' ); ?>" alt="" aria-hidden="true" />
      </span>
      <div class="bdp-card__title">
        <div class="bdp-card__value">
          <?php echo number_format_i18n( (int) $kpi_msg['total'] ); ?>
        </div>
        <div class="bdp-card__subtitle"><?php esc_html_e('Messages','blitz-dock'); ?></div>
      </div>
      <button type="button" class="bdp-card__more" aria-label="<?php esc_attr_e('More options','blitz-dock'); ?>">⋮</button>
    </header>

    <footer class="bdp-card__foot">
      <span class="bdp-card__label js-bdp-msg-label"><?php esc_html_e('Total (last 7 days):','blitz-dock'); ?></span>
      <span class="bdp-card__stat js-bdp-msg-stat"><?php echo number_format_i18n( (int) $kpi_msg['total'] ); ?></span>
      <div class="bdp-card__bar is-neutral"
           role="progressbar"
           aria-valuemin="0" aria-valuemax="100"
           aria-valuenow="100"
           aria-label="<?php esc_attr_e('Total messages','blitz-dock'); ?>">
        <span class="bdp-card__bar__fill js-bdp-msg-fill" style="width:100%"></span>
      </div>
    </footer>

    <div class="bdp-status-breakdown" role="list">
      <span class="bdp-status-chip is-completed" role="listitem" tabindex="0" data-status="completed">
        <?php printf( esc_html__('Completed %d (%s%%)','blitz-dock'), (int) $kpi_msg['completed'], (int) round( ( $kpi_msg['total'] ? ( $kpi_msg['completed'] / $kpi_msg['total'] ) : 0 ) * 100 ) ); ?>
      </span>
      <span class="bdp-status-chip is-pending" role="listitem" tabindex="0" data-status="pending">
        <?php printf( esc_html__('Pending %d (%s%%)','blitz-dock'), (int) $kpi_msg['pending'], (int) round( ( $kpi_msg['total'] ? ( $kpi_msg['pending'] / $kpi_msg['total'] ) : 0 ) * 100 ) ); ?>
      </span>
      <span class="bdp-status-chip is-canceled" role="listitem" tabindex="0" data-status="canceled">
        <?php printf( esc_html__('Canceled %d (%s%%)','blitz-dock'), (int) $kpi_msg['canceled'], (int) round( ( $kpi_msg['total'] ? ( $kpi_msg['canceled'] / $kpi_msg['total'] ) : 0 ) * 100 ) ); ?>
      </span>
    </div>
  </article>
</div>