# 🔍 INDEX - Găsire Rapidă Modificări

> **Pentru alt agent / dezvoltator**: Caută aici IMEDIAT ce cauți!

---

## ⚡ **START RAPID - AI NOU?**

**→ Deschide `QUICK_START.md` ACUM! Copy/paste prompt-ul și începe.**

**Apoi revino aici pentru căutări rapide.** 👇

---

## 🎯 **CĂUTĂRI RAPIDE**

### **Vreau să modific...**

| Ce vreau să modific | Unde găsesc | Fișier |
|---------------------|-------------|--------|
| **Culoarea butoanelor** | CSS Design | `assets/css/design-system.css` sau `includes/webgsm-design-system.php` |
| **Popup "Produs adăugat"** | CSS + JS | `includes/webgsm-design-system.php` + `functions.php` |
| **Formular înregistrare PF/PJ** | Registration | `includes/registration-enhanced.php` → `modules/registration/README.md` |
| **Facturi SmartBill** | Invoices | `includes/facturi.php` → `modules/invoices/README.md` |
| **SKU produse** | Invoices | `includes/facturi.php` → funcția `webgsm_auto_generate_sku` |
| **TVA în facturi** | Invoices | `includes/facturi.php` → funcția `genereaza_factura_smartbill` |
| **Prețuri B2B** | Plugin extern | `plugins/webgsm-b2b-pricing/webgsm-b2b-pricing.php` |
| **Checkout PF/PJ** | Plugin extern | `plugins/webgsm-checkout-pro/webgsm-checkout-pro.php` |
| **Retururi** | Returns | `includes/retururi.php` |
| **Garanții** | Warranty | `includes/garantie.php` |
| **My Account styling** | My Account | `includes/my-account-styling.php` |

---

## 📂 **STRUCTURĂ FIȘIERE - MAP**

```
martfury-child/
│
├── ⚡ QUICK_START.md               ← AI NOU? START AICI! (copy/paste prompt)
├── 📖 README.md                    ← Overview complet proiect
├── 📝 CHANGELOG.md                 ← Istoric modificări (cu date!)
├── 🔍 INDEX.md                     ← CITEȘTI ACUM (găsire rapidă)
├── 🤖 AI_ONBOARDING_PROMPTS.md    ← Prompturi detaliate scenarii specifice
├── ✅ AI_TEST_ANSWERS.md          ← Test verificare AI (cu răspunsuri corecte)
├── 📊 SUMMARY.md                   ← Rezumat complet sistem
├── 🔒 SECURITY.md                  ← Audit securitate (9.2/10)
│
├── functions.php                   ← Include-uri (NU logică!)
├── style.css                       ← Stiluri override simple
│
├── assets/                   ← CSS + JavaScript SEPARAT
│   ├── css/
│   │   ├── design-system.css
│   │   ├── cart.css
│   │   ├── checkout.css
│   │   └── my-account.css
│   └── js/
│       ├── cart-popups.js
│       └── validation.js
│
├── modules/                  ← Logică PHP (fiecare cu README!)
│   ├── invoices/
│   │   └── README.md         ← Tot despre facturi SmartBill
│   ├── registration/
│   │   └── README.md         ← Tot despre formular PF/PJ
│   ├── b2b/
│   ├── checkout/
│   ├── my-account/
│   ├── returns/
│   └── warranty/
│
└── includes/                 ← DEPRECATED (mutat treptat în modules/)
    ├── facturi.php           → modules/invoices/
    ├── registration-enhanced.php → modules/registration/
    ├── retururi.php          → modules/returns/
    ├── garantie.php          → modules/warranty/
    └── ... (alte 10+ fișiere)
```

---

## 🔍 **SEARCH CHEAT SHEET**

### **Caut un CSS class:**
```bash
grep -r ".class-name" includes/ assets/
```

### **Caut o funcție PHP:**
```bash
grep -r "function nume_functie" includes/ modules/
```

### **Caut un hook WordPress:**
```bash
grep -r "add_action\|add_filter" includes/ modules/ | grep "hook_name"
```

### **Caut unde se modifică ceva:**
```bash
# Exemplu: caut unde se ascunde butonul "Vezi coș"
grep -r "vezi.*cos\|view.*cart" includes/ assets/ modules/
```

---

## 🎨 **CSS - UNDE E CE**

| Element | Fișier | Linie aprox. | Selector CSS |
|---------|--------|--------------|--------------|
| **Butoane albastre** | `webgsm-design-system.php` | 17-32 | `.woocommerce .button` |
| **Popup "Adăugat în coș"** | `webgsm-design-system.php` | 34-48 | `.message-box .btn-button` |
| **Mini-cart buttons** | `webgsm-design-system.php` | 640-668 | `.woocommerce-mini-cart__buttons a` |
| **Toggle PF/PJ** | `registration-enhanced.php` | 16-70 | `.webgsm-account-toggle` |
| **Formular PJ albastru** | `registration-enhanced.php` | 71-136 | `#campuri-firma-register` |
| **Buton ANAF** | `registration-enhanced.php` | 113-136 | `#btn_cauta_cui_register` |

