<?php
/**
 * WebGSM - Header Account Menu Customization
 * Modifică meniul dropdown din header + SVG icons + B2B Tier Badge
 * IMPORTANT: Încărcat PRIMUL în functions.php pentru a suprascrie tema părinte
 * 
 * @version 2.0 - Cu badge tier B2B
 */

if (!defined('ABSPATH')) exit;

// ==========================================
// DEZACTIVEAZĂ meniul WordPress custom
// ==========================================
add_filter('has_nav_menu', function($has_nav_menu, $location) {
    if ($location === 'user_logged') {
        return false;
    }
    return $has_nav_menu;
}, 10, 2);

// ==========================================
// OVERRIDE: Funcția martfury_nav_user_menu()
// ==========================================
if (!function_exists('martfury_nav_user_menu')) {
    function martfury_nav_user_menu() {
        $account = get_permalink(get_option('woocommerce_myaccount_page_id'));
        if (substr($account, -1, 1) != '/') {
            $account .= '/';
        }
        
        // SVG Icons - Line Art Style
        $icon_user = '<svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>';
        
        $icon_orders = '<svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>';
        
        $icon_location = '<svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>';
        
        $icon_tier = '<svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>';
        
        $user_menu = [];
        $user_menu[] = sprintf(
            '<ul>
            <li>
                <a href="%s">%s Administrează cont</a>
            </li>
            <li>
                <a href="%s">%s Istoric comenzi</a>
            </li>
            <li>
                <a href="%s">%s Adrese</a>
            </li>
            </ul>',
            esc_url($account . 'edit-account'),
            $icon_user,
            esc_url($account . 'orders'),
            $icon_orders,
            esc_url($account . 'adrese-salvate'),
            $icon_location
        );
        
        return $user_menu;
    }
}

// ==========================================
// BADGE B2B ÎN HEADER - LÂNGĂ NUMELE USERULUI
// ==========================================
add_filter('martfury_account_text', 'webgsm_add_tier_badge_to_account', 10, 1);
function webgsm_add_tier_badge_to_account($text) {
    if (!is_user_logged_in()) {
        return $text;
    }
    
    // Verifică dacă e PJ
    if (!function_exists('webgsm_b2b_pricing') || !class_exists('WebGSM_B2B_Pricing')) {
        return $text;
    }
    
    $b2b = WebGSM_B2B_Pricing::instance();
    if (!$b2b->is_user_pj()) {
        return $text;
    }
    
    $tier = $b2b->get_user_tier();
    if (!$tier) {
        return $text;
    }
    
    // Generează badge inline
    $badge = webgsm_get_header_tier_badge($tier);
    
    return $text . ' ' . $badge;
}

// ==========================================
// FUNCȚIE BADGE PENTRU HEADER (COMPACT)
// ==========================================
function webgsm_get_header_tier_badge($tier) {
    $configs = array(
        'bronze' => array(
            'bg' => 'linear-gradient(135deg, #d4a574, #a67c52)',
            'color' => '#4a3728',
            'border' => '#c9a077',
            'icon' => '◆'
        ),
        'silver' => array(
            'bg' => 'linear-gradient(135deg, #e8e8e8, #a8a8a8)',
            'color' => '#3d3d3d',
            'border' => '#d0d0d0',
            'icon' => '★'
        ),
        'gold' => array(
            'bg' => 'linear-gradient(135deg, #f7e199, #c5a028)',
            'color' => '#5c4813',
            'border' => '#dbb840',
            'icon' => '♕'
        ),
        'platinum' => array(
            'bg' => 'linear-gradient(135deg, #2c3e50, #0d1318)',
            'color' => '#e5e5e5',
            'border' => '#4a6073',
            'icon' => '◈'
        )
    );
    
    $tier = strtolower($tier);
    $config = isset($configs[$tier]) ? $configs[$tier] : $configs['bronze'];
    $label = ucfirst($tier);
    
    return sprintf(
        '<span class="webgsm-header-tier-badge tier-%s" style="
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            margin-left: 6px;
            background: %s;
            color: %s;
            border: 1px solid %s;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.4;
            vertical-align: middle;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        ">%s %s</span>',
        esc_attr($tier),
        $config['bg'],
        $config['color'],
        $config['border'],
        $config['icon'],
        esc_html($label)
    );
}

