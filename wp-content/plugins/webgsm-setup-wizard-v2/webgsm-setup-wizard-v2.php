<?php
/**
 * Plugin Name: WebGSM Setup Wizard v2
 * Description: Creează structura finală cu 5 taburi: Parts, Tools, Accessories, Devices, Services
 * Version: 2.0.0
 * Author: WebGSM
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

/**
 * Widget: două filtre cu bifă – Subcategorie Piese (iPhone, Samsung…) + Tip piesă (Ecrane, Baterii…).
 * Combinând: Piese iPhone + Ecrane → doar ecrane iPhone; Piese Samsung + Baterii → doar baterii Samsung.
 */
/**
 * Widget generic pentru filtre statice dinamice - citește subcategoriile din WooCommerce
 * Funcționează pentru: Piese, Unelte, Accesorii
 */
class WebGSM_Widget_Category_Filter extends WP_Widget {
    
    // Configurație pentru categoriile principale
    private static $main_categories = [
        'piese' => [
            'name' => 'Piese',
            'filter_param' => 'filter_piese_subcat',
            'has_tip_filter' => true, // Piese are și filtrare după Tip piesă
        ],
        'unelte' => [
            'name' => 'Unelte',
            'filter_param' => 'filter_unelte',
        ],
        'accesorii' => [
            'name' => 'Accesorii',
            'filter_param' => 'filter_accesorii',
        ],
        'dispozitive' => [
            'name' => 'Dispozitive',
            'filter_param' => 'filter_dispozitive',
        ],
        'servicii' => [
            'name' => 'Servicii',
            'filter_param' => 'filter_servicii',
        ],
    ];
    
    /**
     * Obține subcategoriile pentru o categorie principală din WooCommerce
     */
    private function get_subcategories($parent_slug) {
        $parent_term = get_term_by('slug', $parent_slug, 'product_cat');
        if (!$parent_term || is_wp_error($parent_term)) {
            return [];
        }
        
        $subcategories = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $parent_term->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        if (is_wp_error($subcategories) || empty($subcategories)) {
            return [];
        }
        
        $result = [];
        foreach ($subcategories as $term) {
            $result[$term->name] = $term->slug;
        }
        
        // Sortează alfabetic după nume
        ksort($result);
        
        return $result;
    }
    
