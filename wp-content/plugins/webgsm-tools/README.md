# WebGSM Tools

Plugin WordPress cu instrumente pentru verificare și procesare produse. Apare în meniul **Upload Tools** împreună cu Setup Wizard.

## Module

### 📦 Product Reviewer
Verifică și corectează produse din CSV înainte de import în WooCommerce.
- Upload CSV
- Validare: categorii, atribute, SEO
- Editor per produs, raport categorii/atribute noi, export CSV corectat

### 🎨 Image Studio
Adaugă badge-uri și logo-uri pe imaginile produselor (canvas, template-uri).

## Meniu admin

Sub **Upload Tools** (în bara laterală admin):
- **Setup Wizard** – WebGSM Setup v2 (categorii, atribute, meniu, filtre)
- **Dashboard** – acasă WebGSM Tools
- **Product Reviewer**
- **Image Studio**

## Cerințe

- WordPress 6.0+
- WooCommerce
- PHP 8.0+

## Structură

```
webgsm-tools/
├── webgsm-tools.php
├── includes/ (admin-menu, helpers, api, reviewer, studio)
├── admin/ (css, js, views)
├── assets/ (brand-logos, badge-templates, fonts)
├── data/ (image-templates.json)
└── README.md
```
