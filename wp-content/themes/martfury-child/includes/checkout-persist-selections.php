<?php
/**
 * Checkout – păstrează selectiile la refresh (metodă livrare, punct Packeta)
 * Rulează doar când WebGSM Checkout Pro e dezactivat (checkout WooCommerce standard).
 */

if (!defined('ABSPATH')) exit;

if (class_exists('WebGSM_Checkout_Pro')) return;

add_action('wp_footer', function() {
    if (!is_checkout() || empty(WC()->cart)) return;
    ?>
    <script>
    (function() {
        var KEY = 'webgsm_checkout_selections';
        
        function save() {
            var data = {};
            var method = document.querySelector('input[name^="shipping_method"]:checked');
            if (method) data.shipping_method = method.value;
            var branchId = document.querySelector('.packeta-selector-branch-id, input[name*="packeta"], input[id*="packeta_branch"], input[id*="branch_id"]');
            if (branchId && branchId.value) data.packeta_branch_id = branchId.value;
            var branchName = document.querySelector('.packeta-selector-branch-name');
            if (branchName && branchName.textContent) data.packeta_branch_name = branchName.textContent.trim();
            try { sessionStorage.setItem(KEY, JSON.stringify(data)); } catch (e) {}
        }
        
        function restore() {
            try {
                var raw = sessionStorage.getItem(KEY);
                if (!raw) return;
                var data = JSON.parse(raw);
                if (data.shipping_method) {
                    var radios = document.querySelectorAll('input[name^="shipping_method"]');
                    for (var i = 0; i < radios.length; i++) {
                        if (radios[i].value === data.shipping_method && !radios[i].checked) {
                            radios[i].checked = true;
                            radios[i].dispatchEvent(new Event('change', { bubbles: true }));
                            if (typeof jQuery !== 'undefined') jQuery(document.body).trigger('update_checkout');
                            break;
                        }
                    }
                }
            } catch (e) {}
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(restore, 100);
                setTimeout(restore, 500);
            });
        } else {
            setTimeout(restore, 100);
        }
        
        document.addEventListener('change', function(e) {
            if (e.target.matches('input[name^="shipping_method"]')) save();
            if (e.target.matches('.packeta-selector-branch-id, input[name*="packeta"], input[id*="packeta"]')) save();
        });
        
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('updated_checkout', function() {
                save();
                setTimeout(restore, 200);
            });
        }
        
        window.addEventListener('beforeunload', save);
    })();
    </script>
    <?php
}, 50);
