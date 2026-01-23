<?php
/**
 * WebGSM - Reset Categories Setup
 * Script temporar pentru resetarea setup-ului categoriilor
 * 
 * INSTRUCȚIUNI:
 * 1. Accesează acest fișier direct în browser: /wp-content/themes/martfury-child/includes/reset-categories.php
 * 2. Sau rulează: wp eval-file wp-content/themes/martfury-child/includes/reset-categories.php
 * 3. ȘTERGE acest fișier după utilizare (pentru securitate)
 */

// Încarcă WordPress
// Path: includes/ -> martfury-child/ -> themes/ -> wp-content/ -> public/ (root)
// Deci: ../../../../wp-load.php

$current_dir = __FILE__;
$wp_load_paths = array(
    dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php', // ../../../../wp-load.php
    dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php', // Alternativ
    __DIR__ . '/../../../../wp-load.php', // Path relativ din includes/
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('❌ Nu s-a putut găsi wp-load.php. Path-uri încercate:<br>' . implode('<br>', $wp_load_paths));
}

// Verifică dacă utilizatorul este admin (pentru securitate)
if (!current_user_can('manage_options')) {
    die('❌ Acces interzis. Trebuie să fii administrator.');
}

// Șterge opțiunile
delete_option('webgsm_categories_installed');
delete_option('webgsm_categories_setup_result');

echo '<!DOCTYPE html>
<html>
<head>
    <title>WebGSM - Reset Categories</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; margin-top: 0; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info { background: #dbeafe; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; border-radius: 4px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ WebGSM - Reset Categories Setup</h1>
        
        <div class="success">
            <strong>✅ Opțiunea resetată cu succes!</strong>
            <p>Opțiunea <code>webgsm_categories_installed</code> a fost ștearsă.</p>
        </div>
        
        <div class="info">
            <strong>ℹ️ Ce urmează:</strong>
            <ul>
                <li>Scriptul de setup va rula automat la următoarea accesare a site-ului</li>
                <li>Toate categoriile vor fi create din nou</li>
                <li>Veți vedea un admin notice cu rezultatul</li>
            </ul>
        </div>
        
        <p><a href="' . admin_url() . '" style="display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px;">← Înapoi la Admin</a></p>
        
        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 12px;">
            <strong>⚠️ IMPORTANT:</strong> Șterge acest fișier după utilizare pentru securitate!
        </p>
    </div>
</body>
</html>';