    /**
     * Obține tipurile de piese (nivel 3) pentru categoria Piese
     * Caută toate categoriile de nivel 3 care sunt subcategorii ale categoriilor "piese-*"
     */
    private function get_piese_tip_categories() {
        $result = [];
        
        // Obține toate categoriile "piese-*" (nivel 2)
        $piese_parents = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'slug' => 'piese',
            'hide_empty' => false,
        ]);
        
        if (is_wp_error($piese_parents) || empty($piese_parents)) {
            return [];
        }
        
        $piese_parent_id = $piese_parents[0]->term_id;
        
        // Obține toate subcategoriile "piese-*" (nivel 2)
        $piese_subcats = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $piese_parent_id,
            'hide_empty' => false,
        ]);
        
        if (is_wp_error($piese_subcats) || empty($piese_subcats)) {
            return [];
        }
        
        // Pentru fiecare subcategorie "piese-*", obține sub-subcategoriile (nivel 3)
        // și normalizează tipul la forma generică (ex. ecrane, baterii),
        // nu la slug specific pe brand (ex. ecrane-xiaomi).
        $tip_names = [];
        foreach ($piese_subcats as $subcat) {
            $brand_suffix = str_replace('piese-', '', $subcat->slug);
            $tip_cats = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $subcat->term_id,
                'hide_empty' => false,
            ]);
            
            if (!is_wp_error($tip_cats) && !empty($tip_cats)) {
                foreach ($tip_cats as $tip_cat) {
                    $tip_slug = preg_replace('/-' . preg_quote($brand_suffix, '/') . '$/', '', $tip_cat->slug);
                    if (!$tip_slug) {
                        continue;
                    }
                    $label = ucwords(str_replace('-', ' ', $tip_slug));
                    $tip_names[$label] = $tip_slug;
                }
            }
        }
        
        // Sortează alfabetic după nume
        ksort($tip_names);
        
        return $tip_names;
    }
    
    /**
     * Detectează categoria principală curentă
     */
    private function detect_main_category() {
        // Verifică parametri de filtrare (prioritate)
        foreach (self::$main_categories as $slug => $config) {
            $param = isset($_GET[$config['filter_param']]) ? sanitize_text_field(wp_unslash($_GET[$config['filter_param']])) : '';
            if (!empty($param)) {
                return $slug;
            }
        }
        
        // Verifică dacă suntem pe o pagină de categorie
        if (is_product_category()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->slug)) {
                $slug = $queried_object->slug;
                
                // Verifică dacă este categoria principală
                if (isset(self::$main_categories[$slug])) {
                    return $slug;
                }
                
                // Verifică dacă este subcategorie
                if ($queried_object->parent > 0) {
                    $parent = get_term($queried_object->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent)) {
                        if (isset(self::$main_categories[$parent->slug])) {
                            return $parent->slug;
                        }
                    }
                }
                
                // Verifică dacă este sub-subcategorie (nivel 3) - caută în toți strămoșii
                $ancestors = get_ancestors($queried_object->term_id, 'product_cat');
                foreach ($ancestors as $ancestor_id) {
                    $ancestor = get_term($ancestor_id, 'product_cat');
                    if ($ancestor && !is_wp_error($ancestor) && isset(self::$main_categories[$ancestor->slug])) {
                        return $ancestor->slug;
                    }
                }
            }
        }
        
        // Pe shop sau alte pagini, nu afișa widget-ul
        return null;
    }

    public function __construct() {
        parent::__construct(
            'webgsm_category_filter',
            'WebGSM Filtru Categorii (Dinamic)',
            ['description' => 'Filtre dinamice care citesc subcategoriile din WooCommerce. Funcționează pentru Piese, Unelte, Accesorii, Dispozitive, Servicii.']
        );
    }

    public function widget($args, $instance) {
        $main_category_slug = $this->detect_main_category();
        
        if (!$main_category_slug || !isset(self::$main_categories[$main_category_slug])) {
            return; // Nu afișa widget-ul dacă nu suntem într-o categorie suportată
        }
        
        $config = self::$main_categories[$main_category_slug];
        $filter_param = $config['filter_param'];
        
        // Obține subcategoriile dinamic
        $subcategories = $this->get_subcategories($main_category_slug);
        
        if (empty($subcategories)) {
            return; // Nu afișa dacă nu există subcategorii
        }
        
        // Procesează parametrii de filtrare (sau categoria curentă când suntem pe pagină directă)
        $selected_param = isset($_GET[$filter_param]) ? sanitize_text_field(wp_unslash($_GET[$filter_param])) : '';
        $selected = $selected_param ? array_map('trim', explode(',', $selected_param)) : [];
        
        $tip_selected = [];
        if ($main_category_slug === 'piese' && isset($config['has_tip_filter']) && $config['has_tip_filter']) {
            $tip_param = isset($_GET['filter_piese_tip']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_tip'])) : '';
            $tip_selected = $tip_param ? WebGSM_Widget_Piese_Filter::normalize_tip_slugs(array_map('trim', explode(',', $tip_param))) : [];
        }

        // Când suntem pe o pagină categorie directă (fără params), detectează subcat/tip pentru starea activ
        if ($main_category_slug === 'piese' && empty($selected) && is_product_category()) {
            $q = get_queried_object();
            if ($q && isset($q->slug)) {
                if (strpos($q->slug, 'piese-') === 0) {
                    $selected = [$q->slug];
                } elseif ($q->parent > 0) {
                    $parent = get_term($q->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && strpos($parent->slug, 'piese-') === 0) {
                        $selected = [$parent->slug];
                        $tip_selected = WebGSM_Widget_Piese_Filter::normalize_tip_slugs([preg_replace('/-' . preg_quote(str_replace('piese-', '', $parent->slug), '/') . '$/', '', $q->slug)]);
                    }
                }
            }
        }
        
        $base_url = remove_query_arg([$filter_param, 'filter_piese_tip', 'paged']);
        $piese_term = get_term_by('slug', 'piese', 'product_cat');
        $piese_base = ($piese_term && !is_wp_error($piese_term)) ? get_term_link($piese_term) : wc_get_page_permalink('shop');
        if (is_wp_error($piese_base)) $piese_base = wc_get_page_permalink('shop');

        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
        }

        echo '<div class="webgsm-category-filter webgsm-category-filter-' . esc_attr($main_category_slug) . '">';
        
        // Afișează subcategoriile – linkuri directe către pagini categorie (ca meniul principal)
        echo '<div class="webgsm-filter-group">';
        echo '<div class="webgsm-filter-label">' . esc_html($config['name']) . '</div>';
        echo '<div class="webgsm-filter-scroll-container">';
        echo '<ul class="woocommerce-widget-layered-nav-list webgsm-filter-list">';
        
        foreach ($subcategories as $label => $slug) {
            $active = in_array($slug, $selected, true);
            $new_vals = $active ? array_diff($selected, [$slug]) : array_merge($selected, [$slug]);
            $href = $base_url;

            if ($main_category_slug === 'piese') {
                $term = get_term_by('slug', $slug, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    if (count($new_vals) === 1 && empty($tip_selected)) {
                        $href = get_term_link($term);
                    } elseif (count($new_vals) === 1 && count($tip_selected) === 1) {
                        $child_slug = WebGSM_Widget_Piese_Filter::find_level3_slug($slug, $tip_selected[0]);
                        if ($child_slug) {
                            $child = get_term_by('slug', $child_slug, 'product_cat');
                            if ($child && !is_wp_error($child)) {
                                $href = get_term_link($child);
                            } else {
                                $href = add_query_arg(['filter_piese_subcat' => implode(',', $new_vals), 'filter_piese_tip' => implode(',', $tip_selected)], $piese_base);
                            }
                        } else {
                            $href = add_query_arg(['filter_piese_subcat' => implode(',', $new_vals), 'filter_piese_tip' => implode(',', $tip_selected)], $piese_base);
                        }
                    } else {
                        if (!empty($new_vals)) $href = add_query_arg($filter_param, implode(',', $new_vals), $href);
                        if (!empty($tip_selected)) $href = add_query_arg('filter_piese_tip', implode(',', $tip_selected), $href);
                        if ($href === $base_url) $href = $piese_base;
                    }
                } else {
                    if (!empty($new_vals)) $href = add_query_arg($filter_param, implode(',', $new_vals), $href);
                    if (!empty($tip_selected)) $href = add_query_arg('filter_piese_tip', implode(',', $tip_selected), $href);
                }
            } else {
                if (!empty($new_vals)) $href = add_query_arg($filter_param, implode(',', $new_vals), $href);
            }
            
            $li_class = 'woocommerce-widget-layered-nav-list__item wc-layered-nav-term' . ($active ? ' woocommerce-widget-layered-nav-list__item--chosen chosen' : '');
            echo '<li class="' . esc_attr($li_class) . '"><a rel="nofollow" href="' . esc_url($href) . '">';
            echo '<span class="webgsm-filter-cb' . ($active ? ' chosen' : '') . '" aria-hidden="true"></span>';
            echo '<span class="webgsm-filter-label-text">' . esc_html($label) . '</span></a></li>';
        }
        
        echo '</ul>';
        echo '</div>'; // .webgsm-filter-scroll-container
        echo '</div>'; // .webgsm-filter-group
        
        // Pentru Piese, afișează și filtrarea după Tip piesă – linkuri directe când e posibil
        if ($main_category_slug === 'piese' && isset($config['has_tip_filter']) && $config['has_tip_filter']) {
            $tip_categories = $this->get_piese_tip_categories();
            if (!empty($tip_categories)) {
                echo '<div class="webgsm-filter-group">';
                echo '<div class="webgsm-filter-label">Tip piesă</div>';
                echo '<div class="webgsm-filter-scroll-container">';
                echo '<ul class="woocommerce-widget-layered-nav-list webgsm-filter-list">';
                
                foreach ($tip_categories as $label => $slug) {
                    $active = in_array($slug, $tip_selected, true);
                    $new_vals = $active ? array_diff($tip_selected, [$slug]) : array_merge($tip_selected, [$slug]);
                    $href = $base_url;

                    if (count($selected) === 1 && count($new_vals) === 1) {
                        $child_slug = WebGSM_Widget_Piese_Filter::find_level3_slug($selected[0], $new_vals[0]);
                        if ($child_slug) {
                            $child = get_term_by('slug', $child_slug, 'product_cat');
                            if ($child && !is_wp_error($child)) {
                                $href = get_term_link($child);
                            } else {
                                $href = add_query_arg(['filter_piese_subcat' => implode(',', $selected), 'filter_piese_tip' => implode(',', $new_vals)], $piese_base);
                            }
                        } else {
                            $href = add_query_arg(['filter_piese_subcat' => implode(',', $selected), 'filter_piese_tip' => implode(',', $new_vals)], $piese_base);
                        }
                    } else {
                        if (!empty($new_vals)) $href = add_query_arg('filter_piese_tip', implode(',', $new_vals), $href);
                        if (!empty($selected)) $href = add_query_arg($filter_param, implode(',', $selected), $href);
                        if ($href === $base_url) $href = $piese_base;
                    }
                    
                    $li_class = 'woocommerce-widget-layered-nav-list__item wc-layered-nav-term' . ($active ? ' woocommerce-widget-layered-nav-list__item--chosen chosen' : '');
                    echo '<li class="' . esc_attr($li_class) . '"><a rel="nofollow" href="' . esc_url($href) . '">';
                    echo '<span class="webgsm-filter-cb' . ($active ? ' chosen' : '') . '" aria-hidden="true"></span>';
                    echo '<span class="webgsm-filter-label-text">' . esc_html($label) . '</span></a></li>';
                }
                
                echo '</ul>';
                echo '</div>'; // .webgsm-filter-scroll-container
                echo '</div>'; // .webgsm-filter-group
            }
        }

        echo '</div>'; // .webgsm-category-filter
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        echo '<p><label for="' . esc_attr($this->get_field_id('title')) . '">Titlu:</label>';
        echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '"></p>';
        echo '<p><small>Widget-ul detectează automat categoria și afișează subcategoriile disponibile din WooCommerce.</small></p>';
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

class WebGSM_Widget_Piese_Filter extends WP_Widget {
    /** Fallback static – folosit doar dacă în WC nu există categorii. */
    private static $subcat_config = [
        'Piese iPhone' => 'piese-iphone',
        'Piese Samsung' => 'piese-samsung',
        'Piese Huawei' => 'piese-huawei',
        'Piese Xiaomi' => 'piese-xiaomi',
        'Piese Ipad' => 'piese-ipad',
        'Piese Macbook' => 'piese-macbook',
    ];
    private static $tip_config = [
        'Ecrane' => 'ecrane',
        'Baterii' => 'baterii',
        'Baterii Piese' => 'baterii-piese',
        'Camere' => 'camere',
        'Carcase' => 'carcase',
        'Difuzoare' => 'difuzoare',
        'Flexuri' => 'flexuri',
        'Mufe Încărcare' => 'mufe-incarcare',
        'Mufe Incarcare' => 'mufe-incarcare',
    ];

    /** Subcategorii Piese din WooCommerce (nivel 2: piese-iphone, piese-samsung …). */
    public static function get_subcat_slugs_from_wc() {
        $parent = get_term_by('slug', 'piese', 'product_cat');
        if (!$parent || is_wp_error($parent)) {
            return array_values(self::$subcat_config);
        }
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $parent->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        if (is_wp_error($terms) || empty($terms)) {
            return array_values(self::$subcat_config);
        }
        return array_map(function ($t) { return $t->slug; }, $terms);
    }

    /** Tipuri piesă din WooCommerce (nivel 3: ecrane, mufe-incarcare etc. – prefix din ecrane-iphone, mufe-incarcare-iphone). */
    public static function get_tip_slugs_from_wc() {
        $parent = get_term_by('slug', 'piese', 'product_cat');
        if (!$parent || is_wp_error($parent)) {
            return array_values(self::$tip_config);
        }
        $subcats = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $parent->term_id,
            'hide_empty' => false,
        ]);
        if (is_wp_error($subcats) || empty($subcats)) {
            return array_values(self::$tip_config);
        }
        $tip_slugs = [];
        foreach ($subcats as $sub) {
            $brand_suffix = str_replace('piese-', '', $sub->slug);
            $children = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $sub->term_id,
                'hide_empty' => false,
            ]);
            if (is_wp_error($children)) continue;
            foreach ($children as $c) {
                $tip = preg_replace('/-' . preg_quote($brand_suffix, '/') . '$/', '', $c->slug);
                if ($tip !== $c->slug) {
                    $tip_slugs[] = $tip;
                }
            }
        }
        $tip_slugs = array_values(array_unique($tip_slugs));
        return $tip_slugs ?: array_values(self::$tip_config);
    }

    /** Curăță lista de slug-uri din query string. */
    public static function normalize_slug_list(array $slugs) {
        $out = [];
        foreach ($slugs as $slug) {
            $slug = sanitize_title($slug);
            if ($slug !== '') {
                $out[] = $slug;
            }
        }
        return array_values(array_unique($out));
    }

    /** Aliasuri slug tip – mapare la slug-ul canonic pentru filtrare corectă. */
    private static $tip_slug_aliases = [
        'mufe-incarcare' => 'mufe-incarcare',
        'mufe-incărcare' => 'mufe-incarcare',
        'mufe_incarcare' => 'mufe-incarcare',
        'baterii-piese' => 'baterii-piese',
    ];

    /**
     * Normalizează tipurile de piese din URL la forma generică.
     * Ex: ecrane-xiaomi => ecrane
     */
    public static function normalize_tip_slugs(array $tip_slugs) {
        $tip_slugs = self::normalize_slug_list($tip_slugs);
        $valid_tips = self::get_tip_slugs_from_wc();
        $out = [];

        foreach ($tip_slugs as $slug) {
            // Alias explicit
            if (isset(self::$tip_slug_aliases[$slug])) {
                $out[] = self::$tip_slug_aliases[$slug];
                continue;
            }
            if (in_array($slug, $valid_tips, true)) {
                $out[] = $slug;
                continue;
            }

            foreach ($valid_tips as $tip) {
                if (strpos($slug, $tip . '-') === 0 || $slug === $tip) {
                    $out[] = $tip;
                    break;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Găsește slug-ul categoriei de nivel 3 (ex: ecrane-iphone) din subcat (piese-iphone) și tip (ecrane).
     */
    public static function find_level3_slug($subcat_slug, $tip_slug) {
        // PHP 8.1+: preg_quote()/str_replace() must not receive null.
        $subcat_slug = (string) ($subcat_slug ?? '');
        $tip_slug = (string) ($tip_slug ?? '');
        if ($subcat_slug === '') {
            return null;
        }
        $subcat_term = get_term_by('slug', $subcat_slug, 'product_cat');
        if (!$subcat_term || is_wp_error($subcat_term)) return null;
        $brand = str_replace('piese-', '', $subcat_slug);
        $children = get_terms(['taxonomy' => 'product_cat', 'parent' => $subcat_term->term_id, 'hide_empty' => false]);
        if (is_wp_error($children) || empty($children)) return null;
        $tip_q = preg_quote($tip_slug, '/');
        $brand_q = preg_quote($brand, '/');
        foreach ($children as $c) {
            if ($c->slug === $tip_slug || strpos($c->slug, $tip_slug . '-') === 0) return $c->slug;
            if (preg_match('/^' . $tip_q . '-.*-' . $brand_q . '$/', $c->slug)) return $c->slug;
            if (preg_match('/^' . $tip_q . '-' . $brand_q . '$/', $c->slug)) return $c->slug;
        }
        return $tip_slug . '-' . $brand;
    }

    /** Păstrează doar slug-urile care există în product_cat. */
    public static function normalize_existing_product_cat_slugs(array $slugs) {
        $slugs = self::normalize_slug_list($slugs);
        $out = [];
        foreach ($slugs as $slug) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $out[] = $slug;
            }
        }
        return array_values(array_unique($out));
    }

    public function __construct() {
        parent::__construct(
            'webgsm_piese_filter',
            'WebGSM Filtru Piese (Subcategorie + Tip)',
            ['description' => 'Filtre cu bifă: Subcategorie Piese (iPhone, Samsung…) și Tip piesă (Ecrane, Baterii…).']
        );
    }

    public function widget($args, $instance) {
        // Verifică dacă widget-ul generic este activ - dacă da, nu afișa widget-ul vechi pentru Piese
        $sidebars = get_option('sidebars_widgets', []);
        $has_generic_widget = false;
        foreach ($sidebars as $sidebar_widgets) {
            if (is_array($sidebar_widgets)) {
                foreach ($sidebar_widgets as $widget_id) {
                    if (strpos($widget_id, 'webgsm_category_filter-') === 0) {
                        $has_generic_widget = true;
                        break 2;
                    }
                }
            }
        }
        
        // Dacă widget-ul generic este activ, nu afișa widget-ul vechi pentru Piese
        if ($has_generic_widget) {
            return;
        }
        
        // Afișează widget-ul DOAR în categoria "Piese" sau subcategoriile sale
        $is_piese_category = false;
        
        if (is_product_category()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->slug)) {
                // Verifică dacă suntem în categoria "piese"
                if ($queried_object->slug === 'piese') {
                    $is_piese_category = true;
                } else {
                    // Verifică dacă este o subcategorie a "piese" (nivel 2: piese-iphone, piese-samsung etc.)
                    $parent = get_term($queried_object->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'piese') {
                        $is_piese_category = true;
                    } else {
                        // Verifică dacă este o sub-subcategorie (nivel 3: ecrane-iphone, baterii-samsung etc.)
                        $ancestors = get_ancestors($queried_object->term_id, 'product_cat');
                        foreach ($ancestors as $ancestor_id) {
                            $ancestor = get_term($ancestor_id, 'product_cat');
                            if ($ancestor && !is_wp_error($ancestor) && $ancestor->slug === 'piese') {
                                $is_piese_category = true;
                                break;
                            }
                        }
                    }
                }
            }
        } elseif (is_shop() || is_product_taxonomy()) {
            // Pe shop sau alte taxonomii, verifică dacă există parametri de filtrare pentru piese
            $subcat_param = isset($_GET['filter_piese_subcat']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_subcat'])) : '';
            $tip_param = isset($_GET['filter_piese_tip']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_tip'])) : '';
            if (!empty($subcat_param) || !empty($tip_param)) {
                $is_piese_category = true;
            }
        }
        
        // Dacă nu suntem în categoria Piese, nu afișa widget-ul
        if (!$is_piese_category) {
            return;
        }
        
        $subcat_param = isset($_GET['filter_piese_subcat']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_subcat'])) : '';
        $tip_param = isset($_GET['filter_piese_tip']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_tip'])) : '';
        $subcat_selected = $subcat_param ? array_map('trim', explode(',', $subcat_param)) : [];
        $tip_selected = $tip_param ? WebGSM_Widget_Piese_Filter::normalize_tip_slugs(array_map('trim', explode(',', $tip_param))) : [];

        if (empty($subcat_selected) && is_product_category()) {
            $q = get_queried_object();
            if ($q && isset($q->slug)) {
                if (strpos($q->slug, 'piese-') === 0) {
                    $subcat_selected = [$q->slug];
                } elseif ($q->parent > 0) {
                    $parent = get_term($q->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && strpos($parent->slug, 'piese-') === 0) {
                        $subcat_selected = [$parent->slug];
                        $tip_selected = WebGSM_Widget_Piese_Filter::normalize_tip_slugs([preg_replace('/-' . preg_quote(str_replace('piese-', '', $parent->slug), '/') . '$/', '', $q->slug)]);
                    }
                }
            }
        }

        $base_url = remove_query_arg(['filter_piese_subcat', 'filter_piese_tip', 'paged']);
        $piese_term = get_term_by('slug', 'piese', 'product_cat');
        $piese_base = ($piese_term && !is_wp_error($piese_term)) ? get_term_link($piese_term) : wc_get_page_permalink('shop');
        if (is_wp_error($piese_base)) $piese_base = wc_get_page_permalink('shop');

        $subcats_for_display = $this->get_subcats_for_display();
        $tips_for_display = $this->get_tips_for_display();

        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
        }

        echo '<div class="webgsm-piese-filter">';

        echo '<div class="webgsm-filter-group">';
        echo '<div class="webgsm-filter-label">Piese</div>';
        echo '<ul class="woocommerce-widget-layered-nav-list webgsm-filter-list">';
        foreach ($subcats_for_display as $label => $slug) {
            $active = in_array($slug, $subcat_selected, true);
            $new_vals = $active ? array_diff($subcat_selected, [$slug]) : array_merge($subcat_selected, [$slug]);
            $href = $base_url;
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term && !is_wp_error($term)) {
                if (count($new_vals) === 1 && empty($tip_selected)) {
                    $href = get_term_link($term);
                } elseif (count($new_vals) === 1 && count($tip_selected) === 1) {
                    $child_slug = self::find_level3_slug($slug, $tip_selected[0]);
                    if ($child_slug) {
                        $child = get_term_by('slug', $child_slug, 'product_cat');
                        if ($child && !is_wp_error($child)) $href = get_term_link($child);
                        else $href = add_query_arg(['filter_piese_subcat' => implode(',', $new_vals), 'filter_piese_tip' => implode(',', $tip_selected)], $piese_base);
                    } else {
                        $href = add_query_arg(['filter_piese_subcat' => implode(',', $new_vals), 'filter_piese_tip' => implode(',', $tip_selected)], $piese_base);
                    }
                } else {
                    if (!empty($new_vals)) $href = add_query_arg('filter_piese_subcat', implode(',', $new_vals), $href);
                    if (!empty($tip_selected)) $href = add_query_arg('filter_piese_tip', implode(',', $tip_selected), $href);
                    if ($href === $base_url) $href = $piese_base;
                }
            } else {
                if (!empty($new_vals)) $href = add_query_arg('filter_piese_subcat', implode(',', $new_vals), $href);
                if (!empty($tip_selected)) $href = add_query_arg('filter_piese_tip', implode(',', $tip_selected), $href);
            }
            $li_class = 'woocommerce-widget-layered-nav-list__item wc-layered-nav-term' . ($active ? ' woocommerce-widget-layered-nav-list__item--chosen chosen' : '');
            echo '<li class="' . esc_attr($li_class) . '"><a rel="nofollow" href="' . esc_url($href) . '">';
            echo '<span class="webgsm-filter-cb' . ($active ? ' chosen' : '') . '" aria-hidden="true"></span>';
            echo '<span class="webgsm-filter-label-text">' . esc_html($label) . '</span></a></li>';
        }
        echo '</ul></div>';

        echo '<div class="webgsm-filter-group">';
        echo '<div class="webgsm-filter-label">Tip piesă</div>';
        echo '<ul class="woocommerce-widget-layered-nav-list webgsm-filter-list">';
        foreach ($tips_for_display as $label => $slug) {
            $active = in_array($slug, $tip_selected, true);
            $new_vals = $active ? array_diff($tip_selected, [$slug]) : array_merge($tip_selected, [$slug]);
            $href = $base_url;
            if (count($subcat_selected) === 1 && count($new_vals) === 1) {
                $child_slug = self::find_level3_slug($subcat_selected[0], $new_vals[0]);
                if ($child_slug) {
                    $child = get_term_by('slug', $child_slug, 'product_cat');
                    if ($child && !is_wp_error($child)) $href = get_term_link($child);
                    else $href = add_query_arg(['filter_piese_subcat' => implode(',', $subcat_selected), 'filter_piese_tip' => implode(',', $new_vals)], $piese_base);
                } else {
                    $href = add_query_arg(['filter_piese_subcat' => implode(',', $subcat_selected), 'filter_piese_tip' => implode(',', $new_vals)], $piese_base);
                }
            } else {
                if (!empty($new_vals)) $href = add_query_arg('filter_piese_tip', implode(',', $new_vals), $href);
                if (!empty($subcat_selected)) $href = add_query_arg('filter_piese_subcat', implode(',', $subcat_selected), $href);
                if ($href === $base_url) $href = $piese_base;
            }
            $li_class = 'woocommerce-widget-layered-nav-list__item wc-layered-nav-term' . ($active ? ' woocommerce-widget-layered-nav-list__item--chosen chosen' : '');
            echo '<li class="' . esc_attr($li_class) . '"><a rel="nofollow" href="' . esc_url($href) . '">';
            echo '<span class="webgsm-filter-cb' . ($active ? ' chosen' : '') . '" aria-hidden="true"></span>';
            echo '<span class="webgsm-filter-label-text">' . esc_html($label) . '</span></a></li>';
        }
        echo '</ul></div>';

        echo '</div>';
        echo $args['after_widget'];
    }

    private function get_subcats_for_display() {
        $parent = get_term_by('slug', 'piese', 'product_cat');
        if (!$parent || is_wp_error($parent)) {
            return self::$subcat_config;
        }
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $parent->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        if (is_wp_error($terms) || empty($terms)) {
            return self::$subcat_config;
        }
        $out = [];
        foreach ($terms as $t) {
            $out[$t->name] = $t->slug;
        }
        return $out;
    }

    private function get_tips_for_display() {
        $parent = get_term_by('slug', 'piese', 'product_cat');
        if (!$parent || is_wp_error($parent)) {
            return self::$tip_config;
        }
        $subcats = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => $parent->term_id,
            'hide_empty' => false,
        ]);
        if (is_wp_error($subcats) || empty($subcats)) {
            return self::$tip_config;
        }
        $tip_by_slug = [];
        foreach ($subcats as $sub) {
            $brand_suffix = str_replace('piese-', '', $sub->slug);
            $children = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $sub->term_id,
                'hide_empty' => false,
            ]);
            if (is_wp_error($children)) continue;
            foreach ($children as $c) {
                $tip_slug = preg_replace('/-' . preg_quote($brand_suffix, '/') . '$/', '', $c->slug);
                if ($tip_slug !== $c->slug && !isset($tip_by_slug[$tip_slug])) {
                    $tip_by_slug[$tip_slug] = $c->name;
                }
            }
        }
        if (empty($tip_by_slug)) {
            return self::$tip_config;
        }
        $out = [];
        foreach ($tip_by_slug as $slug => $name) {
            $label = ucfirst(str_replace('-', ' ', $slug));
            $out[$label] = $slug;
        }
        ksort($out);
        return $out;
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        echo '<p><label for="' . esc_attr($this->get_field_id('title')) . '">Titlu:</label>';
        echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '"></p>';
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }

    /** Returnează slug-urile subcategoriilor (piese-iphone etc.) pentru query. */
    public static function get_subcat_slugs() {
        return array_values(self::$subcat_config);
    }

    /** Returnează slug-urile tipurilor (ecrane, baterii etc.) pentru query. */
    public static function get_tip_slugs() {
        return array_values(self::$tip_config);
    }

    /**
     * Construiește slug-urile categoriilor de nivel 3 (ecrane-iphone, baterii-samsung etc.)
     * din subcat_slugs (piese-iphone, piese-samsung) și tip_slugs (ecrane, baterii).
     */
    public static function resolve_category_slugs(array $subcat_slugs, array $tip_slugs) {
        $out = [];
        foreach ($subcat_slugs as $subcat) {
            $brand = str_replace('piese-', '', $subcat);
            foreach ($tip_slugs as $tip) {
                $out[] = $tip . '-' . $brand;
            }
        }
        return $out;
    }

    /**
     * Rezolvă categoriile de nivel 3 direct din ierarhia WooCommerce
     * (mai robust decât compunerea slug-ului tip-brand).
     */
    public static function resolve_category_term_ids(array $subcat_slugs, array $tip_slugs) {
        $out = [];
        $tip_slugs = array_values(array_unique(array_filter($tip_slugs)));
        if (empty($subcat_slugs) || empty($tip_slugs)) {
            return [];
        }

        foreach ($subcat_slugs as $subcat_slug) {
            $subcat_term = get_term_by('slug', $subcat_slug, 'product_cat');
            if (!$subcat_term || is_wp_error($subcat_term)) {
                continue;
            }

            $children = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $subcat_term->term_id,
                'hide_empty' => false,
            ]);
            if (is_wp_error($children) || empty($children)) {
                continue;
            }

            foreach ($children as $child) {
                foreach ($tip_slugs as $tip_slug) {
                    $child_name_slug = sanitize_title($child->name);
                    if (
                        $child->slug === $tip_slug ||
                        strpos($child->slug, $tip_slug . '-') === 0 ||
                        $child_name_slug === $tip_slug ||
                        strpos($child_name_slug, $tip_slug . '-') === 0
                    ) {
                        $out[] = (int) $child->term_id;
                        break;
                    }
                }
            }
        }

        $out = array_values(array_unique($out));
        if (!empty($out)) {
            return $out;
        }

        // Fallback pentru ierarhii neuniforme:
        // caută termeni sub "piese" care au slug de tipul tip-brand chiar dacă nu sunt copii direcți.
        $piese = get_term_by('slug', 'piese', 'product_cat');
        if (!$piese || is_wp_error($piese)) {
            return [];
        }

        $all_under_piese = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
        if (is_wp_error($all_under_piese) || empty($all_under_piese)) {
            return [];
        }

        foreach ($subcat_slugs as $subcat_slug) {
            $brand = str_replace('piese-', '', sanitize_title($subcat_slug));
            if ($brand === '') {
                continue;
            }

            foreach ($all_under_piese as $term) {
                $ancestors = get_ancestors($term->term_id, 'product_cat');
                if (!in_array((int) $piese->term_id, array_map('intval', $ancestors), true)) {
                    continue;
                }

                $term_slug = sanitize_title($term->slug);
                $term_name_slug = sanitize_title($term->name);

                foreach ($tip_slugs as $tip_slug_raw) {
                    $tip_slug = sanitize_title($tip_slug_raw);
                    $matches_slug = (strpos($term_slug, $tip_slug . '-') === 0 && strpos($term_slug, '-' . $brand) !== false);
                    $matches_name = (strpos($term_name_slug, $tip_slug . '-') === 0 && strpos($term_name_slug, '-' . $brand) !== false);
                    if ($matches_slug || $matches_name) {
                        $out[] = (int) $term->term_id;
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($out));
    }
}

class WebGSM_Setup_Wizard_V2 {
    
    private static $instance = null;
    private $filter_debug_data = [];

    /** Sidebar-uri candidate pentru shop/catalog. */
    private function get_shop_sidebar_candidates() {
        $ids = [
            'catalog-sidebar',
            'shop-sidebar',
            'sidebar-shop',
            'woocommerce-sidebar',
            'mf-catalog-sidebar',
            'martfury-sidebar-shop',
        ];
        return apply_filters('webgsm_filter_shop_sidebar_ids', $ids);
    }

    /** Returnează slug-urile atributelor globale WooCommerce (fără prefixul pa_). */
    private function get_existing_global_attribute_slugs() {
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return [];
        }
        $rows = wc_get_attribute_taxonomies();
        if (empty($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!empty($row->attribute_name)) {
                $out[] = sanitize_title($row->attribute_name);
            }
        }
        return array_values(array_unique($out));
    }

    /** Returnează map slug => label pentru atribute globale WooCommerce. */
    private function get_existing_global_attribute_map() {
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return [];
        }
        $rows = wc_get_attribute_taxonomies();
        if (empty($rows)) {
            return [];
        }
        $map = [];
        foreach ($rows as $row) {
            if (empty($row->attribute_name)) {
                continue;
            }
            $slug = sanitize_title($row->attribute_name);
            $label = isset($row->attribute_label) ? sanitize_text_field($row->attribute_label) : $slug;
            $map[$slug] = $label;
        }
        return $map;
    }

    /**
     * Mapează un filtru logic (calitate, brand-piesa etc.) la slug-ul real de atribut existent în site.
     * Ajută când în magazin slug-ul este diferit (ex: calitate-baterie, brand_piesa etc.).
     */
    private function resolve_real_attribute_slug($logical_slug, array $existing_slugs) {
        $logical_slug = sanitize_title($logical_slug);
        if (empty($existing_slugs)) {
            return $logical_slug;
        }
        if (in_array($logical_slug, $existing_slugs, true)) {
            return $logical_slug;
        }

        $candidates = [
            'model-compatibil' => ['model-compatibil', 'model_compatibil', 'compatibilitate', 'compatibil'],
            'model' => ['model'],
            'calitate' => ['calitate', 'calitate-baterie', 'calitate_baterie', 'quality'],
            'brand-piesa' => ['brand-piesa', 'brand_piesa', 'brand-piese', 'brand'],
            'tehnologie' => ['tehnologie', 'tip-tehnologie', 'tehnologie-baterie', 'tehnologie_baterie', 'tip'],
        ];

        $pool = isset($candidates[$logical_slug]) ? $candidates[$logical_slug] : [$logical_slug];
        foreach ($pool as $cand) {
            $cand = sanitize_title($cand);
            if (in_array($cand, $existing_slugs, true)) {
                return $cand;
            }
        }

        // Fallback: încearcă potrivire parțială.
        foreach ($existing_slugs as $exist) {
            if (strpos($exist, $logical_slug) !== false || strpos($logical_slug, $exist) !== false) {
                return $exist;
            }
        }

        return $logical_slug;
    }

    /** Mapează filtru logic -> slug atribut existent, folosind și label-ul atributului. */
    private function resolve_real_attribute_slug_with_labels($logical_slug, array $existing_map) {
        $existing_slugs = array_keys($existing_map);
        $resolved = $this->resolve_real_attribute_slug($logical_slug, $existing_slugs);
        if (in_array($resolved, $existing_slugs, true)) {
            return $resolved;
        }

        $logical_slug = sanitize_title($logical_slug);
        $label_needles = [
            'model-compatibil' => ['compatibil', 'model compatibil', 'compatibilitate'],
            'model' => ['model'],
            'calitate' => ['calitate', 'quality'],
            'brand-piesa' => ['brand piesa', 'brand-piesa', 'brand'],
            'tehnologie' => ['tehnologie', 'technology', 'tip'],
        ];
        $needles = isset($label_needles[$logical_slug]) ? $label_needles[$logical_slug] : [$logical_slug];
        foreach ($existing_map as $slug => $label) {
            $label_norm = strtolower(remove_accents((string) $label));
            foreach ($needles as $needle) {
                $needle_norm = strtolower(remove_accents((string) $needle));
                if (strpos($label_norm, $needle_norm) !== false) {
                    return $slug;
                }
            }
        }

        return $resolved;
    }
    
    // ===========================================
    // STRUCTURA CATEGORIILOR - 5 TABURI (Piese are 3 nivele: Piese > Piese iPhone > Ecrane)
    // ===========================================
    private $categories = [
        'Piese' => [
            'slug' => 'piese',
            'description' => 'Piese și componente pentru telefoane',
            'children' => [
                'Piese iPhone' => [
                    'slug' => 'piese-iphone',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                        'Mufe Încărcare' => 'mufe-incarcare',
                        'Flexuri' => 'flexuri',
                        'Difuzoare' => 'difuzoare',
                        'Carcase' => 'carcase',
                    ]
                ],
                'Piese Samsung' => [
                    'slug' => 'piese-samsung',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                        'Mufe Încărcare' => 'mufe-incarcare',
                        'Flexuri' => 'flexuri',
                    ]
                ],
                'Piese Huawei' => [
                    'slug' => 'piese-huawei',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                    ]
                ],
                'Piese Xiaomi' => [
                    'slug' => 'piese-xiaomi',
                    'children' => [
                        'Ecrane' => 'ecrane',
                        'Baterii' => 'baterii',
                        'Camere' => 'camere',
                    ]
                ],
            ]
        ],
        'Unelte' => [
            'slug' => 'unelte',
            'description' => 'Unelte și echipamente pentru service',
            'children' => [
                'Șurubelnițe' => 'surubelnite',
                'Pensete' => 'pensete',
                'Stații Lipit' => 'statii-lipit',
                'Separatoare Ecrane' => 'separatoare-ecrane',
                'Microscoape' => 'microscoape',
                'Programatoare' => 'programatoare',
                'Kituri Complete' => 'kituri-complete',
            ]
        ],
        'Accesorii' => [
            'slug' => 'accesorii',
            'description' => 'Accesorii și consumabile',
            'children' => [
                'Huse & Carcase' => 'huse-carcase',
                'Folii Protecție' => 'folii-protectie',
                'Cabluri & Încărcătoare' => 'cabluri-incarcatoare',
                'Adezivi & Consumabile' => 'adezivi-consumabile',
            ]
        ],
        'Dispozitive' => [
            'slug' => 'dispozitive',
            'description' => 'Telefoane și tablete',
            'children' => [
                'Telefoane Folosite' => 'telefoane-folosite',
                'Telefoane Refurbished' => 'telefoane-refurbished',
                'Tablete' => 'tablete',
                'Smartwatch' => 'smartwatch',
            ]
        ],
        'Servicii' => [
            'slug' => 'servicii',
            'description' => 'Servicii și suport',
            'children' => [
                'Reparații' => 'reparatii',
                'Training' => 'training',
                'Buy-back' => 'buy-back',
            ]
        ],
    ];
    
    // ===========================================
    // ATRIBUTE PENTRU FILTRARE
    // ===========================================
    private $attributes = [
        'Model' => [
            'slug' => 'model',
            'terms' => [
                // iPhone
                'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus', 'iPhone 16',
                'iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 15 Plus', 'iPhone 15',
                'iPhone 14 Pro Max', 'iPhone 14 Pro', 'iPhone 14 Plus', 'iPhone 14',
                'iPhone 13 Pro Max', 'iPhone 13 Pro', 'iPhone 13', 'iPhone 13 Mini',
                'iPhone 12 Pro Max', 'iPhone 12 Pro', 'iPhone 12', 'iPhone 12 Mini',
                'iPhone 11 Pro Max', 'iPhone 11 Pro', 'iPhone 11',
                'iPhone XS Max', 'iPhone XS', 'iPhone XR', 'iPhone X',
                'iPhone SE 2022', 'iPhone SE 2020',
                'iPhone 8 Plus', 'iPhone 8', 'iPhone 7 Plus', 'iPhone 7',
                // Samsung Galaxy S
                'Galaxy S24 Ultra', 'Galaxy S24+', 'Galaxy S24',
                'Galaxy S23 Ultra', 'Galaxy S23+', 'Galaxy S23', 'Galaxy S23 FE',
                'Galaxy S22 Ultra', 'Galaxy S22+', 'Galaxy S22',
                'Galaxy S21 Ultra', 'Galaxy S21+', 'Galaxy S21', 'Galaxy S21 FE',
                'Galaxy S20 Ultra', 'Galaxy S20+', 'Galaxy S20', 'Galaxy S20 FE',
                // Samsung Galaxy A
                'Galaxy A55', 'Galaxy A54', 'Galaxy A53', 'Galaxy A52', 'Galaxy A52s',
                'Galaxy A35', 'Galaxy A34', 'Galaxy A33',
                'Galaxy A25', 'Galaxy A24', 'Galaxy A23',
                'Galaxy A15', 'Galaxy A14', 'Galaxy A13',
                // Samsung Galaxy Z
                'Galaxy Z Fold 6', 'Galaxy Z Fold 5', 'Galaxy Z Fold 4', 'Galaxy Z Fold 3',
                'Galaxy Z Flip 6', 'Galaxy Z Flip 5', 'Galaxy Z Flip 4', 'Galaxy Z Flip 3',
                // Samsung Note
                'Galaxy Note 20 Ultra', 'Galaxy Note 20', 'Galaxy Note 10+', 'Galaxy Note 10',
                // Huawei
                'Huawei P60 Pro', 'Huawei P50 Pro', 'Huawei P40 Pro', 'Huawei P30 Pro', 'Huawei P30',
                'Huawei Mate 50 Pro', 'Huawei Mate 40 Pro', 'Huawei Mate 30 Pro',
                // Xiaomi
                'Xiaomi 14 Ultra', 'Xiaomi 14 Pro', 'Xiaomi 14',
                'Xiaomi 13 Ultra', 'Xiaomi 13 Pro', 'Xiaomi 13',
                'Redmi Note 13 Pro+', 'Redmi Note 13 Pro', 'Redmi Note 13',
                'Redmi Note 12 Pro+', 'Redmi Note 12 Pro', 'Redmi Note 12',
                'Poco X6 Pro', 'Poco X5 Pro', 'Poco F5',
                // OnePlus
                'OnePlus 12', 'OnePlus 11', 'OnePlus 10 Pro', 'OnePlus 9 Pro',
                // Google Pixel
                'Pixel 8 Pro', 'Pixel 8', 'Pixel 7 Pro', 'Pixel 7', 'Pixel 6 Pro', 'Pixel 6',
            ]
        ],
        
        /* Model Compatibil = compatibilitate (CSV: "Attribute 1 name" = "Model Compatibil" → slug pa_model-compatibil) */
        'Model Compatibil' => [
            'slug' => 'model-compatibil',
            'terms' => [
                'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus', 'iPhone 16',
                'iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 15 Plus', 'iPhone 15',
                'iPhone 14 Pro Max', 'iPhone 14 Pro', 'iPhone 14 Plus', 'iPhone 14',
                'iPhone 13 Pro Max', 'iPhone 13 Pro', 'iPhone 13', 'iPhone 13 Mini',
                'iPhone 12 Pro Max', 'iPhone 12 Pro', 'iPhone 12', 'iPhone 12 Mini',
                'iPhone 11 Pro Max', 'iPhone 11 Pro', 'iPhone 11',
                'iPhone XS Max', 'iPhone XS', 'iPhone XR', 'iPhone X',
                'iPhone SE 2022', 'iPhone SE 2020', 'iPhone 8 Plus', 'iPhone 8', 'iPhone 7 Plus', 'iPhone 7',
                'Galaxy S24 Ultra', 'Galaxy S24+', 'Galaxy S24', 'Galaxy S23 Ultra', 'Galaxy S23+', 'Galaxy S23',
                'Galaxy S22 Ultra', 'Galaxy S22+', 'Galaxy S22', 'Galaxy S21 Ultra', 'Galaxy S21+', 'Galaxy S21',
                'Galaxy A55', 'Galaxy A54', 'Galaxy A53', 'Galaxy A52', 'Galaxy A35', 'Galaxy A34',
                'Galaxy Z Fold 6', 'Galaxy Z Fold 5', 'Galaxy Z Flip 6', 'Galaxy Z Flip 5',
                'Redmi Note 12 Pro+', 'Redmi Note 12 Pro', 'Redmi Note 12', 'Xiaomi 14', 'Xiaomi 13',
                'Pixel 8 Pro', 'Pixel 8', 'Pixel 7 Pro', 'Pixel 7',
            ]
        ],
        
        'Calitate' => [
            'slug' => 'calitate',
            'terms' => [
                'Original Service Pack',
                'Premium OEM', 
                'OEM',
                'Aftermarket HQ',
                'Aftermarket',
                'Refurbished',
                'Pull (Dezmembrare)',
            ]
        ],
        
        'Brand Piesă' => [
            'slug' => 'brand-piesa',
            'terms' => [
                // Ecrane
                'JK Incell',
                'GX Hard OLED', 
                'GX Soft OLED',
                'ZY Incell',
                'RJ Incell',
                'Ampsentrix',
                'Youda',
                'HQ',
                'PCC',
                'Original Apple',
                'Original Samsung',
                // Baterii
                'Desay',
                'ATL',
                'Sunwoda',
            ]
        ],
        
        'Tehnologie Display' => [
            'slug' => 'tehnologie',
            'terms' => [
                'Soft OLED',
                'Hard OLED',
                'AMOLED',
                'Super AMOLED',
                'Incell LCD',
                'In-Cell',
                'LCD IPS',
                'LCD TFT',
                'Retina',
            ]
        ],
        
        'Brand Telefon' => [
            'slug' => 'brand-telefon',
            'terms' => [
                'Apple',
                'Samsung', 
                'Huawei',
                'Xiaomi',
                'OnePlus',
                'Google',
                'Oppo',
                'Motorola',
                'Sony',
                'LG',
                'Nokia',
            ]
        ],
        
        'Culoare' => [
            'slug' => 'culoare',
            'terms' => [
                'Negru',
                'Alb',
                'Auriu',
                'Argintiu',
                'Albastru',
                'Roșu',
                'Verde',
                'Mov',
                'Roz',
                'Gri',
            ]
        ],
    ];
    
    // ===========================================
    // STRUCTURĂ MENIU (cum apare în megamenu)
    // ===========================================
    private $menu_structure = [
        'Piese' => [
            'icon' => '🔧',
            'columns' => [
                'iPhone' => ['Ecrane', 'Baterii', 'Camere', 'Mufe Încărcare', 'Flexuri', 'Difuzoare', 'Carcase'],
                'Samsung' => ['Ecrane', 'Baterii', 'Camere', 'Mufe Încărcare', 'Flexuri'],
                'Huawei' => ['Ecrane', 'Baterii', 'Camere'],
                'Xiaomi' => ['Ecrane', 'Baterii', 'Camere'],
            ]
        ],
        'Unelte' => [
            'icon' => '🛠️',
            'columns' => [
                'Unelte' => ['Șurubelnițe', 'Pensete', 'Stații Lipit', 'Separatoare Ecrane', 'Microscoape', 'Programatoare', 'Kituri Complete'],
            ]
        ],
        'Accesorii' => [
            'icon' => '📦',
            'columns' => [
                'Accesorii' => ['Huse & Carcase', 'Folii Protecție', 'Cabluri & Încărcătoare', 'Adezivi & Consumabile'],
            ]
        ],
        'Dispozitive' => [
            'icon' => '📱',
            'simple' => true,
        ],
        'Servicii' => [
            'icon' => '⚡',
            'simple' => true,
        ],
    ];
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
        add_action('wp_enqueue_scripts', [$this, 'shop_sidebar_filter_styles'], 20);
        add_action('widgets_init', [$this, 'register_piese_filter_widget']);
        add_action('woocommerce_product_query', [$this, 'apply_piese_filter_query'], 20);
        add_filter('woocommerce_product_query_tax_query', [$this, 'apply_piese_filter_tax_query'], 20, 2);
        add_action('wp_footer', [$this, 'render_filters_debug_console'], 999);
        
        // Adaugă automat widget-ul generic în sidebar la activarea plugin-ului
        register_activation_hook(__FILE__, [$this, 'activate_category_filter_widget']);
        
        // Verifică și adaugă widget-ul generic la fiecare accesare admin (doar o dată)
        if (is_admin()) {
            add_action('admin_init', [$this, 'ensure_category_filter_widget_admin'], 5);
        }
        
        // AJAX handlers
        add_action('wp_ajax_webgsm_v2_create_categories', [$this, 'ajax_create_categories']);
        add_action('wp_ajax_webgsm_v2_save_brand_piesa_extra', [$this, 'ajax_save_brand_piesa_extra']);
        add_action('wp_ajax_webgsm_v2_create_attributes', [$this, 'ajax_create_attributes']);
        add_action('wp_ajax_webgsm_v2_create_menu', [$this, 'ajax_create_menu']);
        add_action('wp_ajax_webgsm_v2_setup_filters', [$this, 'ajax_setup_filters']);
        add_action('wp_ajax_webgsm_v2_clear_filters', [$this, 'ajax_clear_filters']);
        add_action('wp_ajax_webgsm_v2_clear_menu', [$this, 'ajax_clear_menu']);
        add_action('wp_ajax_webgsm_v2_reset', [$this, 'ajax_reset']);
        add_action('wp_ajax_webgsm_v2_cleanup', [$this, 'ajax_cleanup']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Upload Tools',
            'Upload Tools',
            'manage_options',
            'webgsm-setup-v2',
            [$this, 'render_admin_page'],
            'dashicons-upload',
            29
        );
        add_submenu_page(
            'webgsm-setup-v2',
            'Setup Wizard',
            'Setup Wizard',
            'manage_options',
            'webgsm-setup-v2',
            [$this, 'render_admin_page']
        );
    }
    
    /** Pe shop/categorii: sidebar-ul de filtre devine scrollabil când sunt multe filtre. */
    public function shop_sidebar_filter_styles() {
        if (!function_exists('is_shop') || (!is_shop() && !is_product_category() && !is_product_taxonomy())) {
            return;
        }
        $css = '
            .catalog-sidebar,
            .shop-sidebar,
            .sidebar-shop,
            .woocommerce-sidebar {
                max-height: 85vh;
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }
            .catalog-sidebar .widget_woocommerce_layered_nav ul,
            .shop-sidebar .widget_woocommerce_layered_nav ul,
            .sidebar-shop .widget_woocommerce_layered_nav ul,
            .woocommerce-sidebar .widget_woocommerce_layered_nav ul,
            .catalog-sidebar .webgsm-piese-filter .webgsm-filter-list,
            .shop-sidebar .webgsm-piese-filter .webgsm-filter-list,
            .sidebar-shop .webgsm-piese-filter .webgsm-filter-list,
            .woocommerce-sidebar .webgsm-piese-filter .webgsm-filter-list,
            .catalog-sidebar .webgsm-category-filter .webgsm-filter-list,
            .shop-sidebar .webgsm-category-filter .webgsm-filter-list,
            .sidebar-shop .webgsm-category-filter .webgsm-filter-list,
            .woocommerce-sidebar .webgsm-category-filter .webgsm-filter-list {
                max-height: 220px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            /* Container cu scroll fix pentru multe filtre */
            .webgsm-filter-scroll-container {
                max-height: 300px;
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #ccc #f5f5f5;
            }
            .webgsm-filter-scroll-container::-webkit-scrollbar {
                width: 6px;
            }
            .webgsm-filter-scroll-container::-webkit-scrollbar-track {
                background: #f5f5f5;
                border-radius: 3px;
            }
            .webgsm-filter-scroll-container::-webkit-scrollbar-thumb {
                background: #ccc;
                border-radius: 3px;
            }
            .webgsm-filter-scroll-container::-webkit-scrollbar-thumb:hover {
                background: #999;
            }
            /* Stiluri unificate, compacte si profesionale pentru filtre */
            .catalog-sidebar .widget,
            .shop-sidebar .widget,
            .sidebar-shop .widget,
            .woocommerce-sidebar .widget {
                margin-bottom: 14px;
                padding: 12px 12px 10px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #fff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }
            .webgsm-category-filter,
            .webgsm-piese-filter,
            .widget_woocommerce_layered_nav {
                font-size: 13px;
            }
            .webgsm-category-filter .webgsm-filter-group,
            .webgsm-piese-filter .webgsm-filter-group {
                margin-bottom: 12px;
            }
            .webgsm-category-filter .webgsm-filter-group:last-child,
            .webgsm-piese-filter .webgsm-filter-group:last-child {
                margin-bottom: 0;
            }
            .webgsm-category-filter .webgsm-filter-label,
            .webgsm-piese-filter .webgsm-filter-label,
            .widget_woocommerce_layered_nav .widget-title {
                font-weight: 700;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                margin-bottom: 8px;
                color: #1f2937;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list,
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list__item,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item,
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list__item + .woocommerce-widget-layered-nav-list__item,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item + .woocommerce-widget-layered-nav-list__item,
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item + .woocommerce-widget-layered-nav-list__item {
                border-top: 1px solid #f3f4f6;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list__item a,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item a,
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item a {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 2px;
                text-decoration: none;
                color: #374151;
                font-size: 13px;
                line-height: 1.35;
                transition: color 0.2s ease;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list__item a:hover,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item a:hover,
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item a:hover {
                color: #2563eb;
            }
            .webgsm-category-filter .webgsm-filter-cb,
            .webgsm-piese-filter .webgsm-filter-cb {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                width: 15px;
                height: 15px;
                min-width: 15px;
                min-height: 15px;
                background: #fff;
                border: 1px solid #cbd5e1;
                border-radius: 3px;
                box-sizing: border-box;
                line-height: 1;
            }
            .webgsm-category-filter .webgsm-filter-cb.chosen::after,
            .webgsm-piese-filter .webgsm-filter-cb.chosen::after {
                content: "\2713";
                display: block;
                color: #1f2937;
                font-size: 10px;
                font-weight: 700;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list__item--chosen .webgsm-filter-cb,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item--chosen .webgsm-filter-cb {
                border-color: #2563eb;
                background: #eff6ff;
            }
            .webgsm-category-filter .webgsm-filter-label-text,
            .webgsm-piese-filter .webgsm-filter-label-text {
                flex: 1;
            }
            .webgsm-category-filter .woocommerce-widget-layered-nav-list__item a::before,
            .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item a::before {
                content: none !important;
            }
            /* Woo layered nav (atribute) - aceeasi dimensiune cu filtrele custom */
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item a::before {
                margin-right: 8px !important;
                width: 15px !important;
                height: 15px !important;
                border-radius: 3px !important;
                border-color: #cbd5e1 !important;
            }
            .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item--chosen a::before {
                border-color: #2563eb !important;
                background: #eff6ff !important;
            }
            .widget_price_filter .price_slider_wrapper {
                margin-top: 6px;
            }
            .widget_price_filter .price_slider_amount .button {
                height: 36px;
                min-height: 36px;
                border-radius: 20px;
                padding: 0 14px;
                font-size: 12px;
                line-height: 36px;
            }
            @media (max-width: 992px) {
                .catalog-sidebar .widget,
                .shop-sidebar .widget,
                .sidebar-shop .widget,
                .woocommerce-sidebar .widget {
                    padding: 10px;
                    border-radius: 8px;
                }
                .webgsm-filter-scroll-container {
                    max-height: 42vh;
                }
                .webgsm-category-filter .woocommerce-widget-layered-nav-list__item a,
                .webgsm-piese-filter .woocommerce-widget-layered-nav-list__item a,
                .widget_woocommerce_layered_nav .woocommerce-widget-layered-nav-list__item a {
                    padding: 10px 2px;
                    font-size: 13px;
                }
            }
        ';
        wp_register_style('webgsm-wizard-sidebar', false);
        wp_enqueue_style('webgsm-wizard-sidebar');
        wp_add_inline_style('webgsm-wizard-sidebar', $css);
    }
    
    public function register_piese_filter_widget() {
        register_widget('WebGSM_Widget_Piese_Filter');
        register_widget('WebGSM_Widget_Category_Filter'); // Widget generic dinamic
        
        // Adaugă automat widget-ul generic în sidebar dacă nu există deja
        $this->ensure_category_filter_widget();
    }
    
    /**
     * Asigură că widget-ul generic pentru categorii este în sidebar-ul folosit pe shop/categorii.
     * Dacă widget-ul e doar în „blog sidebar”, îl copiază și în sidebar-ul de shop.
     */
    private function ensure_category_filter_widget() {
        if (get_option('webgsm_category_filter_widget_checked')) {
            return;
        }
        $sidebars = get_option('sidebars_widgets', []);
        $shop_sidebar_ids = [
            'catalog-sidebar', 'shop-sidebar', 'sidebar-shop', 'woocommerce-sidebar',
            'mf-catalog-sidebar', 'martfury-sidebar-shop', 'sidebar-1', 'primary-sidebar',
            'content-sidebar', 'blog-sidebar',
        ];
        $shop_sidebar_ids = apply_filters('webgsm_filter_shop_sidebar_ids', $shop_sidebar_ids);
        $shop_sidebar = null;
        foreach ($shop_sidebar_ids as $s) {
            if (isset($sidebars[$s]) && $s !== 'wp_inactive_widgets') {
                $shop_sidebar = $s;
                break;
            }
        }
        if (!$shop_sidebar) {
            update_option('webgsm_category_filter_widget_checked', true);
            return;
        }
        $has_category_filter = false;
        if (isset($sidebars[$shop_sidebar]) && is_array($sidebars[$shop_sidebar])) {
            foreach ($sidebars[$shop_sidebar] as $widget_id) {
                if (strpos($widget_id, 'webgsm_category_filter-') === 0) {
                    $has_category_filter = true;
                    break;
                }
            }
        }
        if (!$has_category_filter) {
            $widget_in_other = null;
            foreach ($sidebars as $sid => $widgets) {
                if ($sid === 'wp_inactive_widgets' || !is_array($widgets)) continue;
                foreach ($widgets as $widget_id) {
                    if (strpos($widget_id, 'webgsm_category_filter-') === 0) {
                        $widget_in_other = $widget_id;
                        break 2;
                    }
                }
            }
            $category_filter_widget = get_option('widget_webgsm_category_filter', []);
            $category_filter_id = 1;
            while (isset($category_filter_widget[$category_filter_id])) {
                $category_filter_id++;
            }
            if ($widget_in_other) {
                $existing_id = (int) str_replace('webgsm_category_filter-', '', $widget_in_other);
                $category_filter_widget[$category_filter_id] = isset($category_filter_widget[$existing_id])
                    ? $category_filter_widget[$existing_id]
                    : ['title' => ''];
            } else {
                $category_filter_widget[$category_filter_id] = ['title' => ''];
            }
            update_option('widget_webgsm_category_filter', $category_filter_widget);
            if (!isset($sidebars[$shop_sidebar])) {
                $sidebars[$shop_sidebar] = [];
            }
            array_unshift($sidebars[$shop_sidebar], 'webgsm_category_filter-' . $category_filter_id);
            update_option('sidebars_widgets', $sidebars);
        }
        update_option('webgsm_category_filter_widget_checked', true);
    }
    
    /**
     * Adaugă widget-ul generic în sidebar la activarea plugin-ului.
     * Resetează și flag-ul de verificare ca la următoarea încărcare să poată copia widget-ul din blog în sidebar-ul de shop.
     */
    public function activate_category_filter_widget() {
        delete_option('webgsm_category_filter_widget_checked');
        $this->ensure_category_filter_widget();
    }
    
    /**
     * Verifică și adaugă widget-ul generic în admin (doar o dată)
     */
    public function ensure_category_filter_widget_admin() {
        if (!get_option('webgsm_category_filter_widget_checked')) {
            $this->ensure_category_filter_widget();
        }
    }
    
    /**
     * Aplică filtrarea după Subcategorie Piese și Tip piesă (query params filter_piese_subcat, filter_piese_tip).
     * Combină: Piese iPhone + Ecrane → doar categorii ecrane-iphone; Piese Samsung + Baterii → baterii-samsung etc.
     * Suportă și filtrarea pentru Unelte și Accesorii.
     */
    public function apply_piese_filter_query($q) {
        $tax_query = $q->get('tax_query') ?: [];
        $cat_term_ids = [];
        
        // ============================================
        // FILTRARE PIESE
        // ============================================
        $subcat_param = isset($_GET['filter_piese_subcat']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_subcat'])) : '';
        $tip_param = isset($_GET['filter_piese_tip']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_tip'])) : '';
        
        if ($subcat_param !== '' || $tip_param !== '') {
            $subcat_slugs = $subcat_param ? array_map('trim', explode(',', $subcat_param)) : [];
            $tip_slugs = $tip_param ? array_map('trim', explode(',', $tip_param)) : [];
            $subcat_slugs = WebGSM_Widget_Piese_Filter::normalize_existing_product_cat_slugs($subcat_slugs);
            $tip_slugs = WebGSM_Widget_Piese_Filter::normalize_tip_slugs($tip_slugs);

            if (!empty($subcat_slugs) && !empty($tip_slugs)) {
                $cat_term_ids = array_merge(
                    $cat_term_ids,
                    WebGSM_Widget_Piese_Filter::resolve_category_term_ids($subcat_slugs, $tip_slugs)
                );
            } elseif (!empty($subcat_slugs)) {
                foreach ($subcat_slugs as $slug) {
                    $t = get_term_by('slug', $slug, 'product_cat');
                    if ($t && !is_wp_error($t)) {
                        $cat_term_ids[] = $t->term_id;
                    }
                }
            } elseif (!empty($tip_slugs)) {
                $all_subcats = WebGSM_Widget_Piese_Filter::get_subcat_slugs_from_wc();
                $cat_term_ids = array_merge(
                    $cat_term_ids,
                    WebGSM_Widget_Piese_Filter::resolve_category_term_ids($all_subcats, $tip_slugs)
                );
            }
        }
        
        // ============================================
        // FILTRARE UNELTE
        // ============================================
        $unelte_param = isset($_GET['filter_unelte']) ? sanitize_text_field(wp_unslash($_GET['filter_unelte'])) : '';
        if ($unelte_param !== '') {
            $unelte_slugs = array_map('trim', explode(',', $unelte_param));
            foreach ($unelte_slugs as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    // Verifică dacă este subcategorie a "unelte"
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'unelte') {
                        $cat_term_ids[] = $t->term_id;
                    }
                }
            }
        }
        
        // ============================================
        // FILTRARE ACCESORII
        // ============================================
        $accesorii_param = isset($_GET['filter_accesorii']) ? sanitize_text_field(wp_unslash($_GET['filter_accesorii'])) : '';
        if ($accesorii_param !== '') {
            $accesorii_slugs = array_map('trim', explode(',', $accesorii_param));
            foreach ($accesorii_slugs as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    // Verifică dacă este subcategorie a "accesorii"
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'accesorii') {
                        $cat_term_ids[] = $t->term_id;
                    }
                }
            }
        }
        
        // ============================================
        // FILTRARE DISPOZITIVE
        // ============================================
        $dispozitive_param = isset($_GET['filter_dispozitive']) ? sanitize_text_field(wp_unslash($_GET['filter_dispozitive'])) : '';
        if ($dispozitive_param !== '') {
            $dispozitive_slugs = array_map('trim', explode(',', $dispozitive_param));
            foreach ($dispozitive_slugs as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    // Verifică dacă este subcategorie a "dispozitive"
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'dispozitive') {
                        $cat_term_ids[] = $t->term_id;
                    }
                }
            }
        }
        
        // ============================================
        // FILTRARE SERVICII
        // ============================================
        $servicii_param = isset($_GET['filter_servicii']) ? sanitize_text_field(wp_unslash($_GET['filter_servicii'])) : '';
        if ($servicii_param !== '') {
            $servicii_slugs = array_map('trim', explode(',', $servicii_param));
            foreach ($servicii_slugs as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    // Verifică dacă este subcategorie a "servicii"
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'servicii') {
                        $cat_term_ids[] = $t->term_id;
                    }
                }
            }
        }

        if (empty($cat_term_ids)) {
            $this->filter_debug_data['apply_piese_filter_query'] = [
                'status' => 'no_term_ids',
                'get' => [
                    'filter_piese_subcat' => isset($_GET['filter_piese_subcat']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_subcat'])) : '',
                    'filter_piese_tip' => isset($_GET['filter_piese_tip']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_tip'])) : '',
                ],
            ];
            return;
        }

        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $cat_term_ids,
            'operator' => 'IN',
            'include_children' => empty($tip_param), // Include children doar dacă nu filtrăm după Tip piesă
        ];
        $q->set('tax_query', $tax_query);
        $this->filter_debug_data['apply_piese_filter_query'] = [
            'status' => 'ok',
            'term_ids' => array_values(array_unique($cat_term_ids)),
            'tip_param' => $tip_param,
            'tax_query' => $tax_query,
        ];
    }

    /**
     * Aplică aceleași filtre prin woocommerce_product_query_tax_query, ca să nu fie suprascrise de WooCommerce.
     * WooCommerce construiește tax_query din acest filtru; fără el, setarea pe $q poate fi ignorată.
     */
    public function apply_piese_filter_tax_query($tax_query, $wc_query) {
        $tax_query = is_array($tax_query) ? $tax_query : [];
        if (!isset($tax_query['relation'])) {
            $tax_query['relation'] = 'AND';
        }
        $cat_term_ids = $this->get_webgsm_filter_term_ids();
        if (empty($cat_term_ids['term_ids'])) {
            $this->filter_debug_data['apply_piese_filter_tax_query'] = [
                'status' => 'no_term_ids',
                'tax_query_before' => $tax_query,
            ];
            return $tax_query;
        }
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $cat_term_ids['term_ids'],
            'operator' => 'IN',
            'include_children' => $cat_term_ids['include_children'],
        ];
        $this->filter_debug_data['apply_piese_filter_tax_query'] = [
            'status' => 'ok',
            'term_ids' => $cat_term_ids['term_ids'],
            'include_children' => $cat_term_ids['include_children'],
            'tax_query_after' => $tax_query,
        ];
        return $tax_query;
    }

    /**
     * Returnează term_ids și include_children pentru filtrele WebGSM (GET params).
     */
    private function get_webgsm_filter_term_ids() {
        $cat_term_ids = [];
        $tip_param = isset($_GET['filter_piese_tip']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_tip'])) : '';

        // PIESE
        $subcat_param = isset($_GET['filter_piese_subcat']) ? sanitize_text_field(wp_unslash($_GET['filter_piese_subcat'])) : '';
        if ($subcat_param !== '' || $tip_param !== '') {
            $subcat_slugs = $subcat_param ? array_map('trim', explode(',', $subcat_param)) : [];
            $tip_slugs = $tip_param ? array_map('trim', explode(',', $tip_param)) : [];
            $subcat_slugs = WebGSM_Widget_Piese_Filter::normalize_existing_product_cat_slugs($subcat_slugs);
            $tip_slugs = WebGSM_Widget_Piese_Filter::normalize_tip_slugs($tip_slugs);
            if (!empty($subcat_slugs) && !empty($tip_slugs)) {
                $cat_term_ids = array_merge(
                    $cat_term_ids,
                    WebGSM_Widget_Piese_Filter::resolve_category_term_ids($subcat_slugs, $tip_slugs)
                );
            } elseif (!empty($subcat_slugs)) {
                foreach ($subcat_slugs as $slug) {
                    $t = get_term_by('slug', $slug, 'product_cat');
                    if ($t && !is_wp_error($t)) $cat_term_ids[] = $t->term_id;
                }
            } elseif (!empty($tip_slugs)) {
                $all_subcats = WebGSM_Widget_Piese_Filter::get_subcat_slugs_from_wc();
                $cat_term_ids = array_merge(
                    $cat_term_ids,
                    WebGSM_Widget_Piese_Filter::resolve_category_term_ids($all_subcats, $tip_slugs)
                );
            }
        }
        // UNELTE
        $unelte_param = isset($_GET['filter_unelte']) ? sanitize_text_field(wp_unslash($_GET['filter_unelte'])) : '';
        if ($unelte_param !== '') {
            foreach (array_map('trim', explode(',', $unelte_param)) as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'unelte') $cat_term_ids[] = $t->term_id;
                }
            }
        }
        // ACCESORII
        $accesorii_param = isset($_GET['filter_accesorii']) ? sanitize_text_field(wp_unslash($_GET['filter_accesorii'])) : '';
        if ($accesorii_param !== '') {
            foreach (array_map('trim', explode(',', $accesorii_param)) as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'accesorii') $cat_term_ids[] = $t->term_id;
                }
            }
        }
        // DISPOZITIVE
        $dispozitive_param = isset($_GET['filter_dispozitive']) ? sanitize_text_field(wp_unslash($_GET['filter_dispozitive'])) : '';
        if ($dispozitive_param !== '') {
            foreach (array_map('trim', explode(',', $dispozitive_param)) as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'dispozitive') $cat_term_ids[] = $t->term_id;
                }
            }
        }
        // SERVICII
        $servicii_param = isset($_GET['filter_servicii']) ? sanitize_text_field(wp_unslash($_GET['filter_servicii'])) : '';
        if ($servicii_param !== '') {
            foreach (array_map('trim', explode(',', $servicii_param)) as $slug) {
                $t = get_term_by('slug', $slug, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    $parent = get_term($t->parent, 'product_cat');
                    if ($parent && !is_wp_error($parent) && $parent->slug === 'servicii') $cat_term_ids[] = $t->term_id;
                }
            }
        }

        $result = [
            'term_ids' => array_values(array_unique($cat_term_ids)),
            'include_children' => empty($tip_param),
        ];
        $this->filter_debug_data['get_webgsm_filter_term_ids'] = [
            'result' => $result,
            'get' => [
                'filter_piese_subcat' => $subcat_param,
                'filter_piese_tip' => $tip_param,
                'filter_unelte' => $unelte_param,
                'filter_accesorii' => $accesorii_param,
                'filter_dispozitive' => $dispozitive_param,
                'filter_servicii' => $servicii_param,
            ],
        ];
        return $result;
    }

    /** Debug runtime în consola browserului: adaugă ?webgsm_debug_filters=1 la URL. */
    public function render_filters_debug_console() {
        $has_filter_params = isset($_GET['filter_piese_subcat']) || isset($_GET['filter_piese_tip']) || isset($_GET['filter_unelte']) || isset($_GET['filter_accesorii']) || isset($_GET['filter_dispozitive']) || isset($_GET['filter_servicii']);
        if (!isset($_GET['webgsm_debug_filters']) && !$has_filter_params) {
            return;
        }
        if (!function_exists('is_shop') || (!is_shop() && !is_product_category() && !is_product_taxonomy())) {
            return;
        }
        $payload = [
            'url' => (is_ssl() ? 'https://' : 'http://') . sanitize_text_field($_SERVER['HTTP_HOST'] ?? '') . sanitize_text_field($_SERVER['REQUEST_URI'] ?? ''),
            'debug' => $this->filter_debug_data,
            'sidebar_diagnostics' => $this->get_sidebar_filter_diagnostics(),
        ];
        ?>
        <script>
        (function() {
            try {
                window.webgsmFilterDebugPayload = <?php echo wp_json_encode($payload); ?>;
                if (window.location.search.indexOf('webgsm_b2b_debug=1') !== -1 && window.console) {
                    console.group('WebGSM Filter Debug');
                    console.log(window.webgsmFilterDebugPayload);
                    console.log('WebGSM Filter Debug JSON:', JSON.stringify(window.webgsmFilterDebugPayload, null, 2));
                    console.groupEnd();
                }
            } catch (e) {}
        })();
        </script>
        <!-- WebGSM Filter Debug: <?php echo esc_html(wp_json_encode($payload)); ?> -->
        <?php
    }

    /** Diagnostic pentru widget-urile de filtre în sidebars de shop. */
    private function get_sidebar_filter_diagnostics() {
        $sidebars = get_option('sidebars_widgets', []);
        $layered_opts = get_option('widget_woocommerce_layered_nav', []);
        $price_opts = get_option('widget_woocommerce_price_filter', []);
        $result = [
            'selected_filter_attrs_option' => get_option('webgsm_v2_filter_attributes', []),
            'shop_sidebar_candidates' => $this->get_shop_sidebar_candidates(),
            'sidebars' => [],
        ];

        foreach ($this->get_shop_sidebar_candidates() as $sid) {
            if (!isset($sidebars[$sid]) || !is_array($sidebars[$sid])) {
                continue;
            }

            $widgets_info = [];
            foreach ($sidebars[$sid] as $wid) {
                if (strpos($wid, 'woocommerce_layered_nav-') === 0) {
                    $num = (int) str_replace('woocommerce_layered_nav-', '', $wid);
                    $cfg = isset($layered_opts[$num]) && is_array($layered_opts[$num]) ? $layered_opts[$num] : [];
                    $attr_slug = isset($cfg['attribute']) ? sanitize_title($cfg['attribute']) : '';
                    $tax = $attr_slug ? wc_attribute_taxonomy_name($attr_slug) : '';
                    $tax_exists = $tax ? taxonomy_exists($tax) : false;
                    $terms_count = null;
                    if ($tax_exists) {
                        $terms = get_terms([
                            'taxonomy' => $tax,
                            'hide_empty' => false,
                            'fields' => 'ids',
                        ]);
                        $terms_count = is_wp_error($terms) ? 'wp_error' : count($terms);
                    }
                    $widgets_info[] = [
                        'id' => $wid,
                        'type' => 'layered_nav',
                        'attribute' => $attr_slug,
                        'taxonomy' => $tax,
                        'taxonomy_exists' => $tax_exists,
                        'terms_count' => $terms_count,
                        'title' => $cfg['title'] ?? '',
                    ];
                } elseif (strpos($wid, 'woocommerce_price_filter-') === 0) {
                    $num = (int) str_replace('woocommerce_price_filter-', '', $wid);
                    $cfg = isset($price_opts[$num]) && is_array($price_opts[$num]) ? $price_opts[$num] : [];
                    $widgets_info[] = [
                        'id' => $wid,
                        'type' => 'price',
                        'title' => $cfg['title'] ?? '',
                    ];
                } elseif (strpos($wid, 'webgsm_category_filter-') === 0 || strpos($wid, 'webgsm_piese_filter-') === 0) {
                    $widgets_info[] = [
                        'id' => $wid,
                        'type' => 'webgsm_filter',
                    ];
                }
            }

            $result['sidebars'][$sid] = $widgets_info;
        }

        return $result;
    }
    
    public function admin_styles($hook) {
        if ($hook !== 'toplevel_page_webgsm-setup-v2') return;
        
        wp_enqueue_script('jquery');
        ?>
        <style>
            .webgsm-wrap { max-width: 1000px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .webgsm-header { background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
            .webgsm-header h1 { margin: 0 0 8px 0; font-size: 28px; color: white; }
            .webgsm-header p { margin: 0; opacity: 0.9; }
            
            .webgsm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            @media (max-width: 900px) { .webgsm-grid { grid-template-columns: 1fr; } }
            
            .webgsm-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
            .webgsm-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
            .webgsm-card-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
            .webgsm-card-icon.blue { background: #dbeafe; }
            .webgsm-card-icon.green { background: #d1fae5; }
            .webgsm-card-icon.purple { background: #ede9fe; }
            .webgsm-card-icon.orange { background: #ffedd5; }
            .webgsm-card-icon.red { background: #fee2e2; }
            
            .webgsm-card h3 { margin: 0; font-size: 16px; color: #1f2937; }
            .webgsm-card p { color: #6b7280; font-size: 13px; margin: 0 0 16px 0; }
            
            .webgsm-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
            .webgsm-btn-primary { background: #2563eb; color: white; }
            .webgsm-btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
            .webgsm-btn-success { background: #059669; color: white; }
            .webgsm-btn-danger { background: #dc2626; color: white; }
            .webgsm-btn-danger:hover { background: #b91c1c; }
            .webgsm-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }
            
            .webgsm-status { margin-top: 12px; padding: 10px 14px; border-radius: 8px; font-size: 13px; display: none; }
            .webgsm-status.show { display: block; }
            .webgsm-status.loading { background: #dbeafe; color: #1e40af; }
            .webgsm-status.success { background: #d1fae5; color: #065f46; }
            .webgsm-status.error { background: #fee2e2; color: #991b1b; }
            
            .webgsm-preview { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin: 12px 0; font-size: 11px; font-family: monospace; max-height: 150px; overflow-y: auto; white-space: pre; }
            
            .webgsm-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
            .webgsm-tab { padding: 10px 16px; background: #f1f5f9; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
            .webgsm-tab:hover { background: #e2e8f0; }
            .webgsm-tab.active { background: #2563eb; color: white; }
            
            .spinner { animation: spin 1s linear infinite; display: inline-block; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            
            .webgsm-full { grid-column: 1 / -1; }
            .webgsm-checklist { list-style: none; padding: 0; margin: 0; }
            .webgsm-checklist li { padding: 8px 0; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
            .webgsm-checklist li:last-child { border-bottom: none; }
            .check-icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; }
            .check-icon.done { background: #d1fae5; color: #059669; }
            .check-icon.pending { background: #f1f5f9; color: #9ca3af; }

            .webgsm-structure-viewer { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
            .webgsm-structure-viewer h2 { margin-top: 0; display: flex; justify-content: space-between; align-items: center; }
            .structure-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; }
            .category-tree, .category-tree ul { list-style: none; padding-left: 20px; }
            .category-tree > li { padding-left: 0; }
            .cat-item { display: inline-block; padding: 3px 0; }
            .cat-item code { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
            .attributes-list { list-style: none; padding: 0; }
            .attributes-list li { padding: 10px; margin: 5px 0; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
            .tags-cloud { line-height: 2; }
            .tag-item { display: inline-block; background: #0073aa; color: #fff; padding: 2px 8px; border-radius: 3px; margin: 2px; font-size: 12px; }
            .export-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; }
            #copy-status { margin-left: 10px; color: #46b450; display: none; }
        </style>
        <?php
    }
    
    public function render_admin_page() {
        $cats_done = get_option('webgsm_v2_categories', false);
        $attrs_done = get_option('webgsm_v2_attributes', false);
        $menu_done = get_option('webgsm_v2_menu', false);
        $filters_done = get_option('webgsm_v2_filters', false);
        ?>
        <div class="webgsm-wrap">
            
            <div class="webgsm-header">
                <h1>🚀 WebGSM Setup Wizard v2</h1>
                <p>Structură finală: Piese • Unelte • Accesorii • Dispozitive • Servicii</p>
            </div>
            
            <!-- Preview Tabs -->
            <div class="webgsm-tabs">
                <div class="webgsm-tab active">🔧 Piese</div>
                <div class="webgsm-tab">🛠️ Unelte</div>
                <div class="webgsm-tab">📦 Accesorii</div>
                <div class="webgsm-tab">📱 Dispozitive</div>
                <div class="webgsm-tab">⚡ Servicii</div>
            </div>
            
            <!-- Vizualizare Structură Actuală (read-only + export AI) -->
            <div class="webgsm-structure-viewer">
                <h2>📊 Structura Actuală <button type="button" class="button" id="toggle-structure">[Arată]</button></h2>
                <div id="structure-content" style="display:none;">
                    <div class="structure-section">
                        <h3>📁 Categorii Produse</h3>
                        <div id="category-tree"><?php echo webgsm_get_category_tree_html(); ?></div>
                    </div>
                    <div class="structure-section">
                        <h3>🏷️ Atribute WooCommerce</h3>
                        <div id="attributes-list"><?php echo webgsm_get_attributes_html(); ?></div>
                    </div>
                    <div class="structure-section">
                        <h3>🔖 Tag-uri Produse</h3>
                        <div id="tags-list"><?php echo webgsm_get_tags_html(); ?></div>
                    </div>
                    <div class="export-section">
                        <button type="button" id="copy-for-ai" class="button button-primary">📋 Copiază Structura pentru AI</button>
                        <button type="button" id="export-json" class="button">📥 Export JSON</button>
                        <span id="copy-status"></span>
                    </div>
                </div>
            </div>

            <div class="webgsm-grid">
                
                <!-- 1. Categorii -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon blue">📁</div>
                        <h3>1. Creare Categorii</h3>
                    </div>
                    <p>Creează 5 categorii principale + ~50 subcategorii. <strong>Prima dată: Creează. După ce ai rulat: poți rula din nou (Actualizează).</strong></p>
                    <div class="webgsm-preview">Piese/
├── Piese iPhone → Ecrane, Baterii, Camere...
├── Piese Samsung → Ecrane, Baterii, Flexuri...
├── Piese Huawei, Piese Xiaomi...
Unelte/
├── Șurubelnițe, Pensete, Stații Lipit...
Accesorii/
├── Huse & Carcase, Folii Protecție...
Dispozitive/
├── Telefoane Folosite, Tablete...
Servicii/
├── Reparații, Training, Buy-back...</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-cats">
                        <?php echo $cats_done ? '🔄 Actualizează Categorii' : '📁 Creează Categorii'; ?>
                    </button>
                    <span style="font-size: 12px; color: #64748b; display: block; margin-top: 4px;"><?php echo $cats_done ? 'Categorii există – poți rula din nou pentru actualizare.' : 'Încă nu ai rulat – apasă Creează.'; ?></span>
                    <div class="webgsm-status" id="status-cats"></div>
                </div>
                
                <!-- 2. Atribute -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon green">🏷️</div>
                        <h3>2. Creare Atribute</h3>
                    </div>
                    <p>Creează atribute pentru filtrare. <strong>Prima dată: Creează. După ce ai rulat: poți rula din nou (Actualizează).</strong></p>
                    <?php
                    $brand_piesa_extra_raw = get_option('webgsm_v2_brand_piesa_extra_terms', []);
                    $brand_piesa_extra = is_array($brand_piesa_extra_raw) ? implode("\n", $brand_piesa_extra_raw) : (string) $brand_piesa_extra_raw;
                    ?>
                    <div class="webgsm-brand-piesa-extra" style="margin:12px 0;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                        <strong>Brand Piesă – termeni suplimentari (unul per linie)</strong>
                        <p style="margin:4px 0 8px;font-size:12px;color:#64748b;">Adaugă aici branduri care nu sunt în lista implicită. Se salvează și se includ la „Creează/Actualizează Atribute”.</p>
                        <textarea id="webgsm-brand-piesa-extra" rows="4" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;"><?php echo esc_textarea($brand_piesa_extra); ?></textarea>
                        <button type="button" class="webgsm-btn" style="margin-top:8px;background:#0ea5e9;color:#fff;" id="btn-save-brand-piesa">Salvează termeni Brand Piesă</button>
                        <span id="status-brand-piesa" style="margin-left:8px;font-size:12px;"></span>
                    </div>
                    <div class="webgsm-preview">Model: iPhone 16 Pro Max ... Galaxy S24 Ultra...
Calitate: Original, Premium OEM, Aftermarket...
Brand Piesă: JK Incell, GX OLED, Ampsentrix... + termenii tăi
Tehnologie: Soft OLED, Hard OLED, Incell...
Brand Telefon: Apple, Samsung, Huawei...
Culoare: Negru, Alb, Auriu...</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-attrs">
                        <?php echo $attrs_done ? '🔄 Actualizează Atribute' : '🏷️ Creează Atribute'; ?>
                    </button>
                    <span style="font-size: 12px; color: #64748b; display: block; margin-top: 4px;"><?php echo $attrs_done ? 'Atribute există – poți rula din nou pentru actualizare.' : 'Încă nu ai rulat – apasă Creează.'; ?></span>
                    <div class="webgsm-status" id="status-attrs"></div>
                </div>
                
                <!-- 3. Meniu -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon purple">🍔</div>
                        <h3>3. Creare Meniu</h3>
                    </div>
                    <p>Creează meniul principal cu 5 tab-uri. Poți <strong>Șterge doar Meniu</strong> apoi <strong>Actualizează Meniu</strong> fără să atingi categorii/atribute.</p>
                    <div class="webgsm-preview">┌─────────┬─────────┬─────────────┬───────────┬──────────┐
│  Piese  │ Unelte  │  Accesorii  │ Dispozitive│ Servicii │
└─────────┴─────────┴─────────────┴───────────┴──────────┘
Piese → 3 nivele: Piese iPhone > Ecrane, Baterii...
Unelte / Accesorii → Dropdown cu categorii
Dispozitive / Servicii → Dropdown simplu</div>
                    <button class="webgsm-btn webgsm-btn-primary" id="btn-menu">
                        <?php echo $menu_done ? '🔄 Actualizează Meniu' : '🍔 Creează Meniu'; ?>
                    </button>
                    <button class="webgsm-btn" style="background: #94a3b8; color: #fff;" id="btn-clear-menu" title="Șterge doar meniul WebGSM (categorii și atribute rămân)">
                        🧹 Șterge doar Meniu
                    </button>
                    <div class="webgsm-status" id="status-menu"></div>
                </div>
                
                <!-- 4. Filtre -->
                <div class="webgsm-card">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon orange">🔍</div>
                        <h3>4. Configurare Filtre</h3>
                    </div>
                    <p><strong>Bifează</strong> filtrele pe care le vrei, <strong>debifează</strong> pe cele pe care nu le vrei, sau <strong>Șterge doar Filtre</strong> ca să le scoți pe toate. Apoi <strong>Aplică Filtre</strong>. Produsele nu își pierd maparea.</p>
                    <?php
                    $available_filters = [
                        'model-compatibil' => 'Compatibilitate (Model compatibil)',
                        'model' => 'Model',
                        'calitate' => 'Calitate',
                        'brand-piesa' => 'Brand Piesă',
                        'tehnologie' => 'Tehnologie',
                    ];
                    $saved_filter_attrs = get_option('webgsm_v2_filter_attributes', ['model-compatibil', 'model', 'calitate', 'brand-piesa', 'tehnologie', 'price']);
                    $current_filters_list = $this->get_current_sidebar_filters_list();
                    ?>
                    <div class="webgsm-filter-config" style="margin: 12px 0; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-weight: 600; margin-bottom: 8px;">☑ Ce filtre să apară (bifează / debifează):</div>
                        <?php foreach ($available_filters as $slug => $label) : ?>
                        <label style="display: block; margin: 4px 0;"><input type="checkbox" class="webgsm-filter-attr" value="<?php echo esc_attr($slug); ?>" <?php echo in_array($slug, $saved_filter_attrs, true) ? 'checked' : ''; ?> /> <?php echo esc_html($label); ?></label>
                        <?php endforeach; ?>
                        <label style="display: block; margin: 4px 0;"><input type="checkbox" class="webgsm-filter-attr" value="price" id="webgsm-filter-price" <?php echo in_array('price', $saved_filter_attrs, true) ? 'checked' : ''; ?> /> 💰 Preț</label>
                    </div>
                    <?php if (!empty($current_filters_list)) : ?>
                    <div class="webgsm-current-filters" style="margin: 8px 0; font-size: 12px; color: #64748b;">
                        <strong>Filtre active acum în sidebar:</strong> <?php echo esc_html($current_filters_list); ?>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                        <button class="webgsm-btn webgsm-btn-primary" id="btn-filters">
                            <?php echo $filters_done ? '🔄 Aplică Filtre (cu selecția de mai sus)' : '🔍 Configurează Filtre'; ?>
                        </button>
                        <button class="webgsm-btn" style="background: #94a3b8; color: #fff;" id="btn-clear-filters" title="Șterge doar widget-urile de filtre din sidebar">
                            🧹 Șterge doar Filtre
                        </button>
                    </div>
                    <div class="webgsm-status" id="status-filters"></div>
                </div>
                
                <!-- Status General -->
                <div class="webgsm-card webgsm-full">
                    <div class="webgsm-card-header">
                        <div class="webgsm-card-icon blue">📊</div>
                        <h3>Status Setup</h3>
                    </div>
                    <ul class="webgsm-checklist">
                        <li>
                            <span class="check-icon <?php echo $cats_done ? 'done' : 'pending'; ?>"><?php echo $cats_done ? '✓' : '○'; ?></span>
                            <span>Categorii WooCommerce (~55 categorii)</span>
                        </li>
                        <li>
                            <span class="check-icon <?php echo $attrs_done ? 'done' : 'pending'; ?>"><?php echo $attrs_done ? '✓' : '○'; ?></span>
                            <span>Atribute pentru filtrare (Model, Compatibilitate, Calitate, Brand, Tehnologie, etc.)</span>
                        </li>
                        <li>
                            <span class="check-icon <?php echo $menu_done ? 'done' : 'pending'; ?>"><?php echo $menu_done ? '✓' : '○'; ?></span>
                            <span>Meniu navigare principal (5 tab-uri)</span>
                        </li>
                        <li>
                            <span class="check-icon <?php echo $filters_done ? 'done' : 'pending'; ?>"><?php echo $filters_done ? '✓' : '○'; ?></span>
                            <span>Widget-uri filtrare sidebar</span>
                        </li>
                    </ul>
                    
                    <p style="margin-top: 12px; font-size: 12px; color: #64748b;"><strong>Șterge Tot</strong> = șterge categorii, atribute, tags, meniu – produsele rămân dar își pierd asignările. Pentru doar actualizare: la Categorii/Atribute apasă Actualizează; la Meniu folosește Șterge doar Meniu + Actualizează; la Filtre bifezi/debifezi și Aplică sau Șterge doar Filtre.</p>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button class="webgsm-btn" style="background: #f59e0b; color: white;" id="btn-reset" title="Resetează doar flag-urile (Categorii/Atribute/Meniu/Filtre) – butoanele vor arăta din nou «Creează» unde nu ai rulat">
                            🔄 Reset Flags
                        </button>
                        <button class="webgsm-btn webgsm-btn-danger" id="btn-cleanup">
                            🗑️ Șterge Tot (Categorii + Subcategorii + Tags + Atribute + Meniu)
                        </button>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            function doAjax(action, btnId, statusId) {
                var $btn = $('#' + btnId);
                var $status = $('#' + statusId);
                var originalText = $btn.html();
                
                $btn.prop('disabled', true).html('<span class="spinner">⏳</span> Se procesează...');
                $status.removeClass('success error').addClass('loading show').text('Se procesează...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: action, nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>' },
                    success: function(response) {
                        if (response.success) {
                            $status.removeClass('loading').addClass('success').html('✅ ' + response.data.message);
                            var id = $btn.attr('id');
                            if (id === 'btn-filters') $btn.html('🔄 Actualizează Filtre').addClass('webgsm-btn-success').prop('disabled', false);
                            else if (id === 'btn-cats') $btn.html('🔄 Actualizează Categorii').addClass('webgsm-btn-success').prop('disabled', false);
                            else if (id === 'btn-attrs') $btn.html('🔄 Actualizează Atribute').addClass('webgsm-btn-success').prop('disabled', false);
                            else if (id === 'btn-menu') $btn.html('🔄 Actualizează Meniu').addClass('webgsm-btn-success').prop('disabled', false);
                            else $btn.html('✅ Gata!').addClass('webgsm-btn-success');
                        } else {
                            $status.removeClass('loading').addClass('error').html('❌ ' + (response.data ? response.data.message : 'Eroare'));
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        $status.removeClass('loading').addClass('error').text('❌ Eroare de conexiune');
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            }
            
            $('#btn-cats').on('click', function() { doAjax('webgsm_v2_create_categories', 'btn-cats', 'status-cats'); });
            $('#btn-save-brand-piesa').on('click', function() {
                var $btn = $('#btn-save-brand-piesa');
                var $status = $('#status-brand-piesa');
                $status.removeClass('success error').text('');
                $.post(ajaxurl, {
                    action: 'webgsm_v2_save_brand_piesa_extra',
                    nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>',
                    terms: $('#webgsm-brand-piesa-extra').val()
                }, function(response) {
                    if (response.success) {
                        $status.addClass('success').css('color','#059669').text('✅ ' + response.data.message);
                    } else {
                        $status.addClass('error').css('color','#dc2626').text('❌ ' + (response.data && response.data.message ? response.data.message : 'Eroare'));
                    }
                }).fail(function() {
                    $status.addClass('error').css('color','#dc2626').text('❌ Eroare de conexiune');
                });
            });
            $('#btn-attrs').on('click', function() { doAjax('webgsm_v2_create_attributes', 'btn-attrs', 'status-attrs'); });
            $('#btn-menu').on('click', function() { doAjax('webgsm_v2_create_menu', 'btn-menu', 'status-menu'); });
            $('#btn-filters').on('click', function() {
                var attrs = [];
                $('.webgsm-filter-attr:checked').each(function() { attrs.push($(this).val()); });
                if (attrs.length === 0) { $('#status-filters').addClass('show error').text('Bifează cel puțin un filtru.'); return; }
                var $btn = $('#btn-filters');
                var $status = $('#status-filters');
                var originalText = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner">⏳</span> Se procesează...');
                $status.removeClass('success error').addClass('loading show').text('Se procesează...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'webgsm_v2_setup_filters', nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>', filter_attrs: attrs },
                    success: function(response) {
                        if (response.success) {
                            $status.removeClass('loading').addClass('success').html('✅ ' + response.data.message);
                            $btn.html('🔄 Aplică Filtre (cu selecția de mai sus)').addClass('webgsm-btn-success').prop('disabled', false);
                        } else {
                            $status.removeClass('loading').addClass('error').html('❌ ' + (response.data ? response.data.message : 'Eroare'));
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        $status.removeClass('loading').addClass('error').text('❌ Eroare de conexiune');
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });
            $('#btn-clear-menu').on('click', function() {
                if (confirm('Ștergi doar meniul WebGSM? Categorii, atribute și filtre rămân neschimbate.')) {
                    var $btn = $('#btn-clear-menu');
                    var $status = $('#status-menu');
                    $btn.prop('disabled', true).html('<span class="spinner">⏳</span>');
                    $status.removeClass('success error').addClass('loading show').text('Se procesează...');
                    $.post(ajaxurl, { action: 'webgsm_v2_clear_menu', nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>' }, function(response) {
                        if (response.success) {
                            $status.removeClass('loading').addClass('success').html('✅ ' + response.data.message);
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            $status.removeClass('loading').addClass('error').html('❌ ' + (response.data ? response.data.message : 'Eroare'));
                            $btn.prop('disabled', false).html('🧹 Șterge doar Meniu');
                        }
                    }).fail(function() {
                        $status.removeClass('loading').addClass('error').text('❌ Eroare de conexiune');
                        $btn.prop('disabled', false).html('🧹 Șterge doar Meniu');
                    });
                }
            });
            $('#btn-clear-filters').on('click', function() {
                if (confirm('Ștergi doar widget-urile de filtre din sidebar? Categorii, atribute și produse rămân neschimbate.')) {
                    var $btn = $('#btn-clear-filters');
                    var $status = $('#status-filters');
                    $btn.prop('disabled', true).html('<span class="spinner">⏳</span>');
                    $status.removeClass('success error').addClass('loading show').text('Se procesează...');
                    $.post(ajaxurl, { action: 'webgsm_v2_clear_filters', nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>' }, function(response) {
                        if (response.success) {
                            $status.removeClass('loading').addClass('success').html('✅ ' + response.data.message);
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            $status.removeClass('loading').addClass('error').html('❌ ' + (response.data ? response.data.message : 'Eroare'));
                            $btn.prop('disabled', false).html('🧹 Șterge doar Filtre');
                        }
                    }).fail(function() {
                        $status.removeClass('loading').addClass('error').text('❌ Eroare de conexiune');
                        $btn.prop('disabled', false).html('🧹 Șterge doar Filtre');
                    });
                }
            });
            
            $('#btn-reset').on('click', function() {
                if (confirm('Reset flags? Vei putea rula din nou toți pașii.')) {
                    $.post(ajaxurl, { action: 'webgsm_v2_reset', nonce: '<?php echo wp_create_nonce('webgsm_v2'); ?>' }, function() {
                        location.reload();
                    });
                }
            });
            
            $('#btn-cleanup').on('click', function() {
                if (confirm('⚠️ ATENȚIE: Se vor ȘTERGE TOATE categoriile (inclusiv subcategorii), tags, atribute și meniul! Continui?')) {
                    if (confirm('Ești absolut sigur? Această acțiune este ireversibilă!')) {
                        doAjax('webgsm_v2_cleanup', 'btn-cleanup', 'status-filters');
                    }
                }
            });
            
            var webgsm_v2 = { nonce: '<?php echo esc_js(wp_create_nonce('webgsm_v2')); ?>' };

            $('#toggle-structure').on('click', function() {
                var $content = $('#structure-content');
                var $btn = $('#toggle-structure');
                $content.slideToggle(function() {
                    $btn.text($content.is(':visible') ? '[Ascunde]' : '[Arată]');
                });
            });

            function copyTextToClipboard(text, onSuccess, onFail) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(onSuccess).catch(function() {
                        tryFallbackCopy();
                    });
                } else {
                    tryFallbackCopy();
                }
                function tryFallbackCopy() {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0;';
                    document.body.appendChild(ta);
                    ta.focus();
                    ta.select();
                    try {
                        var ok = document.execCommand('copy');
                        document.body.removeChild(ta);
                        if (ok) onSuccess(); else onFail();
                    } catch (e) {
                        document.body.removeChild(ta);
                        onFail();
                    }
                }
            }

            $('#copy-for-ai').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Se generează...');
                $.post(ajaxurl, { action: 'webgsm_export_for_ai', nonce: webgsm_v2.nonce }, function(response) {
                    if (response.success) {
                        copyTextToClipboard(response.data.text, function() {
                            $('#copy-status').text('✅ Copiat în clipboard!').fadeIn().delay(2000).fadeOut();
                        }, function() {
                            $('#copy-status').text('Selectează zona de mai jos și apasă Ctrl+C').fadeIn().delay(4000).fadeOut();
                            var $box = $('#copy-fallback-box');
                            if ($box.length) $box.remove();
                            $box = $('<div id="copy-fallback-box" style="margin-top:10px;"><label>Copiază manual:</label><textarea id="copy-fallback-ta" rows="12" style="width:100%;font-size:12px;margin-top:4px;"></textarea></div>');
                            $('.export-section').append($box);
                            $('#copy-fallback-ta').val(response.data.text).select();
                        });
                    }
                    btn.prop('disabled', false).text('📋 Copiază Structura pentru AI');
                }).fail(function() {
                    btn.prop('disabled', false).text('📋 Copiază Structura pentru AI');
                });
            });

            $('#export-json').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                $.post(ajaxurl, { action: 'webgsm_export_json', nonce: webgsm_v2.nonce }, function(response) {
                    if (response.success) {
                        var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'webgsm-structure-' + new Date().toISOString().slice(0, 10) + '.json';
                        a.click();
                        URL.revokeObjectURL(url);
                    }
                    btn.prop('disabled', false);
                }).fail(function() {
                    btn.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
    
    // ===========================================
    // AJAX: Creare Categorii (suportă 2 sau 3 nivele: Piese > Piese iPhone > Ecrane)
    // ===========================================
    public function ajax_create_categories() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        $created = 0;
        
        foreach ($this->categories as $parent_name => $parent_data) {
            // Nivel 1: categoria părinte
            $parent_id = $this->create_category($parent_name, $parent_data['slug'], 0, $parent_data['description'] ?? '');
            if ($parent_id) $created++;
            
            if (empty($parent_data['children'])) continue;
            
            foreach ($parent_data['children'] as $child_name => $child_value) {
                // Nivel 2: fie slug simplu (name => slug), fie array cu slug + children (3 nivele)
                if (is_array($child_value) && isset($child_value['slug'])) {
                    // 3 nivele: Piese > Piese iPhone > Ecrane
                    $level2_id = $this->create_category($child_name, $child_value['slug'], $parent_id);
                    if ($level2_id) $created++;
                    
                    if (!empty($child_value['children'])) {
                        $level2_slug = $child_value['slug'];
                        $brand_suffix = str_replace('piese-', '', $level2_slug); // iphone, samsung, etc.
                        foreach ($child_value['children'] as $sub_name => $sub_slug) {
                            $sub_slug_unique = $sub_slug . '-' . $brand_suffix; // ecrane-iphone, baterii-samsung
                            $sub_id = $this->create_category($sub_name, $sub_slug_unique, $level2_id);
                            if ($sub_id) $created++;
                        }
                    }
                } else {
                    // 2 nivele: Unelte > Șurubelnițe (child_value e string slug)
                    $child_slug = is_string($child_value) ? $child_value : sanitize_title($child_name);
                    $child_id = $this->create_category($child_name, $child_slug, $parent_id);
                    if ($child_id) $created++;
                }
            }
        }
        
        update_option('webgsm_v2_categories', true);
        wp_send_json_success(['message' => "Au fost create {$created} categorii!"]);
    }
    
    private function create_category($name, $slug, $parent = 0, $desc = '') {
        $existing = get_term_by('slug', $slug, 'product_cat');
        if ($existing) return $existing->term_id;
        
        $result = wp_insert_term($name, 'product_cat', [
            'slug' => $slug,
            'parent' => $parent,
            'description' => $desc
        ]);
        
        return is_wp_error($result) ? false : $result['term_id'];
    }
    
    /** Șterge o categorie și toți descendenții (recursiv, de la frunze la rădăcină). */
    private function delete_category_and_children($term_id) {
        $children = get_terms(['taxonomy' => 'product_cat', 'parent' => $term_id, 'hide_empty' => false]);
        foreach ($children as $child) {
            $this->delete_category_and_children($child->term_id);
        }
        wp_delete_term($term_id, 'product_cat');
    }
    
    // ===========================================
    // AJAX: Salvare termeni suplimentari Brand Piesă
    // ===========================================
    public function ajax_save_brand_piesa_extra() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        $raw = isset($_POST['terms']) ? wp_unslash($_POST['terms']) : '';
        $lines = array_filter(array_map('trim', explode("\n", str_replace("\r", "\n", $raw))));
        $lines = array_unique($lines);
        update_option('webgsm_v2_brand_piesa_extra_terms', $lines);
        wp_send_json_success(['message' => 'Termeni Brand Piesă salvați (' . count($lines) . '). Rulează Actualizează Atribute ca să fie creați în WooCommerce.']);
    }
    
    /** Returnează lista de termeni pentru Brand Piesă (din $this->attributes + termeni salvați manual). */
    private function get_brand_piesa_terms() {
        $base = isset($this->attributes['Brand Piesă']['terms']) ? $this->attributes['Brand Piesă']['terms'] : [];
        $extra = get_option('webgsm_v2_brand_piesa_extra_terms', []);
        if (!is_array($extra)) $extra = $extra ? array_filter(array_map('trim', explode("\n", $extra))) : [];
        return array_values(array_unique(array_merge($base, $extra)));
    }
    
    // ===========================================
    // AJAX: Creare Atribute
    // ===========================================
    public function ajax_create_attributes() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        global $wpdb;
        $created_attrs = 0;
        $created_terms = 0;
        
        foreach ($this->attributes as $attr_name => $attr_data) {
            $attr_slug = $attr_data['slug'];
            $terms = $attr_data['terms'];
            if ($attr_slug === 'brand-piesa') {
                $terms = $this->get_brand_piesa_terms();
            }
            
            // Verifică dacă atributul există
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
                $attr_slug
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', [
                    'attribute_name' => $attr_slug,
                    'attribute_label' => $attr_name,
                    'attribute_type' => 'select',
                    'attribute_orderby' => 'menu_order',
                    'attribute_public' => 0
                ]);
                $created_attrs++;
                delete_transient('wc_attribute_taxonomies');
            }
            
            // Înregistrează taxonomia
            $taxonomy = 'pa_' . $attr_slug;
            if (!taxonomy_exists($taxonomy)) {
                register_taxonomy($taxonomy, 'product', [
                    'label' => $attr_name,
                    'public' => false,
                    'show_ui' => true,
                    'hierarchical' => false,
                ]);
            }
            
            // Creează termenii
            foreach ($terms as $term_name) {
                $term_name = is_string($term_name) ? trim($term_name) : '';
                if ($term_name === '') continue;
                $term_slug = sanitize_title($term_name);
                if (!term_exists($term_slug, $taxonomy)) {
                    $result = wp_insert_term($term_name, $taxonomy, ['slug' => $term_slug]);
                    if (!is_wp_error($result)) $created_terms++;
                }
            }
        }
        
        flush_rewrite_rules();
        update_option('webgsm_v2_attributes', true);
        wp_send_json_success(['message' => "Create {$created_attrs} atribute noi și {$created_terms} termeni!"]);
    }
    
    // ===========================================
    // AJAX: Creare Meniu
    // ===========================================
    public function ajax_create_menu() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        $menu_name = 'WebGSM Main Menu';
        
        // Șterge meniul existent
        $existing = wp_get_nav_menu_object($menu_name);
        if ($existing) {
            wp_delete_nav_menu($existing->term_id);
        }
        
        // Creează meniu nou
        $menu_id = wp_create_nav_menu($menu_name);
        if (is_wp_error($menu_id)) {
            wp_send_json_error(['message' => 'Eroare la crearea meniului']);
        }
        
        $items_count = 0;
        $order = 1;
        
        foreach ($this->categories as $parent_name => $parent_data) {
            $parent_term = get_term_by('slug', $parent_data['slug'], 'product_cat');
            if (!$parent_term) continue;
            
            $is_mega = in_array($parent_name, ['Piese', 'Unelte', 'Accesorii']);
            
            $parent_menu_id = wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => $parent_name,
                'menu-item-object' => 'product_cat',
                'menu-item-object-id' => $parent_term->term_id,
                'menu-item-type' => 'taxonomy',
                'menu-item-status' => 'publish',
                'menu-item-classes' => $is_mega ? 'mf-mega-menu' : '',
                'menu-item-position' => $order++
            ]);
            $items_count++;
            
            if (empty($parent_data['children'])) continue;
            
            foreach ($parent_data['children'] as $child_name => $child_value) {
                if (is_array($child_value) && isset($child_value['slug'])) {
                    $child_term = get_term_by('slug', $child_value['slug'], 'product_cat');
                    if (!$child_term) continue;
                    $level2_menu_id = wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title' => $child_name,
                        'menu-item-object' => 'product_cat',
                        'menu-item-object-id' => $child_term->term_id,
                        'menu-item-type' => 'taxonomy',
                        'menu-item-status' => 'publish',
                        'menu-item-parent-id' => $parent_menu_id,
                        'menu-item-position' => $order++
                    ]);
                    $items_count++;
                    if (!empty($child_value['children'])) {
                        $level2_slug = $child_value['slug'];
                        $brand_suffix = str_replace('piese-', '', $level2_slug);
                        foreach ($child_value['children'] as $sub_name => $sub_slug) {
                            $sub_slug_unique = $sub_slug . '-' . $brand_suffix;
                            $sub_term = get_term_by('slug', $sub_slug_unique, 'product_cat');
                            if (!$sub_term) continue;
                            wp_update_nav_menu_item($menu_id, 0, [
                                'menu-item-title' => $sub_name,
                                'menu-item-object' => 'product_cat',
                                'menu-item-object-id' => $sub_term->term_id,
                                'menu-item-type' => 'taxonomy',
                                'menu-item-status' => 'publish',
                                'menu-item-parent-id' => $level2_menu_id,
                                'menu-item-position' => $order++
                            ]);
                            $items_count++;
                        }
                    }
                } else {
                    $child_slug = is_string($child_value) ? $child_value : sanitize_title($child_name);
                    $child_term = get_term_by('slug', $child_slug, 'product_cat');
                    if (!$child_term) continue;
                    wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title' => $child_name,
                        'menu-item-object' => 'product_cat',
                        'menu-item-object-id' => $child_term->term_id,
                        'menu-item-type' => 'taxonomy',
                        'menu-item-status' => 'publish',
                        'menu-item-parent-id' => $parent_menu_id,
                        'menu-item-position' => $order++
                    ]);
                    $items_count++;
                }
            }
        }
        
        // Asociază la locații
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menu_id;
        $locations['primary-menu'] = $menu_id;
        $locations['shop-department'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
        
        update_option('webgsm_v2_menu', true);
        wp_send_json_success(['message' => "Meniu creat cu {$items_count} itemi!"]);
    }
    
    /** Listează filtrele active în sidebar (pentru afișare vizuală). */
    private function get_current_sidebar_filters_list() {
        $sidebars = get_option('sidebars_widgets', []);
        $shop_sidebar = null;
        foreach ($this->get_shop_sidebar_candidates() as $s) {
            if (isset($sidebars[$s]) && !empty($sidebars[$s])) { $shop_sidebar = $s; break; }
        }
        if (!$shop_sidebar) return '';
        $labels = [];
        $attr_labels = [
            'model-compatibil' => 'Compatibilitate',
            'model' => 'Model',
            'calitate' => 'Calitate',
            'brand-piesa' => 'Brand Piesă',
            'tehnologie' => 'Tehnologie',
        ];
        foreach ($sidebars[$shop_sidebar] as $id) {
            if (strpos($id, 'webgsm_piese_filter-') === 0) {
                $labels[] = 'Subcategorie + Tip piesă';
            } elseif (strpos($id, 'woocommerce_layered_nav-') === 0) {
                $num = (int) str_replace('woocommerce_layered_nav-', '', $id);
                $opts = get_option('widget_woocommerce_layered_nav', []);
                $title = isset($opts[$num]['title']) ? $opts[$num]['title'] : (isset($opts[$num]['attribute']) ? ($attr_labels[$opts[$num]['attribute']] ?? $opts[$num]['attribute']) : $id);
                $labels[] = $title;
            } elseif (strpos($id, 'woocommerce_price_filter-') === 0) {
                $labels[] = 'Preț';
            }
        }
        return implode(', ', $labels);
    }
    
    // ===========================================
    // AJAX: Setup Filtre (folosește lista bifată în UI sau opțiunea salvată)
    // ===========================================
    public function ajax_setup_filters() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        delete_option('webgsm_category_filter_widget_checked');

        $filter_attrs = isset($_POST['filter_attrs']) && is_array($_POST['filter_attrs']) ? array_map('sanitize_text_field', $_POST['filter_attrs']) : get_option('webgsm_v2_filter_attributes', ['model-compatibil', 'model', 'calitate', 'brand-piesa', 'tehnologie', 'price']);
        if (empty($filter_attrs)) {
            wp_send_json_error(['message' => 'Bifează cel puțin un filtru.']);
        }
        update_option('webgsm_v2_filter_attributes', $filter_attrs);
        
        $sidebars = get_option('sidebars_widgets', []);
        $target_sidebars = [];
        foreach ($this->get_shop_sidebar_candidates() as $s) {
            if (isset($sidebars[$s])) {
                $target_sidebars[] = $s;
            }
        }
        if (empty($target_sidebars)) {
            $target_sidebars[] = 'catalog-sidebar';
            $sidebars['catalog-sidebar'] = [];
        }

        foreach ($target_sidebars as $sid) {
            if (!isset($sidebars[$sid]) || !is_array($sidebars[$sid])) {
                $sidebars[$sid] = [];
            }
            // Curăță doar widget-urile de filtre, nu distruge alte widget-uri din sidebar.
            $sidebars[$sid] = array_values(array_filter($sidebars[$sid], function ($id) {
                return strpos($id, 'webgsm_category_filter-') !== 0
                    && strpos($id, 'webgsm_piese_filter-') !== 0
                    && strpos($id, 'woocommerce_layered_nav-') !== 0
                    && strpos($id, 'woocommerce_price_filter-') !== 0;
            }));
        }
        
        // Widget generic dinamic pentru toate categoriile (Piese, Unelte, Accesorii)
        $category_filter_widget = get_option('widget_webgsm_category_filter', []);
        $category_filter_id = 1;
        $category_filter_widget[$category_filter_id] = ['title' => ''];
        update_option('widget_webgsm_category_filter', $category_filter_widget);
        foreach ($target_sidebars as $sid) {
            $sidebars[$sid][] = 'webgsm_category_filter-' . $category_filter_id;
        }
        
        // Widget filtre cu bifă: Subcategorie Piese (iPhone, Samsung…) + Tip piesă (Ecrane, Baterii…)
        // Păstrăm și widget-ul vechi pentru compatibilitate, dar widget-ul generic va avea prioritate
        $piese_widget = get_option('widget_webgsm_piese_filter', []);
        $piese_id = 1;
        $piese_widget[$piese_id] = ['title' => ''];
        update_option('widget_webgsm_piese_filter', $piese_widget);
        foreach ($target_sidebars as $sid) {
            $sidebars[$sid][] = 'webgsm_piese_filter-' . $piese_id;
        }
        
        $attr_labels = [
            'model-compatibil' => 'Compatibilitate',
            'model' => 'Model',
            'calitate' => 'Calitate',
            'brand-piesa' => 'Brand Piesă',
            'tehnologie' => 'Tehnologie',
        ];
        $existing_attr_map = $this->get_existing_global_attribute_map();
        $widget_id = 1;
        
        foreach ($filter_attrs as $slug) {
            if ($slug === 'price') continue;
            $resolved_slug = $this->resolve_real_attribute_slug_with_labels($slug, $existing_attr_map);
            $taxonomy = wc_attribute_taxonomy_name($resolved_slug);
            if (!taxonomy_exists($taxonomy)) {
                $this->filter_debug_data['resolved_widget_attributes'][$slug] = [
                    'resolved' => $resolved_slug,
                    'taxonomy' => $taxonomy,
                    'status' => 'taxonomy_missing',
                ];
                continue;
            }
            $widget_data = get_option('widget_woocommerce_layered_nav', []);
            $widget_data[$widget_id] = [
                'title' => $attr_labels[$slug] ?? ucfirst($slug),
                'attribute' => $resolved_slug,
                'display_type' => 'list',
                'query_type' => 'or'
            ];
            update_option('widget_woocommerce_layered_nav', $widget_data);
            foreach ($target_sidebars as $sid) {
                $sidebars[$sid][] = 'woocommerce_layered_nav-' . $widget_id;
            }
            $this->filter_debug_data['resolved_widget_attributes'][$slug] = [
                'resolved' => $resolved_slug,
                'taxonomy' => $taxonomy,
                'status' => 'ok',
            ];
            $widget_id++;
        }
        
        if (in_array('price', $filter_attrs, true)) {
            $price_widget = get_option('widget_woocommerce_price_filter', []);
            $price_widget[1] = ['title' => 'Preț'];
            update_option('widget_woocommerce_price_filter', $price_widget);
            foreach ($target_sidebars as $sid) {
                $sidebars[$sid][] = 'woocommerce_price_filter-1';
            }
        }
        
        update_option('sidebars_widgets', $sidebars);
        update_option('webgsm_v2_filters', true);
        wp_send_json_success(['message' => 'Filtre configurate în sidebar! (conform selecției bifate)', 'sidebars' => $target_sidebars]);
    }
    
    // ===========================================
    // AJAX: Șterge doar Filtre (widget-uri din sidebar) – nu ating categorii, atribute, produse
    // ===========================================
    public function ajax_clear_filters() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        $sidebars = get_option('sidebars_widgets', []);
        $updated = false;
        foreach ($this->get_shop_sidebar_candidates() as $sid) {
            if (!isset($sidebars[$sid]) || empty($sidebars[$sid])) {
                continue;
            }
            $sidebars[$sid] = array_filter($sidebars[$sid], function ($id) {
                return strpos($id, 'woocommerce_layered_nav-') !== 0
                    && strpos($id, 'woocommerce_price_filter-') !== 0
                    && strpos($id, 'webgsm_piese_filter-') !== 0
                    && strpos($id, 'webgsm_category_filter-') !== 0;
            });
            $sidebars[$sid] = array_values($sidebars[$sid]);
            $updated = true;
        }
        
        if ($updated) {
            update_option('sidebars_widgets', $sidebars);
        }
        
        delete_option('webgsm_v2_filters');
        wp_send_json_success(['message' => 'Filtre șterse din sidebar. Categorii, atribute și produse sunt neschimbate. Poți rula «Configurează Filtre» din nou.']);
    }
    
    // ===========================================
    // AJAX: Șterge doar Meniu (nu ating categorii, atribute, filtre, produse)
    // ===========================================
    public function ajax_clear_menu() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        $menu = wp_get_nav_menu_object('WebGSM Main Menu');
        if ($menu) wp_delete_nav_menu($menu->term_id);
        delete_option('webgsm_v2_menu');
        wp_send_json_success(['message' => 'Meniu WebGSM șters. Categorii, atribute și filtre sunt neschimbate. Poți rula «Creează Meniu» din nou.']);
    }
    
    // ===========================================
    // AJAX: Reset Flags
    // ===========================================
    public function ajax_reset() {
        check_ajax_referer('webgsm_v2', 'nonce');
        delete_option('webgsm_v2_categories');
        delete_option('webgsm_v2_attributes');
        delete_option('webgsm_v2_menu');
        delete_option('webgsm_v2_filters');
        wp_send_json_success();
    }
    
    // ===========================================
    // AJAX: Cleanup (șterge tot: categorii + subcategorii + tags + atribute + meniu)
    // ===========================================
    public function ajax_cleanup() {
        check_ajax_referer('webgsm_v2', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Nu ai permisiuni']);
        
        global $wpdb;
        
        // 1) Șterge TOATE categoriile de produse (inclusiv subcategoriile rămase) – de la frunze la rădăcină
        $deleted_cats = 0;
        do {
            $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'all']);
            if (empty($terms)) break;
            $ids = wp_list_pluck($terms, 'term_id');
            $to_delete = [];
            foreach ($terms as $t) {
                $has_child_in_list = false;
                foreach ($terms as $t2) {
                    if ((int) $t2->parent === (int) $t->term_id) {
                        $has_child_in_list = true;
                        break;
                    }
                }
                if (!$has_child_in_list) $to_delete[] = $t->term_id;
            }
            foreach ($to_delete as $tid) {
                if (!is_wp_error(wp_delete_term($tid, 'product_cat'))) $deleted_cats++;
            }
        } while (!empty($terms));
        
        // 2) Șterge TOATE tag-urile de produse
        $tag_terms = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false, 'fields' => 'ids']);
        foreach ($tag_terms as $tid) {
            wp_delete_term($tid, 'product_tag');
        }
        
        // 3) Șterge TOATE atributele WooCommerce (gol total)
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attr) {
            $wpdb->delete($wpdb->prefix . 'woocommerce_attribute_taxonomies', ['attribute_id' => $attr->attribute_id]);
        }
        delete_transient('wc_attribute_taxonomies');
        
        // 4) Șterge meniul WebGSM (să fie meniul gol)
        $menu = wp_get_nav_menu_object('WebGSM Main Menu');
        if ($menu) wp_delete_nav_menu($menu->term_id);
        
        // 5) Reset flags wizard
        delete_option('webgsm_v2_categories');
        delete_option('webgsm_v2_attributes');
        delete_option('webgsm_v2_menu');
        delete_option('webgsm_v2_filters');
        
        wp_send_json_success(['message' => 'Tot șters: categorii (inclusiv subcategorii), tags, atribute, meniu. Poți începe din nou.']);
    }
}

// ===========================================
// VIZUALIZARE ȘI EXPORT PENTRU AI (read-only)
// ===========================================

function webgsm_get_category_tree_html() {
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0,
        'orderby' => 'menu_order'
    ));
    if (is_wp_error($categories) || empty($categories)) {
        return '<p>Nicio categorie.</p>';
    }
    $html = '<ul class="category-tree">';
    foreach ($categories as $cat) {
        $html .= webgsm_render_category_branch($cat);
    }
    $html .= '</ul>';
    return $html;
}

function webgsm_render_category_branch($category, $level = 0) {
    $count = isset($category->count) ? (int) $category->count : 0;
    $children = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $category->term_id
    ));
    if (is_wp_error($children)) {
        $children = array();
    }
    $icon = empty($children) ? '📄' : '📁';
    $name = esc_html($category->name);
    $slug = esc_html($category->slug);
    $html = "<li class='level-{$level}'>";
    $html .= "<span class='cat-item'>{$icon} <strong>{$name}</strong> ";
    $html .= "<code>[{$slug}]</code> ";
    $html .= "<small>({$count} produse)</small></span>";
    if (!empty($children)) {
        $html .= '<ul>';
        foreach ($children as $child) {
            $html .= webgsm_render_category_branch($child, $level + 1);
        }
        $html .= '</ul>';
    }
    $html .= '</li>';
    return $html;
}

function webgsm_get_attributes_html() {
    $attributes = wc_get_attribute_taxonomies();
    if (empty($attributes)) {
        return '<p>Niciun atribut.</p>';
    }
    $html = '<ul class="attributes-list">';
    foreach ($attributes as $attr) {
        $taxonomy = 'pa_' . $attr->attribute_name;
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));
        if (is_wp_error($terms)) {
            $terms = array();
        }
        $term_names = wp_list_pluck($terms, 'name');
        $term_list = implode(', ', array_slice($term_names, 0, 10));
        if (count($term_names) > 10) {
            $term_list .= '... (+' . (count($term_names) - 10) . ')';
        }
        $label = esc_html($attr->attribute_label);
        $tax_esc = esc_html($taxonomy);
        $list_esc = esc_html($term_list);
        $html .= "<li>";
        $html .= "<strong>{$label}</strong> ";
        $html .= "<code>[{$tax_esc}]</code><br>";
        $html .= "<small>Valori: {$list_esc}</small>";
        $html .= "</li>";
    }
    $html .= '</ul>';
    return $html;
}

