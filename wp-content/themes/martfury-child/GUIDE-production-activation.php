<?php
/**
 * GHID ACTIVARE SECURITATE PRODUCȚIE
 * 
 * Adaugă acest cod în themes/martfury-child/functions.php
 * la ÎNCEPUTUL fișierului, după <?php
 */

// =============================================
// DETECTARE AUTOMATĂ MEDIU (LOCAL vs LIVE)
// =============================================

// Verifică dacă e site LIVE (nu local)
$is_production = !in_array($_SERVER['HTTP_HOST'], [
    'localhost',
    '127.0.0.1',
    'webgsm.local',
    'local.webgsm.ro',
    // Adaugă alte domenii locale aici
]);

// Activează securitate DOAR pe LIVE
if ($is_production) {
    require_once get_stylesheet_directory() . '/includes/production-security.php';
}

/**
 * SAU - ACTIVARE MANUALĂ:
 * Decomentează linia de jos când urci pe LIVE
 */
// require_once get_stylesheet_directory() . '/includes/production-security.php';

?>
