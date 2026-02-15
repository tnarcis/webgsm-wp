# Spec: Plugin WooCommerce pentru WebGSM (și opțional SmartBill)

**Scop:** Să nu mai depindem de „uitatul” de sync manual. Plugin instalat pe WordPress/WooCommerce care notifică automat backend-ul WebGSM (și, dacă vrei, poate integra și SmartBill). Alt Cursor/developer pe site va implementa plugin-ul după aceste indicații.

---

## 1. Cum stă treaba acum: Woo + SmartBill

- **Facturile de vânzare** le emite **SmartBill** (API SmartBill).
- **Unde se generează factura** în practică:
  - **Variantă A (recomandată):** WooCommerce trimite evenimentul (comandă completată) către **WebGSM**; **WebGSM** scade stocul în DB și apelează **API SmartBill** pentru factură (date client, linii, EAN din DB). Un singur loc (WebGSM) controlează stocul și factura.
  - **Variantă B:** Un plugin pe **WooCommerce** apelează direct **API SmartBill** când comanda devine „Completed”. Atunci Woo trebuie să aibă toate datele (EAN, cod produs = SKU) și chei SmartBill în plugin; factura se creează din Woo, iar WebGSM trebuie notificat doar pentru stoc (sau îl mai tragi cu „Stoc din Woo” cum facem acum).

**Recomandare:** **Variantă A** — plugin-ul Woo doar **trimite evenimente către WebGSM** (webhook). WebGSM face stoc + facturare SmartBill. Un singur flux, o singură sursă de adevăr (DB-ul nostru).

---

## 2. Ce poate face plugin-ul (alegeri pentru tine)

| Nr | Funcție | Descriere | Întrebare pentru tine |
|----|--------|-----------|------------------------|
| **2.1** | **Notificare la schimbare status comandă** | Când o comandă trece în „Completed” (sau „Processing”), plugin-ul trimite un request (POST) către URL-ul tău WebGSM cu datele comenzii. WebGSM poate scădea stocul și poate porni factura în SmartBill. | **Vrei asta?** (recomandat: da) |
| **2.2** | **Notificare la modificare produs** | Când cineva salvează un produs în Woo (edit), plugin-ul trimite către WebGSM câmpurile modificate (SKU, stoc, preț, nume, etc.). WebGSM poate actualiza DB-ul. | **Vrei și asta?** (dacă sursa de adevăr rămâne DB-ul nostru și doar Woo se actualizează din aplicație, poate nu e necesar.) |
| **2.3** | **Factură SmartBill din plugin** | Plugin-ul, la „Order completed”, apelează direct API SmartBill (cu date din Woo: client, linii, EAN din meta produs). Fără să treacă prin WebGSM. | **Preferi factura din WebGSM (2.1) sau direct din Woo (2.3)?** |

**Rezumat recomandat:**  
- **Da** la 2.1 (notificare la status comandă → WebGSM; WebGSM face stoc + SmartBill).  
- **Nu** la 2.2 dacă modificările de produs le faci doar din aplicația WebGSM și apoi sync către Woo.  
- **Nu** la 2.3 dacă vrei un singur loc de facturare (WebGSM + SmartBill).

---

## 2b. Decizii finale (răspunsuri client)

| Întrebare | Răspuns | Notă |
|-----------|---------|------|
| Notificare la status comandă | **Da** | |
| La ce status(uri) + retur în stoc / vânzare locală | Vezi **Statusuri și retur în stoc** mai jos | |
| Factură SmartBill | **WebGSM** | Factura o emite WebGSM după primirea webhook-ului. |
| Notificare la modificare produs | **Nu** (recomandat) | Sursa de adevăr rămâne DB-ul; sync DB → Woo. |
| URL + GDPR / România / UE | **Conform deciziei tehnice; GDPR și legislație Ro/UE obligatorii** | Verificare compliance înainte de go-live. |
| SKU / EAN | **SKU = al nostru (generat Supabase la adăugare produs); EAN în meta Woo** | Plugin trimite SKU în `line_items`; dacă există meta `gtin_ean` (sau similar), îl include ca `ean` pe linie pentru SmartBill. |

### Statusuri și retur în stoc / vânzare locală sau „folosit”

