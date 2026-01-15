# 📝 Modul Înregistrare - PF/PJ cu LINE-ART Design

> **Responsabil**: Formular înregistrare WooCommerce personalizat cu toggle PF/PJ și design line-art

---

## 📋 **CE FACE ACEST MODUL**

1. **Toggle PF/PJ** cu iconițe SVG line-art
2. **Câmpuri suplimentare** (prenume, nume, telefon)
3. **Formular firmă PJ** (cu ANAF autocompletare)
4. **Validare** câmpuri PF vs. PJ
5. **Confirmare email** obligatorie
6. **Design line-art** (albastru, nu galben)

---

## 📂 **FIȘIERE**

### **Actual:**
- `includes/registration-enhanced.php` (752 linii) - TOT aici

### **Viitor (refactorizat):**
```
modules/registration/
├── README.md                    ← Citești aici
├── fields.php                  ← Câmpuri formular
├── validation.php              ← Validare
├── save.php                    ← Salvare date user
├── email-confirmation.php      ← Confirmare email
└── styles.css                  ← Stiluri (separat de PHP!)
```

---

## 🎨 **DESIGN SYSTEM**

### **Culori:**
```css
--primary-blue: #2196F3
--dark-blue: #1976D2
--light-blue-bg: rgba(33,150,243,0.08)
--hover-blue: rgba(33,150,243,0.15)
```

### **CSS Classes importante:**

| Clasă | Folosire | Stil |
|-------|----------|------|
| `.webgsm-account-toggle` | Container toggle PF/PJ | Flex, gap 12px |
| `.toggle-icon` | SVG icons | 24x24px, stroke |
| `.toggle-icon svg` | Iconițe line-art | stroke-width: 1.5 |
| `.toggle-text` | Text toggle | Font 14px, weight 500 |
| `.b2b-badge` | Badge "PREȚURI B2B" | Gradient albastru, animat |
| `#campuri-firma-register` | Formular PJ | Gradient albastru, border radius 16px |
| `.firma-header` | Header formular PJ | SVG + titlu |
| `#btn_cauta_cui_register` | Buton ANAF | Gradient albastru, hover effect |
| `#anaf_result_register` | Rezultat ANAF | Success/Error/Loading styles |

---

## 🔧 **HOOK-URI FOLOSITE**

### **1. `woocommerce_register_form_start`**
**Ce face**: Adaugă câmpuri la ÎNCEPUTUL formularului

**Funcție**: Anonymous function (în fișier)

**Adaugă:**
- Prenume (required)
- Nume (required)
- Telefon (required)
- Toggle PF/PJ cu SVG icons
- Formular PJ (hidden by default)

**Stiluri inline**: DA (ar trebui mutat în CSS separat)

---

### **2. `woocommerce_registration_errors`**
**Ce face**: Validează câmpurile noi

**Funcție**: Anonymous function

**Validări:**
- Prenume obligatoriu
- Nume obligatoriu
- Telefon obligatoriu
- Dacă PJ: CUI + Denumire firmă obligatorii

**Return**: `WP_Error` object

---

### **3. `woocommerce_created_customer`**
**Ce face**: Salvează datele după creare user

**Funcție**: Anonymous function

**Salvează:**
- `billing_first_name`, `first_name`
- `billing_last_name`, `last_name`
- `billing_phone`
- `_tip_facturare` (`pf` sau `pj`)
- Dacă PJ:
  - `_firma_cui`
  - `_firma_nume`
  - `_firma_reg_com`
  - `_firma_adresa`
  - `_firma_judet`
  - `_firma_oras`
- Confirmaremail:
  - `_email_confirmed` = 0
  - `_confirmation_token`

**Trigger**: Email de confirmare

---

## 📊 **CÂMPURI FORMULAR**

### **PF (Persoană Fizică):**
```html
- Prenume *
- Nume *
- Email * (WooCommerce default)
- Telefon *
- Parola * (WooCommerce default)
```

### **PJ (Persoană Juridică):**
```html
- Prenume *
- Nume *
- Email *
- Telefon *
- Parola *
─────────────────────────
TOGGLE: [👤 PF] [🏢 PJ] ← Badge "PREȚURI B2B"
─────────────────────────
  Formular PJ (dacă selectat):
  - CUI/CIF * [Buton: Autocompletare]
  - Denumire Firmă *
  - Nr. Reg. Comerțului
  - Adresa Firmă
  - Județ
  - Localitate
```

---

## 🎯 **JAVASCRIPT INTERACTIONS**

### **Toggle PF/PJ:**
```javascript
$('input[name="tip_facturare"]').on('change', function() {
    if($(this).val() === 'pj') {
        $('#campuri-firma-register').slideDown();
    } else {
        $('#campuri-firma-register').slideUp();
    }
});
```

### **ANAF Autocompletare:**
```javascript
$('#btn_cauta_cui_register').on('click', function() {
    var cui = $('#reg_firma_cui').val().trim().replace(/^RO/i, '');
    
    $.ajax({
        url: ajaxurl,
        data: { action: 'cauta_cui_anaf', cui: cui },
        success: function(response) {
            if(response.success) {
                // Completează câmpurile
                $('#reg_firma_nume').val(response.data.denumire);
                $('#reg_firma_reg_com').val(response.data.nrRegCom);
                // etc.
            }
        }
    });
});
```

---

## 📧 **CONFIRMARE EMAIL**

