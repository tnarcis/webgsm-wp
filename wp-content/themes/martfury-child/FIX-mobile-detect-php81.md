# Fix: Deprecated Mobile_Detect::__construct() (PHP 8.1)

## De ce apare (și pare sporadică)

- **Cauză:** În PHP 8.1+, un parametru cu default `null` trebuie declarat **explicit** nullable (`?Tip`).
- **Unde:** Fișierul e în **tema părinte** Martfury:  
  `wp-content/themes/martfury/inc/libs/mobile_detect.php`
- **„Sporadic”:** Eroarea apare doar când:
  1. Se instanțiază clasa `Mobile_Detect` (detecție mobil pentru meniu/layout),
  2. `display_errors` / `error_reporting` afișează deprecation notices (în producție sunt adesea ascunse).

Nu e un bug aleatoriu – e o deprecare PHP 8.1 la un parametru din constructor.

---

## Remediu (pe server)

1. Deschide pe server (FTP/cPanel File Manager sau SSH):
   ```
   wp-content/themes/martfury/inc/libs/mobile_detect.php
   ```
2. Găsește linia ~696 – constructorul arată probabil așa:
   ```php
   public function __construct($headers = null)
   ```
   sau:
   ```php
   public function __construct(array $headers = null)
   ```
3. Înlocuiește cu tip nullable explicit:
   ```php
   public function __construct(?array $headers = null)
   ```
4. Salvează fișierul.

Dacă constructorul are mai multe parametri, păstrează restul și pune doar `?array` la `$headers` (ex.: `?array $headers = null, $userAgent = null` → păstrezi cum e, doar primul parametru se schimbă).

---

## Dacă tema Martfury se actualizează

La un update al temei Martfury, fișierul poate fi suprascris. Atunci refă pasul de mai sus sau raportează problema la dezvoltatorul temei Martfury (sau verifică dacă o versiune nouă a temei rezolvă deja problema).
