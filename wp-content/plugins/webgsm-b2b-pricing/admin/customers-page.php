<?php
/**
 * WebGSM B2B Pricing - Customers Page
 * Lista clienți B2B cu opțiune de schimbare manuală tier
 * 
 * @version 2.0
 */
if (!defined('ABSPATH')) exit;

// Procesare acțiuni (schimbare tier)
if (isset($_POST['webgsm_change_tier']) && wp_verify_nonce($_POST['webgsm_tier_nonce'], 'webgsm_change_tier')) {
    $user_id = intval($_POST['user_id']);
    $new_tier = sanitize_text_field($_POST['new_tier']);
    $old_tier = get_user_meta($user_id, '_pj_tier', true);
    
    if ($user_id && $new_tier) {
        update_user_meta($user_id, '_pj_tier', $new_tier);
        update_user_meta($user_id, '_pj_tier_changed_by_admin', current_time('mysql'));
        update_user_meta($user_id, '_pj_tier_admin_note', sanitize_textarea_field($_POST['admin_note'] ?? ''));
        
        // Log the change
        $log = get_user_meta($user_id, '_pj_tier_history', true);
        if (!is_array($log)) $log = array();
        $log[] = array(
            'date' => current_time('mysql'),
            'from' => $old_tier,
            'to' => $new_tier,
            'by' => get_current_user_id(),
            'note' => sanitize_textarea_field($_POST['admin_note'] ?? '')
        );
        update_user_meta($user_id, '_pj_tier_history', $log);
        
        echo '<div class="notice notice-success"><p>✅ Tier-ul a fost actualizat pentru utilizator #' . $user_id . ' de la <strong>' . ucfirst($old_tier) . '</strong> la <strong>' . ucfirst($new_tier) . '</strong>.</p></div>';
    }
}

// Obține clienții B2B
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_tier = isset($_GET['tier']) ? sanitize_text_field($_GET['tier']) : '';

$args = array(
    'meta_query' => array(
        'relation' => 'OR',
        array('key' => '_is_pj', 'value' => 'yes'),
        array('key' => '_tip_client', 'value' => 'pj'),
        array('key' => 'billing_cui', 'compare' => 'EXISTS')
    ),
    'number' => $per_page,
    'paged' => $paged,
    'orderby' => 'registered',
    'order' => 'DESC'
);

if ($search) {
    $args['search'] = '*' . $search . '*';
    $args['search_columns'] = array('user_login', 'user_email', 'display_name');
}

$user_query = new WP_User_Query($args);
$customers = $user_query->get_results();
$total = $user_query->get_total();
$total_pages = ceil($total / $per_page);

$tiers = get_option('webgsm_b2b_tiers', WebGSM_B2B_Pricing::instance()->get_default_tiers());
$tier_keys = array_keys($tiers);
?>

<style>
.webgsm-customers-page {
    max-width: 1400px;
}

.webgsm-customers-page h1 {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 0;
    color: #1f2937;
}

.customers-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card .stat-icon svg {
    width: 24px;
    height: 24px;
}

.stat-card .stat-info h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
}

.stat-card .stat-info p {
    margin: 4px 0 0;
    font-size: 13px;
    color: #6b7280;
}

