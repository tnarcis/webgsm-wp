# ⚡ QUICK START - Pentru Alt AI

> **Copy/paste EXACT acest prompt când începi cu un AI nou pe WebGSM**

---

## 🚀 **PROMPT COMPLET (Copy/Paste tot):**

```
🎯 ONBOARDING WebGSM - WordPress/WooCommerce Custom

📚 PAȘI OBLIGATORII (NU sări peste!):

1. Citește martfury-child/README.md (5 min)
2. Citește martfury-child/INDEX.md (2 min)
3. Citește martfury-child/CHANGELOG.md (3 min)
4. Identifică modulul relevant din INDEX.md
5. Citește modules/{modul}/README.md
6. Propune abordarea (NU implementa direct!)

⚠️ REGULI STRICTE:

❌ NU modifica fără documentație
❌ NU pune CSS în PHP (folosește assets/css/)
❌ NU modifica WordPress/WooCommerce/tema părinte core
❌ NU duplica funcționalități existente

✅ ÎNTOTDEAUNA:
✅ Propune ÎNAINTE să implementezi
✅ Update CHANGELOG.md după modificare
✅ Testează înainte de commit
✅ Respectă structura modulară

📝 TEST RAPID (răspunde la toate 5):

1. Unde modific culoarea butoanelor?
2. Unde găsesc logica pentru facturi SmartBill?
3. Ce fac după fiecare modificare?
4. Pot pune CSS în fișiere PHP? (DA/NU)
5. Unde găsesc instrucțiuni de debugging pentru facturi?

Răspunde COMPLET la toate 5 întrebările.
După test, îți voi da task-ul.
```

---

## ✅ **VERIFICĂ RĂSPUNSURILE AI-ului:**

### **Răspunsuri OBLIGATORII:**

**Q1**: `includes/webgsm-design-system.php` SAU verifică în `INDEX.md → tabel "Ce vreau să modific"`

**Q2**: `includes/facturi.php` + `modules/invoices/README.md`

**Q3**: **TREBUIE să menționeze CHANGELOG.md** + testare + commit

**Q4**: **TREBUIE să spună NU!** (CSS separat de PHP)

**Q5**: `INDEX.md → DEBUGGING RAPID` + `modules/invoices/README.md → DEBUGGING`

### **🚨 STOP IMEDIAT dacă:**

- ❌ Nu menționează CHANGELOG.md la Q3 → **STOP! Recitește documentația**
- ❌ Spune "DA" la Q4 (CSS în PHP) → **STOP! Recitește documentația**
- ❌ Spune "Nu știu" la orice întrebare → **STOP! Recitește documentația**

### **✅ Dacă toate răspunsurile sunt OK:**

```
✅ Perfect! Ai trecut testul.

Task-ul meu: [DESCRIE CE VREI]

Propune abordarea (fișiere, linii, modificări) ÎNAINTE să implementezi.
```

---

## 📋 **TEMPLATE PENTRU TASK:**

După ce AI-ul trece testul, dă-i task-ul cu acest format:

```
Task: [DESCRIERE CLARĂ - ex: "Schimbă culoarea butoanelor în roșu"]

Context suplimentar (dacă e nevoie):
- [Detalii extra]

Pași așteptați:
1. Identifică fișierul exact (din INDEX.md sau README.md modul)
2. Propune-mi modificarea EXACT (arată cod vechi vs. nou)
3. Așteaptă confirmarea mea
4. Implementează
5. Testează
6. Update CHANGELOG.md
7. Commit

Începe cu pasul 1: spune-mi ce fișier vei modifica și de ce.
```

---

## 🎯 **EXEMPLE TASK-URI FRECVENTE:**

### **Design / CSS:**
```
Task: Schimbă culoarea butoanelor din albastru (#2196F3) în roșu (#F44336)

Pași:
1. Verifică INDEX.md → "Culoarea butoanelor"
2. Găsește selectorul în webgsm-design-system.php
3. Propune modificarea
4. După aprobare: implementează, testează, update CHANGELOG, commit
```

### **Bug Fix:**
```
Task: Factură nu se generează pentru comanda #12345

Pași:
1. INDEX.md → DEBUGGING RAPID → "Problem: Factură nu se generează"
2. Urmează pașii de debugging
3. Raportează-mi ce ai găsit (log-uri, erori)
4. Propune soluție
5. După aprobare: implementează fix, testează, update CHANGELOG, commit
```

### **Feature Nou:**
```
Task: Adaugă câmp "CNP" în formular înregistrare

Pași:
1. modules/registration/README.md → analizează structura
2. Propune unde se adaugă (hook exact, validare, salvare)
3. Discutăm abordarea
4. După aprobare: implementează modular, testează, update README + CHANGELOG, commit
```

---

## 📊 **SCORING AI:**

După test, evaluează AI-ul:

| Scor | Status | Acțiune |
|------|--------|---------|
| **45-50** | 🏆 EXCELENT | Poate începe imediat! |
| **35-44** | ✅ ACCEPTABIL | Poate începe, monitorizează |
| **25-34** | ⚠️ INSUFICIENT | Recitește documentația |
| **0-24** | 🚫 NEPREGĂTIT | STOP! Citește TOATĂ documentația |

**Evaluare pe întrebare:**
- Q1-Q5: **10 puncte** fiecare (răspuns complet)
- Q3 și Q4 sunt **CRITICE** (dacă greșește → STOP)

---

## 💾 **FIȘIERE UTILE:**

| Fișier | Când îl folosești |
|--------|-------------------|
| `QUICK_START.md` | **Acest fișier** - start aici! |
| `AI_ONBOARDING_PROMPTS.md` | Prompturi detaliate pentru scenarii specifice |
| `AI_TEST_ANSWERS.md` | Răspunsuri corecte complete (cu scoring) |
| `README.md` | Overview complet proiect |
| `INDEX.md` | Găsire rapidă orice |
| `CHANGELOG.md` | Istoric toate modificările |

---

## ⏱️ **TIMP ESTIMAT:**

- **Onboarding AI**: 15-20 min (citit documentație + test)
- **Task simplu** (CSS): 5-10 min
- **Task mediu** (bug fix): 15-30 min
- **Task complex** (feature): 30-60 min

**TOTAL pentru AI nou cu task simplu: ~25-30 min** ✨

---

## 🎊 **REZULTAT FINAL:**

### **Fără acest sistem (înainte):**
- ⏱️ Onboarding: **2-3 ore** (trial & error)
- 😵 Risc greșeli: **MARE**
- 📝 CHANGELOG: **Uitat**
- 🔍 Găsire cod: **Greu** (caut peste tot)

### **Cu acest sistem (acum):**
- ⏱️ Onboarding: **15-20 min** (documentație clară)
- ✅ Risc greșeli: **MINIM** (reguli clare)
- 📝 CHANGELOG: **Întotdeauna** (obligatoriu în prompt)
- 🎯 Găsire cod: **INSTANT** (INDEX.md)

---

**Copy/paste prompt-ul de sus și începe! 🚀**

**Ultima actualizare**: 2026-01-13
