import json, re, os, html
SP = os.path.dirname(os.path.abspath(__file__)) + '/'
OUT = os.path.dirname(os.path.dirname(os.path.abspath(__file__))) + '/'
DOMAIN = 'https://www.superschoon.be'

def icon(name):
    s = open(OUT + f'assets/img/icoon-{name}.svg').read()
    return s

# Sociale media: Instagram invullen zodra het profiel bestaat (lege waarde = niet tonen)
WHATSAPP = 'https://wa.me/32468430053?text=' + 'Hallo%20Super%20Schoon%2C%20'
SOCIAL = [('Facebook', 'https://www.facebook.com/profile.php?id=61595978537018'), ('Instagram', '')]
SOCIAL = [(n, u) for n, u in SOCIAL if u]

NAV = [('index.html', 'Home'), ('werkwijze.html', 'Werkwijze'), ('over-ons.html', 'Over ons'), ('contact.html', 'Contact')]

ORG = {
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": DOMAIN + "/#bedrijf",
  "name": "Super Schoon",
  "alternateName": "Super Schoon by S&S Projects",
  "description": "Ecologische reiniging van buitenverhardingen zoals terrassen, opritten en paden, met een gepatenteerd borstelsysteem, zonder chemicaliën, zeep, stoom of hogedruk.",
  "url": DOMAIN + "/",
  "logo": DOMAIN + "/assets/img/logo-superschoon.svg",
  "image": DOMAIN + "/assets/img/og-superschoon.jpg",
  "telephone": "+32468430053",
  "email": "info@ssprojects.be",
  "vatID": "BE0735883273",
  "address": {"@type": "PostalAddress", "streetAddress": "Beekveldstraat 1A", "postalCode": "1980", "addressLocality": "Zemst", "addressCountry": "BE"},
  "parentOrganization": {"@type": "Organization", "name": "S&S Projects BV", "url": "https://www.ssprojects.be"},
  "knowsAbout": ["terrasreiniging", "opritreiniging", "ecologische reiniging van buitenverhardingen", "reiniging zonder hogedruk"],
  "areaServed": "Vlaanderen",
  "sameAs": [u for _, u in SOCIAL]
}

exec(open(SP + 'metas.py').read())
def head(title, desc, path, extra_css='', schema=None):
    desc = META.get(path, desc)
    canon = DOMAIN + '/' + ('' if path == 'index.html' else path)
    schemas = [ORG] + (schema or [])
    ld = ''.join(f'<script type="application/ld+json">{json.dumps(s, ensure_ascii=False)}</script>\n' for s in schemas)
    return f'''<!doctype html>
<html lang="nl-BE">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>{html.escape(title)}</title>
<meta name="description" content="{html.escape(desc)}">
<link rel="canonical" href="{canon}">
<meta property="og:type" content="website">
<meta property="og:locale" content="nl_BE">
<meta property="og:site_name" content="Super Schoon">
<meta property="og:title" content="{html.escape(title)}">
<meta property="og:description" content="{html.escape(desc)}">
<meta property="og:url" content="{canon}">
<meta property="og:image" content="{DOMAIN}/assets/img/og-superschoon.jpg">
<meta name="theme-color" content="#151515">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="icon" href="favicon.ico" sizes="32x32">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="preload" href="assets/fonts/questrial-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/style.css">
{extra_css}{ld}</head>
<body>
<a class="skip" href="#inhoud">Naar de inhoud</a>
'''

def header(path):
    links = ''.join(f'<a href="{h}"{" aria-current=\"page\"" if h == path else ""}>{t}</a>' for h, t in NAV)
    cur = ' aria-current="page"' if path == 'prijsberekening.html' else ''
    return f'''<header class="site-head">
  <div class="wrap head-in">
    <a class="brand" href="index.html" aria-label="Super Schoon, naar de startpagina"><img src="assets/img/logo-superschoon.svg" alt="Super Schoon – reiniging van buitenverhardingen" width="200" height="34"></a>
    <button class="menu-btn" type="button" aria-expanded="false" aria-controls="nav" aria-label="Menu openen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    <nav class="nav" id="nav" aria-label="Hoofdmenu">{links}<a class="btn" href="prijsberekening.html"{cur}>Bereken je prijs</a></nav>
  </div>
</header>
<main id="inhoud">
'''

