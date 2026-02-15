# Backend WebGSM — Endpoint webhook WooCommerce

Documentație pentru implementarea endpoint-ului care primește notificările de la plugin-ul **WebGSM Woo Sync** (WooCommerce). Plugin-ul trimite POST la schimbarea statusului comenzii; backend-ul verifică semnătura, înregistrează payload-ul și procesează async (stoc, eventual factură).

---

## 1. Endpoint

- **URL:** `POST /webhook/woo/order` (sau prefix ales: `/api/webhook/woo/order`).
- **Content-Type:** `application/json`.
- **Body:** JSON (format în secțiunea 3).

---

## 2. Verificarea semnăturii (HMAC)

Plugin-ul trimite header-ul:

```http
X-WebGSM-Signature: <hex(HMAC-SHA256(raw_body, secret))>
```

**Reguli backend:**

1. **Citește body-ul raw** înainte de a-l parsa ca JSON (pentru HMAC trebuie exact octeții trimiși). Nu folosi body-ul deja parsat.
2. **Secret partajat:** același string stocat și în plugin (Setări → WebGSM Woo Sync) și în config-ul backend-ului (variabilă de mediu, ex. `WEBHOOK_WOO_SECRET`).
3. **Calcul semnătură așteptată:**
   - `signature_expected = HMAC-SHA256(raw_body, secret)`
   - Codificare: **hex** (lowercase sau uppercase, atât timp cât comparația e consistentă). Plugin-ul trimite de obicei hex.
4. **Comparație:** comparație **constant-time** între `X-WebGSM-Signature` (trimis) și `signature_expected` (hex). Evită `==` simplu pentru a reduce risk de timing attacks.
5. **Dacă semnătura nu e validă:** răspuns **401 Unauthorized**; nu procesa body-ul.

**Pseudocod:**

```
raw_body = request.raw_body   // bytes, nemodificat
header_sig = request.headers["X-WebGSM-Signature"]
expected  = hex(hmac_sha256(raw_body, WEBHOOK_WOO_SECRET))
if not constant_time_compare(header_sig, expected):
    return 401
```

---

## 3. Format payload și câmpuri folosite

Payload-ul este JSON (după validarea HMAC poți parsa `raw_body`).

**Eveniment:** `event === "order.status_changed"`.

**Câmpuri obligatorii:**

| Câmp | Tip | Folosire |
|------|-----|----------|
| `order_id` | number | ID comandă Woo; folosit în idempotency. |
| `status_old` | string | Status anterior (opțional pentru logică). |
| `status_new` | string | **completed** / **cancelled** / **refunded** — determină acțiunea. |
| `order` | object | Detalii comandă. |
| `order.billing` | object | first_name, last_name, company, address_1, city, postcode, country, email, phone, cui. |
| `order.line_items` | array | Fiecare element: `sku` (obligatoriu), `ean` (opțional), `quantity`, `price`, `total`, `name`, `product_id`. |
| `order.total` | string | Total comandă. |
| `order.date_created` | string | ISO date. |
| `order.currency` | string | ex. RON. |

**Idempotency:** același `order_id` + `status_new` nu trebuie procesat de două ori. Cheie recomandată: `woo_order_{order_id}_{status_new}` (ex. `woo_order_12345_completed`).

---

## 4. Idempotency

- **Cheie:** `idempotency_key = "woo_order_" + order_id + "_" + status_new` (toate string).
- **Mecanism:**
  - Înainte de orice procesare: înregistrare în `webhook_log` (sau tabel echivalent) cu `idempotency_key`, `raw_payload` (JSON), `processed = false`, `source = 'woocommerce'`, `event_type = 'order.status_changed'`.
  - Dacă **UNIQUE constraint** pe `idempotency_key` e încălcat (înregistrare deja există), consideră evenimentul deja primit → răspunde **200 OK** fără să reprocesezi.
  - Dacă inserarea reușește → răspunde **200 OK** și lasă procesarea pentru worker-ul async.

