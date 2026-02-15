# WebGSM - My Account - Structura actuală

**Data:** 27 Ianuarie 2026 | **Status:** Finalizat ✅

---

## Dashboard – Welcome header

- **Fișier:** `themes/martfury-child/includes/header-account-menu.php`
- **Conținut:** Box „Bine ai venit, [Nume]” – gradient albastru (#3b82f6), icon cheie; fără pill tier lângă nume (tier doar în blocul „Nivelul tău de Partener” de mai jos). Buton „Vezi progresul” dacă e PJ.

---

## 📋 Structura meniu

- **Panou control** (Dashboard) – scurtături
- **Achizițiile mele:** Comenzi, Retururi, Garantie
- **Date salvate:** Adrese (Firme – în construcție)
- **Setări:** Detalii cont, Ieșire din cont

---

## Module implementate

| Secțiune | Fișier / sursă | Endpoint / note |
|----------|----------------|------------------|
| Dashboard | webgsm-myaccount.php | Nativ WC |
| Comenzi | Nativ WooCommerce | Istoric comenzi |
| Retururi | retururi.php | /panou-control/retururi/ |
| Garantie | garantie.php | /panou-control/garantie/ |
| Adrese salvate | webgsm-myaccount.php + webgsm-myaccount-modals.php | Tabel, Add/Edit/Delete, AJAX, user meta `webgsm_addresses` |
| Detalii cont / Ieșire | Nativ WooCommerce | - |

---

## Stilizare

- **Fișier:** `my-account-styling.php` – headers grup, indentare, responsive.

---

*Sursă: wp-content/MY_ACCOUNT_STRUCTURE.md – mutat în docs/architecture/MY_ACCOUNT.md*