FOOTER = '''</main>
<footer class="site-foot">
  <div class="wrap">
    <div class="foot-grid">
      <div class="stack">
        <img src="assets/img/logo-superschoon-wit.svg" alt="Super Schoon" width="220" height="38">
        <p>Ecologische reiniging van terrassen, opritten en paden. Zonder chemicaliën, zeep, stoom of hogedruk.</p>
      </div>
      <div>
        <h2>Contact</h2>
        <ul>
          <li><a href="tel:+32468430053">0468 43 00 53</a></li>
          <li><a href="''' + WHATSAPP + '''" rel="noopener" target="_blank">WhatsApp</a></li>
          <li><a href="mailto:info@ssprojects.be">info@ssprojects.be</a></li>
          <li>Beekveldstraat 1A, 1980 Zemst</li>
        </ul>''' + (('<h2 class="foot-h2">Volg ons</h2>\n        <ul>' + ''.join(f'<li><a href="{u}" rel="noopener" target="_blank">{n}</a></li>' for n, u in SOCIAL) + '</ul>') if SOCIAL else '') + '''
      </div>
      <div>
        <h2>Snel naar</h2>
        <ul>
          <li><a href="prijsberekening.html">Bereken je prijs</a></li>
          <li><a href="werkwijze.html">Werkwijze en vragen</a></li>
          <li><a href="over-ons.html">Over ons</a></li>
          <li><a href="algemene-voorwaarden.html">Algemene voorwaarden</a></li>
          <li><a href="privacy.html">Privacy</a></li>
          <li><a href="sitemap.html">Sitemap en werkgebied</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-legal">
      <span><b>Super Schoon</b> by S&amp;S Projects</span>
      <span>Beekveldstraat 1A, 1980 Zemst</span>
      <span>0468 43 00 53</span>
      <span>info@ssprojects.be</span>
      <span>Btw BE 0735.883.273</span>
    </div>
  </div>
</footer>
<script src="assets/js/site.js" defer></script>
<script src="assets/js/t.js" defer></script>
'''

import hashlib
def versie(doc):
    def v(m):
        f = m.group(2)
        try: h = hashlib.md5(open(OUT + f, 'rb').read()).hexdigest()[:8]
        except FileNotFoundError: return m.group(0)
        return f'{m.group(1)}{f}?v={h}"'
    return re.sub(r'((?:href|src)=")(assets/(?:css|js)/[^"?]+)"', v, doc)

def page(path, title, desc, body, extra_css='', scripts='', schema=None):
    doc = head(title, desc, path, extra_css, schema) + header(path) + body + FOOTER + scripts + '</body>\n</html>\n'
    open(OUT + path, 'w').write(versie(doc))

def pic(name, alt, sizes='(max-width: 900px) 100vw, 50vw', cls='photo', caption='', tags='', eager=False):
    load = 'eager" fetchpriority="high' if eager else 'lazy'
    cap = f'<figcaption>{caption}</figcaption>' if caption else ''
    return f'''<figure class="{cls}">{tags}<picture>
<source type="image/webp" srcset="assets/img/{name}-800.webp 800w, assets/img/{name}-1400.webp 1400w" sizes="{sizes}">
<img src="assets/img/{name}-1400.jpg" srcset="assets/img/{name}-800.jpg 800w, assets/img/{name}-1400.jpg 1400w" sizes="{sizes}" alt="{alt}" width="1050" height="1400" loading="{load}" decoding="async">
</picture>{cap}</figure>'''

USPS = [
  ('ervaring', 'Meer dan 10 jaar ervaring', 'Wij leggen al meer dan 10 jaar verhardingen aan. We weten dus precies hoe we ze reinigen.'),
  ('ecologisch', '100% ecologisch', 'Een gepatenteerd borstelsysteem en een beetje water. Geen chemicaliën, zeep, stoom of hogedruk.'),
  ('alleverhardingen', 'Alle verhardingen', 'Natuursteen, klinkers, tegels, beton en zelfs houten en composiet terrassen.'),
  ('geenschade', 'Geen schade', 'Je voegen blijven zitten, het oppervlak wordt niet ruw en de oorspronkelijke kleur komt terug.'),
]
def usp_band(light=False):
    items = ''.join(f'<div class="usp">{icon(k)}<h3>{t}</h3><p>{d}</p></div>' for k, t, d in USPS)
    return f'<div class="usps{" on-light" if light else ""}">{items}</div>'

