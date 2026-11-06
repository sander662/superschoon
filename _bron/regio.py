# ---------------- GEMEENTEPAGINA'S + SITEMAP (wordt uitgevoerd vanuit build.py) ----------------
import unicodedata, datetime
from urllib.parse import quote

_js = open(OUT + 'assets/js/calculator.js').read()
_zl = _js[_js.index('const ZONELIJST={') + len('const ZONELIJST='):_js.index('const LEZ')].strip().rstrip(';')
ZONES = json.loads(re.sub(r'(\d):\[', r'"\1":[', _zl.replace("'", '"')))
AANRIJ = {1: 0, 2: 40, 3: 65}
LEZ_ST = {'Brussel', 'Antwerpen'}

def slug(s):
    s = unicodedata.normalize('NFD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')

def prijs(m2):
    if m2 <= 50: p = 190 + 210 * (m2 - 1) / 49
    elif m2 <= 100: p = 400 + 7 * (m2 - 50)
    else: p = 750 + 6 * (m2 - 100)
    return round(p)

def eur(x):
    s = f'{x:,.0f}'.replace(',', '.')
    return '€ ' + s

# Puurs: de fusiegemeente heet Puurs-Sint-Amands
PLAATSEN = []  # dict(naam, hoofd, hoofd_label, zone, calc)
for z in ('1', '2', '3'):
    for hoofd, deel in ZONES[z]:
        label = 'Puurs-Sint-Amands' if hoofd == 'Puurs' else hoofd
        if hoofd == 'Puurs':
            PLAATSEN.append(dict(naam='Puurs-Sint-Amands', hoofd='Puurs', label=label, zone=int(z), calc='Puurs-Sint-Amands (Puurs)', is_hoofd=True))
            PLAATSEN.append(dict(naam='Puurs', hoofd='Puurs', label=label, zone=int(z), calc='Puurs', is_hoofd=False))
            for d in deel:
                if d == 'Puurs-Sint-Amands': continue
                PLAATSEN.append(dict(naam=d, hoofd=hoofd, label=label, zone=int(z), calc=f'{d} ({hoofd})', is_hoofd=False))
            continue
        PLAATSEN.append(dict(naam=hoofd, hoofd=hoofd, label=label, zone=int(z), calc=hoofd, is_hoofd=True))
        for d in deel:
            PLAATSEN.append(dict(naam=d, hoofd=hoofd, label=label, zone=int(z), calc=f'{d} ({hoofd})', is_hoofd=False))
for p in PLAATSEN:
    p['url'] = f"terrasreiniging-{slug(p['naam'])}.html"
assert len({p['url'] for p in PLAATSEN}) == len(PLAATSEN)

# ---- postcode en ligging (bron: postcodelijst met coördinaten, jief/zipcode-belgium) ----
import math
def _n(s): return re.sub('[^a-z]', '', unicodedata.normalize('NFD', s).encode('ascii', 'ignore').decode().lower())
_FR = {'Vorst': 'Forest', 'Brussel': 'Bruxelles', 'Elsene': 'Ixelles', 'Oudergem': 'Auderghem', 'Schaarbeek': 'Schaerbeek', 'Sint-Agatha-Berchem': 'Berchem-Sainte-Agathe',
       'Sint-Gillis': 'Saint-Gilles', 'Sint-Jans-Molenbeek': 'Molenbeek-Saint-Jean', 'Sint-Joost-ten-Node': 'Saint-Josse-ten-Noode',
       'Sint-Lambrechts-Woluwe': 'Woluwe-Saint-Lambert', 'Sint-Pieters-Woluwe': 'Woluwe-Saint-Pierre', 'Ukkel': 'Uccle',
       'Watermaal-Bosvoorde': 'Watermael-Boitsfort', 'Laken': 'Laeken', 'Puurs-Sint-Amands': 'Puurs', 'Opstal': 'Buggenhout'}
