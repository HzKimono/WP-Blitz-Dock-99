<?php
namespace BlitzDock\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {

    public static function init() {
        add_action( 'wp_footer', [ __CLASS__, 'render_chat_panel' ] );
    }

    public static function render_chat_panel() {
        // — Ayar verilerini oku —
        $position_class   = self::get_position_class();
         $visibility_matrix = get_option( 'bdp_visibility_matrix', [] );

        $page_key = '';
        if ( is_front_page() ) {
            $page_key = 'front_page';
        } elseif ( is_home() || is_singular( 'post' ) ) {
            $page_key = 'blog';
        } elseif ( is_singular( 'product' ) || is_post_type_archive( 'product' ) ) {
            $page_key = 'products';
        } elseif ( is_page() || is_singular() ) {
            $page_key = 'pages';
        }

        if ( $visibility_matrix && empty( $visibility_matrix[ $page_key ] ) ) {
            return; // Gizli, hiçbir şey gösterme
        }
        $theme_color      = get_option( 'bdp_theme_color',   '#0073aa' );
        $panel_title      = get_option( 'bdp_header_title',  __( 'Support Center', 'blitz-dock' ) );
        $cta_message      = get_option( 'bdp_cta_message',   '' );
        $custom_avatar    = get_option( 'bdp_custom_avatar_url', '' );
        $selected_avatar  = get_option( 'bdp_selected_avatar',   'avatar1.png' );

        // — Link verilerini oku —
        $social_links     = get_option( 'bdp_social_links', [] );
        $ecomm_links      = get_option( 'bdp_ecomm_links',  [] );
        $faq_items        = get_option( 'bdp_faq_items',    [] );
        $location_embed   = get_option( 'bdp_location_embed', '' );
         $dock_items = [
            [ 'slug' => 'social',    'label' => __( 'Social Links', 'blitz-dock' ), 'icon' => 'social-media.png' ],
            [ 'slug' => 'ecomm',     'label' => __( 'Online Stores', 'blitz-dock' ), 'icon' => 'e-commerce.png' ],
            [ 'slug' => 'faq',       'label' => __( 'FAQ', 'blitz-dock' ), 'icon' => 'faq.png' ],
        ];

        if ( $location_embed ) {
            $dock_items[] = [ 'slug' => 'location', 'label' => __( 'Location', 'blitz-dock' ), 'icon' => 'world.png' ];
        }

        $dock_items[] = [ 'slug' => 'message', 'label' => __( 'Leave a Message', 'blitz-dock' ), 'icon' => 'messages.png' ];
        // — Plugin URL’si —
        $plugin_url = defined( 'BLITZ_DOCK_URL' )
            ? BLITZ_DOCK_URL
            : plugin_dir_url( dirname( __FILE__, 2 ) );

        // — Avatar kaynağı (özel yükleme öncelikli, yoksa sabit avatar klasörü) —
        $avatar_src = $custom_avatar
            ? $custom_avatar
            : $plugin_url . 'assets/avatars/' . $selected_avatar;

        // — Stil ve JS dosyalarını yükle —
        wp_enqueue_style(
            'blitz-dock-style',
            $plugin_url . 'assets/css/style.css',
            [],
            BLITZ_DOCK_VERSION
        );
        wp_enqueue_script(
            'blitz-dock-script',
            $plugin_url . 'assets/js/script.js',
            [ 'jquery' ],
            BLITZ_DOCK_VERSION,
            true
        );

        // — JS’e tüm verileri aktar —
        wp_localize_script(
            'blitz-dock-script',
            'pluginData',
            [
                'pluginUrl'         => $plugin_url,
                'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
                'messageNonce'      => wp_create_nonce( 'bdp_message_nonce' ),

                // İkon klasörleri
                'iconsBaseURL'      => $plugin_url . 'assets/icons/',
                'ecommIconsBaseURL' => $plugin_url . 'assets/ecomm-icons/',
                'avatarBaseURL'     => $plugin_url . 'assets/avatars/',

                // İçerik verileri
                'socialLinks'       => $social_links,
                'ecommLinks'        => $ecomm_links,
                'faqItems'          => $faq_items,
                 'dockItems'        => $dock_items,

                // CTA
                'ctaMessage'        => $cta_message,
                'ctaDelay'          => get_option( 'bdp_cta_delay', 5000 ),
            ]
        );
        ?>

        <!-- Avatar Dock Toggle Button -->
               <div id="bdp-toggle"
             class="bdp-bubble-frame <?php echo esc_attr( $position_class ); ?>"
             aria-expanded="false">
            <img class="bdp-bubble-avatar"
                 src="<?php echo esc_url( $avatar_src ); ?>"
                 alt="<?php esc_attr_e( 'Support Avatar', 'blitz-dock' ); ?>" />
        </div>

        <!-- CTA Message -->
        <?php if ( $cta_message ) : ?>
        <div id="bdp-cta-message"
             style="background-color:<?php echo esc_attr( $theme_color ); ?>; color:#fff;
                    padding:8px 12px; border-radius:6px;
                    margin-top:8px; display:none;
                    position:fixed; bottom:80px; right:32px; z-index:99999;">
            <?php echo esc_html( $cta_message ); ?>
        </div>
        <?php endif; ?>

        <!-- Chat Panel -->
       <div id="bdp-chat-panel"
             class="bdp-chat-panel <?php echo esc_attr( $position_class ); ?>"
             aria-hidden="true"
                       style="display:none;">

            <!-- 1) Header -->
            <div class="bdp-panel-header"
                 style="background-color:<?php echo esc_attr( $theme_color ); ?>;
                        padding:10px; display:flex;
                        align-items:center; justify-content:space-between;">
                <div class="bdp-panel-title"
                     style="font-size:16px; font-weight:600;
                            color:#fff; flex:1; white-space:nowrap;
                            overflow:hidden; text-overflow:ellipsis;">
                    <?php echo esc_html( $panel_title ); ?>
                </div>
                <div class="bdp-panel-controls" style="display:flex; align-items:center;">
                    <button type="button" class="bdp-back-btn" aria-label="<?php esc_attr_e( 'Back', 'blitz-dock' ); ?>" style="background:transparent;border:none;color:#fff;font-size:16px;cursor:pointer;margin-right:8px;padding:4px 6px;">&larr;</button>
                    <button type="button" id="bdp-close-chat" class="bdp-panel-close" aria-label="<?php esc_attr_e( 'Close', 'blitz-dock' ); ?>" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;padding:4px;">&times;</button>
                </div>
            </div>
            <div class="bdp-panel-body">

