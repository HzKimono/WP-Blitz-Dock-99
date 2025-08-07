<?php
namespace BlitzDock\Scripts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Scripts {

    public function __construct() {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Frontend: CSS + JS + Data
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'blitz-dock-style',
            BLITZ_DOCK_URL . 'assets/css/style.css',
            [],
            BLITZ_DOCK_VERSION
        );

        wp_enqueue_script(
            'blitz-dock-script',
            BLITZ_DOCK_URL . 'assets/js/script.js',
            [ 'jquery' ],
            BLITZ_DOCK_VERSION,
            true
        );

        wp_localize_script(
            'blitz-dock-script',
            'pluginData',
            [
                'pluginUrl'          => BLITZ_DOCK_URL,
                'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
                'messageNonce'       => wp_create_nonce( 'bdp_message_nonce' ),

                // Admin panelde kaydedilen seçenekler
                'dockPosition'       => get_option( 'bdp_dock_position', 'bottom_right' ),
                'visibilityMatrix'   => get_option( 'bdp_visibility_matrix', [] ),

                // İçerik
                'socialLinks'        => get_option( 'bdp_social_links', [] ),
                'ecommLinks'         => get_option( 'bdp_ecomm_links', [] ),
                'faqItems'           => get_option( 'bdp_faq_items', [] ),
                'ctaMessage'         => get_option( 'bdp_cta_message', '' ),
                'ctaDelay'           => get_option( 'bdp_cta_delay', 5000 ),
                'ctaDelay'           => get_option( 'bdp_cta_delay', 5000 ),

                // İkon yolları (Kullanım: pluginData.iconsBaseURL + 'facebook.png')
                'iconsBaseURL'       => BLITZ_DOCK_URL . 'assets/icons/',
                'ecommIconsBaseURL'  => BLITZ_DOCK_URL . 'assets/ecomm-icons/',
              'avatarBaseURL'      => BLITZ_DOCK_URL . 'assets/avatars/',
            ]
        );

        wp_localize_script(
            'blitz-dock-script',
            'bdp_ajax',
             [
                'url'       => admin_url( 'admin-ajax.php' ),
                'log_nonce' => wp_create_nonce( 'blitz_dock_log_event' ),
            ]
        );
    }

    /**
     * Admin Panel: CSS + JS + Data
     */
    public function enqueue_admin_assets() {
        wp_enqueue_style(
            'blitz-dock-admin-style',
            BLITZ_DOCK_URL . 'assets/css/admin.css',
            [],
            BLITZ_DOCK_VERSION
        );

        wp_enqueue_script(
            'blitz-dock-admin-script',
            BLITZ_DOCK_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            BLITZ_DOCK_VERSION,
            true
        );

   // Chart.js for analytics graphs
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.4.0',
            true
        );

        wp_enqueue_script(
            'blitz-dock-dashboard',
            BLITZ_DOCK_URL . 'assets/js/dashboard.js',
            [ 'chart-js' ],
            BLITZ_DOCK_VERSION,
            true
        );

        wp_enqueue_script(
            'blitz-dock-analytics',
            BLITZ_DOCK_URL . 'assets/js/analytics.js',
            [ 'chart-js', 'jquery' ],
            BLITZ_DOCK_VERSION,
            true
        );

        wp_localize_script(
            'blitz-dock-admin-script',
            'pluginData',
            [
                'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
                'messageNonce'       => wp_create_nonce( 'bdp_message_nonce' ),
                'iconsBaseURL'       => BLITZ_DOCK_URL . 'assets/icons/',
                'ecommIconsBaseURL'  => BLITZ_DOCK_URL . 'assets/ecomm-icons/',
                'avatarBaseURL'      => BLITZ_DOCK_URL . 'assets/avatars/',
                'socialPlatforms'    => \BlitzDock\get_social_platforms(),
            ]
        );

       wp_localize_script(
            'blitz-dock-admin-script',
            'bdp_ajax',
            [
                'url'          => admin_url( 'admin-ajax.php' ),
                'filter_nonce' => wp_create_nonce( 'bdp_filter_messages' ),
                'update_nonce' => wp_create_nonce( 'bdp_update_status' ),
                'log_nonce'       => wp_create_nonce( 'blitz_dock_log_event' ),
                'analytics_nonce' => wp_create_nonce( 'bdp_filter_analytics' ),
            ]
        );
    }
}