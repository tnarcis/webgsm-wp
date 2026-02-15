# Backend WebGSM – receptie webhook WooCommerce

Plugin-ul **WebGSM Woo Sync** (pe WordPress) trimite POST la schimbarea statusului comenzii. Acest document descrie ce trebuie implementat pe **backend-ul WebGSM** (API-ul vostru) pentru a primi și procesa webhook-ul. Partea de **facturare SmartBill** o aveți deja; aici este doar receptia + stoc + idempotency.

---

## 1. Endpoint

- **URL:** configurat în plugin (ex. `https://api.webgsm.ro/webhook/woo/order`).
- **Metodă:** POST.
- **Content-Type:** `application/json`.
- **Body:** JSON (structura mai jos).

---

## 2. Autentificare

Plugin-ul trimite header:

```
X-WebGSM-Signature: <HMAC-SHA256 hex al body-ului raw, cu secretul partajat>
```

**Verificare pe backend (pseudo-cod):**

```
secret = env.WEBGSM_WEBHOOK_SECRET  // același secret ca în setările plugin
body_raw = request.raw_body        // exact bytes primiti (nu JSON parsat re-serializat)
expected = HMAC-SHA256(body_raw, secret).hex()
received = request.headers["X-WebGSM-Signature"]
if (expected !== received) return 401
```

- Dacă semnătura nu se potrivește: răspunde **401 Unauthorized**.
- Secretul trebuie stocat în siguranță (env var) și același value setat și în plugin (Setări → WebGSM Woo Sync).

---

## 3. Format payload (order.status_changed)

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

- **order_id** + **status_new** sunt suficiente pentru idempotency (vezi mai jos).
- **line_items[].sku** = SKU intern (Supabase); **line_items[].ean** = EAN din meta Woo (pentru SmartBill, dacă factura o emiteți voi).

---

## 4. Comportament recomandat

1. **Răspuns rapid:** După validare semnătură și parsare JSON, răspunde **200 OK** în &lt; 2 s. Procesarea grea (stoc, factură) să fie **asincronă** (worker / coadă / cron).

2. **Idempotency:** Folosiți `order_id` (și eventual `order_id + status_new`) ca cheie. Dacă primiți același eveniment de două ori (retry din plugin), nu scădeți stocul de două ori și nu emiteți două facturi.

3. **status_new:**
   - **completed** → scădere stoc (mișcare vânzare) + puneți în coadă „creare factură SmartBill” (dacă factura o face backend-ul; altfel factura rămâne din Woo cum aveți acum).
   - **cancelled** / **refunded** → reintrare în stoc (retur / anulare); nu emiteți factură.

4. **Erori:** La 4xx/5xx plugin-ul face retry (backoff 5s, 15s) și loghează. Backend-ul la eroare internă poate răspunde 500; plugin-ul va încerca din nou.

---

## 5. Ce NU face acest document

- **Facturare SmartBill:** O aveți implementată în WordPress (Setări SmartBill, `facturi.php`). Backend-ul poate, opțional, emite factura la `completed` folosind datele din webhook (billing, line_items cu EAN); altfel fluxul actual (factură din Woo) rămâne neschimbat.
- **GDPR:** Prelucrarea datelor din webhook (billing, email, telefon) trebuie aliniată la GDPR și legislația Ro/UE; review înainte de go-live.

---

## 6. Rezumat

| Pas | Acțiune |
|-----|--------|
| 1 | Verificați `X-WebGSM-Signature` (HMAC-SHA256 al body raw). 401 dacă nu e ok. |
| 2 | Parseați JSON; verificați `event === "order.status_changed"`. |
| 3 | Verificați idempotency (order_id + status_new deja procesat?). |
| 4 | Răspundeți 200 OK rapid. |
| 5 | Procesați async: la **completed** – scădere stoc (+ eventual factură); la **cancelled** / **refunded** – reintrare stoc. |
