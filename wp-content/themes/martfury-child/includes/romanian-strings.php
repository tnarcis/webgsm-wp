<?php
/**
 * Traducere în română pentru butoane, linkuri și texte WooCommerce / temă (suprascrie engleza).
 * Pe local: gettext nu prinde textele hardcodate în temă, deci folosim și buffer pe HTML.
 */
if (!defined('ABSPATH')) exit;

add_filter('gettext', 'webgsm_romanian_strings', 5, 3);
add_filter('gettext_with_context', 'webgsm_romanian_strings_with_context', 5, 4);
add_filter('ngettext', 'webgsm_romanian_strings_plural', 5, 5);

// Buffer pe HTML ca să înlocuim și textele hardcodate în temă (fără __()/_e())
add_action('template_redirect', 'webgsm_romanian_buffer_start', 0);
function webgsm_romanian_buffer_start() {
    if (is_admin() || wp_doing_ajax() || (function_exists('is_rest_api_request') && is_rest_api_request())) return;
    ob_start('webgsm_romanian_buffer_callback');
}
function webgsm_romanian_buffer_callback($html) {
    $ro = webgsm_get_romanian_strings();
    // Ordonează după lungime (texte lungi primele) ca să nu înlocuim "View" în "View All"
    uksort($ro, function($a, $b) { return strlen($b) - strlen($a); });
    foreach ($ro as $en => $ro_text) {
        if ($en !== $ro_text && strpos($html, $en) !== false) {
            $html = str_replace($en, $ro_text, $html);
        }
    }
    return $html;
}

