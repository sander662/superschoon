/* Super Schoon – intern dashboard */
'use strict';
const $ = id => document.getElementById(id);
const CSRF = $('app').dataset.csrf;
const nl = new Intl.NumberFormat('nl-BE');
const eur = n => n == null ? '–' : '€ ' + n.toLocaleString('nl-BE', {minimumFractionDigits: 0, maximumFractionDigits: 0});
const pct = n => n.toLocaleString('nl-BE', {maximumFractionDigits: 1}) + '%';
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
const iso = d => d.toLocaleDateString('sv-SE');
const dagNaam = s => new Date(s + 'T12:00:00').toLocaleDateString('nl-BE', {weekday: 'short', day: 'numeric', month: 'short'});
const kortDag = s => new Date(s + 'T12:00:00').toLocaleDateString('nl-BE', {day: 'numeric', month: 'short'});
const NAMEN = {'/': 'Home', '/prijsberekening.html': 'Prijsberekening', '/werkwijze.html': 'Werkwijze', '/over-ons.html': 'Over ons', '/contact.html': 'Contact',
  '/sitemap.html': 'Sitemap', '/algemene-voorwaarden.html': 'Algemene voorwaarden', '/privacy.html': 'Privacy'};
const paginaNaam = p => NAMEN[p] || (p.startsWith('/terrasreiniging-') ? 'Gemeente: ' + p.slice(17, -5).replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : p);
const KLIK = {tel: 'Telefoon', mail: 'E-mail', whatsapp: 'WhatsApp', extern: 'Externe links', knop: 'Knoppen', intern: 'Interne links'};

/* ---------- periode ---------- */
let periode = {dagen: 30};
function zetPeriode(dagen) {
  const tot = new Date(), van = new Date(); van.setDate(van.getDate() - (dagen - 1));
  $('van').value = iso(van); $('tot').value = iso(tot);
  document.querySelectorAll('#periodes button').forEach(b => b.setAttribute('aria-pressed', String(+b.dataset.d === dagen)));
  laad();
}
document.querySelectorAll('#periodes button').forEach(b => b.addEventListener('click', () => zetPeriode(+b.dataset.d)));
['van', 'tot'].forEach(id => $(id).addEventListener('change', () => {
  document.querySelectorAll('#periodes button').forEach(b => b.setAttribute('aria-pressed', 'false')); laad();
}));

async function laad() {
  $('periodetekst').textContent = 'Laden…';
  try {
    const r = await fetch(`api.php?van=${$('van').value}&tot=${$('tot').value}`, {credentials: 'same-origin'});
    if (r.status === 401) { location.reload(); return; }
    const d = await r.json();
    if (!r.ok) throw new Error(d.fout || 'Fout');
    toon(d);
  } catch (e) { $('periodetekst').textContent = 'De gegevens konden niet geladen worden.'; }
}

/* ---------- kerncijfers ---------- */
function verschil(nu, vorig, omgekeerd) {
  if (vorig == null) return '';
  if (!vorig) return nu ? '<span class="op">nieuw</span> t.o.v. vorige periode' : 'gelijk aan vorige periode';
  const p = Math.round((nu - vorig) / vorig * 100);
  if (p === 0) return 'gelijk aan vorige periode';
  return `<span class="${(p > 0) !== !!omgekeerd ? 'op' : 'neer'}">${p > 0 ? '▲ +' : '▼ '}${p}%</span> t.o.v. vorige periode`;
}
function kpis(k, v) {
  const tegels = [
    ['Unieke bezoekers', nl.format(k.bezoekers), verschil(k.bezoekers, v.bezoekers)],
    ['Terugkerende bezoekers', nl.format(k.terugkerend), k.bezoekers ? pct(k.terugkerend / k.bezoekers * 100) + ' van de bezoekers' : ''],
    ['Paginaweergaven', nl.format(k.pv), k.bezoeken ? (k.pv / k.bezoeken).toLocaleString('nl-BE', {maximumFractionDigits: 1}) + ' pagina\'s per bezoek' : ''],
    ['Bezoeken', nl.format(k.bezoeken), verschil(k.bezoeken, v.bezoeken)],
    ['Offerteaanvragen', nl.format(k.offertes), verschil(k.offertes, v.offertes)],
    ['Contactberichten', nl.format(k.contact), verschil(k.contact, v.contact)],
    ['Conversie', pct(k.conversie), 'aanvragen + berichten per bezoeker'],
    ['Kliks op telefoon / e-mail', nl.format(k.tel) + ' / ' + nl.format(k.mail), k.whatsapp ? nl.format(k.whatsapp) + ' WhatsApp' : 'telefoon / e-mail'],
  ];
  $('kpis').innerHTML = tegels.map(([t, w, s]) => `<div class="kpi"><span>${t}</span><b>${w}</b><small>${s}</small></div>`).join('');
}

