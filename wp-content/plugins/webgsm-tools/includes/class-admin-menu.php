<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Tools_Admin_Menu {

    /** Parent slug: same as Setup Wizard so both appear under "Upload Tools" */
    const PARENT_SLUG = 'webgsm-setup-v2';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu() {
        add_submenu_page(
            self::PARENT_SLUG,
            'Dashboard',
            'Dashboard',
            'manage_woocommerce',
            'webgsm-tools',
            [$this, 'render_dashboard']
        );
        add_submenu_page(
            self::PARENT_SLUG,
            'Product Reviewer',
            '📦 Product Reviewer',
            'manage_woocommerce',
            'webgsm-reviewer',
            [$this, 'render_reviewer']
        );
        add_submenu_page(
            self::PARENT_SLUG,
            'Image Studio',
            '🎨 Image Studio',
            'manage_woocommerce',
            'webgsm-studio',
            [$this, 'render_studio']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'webgsm') === false) {
            return;
        }

        if (strpos($hook, 'reviewer') !== false) {
            wp_enqueue_style('webgsm-reviewer', WEBGSM_TOOLS_URL . 'admin/css/reviewer.css', [], WEBGSM_TOOLS_VERSION);
            wp_enqueue_script('webgsm-reviewer', WEBGSM_TOOLS_URL . 'admin/js/reviewer.js', ['jquery'], WEBGSM_TOOLS_VERSION, true);
            wp_localize_script('webgsm-reviewer', 'webgsmReviewer', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('webgsm_tools'),
                'categories' => WebGSM_Tools_Helpers::get_categories_tree(),
                'attributes' => WebGSM_Tools_Helpers::get_attributes_with_terms(),
                'validSlugs' => WebGSM_Tools_Helpers::get_valid_category_slugs(),
                'invalidSlugs' => WebGSM_Tools_Helpers::get_invalid_slugs()
            ]);
        }

        if (strpos($hook, 'studio') !== false) {
            wp_enqueue_style('webgsm-studio', WEBGSM_TOOLS_URL . 'admin/css/studio.css', [], WEBGSM_TOOLS_VERSION);
            wp_enqueue_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js', [], '5.3.1', true);
            wp_enqueue_script('webgsm-studio', WEBGSM_TOOLS_URL . 'admin/js/studio.js', ['jquery', 'fabric-js'], WEBGSM_TOOLS_VERSION, true);
            wp_localize_script('webgsm-studio', 'webgsmStudio', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('webgsm_tools'),
                'brandLogos' => WebGSM_Tools_Helpers::get_brand_logos(),
                'templates' => WebGSM_Tools_Helpers::get_image_templates()
            ]);
        }
    }

    public function render_dashboard() {
        include WEBGSM_TOOLS_PATH . 'admin/views/dashboard-page.php';
    }

    public function render_reviewer() {
        include WEBGSM_TOOLS_PATH . 'admin/views/reviewer-page.php';
    }

    public function render_studio() {
        include WEBGSM_TOOLS_PATH . 'admin/views/studio-page.php';
    }
}