TYPES = ['Natuursteen', 'Keramische tegel', 'Betonklinker', 'Kleiklinker', 'XL-tegel', 'Gepolierde beton', 'Houten terras', 'Composiet terras']

CTA = '''<section class="band-green"><div class="wrap cta">
  <div class="stack"><h2>Wat kost het voor jouw terras of oprit?</h2><p class="lead">Vul de oppervlakte en je gemeente in en je ziet meteen je prijs. Of teken je verharding gewoon in op de luchtfoto.</p></div>
  <a class="btn light" href="prijsberekening.html">Bereken je prijs</a>
</div></section>
'''

STONE = '''<section><div class="wrap">
  <div class="endorse">
    <div class="endorse-mark" aria-hidden="true"><span>Aanbevolen</span><span>door de</span><span>fabrikant</span></div>
    <div class="stack">
      <p class="eyebrow">Stone &amp; Style · Ebema</p>
      <h2>Tegelfabrikant raadt deze reiniging aan</h2>
      <p>Stone &amp; Style, het merk voor tegels en klinkers van Ebema, raadt in zijn onderhoudsrichtlijnen aan om je terras door een vakman te laten reinigen met een speciale borstel en uitsluitend water, zonder chemicaliën. Daarvoor verwijzen ze naar Terrazza: het borstelsysteem waarmee wij werken.</p>
      <p>Een hogedrukreiniger raden ze uitdrukkelijk af: die maakt het oppervlak poreuzer en ruwer, waardoor mos en algen sneller terugkomen.</p>
      <p><a href="https://stone-style.ebema.be/nl-BE/plaatsingsvoorschriften/onderhoud-en-reiniging" target="_blank" rel="noopener">Lees de onderhoudsrichtlijnen van Stone &amp; Style</a></p>
    </div>
  </div>
</div></section>
'''

# ---------------- HOME ----------------
home = f'''<section class="hero"><div class="wrap hero-grid">
  <div class="hero-text">
    <p class="eyebrow">Terrassen · opritten · paden</p>
    <h1>Ecologische reiniging van buitenverhardingen</h1>
    <p class="lead">Super Schoon maakt je terras, oprit of tuinpad weer proper met een gepatenteerd borstelsysteem. Zonder chemicaliën, zeep, stoom of hogedruk, en zonder je voegen of stenen te beschadigen.</p>
    <ul class="checks">
      <li>Veilig voor je tuin, kinderen, huisdieren en het grondwater</li>
      <li>Voor natuursteen, klinkers, tegels, beton en hout</li>
      <li>Een spin-off van tuinaannemer S&amp;S Projects, met meer dan 10 jaar ervaring</li>
    </ul>
    <div class="btns"><a class="btn green" href="prijsberekening.html">Bereken je prijs</a><a class="btn ghost" href="werkwijze.html">Zo werken wij</a></div>
  </div>
  {pic('oprit-half-gereinigd', 'Oprit in betonklinkers: bovenaan nog vol mos en korstmos, onderaan gereinigd met het borstelsysteem', '(max-width: 900px) 100vw, 46vw', caption='Dezelfde oprit: bovenaan nog vuil, onderaan gereinigd.', eager=True)}
</div></section>

<section class="band-ink"><div class="wrap">
  <div class="sec-head"><p class="eyebrow">Waarom Super Schoon</p><h2>Grondig proper, zonder schade</h2></div>
  {usp_band()}
</div></section>

<section><div class="wrap split">
  {pic('voor-na-betonklinkers', 'Betonklinkers met links nog groene aanslag en korstmos, rechts na de reiniging', '(max-width: 900px) 100vw, 50vw', tags='<span class="tag voor" style="left:12px">Voor</span><span class="tag na" style="right:12px">Na</span>')}
  <div class="stack">
    <p class="eyebrow">Hoe wij reinigen</p>
    <h2>Borstels in plaats van hogedruk</h2>
    <p>We werken met een wereldwijd gepatenteerd borstelsysteem. Korte borstelharen reinigen het oppervlak, lange haren halen het vuil tussen de voegen weg. Het losgekomen vuil en een beetje water vormen samen een natuurlijk reinigingsmiddel.</p>
    <p>Hogedruk spuit voegen uit en maakt stenen ruw, waardoor nieuw vuil zich sneller vastzet. Onze borstels polijsten het oppervlak net. Er spat ook geen vuil tegen je gevel of ramen.</p>
    <p><a href="werkwijze.html">Lees hoe een reiniging verloopt</a></p>
  </div>
</div></section>

<section class="band-stone"><div class="wrap split top">
  <div class="stack">
    <p class="eyebrow">Geschikt voor</p>
    <h2>Elke buitenverharding</h2>
    <p class="lead">Ons borstelsysteem werkt op harde en zachte materialen. Bij houten terrassen verdwijnt ook de gladde laag, zodat je er weer veilig over loopt.</p>
  </div>
  <ul class="types">{''.join(f'<li>{t}</li>' for t in TYPES)}</ul>
</div></section>

<section><div class="wrap split">
  <div class="stack">
    <p class="eyebrow">Over ons</p>
    <h2>Van aanleggen naar reinigen</h2>
    <p>Super Schoon is ontstaan uit tuinaannemer S&amp;S Projects uit Zemst. Daar leggen Steven Vercammen en Sander Vangrunderbeek al meer dan 10 jaar terrassen, opritten en tuinpaden aan. Wie verhardingen zelf aanlegt, weet ook hoe je ze reinigt zonder ze te beschadigen.</p>
    <p><a href="over-ons.html">Meer over Super Schoon</a></p>
  </div>
  {pic('tuinpad-half-gereinigd', 'Tuinpad in betonklinkers tussen gazon en haag, achteraan nog vuil, vooraan gereinigd', '(max-width: 900px) 100vw, 50vw')}
</div></section>
''' + CTA
page('index.html', 'Super Schoon | Ecologische terras- en opritreiniging', 'Super Schoon reinigt terrassen, opritten en paden ecologisch: zonder chemie, zeep of hogedruk, en zonder schade. Bereken direct je prijs. Spin-off van S&S Projects, Zemst.', home)

