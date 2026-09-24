# Modul Facturi - Oblio Integration

> Generare și gestiune facturi automate via Oblio API (cu descărcare gestiune)

## Ce face

1. Generare automată facturi la finalizare comandă (card / ramburs)
2. Descărcare PDF facturi din contul clientului
3. Setări admin Oblio (CIF, token, serie, gestiune)
4. Descărcare stoc (`useStock`) pe gestiunea configurată
5. Storno la finalizare retur (`retururi.php`)

## Fișiere

- `includes/facturi.php` — API + UI facturi
- `includes/retururi.php` — storno Oblio

## Setări admin

`WooCommerce → Setări Oblio`

| Câmp | Descriere |
|------|-----------|
| Email Oblio | `client_id` (email cont) |
| Token API | `client_secret` din Setări → Date Cont |
| CIF Firmă | CIF emitent |
| Serie Factură | Serie din nomenclator Oblio |
| Gestiune | Nume gestiune (exact) |
| Punct de lucru | Implicit `Sediu` |
| Descarcă stoc | `useStock = 1` |

## Meta comandă

- `_oblio_invoice_number`
- `_oblio_invoice_series`
- `_oblio_invoice_date`
- `_oblio_invoice_link`

## Funcții

- `oblio_get_access_token()` — OAuth Bearer (cache)
- `oblio_request($path, $data, $method)`
- `genereaza_factura_oblio($order_id, $force = false)`
- `get_factura_pdf_oblio($order_id)`

## Debugging

```bash
grep "Oblio" wp-content/debug.log
```

## Docs

- [Oblio API](https://www.oblio.eu/api/)