/* ---------- grafieken ---------- */
const tip = $('tip');
function toonTip(html, x, y) {
  tip.innerHTML = html; tip.hidden = false;
  const w = tip.offsetWidth, h = tip.offsetHeight;
  tip.style.left = Math.min(window.innerWidth - w - 8, Math.max(8, x + 14)) + 'px';
  tip.style.top = Math.max(8, y - h - 12) + 'px';
}
const verbergTip = () => { tip.hidden = true; };
function schaal(max) {
  if (max <= 4) return {max: Math.max(max, 4), stap: 1};
  const ruw = max / 4, m = Math.pow(10, Math.floor(Math.log10(ruw)));
  const stap = [1, 2, 2.5, 5, 10].map(x => x * m).find(x => x >= ruw);
  return {max: Math.ceil(max / stap) * stap, stap};
}
function assen(svg, W, H, L, B, T, s, reeks, el) {
  let g = '';
  for (let y = 0; y <= s.max; y += s.stap) {
    const py = T + (H - T - B) * (1 - y / s.max);
    g += `<line class="${y === 0 ? 'basis' : 'rooster-lijn'}" x1="${L}" x2="${W}" y1="${py}" y2="${py}"/><text x="${L - 6}" y="${py + 4}" text-anchor="end">${nl.format(y)}</text>`;
  }
  const n = reeks.length, elke = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(el.clientWidth / 70))));
  reeks.forEach((r, i) => {
    if (i % elke) return;
    const x = L + (n === 1 ? (W - L) / 2 : (W - L) * (i + .5) / n);
    g += `<text x="${x}" y="${H - 6}" text-anchor="middle">${kortDag(r.dag)}</text>`;
  });
  return `<g class="as">${g}</g>`;
}
function tipHtml(r) {
  return `<b>${dagNaam(r.dag)}</b>${nl.format(r.bezoekers)} bezoekers · ${nl.format(r.pv)} weergaven<br>${nl.format(r.aanvragen)} aanvragen`;
}
function lijnGrafiek(el, reeks) {
  const W = el.clientWidth, H = el.clientHeight, L = 34, B = 24, T = 8, n = reeks.length;
  const s = schaal(Math.max(...reeks.map(r => r.bezoekers), 0));
  const x = i => L + (n === 1 ? (W - L) / 2 : (W - L) * (i + .5) / n);
  const y = v => T + (H - T - B) * (1 - v / s.max);
  const pts = reeks.map((r, i) => `${x(i)},${y(r.bezoekers)}`).join(' ');
  const vlak = n > 1 ? `<polygon class="vlak" points="${x(0)},${y(0)} ${pts} ${x(n - 1)},${y(0)}"/>` : '';
  el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Unieke bezoekers per dag">${assen(null, W, H, L, B, T, s, reeks, el)}${vlak}
    <polyline class="lijn" points="${pts}"/>${n <= 45 ? reeks.map((r, i) => `<circle class="punt" cx="${x(i)}" cy="${y(r.bezoekers)}" r="${n > 20 ? 3 : 4}"/>`).join('') : ''}
    <line class="hoverlijn" id="hl" x1="0" x2="0" y1="${T}" y2="${H - B}" visibility="hidden"/><rect x="${L}" y="0" width="${W - L}" height="${H}" fill="transparent" id="hitvlak"/></svg>`;
  const hl = el.querySelector('#hl'), vlakEl = el.querySelector('#hitvlak');
  vlakEl.addEventListener('mousemove', e => {
    const b = el.getBoundingClientRect(), i = Math.max(0, Math.min(n - 1, Math.floor((e.clientX - b.left - L) / ((W - L) / n))));
    hl.setAttribute('x1', x(i)); hl.setAttribute('x2', x(i)); hl.setAttribute('visibility', 'visible');
    toonTip(tipHtml(reeks[i]), e.clientX, e.clientY);
  });
  vlakEl.addEventListener('mouseleave', () => { hl.setAttribute('visibility', 'hidden'); verbergTip(); });
}
function staafGrafiek(el, reeks) {
  const W = el.clientWidth, H = el.clientHeight, L = 34, B = 24, T = 8, n = reeks.length;
  const s = schaal(Math.max(...reeks.map(r => r.aanvragen), 0));
  const slot = (W - L) / n, bw = Math.max(2, Math.min(28, slot - 2));
  const y = v => T + (H - T - B) * (1 - v / s.max);
  const staven = reeks.map((r, i) => {
    if (!r.aanvragen) return '';
    const x0 = L + slot * i + (slot - bw) / 2, y0 = y(r.aanvragen), h = y(0) - y0, rr = Math.min(4, bw / 2, h);
    return `<path class="staaf" data-i="${i}" d="M${x0},${y(0)} V${y0 + rr} Q${x0},${y0} ${x0 + rr},${y0} H${x0 + bw - rr} Q${x0 + bw},${y0} ${x0 + bw},${y0 + rr} V${y(0)} Z"/>`;
  }).join('');
  el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Aanvragen per dag">${assen(null, W, H, L, B, T, s, reeks, el)}${staven}
    <rect x="${L}" y="0" width="${W - L}" height="${H}" fill="transparent" id="hit2"/></svg>`;
  const hit = el.querySelector('#hit2');
  hit.addEventListener('mousemove', e => {
    const b = el.getBoundingClientRect(), i = Math.max(0, Math.min(n - 1, Math.floor((e.clientX - b.left - L) / slot)));
    el.querySelectorAll('.staaf').forEach(p => p.classList.toggle('hover', +p.dataset.i === i));
    toonTip(tipHtml(reeks[i]), e.clientX, e.clientY);
  });
  hit.addEventListener('mouseleave', () => { el.querySelectorAll('.staaf').forEach(p => p.classList.remove('hover')); verbergTip(); });
}