# ---------------- WERKWIJZE ----------------
FAQ = [
  ('Wat kost een reiniging?', 'De prijs hangt af van de oppervlakte en de afstand tot Zemst. Met onze <a href="prijsberekening.html">prijsberekening</a> zie je meteen wat het kost, inclusief btw.'),
  ('Gebruiken jullie chemische producten?', 'Nee. We reinigen alleen met een gepatenteerd borstelsysteem en een beetje water. Er komen geen chemicaliën, zeep of andere middelen aan te pas.'),
  ('Waarom geen hogedruk?', 'Hogedruk spuit voegen uit, maakt de toplaag van stenen ruw en doet vuil opspatten tegen gevels en ramen. Ruwe stenen vervuilen bovendien sneller opnieuw. Onze borstels polijsten het oppervlak, waardoor nieuw vuil minder snel hecht.'),
  ('Welke verhardingen kunnen jullie reinigen?', 'Natuursteen, keramische tegels, betonklinkers, kleiklinkers, XL-tegels, gepolierde beton, asfalt en houten of composiet terrassen.'),
  ('Wat moet ik zelf voorzien?', 'Een buitenkraan met minstens 2,5 bar waterdruk en een Gardena-koppeling, een stopcontact (230 V) in de buurt en een parkeerplaats voor onze bestelwagen voor de deur. Maak de oppervlakte ook vrij van meubels, bloempotten en voertuigen.'),
  ('Raden tegelfabrikanten deze methode aan?', 'Ja. Stone &amp; Style, het tegel- en klinkermerk van Ebema, raadt aan om je terras door een vakman te laten reinigen met een speciale borstel en uitsluitend water, en verwijst daarvoor naar Terrazza: het borstelsysteem waarmee wij werken. Een hogedrukreiniger raden ze af. <a href="https://stone-style.ebema.be/nl-BE/plaatsingsvoorschriften/onderhoud-en-reiniging" rel="noopener" target="_blank">Lees hun onderhoudsrichtlijnen</a>.'),
  ('Komt het voegzand los?', 'Bij een grondige reiniging komt los voegzand, mos en vuil uit de voegen. Dat is normaal. Wil je de voegen daarna opnieuw laten invullen, vraag het dan gerust: dat bieden we apart aan.'),
  ('Verdwijnen alle vlekken?', 'Groene aanslag, mos, algen en vuil verdwijnen. Diep ingetrokken vlekken zoals olie, roest of kalk verdwijnen niet altijd volledig. Twijfel je, stuur ons dan een foto.'),
  ('Wat is de beste periode om je terras, oprit of tuinpad te reinigen?', 'Dat kan het hele jaar door. In het najaar en de winter voorkom je met een reiniging dat je uitschuift op een glad, groen terras of pad. In de lente en de zomer geniet je meteen van een terras dat er weer als nieuw uitziet. Alleen bij vorst of hevige regen reinigen we niet: dan spreken we samen een nieuwe datum af.'),
]
faq_schema = {"@context": "https://schema.org", "@type": "FAQPage", "mainEntity": [
  {"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": re.sub('<[^>]+>', '', a)}} for q, a in FAQ]}