            <!-- 2) Home Screen -->
         <div id="bdp-home-screen" class="bdp-home-screen">
                         <?php foreach ( $dock_items as $item ) : ?>
                    <div class="bdp-dock-item" role="button" tabindex="0" data-target="bdp-topic-<?php echo esc_attr( $item['slug'] ); ?>" aria-label="<?php echo esc_attr( $item['label'] ); ?>">
                        <img
                            src="<?php echo esc_url( $plugin_url . 'assets/icons/' . $item['icon'] ); ?>"
                            alt="<?php echo esc_attr( $item['label'] ); ?>"
                            class="bdp-dock-icon"
                        />
                        <span class="bdp-dock-label"><?php echo esc_html( $item['label'] ); ?></span>
                    </div>
                  <?php endforeach; ?>
            </div>

            <!-- 3) Social Links Topic -->
            <div id="bdp-topic-social" class="bdp-topic" style="display:none;flex:1;flex-direction:column;padding:22px;">
                <div id="bdp-social-links" class="bdp-links">
                    <?php foreach ( $social_links as $link ) :
                        if ( empty( $link['platform'] ) || empty( $link['url'] ) ) {
                            continue;
                        }
                        printf(
                            '<a href="%1$s" class="bdp-%2$s"><img src="%3$s/assets/icons/%2$s.png" alt="%4$s"></a>',
                            esc_url( $link['url'] ),
                            esc_attr( $link['platform'] ),
                            untrailingslashit( BLITZ_DOCK_URL ),
                            esc_attr( ucfirst( $link['platform'] ) )
                        );
                    endforeach; ?>
                </div>
            </div>

            <!-- 4) Ecomm Links Topic -->
           <div id="bdp-topic-ecomm" class="bdp-topic" style="display:none;flex:1;flex-direction:column;padding:22px;">
                <div id="bdp-ecomm-links" class="bdp-links"></div>
            </div>

            <!-- 5) FAQ Topic -->
            
