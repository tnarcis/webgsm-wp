# ✅ RĂSPUNSURI TEST AI - WebGSM

> **Folosește aceste întrebări pentru a testa orice AI nou înainte să înceapă lucrul**

---

## 📋 **ÎNTREBĂRILE (Copy/Paste pentru AI):**

```
Înainte să începem, răspunde la aceste 5 întrebări rapide:

1. Unde modific culoarea butoanelor?
2. Unde găsesc logica pentru facturi SmartBill?
3. Ce fac după fiecare modificare?
4. Pot pune CSS în fișiere PHP? (DA/NU)
5. Unde găsesc instrucțiuni de debugging pentru facturi?
```

---

## ✅ **RĂSPUNSURI CORECTE:**

### **1. Unde modific culoarea butoanelor?**

**✅ Răspuns COMPLET corect:**
```
Locație: includes/webgsm-design-system.php (linia ~17-32)
SAU (viitor): assets/css/design-system.css

Cum găsesc:
→ INDEX.md → tabel "Ce vreau să modific" → "Culoarea butoanelor"

Selector CSS:
.woocommerce .button {
    background-color: #2196F3;
}
```

**✅ Răspuns MINIM acceptabil:**
```
includes/webgsm-design-system.php
```

**❌ Răspunsuri GREȘITE:**
- "În functions.php" → NU!
- "În style.css" → Parțial, dar nu e locul principal
- "Creez fișier nou" → NU!
- "Nu știu" → Trebuie să citească INDEX.md

**Scor:**
- Răspuns complet: **10/10**
- Răspuns minim: **7/10**
- Răspuns greșit: **0/10**

---

### **2. Unde găsesc logica pentru facturi SmartBill?**

**✅ Răspuns COMPLET corect:**
```
Fișier: includes/facturi.php (630 linii)
Documentație: modules/invoices/README.md

Funcții principale:
- smartbill_request() → API wrapper
- genereaza_factura_smartbill() → Generare factură
- get_factura_pdf_smartbill() → Download PDF
- webgsm_auto_generate_sku() → Auto SKU produse

Hook-uri:
- woocommerce_payment_complete
- woocommerce_order_status_completed
- save_post_product
```

**✅ Răspuns MINIM acceptabil:**
```
includes/facturi.php
Documentație: modules/invoices/README.md
```

**❌ Răspunsuri GREȘITE:**
- "În plugin-uri" → NU, e în temă
- "Nu știu" → Trebuie INDEX.md
- "Caut prin cod" → NU, există documentație!

**Scor:**
- Răspuns complet: **10/10**
- Răspuns minim: **6/10**
- Răspuns greșit: **0/10**

---

### **3. Ce fac după fiecare modificare?**

**✅ Răspuns COMPLET corect:**
```
OBLIGATORIU:
1. Testez modificarea (manual + verificare funcționalitate existentă)
2. Update CHANGELOG.md cu:
   - Data: [YYYY-MM-DD]
   - Modul afectat (ex: Invoices / SmartBill)
   - Descriere clară modificare
   - Fișiere modificate (cu linii dacă e relevant)
3. Git commit cu mesaj descriptiv

OPȚIONAL (dacă e nevoie):
4. Update README.md al modulului
5. Update documentație tehnică
```

**✅ Răspuns MINIM acceptabil:**
```
1. Testez
2. Update CHANGELOG.md
3. Git commit
```

**❌ Răspunsuri GREȘITE:**
- "Doar commit" → NU, lipsește CHANGELOG!
- "Nimic special" → GREȘIT complet!
- "Update README" (fără CHANGELOG) → NU!

**Scor:**
- Răspuns complet: **10/10**
- Răspuns minim: **7/10**
- Răspuns fără CHANGELOG: **0/10** ⚠️ CRITIC

---

### **4. Pot pune CSS în fișiere PHP? (DA/NU)**

**✅ Răspuns CORECT:**
```
NU!

Motivație:
- Încalcă principiul separare design vs. logică
- Cache browser nu funcționează
- Minificare imposibilă
- Greu de modificat/menținut

Unde pun CSS:
- assets/css/ (recomandat viitor)
- includes/webgsm-design-system.php (temporar acceptabil)
- Încărcat cu wp_enqueue_style()

EXCEPȚIE rară:
- Backward compatibility cu cod vechi (până la refactoring)
- TREBUIE marcat cu comentariu: // TODO: Mută în assets/css/
```

**❌ Răspunsuri GREȘITE:**
- "DA" → GREȘIT TOTAL!
- "Depinde" (fără explicație) → INSUFICIENT
- "Nu contează" → GREȘIT!

**Scor:**
- Răspuns NU + motivație: **10/10**
- Răspuns doar NU: **7/10**
- Răspuns DA: **0/10** ⚠️ CRITIC

---

### **5. Unde găsesc instrucțiuni de debugging pentru facturi?**

**✅ Răspuns COMPLET corect:**
```
Locații documentație:
1. INDEX.md → secțiunea "DEBUGGING RAPID" → "Problem: Factură nu se generează"
2. modules/invoices/README.md → secțiunea "🐛 DEBUGGING"

Pași debugging:
1. Verifică: WooCommerce → Setări SmartBill → ☑ API Activ
2. Verifică: wp-content/debug.log (grep "SmartBill")
3. Verifică order meta:
   - _smartbill_invoice_number
   - _smartbill_invoice_series
4. Test manual: În comandă → Buton "Generează factură manual"

Log-uri relevante:
- SmartBill API Request
- SmartBill Product: ... | SKU: ... | TVA: ...
- SmartBill Error: ...
```

**✅ Răspuns MINIM acceptabil:**
```
1. INDEX.md → "DEBUGGING RAPID"
2. modules/invoices/README.md → secțiunea "DEBUGGING"
```

