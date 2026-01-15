<?php
/**
 * WebGSM B2B Pricing - Test Script
 * Accesează: /wp-content/plugins/webgsm-b2b-pricing/test-pricing.php?run=1
 */

// Load WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('Acces interzis - doar admin');
}

if (!isset($_GET['run'])) {
    die('Adaugă ?run=1 la URL');
}

echo '<pre style="font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 8px;">';
echo "═══════════════════════════════════════════════════════\n";
echo "    WebGSM B2B PRICING - TEST COMPLET\n";
echo "═══════════════════════════════════════════════════════\n\n";

$b2b = WebGSM_B2B_Pricing::instance();
$user_id = get_current_user_id();

// Test 1: User Status
echo "▶ TEST 1: STATUS USER\n";
echo "─────────────────────────────────────────────────────\n";
echo "User ID: {$user_id}\n";
echo "Is PJ: " . ($b2b->is_user_pj() ? '✅ DA' : '❌ NU') . "\n";
echo "Tier: " . ($b2b->get_user_tier() ?: 'N/A') . "\n";
echo "Total comenzi: " . number_format($b2b->get_user_total_value($user_id), 2) . " RON\n\n";

// Test 2: Tiers Config
echo "▶ TEST 2: CONFIGURARE TIERS\n";
echo "─────────────────────────────────────────────────────\n";
$tiers = get_option('webgsm_b2b_tiers', $b2b->get_default_tiers());
foreach ($tiers as $slug => $tier) {
    $min = isset($tier['min_value']) ? $tier['min_value'] : 0;
    $discount = isset($tier['discount_extra']) ? $tier['discount_extra'] : 0;
    echo "  {$tier['label']}: min " . number_format($min, 0) . " RON → +{$discount}% discount\n";
}
echo "\n";

// Test 3: Calcul Preț (ia primele 3 produse)
echo "▶ TEST 3: CALCUL PREȚURI (3 produse random)\n";
echo "─────────────────────────────────────────────────────\n";

$products = wc_get_products(['limit' => 3, 'status' => 'publish']);
$errors = [];

foreach ($products as $product) {
    $id = $product->get_id();
    $name = substr($product->get_name(), 0, 30);
    
    // Prețuri din meta (ORIGINALE)
    $price_meta = get_post_meta($id, '_regular_price', true);
    $pret_minim = get_post_meta($id, '_pret_minim_vanzare', true);
    $discount_pj = get_post_meta($id, '_discount_pj', true);
    
    // Preț calculat de plugin
    $price_calculated = $b2b->calculate_b2b_price((float)$price_meta, $product);
    
    // Preț din WooCommerce (ce vede clientul)
    $price_wc = $product->get_price();
    
    echo "\n  📦 #{$id}: {$name}\n";
    echo "     Preț meta (original): " . number_format((float)$price_meta, 2) . " RON\n";
    echo "     Preț minim setat: " . ($pret_minim ? number_format((float)$pret_minim, 2) . " RON" : "Nu e setat") . "\n";
    echo "     Discount PJ produs: " . ($discount_pj !== '' ? "{$discount_pj}%" : "Din categorie/implicit") . "\n";
    echo "     Preț B2B calculat: " . number_format($price_calculated, 2) . " RON\n";
    echo "     Preț WC (afișat): " . number_format((float)$price_wc, 2) . " RON\n";
    
    // Verificări
    if ($pret_minim && $price_calculated < (float)$pret_minim) {
        $errors[] = "❌ EROARE #{$id}: Preț ({$price_calculated}) SUB MINIM ({$pret_minim})!";
        echo "     ⚠️  EROARE: Sub preț minim!\n";
    } else {
        echo "     ✅ OK\n";
    }
    
    // Test aplicare multiplă
    $price_double = $b2b->calculate_b2b_price($price_calculated, $product);
    if (abs($price_double - $price_calculated) > 0.01) {
        $errors[] = "❌ EROARE #{$id}: Discount aplicat DUBLU! ({$price_calculated} → {$price_double})";
        echo "     ⚠️  EROARE: Discount dublu detectat!\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "    REZULTAT FINAL\n";
echo "═══════════════════════════════════════════════════════\n";

if (empty($errors)) {
    echo "✅ TOATE TESTELE AU TRECUT!\n";
    echo "   Prețurile se calculează corect.\n";
    echo "   Preț minim respectat.\n";
    echo "   Nu există discount dublu.\n";
} else {
    echo "❌ S-AU GĂSIT " . count($errors) . " ERORI:\n\n";
    foreach ($errors as $error) {
        echo "   {$error}\n";
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "Test finalizat la: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════\n";
echo '</pre>';
