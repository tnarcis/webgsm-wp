<?php
/**
 * WebGSM B2B Pricing - Pending Accounts Page
 * 
 * Admin page for managing pending B2B account approvals
 * 
 * @package WebGSM_B2B_Pricing
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Acces interzis.');
}

$approval_system = WebGSM_B2B_Approval_System::instance();
$file_upload = WebGSM_B2B_File_Upload::instance();

// Get pending users
$pending_users = get_users(array(
    'role' => 'pending_approval',
    'meta_key' => '_b2b_status',
    'meta_value' => 'pending',
    'orderby' => 'registered',
    'order' => 'DESC'
));

?>
<style>
.webgsm-pending-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.webgsm-pending-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.webgsm-pending-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.webgsm-pending-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.webgsm-pending-table tbody tr:hover {
    background: #f9fafb;
}

.webgsm-pending-table tbody tr:last-child td {
    border-bottom: none;
}

.webgsm-status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.webgsm-status-pending {
    background: #fef3c7;
    color: #92400e;
}

.webgsm-action-btn {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    margin-right: 6px;
}

.webgsm-btn-view {
    background: #3b82f6;
    color: #fff;
}

.webgsm-btn-view:hover {
    background: #2563eb;
    color: #fff;
}

.webgsm-btn-approve {
    background: #22c55e;
    color: #fff;
}

.webgsm-btn-approve:hover {
    background: #16a34a;
    color: #fff;
}

.webgsm-btn-reject {
    background: #ef4444;
    color: #fff;
}

.webgsm-btn-reject:hover {
    background: #dc2626;
    color: #fff;
}

.webgsm-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.webgsm-empty-state svg {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    opacity: 0.5;
}

.webgsm-notice {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}

.webgsm-notice-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #22c55e;
}

.webgsm-notice-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* Certificate Preview Modal */
.webgsm-cert-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.85);
    animation: fadeIn 0.2s ease;
}

.webgsm-cert-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.webgsm-cert-modal-content {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.webgsm-cert-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.webgsm-cert-modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #111827;
}

.webgsm-cert-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.webgsm-cert-modal-close:hover {
    background: #f3f4f6;
    color: #111827;
}

.webgsm-cert-modal-body {
    padding: 24px;
    overflow: auto;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9fafb;
}

.webgsm-cert-preview {
    width: 100%;
    height: 100%;
    min-height: 500px;
    border: none;
    border-radius: 8px;
    background: #fff;
}

