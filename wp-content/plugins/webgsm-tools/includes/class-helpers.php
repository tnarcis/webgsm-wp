<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Tools_Helpers {

    public static function get_categories_tree() {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name'
        ]);
        if (is_wp_error($categories)) {
            return [];
        }
        $tree = [];
        $refs = [];
        foreach ($categories as $cat) {
            $refs[$cat->term_id] = [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent' => $cat->parent,
                'count' => $cat->count,
                'children' => []
            ];
        }
        foreach ($refs as $id => &$cat) {
            if ($cat['parent'] == 0) {
                $tree[$id] = &$cat;
            } else {
                if (isset($refs[$cat['parent']])) {
                    $refs[$cat['parent']]['children'][$id] = &$cat;
                }
            }
        }
        return $tree;
    }

    public static function get_valid_category_slugs() {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'fields' => 'slugs'
        ]);
        return is_wp_error($categories) ? [] : $categories;
    }

    public static function get_invalid_slugs() {
        return [
            'accesorii-service',
            'accesorii-service-xiaomi',
            'baterii-iphone-piese',
            'camere-iphone-piese',
            'ecrane-telefoane',
            'baterii-telefoane',
            'flex-uri-iphone',
            'incarcatoare',
            'surubelnite-unelte'
        ];
    }

    public static function get_attributes_with_terms() {
        $result = [];
        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $attr) {
            $taxonomy = 'pa_' . $attr->attribute_name;
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ]);
            $result[$taxonomy] = [
                'name' => $attr->attribute_label,
                'slug' => $taxonomy,
                'terms' => is_wp_error($terms) ? [] : wp_list_pluck($terms, 'name')
            ];
        }
        return $result;
    }

    public static function validate_category_path($path) {
        $parts = array_map('trim', explode('>', $path));
        $valid_slugs = self::get_valid_category_slugs();
        $invalid_slugs = self::get_invalid_slugs();
        $errors = [];
        $last_slug = '';
        foreach ($parts as $part) {
            $slug = sanitize_title($part);
            if (in_array($slug, $invalid_slugs)) {
                $errors[] = "Slug invalid: $slug";
            }
            $last_slug = $slug;
        }
        if (!in_array($last_slug, $valid_slugs) && empty($errors)) {
            $errors[] = "Categoria finală nu există: $last_slug";
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'slug' => $last_slug
        ];
    }

    public static function get_brand_logos() {
        $logos_dir = WEBGSM_TOOLS_PATH . 'assets/brand-logos/';
        $logos_url = WEBGSM_TOOLS_URL . 'assets/brand-logos/';
        $logos = [];
        if (is_dir($logos_dir)) {
            $files = array_merge(
                glob($logos_dir . '*.png') ?: [],
                glob($logos_dir . '*.jpg') ?: [],
                glob($logos_dir . '*.svg') ?: []
            );
            foreach ($files as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $logos[$name] = $logos_url . basename($file);
            }
        }
        return $logos;
    }

    public static function get_image_templates() {
        $templates_file = WEBGSM_TOOLS_PATH . 'data/image-templates.json';
        if (file_exists($templates_file)) {
            $json = file_get_contents($templates_file);
            $data = json_decode($json, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'ecran-iphone' => [
                'name' => 'Ecran iPhone',
                'badges' => [
                    ['type' => 'model', 'position' => 'top-left', 'style' => 'dark'],
                    ['type' => 'tech', 'position' => 'bottom-left', 'style' => 'green'],
                    ['type' => 'brand', 'position' => 'top-right', 'style' => 'logo'],
                    ['type' => 'features', 'position' => 'bottom-right', 'style' => 'icons']
                ]
            ],
            'baterie' => [
                'name' => 'Baterie',
                'badges' => [
                    ['type' => 'model', 'position' => 'top-left', 'style' => 'dark'],
                    ['type' => 'brand', 'position' => 'top-right', 'style' => 'logo'],
                    ['type' => 'capacity', 'position' => 'bottom-center', 'style' => 'highlight']
                ]
            ],
            'unealta' => [
                'name' => 'Unealtă',
                'badges' => [
                    ['type' => 'brand', 'position' => 'top-right', 'style' => 'logo'],
                    ['type' => 'watermark', 'position' => 'bottom-right', 'style' => 'subtle']
                ]
            ]
        ];
    }

    public static function generate_seo_title($product_name, $model = '', $brand = '') {
        $title = $product_name;
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        return $title;
    }

    public static function generate_seo_description($product_name, $short_desc = '', $model = '') {
        $desc = $short_desc ?: $product_name;
        if (strlen($desc) < 130) {
            $desc .= ' Comandă acum cu livrare rapidă!';
        }
        if (strlen($desc) > 160) {
            $desc = substr($desc, 0, 157) . '...';
        }
        return $desc;
    }
}
