<?php
// Înarcă stilurile temei părinte
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('martfury-parent', get_template_directory_uri() . '/style.css');
});

// PRIORITATE: Încarcă header-account-menu.php ÎNAINTE de tema părinte
require_once get_stylesheet_directory() . '/includes/header-account-menu.php';

// Remove eleganticons preload - loaded via CSS instead
add_action('wp_head', function() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var preloadLinks = document.querySelectorAll("link[rel=preload][href*=eleganticons]");
            preloadLinks.forEach(function(link) {
                link.remove();
            });
        });
    </script>';
}, 1);

// Ascunde butonul mare "Vezi cos" din popup "Adăugat în coș"
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Funcție pentru a elimina butoanele "View Cart" DOAR din popup-ul "Adăugat în coș"
        function hideViewCartButton() {
            // Țintire PRECISĂ: DOAR butoane din .message-box (popup-ul "Produs adăugat")
            $('.message-box .btn-button, .message-box .button.wc-forward, .message-box a.button[href*="cart"]').hide();
            
            // Backup: verifică doar în .message-box
            $('.message-box a, .message-box button').each(function() {
                var $el = $(this);
                var text = $el.text().toLowerCase().trim();
                var href = $el.attr('href') || '';
                
                // Dacă conține "vezi", "view", "cart", "coș" SAU link-ul duce la cart
                if (text.includes('vezi') || text.includes('view') || 
                    text.includes('cart') || text.includes('coș') || 
                    text.includes('cos') || href.includes('cart')) {
                    $el.hide();
                }
            });
            
            // NU ascunde din mini-cart (.woocommerce-mini-cart__buttons)
        }
        
        // Rulează la pornire
        hideViewCartButton();
        
        // Rulează când se adaugă produs în coș
        $(document.body).on('added_to_cart', function() {
            setTimeout(hideViewCartButton, 50);
            setTimeout(hideViewCartButton, 200);
        });
        
        // Observer pentru popup-uri noi
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    hideViewCartButton();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    </script>
    <?php
}, 999);

// Încarcă modulele
require_once get_stylesheet_directory() . '/includes/retururi.php';
require_once get_stylesheet_directory() . '/includes/garantie.php';
require_once get_stylesheet_directory() . '/includes/awb-tracking.php';
require_once get_stylesheet_directory() . '/includes/facturi.php';
require_once get_stylesheet_directory() . '/includes/notificari.php';
require_once get_stylesheet_directory() . '/includes/n8n-webhooks.php';
require_once get_stylesheet_directory() . '/includes/facturare-pj.php';
require_once get_stylesheet_directory() . '/includes/my-account-styling.php';
require_once get_stylesheet_directory() . '/includes/webgsm-myaccount-headers.php'; 
require_once get_stylesheet_directory() . '/includes/admin-tools.php';
require_once get_stylesheet_directory() . '/includes/registration-enhanced.php';
require_once get_stylesheet_directory() . '/includes/webgsm-design-system.php';
require_once get_stylesheet_directory() . '/includes/webgsm-myaccount.php';
require_once get_stylesheet_directory() . '/includes/webgsm-myaccount-modals.php';

