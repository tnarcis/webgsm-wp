# Debug checkout pe iPhone (iOS)

**Dacă cu `?webgsm_debug=1` pagina se încarcă ciudat sau nu vezi opțiunile (Adaugă adresă / Salvează):** încarcă mai întâi checkout-ul **fără** parametru, apoi adaugă `?webgsm_debug=1` la URL și reîmprospătează. Instrumentele de debug se încarcă după ce pagina e gata, ca să nu blocheze nimic.

---

## 1. Mod debug în browser (fără Mac)

Pe **iPhone**, deschide checkout-ul cu parametrul de debug în URL:

```
https://siteul-tau.ro/checkout/?webgsm_debug=1
```

(sau adaugă `?webgsm_debug=1` la URL-ul curent al paginii de checkout)

**Ce se întâmplă:**
- Se încarcă **Eruda** – o consolă de dezvoltare în pagină (icon în colțul ecranului). Apasă pe icon → deschide **Console**, **Elements**, **Network**.
- Când deschizi popup-ul „Adaugă persoană” sau „Adaugă firmă”, apare **sus pe ecran un banner negru** cu text de forma:
  - `Footer: top=XXX bottom=YYY innerHeight=ZZZ visible=true/false`
- În **Console** (tab în Eruda) vei vedea același mesaj.

**Cum interpretezi:**
- **visible=true** → footer-ul e în viewport; dacă tot nu vezi butonul, poate e acoperit (z-index) sau tăiat (overflow).
- **visible=false** sau **top > innerHeight** → footer-ul e sub ecran (problema de layout/position pe iOS).
- **bottom** = poziția (în px) a bazei footer-ului față de partea de sus a viewport-ului.

Poți trimite un screenshot cu bannerul (sau valorile din el) pentru a ajusta CSS.

---

## 2. Safari Web Inspector (Mac + iPhone) – debugging complet

Ai nevoie de **Mac** cu Safari și **iPhone** cu cablu USB (sau pe același Wi‑Fi, unele versiuni permit).

### Pe iPhone
1. **Setări → Safari → Avansat** → activează **Web Inspector**.
2. Conectează iPhone-ul la Mac (sau asigură-te că sunt pe același rețea).
3. Deschide în **Safari** pe iPhone pagina de checkout (nu e nevoie de `?webgsm_debug=1`).

### Pe Mac
1. Deschide **Safari**.
2. Meniu **Safari → Preferințe → Avansat** → bifează **„Afișează meniul Dezvoltare în bara de meniu”**.
3. Meniu **Dezvoltare** → în listă apare **[Numele iPhone-ului]** → alege **pagina de checkout** (ex. „Checkout – WebGSM”).
4. Se deschide **Web Inspector**: tab-uri **Elements**, **Console**, **Network**, etc.

**Ce poți face:**
- **Elements:** selectează elementul `.popup-footer` când popup-ul e deschis și vezi **Styles** / **Computed** (dacă e `display: none`, `height: 0`, `bottom` negativ, etc.).
- **Console:** rulează:
  - `document.querySelector('.webgsm-popup .popup-footer')?.getBoundingClientRect()`  
  → vezi `top`, `bottom`, `height` în pixeli.
  - `window.innerHeight`  
  → înălțimea viewport-ului.
- Schimbi temporar CSS din Inspector ca să testezi (ex. `bottom: 0`, `position: fixed`) și vezi pe telefon dacă butonul apare.

---

## 3. Verificări rapide în Console (cu Eruda sau Web Inspector)

Când popup-ul „Adaugă persoană” / „Adaugă firmă” e deschis, rulează:

```javascript
var f = document.querySelector('.webgsm-popup .popup-footer');
console.log('Footer exists:', !!f);
if (f) {
  var r = f.getBoundingClientRect();
  console.log('top:', r.top, 'bottom:', r.bottom, 'height:', r.height);
  console.log('window.innerHeight:', window.innerHeight);
  console.log('visible:', r.top < window.innerHeight && r.bottom > 0);
}
```

Dacă **Footer exists: false** → footer-ul nu e în DOM (problema de HTML/JS).  
Dacă e **true** dar **visible: false** sau **bottom** foarte mare → footer-ul e jos sub ecran (problema de CSS pe iOS).

---

## 4. După ce ai valorile

Trimite:
- un **screenshot** cu bannerul de debug (top/bottom/innerHeight/visible), sau
- valorile din **Console** pentru `getBoundingClientRect()` și `innerHeight`,

și putem adapta regulile CSS (ex. `bottom`, `safe-area`, sau alt layout) pentru iOS.
