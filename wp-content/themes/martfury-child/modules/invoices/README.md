# 📄 Modul Facturi - SmartBill Integration

> **Responsabil**: Generare și gestiune facturi automate via SmartBill API

---

## 📋 **CE FACE ACEST MODUL**

1. **Generare automată facturi** la finalizare comandă
2. **Download PDF** facturi din contul clientului
3. **Setări admin** pentru SmartBill (CIF, token, serie)
4. **Auto-generare SKU** pentru produse
5. **TVA dinamic** din prețurile WooCommerce

---

## 📂 **FIȘIERE**

### **Actual (în includes/):**
- `includes/facturi.php` (630 linii) - TOT modulul aici

### **Viitor (refactorizat în modules/invoices/):**
```
modules/invoices/
├── README.md                    ← Citești aici
├── smartbill-api.php           ← API calls la SmartBill
├── invoice-settings.php        ← Pagină setări admin
├── invoice-display.php         ← Afișare în cont client
├── sku-generator.php           ← Auto-generare SKU
└── tax-calculator.php          ← Calcul TVA dinamic
```

---

## ⚙️ **SETĂRI ADMIN**

### **Locație:**
`WooCommerce → Setări SmartBill`

### **Câmpuri:**
| Câmp | Descriere | Default |
|------|-----------|---------|
| **API Activ** | Activează/Dezactivează generare | ❌ Oprit |
| **Email SmartBill** | Username API | `info@webgsm.ro` |
| **Token API** | Token din SmartBill.ro | `003|5088be0e...` |
| **CIF Firmă** | CIF-ul companiei | `RO31902941` |
| **Serie Factură** | Seria folosită | `WEB` |
| **Cotă TVA Fallback** | TVA dacă WooCommerce nu calculează | `19` |

---

## 🔧 **FUNCȚII PRINCIPALE**

### **1. `smartbill_request($endpoint, $data, $method)`**
**Ce face**: Wrapper pentru toate request-urile către SmartBill API

**Parametri:**
- `$endpoint` (string) - Endpoint API (ex: `'invoice'`)
- `$data` (array) - Date de trimis
- `$method` (string) - `'POST'` sau `'GET'`

**Return**: Array cu răspuns SmartBill

**Exemplu:**
```php
$response = smartbill_request('invoice', $invoice_data, 'POST');
if (isset($response['number'])) {
    // Factură generată cu succes
}
```

**Logging**: Loghează automat SKU-uri și erori în `debug.log`

---

### **2. `genereaza_factura_smartbill($order_id)`**
**Ce face**: Generează factură pentru o comandă

**Trigger hooks:**
- `woocommerce_payment_complete` (plată online)
- `woocommerce_order_status_completed` (la livrare/ramburs)

**Flow:**
1. Verifică dacă API e activ
2. Verifică dacă factura există deja
3. Colectează date client (PF sau PJ)
4. Pregătește produse cu SKU + TVA
5. Adaugă transport
6. Trimite la SmartBill
7. Salvează număr factură în order meta

**Order meta salvate:**
- `_smartbill_invoice_number` - Număr factură
- `_smartbill_invoice_series` - Serie factură
- `_smartbill_invoice_date` - Dată generare

**Exemplu apel manual:**
```php
$result = genereaza_factura_smartbill(12345);
if ($result && isset($result['number'])) {
    echo "Factura " . $result['series'] . $result['number'];
}
```

---

### **3. `get_factura_pdf_smartbill($order_id)`**
**Ce face**: Descarcă PDF-ul facturii de la SmartBill

**Return**: Binary PDF content sau `false`

**Folosit de**: AJAX handler `download_factura_pdf`

**Exemplu:**
```php
$pdf = get_factura_pdf_smartbill(12345);
if ($pdf) {
    header('Content-Type: application/pdf');
    echo $pdf;
}
```

---

### **4. `webgsm_auto_generate_sku($product_id)`**
**Ce face**: Generează SKU automat la salvare produs (dacă nu are)

**Hook**: `save_post_product`

**Format SKU**: `WEBGSM-{Product_ID}`

**Exemplu**: Produs #456 fără SKU → primește `WEBGSM-456`

---

### **5. `webgsm_bulk_generate_skus()`**
**Ce face**: Generează SKU pentru TOATE produsele fără SKU

**Trigger**: Buton în `WooCommerce → Setări SmartBill`

