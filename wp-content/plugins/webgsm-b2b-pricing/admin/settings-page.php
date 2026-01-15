<?php
/**
 * WebGSM B2B Pricing - Settings Page
 * Version 2.0 - Cu sumă minimă și perioadă menținere
 */
if (!defined('ABSPATH')) exit;

$tiers = get_option('webgsm_b2b_tiers', WebGSM_B2B_Pricing::instance()->get_default_tiers());
$discount_implicit = get_option('webgsm_b2b_discount_implicit', 5);
$marja_minima = get_option('webgsm_b2b_marja_minima', 5);
$show_badge = get_option('webgsm_b2b_show_badge', 'yes');
$badge_text = get_option('webgsm_b2b_badge_text', 'Preț B2B');
$tier_retention = get_option('webgsm_b2b_tier_retention_months', 3);

// Salvare setări
if (isset($_POST['webgsm_b2b_save_settings']) && wp_verify_nonce($_POST['webgsm_b2b_nonce'], 'webgsm_b2b_save_settings')) {
    
    update_option('webgsm_b2b_discount_implicit', sanitize_text_field($_POST['discount_implicit']));
    update_option('webgsm_b2b_marja_minima', sanitize_text_field($_POST['marja_minima']));
    update_option('webgsm_b2b_show_badge', isset($_POST['show_badge']) ? 'yes' : 'no');
    update_option('webgsm_b2b_badge_text', sanitize_text_field($_POST['badge_text']));
    update_option('webgsm_b2b_tier_retention_months', intval($_POST['tier_retention']));
    
    // Tiers - ACUM CU min_value (SUMĂ)
    $new_tiers = array();
    foreach ($_POST['tier_name'] as $key => $name) {
        $slug = sanitize_title($name);
        $new_tiers[$slug] = array(
            'label' => sanitize_text_field($name),
            'min_value' => floatval($_POST['tier_min_value'][$key]),
            'discount_extra' => floatval($_POST['tier_discount'][$key])
        );
    }
    update_option('webgsm_b2b_tiers', $new_tiers);
    
    // Refresh variables
    $tiers = $new_tiers;
    $discount_implicit = get_option('webgsm_b2b_discount_implicit', 5);
    $marja_minima = get_option('webgsm_b2b_marja_minima', 5);
    $show_badge = get_option('webgsm_b2b_show_badge', 'yes');
    $badge_text = get_option('webgsm_b2b_badge_text', 'Preț B2B');
    $tier_retention = get_option('webgsm_b2b_tier_retention_months', 3);
    
    echo '<div class="notice notice-success"><p>✅ Setările au fost salvate!</p></div>';
}
?>

<style>
/* Settings Page Styles */
.webgsm-b2b-admin {
    max-width: 1200px;
}

.webgsm-b2b-admin h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px 0;
    color: #1f2937;
}

.webgsm-b2b-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.webgsm-b2b-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.webgsm-b2b-card .card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.webgsm-b2b-card .card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.webgsm-b2b-card .card-body {
    padding: 20px;
}

.webgsm-b2b-card .form-table th {
    width: 180px;
    padding: 12px 10px 12px 0;
    font-weight: 500;
    color: #374151;
}

.webgsm-b2b-card .form-table td {
    padding: 12px 0;
}

.webgsm-b2b-card input[type="number"],
.webgsm-b2b-card input[type="text"] {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 8px 12px;
    transition: all 0.2s ease;
}

.webgsm-b2b-card input[type="number"]:focus,
.webgsm-b2b-card input[type="text"]:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

/* Tiers Table */
#tiers-table {
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

#tiers-table thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 12px 16px;
    font-weight: 600;
    color: #374151;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

#tiers-table tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

#tiers-table tbody tr:last-child td {
    border-bottom: none;
}

#tiers-table tbody tr:hover {
    background: #f9fafb;
}

#tiers-table input {
    width: 100%;
    max-width: 150px;
}

#tiers-table .tier-name-input {
    max-width: 120px;
}

#tiers-table .tier-value-input {
    max-width: 120px;
}

#tiers-table .tier-discount-input {
    max-width: 80px;
}

.remove-tier {
    background: #fee2e2 !important;
    border: 1px solid #fecaca !important;
    color: #dc2626 !important;
    border-radius: 6px !important;
    width: 32px;
    height: 32px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-tier:hover {
    background: #fecaca !important;
    border-color: #f87171 !important;
}

#add-tier {
    margin-top: 12px;
    background: #eff6ff !important;
    border: 1px solid #bfdbfe !important;
    color: #2563eb !important;
    border-radius: 6px !important;
    padding: 8px 16px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease;
}

#add-tier:hover {
    background: #dbeafe !important;
    border-color: #93c5fd !important;
}