function webgsm_get_tags_html() {
    $total = wp_count_terms(array('taxonomy' => 'product_tag'));
    if (is_wp_error($total)) {
        $total = 0;
    }
    $tags = get_terms(array(
        'taxonomy' => 'product_tag',
        'hide_empty' => false,
        'number' => 50,
        'orderby' => 'count',
        'order' => 'DESC'
    ));
    if (is_wp_error($tags)) {
        $tags = array();
    }
    $html = "<p>Total: {$total} tag-uri</p>";
    $html .= '<div class="tags-cloud">';
    foreach ($tags as $tag) {
        $name = esc_html($tag->name);
        $count = (int) $tag->count;
        $html .= "<span class='tag-item'>{$name} ({$count})</span> ";
    }
    $html .= '</div>';
    return $html;
}

function webgsm_generate_ai_structure_text() {
    $output = "# STRUCTURA WEBGSM - " . date('Y-m-d H:i') . "\n\n";
    $output .= "## CATEGORII\n\n";
    $output .= webgsm_get_categories_text_tree();
    $output .= "\n## ATRIBUTE\n\n";
    $attributes = wc_get_attribute_taxonomies();
    foreach ($attributes as $attr) {
        $taxonomy = 'pa_' . $attr->attribute_name;
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        if (is_wp_error($terms)) {
            $terms = array();
        }
        $term_names = wp_list_pluck($terms, 'name');
        $output .= "### " . $attr->attribute_label . " [{$taxonomy}]\n";
        $output .= "Valori: " . implode(', ', $term_names) . "\n\n";
    }
    $output .= "## TAG-URI (top 30)\n\n";
    $tags = get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false, 'number' => 30, 'orderby' => 'count', 'order' => 'DESC'));
    if (is_wp_error($tags)) {
        $tags = array();
    }
    $tag_names = wp_list_pluck($tags, 'name');
    $output .= implode(', ', $tag_names) . "\n";
    return $output;
}