---

## 🔧 **PHP - FUNCȚII IMPORTANTE**

| Funcție | Fișier | Ce face | Hook |
|---------|--------|---------|------|
| `smartbill_request()` | `facturi.php` | API call la SmartBill | - |
| `genereaza_factura_smartbill()` | `facturi.php` | Generează factură | `woocommerce_payment_complete` |
| `webgsm_auto_generate_sku()` | `facturi.php` | Auto SKU produse | `save_post_product` |
| `detect_pj_on_registration()` | `webgsm-b2b-pricing.php` | Detectează clienți B2B | `woocommerce_created_customer` |
| `hideViewCartButton()` | `functions.php` | Ascunde buton popup | JavaScript (eveniment `added_to_cart`) |

---

## 📋 **HOOK-URI FOLOSITE**

### **Registration:**
- `woocommerce_register_form_start` → Adaugă câmpuri înregistrare
- `woocommerce_registration_errors` → Validare
- `woocommerce_created_customer` → Salvare date user

### **Invoices:**
- `woocommerce_payment_complete` → Generează factură (plată online)
- `woocommerce_order_status_completed` → Generează factură (ramburs)
- `save_post_product` → Auto-generare SKU

### **B2B:**
- `woocommerce_product_get_price` → Modifică preț produs
- `woocommerce_get_price_html` → Afișare preț special
- `woocommerce_cart_totals_after_order_total` → Economie B2B în cart
- `woocommerce_created_customer` → Detectare PJ

---

## 🐛 **DEBUGGING RAPID**

### **Problem: Buton nu dispare**
1. **Caută în**: `functions.php` + `webgsm-design-system.php`
2. **Verifică**: Selectori CSS (`.message-box`, `.btn-button`)
3. **Test**: Hard refresh (`Cmd+Shift+R`)
4. **Log**: Console → vezi `hideViewCartButton()` rulează?

### **Problem: Factură nu se generează**
1. **Verifică**: `WooCommerce → Setări SmartBill` → ☑ API Activ
2. **Log**: `wp-content/debug.log` → caută "SmartBill"
3. **Test manual**: În comandă → Click "Generează factură manual"

### **Problem: User PJ nu primește prețuri B2B**
1. **Verifică user meta**: `_is_pj`, `_tip_client`
2. **Verifică**: `wp-content/debug.log` → caută "detect_pj_on_registration"
3. **Test**: În admin → Users → Edit user → Vezi user meta

### **Problem: SKU nu apare în factură**
1. **Verifică**: Produsul are SKU? (Admin → Produse → Edit → SKU field)
2. **Generează bulk**: `WooCommerce → Setări SmartBill` → Buton "Generează SKU"
3. **Verifică SmartBill.ro**: Setări → Setări Facturi → ☑ Afișează codul produsului

---

## 📞 **PRIORITIZARE PROBLEME**

### **🔴 CRITIC (rezolvă IMEDIAT):**
- Factură nu se generează → Vezi `modules/invoices/README.md`
- User nu se poate înregistra → Vezi `modules/registration/README.md`
- Prețuri B2B greșite → Vezi plugin `webgsm-b2b-pricing`

### **🟡 IMPORTANT (rezolvă în 1-2 zile):**
- Design greșit → Vezi `assets/css/` + `CHANGELOG.md`
- Validare lipsă → Vezi `modules/*/README.md`

### **🟢 NICE TO HAVE:**
- Refactoring → Vezi `README.md` secțiunea "Structură viitoare"
- Optimizări performanță

---

## 📚 **DOCUMENTAȚIE EXTERNĂ**

- **WooCommerce Hooks**: https://woocommerce.github.io/code-reference/hooks/hooks.html
- **WordPress Hooks**: https://developer.wordpress.org/reference/hooks/
- **SmartBill API**: https://www.smartbill.ro/api/

---

## ✅ **CHECKLIST PENTRU ALT AGENT**

Când începi să lucrezi pe acest proiect:

- [ ] Citește `README.md` (5 min)
- [ ] Citește `CHANGELOG.md` (3 min) - vezi ce s-a modificat recent
- [ ] Citește `INDEX.md` (ACEST FIȘIER) (2 min)
- [ ] Identifică modulul relevant din tabelul de mai sus
- [ ] Citește `modules/{modul}/README.md` (5-10 min)
- [ ] Fă modificarea DOAR în locul indicat
- [ ] Testează
- [ ] Update `CHANGELOG.md` cu ce ai făcut
- [ ] Commit cu mesaj descriptiv

**Total timp onboarding: 15-20 minute** ✨

---

**Ultima actualizare**: 2026-01-13
