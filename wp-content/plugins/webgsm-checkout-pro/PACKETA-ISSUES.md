# Packeta Integration — Diagnostic & Fix Plan

## Fișiere relevante

- **Plugin nostru:** `wp-content/plugins/webgsm-checkout-pro/webgsm-checkout-pro.php`
- **JS nostru:** `wp-content/plugins/webgsm-checkout-pro/assets/js/checkout.js`
- **Validare noastră:** `wp-content/plugins/webgsm-checkout-pro/includes/class-checkout-validate.php`
- **Packeta checkout:** `wp-content/plugins/packeta/src/Packetery/Module/Checkout/Checkout.php`
- **Packeta order updater:** `wp-content/plugins/packeta/src/Packetery/Module/Checkout/OrderUpdater.php`
- **Packeta storage:** `wp-content/plugins/packeta/src/Packetery/Module/Checkout/CheckoutStorage.php`
- **Packeta atribute:** `wp-content/plugins/packeta/src/Packetery/Module/Order/Attribute.php`

---

## Problema 1: Ramburs (COD) nu apare pe live

### Cauza

Packeta filtrează metodele de plată prin `filterPaymentGateways()` în `Checkout.php` (linia 380).

La linia 458: dacă curierul NU suportă COD (`$supportsCod === false`), elimină rambursul din lista de gateways.
La linia 462: dacă metoda de plată e explicit dezactivată în setările curierului Packeta, o elimină.

### Fix (din admin, fără cod)

1. Intră în **WP Admin → Packeta → Carrier Settings** (Setări curier)
2. Pentru FIECARE curier activ (Sameday HD, Sameday Box, Fan Courier HD, Fan Box, Easybox, etc.):
   - Verifică "**Supports COD**" / "Plata ramburs" → trebuie **bifat**
   - Verifică "**Disallowed payment methods**" / "Metode plată interzise" → rambursul/COD **NU trebuie** să fie în listă
3. Salvează și testează din nou pe checkout

---

## Problema 2: Packeta nu primește datele pentru generare AWB

### Cum funcționează Packeta intern

Packeta salvează datele comenzii prin hookul `woocommerce_checkout_update_order_meta` care apelează `OrderUpdater::actionUpdateOrderById()`.

Fluxul:
1. Când utilizatorul selectează un locker din widget, Packeta salvează datele într-un **WordPress transient** (via `CheckoutStorage`) — indexat după `shipping_method_id`
2. La submit comandă, `OrderUpdater::actionUpdateOrder()`:
   - Preia `$chosenMethod` din sesiune WooCommerce
   - Citește datele din `CheckoutStorage::getPostDataIncludingStoredData()` care combină:
     - Datele din `$_POST` (via `$this->httpRequest->getPost()`)
     - Datele din transient (backup dacă nu sunt în POST)
   - Salvează în tabela internă Packeta (`$this->orderRepository->save($order)`)

### Atributele pe care Packeta le caută (din `Order\Attribute`)

Pickup point:
- `packetery_point_id` — **OBLIGATORIU** (ID-ul numeric al locker-ului)
- `packetery_point_name` — numele locker-ului
- `packetery_point_city` — orașul
- `packetery_point_zip` — codul poștal
- `packetery_point_street` — strada
- `packetery_point_place` — business name
- `packetery_carrier_id` — ID-ul curierului Packeta
- `packetery_point_url` — URL
- `packetery_point_type` — tipul punctului

### Ce face pluginul nostru (potențial conflict)

1. **`apply_custom_shipping_fields()`** (hook: `woocommerce_checkout_create_order`, prioritate 999):
   - Când e pickup point: suprascrie adresa de shipping pe `WC_Order` cu adresa locker-ului
   - Setează `shipping_first_name` și `shipping_last_name` la empty string
   - Setează `shipping_company` = locker name
   - **Problemă potențială**: Rulează ÎNAINTE de Packeta (care e pe `woocommerce_checkout_update_order_meta`), dar suprascrierea adresei de shipping poate interfera cu cum Packeta detectează datele

2. **JS: injectare câmpuri în form** (la submit):
   - Copiem `input[name*="packetery"]` din DOM în `<form>` ca hidden inputs
   - Packeta widget-ul creează câmpuri hidden (`packetery_point_id`, `packetery_point_name`, etc.) — noi le clonăm

3. **JS: `moveShippingSection()`**:
   - Mută secțiunea de shipping (inclusiv widget-ul Packeta) din locul original într-un container custom
   - **Problemă potențială**: Dacă mutarea DOM face ca widget-ul Packeta să nu mai poată salva transientul corect (AJAX-ul Packeta care salvează datele selectate poate eșua dacă elementele au fost mutate)

### Posibile cauze ale eșecului AWB

1. **Transientul Packeta e gol**: Widgetul Packeta salvează selecția în transient via AJAX. Dacă DOM-ul a fost mutat de noi, AJAX-ul de save poate eșua sau salva sub un alt key.

2. **`packetery_point_id` lipsește din POST**: Noi injectăm `packetery_point_*` dar posibil `packetery_point_id` nu este generat de Packeta widget (câmpul hidden) sau noi nu îl copiem.

3. **`chosenMethod` nu se potrivește**: Packeta caută transientul indexat cu `$chosenMethod`. Dacă formatul method ID-ului diferă (ex. `packeta_method_25061:14` vs `PACKETA_METHOD_25061:14`), lookup-ul eșuează → returns empty → Packeta nu salvează nimic.

