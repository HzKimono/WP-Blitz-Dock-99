<?php
namespace BlitzDock\Reports;

use BlitzDock\Core\Catalog;

defined('ABSPATH') || exit;

class Dashboard_Metrics {
    /**
     * Returns Social Links metrics for the dashboard card.
     *
     * @param int $days Number of days to look back for clicks.
     * @return array{active:int,total:int,clicks:int,progress:int}
     */
    public static function social_links_overview( $days = 7 ) {
        $catalog = Catalog::get_social_platforms();
        $total   = is_array( $catalog ) ? count( $catalog ) : 0;

        // Count unique active social links (non-empty URL).
        $links  = get_option( 'bdp_social_links', [] );
        $uniq   = [];
        if ( is_array( $links ) ) {
            foreach ( $links as $item ) {
                $slug = sanitize_key( $item['platform'] ?? '' );
                $url  = trim( $item['url'] ?? '' );
                if ( $slug && '' !== $url ) {
                    $uniq[ $slug ] = true;
                }
            }
        }
        $active = count( $uniq );

        // Clicks in the last N days using the analytics table.
        global $wpdb;
        $table = $wpdb->prefix . 'bdp_analytics';

        $event_types = apply_filters( 'blitz_dock_social_event_types', [ 'click' ] );
        $event_types = array_values( array_unique( (array) $event_types ) );
        $from        = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - absint( $days ) * DAY_IN_SECONDS );

        $placeholders = implode( ',', array_fill( 0, count( $event_types ), '%s' ) );
        $sql          = "SELECT COUNT(*) FROM {$table} WHERE event_type IN ($placeholders) AND event_topic = %s AND created_at >= %s";
        $params       = array_merge( $event_types, [ 'social_links', $from ] );
        $clicks       = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

        // Progress based on target option.
        $target = (int) get_option( 'bdp_clicks_target', 100 );
        $target = (int) apply_filters( 'blitz_dock_social_clicks_target', $target );
        if ( $target <= 0 ) {
            $target = 100;
        }
        $progress = $target > 0 ? (int) round( ( $clicks / $target ) * 100 ) : 0;
        if ( $progress > 100 ) {
            $progress = 100;
        } elseif ( $progress < 0 ) {
            $progress = 0;
        }

        return [
            'active'   => $active,
            'total'    => $total,
            'clicks'   => $clicks,
            'progress' => $progress,
        ];
    }

    /**
     * Dock Opens KPI (last N days), mirrors Analytics filters exactly.
     *
     * @param int $days Number of days to look back for opens.
     * @return array{opens:int,progress:int}
     */
      public static function dock_opens_overview( $days = 7 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bdp_analytics';
        $from  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - absint( $days ) * DAY_IN_SECONDS );

        // Mirror Analytics: event_type='view' AND event_topic='panel_open'
        $sql   = "SELECT COUNT(*) FROM {$table}
                WHERE event_type = %s AND event_topic = %s AND created_at >= %s";
        $opens = (int) $wpdb->get_var( $wpdb->prepare( $sql, 'view', 'panel_open', $from ) );

        // Progress vs target (configurable; default 100)
        $target = (int) get_option( 'bdp_panel_open_target', 100 );
        if ( $target <= 0 ) {
            $target = 100;
        }
          return [
            'opens'    => $opens,
            'progress' => $progress,
        ];
    }

    /**
     * Messages KPI (last N days), mirrors the chart’s filters and timezone.
     *
     * @param int $days Number of days to look back for messages.
     * @return array{completed:int,pending:int,canceled:int,total:int,progress:int}
     */
    public static function messages_overview( $days = 7 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bdp_messages';
        $from  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - absint( $days ) * DAY_IN_SECONDS );

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE date_submitted >= %s", $from
        ) );
        $completed = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s AND date_submitted >= %s", 'completed', $from
        ) );
        $pending = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s AND date_submitted >= %s", 'pending', $from
        ) );
        $canceled = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s AND date_submitted >= %s", 'canceled', $from
        ) );

        // Default progress: resolution rate (completed / (completed + pending)).
        $den      = max( 1, $completed + $pending );
        $progress = (int) round( ( $completed / $den ) * 100 );

        return compact( 'completed', 'pending', 'canceled', 'total', 'progress' );
    }
}