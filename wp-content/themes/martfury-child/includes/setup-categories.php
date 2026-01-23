<?php
/**
 * WebGSM - Setup Categories
 * Creează categoriile de produse WooCommerce la prima accesare
 * 
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit dacă accesat direct

/**
 * Verifică dacă categoriile au fost deja create
 * NOTĂ: Pentru a forța rularea din nou, șterge opțiunea:
 * delete_option('webgsm_categories_installed');
 */
if (get_option('webgsm_categories_installed')) {
    return; // Nu rulează din nou
}

/**
 * Funcție pentru crearea categoriilor
 */
function webgsm_setup_product_categories() {
    // Verifică dacă WooCommerce este activ
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    $categories_created = 0;
    $errors = array();
    
    // Structura categoriilor
    $categories = array(
        // Ecrane
        array(
            'name' => 'Ecrane',
            'slug' => 'ecrane',
            'parent' => 0
        ),
        array(
            'name' => 'iPhone',
            'slug' => 'ecrane-iphone',
            'parent' => 'ecrane'
        ),
        array(
            'name' => 'Samsung',
            'slug' => 'ecrane-samsung',
            'parent' => 'ecrane'
        ),
        
        // Baterii
        array(
            'name' => 'Baterii',
            'slug' => 'baterii',
            'parent' => 0
        ),
        array(
            'name' => 'iPhone',
            'slug' => 'baterii-iphone',
            'parent' => 'baterii'
        ),
        array(
            'name' => 'Samsung',
            'slug' => 'baterii-samsung',
            'parent' => 'baterii'
        ),
        
        // Camere
        array(
            'name' => 'Camere',
            'slug' => 'camere',
            'parent' => 0
        ),
        array(
            'name' => 'iPhone',
            'slug' => 'camere-iphone',
            'parent' => 'camere'
        ),
        array(
            'name' => 'Samsung',
            'slug' => 'camere-samsung',
            'parent' => 'camere'
        ),
        
        // Flex-uri
        array(
            'name' => 'Flex-uri',
            'slug' => 'flex-uri',
            'parent' => 0
        ),
        array(
            'name' => 'iPhone',
            'slug' => 'flex-uri-iphone',
            'parent' => 'flex-uri'
        ),
        array(
            'name' => 'Samsung',
            'slug' => 'flex-uri-samsung',
            'parent' => 'flex-uri'
        ),
        
        // Carcase
        array(
            'name' => 'Carcase',
            'slug' => 'carcase',
            'parent' => 0
        ),
        array(
            'name' => 'iPhone',
            'slug' => 'carcase-iphone',
            'parent' => 'carcase'
        ),
        array(
            'name' => 'Samsung',
            'slug' => 'carcase-samsung',
            'parent' => 'carcase'
        ),
        
        // Accesorii Service
        array(
            'name' => 'Accesorii Service',
            'slug' => 'accesorii-service',
            'parent' => 0
        ),
        array(
            'name' => 'Adezivi',
            'slug' => 'adezivi',
            'parent' => 'accesorii-service'
        ),
        array(
            'name' => 'Șuruburi',
            'slug' => 'suruburi',
            'parent' => 'accesorii-service'
        ),
        array(
            'name' => 'Unelte',
            'slug' => 'unelte',
            'parent' => 'accesorii-service'
        ),
        array(
            'name' => 'Mesh-uri',
            'slug' => 'mesh-uri',
            'parent' => 'accesorii-service'
        ),
    );
    
    // Cache pentru parent IDs (pentru a evita query-uri multiple)
    $parent_cache = array();
    
    // Creează categoriile
    foreach ($categories as $category) {
        $name = $category['name'];
        $slug = $category['slug'];
        $parent_slug = $category['parent'];
        
        // Verifică dacă categoria există deja
        $existing_term = term_exists($slug, 'product_cat');
        if ($existing_term) {
            continue; // Categoria există deja, trecem la următoarea
        }
        
        // Determină parent ID
        $parent_id = 0;
        if ($parent_slug !== 0) {
            // Caută parent-ul în cache sau în DB
            if (isset($parent_cache[$parent_slug])) {
                $parent_id = $parent_cache[$parent_slug];
            } else {
                $parent_term = get_term_by('slug', $parent_slug, 'product_cat');
                if ($parent_term) {
                    $parent_id = $parent_term->term_id;
                    $parent_cache[$parent_slug] = $parent_id;
                } else {
                    $errors[] = sprintf('Parent "%s" nu a fost găsit pentru "%s"', $parent_slug, $name);
                    continue; // Skip dacă parent-ul nu există
                }
            }
        }
        
        // Creează categoria
        $result = wp_insert_term(
            $name,
            'product_cat',
            array(
                'slug' => $slug,
                'parent' => $parent_id
            )
        );
        
        if (is_wp_error($result)) {
            $errors[] = sprintf('Eroare la crearea categoriei "%s": %s', $name, $result->get_error_message());
        } else {
            $categories_created++;
            // Adaugă în cache pentru a fi folosit ca parent pentru child-uri
            $parent_cache[$slug] = $result['term_id'];
        }
    }
    
    // Setează opțiunea ca să nu ruleze din nou
    update_option('webgsm_categories_installed', true);
    
    // Salvează rezultatele pentru admin notice
    update_option('webgsm_categories_setup_result', array(
        'created' => $categories_created,
        'errors' => $errors,
        'timestamp' => current_time('mysql')
    ));
    
    return array(
        'created' => $categories_created,
        'errors' => $errors
    );
}

/**
 * Rulează setup-ul la init (doar o dată)
 * 
 * NOTĂ: Pentru a forța rularea din nou (după ștergerea categoriilor):
 * 1. Șterge opțiunea: delete_option('webgsm_categories_installed');
 * 2. Sau adaugă în wp-config.php temporar: define('WEBGSM_FORCE_CATEGORIES_SETUP', true);
 */
add_action('init', function() {
    // Verifică dacă categoriile au fost deja create (dacă nu e forțat)
    if (!defined('WEBGSM_FORCE_CATEGORIES_SETUP') && get_option('webgsm_categories_installed')) {
        return;
    }
    
    // Rulează setup-ul
    webgsm_setup_product_categories();
}, 10);

/**
 * Afișează admin notice cu rezultatul
 */
add_action('admin_notices', function() {
    // Verifică dacă există rezultat
    $result = get_option('webgsm_categories_setup_result');
    if (!$result) {
        return;
    }
    
    // Șterge rezultatul după afișare (pentru a nu afișa mereu)
    delete_option('webgsm_categories_setup_result');
    
    $created = isset($result['created']) ? $result['created'] : 0;
    $errors = isset($result['errors']) ? $result['errors'] : array();
    
    if ($created > 0 || !empty($errors)) {
        $class = !empty($errors) ? 'notice notice-warning' : 'notice notice-success is-dismissible';
        ?>
        <div class="<?php echo esc_attr($class); ?>">
            <p><strong>WebGSM - Setup Categorii:</strong></p>
            <?php if ($created > 0) : ?>
                <p>✅ <strong><?php echo $created; ?></strong> categorii create cu succes.</p>
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