werk = f'''<section class="page-head"><div class="wrap stack">
  <p class="eyebrow">Werkwijze</p>
  <h1>Zo reinigen wij je verharding</h1>
  <p class="lead">Van prijsberekening tot een propere oprit: zo verloopt een reiniging bij Super Schoon.</p>
</div></section>

<section><div class="wrap">
  <ol class="steps">
    <li><h3>Bereken je prijs</h3><p>Vul online de oppervlakte en je gemeente in, of teken je verharding in op de luchtfoto. Je ziet meteen wat het kost.</p></li>
    <li><h3>We plannen een datum</h3><p>Na je aanvraag bevestigen we de prijs en spreken we een dag af. Zorg die dag voor een buitenkraan (minstens 2,5 bar, Gardena-koppeling), een stopcontact en een parkeerplaats voor de deur.</p></li>
    <li><h3>We reinigen</h3><p>Met het borstelsysteem en een beetje water. Zonder chemicaliën, zeep, stoom of hogedruk.</p></li>
    <li><h3>Klaar</h3><p>We laten alles borstelschoon achter. Kinderen en huisdieren kunnen meteen weer naar buiten.</p></li>
  </ol>
</div></section>

<section class="band-stone"><div class="wrap split top">
  <div class="stack">
    <p class="eyebrow">Het borstelsysteem</p>
    <h2>Polijsten in plaats van wegspuiten</h2>
    <p>De borstelmachine heeft vier blokborstels met een mix van korte en lange haren. De korte haren reinigen het oppervlak, de lange halen het vuil tussen de voegen weg. Het systeem is wereldwijd gepatenteerd.</p>
    <p>Het losgekomen vuil vormt met een beetje water een natuurlijk reinigingsmiddel. Zo verdwijnen groene aanslag, mos en algen, en komt de oorspronkelijke kleur van je stenen terug.</p>
  </div>
  <div class="table-wrap"><table class="compare">
    <thead><tr><th scope="col"></th><th scope="col">Hogedruk</th><th scope="col">Super Schoon</th></tr></thead>
    <tbody>
      <tr><th scope="row">Voegen</th><td>Worden uitgespoten</td><td>Blijven zitten</td></tr>
      <tr><th scope="row">Oppervlak</th><td>Wordt ruw, vervuilt sneller opnieuw</td><td>Wordt gepolijst, vuil hecht minder</td></tr>
      <tr><th scope="row">Gevel en ramen</th><td>Vuil spat op</td><td>Geen opspattend vuil</td></tr>
      <tr><th scope="row">Middelen</th><td>Vaak met chemische nabehandeling</td><td>Alleen water</td></tr>
      <tr><th scope="row">Lawaai</th><td>Luid</td><td>Stil</td></tr>
    </tbody>
  </table></div>
</div></section>

<section><div class="wrap">
  <div class="sec-head"><p class="eyebrow">Veelgestelde vragen</p><h2>Goed om te weten</h2></div>
  <div class="faq">{''.join(f'<details><summary>{q}</summary><p>{a}</p></details>' for q, a in FAQ)}</div>
</div></section>
''' + CTA
page('werkwijze.html', 'Werkwijze en veelgestelde vragen | Super Schoon', 'Zo reinigt Super Schoon terrassen en opritten: een gepatenteerd borstelsysteem met alleen water, zonder hogedruk of chemie. Met antwoorden op veelgestelde vragen.', werk, schema=[faq_schema])