---

## 5. Răspuns și procesare asincronă

- **Răspuns:** după validare HMAC, validare JSON și înregistrare idempotency (insert sau detect duplicate): întoarce **200 OK** în **sub ~2 secunde**. Nu aștepta pe scăderea stocului sau pe factură.
- **Procesare:** un **worker** (cron, queue, background job) citește din `webhook_log` înregistrările cu `processed = false`, pentru fiecare:
  - **status_new === "completed":**
    - Creează/actualizează `shop_order` și `order_line` (din `order`, billing, line_items).
    - **Scade stocul** în DB: pentru fiecare linie, match pe `sku` (product_id din DB), înregistrare mișcare `vanzare` și actualizare `stock`. Dacă nu există produs cu acel SKU, log eroare și continuă sau marchează failed.
    - Opțional: pune în coadă (outbox) eveniment „creare factură SmartBill” cu datele comenzii (client, linii cu sku/ean, total). Integrarea SmartBill o aveți deja; acest doc nu o implementează.
  - **status_new === "cancelled" sau "refunded":**
    - **Reintrare în stoc:** pentru liniile comenzii (identificate prin order_id deja salvat sau din payload), anulează vânzarea: mișcare de tip retur / anulare (sau mișcare inversă) astfel încât cantitățile să revină în `stock`. Folosești același `order_id` și `line_items` (sku + quantity) ca la completed.
  - După procesare (succes sau eșec): actualizează `webhook_log.processed = true`, `processed_at = now()`, eventual `error` dacă a eșuat.

---

## 6. Erori și retry

- **400 Bad Request:** body invalid (nu e JSON sau lipsește `event` / `order_id` / `status_new` / `order.line_items`). Nu înregistra în webhook_log cu processed=true; plugin-ul poate retrimite.
- **401 Unauthorized:** semnătură HMAC invalidă.
- **200 OK:** eveniment acceptat (inserat sau deja cunoscut). Orice eroare ulterioară în worker se tratează intern (retry worker, alertă, etc.).

Plugin-ul face retry la 5s și 15s la 4xx/5xx; de aceea răspunsul rapid 200 e important.

---

## 7. Tabel / structură (referință)

Folosirea tabelelor existente din schema WebGSM:

- **webhook_log:** `id`, `source` ('woocommerce'), `event_type` ('order.status_changed'), `idempotency_key` (UNIQUE), `raw_payload` (JSONB), `processed`, `processed_at`, `error`, `created_at`.
- **shop_order:** `woo_order_id`, `order_number`, `client_id`, `status`, `total_ron`, `order_date`, etc.
- **order_line:** `order_id`, `product_id`, `quantity`, `unit_price_ron`, `total_ron`.
- **stock** / **stock_movement:** pentru scădere (vanzare) și reintrare (cancelled/refunded).

Match produs din payload: `line_items[].sku` → `product.sku` → `product_id` pentru `order_line` și pentru mișcări de stoc.

---

## 8. Rezumat

1. **POST** pe `/webhook/woo/order`, body JSON.
2. **Verificare** `X-WebGSM-Signature`: HMAC-SHA256 pe **raw body**, hex, comparație constant-time cu secret partajat.
3. **Parse** JSON; validează `event`, `order_id`, `status_new`, `order`, `order.line_items`.
4. **Idempotency:** `idempotency_key = "woo_order_{order_id}_{status_new}"`; INSERT în `webhook_log`; la conflict (duplicate) → 200 fără reprocesare.
5. **Răspuns 200** rapid.
6. **Worker:** pentru `processed = false` — completed: stoc scăzut + eventual coadă factură SmartBill; cancelled/refunded: stoc readăugat.

Integrarea SmartBill (generare factură la completed) rămâne în codul existent; acest document nu o descrie, doar menționează că poți pune evenimentul în outbox pentru ea.
