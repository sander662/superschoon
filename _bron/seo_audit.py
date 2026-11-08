# ---------------- SEO-ANALYSE voor het dashboard (wordt uitgevoerd vanuit build.py) ----------------
import glob as _glob, datetime as _dt
from html.parser import HTMLParser as _HP
from collections import Counter as _C

class _P(_HP):
    def __init__(self):
        super().__init__(); self.h1 = 0; self.imgs = []; self.links = []; self.meta = {}; self.canon = False
        self.ld = 0; self.tekst = []; self._skip = 0; self.noindex = False; self.title = ''; self._t = False
    def handle_starttag(self, tag, a):
        a = dict(a)
        if tag == 'h1': self.h1 += 1
        if tag == 'img': self.imgs.append(a)
        if tag == 'a' and a.get('href'): self.links.append(a['href'])
        if tag == 'meta':
            k = a.get('name') or a.get('property')
            if k: self.meta[k] = a.get('content', '')
            if k == 'robots' and 'noindex' in a.get('content', ''): self.noindex = True
        if tag == 'link' and a.get('rel') == 'canonical': self.canon = True
        if tag == 'script' and a.get('type') == 'application/ld+json': self.ld += 1
        if tag in ('script', 'style'): self._skip += 1
        if tag == 'title': self._t = True
    def handle_endtag(self, tag):
        if tag in ('script', 'style'): self._skip -= 1
        if tag == 'title': self._t = False
    def handle_data(self, d):
        if self._t: self.title += d
        elif not self._skip: self.tekst.append(d)

_sitemap = open(OUT + 'sitemap.xml').read()
_res = []
for _f in sorted(_glob.glob(OUT + '*.html')):
    _naam = _f[len(OUT):]
    if _naam == '404.html' or _naam.startswith('google'): continue
    _raw = open(_f).read(); _p = _P(); _p.feed(_raw)
    _res.append((_naam, _raw, _p))

_titels = _C(html.unescape(p.title) for _, _, p in _res)
_descs = _C(p.meta.get('description', '') for _, _, p in _res)
_paginas = []
for _naam, _raw, _p in _res:
    t = html.unescape(_p.title); d = html.unescape(_p.meta.get('description', ''))
    url = '/' if _naam == 'index.html' else '/' + _naam
    woorden = len(re.findall(r'\w+', ' '.join(_p.tekst)))
    intern = [l for l in _p.links if not re.match(r'^(https?:|mailto:|tel:|#)', l)]
    checks = [
        (30 <= len(t) <= 65, f'Titel is {len(t)} tekens (ideaal 30–65)'),
        (70 <= len(d) <= 160, f'Meta-beschrijving is {len(d)} tekens (ideaal 70–160)'),
        (_titels[t] == 1, 'Titel komt ook op een andere pagina voor'),
        (_descs[_p.meta.get('description', '')] == 1, 'Meta-beschrijving komt ook op een andere pagina voor'),
        (_p.h1 == 1, f'{_p.h1} H1-titels (er hoort er precies 1 te zijn)'),
        (_p.canon, 'Canonieke link ontbreekt'),
        ('og:title' in _p.meta and 'og:image' in _p.meta, 'Gegevens voor sociale media (Open Graph) ontbreken'),
        (_p.ld > 0, 'Gestructureerde gegevens (schema.org) ontbreken'),
        (all(i.get('alt') is not None for i in _p.imgs), 'Afbeelding zonder alt-tekst'),
        (all(i.get('width') and i.get('height') for i in _p.imgs), 'Afbeelding zonder breedte en hoogte'),
        (woorden >= 300 or _naam in ('contact.html', 'prijsberekening.html', 'sitemap.html'), f'Weinig tekst ({woorden} woorden, minstens 300 aanbevolen)'),
        (len(intern) >= 3, 'Te weinig interne links'),
        (url in _sitemap or (DOMAIN + url) in _sitemap, 'Staat niet in sitemap.xml'),
        (not _p.noindex, 'Pagina staat op noindex'),
        (len(_raw.encode()) < 150_000, f'HTML is groot ({len(_raw.encode()) // 1024} kB)'),
    ]
    fouten = [m for ok, m in checks if not ok]
    _paginas.append({'url': url, 'score': round(100 * sum(ok for ok, _ in checks) / len(checks)), 'fouten': fouten})
_paginas.sort(key=lambda x: (x['score'], x['url']))

_ht = open(OUT + '.htaccess').read()
_ix = open(OUT + 'index.html').read()
_robots = open(OUT + 'robots.txt').read()
_site = [
    {'naam': 'sitemap.xml aanwezig en ingediend bij Google', 'ok': os.path.exists(OUT + 'sitemap.xml'), 'uitleg': f'{_sitemap.count("<url>")} pagina\'s'},
    {'naam': 'robots.txt verwijst naar de sitemap', 'ok': 'Sitemap:' in _robots, 'uitleg': ''},
    {'naam': 'llms.txt voor AI-assistenten', 'ok': os.path.exists(OUT + 'llms.txt'), 'uitleg': ''},
    {'naam': 'Altijd https en www', 'ok': 'HTTPS' in _ht and 'superschoon' in _ht, 'uitleg': ''},
    {'naam': 'Geverifieerd in Google Search Console', 'ok': bool(_glob.glob(OUT + 'google*.html')), 'uitleg': ''},
    {'naam': 'Eigen 404-pagina', 'ok': os.path.exists(OUT + '404.html'), 'uitleg': ''},
    {'naam': 'Bedrijfsgegevens (LocalBusiness) op elke pagina', 'ok': all('LocalBusiness' in r for _, r, _ in _res), 'uitleg': ''},
    {'naam': 'Gemeentepagina\'s voor het werkgebied', 'ok': len([1 for n, _, _ in _res if n.startswith('terrasreiniging-')]) > 0,
     'uitleg': f"{len([1 for n, _, _ in _res if n.startswith('terrasreiniging-')])} pagina's"},
    {'naam': 'Google Bedrijfsprofiel', 'ok': True, 'uitleg': 'Geverifieerd; openingsuren, servicegebied en beschrijving ingevuld (8/11). Nog toe te voegen: logo, foto\'s, diensten'},
    {'naam': 'Sociale media gekoppeld (Facebook, Instagram)', 'ok': all(x in _ix for x in ('facebook.com', 'wa.me')),
     'uitleg': ('Facebook en WhatsApp gelinkt' + ('' if 'instagram.com' in _ix else '; Instagram nog aan te maken'))},
    {'naam': 'Reviews op de website', 'ok': False, 'uitleg': 'Nog geen reviews verzameld'},
]
_gem = sum(p['score'] for p in _paginas) / len(_paginas)
_score = round(0.7 * _gem + 0.3 * 100 * sum(c['ok'] for c in _site) / len(_site))
os.makedirs(OUT + 'dashboard', exist_ok=True)
json.dump({'datum': _dt.datetime.now().isoformat(timespec='minutes'), 'score': _score, 'pagina_gemiddelde': round(_gem),
           'aantal': len(_paginas), 'site': _site, 'paginas': _paginas},
          open(OUT + 'dashboard/seo.json', 'w'), ensure_ascii=False)
print('seo-score:', _score, '| pagina-gemiddelde', round(_gem), '| met fouten', sum(1 for p in _paginas if p['fouten']))
