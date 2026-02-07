<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap webgsm-tools">
    <h1>WebGSM Tools</h1>
    <p class="description">Instrumente pentru verificare și procesare produse.</p>
    <div class="webgsm-card" style="max-width: 600px;">
        <h2>Module</h2>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 12px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-reviewer')); ?>">📦 Product Reviewer</a>
                <br><small>Verifică și corectează produse din CSV înainte de import.</small>
            </li>
            <li>
                <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-studio')); ?>">🎨 Image Studio</a>
                <br><small>Adaugă badge-uri și logo-uri pe imaginile produselor.</small>
            </li>
        </ul>
    </div>
</div>