- **Scădere stoc:** trimite webhook când statusul devine **Completed** (comandă finalizată, livrată/încheiată). WebGSM scade stocul doar la acest eveniment, nu la Processing — astfel eviți să scazi la comandă plătită și apoi să rămână produsul „blocat” dacă comanda e anulată.
- **Reintrare în stoc:** trimite webhook și pentru **Cancelled** și **Refunded**. WebGSM, la primire, face mișcare **retur_client** (sau anulare vânzare) și readaugă cantitățile în stoc. Plugin-ul trimite același format, cu `status_new`: `cancelled` / `refunded` și același `order` + `line_items`; WebGSM interpretează și reface stocul.
- **O bucată, vânzare locală sau folosit (service):**
  - **Vânzare locală:** se poate face o **comandă în Woo** (ex. cash la livrare, status marcat Completed). La Completed se trimite webhook → WebGSM scade stocul și poate emite factura. Nu e nevoie de alt flux.
  - **Folosit (ex. piesă folosită la reparație, nu vândută):** nu trece prin Woo. Se înregistrează în **WebGSM** ca **consum** (bon de consum / mișcare stoc tip consum). Stocul se scade din aplicația noastră, nu din plugin. Woo reflectă apoi stocul la următorul sync DB → Woo (sau la „Stoc din Woo” dacă sursa de adevăr pentru acel produs e Woo).

**Rezumat:** Plugin trimite la **Completed** (scădere stoc + factură), **Cancelled**, **Refunded** (reintrare stoc). Vânzare locală = comandă Woo Completed. Piesă folosită la service = doar în WebGSM (consum), fără webhook.

---

## 3. Specificație tehnică pentru plugin (pentru developer pe site)

### 3.1 Rolul plugin-ului

- Să trimită **POST** către un **URL WebGSM** (configurabil în setările plugin-ului) cu:
  - **La schimbare status comandă:** payload cu comanda (id, status, linii cu product_id, sku, quantity, preț, date client, etc.).
  - Opțional: **La salvare produs:** payload cu produsul (id, sku, stock_quantity, price, name, etc.).

### 3.2 WordPress / WooCommerce hooks

- **Pentru comenzi:**  
  - Hook: `woocommerce_order_status_changed` (parametri: `order_id`, `old_status`, `new_status`, `order`).  
  - **Trimite webhook la:** `completed` (scădere stoc + factură), `cancelled`, `refunded` (reintrare în stoc).  
  - Setări plugin: lista de statusuri e configurabilă (recomandat: completed, cancelled, refunded bifate).

- **Pentru produs (opțional):**  
  - `woocommerce_update_product` sau `woocommerce_process_product_meta`  
  - Doar dacă ai ales și 2.2.

### 3.3 URL și autentificare

- **URL endpoint:** configurat în plugin, ex. `https://api.webgsm.ro/webhook/woo/order` (sau ce punem noi live).  
- **Autentificare:** un **secret partajat** (parolă lungă) stocat atât în plugin, cât și în WebGSM. La fiecare request, plugin-ul trimite un header, ex.:  
  `X-WebGSM-Signature: HMAC-SHA256(body, secret)`  
  WebGSM verifică semnătura; dacă nu e ok, returnează 401.  
- **Metodă:** POST.  
- **Content-Type:** `application/json`.  
- **Body:** JSON (vezi mai jos).

### 3.4 Format payload – eveniment comandă (obligatoriu)

Plugin-ul trimite un JSON de forma (exemplu):

```json
{
  "event": "order.status_changed",
  "timestamp": "2026-02-09T12:00:00Z",
  "order_id": 12345,
  "order_number": "12345",
  "status_old": "processing",
  "status_new": "completed",
  "order": {
    "id": 12345,
    "billing": {
      "first_name": "...",
      "last_name": "...",
      "company": "",
      "address_1": "...",
      "city": "...",
      "postcode": "...",
      "country": "RO",
      "email": "...",
      "phone": "...",
      "cui": ""
    },
    "line_items": [
      {
        "product_id": 99,
        "sku": "100001",
        "ean": "5941234567890",
        "name": "Ecran Samsung ...",
        "quantity": 2,
        "price": "150.00",
        "total": "300.00"
      }
    ],
    "total": "350.00",
    "currency": "RON",
    "date_created": "2026-02-09T10:00:00",
    "payment_method": "stripe",
    "payment_method_title": "Card"
  }
}
```

