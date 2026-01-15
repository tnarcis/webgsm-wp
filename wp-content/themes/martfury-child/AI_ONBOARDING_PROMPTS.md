# 🤖 PROMPTURI PENTRU ALT AI - WebGSM

> **Copy/Paste aceste prompturi când lucrezi cu un AI nou pe proiect**

---

## 🎯 **PROMPT PRINCIPAL - ONBOARDING**

### **📋 Copy/Paste acest prompt la ORICE AI nou:**

```
🎯 ONBOARDING WebGSM:

Lucrez pe WordPress/WooCommerce custom pentru WebGSM.

📚 PAȘI OBLIGATORII ÎNAINTE de orice modificare:

1. Citește martfury-child/README.md (5 min) - overview complet structură
2. Citește martfury-child/INDEX.md (2 min) - găsire rapidă
3. Citește martfury-child/CHANGELOG.md (3 min) - istoric modificări
4. Identifică modulul relevant din INDEX.md pentru task-ul meu
5. Citește modules/{modul}/README.md - documentație detaliată modul
6. APOI propune-mi abordarea (NU implementa direct!)

⚠️ REGULI STRICTE:

❌ NU modifica NIMIC fără să citești documentația
❌ NU pune CSS în fișiere PHP (folosește assets/css/)
❌ NU pune JavaScript în fișiere PHP (folosește assets/js/)
❌ NU modifica WordPress core / WooCommerce core / tema părinte
❌ NU crea duplicate de funcționalități existente

✅ ÎNTOTDEAUNA:
✅ Propune abordarea ÎNAINTE să implementezi
✅ Update CHANGELOG.md după fiecare modificare
✅ Respectă structura modulară existentă
✅ Un modul = O funcționalitate
✅ Testează înainte de commit

🎯 TASK-UL MEU: [DESCRIE CE VREI AICI]

Confirmă că ai citit documentația și spune-mi ce ai înțeles despre:
- Structura proiectului
- Modulul relevant pentru task
- Unde vei face modificările
```

---

## ✅ **TEST DE VERIFICARE - Răspunsuri Corecte**

### **Întrebări pentru a testa AI-ul:**

```
Înainte să începem, răspunde la aceste 5 întrebări rapide:

1. Unde modific culoarea butoanelor?
2. Unde găsesc logica pentru facturi SmartBill?
3. Ce fac după fiecare modificare?
4. Pot pune CSS în fișiere PHP? (DA/NU)
5. Unde găsesc instrucțiuni de debugging pentru facturi?
```

### **📝 RĂSPUNSURI CORECTE:**

#### **1. Unde modific culoarea butoanelor?**
✅ **Răspuns corect:**
```
- Locație principală: includes/webgsm-design-system.php
- SAU (viitor): assets/css/design-system.css
- Verifică în INDEX.md → tabel "Ce vreau să modific" → "Culoarea butoanelor"
```

❌ **Răspuns greșit:**
- "În functions.php" → NU!
- "În style.css direct" → Parțial corect, dar nu e locul principal
- "Creez un fișier nou" → NU!

---

#### **2. Unde găsesc logica pentru facturi SmartBill?**
✅ **Răspuns corect:**
```
- Fișier actual: includes/facturi.php (630 linii)
- Documentație: modules/invoices/README.md
- Funcții principale:
  - smartbill_request() - API calls
  - genereaza_factura_smartbill() - Generare factură
  - webgsm_auto_generate_sku() - Auto SKU
```

❌ **Răspuns greșit:**
- "În plugin-uri" → NU, e în temă
- "Nu știu" → Trebuie să citească INDEX.md

---

#### **3. Ce fac după fiecare modificare?**
✅ **Răspuns corect:**
```
1. Testez modificarea
2. UPDATE CHANGELOG.md cu:
   - Data [YYYY-MM-DD]
   - Modul afectat
   - Descriere modificare
   - Fișiere modificate
3. Git commit cu mesaj descriptiv
4. (Optional) Update README.md al modulului dacă e nevoie
```

❌ **Răspuns greșit:**
- "Doar commit" → NU, lipsește CHANGELOG
- "Nimic special" → GREȘIT!

---

#### **4. Pot pune CSS în fișiere PHP? (DA/NU)**
✅ **Răspuns corect:**
```
NU! (cu excepții rare pentru backward compatibility)

CSS-ul trebuie:
- În assets/css/ (viitor)
- SAU în includes/webgsm-design-system.php (temporar)
- Încărcat cu wp_enqueue_style()

Motivație:
- Separare design de logică
- Cache browser
- Minificare posibilă
- Mai ușor de modificat
```

❌ **Răspuns greșit:**
- "DA" → GREȘIT!
- "Depinde" → NU, regula e clară

