# WebGSM Site Audit

Plugin WordPress pentru analiză site: linkuri moarte, crawl-uri, probleme SEO.

## Funcționalități

- **Scan linkuri** – verifică linkurile din posturi, pagini, produse, meniuri, widget-uri
- **Setări** – configurezi ce surse să scaneze, timeout, redirect-uri
- **Import GSC** – lipești JSON exportat din Google Search Console pentru analiză
- **Dashboard** – rezultate vizuale, filtre, badge-uri pe status

## Utilizare

1. Activează pluginul din **Plugins**
2. Mergi la **Site Audit** în meniul admin
3. Configurează **Setări** (opțional)
4. Apasă **Rulează scan** pentru verificare linkuri
5. Pentru GSC: exportă date din Search Console (JSON) și lipește în câmpul dedicat

## Google Search Console

Integrarea completă cu API-ul GSC necesită:
- Proiect Google Cloud
- Search Console API activat
- Credențiale OAuth sau Service Account

Până atunci, poți exporta manual datele și le poți importa în plugin.

## Cerințe

- WordPress 6.0+
- PHP 8.0+
