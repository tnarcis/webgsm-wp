<?php
/**
 * WebGSM - Setup Attributes
 * Creează atributele globale WooCommerce la prima accesare
 * 
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit dacă accesat direct

/**
 * Verifică dacă atributele au fost deja create
 */
if (get_option('webgsm_attributes_installed')) {
    return; // Nu rulează din nou
}

/**
 * Funcție pentru crearea atributelor
 */
function webgsm_setup_product_attributes() {
    // Verifică dacă WooCommerce este activ
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    $attributes_created = 0;
    $terms_created = 0;
    $errors = array();
    
    // Structura atributelor (slug-uri fără prefixul 'pa_')
    $attributes = array(
        array(
            'name' => 'Model Compatibil',
            'slug' => 'model',
            'values' => array(
                // iPhone
                'iPhone 16 Pro Max',
                'iPhone 16 Pro',
                'iPhone 16 Plus',
                'iPhone 16',
                'iPhone 15 Pro Max',
                'iPhone 15 Pro',
                'iPhone 15 Plus',
                'iPhone 15',
                'iPhone 14 Pro Max',
                'iPhone 14 Pro',
                'iPhone 14 Plus',
                'iPhone 14',
                'iPhone 13 Pro Max',
                'iPhone 13 Pro',
                'iPhone 13 Mini',
                'iPhone 13',
                'iPhone 12 Pro Max',
                'iPhone 12 Pro',
                'iPhone 12 Mini',
                'iPhone 12',
                'iPhone 11 Pro Max',
                'iPhone 11 Pro',
                'iPhone 11',
                'iPhone XS Max',
                'iPhone XS',
                'iPhone XR',
                'iPhone X',
                'iPhone SE 2022',
                'iPhone SE 2020',
                'iPhone 8 Plus',
                'iPhone 8',
                'iPhone 7 Plus',
                'iPhone 7',
                // Samsung
                'Galaxy S24 Ultra',
                'Galaxy S24+',
                'Galaxy S24',
                'Galaxy S23 Ultra',
                'Galaxy S23+',
                'Galaxy S23',
                'Galaxy S22 Ultra',
                'Galaxy S22+',
                'Galaxy S22',
                'Galaxy A54',
                'Galaxy A53',
                'Galaxy A52',
                'Galaxy A34',
                'Galaxy A14',
                'Galaxy A13',
            )
        ),
        array(
            'name' => 'Calitate',
            'slug' => 'calitate',
            'values' => array(
                'Service Pack',
                'Premium OEM',
                'Aftermarket',
                'Refurbished',
            )
        ),
        array(
            'name' => 'Brand Piesă',
            'slug' => 'brand-piesa',
            'values' => array(
                'Apple Original',
                'Samsung Original',
                'JK',
                'GX',
                'ZY',
                'RJ',
                'HEX',
                'Foxconn',
                'Incell Generic',
            )
        ),
        array(
            'name' => 'Tehnologie',
            'slug' => 'tehnologie',
            'values' => array(
                'OLED Original',
                'Soft OLED',
                'Hard OLED',
                'Incell',
                'TFT',
                'LCD',
            )
        ),
        array(
            'name' => 'Culoare',
            'slug' => 'culoare',
            'values' => array(
                'Negru',
                'Alb',
                'Auriu',
                'Argintiu',
                'Albastru',
                'Verde',
                'Mov',
                'Roșu',
            )
        ),
    );
    
    // Creează atributele
    foreach ($attributes as $attribute_data) {
        $attribute_name = $attribute_data['name'];
        $attribute_slug = $attribute_data['slug'];
        $attribute_values = $attribute_data['values'];
        
        // Verifică dacă atributul există deja
        // Metoda 1: Verifică direct în DB
        global $wpdb;
        $existing_attribute_id = $wpdb->get_var($wpdb->prepare(
            "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
            $attribute_slug
        ));
        
        // Metoda 2: Verifică cu funcția WooCommerce (dacă există)
        if (!$existing_attribute_id && function_exists('wc_attribute_taxonomy_id_by_name')) {
            $existing_attribute_id = wc_attribute_taxonomy_id_by_name($attribute_slug);
        }
        
        if (!$existing_attribute_id) {
            // Creează atributul
            $attribute_id = wc_create_attribute(array(
                'name' => $attribute_name,
                'slug' => $attribute_slug,
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => true, // Pentru filtrare
            ));
            
            if (is_wp_error($attribute_id)) {
                $errors[] = sprintf('Eroare la crearea atributului "%s": %s', $attribute_name, $attribute_id->get_error_message());
                continue;
            }
            
            $attributes_created++;
            
            // Reînregistrează taxonomiile WooCommerce
            if (class_exists('WooCommerce')) {
                // Metoda 1: Folosește WC()->attribute_functions dacă există
                if (isset(WC()->attribute_functions) && method_exists(WC()->attribute_functions, 'register_attribute_taxonomies')) {
                    WC()->attribute_functions->register_attribute_taxonomies();
                } else {
                    // Metoda 2: Reînregistrează manual toate taxonomiile
                    $attribute_taxonomies = wc_get_attribute_taxonomies();
                    if ($attribute_taxonomies) {
                        foreach ($attribute_taxonomies as $tax) {
                            $taxonomy_name = wc_attribute_taxonomy_name($tax->attribute_name);
                            if (!taxonomy_exists($taxonomy_name)) {
                                register_taxonomy(
                                    $taxonomy_name,
                                    array('product'),
                                    array(
                                        'hierarchical' => false,
                                        'labels' => array(
                                            'name' => $tax->attribute_label,
                                            'singular_name' => $tax->attribute_label,
                                        ),
                                        'show_ui' => true,
                                        'query_var' => true,
                                        'rewrite' => array('slug' => $tax->attribute_name),
                                        'public' => true,
                                        'show_in_nav_menus' => false,
                                        'show_tagcloud' => false,
                                        'show_in_quick_edit' => false,
                                    )
                                );
                            }
                        }
                    }
                }
            }
            
            // Flush rewrite rules
            flush_rewrite_rules(false);
        }
        
        // Obține taxonomia pentru atribut (WooCommerce adaugă automat 'pa_')
        $taxonomy = wc_attribute_taxonomy_name($attribute_slug);
        
        // Verifică dacă taxonomia există (dacă nu, reînregistrează)
        if (!taxonomy_exists($taxonomy)) {
            // Reînregistrează taxonomiile
            if (class_exists('WooCommerce')) {
                // Metoda 1: Folosește WC()->attribute_functions dacă există
                if (isset(WC()->attribute_functions) && method_exists(WC()->attribute_functions, 'register_attribute_taxonomies')) {
                    WC()->attribute_functions->register_attribute_taxonomies();
                } else {
                    // Metoda 2: Reînregistrează manual toate taxonomiile
                    $attribute_taxonomies = wc_get_attribute_taxonomies();
                    if ($attribute_taxonomies) {
                        foreach ($attribute_taxonomies as $tax) {
                            $taxonomy_name = wc_attribute_taxonomy_name($tax->attribute_name);
                            if (!taxonomy_exists($taxonomy_name)) {
                                register_taxonomy(
                                    $taxonomy_name,
                                    array('product'),
                                    array(
                                        'hierarchical' => false,
                                        'labels' => array(
                                            'name' => $tax->attribute_label,
                                            'singular_name' => $tax->attribute_label,
                                        ),
                                        'show_ui' => true,
                                        'query_var' => true,
                                        'rewrite' => array('slug' => $tax->attribute_name),
                                        'public' => true,
                                        'show_in_nav_menus' => false,
                                        'show_tagcloud' => false,
                                        'show_in_quick_edit' => false,
                                    )
                                );
                            }
                        }
                    }
                }
            }
            
            // Flush rewrite rules
            flush_rewrite_rules(false);
        }
        
        // Creează termenii (valorile) pentru atribut
        foreach ($attribute_values as $value) {
            // Verifică dacă termenul există deja
            $existing_term = term_exists($value, $taxonomy);
            if ($existing_term) {
                continue; // Termenul există deja, trecem la următoarea
            }
            
            // Creează termenul
            $term_result = wp_insert_term(
                $value,
                $taxonomy,
                array()
            );
            
            if (is_wp_error($term_result)) {
                $errors[] = sprintf('Eroare la crearea termenului "%s" pentru atributul "%s": %s', $value, $attribute_name, $term_result->get_error_message());
            } else {
                $terms_created++;
            }
        }
    }
    
    // Setează opțiunea ca să nu ruleze din nou
    update_option('webgsm_attributes_installed', true);
    
    // Salvează rezultatele pentru admin notice
    update_option('webgsm_attributes_setup_result', array(
        'attributes_created' => $attributes_created,
        'terms_created' => $terms_created,
        'errors' => $errors,
        'timestamp' => current_time('mysql')
    ));
    
    return array(
        'attributes_created' => $attributes_created,
        'terms_created' => $terms_created,
        'errors' => $errors
    );
}