**Return**: Număr de SKU-uri generate

---

## 📊 **DATE TRIMISE LA SMARTBILL**

### **Structură JSON:**
```json
{
  "companyVatCode": "RO31902941",
  "seriesName": "WEB",
  "client": {
    "name": "Nume Client / Firmă",
    "vatCode": "CUI (doar PJ)",
    "regCom": "Nr. Reg. Com. (doar PJ)",
    "address": "Adresa",
    "city": "Oraș",
    "county": "Județ",
    "country": "Romania",
    "email": "client@email.ro",
    "phone": "0712345678",
    "isTaxPayer": true/false
  },
  "products": [
    {
      "name": "Nume Produs",
      "code": "WEBGSM-123",          // SKU
      "measuringUnitName": "buc",
      "currency": "RON",
      "quantity": 2,
      "price": 100.50,                // Fără TVA
      "isTaxIncluded": false,
      "taxPercentage": 19.00,         // Dinamic din WooCommerce
      "saveToDb": false
    },
    {
      "name": "Transport",
      "code": "TRANSPORT",
      "quantity": 1,
      "price": 15.00,
      "taxPercentage": 19.00
    }
  ],
  "issueDate": "2026-01-13",
  "dueDate": "2026-01-28",
  "currency": "RON",
  "language": "RO",
  "observations": "Comandă online #12345"
}
```

---

## 🧪 **TESTARE**

### **Test 1: Generare factură manuală**
```php
// În wp-admin → Tools → Site Health → Debug
$order_id = 12345;
$result = genereaza_factura_smartbill($order_id);
var_dump($result);
```

### **Test 2: Verificare SKU**
```bash
# În debug.log
grep "SmartBill Product" wp-content/debug.log
# Output: SmartBill Product: Nume | SKU: WEBGSM-123 | TVA: 19%
```

### **Test 3: Verificare request**
```bash
# În debug.log
grep "=== SmartBill API Request ===" wp-content/debug.log
```

---

## 🐛 **DEBUGGING**

### **Activare debug:**
```php
// În wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### **Log-uri relevante:**
```bash
# Vezi toate request-urile SmartBill
tail -f wp-content/debug.log | grep "SmartBill"

# Vezi SKU-uri trimise
grep "Code/SKU:" wp-content/debug.log

# Vezi erori
grep "SmartBill Error" wp-content/debug.log
```

---

## ⚠️ **PROBLEME COMUNE**

### **1. Factura nu se generează**
**Cauze:**
- API dezactivat → Verifică în setări: ☑ API Activ
- Token invalid → Verifică token în SmartBill.ro
- Produs fără preț → Verifică prețuri produse

**Verificare:**
```bash
grep "SmartBill: API dezactivat" wp-content/debug.log
```

### **2. TVA greșit (21% în loc de 19%)**
**Soluție:**
1. Mergi la **WooCommerce → Setări → Taxe**
2. Activează taxele
3. Setează cotă 19% pentru RO
4. Salvează

**Verificare:**
```bash
grep "TVA:" wp-content/debug.log
# Ar trebui să vezi: TVA: 19%
```

### **3. SKU nu apare în factură PDF**
**Soluție în SmartBill.ro:**
1. **Setări → Setări Generale → Setări Facturi**
2. Secțiunea "Produse/Servicii"
3. Bifează: ☑ **Afișează codul produsului în facturi**
4. Salvează

---

## 🔄 **MODIFICĂRI VIITOARE (Refactoring)**

### **Plan:**
1. ✅ Separă `smartbill-api.php` (API calls)
2. ✅ Separă `invoice-settings.php` (admin page)
3. ✅ Separă `invoice-display.php` (frontend)
4. ✅ Separă `sku-generator.php` (SKU logic)
5. ✅ Separă `tax-calculator.php` (TVA logic)

### **Benefits:**
- Mai ușor de testat
- Mai ușor de modificat
- Mai ușor de înțeles
- Fără breaking changes

---

## 📖 **LINK-URI UTILE**

- [SmartBill API Docs](https://www.smartbill.ro/api/)
- [SmartBill Postman Collection](https://documenter.getpostman.com/view/5245987/RWaLP1YD)
- [WooCommerce Order Hooks](https://woocommerce.github.io/code-reference/hooks/hooks.html)

---

## 📞 **CONTACT**

Întrebări despre modul: echipa WebGSM

**Ultima actualizare**: 2026-01-13