.filters-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 16px 20px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.filters-bar input[type="search"] {
    flex: 1;
    min-width: 200px;
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.filters-bar select {
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    min-width: 150px;
}

.filters-bar button {
    padding: 10px 20px;
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filters-bar button:hover {
    background: #2563eb;
}

.customers-table {
    width: 100%;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    border-collapse: separate;
    border-spacing: 0;
    overflow: hidden;
}

.customers-table thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.customers-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
    vertical-align: middle;
}

.customers-table tbody tr:hover {
    background: #f9fafb;
}

.customers-table tbody tr:last-child td {
    border-bottom: none;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.customer-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    font-size: 14px;
}

.customer-details h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.customer-details p {
    margin: 2px 0 0;
    font-size: 12px;
    color: #6b7280;
}

/* Tier Badges */
.tier-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.tier-badge.bronze {
    background: linear-gradient(135deg, #d4a574, #a67c52);
    color: #4a3728;
}

.tier-badge.silver {
    background: linear-gradient(135deg, #e8e8e8, #a8a8a8);
    color: #3d3d3d;
}

.tier-badge.gold {
    background: linear-gradient(135deg, #f7e199, #c5a028);
    color: #5c4813;
}

.tier-badge.platinum {
    background: linear-gradient(135deg, #2c3e50, #0d1318);
    color: #e5e5e5;
}

.value-cell {
    font-weight: 600;
    color: #3b82f6;
}

.date-cell {
    color: #6b7280;
    font-size: 13px;
}

/* Actions */
.action-btn {
    padding: 6px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    color: #374151;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.action-btn.change-tier {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #2563eb;
}

.action-btn.change-tier:hover {
    background: #dbeafe;
}

/* Modal */
.tier-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999999;
    align-items: center;
    justify-content: center;
}

.tier-modal.active {
    display: flex;
}

.tier-modal-content {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    max-width: 480px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.tier-modal-content h3 {
    margin: 0 0 20px;
    font-size: 20px;
    color: #1f2937;
}

.tier-modal-content label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
}

.tier-modal-content select,
.tier-modal-content textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 16px;
}

.tier-modal-content textarea {
    min-height: 80px;
    resize: vertical;
}

.tier-modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.tier-modal-actions button {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tier-modal-actions .cancel-btn {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    color: #374151;
}

.tier-modal-actions .save-btn {
    background: #3b82f6;
    border: none;
    color: #fff;
}

.tier-modal-actions .save-btn:hover {
    background: #2563eb;
}

/* Pagination */
.pagination-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 12px;
}

.pagination-info {
    color: #6b7280;
    font-size: 14px;
}

.pagination-links {
    display: flex;
    gap: 8px;
}

.pagination-links a,
.pagination-links span {
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s ease;
}

.pagination-links a:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.pagination-links .current {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}
</style>

<div class="wrap webgsm-customers-page">
    <h1>
        <span class="dashicons dashicons-groups" style="color: #3b82f6;"></span>
        Clienți B2B
        <span style="font-size: 12px; background: #dbeafe; color: #1d4ed8; padding: 4px 10px; border-radius: 20px; margin-left: 10px;">
            <?php echo $total; ?> clienți
        </span>
    </h1>
    
    <!-- Stats -->
    <div class="customers-stats">
        <?php
        // Calculează statistici per tier
        $tier_counts = array('bronze' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0);
        $all_b2b = new WP_User_Query(array(
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_is_pj', 'value' => 'yes'),
                array('key' => '_tip_client', 'value' => 'pj')
            ),
            'number' => -1,
            'fields' => 'ID'
        ));
        foreach ($all_b2b->get_results() as $uid) {
            $t = get_user_meta($uid, '_pj_tier', true) ?: 'bronze';
            if (isset($tier_counts[$t])) $tier_counts[$t]++;
        }
        
        $stat_configs = array(
            'bronze' => array('bg' => '#fef3c7', 'color' => '#d97706', 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z'),
            'silver' => array('bg' => '#f3f4f6', 'color' => '#6b7280', 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'),
            'gold' => array('bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0'),
            'platinum' => array('bg' => '#1e293b', 'color' => '#e2e8f0', 'icon' => 'M12 3l2.5 5.5L20 9.5l-4 4.5 1 6-5-3-5 3 1-6-4-4.5 5.5-1L12 3z')
        );
        
        foreach ($tier_counts as $tier => $count):
            $cfg = $stat_configs[$tier];
        ?>
        <div class="stat-card">
            <div class="stat-icon" style="background: <?php echo $cfg['bg']; ?>;">
                <svg viewBox="0 0 24 24" fill="none" stroke="<?php echo $cfg['color']; ?>" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $cfg['icon']; ?>"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3><?php echo $count; ?></h3>
                <p><?php echo ucfirst($tier); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Filters -->
    <form method="get" class="filters-bar">
        <input type="hidden" name="page" value="webgsm-b2b-customers">
        <input type="search" name="s" placeholder="Caută client (nume, email)..." value="<?php echo esc_attr($search); ?>">
        <select name="tier">
            <option value="">Toate nivelurile</option>
            <?php foreach ($tier_keys as $t): ?>
            <option value="<?php echo $t; ?>" <?php selected($filter_tier, $t); ?>><?php echo ucfirst($t); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">
            <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
            Filtrează
        </button>
    </form>
    
    <!-- Table -->
    <table class="customers-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Firmă / CUI</th>
                <th>Nivel</th>
                <th>Valoare comenzi</th>
                <th>Înregistrat</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                    Nu au fost găsiți clienți B2B.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($customers as $customer): 
                $tier = get_user_meta($customer->ID, '_pj_tier', true) ?: 'bronze';
                $company = get_user_meta($customer->ID, 'billing_company', true);
                $cui = get_user_meta($customer->ID, 'billing_cui', true);
                $total_value = WebGSM_B2B_Pricing::instance()->get_user_total_value($customer->ID);
                $initial = strtoupper(substr($customer->display_name ?: $customer->user_login, 0, 1));
            ?>
            <tr>
                <td>
                    <div class="customer-info">
                        <div class="customer-avatar"><?php echo $initial; ?></div>
                        <div class="customer-details">
                            <h4><?php echo esc_html($customer->display_name ?: $customer->user_login); ?></h4>
                            <p><?php echo esc_html($customer->user_email); ?></p>
                        </div>
                    </div>
                </td>
                <td>
                    <strong><?php echo esc_html($company ?: '-'); ?></strong><br>
                    <small style="color: #6b7280;"><?php echo esc_html($cui ?: '-'); ?></small>
                </td>
                <td>
                    <span class="tier-badge <?php echo $tier; ?>"><?php echo ucfirst($tier); ?></span>
                </td>
                <td class="value-cell">
                    <?php echo number_format($total_value, 0, ',', '.'); ?> RON
                </td>
                <td class="date-cell">
                    <?php echo date_i18n('d M Y', strtotime($customer->user_registered)); ?>
                </td>
                <td>
                    <button type="button" class="action-btn change-tier" 
                            data-user-id="<?php echo $customer->ID; ?>"
                            data-user-name="<?php echo esc_attr($customer->display_name ?: $customer->user_login); ?>"
                            data-current-tier="<?php echo $tier; ?>">
                        Schimbă nivel
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-wrap">
        <div class="pagination-info">
            Afișare <?php echo (($paged - 1) * $per_page) + 1; ?>-<?php echo min($paged * $per_page, $total); ?> din <?php echo $total; ?> clienți
        </div>
        <div class="pagination-links">
            <?php
            $base_url = admin_url('admin.php?page=webgsm-b2b-customers');
            if ($search) $base_url .= '&s=' . urlencode($search);
            if ($filter_tier) $base_url .= '&tier=' . urlencode($filter_tier);
            
            if ($paged > 1): ?>
            <a href="<?php echo esc_url($base_url . '&paged=' . ($paged - 1)); ?>">← Anterior</a>
            <?php endif;
            
            for ($i = max(1, $paged - 2); $i <= min($total_pages, $paged + 2); $i++):
                if ($i == $paged): ?>
                <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="<?php echo esc_url($base_url . '&paged=' . $i); ?>"><?php echo $i; ?></a>
                <?php endif;
            endfor;
            
            if ($paged < $total_pages): ?>
            <a href="<?php echo esc_url($base_url . '&paged=' . ($paged + 1)); ?>">Următor →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Schimbare Tier -->
<div class="tier-modal" id="tier-modal">
    <div class="tier-modal-content">
        <h3>Schimbă nivelul partener</h3>
        <form method="post">
            <?php wp_nonce_field('webgsm_change_tier', 'webgsm_tier_nonce'); ?>
            <input type="hidden" name="user_id" id="modal-user-id">
            
            <p style="margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                <strong id="modal-user-name"></strong>
                <br>
                <small style="color: #6b7280;">Nivel curent: <span id="modal-current-tier"></span></small>
            </p>
            
            <label for="new_tier">Nivel nou:</label>
            <select name="new_tier" id="new_tier" required>
                <?php foreach ($tier_keys as $t): ?>
                <option value="<?php echo $t; ?>"><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="admin_note">Notă administrator (opțional):</label>
            <textarea name="admin_note" id="admin_note" placeholder="Ex: Retrogradare manuală - lipsă activitate..."></textarea>
            
            <div style="padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; margin-top: 8px;">
                <p style="margin: 0; font-size: 13px; color: #991b1b;">
                    <strong>⚠️ Atenție:</strong> Această acțiune va fi înregistrată în istoricul clientului.
                </p>
            </div>
            
            <div class="tier-modal-actions">
                <button type="button" class="cancel-btn" onclick="closeTierModal()">Anulează</button>
                <button type="submit" name="webgsm_change_tier" class="save-btn">Salvează</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Deschide modal
    $('.change-tier').on('click', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var currentTier = $(this).data('current-tier');
        
        $('#modal-user-id').val(userId);
        $('#modal-user-name').text(userName);
        $('#modal-current-tier').text(currentTier.charAt(0).toUpperCase() + currentTier.slice(1));
        $('#new_tier').val(currentTier);
        
        $('#tier-modal').addClass('active');
    });
    
    // Închide modal la click outside
    $('#tier-modal').on('click', function(e) {
        if ($(e.target).is('#tier-modal')) {
            closeTierModal();
        }
    });
});

function closeTierModal() {
    jQuery('#tier-modal').removeClass('active');
}
</script>
