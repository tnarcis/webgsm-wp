<?php
/**
 * WebGSM - Setup ACF Fields
 * Creează câmpurile ACF pentru produsele WooCommerce
 * 
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit dacă accesat direct

/**
 * Verifică dacă ACF este activ
 */
if (!function_exists('acf_add_local_field_group')) {
    return; // ACF nu este activ
}

/**
 * GRUP 1: Specificații Tehnice
 * Vizibil pe frontend în tab produs
 */
acf_add_local_field_group(array(
    'key' => 'group_specificatii_tehnice',
    'title' => 'Specificații Tehnice',
    'fields' => array(
        array(
            'key' => 'field_coduri_compatibilitate',
            'label' => 'Coduri Compatibilitate Apple/Samsung',
            'name' => 'coduri_compatibilitate',
            'type' => 'textarea',
            'instructions' => 'Codurile modelului (separate prin virgulă). Se afișează în tab și schema SEO.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'placeholder' => 'A2633, A2482, A2631, A2634, A2635',
            'maxlength' => '',
            'rows' => 4,
            'new_lines' => '',
        ),
        array(
            'key' => 'field_ic_movable',
            'label' => 'IC Movable',
            'name' => 'ic_movable',
            'type' => 'true_false',
            'instructions' => 'Ecranul are IC transferabil de pe ecranul original?',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'message' => '',
            'default_value' => 0,
            'ui' => 1,
            'ui_on_text' => 'Da',
            'ui_off_text' => 'Nu',
        ),
        array(
            'key' => 'field_truetone_support',
            'label' => 'TrueTone Support',
            'name' => 'truetone_support',
            'type' => 'true_false',
            'instructions' => 'Suportă funcția TrueTone după montaj?',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'message' => '',
            'default_value' => 0,
            'ui' => 1,
            'ui_on_text' => 'Da',
            'ui_off_text' => 'Nu',
        ),
        array(
            'key' => 'field_garantie_luni',
            'label' => 'Garanție (luni)',
            'name' => 'garantie_luni',
            'type' => 'select',
            'instructions' => 'Garanție în luni. B2C minim 24 luni conform legii. Sincronizat cu sistemul de garanții.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                '0' => 'Fără garanție',
                '1' => '1 lună',
                '3' => '3 luni',
                '6' => '6 luni',
                '12' => '12 luni (1 an)',
                '24' => '24 luni (2 ani)',
                '36' => '36 luni (3 ani)',
                '48' => '48 luni (4 ani)',
                '60' => '60 luni (5 ani)',
                'lifetime' => 'Lifetime (pe viață)',
            ),
            'default_value' => '24',
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 0,
            'return_format' => 'value',
            'ajax' => 0,
            'placeholder' => '',
        ),
        array(
            'key' => 'field_locatie_stoc',
            'label' => 'Locație Stoc',
            'name' => 'locatie_stoc',
            'type' => 'select',
            'instructions' => 'De unde se livrează produsul.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'magazin_webgsm' => 'Magazin WebGSM (stoc fizic)',
                'depozit_central' => 'Depozit Central (Timișoara 24h)',
                'furnizor_extern' => 'Furnizor Extern (48-72h)',
            ),
            'default_value' => 'depozit_central',
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 0,
            'return_format' => 'value',
            'ajax' => 0,
            'placeholder' => '',
        ),
        array(
            'key' => 'field_timp_livrare',
            'label' => 'Timp Livrare',
            'name' => 'timp_livrare',
            'type' => 'select',
            'instructions' => 'Timpul estimat de livrare.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'choices' => array(
                'imediat' => 'Livrare Imediată',
                '24h' => 'Livrare 24h',
                '48h' => 'Livrare 48-72h',
            ),
            'default_value' => '24h',
            'allow_null' => 0,
            'multiple' => 0,
            'ui' => 0,
            'return_format' => 'value',
            'ajax' => 0,
            'placeholder' => '',
        ),
        array(
            'key' => 'field_caracteristici_tehnice',
            'label' => 'Caracteristici Tehnice Adiționale',
            'name' => 'caracteristici_tehnice',
            'type' => 'textarea',
            'instructions' => 'Una per linie, format: Nume | Valoare. Exemple: "Tensiune alimentare | 11.4 V", "Capacitate | 6680 mAh", "Rezoluție | 12 MP". Se afișează în tab-ul Specificații Tehnice.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'default_value' => '',
            'placeholder' => "Tensiune alimentare | 11.4 V\nCapacitate | 6680 mAh\nCuloare | Negru",
            'maxlength' => '',
            'rows' => 6,
            'new_lines' => '',
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'product',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => true,
    'description' => 'Specificații tehnice pentru produse - vizibile pe frontend',
));

/**
 * Date Gestiune: integrate în tab Inventory (product-inventory-gestiune.php).
 * EAN/GTIN = _global_unique_id (WC) + sincronizat în gtin_ean.
 * SKU Furnizor, Furnizor Activ, Preț Achiziție sunt în Inventory; Preț = _pret_achizitie (B2B).
 */