function webgsm_get_romanian_strings() {
    return array(
        // WooCommerce & magazin
        'Add to cart'           => 'Adaugă în coș',
        'View cart'             => 'Vezi coșul',
        'Read more'             => 'Citește mai mult',
        'Shop'                  => 'Magazin',
        'Cart'                  => 'Coș',
        'Checkout'              => 'Finalizare comandă',
        'My account'            => 'Contul meu',
        'Search'                => 'Caută',
        'Home'                  => 'Acasă',
        'Products'              => 'Produse',
        'Product'               => 'Produs',
        'Price'                 => 'Preț',
        'Sale!'                 => 'Reducere!',
        'Select options'        => 'Alege opțiuni',
        'Browse categories'     => 'Categorii',
        'Category'              => 'Categorie',
        'Category:'             => 'Categorie:',
        'Categories'            => 'Categorii',
        'Description'           => 'Descriere',
        'Additional information' => 'Informații suplimentare',
        'Reviews'               => 'Recenzii',
        'Related products'      => 'Produse similare',
        'You may also like'      => 'Îți mai pot plăcea',
        'Order'                 => 'Comandă',
        'Orders'                => 'Comenzi',
        'Billing details'        => 'Date facturare',
        'Your order'            => 'Comanda ta',
        'Place order'           => 'Plasează comanda',
        'Proceed to checkout'   => 'Finalizează comanda',
        'Update cart'           => 'Actualizează coșul',
        'Apply coupon'          => 'Aplică cupon',
        'Coupon code'           => 'Cod cupon',
        'Quantity'              => 'Cantitate',
        'Subtotal'              => 'Subtotal',
        'Total'                 => 'Total',
        'Remove'                => 'Șterge',
        'Continue shopping'    => 'Continuă cumpărăturile',
        'No products in the cart'=> 'Nu există produse în coș',
        'Your cart is currently empty.' => 'Coșul tău este gol.',
        'Return to shop'        => 'Înapoi la magazin',
        'Order received'        => 'Comandă primită',
        'Thank you'             => 'Mulțumim',
        'Log out'               => 'Deconectare',
        'Log in'                => 'Autentificare',
        'Register'              => 'Înregistrare',
        'Username or email address' => 'Utilizator sau email',
        'Password'              => 'Parolă',
        'Remember me'           => 'Ține-mă minte',
        'Lost your password?'   => 'Ai uitat parola?',
        'Back to shop'          => 'Înapoi la magazin',
        'Filter'                => 'Filtrează',
        'Clear'                 => 'Resetează',
        'Sort by'               => 'Sortează după',
        'Default sorting'       => 'Sortare implicită',
        'Sort by popularity'    => 'După popularitate',
        'Sort by average rating'=> 'După rating',
        'Sort by latest'        => 'Cele mai noi',
        'Sort by price: low to high' => 'Preț: mic → mare',
        'Sort by price: high to low' => 'Preț: mare → mic',
        'Add to wishlist'       => 'Adaugă la favorite',
        'Browse wishlist'       => 'Vezi favorite',
        'Compare'               => 'Compară',
        'Quick view'            => 'Vizualizare rapidă',
        'Out of stock'          => 'Stoc epuizat',
        'In stock'              => 'În stoc',
        'Back'                  => 'Înapoi',
        'Previous'               => 'Anterior',
        'Next'                  => 'Următorul',
        'Close'                 => 'Închide',
        'Submit'                => 'Trimite',
        'Save'                  => 'Salvează',
        'Cancel'                => 'Anulează',
        'Edit'                  => 'Editează',
        'Delete'                => 'Șterge',
        'View'                  => 'Vizualizează',
        'Loading...'            => 'Se încarcă...',
        'No products were found matching your selection.' => 'Nu s-au găsit produse.',
        'Search results'       => 'Rezultate căutare',
        'Your order has been received.' => 'Comanda ta a fost primită.',
        'Free'                  => 'Gratuit',
        'New'                   => 'Nou',
        // Tema – linkuri, footer, produs
        'Quick Links'           => 'Linkuri rapide',
        'Tags:'                 => 'Etichete:',
        'Tags'                  => 'Etichete',
        'Roll over image to zoom in' => 'Treceți cu mouse-ul peste imagine pentru zoom',
        'click to open expand view' => 'click pentru vedere mărită',
        'View All'              => 'Vezi toate',
        'View all'              => 'Vezi toate',
        'All Rights Reserved'   => 'Toate drepturile rezervate',
        '© 2025 webgsm.ro. All Rights Reserved' => '© 2025 webgsm.ro. Toate drepturile rezervate',
        'Contact'               => 'Contact',
        'Contact Us'            => 'Contactați-ne',
        'About Us'              => 'Despre noi',
        'About'                 => 'Despre',
        'Follow Us'             => 'Urmăriți-ne',
        'Newsletter'            => 'Newsletter',
        'Subscribe'             => 'Abonare',
        'Enter your email'      => 'Introdu emailul',
        'Privacy Policy'        => 'Politica de confidențialitate',
        'Terms and Conditions'  => 'Termeni și condiții',
        'Terms of Use'          => 'Termeni de utilizare',
        'Sitemap'               => 'Harta site',
        'Follow'                => 'Urmărește',
        'Share'                 => 'Distribuie',
        'Share this'            => 'Distribuie',
        'Back to top'           => 'Înapoi sus',
        'Scroll to top'         => 'Sus',
        'Menu'                  => 'Meniu',
        'Navigation'            => 'Navigare',
        'More'                  => 'Mai mult',
        'Less'                  => 'Mai puțin',
        'Show more'             => 'Afișează mai mult',
        'Show less'             => 'Afișează mai puțin',
        'Read More'             => 'Citește mai mult',
        'Learn more'            => 'Află mai mult',
        'Learn More'            => 'Află mai mult',
        'Details'               => 'Detalii',
        'Information'          => 'Informații',
        'Support'               => 'Suport',
        'Help'                  => 'Ajutor',
        'FAQ'                   => 'Întrebări frecvente',
        'Shipping'              => 'Livrare',
        'Delivery'              => 'Livrare',
        'Payment'               => 'Plată',
        'Pay'                   => 'Plătește',
        'Buy'                   => 'Cumpără',
        'Buy now'               => 'Cumpără acum',
        'Add'                   => 'Adaugă',
        'Remove'                => 'Șterge',
        'Update'                => 'Actualizează',
        'Apply'                 => 'Aplică',
        'Done'                  => 'Gata',
        'Yes'                   => 'Da',
        'No'                    => 'Nu',
        'Error'                 => 'Eroare',
        'Success'               => 'Succes',
        'Warning'               => 'Atenție',
        'Notice'               => 'Notă',
        'Page'                  => 'Pagină',
        'Pages'                 => 'Pagini',
        'Archives'              => 'Arhive',
        'Categories'            => 'Categorii',
        'Tags'                  => 'Etichete',
        'Author'                => 'Autor',
        'Date'                  => 'Data',
        'Comments'              => 'Comentarii',
        'Leave a comment'       => 'Lasa un comentariu',
        'Reply'                 => 'Răspunde',
        'Send'                  => 'Trimite',
        'Name'                  => 'Nume',
        'Email'                 => 'Email',
        'Message'               => 'Mesaj',
        'Phone'                 => 'Telefon',
        'Address'               => 'Adresă',
        'City'                  => 'Oraș',
        'Country'               => 'Țara',
        'Postcode'              => 'Cod poștal',
        'State'                 => 'Județ',
        'Company'               => 'Companie',
        'Notes'                 => 'Note',
        'Optional'              => 'Opțional',
        'Required'              => 'Obligatoriu',
        'Welcome'               => 'Bine ați venit',
        'Hello'                 => 'Bună ziua',
        'Goodbye'               => 'La revedere',
        'Copyright'             => 'Drepturi de autor',
        'All rights reserved'   => 'Toate drepturile rezervate',
        'Powered by'            => 'Realizat cu',
        'Skip to content'       => 'Sari la conținut',
        'Open menu'             => 'Deschide meniul',
        'Close menu'            => 'Închide meniul',
    );
}

function webgsm_romanian_strings($translated, $text, $domain) {
    $ro = webgsm_get_romanian_strings();
    if (isset($ro[$text])) {
        return $ro[$text];
    }
    return $translated;
}

function webgsm_romanian_strings_with_context($translated, $text, $context, $domain) {
    return webgsm_romanian_strings($translated, $text, $domain);
}

function webgsm_romanian_strings_plural($translated, $single, $plural, $number, $domain) {
    $ro = array(
        '%s item' => array('%s produs', '%s produse'),
        '%s item(s)' => array('%s produs', '%s produse'),
    );
    $key = $single;
    if (isset($ro[$key])) {
        $choice = ($number == 1) ? 0 : 1;
        return sprintf($ro[$key][$choice], $number);
    }
    return $translated;
}