4. **Hookul nostru suprascrie înainte ca Packeta să citească**: Noi suntem pe `woocommerce_checkout_create_order` (prio 999), Packeta pe `woocommerce_checkout_update_order_meta`. Ordinea: `create_order` → order saved → `update_order_meta`. Deci Packeta citește DUPĂ noi — OK din perspectiva ordinii. Dar Packeta citește `$_POST` direct via Nette `$httpRequest->getPost()` care poate fi diferit de `$_POST` global.

### Plan de fix

#### Fix A: Asigură că inputurile Packeta sunt în form la submit

În `checkout.js`, la injectarea câmpurilor Packeta în form, verifică explicit că `packetery_point_id` este prezent și are valoare:

```javascript
// La submit, în secțiunea de injectare Packeta
if (isPacketaPickupMethod(chosenShippingMethod)) {
    $form.find('input[name*="packetery"], input[name*="packeta"]').remove();
    
    // Copiază TOATE câmpurile Packeta din pagină în form
    $('input[name^="packetery_"]').each(function() {
        var n = $(this).attr('name');
        var v = $(this).val();
        if (n && !$form.find('input[name="' + n + '"]').length) {
            $form.append('<input type="hidden" name="' + n + '" value="' + (v || '') + '">');
        }
    });
    
    // Log pentru debug
    console.log('[WebGSM] Packeta fields injected:', 
        $form.find('input[name^="packetery_"]').map(function(){ 
            return this.name + '=' + this.value; 
        }).get()
    );
}
```

#### Fix B: Nu suprascrie shipping address pentru comenzi Box

Comentariul din `apply_custom_shipping_fields()` zice "Skip when Packeta/Easybox is chosen – Packeta sets the pickup point address" dar de fapt noi NU skipăm — noi setăm adresa! Packeta la linia 256-258 din `OrderUpdater.php` face:

```php
if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
    $this->mapper->toWcOrderShippingAddress( $wcOrder, $attrName, (string) $attrValue );
}
```

Deci Packeta DEJA setează adresa dacă opțiunea e activată. Noi o setăm redundant pe `woocommerce_checkout_create_order` și posibil conflictuăm.

**Fix**: În `apply_custom_shipping_fields()`, pentru pickup point methods, setăm DOAR `_same_as_billing` meta și NU mai modificăm adresa de shipping — lăsăm Packeta să o seteze:

```php
public function apply_custom_shipping_fields( $order, $data ) {
    if ( self::is_packeta_pickup_point_method() ) {
        $order->update_meta_data( '_same_as_billing', '0' );
        // NU mai setăm adresa de shipping — Packeta o setează singur
        // prin OrderUpdater::getPropsFromCheckoutData() + replaceShippingAddressWithPickupPointAddress()
        return;
    }
    // ... rest door-to-door logic ...
}
```

#### Fix C: Verifică transientul Packeta (debug temporar)

Adaugă temporar în `apply_custom_shipping_fields()`:

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    $chosen = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
    error_log('[WebGSM] Packeta debug: chosen_methods=' . print_r($chosen, true));
    error_log('[WebGSM] Packeta debug: POST keys with packetery=' . 
        implode(', ', array_filter(array_keys($_POST), function($k) { 
            return stripos($k, 'packetery') !== false; 
        }))
    );
    foreach ($_POST as $k => $v) {
        if (stripos($k, 'packetery') !== false) {
            error_log("[WebGSM] Packeta POST: $k = $v");
        }
    }
}
```

#### Fix D: Verifică setarea "Replace shipping address" din Packeta

În **WP Admin → Packeta → Settings**, verifică opțiunea:
- "**Replace shipping address with pickup point address**" → trebuie **activată** pentru ca adresa locker-ului să apară corect pe comandă

---

## Problema 3: Comanda durează ~1 minut

### Posibile cauze

1. **`get_packeta_pickup_method_ids()`** — funcția noastră face query la DB + iterează toate zonele WooCommerce la FIECARE page load. Are un `static $cache` dar doar per-request.
2. **Packeta diagnostics logging** — Packeta loghează extensiv (fiecare apel `$this->diagnosticsLogger->log()`). Pe hosting lent, asta poate încetini.
3. **Microsoft Clarity / Visual Studio tracking** — erorile CORS `n.clarity.ms/collect` și `dc.services.visualstudio.com/v2/track` pot cauza timeout-uri în browser dacă scriptul așteaptă răspuns.

### Fix

1. Dezactivează temporar **Microsoft Clarity** (din setări sau eliminând scriptul din header) pentru a testa dacă viteza se îmbunătățește
2. În Packeta settings, verifică dacă **diagnostic logging** poate fi dezactivat
3. Funcția `get_packeta_pickup_method_ids()` e OK cu cache static, dar verifică dacă nu e apelată de prea multe ori per request

---

## Ordinea de implementare recomandată

1. **Fix admin Packeta** (fără cod): setări COD + replace shipping address
2. **Fix B**: Nu mai suprascrie adresa shipping pt Box în `apply_custom_shipping_fields()`
3. **Fix A**: Verifică injectarea completă a câmpurilor Packeta la submit
4. **Fix C**: Debug temporar pentru a verifica ce date primește Packeta
5. **Test**: Plasează comandă Box → verifică în admin Packeta dacă datele sunt complete
6. **Fix D**: Dacă tot nu merge, verifică transientul
