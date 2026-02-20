<?php
/**
 * Traducere în română pentru butoane, linkuri și texte WooCommerce / temă (suprascrie engleza).
 * Pe local: gettext nu prinde textele hardcodate în temă, deci folosim și buffer pe HTML.
 */
if (!defined('ABSPATH')) exit;

add_filter('gettext', 'webgsm_romanian_strings', 5, 3);
add_filter('gettext_with_context', 'webgsm_romanian_strings_with_context', 5, 4);
add_filter('ngettext', 'webgsm_romanian_strings_plural', 5, 5);

// iTelefon → iPhone peste tot (titluri, conținut, meniu, widget-uri)
add_filter('the_title', 'webgsm_fix_itelefon', 10, 2);
add_filter('the_content', 'webgsm_fix_itelefon', 10, 1);
add_filter('nav_menu_item_title', 'webgsm_fix_itelefon', 10, 1);
add_filter('widget_title', 'webgsm_fix_itelefon', 10, 1);
add_filter('get_the_archive_title', 'webgsm_fix_itelefon', 10, 1);
function webgsm_fix_itelefon($text) {
    if (!is_string($text)) return $text;
    return str_replace(array('iTelefon', 'ITelefon', 'itelefon'), 'iPhone', $text);
}

// Înlocuiri în DOM pentru texte randate via JavaScript (buffer-ul nu le prinde)
add_action('wp_footer', 'webgsm_romanian_dom_replacements', 999);
function webgsm_romanian_dom_replacements() {
    if (is_admin() || wp_doing_ajax()) return;
    ?>
    <script>
    (function() {
        var r = [
            ['Produse found', 'Produse găsite'],
            ['Nuu', 'Nou'],
            ['Your Recently Viewed Products', 'Produse vizualizate recent'],
            ['Your Recently Viewed Products.', 'Produse vizualizate recent.'],
            ['View All', 'Vezi toate'],
            ['View all', 'Vezi toate'],
            ['iTelefon', 'iPhone'],
            ['ITelefon', 'iPhone'],
            ['itelefon', 'iPhone'],
            ['Până latal', 'Total'],
            ['Pana latal', 'Total']
        ];
        function inFooter(n) {
            if (!n || n.nodeType !== 1) return false;
            if (n.closest) return n.closest('footer, .site-footer, #colophon') !== null;
            var p = n.parentElement;
            while (p) {
                if (p.tagName === 'FOOTER' || p.classList.contains('site-footer') || p.id === 'colophon') return true;
                p = p.parentElement;
            }
            return false;
        }
        function walk(node) {
            if (node.nodeType === 1 && inFooter(node)) return;
            if (node.nodeType === 3) {
                var text = node.data;
                for (var i = 0; i < r.length; i++) {
                    text = text.split(r[i][0]).join(r[i][1]);
                }
                if (text !== node.data) node.data = text;
            } else if (node.nodeType === 1 && !/^(script|style|textarea)$/i.test(node.tagName)) {
                for (var c = node.firstChild; c; c = c.nextSibling) walk(c);
            }
        }
        if (document.body) walk(document.body);
        document.addEventListener('DOMContentLoaded', function() {
            walk(document.body);
            var obs = new MutationObserver(function(mutations) {
                mutations.forEach(function(m) {
                    for (var i = 0; i < m.addedNodes.length; i++) walk(m.addedNodes[i]);
                });
            });
            if (document.body) obs.observe(document.body, { childList: true, subtree: true });
        });
    })();
    </script>
    <?php
}