TEAM = [('steven', 'Steven Vercammen', 'Zaakvoerder'), ('sander', 'Sander Vangrunderbeek', 'Zaakvoerder'), ('indy', 'Indy', 'Tuinarbeider'), ('vital', 'Vital', 'Tuinarbeider'), ('thomas', 'Thomas', 'Tuinarbeider'), ('maksym', 'Maksym', 'Tuinarbeider'), ('dirk', 'Dirk', 'Freelance tuinarbeider'), ('lorenzo', 'Lorenzo', 'Stagiair')]
def tcard(k, n, r, groot=False):
    f = f'{k}-groot' if groot else k
    w, h = (800, 1000) if groot else (480, 600)
    return (f'<figure class="tcard{" tcard-groot" if groot else ""}"><picture><source type="image/webp" srcset="assets/img/team/{f}.webp">'
            f'<img src="assets/img/team/{f}.jpg" alt="{n}, {r.lower()} bij S&amp;S Projects" width="{w}" height="{h}" loading="lazy" decoding="async"></picture>'
            f'<figcaption><strong>{n}</strong><span>{r}</span></figcaption></figure>')
TEAM_OK = all(os.path.exists(OUT + f'assets/img/team/{k}.jpg') for k, _, _ in TEAM)
TEAMSEC = '' if TEAM_OK else '<!--'
TEAMEND = '' if TEAM_OK else '-->'
# ---------------- OVER ONS ----------------
over = f'''<section class="page-head"><div class="wrap stack">
  <p class="eyebrow">Over ons</p>
  <h1>Ecologische reiniging van buitenverhardingen</h1>
  <p class="lead">Super Schoon is gespecialiseerd in de ecologische reiniging van buitenverhardingen. We maken terrassen, opritten, paden en speelpleinen weer proper, zonder chemicaliën, zeep, stoom of hogedruk.</p>
</div></section>

<section><div class="wrap split top">
  <div class="prose">
    <h2>Van aanleggen naar reinigen</h2>
    <p>Super Schoon is een spin-off van tuinaannemer S&amp;S Projects uit Zemst. Daar leggen Steven Vercammen en Sander Vangrunderbeek al meer dan 10 jaar terrassen, opritten en tuinpaden aan. Wie verhardingen zelf aanlegt, weet ook hoe je ze reinigt zonder ze te beschadigen. Die kennis brengen we nu samen in Super Schoon.</p>
    <h2>Hoe wij reinigen</h2>
    <p>We werken met een wereldwijd gepatenteerd borstelsysteem. Korte borstelharen reinigen het oppervlak, lange haren halen het vuil tussen de voegen weg. Het losgekomen vuil en een beetje water vormen samen een natuurlijk reinigingsmiddel. Er komt niets schadelijks in je borders, je gazon of het grondwater terecht. Kinderen en huisdieren kunnen meteen weer naar buiten.</p>
    <h2>Voor elke verharding, zonder schade</h2>
    <p>Ons borstelsysteem werkt op natuursteen, betonklinkers, gebakken klinkers, beton, asfalt en houten of composiet terrassen. Hogedruk spuit voegen uit en maakt stenen ruw. Onze borstels polijsten het oppervlak net. Daardoor blijven je voegen intact, komt de oorspronkelijke kleur terug en hecht nieuw vuil minder snel. Er spat ook geen vuil tegen je gevel of ramen.</p>
  </div>
  {pic('oprit-half-gereinigd', 'Oprit in betonklinkers, half gereinigd door Super Schoon', '(max-width: 900px) 100vw, 50vw')}
</div></section>

<section class="band-stone"><div class="wrap">
  <div class="sec-head"><p class="eyebrow">Waarom Super Schoon</p><h2>Vier redenen</h2></div>
  {usp_band(light=True)}
</div></section>

{TEAMSEC}<section><div class="wrap team-wrap">
  <div class="team-top">
    <div class="stack team-intro">
      <p class="eyebrow">Ons team</p>
      <h2>Maak kennis met het team</h2>
      <p class="lead">Super Schoon is een initiatief van Steven en Sander, die S&amp;S Projects in 2016 als twee vrienden met een passie voor groen startten.</p>
      <p class="lead">Samen met hun team van geschoolde tuinprofessionals zorgen ze al meer dan 10 jaar voor zorgeloze tuinen en terrassen.</p>
    </div>
    <div class="founders">{''.join(tcard(k, n, r, groot=True) for k, n, r in TEAM[:2])}</div>
  </div>
  <div class="crew">{''.join(tcard(k, n, r) for k, n, r in TEAM[2:])}</div>
</div></section>
{TEAMEND}
<section class="band-stone"><div class="wrap cta">
  <div class="stack"><p class="eyebrow">S&amp;S Projects</p><h2>Ook voor tuinaanleg en tuinonderhoud</h2><p class="lead">Super Schoon is een spin-off van tuinaannemer S&amp;S Projects. Bekijk ook onze andere diensten: tuinontwerp en tuinaanleg, tuinonderhoud, en poorten en afsluitingen.</p></div>
  <a class="btn" href="https://www.ssprojects.be" target="_blank" rel="noopener">Naar ssprojects.be</a>
</div></section>
''' + CTA
page('over-ons.html', 'Over ons | Super Schoon by S&S Projects', 'Super Schoon is de ecologische reinigingsdienst van tuinaannemer S&S Projects uit Zemst: meer dan 10 jaar ervaring met terrassen en opritten.', over)