---

#### **5. Unde găsesc instrucțiuni de debugging pentru facturi?**
✅ **Răspuns corect:**
```
1. INDEX.md → secțiunea "DEBUGGING RAPID" → "Problem: Factură nu se generează"
2. modules/invoices/README.md → secțiunea "🐛 DEBUGGING"
3. Verificări:
   - WooCommerce → Setări SmartBill → API Activ
   - wp-content/debug.log → grep "SmartBill"
   - Order meta: _smartbill_invoice_number
```

❌ **Răspuns greșit:**
- "Caut prin cod" → NU, există documentație!
- "Nu știu" → Trebuie să citească INDEX.md

---

## 📋 **PROMPTURI PENTRU SCENARII SPECIFICE**

### **🎨 Scenariu 1: Modificare Design / CSS**

```
Vreau să modific: [DESCRIERE - ex: "culoarea butoanelor din albastru în roșu"]

PAȘI:
1. Citește INDEX.md → tabel "Ce vreau să modific" → găsește elementul
2. Deschide fișierul indicat (ex: includes/webgsm-design-system.php)
3. Caută selectorul CSS relevant (ex: .woocommerce .button)
4. Propune-mi modificarea EXACT (arată-mi vechiul vs. noul CSS)
5. Așteaptă confirmarea mea
6. Implementează
7. Update CHANGELOG.md cu:
   - Data: [2026-01-13]
   - Modul: Design / UI
   - Descriere: "Schimbat culoare butoane din #2196F3 în #FF5722"
   - Fișier: includes/webgsm-design-system.php (linia X-Y)

Confirmă că ai înțeles pașii.
```

---

### **🐛 Scenariu 2: Bug Fix / Debugging**

```
Am o problemă: [DESCRIERE - ex: "factură nu se generează pentru comandă #12345"]

PAȘI:
1. Citește INDEX.md → secțiunea "DEBUGGING RAPID" pentru problema mea
2. Urmează pașii de debugging din INDEX.md
3. Citește modules/[modul-relevant]/README.md (ex: modules/invoices/README.md)
4. Verifică log-urile: wp-content/debug.log
5. Raportează-mi ce ai găsit (erori, log-uri relevante)
6. Propune soluție cu explicație
7. Așteaptă confirmarea mea
8. Implementează fix-ul
9. Testează (arată-mi că merge)
10. Update CHANGELOG.md cu:
    - Data: [YYYY-MM-DD]
    - Modul: [modul]
    - Descriere: "FIX: [descriere bug] - cauză + soluție"
    - Fișiere modificate

NU implementa nimic fără să-mi raportezi mai întâi ce ai găsit!
```

---

### **✨ Scenariu 3: Feature Nou**

```
Vreau să adaug: [DESCRIERE - ex: "câmp nou 'CNP' în formular înregistrare"]

PAȘI:
1. Citește README.md → principii organizare
2. Identifică modulul relevant (ex: modules/registration/)
3. Citește README.md al modulului (ex: modules/registration/README.md)
4. Analizează structura existentă (hook-uri, funcții, validări)
5. Propune-mi:
   - Unde se adaugă câmpul (hook exact)
   - Cum se validează
   - Unde se salvează (user meta)
   - Dacă e nevoie fișier nou sau modificare existentă
6. Discutăm abordarea
7. După aprobare, implementează MODULAR
8. Testează (arată-mi rezultatul)
9. Creează/update README pentru feature-ul nou
10. Update CHANGELOG.md

IMPORTANT:
- Feature-ul trebuie să fie MODULAR (ușor de dezactivat/șters)
- NU duplica cod existent
- Respectă naming conventions existente

Confirmă că ai înțeles și propune abordarea.
```

---

### **📦 Scenariu 4: Refactoring**

```
Vreau să refactorizez: [DESCRIERE - ex: "mută CSS din registration-enhanced.php în fișier separat"]

PAȘI:
1. Citește modulul relevant din modules/
2. Identifică codul de mutat (linii exacte)
3. Propune-mi:
   - Fișier nou (ex: assets/css/registration.css)
   - Cum se încarcă (wp_enqueue_style în functions.php)
   - Verificări că nu se strică nimic
4. După aprobare:
   - Creează fișierul nou
   - Mută codul
   - Adaugă enqueue în functions.php
   - Testează (compară înainte/după)
   - Șterge codul vechi DOAR după confirmare
5. Update README.md al modulului
6. Update CHANGELOG.md

ATENȚIE:
- Testează ÎNAINTE să ștergi codul vechi
- Verifică că stilurile se aplică corect
- Cache clear după modificare

Propune-mi planul detaliat.
```

---

## ⚠️ **RED FLAGS - Oprește AI-ul IMEDIAT dacă:**