_GI = {}
for r in json.load(open(SP + 'geo-postcodes.json')):
    _GI.setdefault(_n(r['city']), []).append(r)
BASIS = (50.9865, 4.4441)  # Zemst
def _km(a, b):
    la1, lo1, la2, lo2 = map(math.radians, (a[0], a[1], b[0], b[1]))
    h = math.sin((la2-la1)/2)**2 + math.cos(la1)*math.cos(la2)*math.sin((lo2-lo1)/2)**2
    return 6371 * 2 * math.asin(math.sqrt(h))
def _zoek(naam, nabij=None):
    k = _GI.get(_n(_FR.get(naam, naam)), [])
    if not k: return None
    if nabij: k = sorted(k, key=lambda r: _km((r['lat'], r['lng']), nabij))
    return k[0]
for p in PLAATSEN:
    if p['is_hoofd']:
        r = _zoek(p['naam'], BASIS); p['geo'] = (r['lat'], r['lng']); p['pc'] = r['zip']
for p in PLAATSEN:
    if not p['is_hoofd']:
        h = next(q for q in PLAATSEN if q['hoofd'] == p['hoofd'] and q['is_hoofd'])
        r = _zoek(p['naam'], h['geo']); p['geo'] = (r['lat'], r['lng']); p['pc'] = r['zip']
# correcties: in de bronlijst staan deze coördinaten verkeerd
_FIX = {'Berg': (50.937, 4.540), 'Hamme': (50.958, 4.248), 'Schriek': (51.032, 4.680)}
for p in PLAATSEN:
    if p['naam'] in _FIX and not p['is_hoofd']: p['geo'] = _FIX[p['naam']]
