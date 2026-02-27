# Traduceri română – doar .po / .mo (fără romanian-strings.php)

Site-ul folosește **doar** fișierele .po și .mo pentru română. Fișierul `includes/romanian-strings.php` **nu este încărcat** (nu apare în `functions.php`), deci nu rulează și nu încetinește nimic.

---

## Unde sunt fișierele de limbă

| Sursă | Locație | Fișiere ro_RO |
|-------|---------|----------------|
| **Tema Martfury** | `wp-content/languages/themes/` | `Martfury-ro_RO.po`, `Martfury-ro_RO.mo` |
| **WooCommerce** | `wp-content/languages/plugins/` | `woocommerce-ro_RO.po`, `woocommerce-ro_RO.mo` |
| **Alte plugin-uri** | `wp-content/languages/plugins/` | `nume-plugin-ro_RO.po` / `.mo` |
| **WordPress core** | `wp-content/languages/` | `ro_RO.po`, `ro_RO.mo` |

WordPress citește automat:
- `wp-content/languages/themes/` pentru teme
- `wp-content/languages/plugins/` pentru plugin-uri
- `wp-content/languages/` pentru core

Nu e nevoie să încarci manual aceste fișiere; dacă limba site-ului e setată pe **Română** (Setări → General → Limba site), WP le folosește singur.

---

## Cum identifici CE și UNDE editezi

### 1. Ce text vrei în română?

Exemplu: pe mobil apare „View All” și vrei „Vezi toate”.

### 2. Din ce provine textul? (tema / WooCommerce / plugin)

- **Tema (header, meniu, footer, butoane temă):** → editezi **Tema Martfury**  
  Fișier: `wp-content/languages/themes/Martfury-ro_RO.po`
- **Magazin (coș, checkout, produs, „Add to cart”):** → editezi **WooCommerce**  
  Fișier: `wp-content/languages/plugins/woocommerce-ro_RO.po`
- **Un plugin anume (ex: formulare, livrări):** → editezi acel plugin  
  Fișier: `wp-content/languages/plugins/[nume-plugin]-ro_RO.po`

### 3. Cum găsești stringul în .po

- **Caută în .po** textul în engleză (ex: `View All`).  
  În .po arată așa:
  ```text
  msgid "View All"
  msgstr "Vezi toate"
  ```
  Dacă `msgstr` e gol, completezi tu traducerea.
- **Cu Loco Translate:**  
  Setări → Loco Translate → Teme / Plugin-uri → alegi limba **Română** → găsești stringul în listă și editezi acolo. Salvezi și se generează .mo.
- **Cu Poedit:**  
  Deschizi `Martfury-ro_RO.po` sau `woocommerce-ro_RO.po`, cauți `msgid "View All"` și pui în `msgstr` „Vezi toate”, apoi salvezi (Poedit creează/actualizează .mo).

---

## Flux recomandat (manual)

1. Setezi limba site: **Setări → General → Limba site: Română**.
2. Pentru **tema Martfury:**  
   Editezi `wp-content/languages/themes/Martfury-ro_RO.po` (adaugi/completezi `msgstr`), salvezi; generezi .mo (Loco sau Poedit).
3. Pentru **WooCommerce:**  
   La fel cu `wp-content/languages/plugins/woocommerce-ro_RO.po`.
4. După modificări, golești cache (dacă ai cache / CDN) și reîncarci pagina.

---

## Dacă un text tot nu se traduce

- Unele texte sunt **hardcodate** în temă (fără `__()` / `_e()`). Alea **nu** apar în .po și nu se pot traduce doar din .po; trebuie editat codul temei (sau un child theme) să folosească funcții de traducere.
- Texte afișate **doar prin JavaScript** (injectate din JS) nu sunt mereu în .po; unele sunt în fișiere .json din `wp-content/languages/plugins/` (ex. WooCommerce). Pentru ele poți căuta în acel plugin după textul în engleză.

---

## Rezumat

| Vrei să... | Unde |
|------------|------|
| Traduci texte din **tema Martfury** | `wp-content/languages/themes/Martfury-ro_RO.po` (apoi .mo) |
| Traduci texte **WooCommerce** | `wp-content/languages/plugins/woocommerce-ro_RO.po` (apoi .mo) |
| Traduci un **plugin** | `wp-content/languages/plugins/[plugin]-ro_RO.po` (apoi .mo) |
| Să nu mai folosești traduceri PHP care trag site-ul în jos | Nu încarci `romanian-strings.php` (acum nu e încărcat) |

**Loco Translate** (plugin WordPress) simplifică: vezi toate stringurile pe teme/plugin, editezi în browser, salvezi și se generează .mo automat.
