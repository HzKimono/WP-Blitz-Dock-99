<?php
namespace BlitzDock\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    public static function init() {
        $instance = new self();
        $instance->load_dependencies();
        $instance->initialize_modules();
    }

    private function load_dependencies() {
      // Catalog & reports
        require_once BLITZ_DOCK_PATH . 'includes/Core/class-catalog.php';
        require_once BLITZ_DOCK_PATH . 'includes/Reports/class-dashboard-metrics.php';

        // Admin
        require_once BLITZ_DOCK_PATH . 'includes/AdminUI/class-admin.php';

        // Frontend UI
        require_once BLITZ_DOCK_PATH . 'includes/Frontend/class-frontend.php';
        
       // Messages AJAX handler
        require_once BLITZ_DOCK_PATH . 'includes/Frontend/class-messages.php';

        require_once BLITZ_DOCK_PATH . 'includes/Frontend/class-analytics.php';
        // Script & Style loaders
        require_once BLITZ_DOCK_PATH . 'includes/Scripts/class-scripts.php';

      // Form Handler
        require_once BLITZ_DOCK_PATH . 'includes/AdminUI/class-settings-handler.php';
    }

    private function initialize_modules() {
        // 1) AJAX handler her zaman kayıtlı olsun
         \BlitzDock\Frontend\Messages::init();
        \BlitzDock\Frontend\Analytics::init();

        // 2) Admin panel modülleri
        if ( is_admin() ) {
          \BlitzDock\Admin\Admin::init();
            \BlitzDock\AdminUI\Settings_Handler::init();
        }
        // 3) Script & Style yükleyici
        new \BlitzDock\Scripts\Scripts();

        // 4) Sadece front-end için UI render modülü
        if ( ! is_admin() ) {
            \BlitzDock\Frontend\Frontend::init();
        }
    }
}