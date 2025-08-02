<?php
namespace BlitzDock\AdminUI;

if ( ! defined( 'ABSPATH' ) ) exit;

class Live_Chat_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'blitz-dock',
            __( 'Live Chat', 'blitz-dock' ),
            __( 'Live Chat', 'blitz-dock' ),
            'manage_options',
            'blitz-dock-live-chat',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        $page_file = BLITZ_DOCK_PATH . 'includes/AdminUI/live-chat-page.php';
        if ( file_exists( $page_file ) ) {
            include $page_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Page not found.', 'blitz-dock' ) . '</p></div>';
        }
    }
}