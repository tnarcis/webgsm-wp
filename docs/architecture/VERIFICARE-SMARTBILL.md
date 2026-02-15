# Verificare integrare SmartBill (existentă)

**Data verificării:** 2026-01 – în contextul implementării plugin-ului WebGSM Woo Sync.

## Locație

- **Fișier:** `wp-content/themes/martfury-child/includes/facturi.php`
- **Setări admin:** WooCommerce → **Setări SmartBill** (pagina `smartbill-settings`)

## Ce face (verificat)

- **API:** `smartbill_request($endpoint, $data, $method)` – apelează `https://ws.smartbill.ro/SBORO/api/`.
- **Generare factură:** `genereaza_factura_smartbill($order_id)`:
  - Verifică `smartbill_api_active`; dacă dezactivat, nu trimite.
  - Evită dublarea: dacă există `_smartbill_invoice_number` pe comandă, returnează fără a mai trimite.
  - Construiește client din billing (inclusiv CIF PJ din `_billing_cif`, `_billing_company_name`, `_billing_reg_com`).
  - Linii: nume, **SKU** (sau `PROD-{id}`), cantitate, preț, TVA (calculat din taxe Woo).
  - Transport: linie separată dacă există shipping.
- **Trigger:**
  - **Processing** → factură pentru plăți online (stripe, paypal, netopia, etc.).
  - **Completed** → factură pentru ramburs/offline (cod, bacs, easybox, etc.).
- **PDF:** `get_factura_pdf_smartbill($order_id)` – descarcă PDF de la SmartBill.

## Conformitate cu SPEC

- SPEC prevede **Variantă A** (factura o emite WebGSM după webhook). Implementarea actuală este **Variantă B** (factura o emite Woo direct către SmartBill).
- **Decizie:** Nu s-a modificat nimic la facturare. Plugin-ul WebGSM Woo Sync doar trimite webhook către WebGSM; backend-ul poate folosi datele pentru stoc și, opțional, pentru o facturare viitoare din WebGSM. Până atunci, facturarea rămâne din Woo (facturi.php).

## EAN / GTIN

- În `facturi.php` produsele trimise la SmartBill au **code** = SKU (sau PROD-id). EAN nu este trimis în payload-ul SmartBill actual.
- În **webhook-ul** trimis de noul plugin, fiecare `line_item` include **ean** (din meta `gtin_ean` pe produs Woo), pentru cazul în care backend-ul WebGSM emite factura și are nevoie de EAN.

## Rezumat

| Element | Status |
|--------|--------|
| Locație facturare | Theme: `includes/facturi.php` |
| Modificări făcute | **Niciuna** – doar verificare |
| Compatibilitate cu WebGSM Woo Sync | Plugin-ul nu interferează; facturarea rămâne în Woo. |
