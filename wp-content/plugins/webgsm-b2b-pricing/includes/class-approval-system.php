<?php
/**
 * WebGSM B2B Approval System
 * 
 * Handles B2B account approval workflow
 * 
 * @package WebGSM_B2B_Pricing
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

class WebGSM_B2B_Approval_System {
    
    private static $instance = null;
    private $file_upload;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->file_upload = WebGSM_B2B_File_Upload::instance();
        $this->init_hooks();
        $this->create_b2b_role();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Block pending users
        add_action('wp_login', array($this, 'block_pending_users'), 10, 2);
        add_action('woocommerce_checkout_process', array($this, 'prevent_pending_checkout'));
        
        // AJAX handlers
        add_action('wp_ajax_webgsm_approve_account', array($this, 'ajax_approve_account'));
        add_action('wp_ajax_webgsm_reject_account', array($this, 'ajax_reject_account'));
        
        // Certificate view handler
        add_action('wp_ajax_webgsm_view_certificate', array($this->file_upload, 'ajax_view_certificate'));
        
        // Certificate delete handler
        add_action('wp_ajax_webgsm_delete_certificate', array($this, 'ajax_delete_certificate'));
        
        // Certificate upload for pending users
        add_action('wp_ajax_webgsm_upload_pending_cert', array($this, 'ajax_upload_pending_cert'));
        
        // Certificate upload for admin (from customers page)
        add_action('wp_ajax_webgsm_admin_upload_certificate', array($this, 'ajax_admin_upload_certificate'));
        
        // Tier history handler
        add_action('wp_ajax_webgsm_get_tier_history', array($this, 'ajax_get_tier_history'));
    }
    
    /**
     * Create b2b_customer role if doesn't exist
     */
    private function create_b2b_role() {
        if (!get_role('b2b_customer')) {
            $capabilities = get_role('subscriber')->capabilities;
            $capabilities['view_b2b_prices'] = true;
            add_role('b2b_customer', 'Client B2B', $capabilities);
        }
    }
    
    /**
     * Set user to pending approval on registration
     * 
     * @param int $user_id User ID
     * @param array $file Certificate file from $_FILES
     */
    public function set_pending_on_registration($user_id, $file = null) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Remove customer role
        $user->remove_role('customer');
        
        // Add pending_approval role
        if (!get_role('pending_approval')) {
            add_role('pending_approval', 'Aprobare B2B', array('read' => true));
        }
        $user->add_role('pending_approval');
        
        // Set status meta
        update_user_meta($user_id, '_b2b_status', 'pending');
        update_user_meta($user_id, '_b2b_pending_date', current_time('mysql'));
        
        // Handle certificate upload if provided
        if ($file && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
            $upload_result = $this->file_upload->handle_certificate_upload($file, $user_id);
            if (is_wp_error($upload_result)) {
                error_log('WebGSM B2B: Certificate upload failed for user ' . $user_id . ': ' . $upload_result->get_error_message());
            }
        }
        
        // Send admin notification
        $this->send_admin_notification($user_id);
        
        return true;
    }
    
    /**
     * Approve B2B account
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function approve_account($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Remove pending role
        $user->remove_role('pending_approval');
        
        // Add b2b_customer role
        $user->add_role('b2b_customer');
        $user->add_role('customer'); // Also add customer for WooCommerce compatibility
        
        // Update status
        update_user_meta($user_id, '_b2b_status', 'approved');
        update_user_meta($user_id, '_b2b_approved_date', current_time('mysql'));
        update_user_meta($user_id, '_is_pj', 'yes');
        
        // Send approval email
        $this->send_approval_email($user_id);
        
        return true;
    }
    
    /**
     * Reject B2B account
     * 
     * @param int $user_id User ID
     * @param bool $delete_user Whether to delete user account
     * @return bool Success status
     */
    public function reject_account($user_id, $delete_user = false) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Set rejected status
        update_user_meta($user_id, '_b2b_status', 'rejected');
        update_user_meta($user_id, '_b2b_rejected_date', current_time('mysql'));
        
        // Delete certificate
        $this->file_upload->delete_certificate($user_id);
        
        // Delete user if requested
        if ($delete_user) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            return true;
        }
        
        // Remove pending role, keep only subscriber
        $user->remove_role('pending_approval');
        $user->remove_role('b2b_customer');
        $user->set_role('subscriber');
        
        return true;
    }
    
    /**
     * Block pending users from accessing site
     */
    public function block_pending_users($user_login, $user) {
        if (in_array('pending_approval', (array) $user->roles)) {
            wp_logout();
            wp_redirect(add_query_arg('b2b_pending', '1', wp_login_url()));
            exit;
        }
    }
    
    /**
     * Prevent pending users from checkout
     */
    public function prevent_pending_checkout() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $status = get_user_meta($user_id, '_b2b_status', true);
        
        if ($status === 'pending') {
            wc_add_notice('Contul tău este în așteptare aprobare. Te rugăm să aștepți aprobarea administratorului.', 'error');
        }
    }
    
    /**
     * AJAX handler for approving account
     */
    public function ajax_approve_account() {
        check_ajax_referer('webgsm_approve_account', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisiuni insuficiente.'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'ID utilizator invalid.'));
        }
        
        $result = $this->approve_account($user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Cont aprobat cu succes!'));
        } else {
            wp_send_json_error(array('message' => 'Eroare la aprobare.'));
        }
    }
    
    /**
     * AJAX handler for rejecting account
     */
    public function ajax_reject_account() {
        check_ajax_referer('webgsm_reject_account', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisiuni insuficiente.'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $delete_user = isset($_POST['delete_user']) && $_POST['delete_user'] === '1';
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'ID utilizator invalid.'));
        }
        
        $result = $this->reject_account($user_id, $delete_user);
        
        if ($result) {
            $message = $delete_user ? 'Cont respins și șters.' : 'Cont respins.';
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => 'Eroare la respingere.'));
        }
    }
    
    /**
     * Send admin notification email
     */
    private function send_admin_notification($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $company_name = get_user_meta($user_id, 'billing_company', true) ?: 'N/A';
        $cui = get_user_meta($user_id, 'billing_cui', true) ?: 'N/A';
        $cert_url = $this->file_upload->get_certificate_url($user_id);
        
        $subject = '[WebGSM] Cont B2B nou - Aprobare necesară';
        
        $message = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #1f2937;">Cont B2B nou - Aprobare necesară</h2>
            <p>Un nou cont B2B a fost creat și necesită aprobare:</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;"><strong>Nume Firmă:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">' . esc_html($company_name) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;"><strong>CUI:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">' . esc_html($cui) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;"><strong>Email:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">' . esc_html($user->user_email) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;"><strong>Data:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">' . current_time('d.m.Y H:i') . '</td>
                </tr>
            </table>
            <p style="margin-top: 20px;">
                <a href="' . admin_url('admin.php?page=webgsm-b2b-pending') . '" style="display: inline-block; background: #3b82f6; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">Vezi conturile pending</a>
            </p>
        </div>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Send approval email to user
     */
    private function send_approval_email($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $subject = 'Contul B2B a fost aprobat - WebGSM';
        
        $message = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #22c55e;">✅ Contul tău B2B a fost aprobat!</h2>
            <p>Dragă ' . esc_html($user->display_name) . ',</p>
            <p>Contul tău B2B a fost aprobat cu succes. Acum poți accesa:</p>
            <ul style="margin: 20px 0; padding-left: 20px;">
                <li>Prețuri B2B exclusive</li>
                <li>Discount-uri speciale pe nivel</li>
                <li>Toate beneficiile de parteneriat</li>
            </ul>
            <p style="margin-top: 30px;">
                <a href="' . wc_get_account_endpoint_url('dashboard') . '" style="display: inline-block; background: #3b82f6; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">Accesează contul</a>
            </p>
        </div>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * AJAX handler for uploading certificate by pending user
     */
    public function ajax_upload_pending_cert() {
        check_ajax_referer('webgsm_upload_pending_cert', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Trebuie să fiți autentificat.');
        }
        
        $user_id = get_current_user_id();
        $b2b_status = get_user_meta($user_id, '_b2b_status', true);
        
        if ($b2b_status !== 'pending') {
            wp_send_json_error('Această funcție este disponibilă doar pentru conturi în așteptare.');
        }
        
        if (!isset($_FILES['cert_file']) || empty($_FILES['cert_file']['tmp_name'])) {
            wp_send_json_error('Nu a fost selectat niciun fișier.');
        }
        
        $result = $this->file_upload->handle_certificate_upload($_FILES['cert_file'], $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Certificat încărcat cu succes!');
        }
    }
    
    /**
     * AJAX handler for admin uploading certificate for any B2B customer
     */
    public function ajax_admin_upload_certificate() {
        check_ajax_referer('webgsm_admin_upload_cert', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisiuni insuficiente.'));
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'ID utilizator invalid.'));
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'Utilizator negăsit.'));
            return;
        }
        
        // Verifică dacă utilizatorul este B2B
        $is_pj = get_user_meta($user_id, '_is_pj', true);
        if ($is_pj !== 'yes') {
            wp_send_json_error(array('message' => 'Utilizatorul nu este B2B.'));
            return;
        }
        
        if (!isset($_FILES['certificate_file']) || empty($_FILES['certificate_file']['tmp_name'])) {
            wp_send_json_error(array('message' => 'Nu a fost selectat niciun fișier.'));
            return;
        }
        
        $result = $this->file_upload->handle_certificate_upload($_FILES['certificate_file'], $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            // Log acțiunea
            $admin_user = wp_get_current_user();
            update_user_meta($user_id, '_certificate_uploaded_by_admin', $admin_user->ID);
            update_user_meta($user_id, '_certificate_uploaded_date', current_time('mysql'));
            
            wp_send_json_success(array(
                'message' => 'Certificat încărcat cu succes pentru ' . $user->display_name . '!'
            ));
        }
    }
    
    /**
     * AJAX Handler pentru istoric tier
     */
    public function ajax_get_tier_history() {
        check_ajax_referer('webgsm_get_tier_history', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisiuni insuficiente.'));
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'ID utilizator invalid.'));
            return;
        }
        
        $history = get_user_meta($user_id, '_pj_tier_history', true);
        
        if (!is_array($history)) {
            $history = array();
        }
        
        // Adaugă numele adminilor pentru fiecare intrare
        foreach ($history as $key => $entry) {
            if (isset($entry['by'])) {
                $admin = get_userdata($entry['by']);
                $history[$key]['admin_name'] = $admin ? $admin->display_name : 'Admin #' . $entry['by'];
            } else {
                $history[$key]['admin_name'] = 'Necunoscut';
            }
        }
        
        wp_send_json_success(array('history' => $history));
    }
}