/* Tier Badges Preview */
.tier-badge-preview {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tier-badge-preview.bronze {
    background: linear-gradient(135deg, #d4a574 0%, #a67c52 100%);
    color: #4a3728;
}

.tier-badge-preview.silver {
    background: linear-gradient(135deg, #e8e8e8 0%, #a8a8a8 100%);
    color: #3d3d3d;
}

.tier-badge-preview.gold {
    background: linear-gradient(135deg, #f7e199 0%, #c5a028 100%);
    color: #5c4813;
}

.tier-badge-preview.platinum {
    background: linear-gradient(135deg, #2c3e50 0%, #0d1318 100%);
    color: #e5e5e5;
}

/* Info Card */
.info-card .card-body {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.info-flow {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.flow-step {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #bbf7d0;
}

.flow-step.final {
    background: #22c55e;
    color: #fff;
    border-color: #22c55e;
}

.flow-step .step-number {
    width: 24px;
    height: 24px;
    background: #22c55e;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.flow-step.final .step-number {
    background: #fff;
    color: #22c55e;
}

.flow-arrow {
    color: #22c55e;
    font-size: 18px;
    font-weight: bold;
}

/* Submit Button */
.submit .button-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    border: none !important;
    padding: 12px 32px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
    transition: all 0.2s ease !important;
}

.submit .button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4) !important;
}

/* Retention Info */
.retention-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    padding: 10px 14px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    font-size: 13px;
    color: #92400e;
}

.retention-info svg {
    flex-shrink: 0;
}
</style>

<div class="wrap webgsm-b2b-admin">
    <h1>
        <span class="dashicons dashicons-chart-line" style="color: #3b82f6;"></span>
        WebGSM B2B Pricing - Setări
        <span style="font-size: 12px; background: #dbeafe; color: #1d4ed8; padding: 4px 10px; border-radius: 20px; margin-left: 10px;">v2.0</span>
    </h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('webgsm_b2b_save_settings', 'webgsm_b2b_nonce'); ?>
        
        <div class="webgsm-b2b-cards">
            
            <!-- SETĂRI GENERALE -->
            <div class="webgsm-b2b-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-settings" style="color: #6b7280;"></span>
                        Setări Generale
                    </h2>
                </div>
                <div class="card-body">
                    <table class="form-table">
                        <tr>
                            <th><label for="discount_implicit">Discount implicit PJ (%)</label></th>
                            <td>
                                <input type="number" name="discount_implicit" id="discount_implicit" 
                                       value="<?php echo esc_attr($discount_implicit); ?>" 
                                       step="0.1" min="0" max="100" class="small-text">
                                <p class="description">Discount aplicat dacă produsul/categoria nu are discount specific.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="marja_minima">Marjă minimă profit (%)</label></th>
                            <td>
                                <input type="number" name="marja_minima" id="marja_minima" 
                                       value="<?php echo esc_attr($marja_minima); ?>" 
                                       step="0.1" min="0" max="100" class="small-text">
                                <p class="description">Marjă minimă adăugată la prețul de achiziție.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="show_badge">Afișează badge nivel</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_badge" id="show_badge" value="yes" 
                                           <?php checked($show_badge, 'yes'); ?>>
                                    Afișează badge-ul de nivel lângă preț
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- SISTEM TIERS -->
            <div class="webgsm-b2b-card" style="grid-column: span 2;">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-awards" style="color: #d97706;"></span>
                        Sistem Niveluri Parteneri (Tiers)
                    </h2>
                </div>
                <div class="card-body">
                    <p class="description" style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Clienții avansează automat în funcție de <strong>valoarea totală</strong> a comenzilor (în RON).
                        Discount-ul extra din tier se adaugă la discount-ul produs/categorie.
                    </p>
                    
                    <!-- Badges Preview -->
                    <div style="margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <span style="color: #6b7280; font-size: 13px; display: flex; align-items: center;">Preview badges:</span>
                        <span class="tier-badge-preview bronze">Bronze</span>
                        <span class="tier-badge-preview silver">Silver</span>
                        <span class="tier-badge-preview gold">Gold</span>
                        <span class="tier-badge-preview platinum">Platinum</span>
                    </div>
                    
                    <table class="widefat" id="tiers-table">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Nume Nivel</th>
                                <th style="width: 180px;">
                                    Sumă Minimă (RON)
                                    <span title="Valoarea totală a comenzilor necesară pentru a atinge acest nivel" style="cursor: help; color: #9ca3af;">ⓘ</span>
                                </th>
                                <th style="width: 140px;">Discount Extra (%)</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tiers as $slug => $tier): 
                                $min_value = isset($tier['min_value']) ? $tier['min_value'] : (isset($tier['min_orders']) ? $tier['min_orders'] * 500 : 0);
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="tier_name[]" value="<?php echo esc_attr($tier['label']); ?>" 
                                           class="tier-name-input" placeholder="Nume nivel">
                                </td>
                                <td>
                                    <input type="number" name="tier_min_value[]" value="<?php echo esc_attr($min_value); ?>" 
                                           min="0" step="100" class="tier-value-input" placeholder="0">
                                    <span style="color: #6b7280; font-size: 12px; margin-left: 4px;">RON</span>
                                </td>
                                <td>
                                    <input type="number" name="tier_discount[]" value="<?php echo esc_attr($tier['discount_extra']); ?>" 
                                           step="0.1" min="0" max="100" class="tier-discount-input">
                                    <span style="color: #6b7280; font-size: 12px; margin-left: 4px;">%</span>
                                </td>
                                <td>
                                    <button type="button" class="button remove-tier" title="Șterge nivel">&times;</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <button type="button" class="button" id="add-tier">
                        <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
                        Adaugă Nivel
                    </button>
                    
                    <!-- Tier Retention -->
                    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <h4 style="margin: 0 0 12px 0; color: #374151; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Menținere Nivel
                        </h4>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <label for="tier_retention" style="color: #374151;">Perioadă menținere:</label>
                            <input type="number" name="tier_retention" id="tier_retention" 
                                   value="<?php echo esc_attr($tier_retention); ?>" 
                                   min="1" max="24" style="width: 70px;">
                            <span style="color: #6b7280;">luni</span>
                        </div>
                        <div class="retention-info">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Odată atins un nivel, clientul îl păstrează minimum <strong><?php echo $tier_retention; ?> luni</strong>. 
                            Retrogradarea se face <strong>doar manual</strong> de către administrator.</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- INFO BOX -->
            <div class="webgsm-b2b-card info-card" style="grid-column: span 2;">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-info" style="color: #22c55e;"></span>
                        Cum funcționează?
                    </h2>
                </div>
                <div class="card-body">
                    <div class="info-flow">
                        <div class="flow-step">
                            <span class="step-number">1</span>
                            <span class="step-text">Preț retail (PF)</span>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-step">
                            <span class="step-number">2</span>
                            <span class="step-text">- Discount PJ (produs/categorie/implicit)</span>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-step">
                            <span class="step-number">3</span>
                            <span class="step-text">- Discount Tier (Bronze/Silver/Gold/Platinum)</span>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-step">
                            <span class="step-number">4</span>
                            <span class="step-text">Verificare preț minim (HARD LIMIT)</span>
                        </div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-step final">
                            <span class="step-number">✓</span>
                            <span class="step-text">Preț Final B2B</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 16px; background: #fff; border-radius: 8px; border: 1px solid #bbf7d0;">
                        <h4 style="margin: 0 0 12px 0; color: #166534;">📊 Exemplu calcul pentru client Gold:</h4>
                        <code style="display: block; white-space: pre-line; font-size: 13px; color: #374151; line-height: 1.8;">
Preț retail: 1.000 RON
Discount PJ categorie: 10%
Discount Tier Gold: +5% extra
────────────────────
Preț calculat: 1.000 - 15% = <strong style="color: #2563eb;">850 RON</strong>
Preț minim setat: 800 RON
────────────────────
Preț final: <strong style="color: #22c55e;">850 RON</strong> ✓ (peste minim)
                        </code>
                    </div>
                </div>
            </div>
            
        </div>
        
        <p class="submit">
            <button type="submit" name="webgsm_b2b_save_settings" class="button button-primary button-large">
                <span class="dashicons dashicons-saved" style="vertical-align: middle; margin-right: 6px;"></span>
                Salvează Setările
            </button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Adaugă tier
    $('#add-tier').on('click', function() {
        var newRow = '<tr>' +
            '<td><input type="text" name="tier_name[]" value="" class="tier-name-input" placeholder="Nume nivel"></td>' +
            '<td><input type="number" name="tier_min_value[]" value="0" min="0" step="100" class="tier-value-input"><span style="color: #6b7280; font-size: 12px; margin-left: 4px;">RON</span></td>' +
            '<td><input type="number" name="tier_discount[]" value="0" step="0.1" min="0" max="100" class="tier-discount-input"><span style="color: #6b7280; font-size: 12px; margin-left: 4px;">%</span></td>' +
            '<td><button type="button" class="button remove-tier" title="Șterge nivel">&times;</button></td>' +
            '</tr>';
        $('#tiers-table tbody').append(newRow);
    });
    
    // Șterge tier
    $(document).on('click', '.remove-tier', function() {
        if ($('#tiers-table tbody tr').length > 1) {
            $(this).closest('tr').fadeOut(200, function() { $(this).remove(); });
        } else {
            alert('Trebuie să existe cel puțin un nivel.');
        }
    });
    
    // Update retention info
    $('#tier_retention').on('change', function() {
        var months = $(this).val();
        $('.retention-info strong').first().text(months + ' luni');
    });
});
</script>