**❌ Răspunsuri GREȘITE:**
- "Caut prin cod" → NU, există documentație!
- "Încerc random" → NU!
- "Nu știu" → Trebuie INDEX.md

**Scor:**
- Răspuns complet: **10/10**
- Răspuns minim: **6/10**
- Răspuns greșit: **0/10**

---

## 📊 **SCORING TOTAL:**

### **Interpretare scor:**

| Scor Total | Evaluare | Acțiune |
|------------|----------|---------|
| **45-50** | 🏆 **EXCELENT** | AI-ul e pregătit, poate începe lucrul! |
| **35-44** | ✅ **ACCEPTABIL** | Poate începe, dar monitorizează primele task-uri |
| **25-34** | ⚠️ **INSUFICIENT** | Cere AI-ului să recitească documentația |
| **0-24** | 🚫 **NEPREGĂTIT** | STOP! AI-ul trebuie să citească TOATĂ documentația |

### **Întrebări CRITICE (nu poate greși):**

- **Q3**: Ce fac după fiecare modificare? → TREBUIE să menționeze CHANGELOG
- **Q4**: Pot pune CSS în PHP? → TREBUIE să spună NU

**Dacă greșește la Q3 sau Q4 → STOP imediat!** ⛔

---

## 🎯 **PROMPT PENTRU TESTARE:**

**Copy/Paste asta pentru a testa AI-ul:**

```
📝 TEST DE VERIFICARE

Răspunde la aceste 5 întrebări pentru a demonstra că ai citit documentația:

1. Unde modific culoarea butoanelor?

2. Unde găsesc logica pentru facturi SmartBill?

3. Ce fac după fiecare modificare?

4. Pot pune CSS în fișiere PHP? (DA/NU + Motivație)

5. Unde găsesc instrucțiuni de debugging pentru facturi?

Răspunde COMPLET la fiecare întrebare (nu doar cu un cuvânt).
```

---

## ✅ **EXEMPLE RĂSPUNSURI AI**

### **🏆 EXEMPLU EXCELENT (50/50):**

```
1. Culoarea butoanelor se modifică în:
   - includes/webgsm-design-system.php (linia 23-27)
   - Găsesc exact locul în INDEX.md → tabel "Ce vreau să modific"
   - Selector: .woocommerce .button { background-color: #2196F3; }

2. Logica facturi SmartBill:
   - Fișier: includes/facturi.php
   - Documentație: modules/invoices/README.md
   - Funcții: smartbill_request(), genereaza_factura_smartbill(), etc.

3. După fiecare modificare:
   - Testez modificarea
   - Update CHANGELOG.md (data + modul + descriere + fișiere)
   - Git commit descriptiv
   - (Optional) Update README.md modul

4. NU pot pune CSS în PHP!
   - Motivație: Separare design vs. logică, cache, minificare
   - Locație corectă: assets/css/ sau webgsm-design-system.php
   - Încărcat cu wp_enqueue_style()

5. Debugging facturi:
   - INDEX.md → "DEBUGGING RAPID" → "Problem: Factură nu se generează"
   - modules/invoices/README.md → secțiunea "DEBUGGING"
   - Verificări: API activ, debug.log, order meta
```

**Scor: 50/50 ✅ Poate începe lucrul!**

---

### **✅ EXEMPLU ACCEPTABIL (37/50):**

```
1. includes/webgsm-design-system.php

2. includes/facturi.php
   Documentație: modules/invoices/README.md

3. Testez, update CHANGELOG.md, commit

4. NU. CSS trebuie în fișiere separate pentru separare design/logică.

5. INDEX.md → DEBUGGING RAPID
   modules/invoices/README.md
```

**Scor: 37/50 ✅ Acceptabil, poate începe (monitorizează).**

---

### **⚠️ EXEMPLU INSUFICIENT (22/50):**

```
1. În fișierul de stiluri

2. În includes/ undeva

3. Fac commit

4. Depinde de situație

5. Caut prin cod
```

**Scor: 22/50 ⚠️ INSUFICIENT! Cere să recitească documentația.**

---

### **🚫 EXEMPLU NEPREGĂTIT (5/50):**

```
1. Nu știu exact

2. Probabil în plugin-uri

3. Nimic special

4. DA, pot

5. Nu știu
```

**Scor: 5/50 🚫 NEPREGĂTIT! STOP → Citește TOATĂ documentația!**

---

## 🔄 **RETESTARE**

Dacă AI-ul a picat testul:

```
⚠️ Scor insuficient: [SCOR]/50

Te rog recitește:
□ README.md (5 min)
□ INDEX.md (2 min)
□ CHANGELOG.md (3 min)
□ [Module specifice dacă e nevoie]

După ce citești, voi re-testa cu aceleași întrebări.
Ai nevoie de scor minim 35/50 pentru a începe lucrul.
```

---

## 💡 **TIPS PENTRU EVALUARE:**

### **Semnale că AI-ul E PREGĂTIT:**
- ✅ Citează fișiere exacte (cu path-uri)
- ✅ Menționează CHANGELOG.md spontan
- ✅ Explică MOTIVAȚIA (nu doar răspunde DA/NU)
- ✅ Referă documentația (INDEX.md, modules/*/README.md)

### **Semnale că AI-ul NU E PREGĂTIT:**
- ❌ Răspunsuri vagi ("undeva", "probabil", "depinde")
- ❌ Nu menționează CHANGELOG
- ❌ Spune "DA" la CSS în PHP
- ❌ Spune "caut prin cod" în loc de documentație

---

**Ultima actualizare**: 2026-01-13

**Folosește acest fișier**: Înainte de a începe orice task cu un AI nou!
