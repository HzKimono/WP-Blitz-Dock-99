<?php
namespace BlitzDock\AdminUI;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings_Handler {

    /**
     * Admin‐post hook’a kaydol
     */
    public static function init() {
        add_action( 'admin_post_blitz_dock_save_settings', [ __CLASS__, 'save_settings' ] );
    }

    /**
     * Ayarları kaydeder.
     */
    public static function save_settings() {

        // Yetki ve Nonce kontrolü
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'blitz-dock' ) );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'blitz_dock_settings_verify' ) ) {
            wp_die( esc_html__( 'Güvenlik kontrolü başarısız.', 'blitz-dock' ) );
        }

        // Dock & Görünürlük Ayarları
        update_option( 'bdp_dock_position', sanitize_text_field( $_POST['bdp_dock_position'] ?? 'bottom_right' ) );
        update_option( 'bdp_theme_color', sanitize_hex_color( $_POST['bdp_theme_color'] ?? '#0073aa' ) );
        update_option( 'bdp_header_title', sanitize_text_field( $_POST['bdp_header_title'] ?? '' ) );
        update_option( 'bdp_cta_message', sanitize_textarea_field( $_POST['bdp_cta_message'] ?? '' ) );

        // Avatar Seçimi ve Yükleme
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        if ( ! empty( $_FILES['bdp_custom_avatar']['name'] ) ) {
            $attach_id = media_handle_upload( 'bdp_custom_avatar', 0 );
            if ( ! is_wp_error( $attach_id ) ) {
                $url = wp_get_attachment_url( $attach_id );
                update_option( 'bdp_custom_avatar_url', esc_url_raw( $url ) );
                update_option( 'bdp_selected_avatar', '' ); // Özel avatar seçilmişse varsayılanı temizle
            }
        } elseif ( ! empty( $_POST['bdp_selected_avatar'] ) ) {
            update_option( 'bdp_selected_avatar', sanitize_file_name( $_POST['bdp_selected_avatar'] ) );
            update_option( 'bdp_custom_avatar_url', '' ); // Galeriden seçilmişse özel avatarı temizle
        }

       // Sayfa Bazlı Görünürlük Ayarları
        $pages = [ 'front_page', 'blog', 'pages', 'products' ];
        $vis   = [];
        foreach ( $pages as $key ) {
            if ( ! empty( $_POST['bdp_visibility_matrix'][ $key ] ) ) {
                $vis[ $key ] = true;
            }
        }
        update_option( 'bdp_visibility_matrix', $vis );

     // Sosyal medya bağlantıları
        if ( ! empty( $_POST['bdp_social_links'] ) ) {
            $whitelist = array_keys( \BlitzDock\get_social_platforms() );
            $sanitized_social = [];
            foreach ( (array) $_POST['bdp_social_links'] as $item ) {
                $platform = sanitize_key( $item['platform'] ?? '' );
                $url      = esc_url_raw( $item['url'] ?? '' );
                if ( $platform && $url && in_array( $platform, $whitelist, true ) ) {
                    $sanitized_social[] = [
                        'platform' => $platform,
                        'url'      => $url,
                    ];
                }
            }
            if ( $sanitized_social ) {
                update_option( 'bdp_social_links', $sanitized_social );
            } else {
                delete_option( 'bdp_social_links' );
            }
        } else {
            delete_option( 'bdp_social_links' );
        }

        // FAQ öğeleri
        if ( ! empty( $_POST['bdp_faq_items'] ) ) {
            $sanitized_faq = array_map( function( $item ) {
                return [
                    'question' => sanitize_text_field( $item['question'] ?? '' ),
                    'answer'   => wp_kses_post( $item['answer'] ?? '' ),
                ];
            }, $_POST['bdp_faq_items'] );
            update_option( 'bdp_faq_items', $sanitized_faq );
        } else {
            delete_option( 'bdp_faq_items' );
        }

        // Online mağazalar bağlantıları
      if ( ! empty( $_POST['bdp_ecomm_links'] ) ) {
            $sanitized_ecomm = array_map( function( $item ) {
                return [
                    'name' => sanitize_text_field( $item['name'] ?? '' ),
                    'url'  => esc_url_raw( $item['url'] ?? '' ),
                ];
            }, $_POST['bdp_ecomm_links'] );
            update_option( 'bdp_ecomm_links', $sanitized_ecomm );
        } else {
            delete_option( 'bdp_ecomm_links' );
        }

       // Location map (API key or iframe)
        if ( isset( $_POST['bdp_location_embed'] ) ) {
            $raw = trim( wp_unslash( $_POST['bdp_location_embed'] ) );

            if ( 0 === strpos( $raw, '<iframe' ) ) {
                $allowed = wp_kses_allowed_html( 'post' );
                $allowed['iframe'] = [
                    'src'            => true,
                    'width'          => true,
                    'height'         => true,
                    'style'          => true,
                    'loading'        => true,
                    'referrerpolicy' => true,
                    'frameborder'    => true,
                    'allow'          => true,
                    'allowfullscreen'=> true,
                ];
                $embed = wp_kses( $raw, $allowed );
                if ( $embed ) {
                    update_option( 'bdp_location_embed', $embed );
                } else {
                    delete_option( 'bdp_location_embed' );
                }
            } else {
                $key = sanitize_text_field( $raw );
                if ( $key ) {
                    update_option( 'bdp_location_embed', $key );
                } else {
                    delete_option( 'bdp_location_embed' );
                }
            }
        }

        // Yönlendirme
        wp_safe_redirect( add_query_arg( 'saved', 'true', admin_url( 'admin.php?page=blitz-dock' ) ) );
        exit;
    }
}