<div id="bdp-topic-faq" class="bdp-topic" style="display:none; flex:1; flex-direction:column; padding:22px;">
  <?php if ( ! empty( $faq_items ) ) : ?>
    <div class="bdp-faq-list">
      <?php foreach ( $faq_items as $item ) :
        $q = esc_html( $item['question'] ?? '' );
        $a = esc_html( $item['answer'] ?? '' );
        if ( ! $q || ! $a ) continue;
      ?>
        <div class="bdp-faq-item" style="border-bottom:1px solid #eee; padding:10px 0;">
          <div class="bdp-faq-q" style="font-weight:600; color:#222;">
            <?php echo $q; ?>
          </div>
          <div class="bdp-faq-a" style="color:#444; font-size:14px; margin-top:2px;">
            <?php echo $a; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <div style="color:#888; text-align:center; margin-top:16px;">
      <?php esc_html_e( 'No FAQs added yet.', 'blitz-dock' ); ?>
    </div>
 <?php endif; ?>
</div>

            <?php if ( $location_embed ) : ?>
            <div id="bdp-topic-location" class="bdp-topic" style="display:none; flex:1; flex-direction:column; padding:0;">
                <?php if ( 0 === strpos( trim( $location_embed ), '<iframe' ) ) : ?>
                    <?php echo $location_embed; ?>
                <?php else : ?>
                    <div id="bdp-front-map" style="width:100%;height:300px;"></div>
                    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr( $location_embed ); ?>&callback=bdpFrontInitMap"></script>
                    <script>
                      function bdpFrontInitMap() {
                        try {
                          new google.maps.Map(document.getElementById('bdp-front-map'), {center: {lat: 0, lng: 0}, zoom: 2});
                        } catch (e) {}
                      }
                    </script>
                <?php endif; ?>
            </div>
            <?php endif; ?>

                 <div id="bdp-topic-message"
                 class="bdp-topic"
                 style="display:none; flex:1; flex-direction:column; padding:22px;">
                 
                <form id="bdp-message-form">
                    <input type="text"    name="name"    placeholder="<?php esc_attr_e( 'Adınız',       'blitz-dock' ); ?>" required style="width:100%; margin-bottom:8px;" />
                    <input type="email"   name="email"   placeholder="<?php esc_attr_e( 'E-posta',      'blitz-dock' ); ?>" required style="width:100%; margin-bottom:8px;" />
                    <textarea name="message" rows="4" placeholder="<?php esc_attr_e( 'Mesajınız', 'blitz-dock' ); ?>" required style="width:100%; margin-bottom:8px;"></textarea>
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'bdp_message_nonce' ); ?>" />
                    <button type="submit" class="button" style="width:100%;">
                        <?php esc_html_e( 'Gönder', 'blitz-dock' ); ?>
                    </button>
                </form>
                <div id="bdp-message-success" style="display:none; margin-top:10px; color:green;"></div>
            </div>

   </div><!-- .bdp-panel-body -->

        </div><!-- #bdp-chat-panel -->


        <?php
    }

    public static function get_position_class() {
         $pos = get_option( 'bdp_dock_position', 'bottom_right' );
        return $pos === 'bottom_left' ? 'dock-bottom-left' : 'dock-bottom-right';
    }
}


add_action('wp_footer', function () {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const links = document.querySelectorAll('.bdp-ecomm-link');

        links.forEach(link => {
            const icon = link.querySelector('img');
            const id = link.getAttribute('data-id');

            if (id && icon) {
                const basePath = "https://printsblitz.com/wp-content/plugins/blitz-dock/assets/icons/";
                const iconPath = `${basePath}${id}.png`;

                fetch(iconPath, { method: 'HEAD' })
                    .then(res => {
                        icon.src = res.ok ? iconPath : `${basePath}default.png`;
                    })
                    .catch(() => {
                        icon.src = `${basePath}default.png`;
                    });
            }
        });
    });
    </script>
    <?php
});