/**
 * Rulează setup-ul la init (doar o dată)
 * 
 * NOTĂ: Pentru a forța rularea din nou (după ștergerea atributelor):
 * 1. Șterge opțiunea: delete_option('webgsm_attributes_installed');
 * 2. Sau adaugă în wp-config.php temporar: define('WEBGSM_FORCE_ATTRIBUTES_SETUP', true);
 */
add_action('init', function() {
    // Verifică dacă atributele au fost deja create (dacă nu e forțat)
    if (!defined('WEBGSM_FORCE_ATTRIBUTES_SETUP') && get_option('webgsm_attributes_installed')) {
        return;
    }
    
    // Verifică dacă WooCommerce este activ
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // Rulează setup-ul
    webgsm_setup_product_attributes();
}, 99); // Prioritate 99 pentru a rula după WooCommerce (care se încarcă la 10)

/**
 * Afișează admin notice cu rezultatul
 */
add_action('admin_notices', function() {
    // Verifică dacă există rezultat
    $result = get_option('webgsm_attributes_setup_result');
    if (!$result) {
        return;
    }
    
    // Șterge rezultatul după afișare (pentru a nu afișa mereu)
    delete_option('webgsm_attributes_setup_result');
    
    $attributes_created = isset($result['attributes_created']) ? $result['attributes_created'] : 0;
    $terms_created = isset($result['terms_created']) ? $result['terms_created'] : 0;
    $errors = isset($result['errors']) ? $result['errors'] : array();
    
    if ($attributes_created > 0 || $terms_created > 0 || !empty($errors)) {
        $class = !empty($errors) ? 'notice notice-warning' : 'notice notice-success is-dismissible';
        ?>
        <div class="<?php echo esc_attr($class); ?>">
            <p><strong>WebGSM - Setup Atribute:</strong></p>
            <?php if ($attributes_created > 0) : ?>
                <p>✅ <strong><?php echo $attributes_created; ?></strong> atribute create cu succes.</p>
            <?php endif; ?>
            <?php if ($terms_created > 0) : ?>
                <p>✅ <strong><?php echo $terms_created; ?></strong> valori (termeni) create cu succes.</p>
            <?php endif; ?>
            <?php if (!empty($errors)) : ?>
                <p>⚠️ <strong>Erori:</strong></p>
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }
});
