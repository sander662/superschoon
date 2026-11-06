# Bronbestanden van superschoon.be

De HTML-pagina's in de hoofdmap worden gegenereerd. Pas de inhoud hier aan en draai daarna:

    python3 _bron/build.py

- build.py – alle hoofdpagina's (home, werkwijze, over ons, contact, prijsberekening, juridische pagina's, 404)
- metas.py – meta-beschrijvingen per pagina (max. 155 tekens)
- regio.py – gemeentepagina's, sitemap.html, sitemap.xml en llms.txt (werkgebied uit assets/js/calculator.js)
- seo_audit.py – SEO-analyse voor het dashboard (dashboard/seo.json)
- av.html, privacy.html, calc_form.html – inhoud van de voorwaarden, privacyverklaring en het formulier
- geo-postcodes.json – postcodes en coördinaten (bron: jief/zipcode-belgium)

Deze map is op de website afgeschermd (.htaccess).
