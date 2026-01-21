<?php
/**
 * WebGSM B2B File Upload Handler
 * 
 * Handles certificate uploads for B2B account approval
 * 
 * @package WebGSM_B2B_Pricing
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

class WebGSM_B2B_File_Upload {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->init_upload_directory();
    }
    
    /**
     * Initialize upload directory and .htaccess protection
     */
    private function init_upload_directory() {
        $upload_dir = wp_upload_dir();
        $cert_dir = $upload_dir['basedir'] . '/webgsm-b2b/certificates';
        
        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
            
            // Create .htaccess to deny direct access
            $htaccess_content = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($cert_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Handle certificate upload
     * 
     * @param array $file $_FILES array
     * @param int $user_id User ID
     * @return string|WP_Error File path on success, WP_Error on failure
     */
    public function handle_certificate_upload($file, $user_id) {
        // Validate user ID
        if (!$user_id || !is_numeric($user_id)) {
            error_log('[WebGSM B2B] Upload certificat: ID utilizator invalid: ' . $user_id);
            return new WP_Error('invalid_user', 'ID utilizator invalid.');
        }
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            error_log('[WebGSM B2B] Upload certificat: Fișier invalid sau lipsă pentru user ' . $user_id);
            return new WP_Error('no_file', 'Nu a fost încărcat niciun fișier.');
        }
        
        // Load WordPress file handling functions
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Validate file type
        $allowed_types = array('application/pdf', 'image/jpeg', 'image/jpg', 'image/png');
        $file_type = wp_check_filetype($file['name']);
        $mime_type = isset($file['type']) ? $file['type'] : '';
        
        // Double check file type
        if (!in_array($mime_type, $allowed_types) && !in_array($file_type['ext'], array('pdf', 'jpg', 'jpeg', 'png'))) {
            error_log('[WebGSM B2B] Upload certificat: Format invalid pentru user ' . $user_id . ' - Type: ' . $mime_type . ', Ext: ' . $file_type['ext']);
            return new WP_Error('invalid_type', 'Format nepermis. Doar PDF, JPG, PNG sunt permise.');
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            error_log('[WebGSM B2B] Upload certificat: Fișier prea mare pentru user ' . $user_id . ' - Size: ' . $file['size']);
            return new WP_Error('file_too_large', 'Fișierul depășește 5MB.');
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        if ($upload_dir['error']) {
            error_log('[WebGSM B2B] Upload certificat: Eroare director upload: ' . $upload_dir['error']);
            return new WP_Error('upload_dir_error', 'Eroare la accesarea directorului de upload.');
        }
        
        $cert_dir = $upload_dir['basedir'] . '/webgsm-b2b/certificates';
        $user_dir = $cert_dir . '/' . $user_id;
        
        // Create directories if they don't exist
        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
            // Create .htaccess for security
            $htaccess_path = $cert_dir . '/.htaccess';
            if (!file_exists($htaccess_path)) {
                file_put_contents($htaccess_path, "Order Deny,Allow\nDeny from all\n");
            }
        }
        
        if (!file_exists($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        
        // Delete old certificate if exists (but keep directory)
        $old_cert_path = get_user_meta($user_id, '_b2b_certificate_path', true);
        if (!empty($old_cert_path) && file_exists($old_cert_path)) {
            @unlink($old_cert_path);
            delete_user_meta($user_id, '_b2b_certificate_path');
            delete_user_meta($user_id, '_b2b_certificate_filename');
            delete_user_meta($user_id, '_b2b_certificate_uploaded');
        }
        
        // Generate unique filename
        $file_extension = $file_type['ext'] ?: 'pdf';
        $filename = 'certificat-' . $user_id . '-' . time() . '.' . $file_extension;
        $file_path = $user_dir . '/' . $filename;
        
        // Use wp_handle_upload for proper WordPress handling
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) use ($user_id) {
                return 'certificat-' . $user_id . '-' . time() . $ext;
            }
        );
        
        // Prepare file array for wp_handle_upload
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            error_log('[WebGSM B2B] Upload certificat: Eroare wp_handle_upload pentru user ' . $user_id . ' - ' . $uploaded_file['error']);
            return new WP_Error('upload_failed', $uploaded_file['error']);
        }
        
        // Ensure user directory exists before moving file
        if (!file_exists($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        
        // Verify source file exists
        if (!file_exists($uploaded_file['file'])) {
            error_log('[WebGSM B2B] Upload certificat: Fișier sursă nu există: ' . $uploaded_file['file']);
            return new WP_Error('source_not_found', 'Fișierul sursă nu a fost găsit.');
        }
        
        // Move file to user-specific directory
        $final_path = $user_dir . '/' . basename($uploaded_file['file']);
        
        if (!rename($uploaded_file['file'], $final_path)) {
            error_log('[WebGSM B2B] Upload certificat: Eroare la mutarea fișierului pentru user ' . $user_id);
            @unlink($uploaded_file['file']); // Clean up
            return new WP_Error('move_failed', 'Eroare la mutarea fișierului.');
        }
        
        // Set proper permissions
        chmod($final_path, 0644);
        
        // Save file path in user meta
        update_user_meta($user_id, '_b2b_certificate_path', $final_path);
        update_user_meta($user_id, '_b2b_certificate_filename', basename($final_path));
        update_user_meta($user_id, '_b2b_certificate_uploaded', current_time('mysql'));
        
        return $final_path;
    }
    
    /**
     * Get certificate URL (protected - admin only)
     * 
     * @param int $user_id User ID
     * @return string|false Certificate URL or false if not found
     */
    public function get_certificate_url($user_id) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $file_path = get_user_meta($user_id, '_b2b_certificate_path', true);
        if (empty($file_path) || !file_exists($file_path)) {
            return false;
        }
        
        // Return protected URL via admin-ajax
        return admin_url('admin-ajax.php?action=webgsm_view_certificate&user_id=' . $user_id . '&nonce=' . wp_create_nonce('view_cert_' . $user_id));
    }
    
    /**
     * Delete certificate
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function delete_certificate($user_id) {
        $file_path = get_user_meta($user_id, '_b2b_certificate_path', true);
        
        if (!empty($file_path) && file_exists($file_path)) {
            @unlink($file_path);
        }
        
        // Delete user directory if empty
        $upload_dir = wp_upload_dir();
        $user_dir = $upload_dir['basedir'] . '/webgsm-b2b/certificates/' . $user_id;
        if (is_dir($user_dir)) {
            @rmdir($user_dir);
        }
        
        // Delete user meta
        delete_user_meta($user_id, '_b2b_certificate_path');
        delete_user_meta($user_id, '_b2b_certificate_filename');
        delete_user_meta($user_id, '_b2b_certificate_uploaded');
        
        return true;
    }
    
    /**
     * AJAX handler for viewing certificate
     */
    public function ajax_view_certificate() {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
        
        // Security checks
        if (!current_user_can('manage_options')) {
            wp_die('Acces interzis.');
        }
        
        if (!wp_verify_nonce($nonce, 'view_cert_' . $user_id)) {
            wp_die('Token invalid.');
        }
        
        $file_path = get_user_meta($user_id, '_b2b_certificate_path', true);
        
        if (empty($file_path) || !file_exists($file_path)) {
            wp_die('Certificatul nu a fost găsit.');
        }
        
        // Get file info
        $file_type = wp_check_filetype($file_path);
        $mime_type = wp_check_filetype_and_ext($file_path, basename($file_path));
        
        // Set headers
        header('Content-Type: ' . $mime_type['type']);
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        
        // Output file
        readfile($file_path);
        exit;
    }
}
