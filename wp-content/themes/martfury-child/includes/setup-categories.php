<?php
/**
 * WebGSM - Setup Categories
 * Creează categoriile de produse WooCommerce aliniate cu logica scriptului Python (scraping).
 * 3 categorii părinte: Piese, Unelte, Accesorii.
 *
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

if (get_option('webgsm_categories_installed')) {
    return;
}

/**
 * Returnează structura categoriilor (nume, slug, parent_slug).
 * Ierarhia corespunde cu get_woo_category() din scriptul Python.
 */
function webgsm_get_category_structure() {
    $brands = array('iPhone', 'Samsung', 'Huawei', 'Xiaomi', 'Google', 'OnePlus', 'Oppo', 'Motorola');
    $piese_sub = array(
        'Ecrane', 'Baterii', 'Mufe Incarcare', 'Camere', 'Flexuri', 'Difuzoare',
        'Butoane', 'Sticla', 'Carcase', 'Vibratoare', 'Sertare SIM', 'Alte Piese'
    );

    $cats = array();

    // ═══ TOP: Piese, Unelte, Accesorii ═══
    $cats[] = array('name' => 'Piese', 'slug' => 'piese', 'parent' => 0);
    $cats[] = array('name' => 'Unelte', 'slug' => 'unelte', 'parent' => 0);
    $cats[] = array('name' => 'Accesorii', 'slug' => 'accesorii', 'parent' => 0);

    // ═══ Unelte > subcategorii ═══
    $unelte_sub = array(
        'Surubelnite' => 'surubelnite',
        'Pensete' => 'pensete',
        'Deschizatoare' => 'deschizatoare',
        'Suporturi PCB' => 'suporturi-pcb',
        'Lupe si Microscoape' => 'lupe-si-microscoape',
        'Statii si Echipamente' => 'statii-si-echipamente',
        'Testere' => 'testere',
        'Consumabile' => 'consumabile',
    );
    foreach ($unelte_sub as $name => $slug) {
        $cats[] = array('name' => $name, 'slug' => $slug, 'parent' => 'unelte');
    }

    // ═══ Accesorii > subcategorii ═══
    $accesorii_sub = array(
        'Huse' => 'huse',
        'Folii Protectie' => 'folii-protectie',
        'Cabluri' => 'cabluri',
        'Incarcatoare' => 'incarcatoare',
        'Suporturi Auto' => 'suporturi-auto',
    );
    foreach ($accesorii_sub as $name => $slug) {
        $cats[] = array('name' => $name, 'slug' => $slug, 'parent' => 'accesorii');
    }

    // ═══ Piese > Piese {Brand} ═══
    $brand_slugs = array(
        'iPhone' => 'piese-iphone',
        'Samsung' => 'piese-samsung',
        'Huawei' => 'piese-huawei',
        'Xiaomi' => 'piese-xiaomi',
        'Google' => 'piese-google',
        'OnePlus' => 'piese-oneplus',
        'Oppo' => 'piese-oppo',
        'Motorola' => 'piese-motorola',
    );
    foreach ($brands as $brand) {
        $pslug = $brand_slugs[$brand];
        $cats[] = array('name' => "Piese {$brand}", 'slug' => $pslug, 'parent' => 'piese');
        foreach ($piese_sub as $sub) {
            $sub_name = $sub . ' ' . $brand;
            $sub_slug = str_replace(' ', '-', strtolower(remove_accents_simple($sub))) . '-' . strtolower($brand);
            $sub_slug = preg_replace('/[^a-z0-9\-]/', '-', $sub_slug);
            $sub_slug = preg_replace('/\-+/', '-', trim($sub_slug, '-'));
            $cats[] = array('name' => $sub_name, 'slug' => $sub_slug, 'parent' => $pslug);
        }
    }

    return $cats;
}

function remove_accents_simple($s) {
    $map = array('ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't', 'ş' => 's', 'ţ' => 't');
    return strtr(mb_strtolower($s, 'UTF-8'), $map);
}

/**
 * Creează categoriile în WooCommerce.
 */
function webgsm_setup_product_categories() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    $categories = webgsm_get_category_structure();
    $parent_cache = array();
    $categories_created = 0;
    $errors = array();

    foreach ($categories as $category) {
        $name = $category['name'];
        $slug = $category['slug'];
        $parent_slug = $category['parent'];

        if (term_exists($slug, 'product_cat')) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term) {
                $parent_cache[$slug] = $term->term_id;
            }
            continue;
        }

        $parent_id = 0;
        if ($parent_slug !== 0) {
            if (isset($parent_cache[$parent_slug])) {
                $parent_id = $parent_cache[$parent_slug];
            } else {
                $parent_term = get_term_by('slug', $parent_slug, 'product_cat');
                if ($parent_term) {
                    $parent_id = $parent_term->term_id;
                    $parent_cache[$parent_slug] = $parent_id;
                } else {
                    $errors[] = sprintf('Parent "%s" nu există pentru "%s"', $parent_slug, $name);
                    continue;
                }
            }
        }

        $result = wp_insert_term(
            $name,
            'product_cat',
            array('slug' => $slug, 'parent' => $parent_id)
        );

        if (is_wp_error($result)) {
            $errors[] = sprintf('Eroare "%s": %s', $name, $result->get_error_message());
        } else {
            $categories_created++;
            $parent_cache[$slug] = $result['term_id'];
        }
    }

    update_option('webgsm_categories_installed', true);
    update_option('webgsm_categories_setup_result', array(
        'created' => $categories_created,
        'errors' => $errors,
        'timestamp' => current_time('mysql')
    ));

    return array('created' => $categories_created, 'errors' => $errors);
}

add_action('init', function() {
    if (!defined('WEBGSM_FORCE_CATEGORIES_SETUP') && get_option('webgsm_categories_installed')) {
        return;
    }
    webgsm_setup_product_categories();
}, 10);

add_action('admin_notices', function() {
    $result = get_option('webgsm_categories_setup_result');
    if (!$result) return;
    delete_option('webgsm_categories_setup_result');
    $created = isset($result['created']) ? $result['created'] : 0;
    $errors = isset($result['errors']) ? $result['errors'] : array();
    if ($created > 0 || !empty($errors)) {
        $class = !empty($errors) ? 'notice notice-warning' : 'notice notice-success is-dismissible';
        echo '<div class="' . esc_attr($class) . '"><p><strong>WebGSM - Setup Categorii:</strong></p>';
        if ($created > 0) echo '<p>✅ <strong>' . (int) $created . '</strong> categorii create.</p>';
        if (!empty($errors)) {
            echo '<p>⚠️ Erori:</p><ul style="margin-left:20px;">';
            foreach ($errors as $e) echo '<li>' . esc_html($e) . '</li>';
            echo '</ul>';
        }
        echo '</div>';
    }
});