- Câmpuri minim necesare: `order_id`, `status_new`, `order.billing` (nume, adresă, CUI, email, telefon), `order.line_items` (sku, quantity, price/total), `order.total`, `order.date_created`.  
- **SKU:** este SKU-ul nostru intern (generat în Supabase la adăugare produs), deja prezent pe produsul Woo după sync. Includeți-l obligatoriu în fiecare `line_item` ca `sku`.  
- **EAN:** dacă produsul are în Woo meta `gtin_ean` (sau alt key folosit pentru EAN), includeți-l în fiecare `line_item` ca `ean` pentru factura SmartBill.

### 3.5 Format payload – eveniment produs (opțional)

Dacă implementezi și 2.2:

```json
{
  "event": "product.updated",
  "timestamp": "2026-02-09T12:00:00Z",
  "product": {
    "id": 99,
    "sku": "100001",
    "name": "...",
    "regular_price": "150.00",
    "sale_price": "",
    "stock_quantity": 5,
    "stock_status": "instock",
    "description": "...",
    "short_description": "..."
  }
}
```

### 3.6 Comportament plugin

- După ce trimite POST, verifică **status code**:
  - **2xx:** succes; nu afișa eroare.
  - **4xx/5xx:** log în WordPress (ex. `error_log`) și opțional notificare admin (ex. o singură notificare pe zi pentru aceeași eroare).
- **Timeout:** ex. 15 secunde. Dacă timeout, retry 1–2 ori cu backoff (ex. după 5 s, 15 s).
- **Idempotency:** trimite mereu `order_id` (și eventual `order_id + status_new`). WebGSM poate folosi `order_id` ca idempotency key ca să nu proceseze aceeași comandă de două ori.

### 3.7 Setări în admin (exemplu)

- **URL endpoint WebGSM** (obligatoriu).  
- **Secret pentru semnătură** (obligatoriu).  
- **Statusuri pentru care se trimite webhook** (checkbox: **Completed**, **Cancelled**, **Refunded** — toate trei recomandate).  
- Opțional: **Activează notificare la modificare produs** (recomandat: nu).  
- Opțional: **Log requests** (da/nu) pentru debug.

### 3.8 GDPR și conformitate România / UE

- Datele trimise în webhook (date facturare, email, telefon, adresă) sunt **date personale**. Implementarea trebuie să fie **GDPR compliant** și în linie cu cerințele din **România și UE** (minimizare date, securitate, drepturile persoanei vizate, documentație).  
- **Recomandare:** înainte de go-live, analiză compliance (baza legală, informare, retenție, acces securizat la endpoint, politici de confidențialitate). Spec-ul nu înlocuiește acest review.

---

## 4. Ce face WebGSM (partea noastră, de implementat)

- **Endpoint:** `POST /webhook/woo/order` (sau nume similar).  
- Verifică header-ul `X-WebGSM-Signature` cu secretul partajat.  
- Parsează JSON; `event === "order.status_changed"`:
  - **status_new === "completed":** salvează/actualizează comanda în `shop_order` / `order_line`; scade stocul (mișcare `vanzare`) per linie (match pe SKU); pune în coadă eveniment „creare factură SmartBill” (client, linii, EAN din payload).  
  - **status_new === "cancelled" sau "refunded":** readaugă stocul (mișcare retur / anulare vânzare) pentru liniile comenzii; nu se emite factură.  
- Răspunde **200 OK** rapid (< 2 s); procesarea (stoc, factură) se face async (worker/cron).  
- **GDPR / conformitate:** prelucrarea datelor din webhook (inclusiv date facturare) trebuie aliniată la GDPR și legislația Ro/UE; review înainte de go-live.

---

## 5. Rezumat pentru developer (site / plugin)

- **Trimite webhook la:** status **completed** (scădere stoc + factură din WebGSM), **cancelled**, **refunded** (reintrare stoc).  
- **Nu** trimite la modificare produs (sursa de adevăr = DB WebGSM).  
- **SKU** în `line_items` = SKU intern (Supabase); **EAN** din meta produs Woo (ex. `gtin_ean`) în `line_items[].ean`.  
- **Factură:** o emite **WebGSM** (API SmartBill), nu plugin-ul.  
- **GDPR / Ro / UE:** implementare conformă; analiză compliance înainte de go-live.  
- **URL endpoint:** configurabil în plugin; se completează când API-ul WebGSM e gata.
