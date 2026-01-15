<?php
if (!defined('ABSPATH')) exit;

// Stats rapide
$total_b2b_users = count(get_users(array(
    'meta_query' => array(
        'relation' => 'OR',
        array('key' => '_is_pj', 'value' => 'yes'),
        array('key' => 'billing_cui', 'compare' => 'EXISTS')
    ),
    'fields' => 'ID'
)));

// Comenzi B2B luna curentă
global $wpdb;
$month_start = date('Y-m-01 00:00:00');
$orders_this_month = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'shop_order'
     AND p.post_status IN ('wc-completed', 'wc-processing')
     AND p.post_date >= %s
     AND pm.meta_key = '_tip_client'
     AND pm.meta_value = 'pj'",
    $month_start
));
?>

<div class="wrap webgsm-b2b-admin">
    <h1><span class="dashicons dashicons-chart-area"></span> Rapoarte B2B</h1>
    
    <div class="webgsm-b2b-cards stats-cards">
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo intval($total_b2b_users); ?></span>
                <span class="stat-label">Clienți B2B</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-cart"></span></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo intval($orders_this_month); ?></span>
                <span class="stat-label">Comenzi B2B luna aceasta</span>
            </div>
        </div>
    </div>
    
    <p><em>Rapoarte detaliate vor fi disponibile în versiunile viitoare.</em></p>
</div>
