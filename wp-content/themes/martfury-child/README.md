# 🎨 Martfury Child Theme - WebGSM

> Temă child organizată modular pentru WebGSM - actualizată **2026-01-13**

---

## 📋 **STRUCTURA PROIECTULUI**

```
martfury-child/
├── README.md                    ← Citești aici
├── CHANGELOG.md                 ← Istoric modificări
├── functions.php                ← DOAR include-uri
│
├── assets/                      ← CSS + JavaScript
│   ├── css/
│   │   ├── design-system.css   ← Butoane, culori, tipografie
│   │   ├── cart.css            ← Stiluri coș (popup, mini-cart)
│   │   ├── checkout.css        ← Stiluri checkout
│   │   └── my-account.css      ← Stiluri "Contul meu"
│   └── js/
│       ├── cart-popups.js      ← Ascunde butoane popup
│       └── validation.js       ← Validări formulare
│
├── modules/                     ← Logică PHP (fiecare cu README.md)
│   ├── b2b/                    ← Integrare B2B Pricing
│   ├── invoices/               ← SmartBill facturi
│   ├── registration/           ← Formular înregistrare PF/PJ
│   ├── checkout/               ← Checkout personalizat
│   ├── my-account/             ← Dashboard cont
│   ├── returns/                ← Retururi
│   └── warranty/               ← Garanții
│
└── includes/                    ← DEPRECATED (backward compatibility)
```

---

## 🎯 **PRINCIPII ORGANIZARE**

### ✅ **DO:**
1. **Un modul = O funcționalitate** (invoices, registration, etc.)
2. **CSS separat de PHP** (logic vs. design)
3. **README.md în fiecare modul** (ce face, cum se modifică)
4. **Hooks specifice** (nu modificări directe în core)
5. **Update CHANGELOG.md** la fiecare modificare

### ❌ **DON'T:**
1. **Nu pune CSS în fișiere PHP** (folosește assets/css/)
2. **Nu pune logică în functions.php** (folosește modules/)
3. **Nu modifica fișiere core** (tema părinte, WordPress, WooCommerce)
4. **Nu duplica cod** (un singur loc pentru fiecare funcționalitate)

---

## 📦 **MODULE ACTIVE**

| Modul | Fișier principal | Descriere | Status |
|-------|------------------|-----------|--------|
| **B2B Integration** | `modules/b2b/b2b-hooks.php` | Integrare webgsm-b2b-pricing | ✅ Active |
| **Invoices** | `modules/invoices/smartbill-api.php` | Facturi SmartBill automate | ✅ Active |
| **Registration** | `modules/registration/registration-fields.php` | Formular PF/PJ cu line-art | ✅ Active |
| **Checkout** | `modules/checkout/checkout-custom.php` | Integrare webgsm-checkout-pro | ✅ Active |
| **My Account** | `modules/my-account/account-dashboard.php` | Dashboard personalizat | ✅ Active |
| **Returns** | `modules/returns/returns.php` | Sistem retururi | ✅ Active |
| **Warranty** | `modules/warranty/warranty.php` | Gestiune garanții | ✅ Active |

---

## 🔧 **CUM SE MODIFICĂ**

### **Exemplu: Schimbare culoare buton**
```
1. Deschide: assets/css/design-system.css
2. Caută: /* Butoane principale */
3. Modifică: background-color: #2196F3;
4. Salvează → Refresh site
5. Update CHANGELOG.md
```

### **Exemplu: Adaugă câmp în înregistrare**
```
1. Deschide: modules/registration/README.md (citește structura)
2. Editează: modules/registration/registration-fields.php
3. Adaugă hook nou (vezi README pentru exemple)
4. Salvează → Testează
5. Update CHANGELOG.md
```

---

## 🛡️ **UPDATE-SAFE**

### **La update WordPress/WooCommerce/Tema:**
- ✅ **SIGURE** (nu se pierd):
  - Tot din `martfury-child/`
  - Plugin-uri custom (`webgsm-b2b-pricing`, `webgsm-checkout-pro`)
  
- ⚠️ **ATENȚIE**:
  - Verifică compatibilitatea hook-urilor
  - Testează pe staging întâi

---

## 📖 **DOCUMENTAȚIE COMPLETĂ**

Pentru fiecare modul, vezi `modules/{modul}/README.md`:
- `modules/invoices/README.md` - Tot despre facturi SmartBill
- `modules/registration/README.md` - Tot despre înregistrare
- etc.

---

## 🐛 **DEBUGGING**

### **Log-uri:**
```bash
# WordPress debug log
tail -f wp-content/debug.log

# SmartBill requests
grep "SmartBill" wp-content/debug.log
```

### **Cache:**
```bash
# Clear toate cache-urile
WP Admin → B2B Pricing → Clear Cache
WP Admin → WooCommerce → Status → Tools → Clear transients
```

---

## 👥 **PENTRU ALT AGENT / DEZVOLTATOR**

### **🚀 START RAPID (AI NOU):**

**→ Deschide `QUICK_START.md` și copy/paste prompt-ul!** ⚡

### **Pași rapizi:**
1. **`QUICK_START.md`** - Copy/paste prompt pentru AI nou (2 min)
2. **Test 5 întrebări** - Verifică AI e pregătit (3 min)
3. **Citește README.md** (acest fișier) - Overview (5 min)
4. **Citește INDEX.md** - Găsire rapidă orice (2 min)
5. **CHANGELOG.md** - Vezi modificări recente (3 min)
6. **Identifică modulul** - Tabel MODULE ACTIVE mai jos
7. **Citește README.md** al modulului specific (5-10 min)
8. **Modifică** DOAR în locul indicat
9. **Update CHANGELOG.md** - OBLIGATORIU!

**Total timp onboarding: 15-20 minute** ✨

### **Golden Rule:**
> **"Un fișier = O responsabilitate"**
> 
> Dacă vrei să modifici butoanele → `assets/css/design-system.css`
> Dacă vrei să modifici facturi → `modules/invoices/`
> NICIODATĂ amesteca logică cu design în același fișier!

---

## 📊 **STATISTICI**

- **Linii de cod CSS**: ~2,500
- **Linii de cod PHP**: ~8,000
- **Module active**: 7
- **Hook-uri custom**: 45+
- **Ultima actualizare**: 2026-01-13

---

## 🔗 **LINK-URI UTILE**

- [WooCommerce Hooks](https://woocommerce.github.io/code-reference/hooks/hooks.html)
- [WordPress Hooks](https://developer.wordpress.org/reference/hooks/)
- [SmartBill API Docs](https://www.smartbill.ro/api/)

---

**Întrebări?** Deschide un issue sau contactează echipa WebGSM.