// Buffer pe HTML ca să înlocuim și textele hardcodate în temă (fără __()/_e())
add_action('template_redirect', 'webgsm_romanian_buffer_start', 0);
function webgsm_romanian_buffer_start() {
    if (is_admin() || wp_doing_ajax() || (function_exists('is_rest_api_request') && is_rest_api_request())) return;
    ob_start('webgsm_romanian_buffer_callback');
}
function webgsm_romanian_buffer_callback($html) {
    // Protejează <style> și <script> ca să nu stricăm CSS/JS (ex. "Back" în "background")
    $protected = array();
    $html = preg_replace_callback('#<(style|script)[^>]*>.*?</\1>#is', function($m) use (&$protected) {
        $key = '%%WEBGSM_PROT_' . count($protected) . '%%';
        $protected[$key] = $m[0];
        return $key;
    }, $html);
    // Exclude footer: conținutul din Personalizare să nu fie suprascris de traduceri
    $footer_placeholders = array();
    $html = preg_replace_callback('#<footer[^>]*>.*?</footer>#is', function($m) use (&$footer_placeholders) {
        $key = '%%WEBGSM_FOOTER_' . count($footer_placeholders) . '%%';
        $footer_placeholders[$key] = $m[0];
        return $key;
    }, $html);
    $ro = webgsm_get_romanian_strings();
    uksort($ro, function($a, $b) { return strlen($b) - strlen($a); });
    foreach ($ro as $en => $ro_text) {
        if ($en !== $ro_text && strpos($html, $en) !== false) {
            $html = str_replace($en, $ro_text, $html);
        }
    }
    foreach ($footer_placeholders as $key => $content) {
        $html = str_replace($key, $content, $html);
    }
    foreach ($protected as $key => $content) {
        $html = str_replace($key, $content, $html);
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
        'Leave a comment'       => 'Lasă un comentariu',
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

        // Variante majuscule / formulări alternative
        'View Cart'             => 'Vezi coșul',
        'My Account'             => 'Contul meu',
        'Billing address'       => 'Adresă facturare',
        'Billing Address'       => 'Adresă facturare',
        'Shipping address'      => 'Adresă livrare',
        'Shipping Address'      => 'Adresă livrare',
        'Ship to a different address?' => 'Livrare la altă adresă?',
        'First name'            => 'Prenume',
        'Last name'             => 'Nume',
        'Order notes'           => 'Note comandă',
        'Order Notes'            => 'Note comandă',
        'Order notes (optional)' => 'Note comandă (opțional)',
        'Product name'          => 'Denumire produs',
        'Item'                  => 'Produs',
        'Items'                 => 'Produse',
        'Discount'              => 'Reducere',
        'Order total'           => 'Total comandă',
        'Order Total'           => 'Total comandă',
        'Payment method'       => 'Metodă de plată',
        'Payment Method'        => 'Metodă de plată',
        'Shipping method'       => 'Metodă de livrare',
        'Shipping Method'        => 'Metodă de livrare',
        'Your order'            => 'Comanda ta',
        'Order date'            => 'Data comenzii',
        'Order number'          => 'Număr comandă',
        'Order Number'          => 'Număr comandă',
        'Order details'         => 'Detalii comandă',
        'Order Details'         => 'Detalii comandă',
        'View order'            => 'Vezi comanda',
        'View Order'            => 'Vezi comanda',
        'Order status'          => 'Status comandă',
        'Order Status'          => 'Status comandă',
        'Billing &amp; Shipping' => 'Facturare și livrare',
        'Billing & Shipping'    => 'Facturare și livrare',

        // Cont / My Account
        'Account details'       => 'Detalii cont',
        'Account Details'       => 'Detalii cont',
        'Edit address'          => 'Editează adresa',
        'Edit Address'          => 'Editează adresa',
        'Addresses'             => 'Adrese',
        'Change password'       => 'Schimbă parola',
        'Change Password'       => 'Schimbă parola',
        'Current password'      => 'Parola curentă',
        'New password'          => 'Parolă nouă',
        'Confirm password'      => 'Confirmă parola',
        'Dashboard'             => 'Panou',
        'Download'              => 'Descarcă',
        'Downloads'             => 'Descărcări',
        'No downloads available yet.' => 'Nu există descărcări disponibile.',
        'No order has been made yet.' => 'Nu ați plasat încă nicio comandă.',
        'Recent orders'         => 'Comenzi recente',
        'Logout'                => 'Deconectare',
        'Login'                 => 'Autentificare',
        'Username'              => 'Utilizator',
        'Email address'         => 'Adresă email',
        'Phone number'          => 'Număr telefon',

        // Checkout / plată
        'Your order has been received. Thank you for your purchase!' => 'Comanda ta a fost primită. Mulțumim pentru achiziție!',
        'I have read and agree to the website' => 'Am citit și accept',
        'terms and conditions'  => 'termenii și condițiile',
        'Please read and accept the terms and conditions to proceed with your order.' => 'Citiți și acceptați termenii și condițiile pentru a finaliza comanda.',
        'There are no shipping methods available.' => 'Nu există metode de livrare disponibile.',
        'Sorry, it seems there are no available payment methods.' => 'Nu există metode de plată disponibile.',
        'Secure payment'        => 'Plată securizată',

        // Produs / catalog
        'SKU'                   => 'Cod produs',
        'Weight'                => 'Greutate',
        'Dimensions'            => 'Dimensiuni',
        'Availability'          => 'Disponibilitate',
        'Available on backorder' => 'Disponibil la comandă',
        'Back to products'      => 'Înapoi la produse',
        'Product categories'    => 'Categorii produse',
        'Product Categories'    => 'Categorii produse',
        'Product tags'          => 'Etichete produs',
        'Product Tags'          => 'Etichete produs',
        'Rating'                => 'Rating',
        'No reviews yet'        => 'Nicio recenzie încă',
        'Add a review'          => 'Adaugă o recenzie',
        'Write a review'        => 'Scrie o recenzie',

        // Mesaje / notificări
        'has been added to your cart.' => 'a fost adăugat în coș.',
        'has been added to your cart' => 'a fost adăugat în coș',
        'Cart updated.'         => 'Coș actualizat.',
        'Coupon applied successfully.' => 'Cupon aplicat cu succes.',
        'Invalid coupon.'       => 'Cupon invalid.',
        'Please enter a valid coupon code.' => 'Introduceți un cod de cupon valid.',
        'Sorry, this coupon is not applicable to your cart.' => 'Acest cupon nu se aplică coșului tău.',
        'Please fill in all required fields.' => 'Completați toate câmpurile obligatorii.',
        'Please accept the terms and conditions.' => 'Acceptați termenii și condițiile.',
        'An error occurred. Please try again.' => 'A apărut o eroare. Încercați din nou.',
        'Processing...'         => 'Se procesează...',
        'Please wait...'        => 'Așteptați...',

        // Paginare / listare
        'Previous page'         => 'Pagina anterioară',
        'Next page'             => 'Pagina următoare',
        'Page %s of %s'         => 'Pagina %s din %s',
        'Showing the single result' => 'Se afișează rezultatul',
        'Showing %s&ndash;%s of %s results' => 'Afișare %s&ndash;%s din %s rezultate',
        'No results found'      => 'Nu s-au găsit rezultate',
        'Search for:'           => 'Căutare:',
        'Search Results for: %s' => 'Rezultate căutare: %s',

        // Diverse
        'Brands'                => 'Branduri',
        'Brand'                 => 'Brand',
        'All'                   => 'Toate',
        'Select'                => 'Selectează',
        'Choose an option'      => 'Alege o opțiune',
        'Reset'                 => 'Resetează',
        'Clear all'             => 'Șterge tot',
        'Apply filters'         => 'Aplică filtre',
        'Active filters'        => 'Filtre active',
        'Price range'           => 'Interval preț',
        'Any'                   => 'Oricare',
        'From'                  => 'De la',
        'To'                    => 'Până la',
        'Go'                    => 'Mergi',
        'View all results'      => 'Vezi toate rezultatele',
        'Your rating'           => 'Ratingul tău',
        'Your review'            => 'Recenzia ta',

        // Recenzii produs
        'Be the first to review' => 'Fii primul care lasă o recenzie',
        'BE THE FIRST TO REVIEW' => 'FII PRIMUL CARE LASĂ O RECENZIE',
        'of this product'       => 'pentru acest produs',
        'Ratingul tău of this product' => 'Ratingul tău pentru acest produs',
        'Write your review here...' => 'Scrie recenzia ta aici...',
        'There are no reviews yet.' => 'Nu există încă recenzii.',
        'No reviews yet'        => 'Nu există încă recenzii',

        // Etichetă Nou / corectare Nuu
        'Nuu'                   => 'Nou',

        // Brand / denumiri produse (corectare iTelefon → iPhone)
        'iTelefon'               => 'iPhone',

        // Status stoc / precomandă
        'Status:'               => 'Status:',
        'Disponibil la precomanda' => 'Disponibil la precomandă',
        'Available on backorder' => 'Disponibil la comandă',

        // Rezultate căutare / listare
        'products found'        => 'produse găsite',
        'product found'         => 'produs găsit',
        'Produse found'         => 'Produse găsite',
        'Produs found'          => 'Produs găsit',
        '%s products found'     => '%s produse găsite',
        '%s product found'      => '%s produs găsit',
        'result found'          => 'rezultat găsit',
        'results found'         => 'rezultate găsite',
        '1 result found'        => '1 rezultat găsit',
        'Your Recently Viewed Products' => 'Produse vizualizate recent',
        'Your Recently Viewed Products.' => 'Produse vizualizate recent',
        'Recently Viewed Products' => 'Produse vizualizate recent',
        'Recently Viewed'       => 'Vizualizate recent',

        // Quick view (buton ochi)
        'Quick View'            => 'Vizualizare rapidă',
        'Vizualizeaza'          => 'Vizualizează',

        // Oferte / countdown
        'Ends In'               => 'Se termină în',
        'Ends in'               => 'Se termină în',
        'Ofertele Ends In'      => 'Oferta se termină în',
        'Deals Ends In'         => 'Oferta se termină în',

        // Corectare text greșit (traducere/typo)
        'Până latal'            => 'Total',
        'Pana latal'            => 'Total',
    );
}

function webgsm_romanian_strings($translated, $text, $domain) {
    // Corectare Nuu → Nou (dacă o traducere greșită returnează Nuu)
    if ($translated === 'Nuu') {
        return 'Nou';
    }
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
        '%s product' => array('%s produs', '%s produse'),
        '%s products' => array('%s produs', '%s produse'),
        '%s order' => array('%s comandă', '%s comenzi'),
        '%s orders' => array('%s comandă', '%s comenzi'),
    );
    $key = $single;
    if (isset($ro[$key])) {
        $choice = ($number == 1) ? 0 : 1;
        return sprintf($ro[$key][$choice], $number);
    }
    return $translated;
}