/* ---------- lijsten ---------- */
function staven(id, lijst, opts = {}) {
  const el = $(id);
  if (!lijst || !lijst.length) { el.innerHTML = `<p class="leeg">${opts.leeg || 'Nog geen gegevens in deze periode.'}</p>`; return; }
  const max = Math.max(...lijst.map(r => r.n)), tot = lijst.reduce((s, r) => s + r.n, 0);
  el.innerHTML = '<ul class="staven">' + lijst.map(r => {
    const naam = opts.naam ? opts.naam(r) : esc(r.naam);
    const extra = opts.pct ? ` <span class="zacht">(${pct(r.n / tot * 100)})</span>` : '';
    return `<li><span class="naam" title="${esc(r.naam)}">${naam}</span><span class="waarde">${nl.format(r.n)}${extra}</span><span class="balk"><i style="width:${Math.max(2, r.n / max * 100)}%"></i></span></li>`;
  }).join('') + '</ul>';
}

function toon(d) {
  const nu = d.nu;
  $('periodetekst').textContent = `${dagNaam(d.van)} t/m ${dagNaam(d.tot)} · vergeleken met de ${d.dagen} dagen ervoor`;
  $('live').querySelector('b').textContent = nl.format(d.live);
  $('sinds').textContent = d.sinds ? `Statistieken verzameld sinds ${new Date(d.sinds + '-01T12:00:00').toLocaleDateString('nl-BE', {month: 'long', year: 'numeric'})}.` : 'Er zijn nog geen statistieken verzameld.';
  kpis(nu.kpi, d.vorig);
  lijnGrafiek($('g-bezoekers'), nu.reeks);
  staafGrafiek($('g-aanvragen'), nu.reeks);
  $('t-bezoekers').innerHTML = `<div class="tabel-wrap"><table><thead><tr><th>Dag</th><th class="getal">Bezoekers</th><th class="getal">Weergaven</th><th class="getal">Aanvragen</th></tr></thead><tbody>${
    nu.reeks.slice().reverse().map(r => `<tr><td>${dagNaam(r.dag)}</td><td class="getal">${r.bezoekers}</td><td class="getal">${r.pv}</td><td class="getal">${r.aanvragen}</td></tr>`).join('')}</tbody></table></div>`;

  staven('bronnen', nu.bronnen, {pct: true});
  staven('verwijzers', nu.verwijzers, {leeg: 'Nog geen verwijzende websites.'});
  staven('toestellen', nu.toestellen, {pct: true, naam: r => ({gsm: 'Gsm', tablet: 'Tablet', computer: 'Computer'}[r.naam] || esc(r.naam))});
  staven('instap', nu.instap, {naam: r => esc(paginaNaam(r.naam))});
  staven('paginas', nu.paginas, {naam: r => `${esc(paginaNaam(r.naam))} <span class="zacht">· ${nl.format(r.bezoekers)} bezoekers</span>`});
  staven('gemeentes', nu.gemeentes, {naam: r => esc(paginaNaam(r.naam).replace('Gemeente: ', '')), leeg: 'Nog geen bezoeken op gemeentepagina\'s.'});
  staven('kliksoorten', nu.kliksoorten, {naam: r => KLIK[r.naam] || esc(r.naam)});
  staven('kliks', nu.kliks, {naam: r => `${esc(r.naam || r.doel)} <span class="zacht">· ${KLIK[r.soort] || r.soort}</span>`});

  const t = nu.trechter;
  const stappen = [['Bezoek prijsberekening', t.calculator], ['Begonnen met invullen', t.gestart], ['Kaart gebruikt', t.kaart], ['Aanvraag verstuurd', t.verstuurd], ['Offerte online aanvaard', t.aanvaard || 0]];
  $('trechter').innerHTML = t.calculator ? '<ul class="staven">' + stappen.map(([n, w], i) =>
    `<li><span class="naam">${n}</span><span class="waarde">${nl.format(w)}${i ? ` <span class="zacht">(${pct(t.calculator ? w / t.calculator * 100 : 0)})</span>` : ''}</span><span class="balk"><i style="width:${Math.max(2, w / t.calculator * 100)}%"></i></span></li>`).join('') + '</ul>'
    : '<p class="leeg">Nog geen bezoeken aan de prijsberekening.</p>';
  const a = nu.aanvragen;
  $('calcfeiten').innerHTML = `<div class="feiten">
    <div class="feit"><span>Gemiddelde oppervlakte</span><b>${a.gem_m2 == null ? '–' : nl.format(a.gem_m2) + ' m²'}</b></div>
    <div class="feit"><span>Gemiddelde prijs (excl. btw)</span><b>${eur(a.gem_bedrag)}</b></div>
    <div class="feit"><span>Totale waarde aanvragen</span><b>${eur(a.totaal_bedrag)}</b></div>
    <div class="feit"><span>Met foto's / ingetekend</span><b>${a.met_fotos} / ${a.met_kaart}</b></div>
    <div class="feit"><span>Nieuwsbrief aangevinkt</span><b>${a.nieuwsbrief}</b></div>
    <div class="feit"><span>Met kortingscode</span><b>${a.codes.reduce((s, c) => s + c.n, 0)}</b></div></div>`;
  staven('perbron', a.per_bron, {leeg: 'Nog geen aanvragen in deze periode.'});
  staven('pergemeente', a.per_gemeente, {leeg: 'Nog geen aanvragen in deze periode.'});
  const kc = nu.kortingscodes;
  $('kcodes').innerHTML = `<div class="feiten" style="margin:0 0 12px"><div class="feit"><span>Ingegeven codes</span><b>${kc.pogingen}</b></div><div class="feit"><span>Geldige codes</span><b>${kc.geldig}</b></div></div>`;
  const kcLijst = document.createElement('div'); kcLijst.id = 'kcodes-lijst'; $('kcodes').appendChild(kcLijst);
  staven('kcodes-lijst', kc.top, {leeg: 'Nog geen kortingscodes ingegeven.'});

  $('recent').innerHTML = a.recent.length ? `<table><thead><tr><th>Datum</th><th>Gemeente</th><th>Verharding</th><th class="getal">Oppervlakte</th><th class="getal">Prijs excl. btw</th><th>Bron</th><th>Code</th><th class="getal">Foto's</th></tr></thead><tbody>${
    a.recent.map(r => `<tr><td>${new Date(r.t * 1000).toLocaleString('nl-BE', {day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'})}</td><td>${esc(r.g || '–')}</td><td>${esc((r.ty || []).join(', ') || '–')}</td><td class="getal">${r.m2 == null ? '–' : nl.format(r.m2) + ' m²'}</td><td class="getal">${eur(r.b)}</td><td>${esc(r.bron)}</td><td>${esc(r.code || '–')}</td><td class="getal">${r.f || 0}</td></tr>`).join('')}</tbody></table>`
    : '<p class="leeg">Nog geen offerteaanvragen in deze periode.</p>';

  offertes(d.offertes || []);
  seo(d.seo); psiHistoriek(d.pagespeed || []);
}

/* ---------- offertes ---------- */
const STATUS = {open: ['Open', 'let'], aanvaard: ['Aanvaard', 'goed'], geweigerd: ['Geweigerd', 'slecht'], herroepen: ['Herroepen', 'slecht'], verlopen: ['Verlopen', 'let']};
function offertes(lijst) {
  const tel = {}; lijst.forEach(o => tel[o.status] = (tel[o.status] || 0) + 1);
  const waarde = lijst.filter(o => o.status === 'aanvaard').reduce((s, o) => s + o.incl, 0);
  $('offertes-feiten').innerHTML = `<div class="feiten" style="grid-template-columns:repeat(5,minmax(0,1fr));margin:0 0 12px">${
    ['open', 'aanvaard', 'geweigerd', 'herroepen', 'verlopen'].map(k => `<div class="feit"><span>${STATUS[k][0]}</span><b>${tel[k] || 0}</b></div>`).join('')}</div>
    <p class="zacht">Waarde aanvaarde offertes: <b>${eur(waarde)}</b> incl. btw · laatste 50 offertes, alle periodes.</p>`;
  $('offertes').innerHTML = lijst.length ? `<table><thead><tr><th>Nummer</th><th>Datum</th><th>Klant</th><th>Gemeente</th><th class="getal">Oppervlakte</th><th class="getal">Totaal incl. btw</th><th>Status</th><th></th></tr></thead><tbody>${
    lijst.map(o => `<tr><td>${esc(o.nummer)}</td><td>${new Date(o.t * 1000).toLocaleDateString('nl-BE')}</td><td>${esc(o.naam)}</td><td>${esc(o.gemeente || '–')}</td><td class="getal">${nl.format(o.m2)} m²</td><td class="getal">${eur(o.incl)}</td><td><span class="status ${STATUS[o.status][1]}">${STATUS[o.status][0]}</span>${o.status === 'aanvaard' && o.vroeg === false ? '<br><span class="zacht">niet vóór einde herroeping</span>' : ''}</td><td><a href="/offerte.php?id=${esc(o.token)}" target="_blank" rel="noopener">Bekijk</a></td></tr>`).join('')}</tbody></table>`
    : '<p class="leeg">Nog geen online offertes.</p>';
}

/* ---------- vindbaarheid ---------- */
function status(score) {
  if (score >= 90) return '<span class="status goed">✓ goed</span>';
  if (score >= 50) return '<span class="status let">! kan beter</span>';
  return '<span class="status slecht">✕ zwak</span>';
}
function ring(score) {
  const r = 62, o = 2 * Math.PI * r, kleur = score >= 90 ? 'var(--good)' : score >= 50 ? 'var(--warning)' : 'var(--critical)';
  return `<svg class="ring" viewBox="0 0 150 150" role="img" aria-label="SEO-score ${score} op 100"><circle cx="75" cy="75" r="${r}" fill="none" stroke="var(--grid)" stroke-width="12"/>
    <circle cx="75" cy="75" r="${r}" fill="none" stroke="${kleur}" stroke-width="12" stroke-linecap="round" stroke-dasharray="${o * score / 100} ${o}" transform="rotate(-90 75 75)"/>
    <text x="75" y="84" text-anchor="middle" style="font:400 38px var(--display);fill:var(--ink)">${score}</text></svg>`;
}
function seo(s) {
  if (!s) { $('seoscore').innerHTML = '<p class="leeg">Nog geen SEO-analyse beschikbaar.</p>'; return; }
  $('seoscore').innerHTML = ring(s.score) + `<p>Vindbaarheidsscore op 100<br>${status(s.score)}</p><p>Geanalyseerd op ${new Date(s.datum).toLocaleDateString('nl-BE')}<br>${s.aantal} pagina's</p>`;
  $('seochecks').innerHTML = '<ul class="checks">' + s.site.map(c => `<li>${c.ok ? '<span class="status goed">✓</span>' : '<span class="status slecht">✕</span>'}<span>${esc(c.naam)}${c.uitleg ? `<br><span class="zacht">${esc(c.uitleg)}</span>` : ''}</span></li>`).join('') + '</ul>';
  const slecht = s.paginas.filter(p => p.fouten.length).slice(0, 25);
  $('seopaginas').innerHTML = slecht.length ? `<table><thead><tr><th>Pagina</th><th class="getal">Score</th><th>Verbeterpunten</th></tr></thead><tbody>${
    slecht.map(p => `<tr><td><a href="${esc(p.url)}" target="_blank" rel="noopener">${esc(paginaNaam(p.url))}</a></td><td class="getal">${p.score}</td><td>${p.fouten.map(esc).join('<br>')}</td></tr>`).join('')}</tbody></table>`
    : '<p class="leeg">Alle pagina\'s halen alle controles.</p>';
}
const PSI = {performance: 'Snelheid', accessibility: 'Toegankelijkheid', 'best-practices': 'Goede praktijken', seo: 'SEO'};
function psiHistoriek(lijst) {
  $('psi-historiek').innerHTML = lijst.length ? `<table><thead><tr><th>Datum</th><th>Pagina</th><th>Toestel</th>${Object.values(PSI).map(n => `<th class="getal">${n}</th>`).join('')}<th class="getal">LCP</th></tr></thead><tbody>${
    lijst.slice(0, 15).map(r => `<tr><td>${new Date(r.t * 1000).toLocaleString('nl-BE', {day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'})}</td><td>${esc(paginaNaam(new URL(r.url).pathname))}</td><td>${r.toestel === 'desktop' ? 'Computer' : 'Gsm'}</td>${
      Object.keys(PSI).map(k => `<td class="getal">${r[k] ?? '–'}</td>`).join('')}<td class="getal">${esc(r.lcp || '–')}</td></tr>`).join('')}</tbody></table>`
    : '<p class="leeg">Nog geen metingen. Klik op "Nu meten".</p>';
}
$('psi-knop').addEventListener('click', async () => {
  const url = $('psi-url').value, toestel = $('psi-toestel').value, knop = $('psi-knop');
  knop.disabled = true; $('psi-status').textContent = 'Google meet de pagina, dit duurt 20 tot 60 seconden…';
  try {
    const q = new URLSearchParams({url, strategy: toestel, locale: 'nl'});
    Object.keys(PSI).forEach(c => q.append('category', c));
    const r = await fetch('https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' + q);
    const j = await r.json();
    if (!r.ok) throw new Error(j.error && j.error.code === 429 ? 'Google laat even geen metingen meer toe. Probeer het later opnieuw.' : 'De meting is mislukt.');
    const c = j.lighthouseResult.categories, au = j.lighthouseResult.audits;
    const scores = {}; Object.keys(PSI).forEach(k => scores[k] = c[k] ? Math.round(c[k].score * 100) : null);
    $('psi-scores').innerHTML = Object.entries(PSI).map(([k, n]) => `<div class="psi"><b>${scores[k] ?? '–'}</b><span>${n}</span><br>${scores[k] == null ? '' : status(scores[k])}</div>`).join('');
    const lcp = au['largest-contentful-paint']?.displayValue, cls = au['cumulative-layout-shift']?.displayValue, tbt = au['total-blocking-time']?.displayValue;
    $('psi-status').textContent = `Gemeten. Grootste element geladen na ${lcp || '–'}, verschuiving ${cls || '–'}, blokkeertijd ${tbt || '–'}.`;
    await fetch('api.php', {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF': CSRF}, body: JSON.stringify({actie: 'psi', url, toestel: toestel === 'desktop' ? 'desktop' : 'gsm', scores, lcp, cls, tbt})});
    laad();
  } catch (e) { $('psi-status').textContent = e.message || 'De meting is mislukt.'; }
  knop.disabled = false;
});

/* ---------- wachtwoord ---------- */
$('ww-form').addEventListener('submit', async e => {
  e.preventDefault();
  const msg = $('ww-msg'); msg.textContent = 'Bezig…';
  try {
    const r = await fetch('api.php', {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF': CSRF}, body: JSON.stringify({actie: 'wachtwoord', oud: $('ww-oud').value, nieuw: $('ww-nieuw').value})});
    const j = await r.json();
    msg.textContent = j.ok ? 'Wachtwoord gewijzigd.' : (j.fout || 'Wijzigen is mislukt.');
    if (j.ok) e.target.reset();
  } catch (err) { msg.textContent = 'Wijzigen is mislukt.'; }
});

let rt; window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(laad, 250); });
setInterval(laad, 5 * 60 * 1000);
zetPeriode(30);
