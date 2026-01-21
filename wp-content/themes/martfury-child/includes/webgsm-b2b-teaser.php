<?php
/**
 * WebGSM B2B Teaser
 * Banner subtil pentru atragere clienți B2B (service GSM)
 * 
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit dacă accesat direct

/**
 * WebGSM B2B Teaser - Versiune subtilă pentru service GSM
 */

// ============================================
// A. BANNER SUBTIL PE PAGINA PRODUSULUI
// ============================================

add_action('woocommerce_single_product_summary', 'webgsm_b2b_teaser_single_product', 11);

function webgsm_b2b_teaser_single_product() {
    // Nu afișăm pentru utilizatori PJ (deja au cont B2B)
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $is_pj = get_user_meta($user_id, '_is_pj', true);
        if ($is_pj === 'yes') return;
    }
    
    global $product;
    if (!$product) return;
    
    $price_regular = $product->get_regular_price();
    if (!$price_regular || $price_regular <= 0) return;
    
    // Pentru utilizatori logați, pregătim datele pentru email
    $user_email = '';
    $user_name = '';
    $user_phone = '';
    $is_logged_in = is_user_logged_in();
    
    if ($is_logged_in) {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $user_email = $user->user_email;
        $user_name = trim($user->first_name . ' ' . $user->last_name);
        if (empty($user_name)) {
            $user_name = $user->display_name;
        }
        $user_phone = get_user_meta($user_id, 'billing_phone', true);
    }
    
    // Pregătește email pentru solicitare cont B2B
    $email_subject = 'Solicitare cont B2B - ' . ($user_name ?: 'Client WebGSM');
    $email_body = 'Bună ziua,' . "\n\n";
    $email_body .= 'Doresc să solicit un cont B2B pentru a beneficia de prețurile pentru parteneri.' . "\n\n";
    
    if ($is_logged_in) {
        $email_body .= 'Date cont existent:' . "\n";
        $email_body .= '- Email: ' . $user_email . "\n";
        if ($user_name) {
            $email_body .= '- Nume: ' . $user_name . "\n";
        }
        if ($user_phone) {
            $email_body .= '- Telefon: ' . $user_phone . "\n";
        }
        $email_body .= "\n";
    }
    
    $email_body .= 'Vă rog să-mi aprobați contul B2B. Atașez certificatul CUI sau documentul necesar.' . "\n\n";
    $email_body .= 'Mulțumesc!';
    
    ?>
    <div class="webgsm-b2b-teaser" style="
        margin: 10px 0;
        padding: 8px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 2px solid #94a3b8;
        border-radius: 4px;
        font-size: 12px;
        color: #64748b;
    ">
        <div style="color: #475569; line-height: 1.5;">
            <span style="color: #64748b;">Ești service GSM?</span>
            <strong style="color: #334155; font-weight: 500;"> Beneficiezi de prețuri B2B</strong>
            <span style="color: #64748b;"> și discounturi permanente pentru parteneri.</span>
        </div>
        
        <?php if ($is_logged_in) : ?>
            <!-- Pentru utilizatori logați: buton solicitare cont B2B -->
            <div style="margin-top: 8px;">
                <a href="mailto:info@webgsm.ro?subject=<?php echo rawurlencode($email_subject); ?>&body=<?php echo rawurlencode($email_body); ?>" 
                   style="
                       display: inline-block;
                       padding: 5px 10px;
                       background: #f1f5f9;
                       color: #475569;
                       font-size: 11px;
                       font-weight: 500;
                       border: 1px solid #cbd5e1;
                       border-radius: 3px;
                       text-decoration: none;
                       transition: all 0.2s;
                   "
                   onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8';"
                   onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';">
                    Solicită cont B2B
                </a>
            </div>
        <?php else : ?>
            <!-- Pentru utilizatori neautentificați: link către cont gratuit -->
            <div style="margin-top: 8px;">
                <a href="<?php echo esc_url(add_query_arg('tip_client', 'pj', wc_get_page_permalink('myaccount'))); ?>" 
                   style="
                       display: inline-block;
                       padding: 5px 10px;
                       color: #475569;
                       font-size: 11px;
                       text-decoration: underline;
                   "
                   onmouseover="this.style.color='#334155';"
                   onmouseout="this.style.color='#475569';">
                    Cont gratuit
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