.webgsm-cert-preview img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-clock" style="color: #f59e0b;"></span>
        Conturi B2B în Așteptare Aprobare
        <span style="font-size: 14px; font-weight: normal; color: #6b7280; margin-left: 10px;">
            (<?php echo count($pending_users); ?> conturi)
        </span>
    </h1>
    
    <div id="webgsm-notice-container"></div>
    
    <?php if (empty($pending_users)): ?>
        <div class="webgsm-empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 style="color: #6b7280; margin: 0 0 10px 0;">Nu există conturi în așteptare</h2>
            <p style="color: #9ca3af; margin: 0;">Toate conturile B2B au fost procesate.</p>
        </div>
    <?php else: ?>
        <table class="webgsm-pending-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume Firmă</th>
                    <th>CUI</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Data înregistrare</th>
                    <th>Certificat</th>
                    <th>Status</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_users as $user): 
                    $company = get_user_meta($user->ID, 'billing_company', true) ?: '-';
                    $cui = get_user_meta($user->ID, 'billing_cui', true) ?: '-';
                    $phone = get_user_meta($user->ID, 'billing_phone', true) ?: '-';
                    $cert_path = get_user_meta($user->ID, '_b2b_certificate_path', true);
                    $has_cert = !empty($cert_path) && file_exists($cert_path);
                    $cert_url = $file_upload->get_certificate_url($user->ID);
                ?>
                <tr data-user-id="<?php echo esc_attr($user->ID); ?>">
                    <td><?php echo esc_html($user->ID); ?></td>
                    <td><strong><?php echo esc_html($company); ?></strong></td>
                    <td><?php echo esc_html($cui); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html($phone); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($user->user_registered)); ?></td>
                    <td>
                        <?php if ($has_cert && $cert_url): ?>
                            <button type="button" class="webgsm-action-btn webgsm-btn-view webgsm-view-cert-btn" data-cert-url="<?php echo esc_url($cert_url); ?>" data-user-id="<?php echo esc_attr($user->ID); ?>">
                                <span class="dashicons dashicons-media-document" style="font-size: 14px; vertical-align: middle;"></span>
                                Vezi Certificat
                            </button>
                        <?php else: ?>
                            <span style="color: #ef4444; font-size: 12px; font-weight: 600;">⚠️ Lipsă</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="webgsm-status-badge webgsm-status-pending">Pending</span>
                    </td>
                    <td>
                        <button class="webgsm-action-btn webgsm-btn-approve" data-action="approve" data-user-id="<?php echo esc_attr($user->ID); ?>">
                            <span class="dashicons dashicons-yes-alt" style="font-size: 14px; vertical-align: middle;"></span>
                            Approve
                        </button>
                        <button class="webgsm-action-btn webgsm-btn-reject" data-action="reject" data-user-id="<?php echo esc_attr($user->ID); ?>">
                            <span class="dashicons dashicons-dismiss" style="font-size: 14px; vertical-align: middle;"></span>
                            Reject & Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var noticeContainer = $('#webgsm-notice-container');
    
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'webgsm-notice-success' : 'webgsm-notice-error';
        var notice = $('<div class="webgsm-notice ' + noticeClass + '">' + message + '</div>');
        noticeContainer.html(notice);
        
        setTimeout(function() {
            notice.fadeOut(300, function() {
                notice.remove();
            });
        }, 5000);
    }
    
    // Approve account
    $('.webgsm-btn-approve').on('click', function() {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        var $row = $btn.closest('tr');
        
        if (!confirm('Ești sigur că vrei să aprobi acest cont?')) {
            return;
        }
        
        $btn.prop('disabled', true).text('Se procesează...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'webgsm_approve_account',
                user_id: userId,
                nonce: '<?php echo wp_create_nonce('webgsm_approve_account'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Reload if no more rows
                        if ($('.webgsm-pending-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    showNotice(response.data.message || 'Eroare la aprobare.', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt" style="font-size: 14px; vertical-align: middle;"></span> Approve');
                }
            },
            error: function() {
                showNotice('Eroare de conexiune.', 'error');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt" style="font-size: 14px; vertical-align: middle;"></span> Approve');
            }
        });
    });
    
    // Reject account
    $('.webgsm-btn-reject').on('click', function() {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        var $row = $btn.closest('tr');
        
        if (!confirm('Ești sigur că vrei să respingi și să ștergi acest cont? Această acțiune nu poate fi anulată.')) {
            return;
        }
        
        $btn.prop('disabled', true).text('Se procesează...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'webgsm_reject_account',
                user_id: userId,
                delete_user: '1',
                nonce: '<?php echo wp_create_nonce('webgsm_reject_account'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Reload if no more rows
                        if ($('.webgsm-pending-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    showNotice(response.data.message || 'Eroare la respingere.', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-dismiss" style="font-size: 14px; vertical-align: middle;"></span> Reject & Delete');
                }
            },
            error: function() {
                showNotice('Eroare de conexiune.', 'error');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-dismiss" style="font-size: 14px; vertical-align: middle;"></span> Reject & Delete');
            }
        });
    });
    
    // Certificate preview modal
    var certModal = $('<div class="webgsm-cert-modal"><div class="webgsm-cert-modal-content"><div class="webgsm-cert-modal-header"><h3>Preview Certificat</h3><button class="webgsm-cert-modal-close">&times;</button></div><div class="webgsm-cert-modal-body"><iframe class="webgsm-cert-preview" src=""></iframe></div></div></div>');
    $('body').append(certModal);
    
    $('.webgsm-view-cert-btn').on('click', function() {
        var certUrl = $(this).data('cert-url');
        var userId = $(this).data('user-id');
        
        // Get file extension to determine if it's an image
        var isImage = certUrl.match(/\.(jpg|jpeg|png)$/i);
        
        if (isImage) {
            certModal.find('.webgsm-cert-preview').replaceWith('<img class="webgsm-cert-preview" src="' + certUrl + '" alt="Certificat">');
        } else {
            certModal.find('.webgsm-cert-preview').replaceWith('<iframe class="webgsm-cert-preview" src="' + certUrl + '"></iframe>');
        }
        
        certModal.addClass('active');
    });
    
    certModal.find('.webgsm-cert-modal-close').on('click', function() {
        certModal.removeClass('active');
    });
    
    certModal.on('click', function(e) {
        if ($(e.target).hasClass('webgsm-cert-modal')) {
            certModal.removeClass('active');
        }
    });
    
    // Close on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && certModal.hasClass('active')) {
            certModal.removeClass('active');
        }
    });
});
</script>
