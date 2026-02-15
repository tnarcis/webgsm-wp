# 🎨 Martfury Child Theme - WebGSM

> Temă child organizată modular – actualizată **2026-01-13**

---

## 📋 Structura proiectului

```
martfury-child/
├── functions.php                ← DOAR include-uri
├── assets/                       ← CSS + JavaScript
│   ├── css/ (design-system, cart, checkout, my-account)
│   └── js/ (cart-popups, validation)
├── modules/                      ← Logică PHP (fiecare cu README)
│   ├── invoices/   │   registration/   │   b2b/   checkout/   my-account/   returns/   warranty/
└── includes/                     ← Backward compatibility (facturi, registration-enhanced, retururi, garantie, etc.)
```

---

## 🎯 Principii

- **DO:** Un modul = o funcționalitate; CSS separat de PHP; README în fiecare modul; update CHANGELOG.
- **DON'T:** CSS în PHP; logică în functions.php; modificări în core; cod duplicat.

---

## 📖 Documentație

- Găsire rapidă: [INDEX_RAPID.md](INDEX_RAPID.md)
- My Account: [MY_ACCOUNT.md](MY_ACCOUNT.md)
- Module: [../modules/REGISTRATION.md](../modules/REGISTRATION.md), [../modules/INVOICES.md](../modules/INVOICES.md)

---

*Sursă: wp-content/themes/martfury-child/README.md – mutat în docs/architecture/THEME_STRUCTURE.md*