# ---------------- PRIJSBEREKENING ----------------
form = open(SP + 'calc_form.html').read()
for a, b in [('class="grid"', 'class="calc-grid"'), ('class="col"', 'class="calc-col"'), ('class="side"', 'class="calc-side"'),
             ('class="types"', 'class="vtypes"'), ('class="type"', 'class="vtype"'), ('class="btn go"', 'class="mbtn go"'), ('class="btn"', 'class="mbtn"'),
             ('class="primary"', 'class="btn green"'), ('<p class="eyebrow">Jouw prijs', '<p class="eyebrow">Jouw prijs')]:
    form = form.replace(a, b)
form = form.replace('<div class="submit">', '<div class="hp" aria-hidden="true"><label for="hp-website">Laat dit veld leeg</label><input id="hp-website" name="website" tabindex="-1" autocomplete="off"></div>\n        <div class="formmsg err" id="sendmsg" role="alert" hidden></div>\n        <div class="submit">')
form = form.replace('<span>Ik geef Super Schoon toestemming om mijn gegevens te gebruiken om contact met mij op te nemen over deze aanvraag.</span>',
                    '<span>Ik geef Super Schoon toestemming om mijn gegevens te gebruiken om contact met mij op te nemen over deze aanvraag. Meer in onze <a href="privacy.html">privacyverklaring</a>.</span>')
form = form.replace('De definitieve prijs bevestigen we na een korte controle ter plaatse of aan de hand van foto\'s.', 'De definitieve prijs bevestigen we na een korte controle ter plaatse of aan de hand van foto\'s. Zie ook onze <a href="algemene-voorwaarden.html" style="color:#fff">algemene voorwaarden</a>.')
assert 'class="mbtn go"' in form and 'hp-website' in form and 'class="btn green"' in form
calc = f'''<section class="page-head"><div class="wrap stack">
  <p class="eyebrow">Prijsberekening</p>
  <h1>Bereken direct de prijs voor je terras of oprit</h1>
  <p class="lead">Vul de oppervlakte en je gemeente in en je ziet meteen wat de reiniging kost. Laat je gegevens achter, dan nemen we contact op om een datum te plannen.</p>
</div></section>
<section class="band-stone"><div class="wrap">
{form}
</div></section>
'''
page('prijsberekening.html', 'Bereken de prijs voor je terras- of opritreiniging | Super Schoon', 'Bereken in één minuut wat de ecologische reiniging van je terras of oprit kost. Vul de oppervlakte in of teken ze in op de luchtfoto, en vraag meteen een offerte aan.', calc,
     extra_css='<link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css">\n<link rel="stylesheet" href="assets/css/calculator.css">\n',
     scripts='<script src="assets/vendor/leaflet/leaflet.js"></script>\n<script src="assets/js/calculator.js"></script>\n')

