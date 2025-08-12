<?php
namespace BlitzDock\Reports;

use BlitzDock\Core\Catalog;

defined('ABSPATH') || exit;

class Dashboard_Metrics {
    /** Social Links: {active}/{total_supported} and 7-day clicks */
    public static function social_links_overview(int $days = 7): array {
        $catalog = Catalog::get_social_platforms();
        $total_supported = is_array($catalog) ? count($catalog) : 0;

        // Active = configured/enabled links in options (adjust key/shape to your plugin)
        $links  = get_option('bdp_social_links', []); // replace with your real option key
        $active = 0;
        if (is_array($links)) {
            foreach ($links as $link) {
                $url = isset($link['url']) ? trim((string)$link['url']) : '';
                $en  = !empty($link['enabled']);
                if ($url !== '' || $en) {
                    $active++;
                }
            }
        }

        // Clicks in last N days — prefer your Analytics class/table if available
        $clicks = 0;
        $since  = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        global $wpdb;
        $candidates = [
            $wpdb->prefix.'blitzdock_events'  => ['type','created_at'],
            $wpdb->prefix.'blitz_dock_events' => ['type','created_at'],
            $wpdb->prefix.'bdp_analytics'     => ['event_type','created_at'],
        ];
        foreach ($candidates as $table => $cols) {
            $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $table) );
            if ($exists === $table) {
                [$colType,$colDate] = $cols;
                $clicks = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE {$colDate} >= %s AND {$colType} IN ('social_click','social')",
                    $since
                ));
                break;
            }
        }

        $coverage = $total_supported ? round($active * 100 / $total_supported) : 0;

        return [
            'active'   => $active,
            'total'    => $total_supported,
            'clicks'   => $clicks,
            'coverage' => $coverage,
        ];
    }
}