# WebGSM WooCommerce Sync

Plugin WordPress care trimite evenimente (schimbare status comandă) către backend-ul WebGSM. Conform [SPEC-plugin-woocommerce-webgsm](../docs/architecture/SPEC-plugin-woocommerce-webgsm.md).

## Ce face

- La schimbarea statusului comenzii în **Completed**, **Cancelled** sau **Refunded**, trimite un POST (webhook) către URL-ul configurat, cu payload JSON (order_id, status_old, status_new, order cu billing și line_items).
- Semnătură: header `X-WebGSM-Signature` = HMAC-SHA256(body, secret).
- Retry: la eșec (4xx/5xx sau timeout) reîncearcă după 5s și 15s.

## Setări

**Setări → WebGSM Woo Sync**

- **URL endpoint WebGSM** – obligatoriu (ex. `https://api.webgsm.ro/webhook/woo/order`).
- **Secret (HMAC)** – același secret ca pe backend; folosit pentru semnătură.
- **Statusuri** – bifați Completed, Cancelled, Refunded (recomandat toate trei).
- **Log requests** – opțional, pentru debug (scrie în `debug.log`).

## Facturare SmartBill

Facturarea SmartBill este implementată în **tema** (martfury-child): `includes/facturi.php`. La status **Processing** (plăți online) sau **Completed** (ramburs) se generează factura direct din Woo către SmartBill. Acest plugin **nu** înlocuiește acea logică; doar notifică WebGSM pentru stoc (și eventual pentru o facturare ulterioară din backend, dacă o implementați).

## Backend WebGSM

Pentru implementarea receptiei pe API-ul WebGSM: [WEBHOOK-BACKEND-WEBGSM.md](../../../docs/architecture/WEBHOOK-BACKEND-WEBGSM.md).