for p in PLAATSEN:
    if p['naam'] == 'Antwerpen': p['pc'] = '2000'
    p['km'] = _km(BASIS, p['geo'])
    dy = p['geo'][0] - BASIS[0]; dx = (p['geo'][1] - BASIS[1]) * math.cos(math.radians(BASIS[0]))
    hoek = (math.degrees(math.atan2(dx, dy)) + 360) % 360
    p['richting'] = ['ten noorden', 'ten noordoosten', 'ten oosten', 'ten zuidoosten', 'ten zuiden', 'ten zuidwesten', 'ten westen', 'ten noordwesten'][int((hoek + 22.5) // 45) % 8]

def afstand_tekst(p):
    if p['naam'] == 'Zemst':
        return 'Onze vestiging ligt in Zemst zelf, in de Beekveldstraat.'
    km = p['km']
    if km < 1.5:
        return f"{p['naam']} ({p['pc']}) ligt vlak bij onze vestiging in Zemst."
    return f"{p['naam']} ({p['pc']}) ligt ongeveer {round(km)} km {p['richting']} van onze vestiging in Zemst, in vogelvlucht."

def ligging(p):
    if p['is_hoofd']:
        deel = [q['naam'] for q in PLAATSEN if q['hoofd'] == p['hoofd'] and not q['is_hoofd']]
        if p['hoofd'] == 'Brussel':
            return 'We werken in heel het Brussels Hoofdstedelijk Gewest, van ' + ', '.join(deel[:-1]) + ' tot ' + deel[-1] + '.'
        if p['hoofd'] == 'Antwerpen':
            return 'We werken in alle districten van Antwerpen: ' + ', '.join(deel[:-1]) + ' en ' + deel[-1] + '.'
        if deel:
            return f"Dat geldt voor heel {p['naam']}, ook voor " + (', '.join(deel[:-1]) + ' en ' + deel[-1] if len(deel) > 1 else deel[0]) + '.'
        return ''
    if p['hoofd'] == 'Brussel':
        return f"{p['naam']} ligt in het Brussels Hoofdstedelijk Gewest."
    if p['hoofd'] == 'Antwerpen':
        return f"{p['naam']} is een district van de stad Antwerpen."
    if p['naam'] == 'Puurs':
        return 'Puurs is een deelgemeente van Puurs-Sint-Amands.'
    return f"{p['naam']} is een deelgemeente van {p['label']}."

def rit_tekst(p):
    k = AANRIJ[p['zone']]
    if k == 0:
        return f"{p['naam']} valt in zone 1, dichtbij onze vestiging. Je betaalt daarom geen verplaatsingskost."
    return f"Voor {p['naam']} rekenen we een vaste verplaatsingskost van {eur(k)} excl. btw ({eur(round(k*1.21))} incl. btw), ongeacht de oppervlakte."

INTRO = [
  'Is je terras, oprit of tuinpad in {n} groen uitgeslagen of vol mos? Super Schoon maakt het weer proper met een gepatenteerd borstelsysteem en een beetje water. Zonder chemicaliën, zeep, stoom of hogedruk.',
  'Super Schoon reinigt terrassen, opritten en paden in {n} op een ecologische manier. We werken met borstels en een beetje water, niet met chemicaliën of hogedruk. Zo blijven je voegen zitten en wordt je verharding niet ruw.',
  'Wil je je terras of oprit in {n} laten reinigen zonder je voegen uit te spuiten? Wij werken met een gepatenteerd borstelsysteem en een beetje water. Geen chemicaliën, geen zeep, geen stoom en geen hogedruk.',
]

def faq_regio(p):
    n = p['naam']; k = AANRIJ[p['zone']]
    rit = 'zonder verplaatsingskost' if k == 0 else f'plus {eur(k)} verplaatsingskost (excl. btw)'
    ex = prijs(40) + k
    return [
      (f'Wat kost een terrasreiniging in {n}?', f'De prijs hangt af van de oppervlakte. Een terras van 40 m² in {n} kost {eur(round(ex*1.21))} incl. btw: {eur(prijs(40))} voor de reiniging {rit}. Met onze <a href="prijsberekening.html?gemeente={quote(p["calc"])}">prijsberekening</a> zie je meteen je eigen prijs.'),
      (f'Komen jullie ook naar {n}?', f'Ja. {ligging(p)} {afstand_tekst(p)} Daarmee valt {n} in onze zone {p["zone"]}.'.replace('  ', ' ')),
      ('Wat moet ik zelf voorzien?', 'Een buitenkraan met minstens 2,5 bar waterdruk en een Gardena-koppeling, en een stopcontact (230 V) in de buurt. Maak de oppervlakte ook vrij van meubels, bloempotten en voertuigen.'),
      ('Waarom reinigen jullie niet met hogedruk?', 'Hogedruk spuit voegen uit, maakt de toplaag van stenen ruw en doet vuil opspatten tegen gevels en ramen. Ruwe stenen vervuilen bovendien sneller opnieuw. Onze borstels polijsten het oppervlak, waardoor nieuw vuil minder snel hecht.'),
    ]

def buren(p):
    eigen = [q for q in PLAATSEN if q['hoofd'] == p['hoofd'] and q is not p]
    rest = sorted((q for q in PLAATSEN if q['hoofd'] != p['hoofd']), key=lambda q: _km(p['geo'], q['geo']))
    return (eigen[:10] + rest)[:16]

def regio_pagina(i, p):
    n = p['naam']; k = AANRIJ[p['zone']]; calc_url = 'prijsberekening.html?gemeente=' + quote(p['calc'])
    rijen = ''.join(f'<tr><th scope="row">{m} m²</th><td>{eur(round((prijs(m)+k)*1.21))}</td></tr>' for m in (20, 40, 75, 120))
    lez = '<p>In de lage-emissiezone (LEZ) komt er een kleine toeslag bij.</p>' if p['hoofd'] in LEZ_ST else ''
    deel_van = '' if p['is_hoofd'] else f' · {p["label"]}'
    faq = faq_regio(p)
    nb = buren(p)
    body = f'''<section class="page-head"><div class="wrap stack">
  <p class="eyebrow">Werkgebied{deel_van}</p>
  <h1>Terras en oprit reinigen in {n}</h1>
  <p class="lead">{INTRO[i % 3].format(n=n)}</p>
  <div class="btns"><a class="btn green" href="{calc_url}">Bereken je prijs voor {n}</a><a class="btn ghost" href="werkwijze.html">Zo werken wij</a></div>
</div></section>

<section><div class="wrap split top">
  <div class="stack">
    <p class="eyebrow">Prijs in {n}</p>
    <h2>Wat kost het in {n}?</h2>
    <p>{afstand_tekst(p)} {rit_tekst(p)} {ligging(p)}</p>
    <p>De reiniging zelf hangt alleen af van de oppervlakte. Hoe groter de oppervlakte, hoe lager de prijs per m². In de tabel zie je enkele voorbeelden voor {n}, verplaatsing en 21% btw inbegrepen.</p>
    {lez}
    <p>Ken je de oppervlakte niet? In de <a href="{calc_url}">prijsberekening</a> teken je je terras of oprit gewoon in op een luchtfoto.</p>
  </div>
  <div class="stack">
    <div class="table-wrap"><table class="compare prijstabel"><caption>Voorbeeldprijzen in {n}, incl. btw</caption>
      <thead><tr><th scope="col">Oppervlakte</th><th scope="col">Totaalprijs</th></tr></thead><tbody>{rijen}</tbody></table></div>
    <p class="hint">Indicatieve prijzen. We bevestigen de prijs na een korte controle ter plaatse of aan de hand van foto's.</p>
  </div>
</div></section>

<section class="band-ink"><div class="wrap">
  <div class="sec-head"><p class="eyebrow">Waarom Super Schoon</p><h2>Grondig proper in {n}, zonder schade</h2></div>
  {usp_band()}
</div></section>

<section class="band-stone"><div class="wrap split top">
  <div class="stack">
    <p class="eyebrow">Geschikt voor</p>
    <h2>Elke buitenverharding</h2>
    <p class="lead">Ons borstelsysteem werkt op harde en zachte materialen: van natuursteen en klinkers tot houten en composiet terrassen.</p>
    <p>Super Schoon is een spin-off van tuinaannemer <a href="https://www.ssprojects.be" target="_blank" rel="noopener">S&amp;S Projects</a> uit Zemst, die al meer dan 10 jaar terrassen en opritten aanlegt in de regio.</p>
  </div>
  <ul class="types">{''.join(f'<li>{t}</li>' for t in TYPES)}</ul>
</div></section>

<section><div class="wrap stack-lg">
  <div class="sec-head"><p class="eyebrow">Vragen</p><h2>Veelgestelde vragen over reinigen in {n}</h2></div>
  <div class="faq">{''.join(f'<details><summary>{q}</summary><p>{a}</p></details>' for q, a in faq)}</div>
</div></section>

<section class="band-stone"><div class="wrap stack">
  <h2>Ook in de buurt van {n}</h2>
  <p>Woon je in een van deze plaatsen rond {n}? Ook daar reinigen we terrassen en opritten.</p>
  <ul class="plaatsen">{''.join(f'<li><a href="{q["url"]}">{q["naam"]}</a></li>' for q in nb)}</ul>
  <p><a href="sitemap.html">Bekijk ons volledige werkgebied</a></p>
</div></section>

<section class="band-green"><div class="wrap cta">
  <div class="stack"><h2>Wat kost het voor jouw terras in {n}?</h2><p class="lead">Vul de oppervlakte in of teken je verharding in op de luchtfoto, en je ziet meteen je prijs.</p></div>
  <a class="btn light" href="{calc_url}">Bereken je prijs</a>
</div></section>
'''
    place = {"@type": "City" if p['is_hoofd'] else "Place", "name": n,
             "address": {"@type": "PostalAddress", "postalCode": p['pc'], "addressLocality": n, "addressCountry": "BE"},
             "geo": {"@type": "GeoCoordinates", "latitude": round(p['geo'][0], 4), "longitude": round(p['geo'][1], 4)}}
    if not p['is_hoofd']:
        place["containedInPlace"] = {"@type": "City", "name": p['label']}
    schema = [
      {"@context": "https://schema.org", "@type": "Service", "name": f"Terras- en opritreiniging in {n}",
       "serviceType": "Ecologische reiniging van buitenverhardingen", "provider": {"@id": DOMAIN + "/#bedrijf"},
       "areaServed": place, "url": DOMAIN + '/' + p['url'],
       "offers": {"@type": "Offer", "priceCurrency": "EUR", "price": round((prijs(40)+k)*1.21, 2),
                  "description": f"Reiniging van 40 m² in {n}, incl. verplaatsing en btw", "url": DOMAIN + '/' + calc_url}},
      {"@context": "https://schema.org", "@type": "BreadcrumbList", "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Home", "item": DOMAIN + "/"},
        {"@type": "ListItem", "position": 2, "name": "Werkgebied", "item": DOMAIN + "/sitemap.html"},
        {"@type": "ListItem", "position": 3, "name": n, "item": DOMAIN + '/' + p['url']}]},
      {"@context": "https://schema.org", "@type": "FAQPage", "mainEntity": [
        {"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": re.sub('<[^>]+>', '', a)}} for q, a in faq]},
    ]
    totaal = eur(round((prijs(40)+k)*1.21))
    kandidaten = [
      f"Terras of oprit laten reinigen in {n}? Ecologisch, zonder hogedruk of chemie. Een terras van 40 m² kost {totaal} incl. btw. Bereken direct je eigen prijs.",
      f"Terras of oprit reinigen in {n}? Ecologisch, zonder hogedruk of chemie. 40 m² kost {totaal} incl. btw. Bereken direct je eigen prijs.",
      f"Terras of oprit reinigen in {n}? Zonder hogedruk of chemie. 40 m²: {totaal} incl. btw. Bereken direct je prijs.",
    ]
    desc = next(d for d in kandidaten if len(d) <= 155)
    page(p['url'], f'Terras en oprit reinigen in {n} | Super Schoon', desc, body, schema=schema)

