# WebGSM Site Audit – Super Tool

Plugin WordPress pentru analiză completă: linkuri, debug, securitate, performanță, SEO.

## Funcționalități

- **Linkuri** – scan linkuri moarte din posturi, pagini, produse, meniuri, widget-uri
- **Debug Log** – vizualizare, filtrare, golire debug.log
- **Securitate** – fișiere sensibile expuse, WP_DEBUG, SSL, plugin-uri neactualizate
- **Performanță** – imagini mari, transiente, revizii, autoload DB
- **SEO** – meta title/description, linkuri rupte, motorii de căutare
- **GSC** – import JSON din Google Search Console

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
