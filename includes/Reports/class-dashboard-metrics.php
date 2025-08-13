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
}