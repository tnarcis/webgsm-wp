# 🔍 INDEX - Găsire rapidă modificări

> Path-uri relative la **wp-content**. Pentru temă: `themes/martfury-child/`; pentru plugin-uri: `plugins/<nume-plugin>/`.

---

## 🎯 Vreau să modific...

| Ce | Unde |
|----|------|
| Culoarea butoanelor | `themes/martfury-child/includes/webgsm-design-system.php` sau `assets/css/design-system.css` |
| Popup "Produs adăugat" | `webgsm-design-system.php` + `functions.php` (temă child) |
| Formular înregistrare PF/PJ | `themes/martfury-child/includes/registration-enhanced.php` → [../modules/REGISTRATION.md](../modules/REGISTRATION.md) |
| Facturi SmartBill | `themes/martfury-child/includes/facturi.php` → [../modules/INVOICES.md](../modules/INVOICES.md) |
| SKU produse | `facturi.php` → `webgsm_auto_generate_sku` |
| TVA în facturi | `facturi.php` → `genereaza_factura_smartbill` |
| Prețuri B2B | `plugins/webgsm-b2b-pricing/webgsm-b2b-pricing.php` |
| Checkout PF/PJ | `plugins/webgsm-checkout-pro/webgsm-checkout-pro.php` |
| **Filtre categorii shop** (Piese, Unelte, Accesorii, etc.) | `plugins/webgsm-setup-wizard-v2/webgsm-setup-wizard-v2.php` (widget + `apply_piese_filter_query`) |
| **My Account – welcome header** (text, gradient, icon) | `themes/martfury-child/includes/header-account-menu.php` |
| Retururi | `themes/martfury-child/includes/retururi.php` |
| Garanții | `themes/martfury-child/includes/garantie.php` |
| My Account styling (meniu, tabele) | `themes/martfury-child/includes/my-account-styling.php` |

---

## 🔍 Search

Din rădăcina `wp-content/themes/martfury-child/`:
- CSS class: `grep -r ".class-name" includes/ assets/`
- Funcție PHP: `grep -r "function nume_functie" includes/ modules/`
- Hook: `grep -r "add_action\|add_filter" includes/ modules/ | grep "hook_name"`

---

## 🐛 Debugging rapid

- **Factură nu se generează:** Setări SmartBill, `debug.log` → "SmartBill"
- **User PJ fără prețuri B2B:** user meta `_is_pj`, `_tip_client`; log "detect_pj_on_registration"
- **SKU lipsă în factură:** SKU pe produs; SmartBill → Afișează cod produs

---

*Sursă: wp-content/themes/martfury-child/INDEX.md – mutat în docs/architecture/INDEX_RAPID.md*