### **🚨 Semnale de ALARMĂ:**

| Ce spune AI-ul | De ce e GREȘIT | Ce trebuie să facă |
|----------------|----------------|-------------------|
| "Voi crea un plugin nou..." | Există deja structură! | Citește README.md → Module existente |
| "Voi modifica direct în tema părinte..." | NU se modifică core! | Doar martfury-child/ |
| "Voi pune CSS-ul inline în PHP..." | Încalcă principiile! | assets/css/ sau design-system.php |
| "Voi face modificarea direct..." | Lipsește aprobare! | Propune ÎNTÂI, implementează DUPĂ |
| "Am făcut commit" (fără CHANGELOG) | Lipsește documentare! | Update CHANGELOG.md |
| "Nu știu unde e..." | Nu a citit documentația! | INDEX.md → găsire rapidă |

### **📢 Prompt de OPRIRE:**

```
⛔ STOP!

Văd că vrei să [ACȚIUNE GREȘITĂ].

Asta încalcă principiile proiectului:
- [MOTIV DE CE E GREȘIT]

Te rog:
1. Citește din nou [DOCUMENT RELEVANT]
2. Propune o abordare care respectă principiile
3. Așteaptă confirmarea mea

NU continua fără să corectezi abordarea!
```

---

## 📊 **CHECKLIST FINALĂ - Înainte de Commit**

### **✅ Verifică cu AI-ul:**

```
Înainte de commit, confirmă că ai făcut:

□ Modificarea funcționează (testat manual)
□ Nu am spart nimic existent (testat old features)
□ Am update CHANGELOG.md cu:
  □ Data corectă [YYYY-MM-DD]
  □ Modul afectat
  □ Descriere clară
  □ Fișiere modificate
□ Am verificat linter errors (dacă există)
□ Codul e curat (fără console.log, var_dump, etc.)
□ Respectă stilul existent (indentare, naming)
□ Dacă e feature nou: am update README.md al modulului

Confirmă toate punctele înainte de commit!
```

---

## 🎯 **EXEMPLE PRACTICE - Conversații Bune**

### **✅ EXEMPLU BUN - AI pregătit:**

**Tu:**
> Vreau să schimb culoarea butoanelor în roșu.

**AI bun:**
```
✅ Am citit documentația:

Din INDEX.md → tabel "Ce vreau să modific" → "Culoarea butoanelor":
→ Fișier: includes/webgsm-design-system.php (sau assets/css/design-system.css)

Găsit în webgsm-design-system.php linia 23-27:
```css
.woocommerce .button {
    background-color: #2196F3; /* Albastru actual */
}
```

PROPUNERE modificare:
```css
.woocommerce .button {
    background-color: #F44336; /* Roșu material design */
}
```

Vrei să procedez? După confirmare, voi:
1. Modifica CSS-ul
2. Update CHANGELOG.md
3. Propune commit message

Confirmă sau cere ajustări.
```

---

### **❌ EXEMPLU GREȘIT - AI nepregătit:**

**Tu:**
> Vreau să schimb culoarea butoanelor în roșu.

**AI greșit:**
```
❌ Ok, voi crea un fișier custom-buttons.css și voi modifica...
```

**Tu (STOP!):**
```
⛔ STOP!

Nu ai citit documentația. Te rog:
1. Citește INDEX.md → tabel "Ce vreau să modific"
2. Găsește unde se modifică butoanele
3. Citește documentația relevantă
4. APOI propune abordarea

NU implementa nimic încă!
```

---

## 💾 **SALVEAZĂ ACEST FIȘIER**

### **Unde să-l găsești:**
```
/martfury-child/AI_ONBOARDING_PROMPTS.md
```

### **Cum să-l folosești:**

1. **La început de proiect cu AI nou:**
   - Copy/paste "PROMPT PRINCIPAL - ONBOARDING"
   - Așteaptă confirmarea AI-ului
   - Testează cu "TEST DE VERIFICARE"

2. **Pentru task-uri specifice:**
   - Copy/paste prompt-ul pentru scenariul relevant
   - Urmează pașii

3. **Înainte de commit:**
   - Copy/paste "CHECKLIST FINALĂ"
   - Verifică toate punctele

---

## 🎊 **REZULTAT AȘTEPTAT**

### **Cu aceste prompturi:**

✅ **AI-ul știe EXACT ce să facă**
✅ **Risc de greșeli: MINIM**
✅ **Timp onboarding: 15-20 min** (nu 2-3 ore)
✅ **Modificări consistente** (respectă structura)
✅ **Documentație actualizată** (CHANGELOG întotdeauna)

---

**Ultima actualizare**: 2026-01-13

**Versiune**: 1.0

**Autor**: WebGSM Team