function webgsm_get_categories_text_tree($parent = 0, $prefix = '') {
    $output = '';
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $parent,
        'orderby' => 'menu_order'
    ));
    if (is_wp_error($categories)) {
        return $output;
    }
    $total = count($categories);
    foreach ($categories as $i => $cat) {
        $is_last = ($i === $total - 1);
        $connector = $is_last ? '└── ' : '├── ';
        $child_prefix = $is_last ? '    ' : '│   ';
        $output .= $prefix . $connector . $cat->name . " [" . $cat->slug . "] (" . $cat->count . " produse)\n";
        $output .= webgsm_get_categories_text_tree($cat->term_id, $prefix . $child_prefix);
    }
    return $output;
}

function webgsm_get_categories_array($parent = 0) {
    $result = array();
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $parent
    ));
    if (is_wp_error($categories)) {
        return $result;
    }
    foreach ($categories as $cat) {
        $result[$cat->slug] = array(
            'name' => $cat->name,
            'slug' => $cat->slug,
            'count' => $cat->count,
            'children' => webgsm_get_categories_array($cat->term_id)
        );
    }
    return $result;
}

function webgsm_get_attributes_array() {
    $result = array();
    $attributes = wc_get_attribute_taxonomies();
    foreach ($attributes as $attr) {
        $taxonomy = 'pa_' . $attr->attribute_name;
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        if (is_wp_error($terms)) {
            $terms = array();
        }
        $result[$taxonomy] = array(
            'name' => $attr->attribute_label,
            'slug' => $taxonomy,
            'terms' => wp_list_pluck($terms, 'name')
        );
    }
    return $result;
}

function webgsm_get_tags_array() {
    $tags = get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false));
    if (is_wp_error($tags)) {
        return array();
    }
    return wp_list_pluck($tags, 'name');
}

add_action('wp_ajax_webgsm_export_for_ai', 'webgsm_ajax_export_for_ai');
function webgsm_ajax_export_for_ai() {
    check_ajax_referer('webgsm_v2', 'nonce');
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    $text = webgsm_generate_ai_structure_text();
    wp_send_json_success(array('text' => $text));
}

add_action('wp_ajax_webgsm_export_json', 'webgsm_ajax_export_json');
function webgsm_ajax_export_json() {
    check_ajax_referer('webgsm_v2', 'nonce');
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    $data = array(
        'version' => '2.1.0',
        'exported_at' => date('c'),
        'site' => get_site_url(),
        'categories' => webgsm_get_categories_array(),
        'attributes' => webgsm_get_attributes_array(),
        'tags' => webgsm_get_tags_array()
    );
    wp_send_json_success($data);
}

WebGSM_Setup_Wizard_V2::instance();