// ==========================================
// CSS + SVG ICONS pentru meniul header
// ==========================================
add_action('wp_head', function() {
    ?>
    <style>
    /* Header Account Menu - SVG Icons + Hover */
    .topbar-menu .extra-menu-item.account-item .account-links ul {
        padding: 5px 0 !important;
    }
    
    .topbar-menu .extra-menu-item.account-item .account-links li {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .topbar-menu .extra-menu-item.account-item .account-links li a {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 16px !important;
        transition: all 0.2s ease !important;
        color: #475569 !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        line-height: 1.3 !important;
        text-decoration: none !important;
    }
    
    /* HOVER - Albastru intens pe text SI iconiță */
    .topbar-menu .extra-menu-item.account-item .account-links li a:hover {
        background: #eff6ff !important;
        color: #3b82f6 !important;
    }
    
    .topbar-menu .extra-menu-item.account-item .account-links li a svg.menu-icon {
        width: 12px !important;
        height: 12px !important;
        min-width: 12px !important;
        max-width: 12px !important;
        min-height: 12px !important;
        max-height: 12px !important;
        flex-shrink: 0 !important;
        stroke: #475569 !important;
        transition: all 0.2s ease !important;
        vertical-align: middle !important;
        display: block !important;
    }
    
    /* HOVER - SVG devine albastru */
    .topbar-menu .extra-menu-item.account-item .account-links li a:hover svg.menu-icon,
    .topbar-menu .extra-menu-item.account-item .account-links li a:hover svg.menu-icon path {
        stroke: #3b82f6 !important;
    }
    
    /* ASCUNDE items nedorite din meniu */
    .topbar-menu .extra-menu-item.account-item .account-links li a[href*="retururi"],
    .topbar-menu .extra-menu-item.account-item .account-links li a[href*="garantie"],
    .topbar-menu .extra-menu-item.account-item .account-links li a[href*="date-facturare"] {
        display: none !important;
    }
    
    /* ==========================================
       B2B Tier Badge în Header - Elegant Design
       ========================================== */
    
    .webgsm-header-tier-badge {
        animation: badgeFadeIn 0.3s ease;
    }
    
    @keyframes badgeFadeIn {
        from {
            opacity: 0;
            transform: translateX(-5px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Hover effect pe badge */
    .webgsm-header-tier-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.15) !important;
    }
    
    /* Tier-specific hover glow */
    .webgsm-header-tier-badge.tier-bronze:hover {
        box-shadow: 0 3px 12px rgba(180, 140, 100, 0.4) !important;
    }
    
    .webgsm-header-tier-badge.tier-silver:hover {
        box-shadow: 0 3px 12px rgba(160, 160, 160, 0.5) !important;
    }
    
    .webgsm-header-tier-badge.tier-gold:hover {
        box-shadow: 0 3px 12px rgba(212, 175, 55, 0.5) !important;
    }
    
    .webgsm-header-tier-badge.tier-platinum:hover {
        box-shadow: 0 3px 12px rgba(44, 62, 80, 0.6) !important;
    }
    
    /* Platinum special shimmer effect */
    .webgsm-header-tier-badge.tier-platinum {
        position: relative;
        overflow: hidden;
    }
    
    .webgsm-header-tier-badge.tier-platinum::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 50%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        animation: platinumShimmer 3s infinite;
    }
    
    @keyframes platinumShimmer {
        0% { left: -100%; }
        50%, 100% { left: 200%; }
    }
    
    /* Responsive - ascunde pe mobil mic */
    @media (max-width: 480px) {
        .webgsm-header-tier-badge {
            display: none !important;
        }
    }
    </style>
    <?php
}, 100);

// ==========================================
// SALUT PERSONALIZAT CU BADGE ÎN DASHBOARD
// ==========================================
add_action('woocommerce_account_dashboard', 'webgsm_dashboard_welcome_with_badge', 5);
function webgsm_dashboard_welcome_with_badge() {
    if (!is_user_logged_in()) return;
    
    $user = wp_get_current_user();
    $display_name = $user->display_name ?: $user->user_login;
    
    // Verifică dacă e PJ
    $badge_html = '';
    if (function_exists('webgsm_b2b_pricing') && class_exists('WebGSM_B2B_Pricing')) {
        $b2b = WebGSM_B2B_Pricing::instance();
        if ($b2b->is_user_pj()) {
            $tier = $b2b->get_user_tier();
            if ($tier && function_exists('webgsm_get_tier_badge')) {
                $badge_html = webgsm_get_tier_badge($tier, 'dashboard');
            }
        }
    }
    
    ?>
    <div class="webgsm-dashboard-welcome" style="
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        padding: 20px 24px;
        margin-bottom: 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    ">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="
                width: 48px;
                height: 48px;
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 20px;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            ">
                <?php echo strtoupper(substr($display_name, 0, 1)); ?>
            </div>
            <div>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Bine ai venit,</p>
                <h2 style="margin: 0; font-size: 20px; color: #1f2937; font-weight: 600;">
                    <?php echo esc_html($display_name); ?>
                    <?php if ($badge_html): ?>
                        <span style="margin-left: 8px;"><?php echo $badge_html; ?></span>
                    <?php endif; ?>
                </h2>
            </div>
        </div>
        
        <?php if ($badge_html): ?>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" style="
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #475569;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        " onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6';" 
           onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
            </svg>
            Vezi progresul
        </a>
        <?php endif; ?>
    </div>
    
    <?php
    // Afișează progress bar dacă e PJ
    if ($badge_html && function_exists('webgsm_get_tier_progress_bar')) {
        echo webgsm_get_tier_progress_bar();
    }
}