for i, p in enumerate(PLAATSEN):
    regio_pagina(i, p)

# ---------------- HTML-SITEMAP ----------------
PAGINAS = [('index.html', 'Home'), ('prijsberekening.html', 'Bereken je prijs'), ('werkwijze.html', 'Werkwijze en veelgestelde vragen'),
           ('over-ons.html', 'Over ons'), ('contact.html', 'Contact'), ('algemene-voorwaarden.html', 'Algemene voorwaarden'), ('privacy.html', 'Privacyverklaring')]
alfa = sorted(PLAATSEN, key=lambda q: slug(q['naam']))
groepen = {}
for q in alfa:
    groepen.setdefault(slug(q['naam'])[0].upper(), []).append(q)
letters = ''.join(f'<a href="sitemap.html#letter-{l.lower()}">{l}</a>' for l in groepen)
blokken = ''.join(f'<div class="az-groep" id="letter-{l.lower()}"><h3>{l}</h3><ul>' +
                  ''.join(f'<li><a href="{q["url"]}">Terrasreiniging {q["naam"]}</a> <span class="pc">{q["pc"]}</span></li>' for q in qs) + '</ul></div>' for l, qs in groepen.items())
sm = f'''<section class="page-head"><div class="wrap stack">
  <p class="eyebrow">Super Schoon</p>
  <h1>Sitemap</h1>
  <p class="lead">Alle pagina's van deze website, en de gemeenten waar we terrassen, opritten en paden reinigen.</p>
</div></section>
<section><div class="wrap stack-lg">
  <div class="stack"><h2>Pagina's</h2><ul class="plaatsen">{''.join(f'<li><a href="{u}">{t}</a></li>' for u, t in PAGINAS)}</ul></div>
  <div class="stack"><h2>Werkgebied</h2>
    <p>We reinigen in de gemeenten en deelgemeenten hieronder. Woon je verder? Ook dan komen we graag: de <a href="prijsberekening.html">prijsberekening</a> toont je verplaatsingskost.</p>
    <nav class="az-nav" aria-label="Naar letter">{letters}</nav>
    <div class="az">{blokken}</div>
  </div>
</div></section>
'''
page('sitemap.html', 'Sitemap en werkgebied | Super Schoon', 'Overzicht van alle pagina\'s van Super Schoon en van de gemeenten waar we terrassen, opritten en paden ecologisch reinigen.', sm)