### **Flow:**
1. User se înregistrează
2. `_email_confirmed` = 0
3. Se generează token: `wp_generate_password(32, false)`
4. Email trimis cu link confirmare
5. User dă click → token verificat
6. `_email_confirmed` = 1
7. User se poate loga

### **Funcții:**
- `envoi_email_confirmare($customer_id)` - Trimite email
- Handler `init` - Procesează confirmarea (URL param: `confirm_email`, `user_id`, `token`)

### **Blocare login:**
```php
add_filter('wp_authenticate_user', function($user, $password) {
    $confirmed = get_user_meta($user->ID, '_email_confirmed', true);
    if ($confirmed != 1) {
        return new WP_Error('email_not_confirmed', 'Email neconfirmat!');
    }
    return $user;
});
```

---

## 🔗 **INTEGRARE CU B2B PLUGIN**

### **Câmpuri folosite de `webgsm-b2b-pricing`:**
| Câmp formular | User meta salvat | Detectare PJ |
|---------------|------------------|--------------|
| `tip_facturare` | `_tip_facturare` | ✅ Verificat |
| `firma_cui` | `_firma_cui` | ✅ Verificat |
| `firma_nume` | `_firma_nume` | ✅ Verificat |

### **Hook B2B:**
```php
// În webgsm-b2b-pricing.php
add_action('woocommerce_created_customer', 'detect_pj_on_registration', 20);

function detect_pj_on_registration($customer_id) {
    $tip = get_user_meta($customer_id, '_tip_facturare', true);
    $cui = get_user_meta($customer_id, '_firma_cui', true);
    
    if ($tip === 'pj' || !empty($cui)) {
        update_user_meta($customer_id, '_is_pj', 'yes');
        update_user_meta($customer_id, '_tip_client', 'pj');
        // → Prețuri B2B activate!
    }
}
```

---

## 🧪 **TESTARE**

### **Test 1: Înregistrare PF**
1. Mergi la `/my-account/`
2. Tab "Înregistrare"
3. Toggle: 👤 **Persoană Fizică**
4. Completează: Prenume, Nume, Email, Telefon, Parolă
5. Click "Înregistrare"
6. Verifică email → confirmare
7. Login → NU ar trebui să vadă prețuri B2B

### **Test 2: Înregistrare PJ**
1. Toggle: 🏢 **Persoană Juridică** (badge "PREȚURI B2B" apare)
2. Formular albastru se deschide (slideDown)
3. CUI: `RO12345678` → Click "Autocompletare"
4. Câmpurile se completează automat (ANAF)
5. Finalizează înregistrarea
6. Confirmă email
7. Login → **AR TREBUI** să vadă prețuri B2B!

### **Test 3: Validare**
1. Lasă Prenume gol → Error
2. Alege PJ dar fără CUI → Error
3. Alege PJ dar fără Denumire → Error

---

## 🎨 **MODIFICĂRI DESIGN**

### **Schimbă culoarea toggle:**
```css
/* În includes/registration-enhanced.php, secțiunea <style> */
.webgsm-account-toggle label:hover {
    border-color: #FF5722; /* Schimbă din #2196F3 */
}
```

### **Schimbă gradient formular PJ:**
```css
#campuri-firma-register {
    background: linear-gradient(135deg, 
        rgba(255,87,34,0.04) 0%,    /* Schimbă din albastru */
        rgba(255,87,34,0.08) 100%
    );
}
```

### **Schimbă iconițe:**
Înlocuiește SVG-urile în:
```php
<span class="toggle-icon">
    <svg viewBox="0 0 24 24">
        <!-- Înlocuiește path-urile aici -->
    </svg>
</span>
```

---

## ⚠️ **ATENȚIE - STILURI INLINE!**

### **Problemă actuală:**
Toate stilurile CSS sunt în `<style>` tags în fișierul PHP (linia 16-511)

### **Trebuie mutat în:**
`modules/registration/styles.css` sau `assets/css/registration.css`

### **Beneficii:**
- CSS separat de logică
- Mai ușor de modificat
- Cache browser
- Minificare posibilă

---

## 🔄 **REFACTORING PLAN**

### **1. Separă stilurile:**
```php
// În functions.php
wp_enqueue_style('webgsm-registration', 
    get_stylesheet_directory_uri() . '/modules/registration/styles.css'
);
```

### **2. Separă JavaScript:**
```php
wp_enqueue_script('webgsm-registration', 
    get_stylesheet_directory_uri() . '/modules/registration/scripts.js',
    ['jquery']
);
```

### **3. Separă funcțiile:**
- `fields.php` - Hook-uri câmpuri
- `validation.php` - Hook validare
- `save.php` - Hook salvare
- `email-confirmation.php` - Sistem confirmare

---

## 📖 **DOCUMENTAȚIE ANAF**

API folosit pentru autocompletare (în tema părinte sau plugin):
- Endpoint: Probabil `/admin-ajax.php?action=cauta_cui_anaf`
- Handler: Caută în fișiere pentru `add_action('wp_ajax_cauta_cui_anaf')`

---

## 📞 **DEBUGGING**

### **Verifică dacă user e marcat PJ:**
```sql
SELECT * FROM wp_usermeta 
WHERE meta_key IN ('_is_pj', '_tip_facturare', '_firma_cui') 
AND user_id = 123;
```

### **Verifică email confirmat:**
```sql
SELECT * FROM wp_usermeta 
WHERE meta_key = '_email_confirmed' 
AND user_id = 123;
-- Ar trebui: meta_value = '1'
```

---

**Ultima actualizare**: 2026-01-13