# ---------------- CONTACT ----------------
contact = '''<section class="page-head"><div class="wrap stack">
  <p class="eyebrow">Contact</p>
  <h1>Vraag of opmerking?</h1>
  <p class="lead">Bel, mail of stuur ons een bericht. Wil je meteen een prijs? Gebruik dan de <a href="prijsberekening.html">prijsberekening</a>.</p>
</div></section>
<section><div class="wrap split top">
  <div class="stack-lg">
    <div class="facts">
      <div class="fact"><span>Telefoon</span><a href="tel:+32468430053">0468 43 00 53</a></div>
      <div class="fact"><span>WhatsApp</span><a href="''' + WHATSAPP + '''" rel="noopener" target="_blank">Stuur een bericht</a></div>
      <div class="fact"><span>E-mail</span><a href="mailto:info@ssprojects.be">info@ssprojects.be</a></div>
      <div class="fact"><span>Adres</span><b>Beekveldstraat 1A<br>1980 Zemst</b></div>
      <div class="fact"><span>Btw</span><b>BE 0735.883.273</b></div>
    </div>
    <p class="hint">Super Schoon is een handelsnaam van S&amp;S Projects BV.</p>
  </div>
  <form class="panel" id="contactform" novalidate>
    <h2 style="font-size:26px;margin-bottom:18px">Stuur ons een bericht</h2>
    <div class="fields">
      <div class="f full"><label for="c-naam">Naam</label><input id="c-naam" name="naam" autocomplete="name" required><div class="ferr" id="ce-naam"></div></div>
      <div class="f"><label for="c-email">E-mailadres</label><input id="c-email" name="email" type="email" autocomplete="email" required><div class="ferr" id="ce-email"></div></div>
      <div class="f"><label for="c-tel">Telefoonnummer <span class="opt">(optioneel)</span></label><input id="c-tel" name="tel" type="tel" autocomplete="tel"></div>
      <div class="f full"><label for="c-bericht">Bericht</label><textarea id="c-bericht" name="bericht" required></textarea><div class="ferr" id="ce-bericht"></div></div>
      <div class="hp" aria-hidden="true"><label for="c-website">Laat dit veld leeg</label><input id="c-website" name="website" tabindex="-1" autocomplete="off"></div>
      <label class="consent full" for="c-ok"><input type="checkbox" id="c-ok" name="ok" required><span>Ik geef Super Schoon toestemming om mijn gegevens te gebruiken om op mijn bericht te antwoorden. Meer in onze <a href="privacy.html">privacyverklaring</a>.</span></label>
      <label class="consent full" for="c-nieuwsbrief"><input type="checkbox" id="c-nieuwsbrief" name="nieuwsbrief"><span>Ja, ik wil de nieuwsbrief van Super Schoon en S&amp;S Projects ontvangen <span class="opt">(optioneel, je kan je altijd uitschrijven)</span>.</span></label>
      <div class="ferr full" id="ce-ok"></div>
      <div class="formmsg full" id="c-msg" role="status" hidden></div>
      <div class="full"><button class="btn green" type="submit">Bericht versturen</button></div>
    </div>
  </form>
</div></section>
'''
page('contact.html', 'Contact | Super Schoon, terras- en opritreiniging', 'Neem contact op met Super Schoon, ecologische reiniging van buitenverhardingen: 0468 43 00 53, info@ssprojects.be, Beekveldstraat 1A, 1980 Zemst.', contact,
     scripts='<script src="assets/js/contact.js"></script>\n')

# ---------------- LEGAL ----------------
def legal(path, title, desc, h1, md_html):
    body = f'''<section class="page-head"><div class="wrap stack"><p class="eyebrow">Super Schoon</p><h1>{h1}</h1></div></section>
<section><div class="wrap"><div class="prose">{md_html}</div></div></section>
'''
    page(path, title, desc, body)

legal('algemene-voorwaarden.html', 'Algemene voorwaarden | Super Schoon', 'Algemene voorwaarden van Super Schoon, handelsnaam van S&S Projects BV, voor de reiniging van buitenverhardingen.', 'Algemene voorwaarden', open(SP + 'av.html').read())
legal('privacy.html', 'Privacyverklaring | Super Schoon', 'Hoe Super Schoon (S&S Projects BV) omgaat met je persoonsgegevens.', 'Privacyverklaring', open(SP + 'privacy.html').read())

# ---------------- 404 ----------------
nf = '''<section class="page-head" style="border:0"><div class="wrap stack">
  <p class="eyebrow">Pagina niet gevonden</p>
  <h1>Deze pagina bestaat niet (meer)</h1>
  <p class="lead">Misschien zoek je een van deze pagina's:</p>
  <div class="btns"><a class="btn green" href="/prijsberekening.html">Bereken je prijs</a><a class="btn ghost" href="/">Naar de startpagina</a></div>
</div></section>
'''
doc = head('Pagina niet gevonden | Super Schoon', 'Deze pagina bestaat niet.', '404.html') + header('') + nf + FOOTER + '</body>\n</html>\n'
doc = doc.replace('<head>', '<head>\n<base href="/">\n<meta name="robots" content="noindex">', 1)
open(OUT + '404.html', 'w').write(doc)
exec(open(SP + 'regio.py').read())
exec(open(SP + 'seo_audit.py').read())
print('ok')