# ---------------- sitemap.xml ----------------
vandaag = datetime.date.today().isoformat()
xml = [('', '1.0'), ('prijsberekening.html', '0.9'), ('werkwijze.html', '0.8'), ('over-ons.html', '0.7'), ('contact.html', '0.6'),
       ('sitemap.html', '0.5'), ('algemene-voorwaarden.html', '0.3'), ('privacy.html', '0.3')] + [(q['url'], '0.6' if q['is_hoofd'] else '0.5') for q in PLAATSEN]
open(OUT + 'sitemap.xml', 'w').write('<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
    ''.join(f'  <url><loc>{DOMAIN}/{u}</loc><lastmod>{vandaag}</lastmod><priority>{pr}</priority></url>\n' for u, pr in xml) + '</urlset>\n')

# ---------------- llms.txt (voor AI-assistenten) ----------------
def lijst(z):
    return '\n'.join(f"- [{q['naam']} ({q['pc']})]({DOMAIN}/{q['url']})" + ('' if q['is_hoofd'] else f" (deel van {q['label']})") for q in PLAATSEN if q['zone'] == z)
llms = f'''# Super Schoon

> Super Schoon reinigt terrassen, opritten en tuinpaden ecologisch, met een gepatenteerd borstelsysteem en een beetje water: zonder chemicaliën, zeep, stoom of hogedruk. Super Schoon is een handelsnaam en spin-off van tuinaannemer S&S Projects BV (Beekveldstraat 1A, 1980 Zemst, België, btw BE 0735.883.273), die al meer dan 10 jaar verhardingen aanlegt.

- Telefoon: +32 468 43 00 53
- E-mail: info@ssprojects.be
- Geschikt voor: natuursteen, keramische tegels, betonklinkers, kleiklinkers, XL-tegels, gepolierde beton, asfalt, houten en composiet terrassen.
- Prijs reiniging (excl. btw): tot 50 m² van € 190 (1 m²) tot € 400 (50 m²); 50–100 m²: € 400 + € 7 per extra m²; boven 100 m²: € 750 + € 6 per extra m². Plus verplaatsingskost: zone 1 gratis, zone 2 € 40, zone 3 € 65, verder volgens reistijd. Alle prijzen zijn indicatief en worden bevestigd na controle ter plaatse of op basis van foto's.
- De klant voorziet een buitenkraan (min. 2,5 bar, Gardena-koppeling) en een stopcontact (230 V).

## Belangrijkste pagina's

- [Prijsberekening]({DOMAIN}/prijsberekening.html): directe prijs en offerteaanvraag, met intekenen op luchtfoto
- [Werkwijze en veelgestelde vragen]({DOMAIN}/werkwijze.html)
- [Over ons]({DOMAIN}/over-ons.html)
- [Contact]({DOMAIN}/contact.html)
- [Algemene voorwaarden]({DOMAIN}/algemene-voorwaarden.html)
- [Sitemap en werkgebied]({DOMAIN}/sitemap.html)

## Werkgebied zone 1 (geen verplaatsingskost)

{lijst(1)}

## Werkgebied zone 2 (verplaatsing € 40 excl. btw)

{lijst(2)}

## Werkgebied zone 3 (verplaatsing € 65 excl. btw)

{lijst(3)}
'''
open(OUT + 'llms.txt', 'w').write(llms)
print('regio:', len(PLAATSEN), 'pagina\'s')
