/* Super Schoon – offerte opmaken in het dashboard */
(function () {
  'use strict';
  const $ = id => document.getElementById(id);
  const app = $('om');
  const CSRF = app.dataset.csrf;
  const UUR = parseFloat(app.dataset.uurtarief) || 60;
  const ZONES = window.OM_ZONES || {};
  const eur = n => '€ ' + (Math.round(n * 100) / 100).toLocaleString('nl-BE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const getal = v => { const n = parseFloat(String(v).replace(',', '.')); return isFinite(n) ? n : 0; };
  let tlContact = '';

  async function post(data) {
    try {
      const r = await fetch('offerte-maken.php', {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF': CSRF}, body: JSON.stringify(data)});
      return await r.json().catch(() => ({ok: false, fout: 'Onverwacht antwoord van de server.'}));
    } catch (e) { return {ok: false, fout: 'Geen verbinding.'}; }
  }

  /* ---- prijs reiniging (zelfde formule als de website) ---- */
  function prijsReiniging(m2) {
    if (m2 < 1) return 0;
    let p;
    if (m2 <= 50) p = 190 + 210 * (m2 - 1) / 49;
    else if (m2 <= 100) p = 400 + 7 * (m2 - 50);
    else p = 750 + 6 * (m2 - 100);
    return Math.round(p);
  }
  const m2 = $('r-m2'), prijs = $('r-prijs'), aanpassen = $('r-aanpassen'), rit = $('r-rit'), gem = $('k-gemeente');
  function zetPrijs() { if (!aanpassen.checked) prijs.value = prijsReiniging(getal(m2.value)) || ''; totaal(); }
  m2.addEventListener('input', zetPrijs);
  aanpassen.addEventListener('change', () => { prijs.readOnly = !aanpassen.checked; zetPrijs(); });
  prijs.addEventListener('input', totaal);
  rit.addEventListener('input', totaal);
  let ritZelf = false;
  rit.addEventListener('input', () => { ritZelf = true; });
  function zetRit() {
    const n = gem.value.trim();
    const sleutel = Object.keys(ZONES).find(k => k.toLowerCase() === n.toLowerCase());
    if (sleutel !== undefined) {
      if (!ritZelf) rit.value = ZONES[sleutel];
      $('r-rit-info').textContent = `${sleutel}: verplaatsing ${ZONES[sleutel] === 0 ? 'gratis (zone 1)' : eur(ZONES[sleutel]) + ' excl. btw'} volgens de prijsberekening.`;
    } else {
      $('r-rit-info').textContent = n ? 'Gemeente niet in de zonelijst: vul de verplaatsing zelf in (of laat leeg).' : '';
    }
    totaal();
  }
  gem.addEventListener('input', zetRit);
  gem.addEventListener('change', zetRit);

  /* ---- extra lijnen ---- */
  const tbody = $('lijnen');
  function lijn(d = {}) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input class="l-oms" placeholder="Bv. Voegen opnieuw inzanden" maxlength="150"></td>
      <td><input class="l-toel" maxlength="500"></td>
      <td><input class="l-aantal r" type="number" step="0.01" min="0" inputmode="decimal" value="1"></td>
      <td><input class="l-eenh" list="eenheden" maxlength="20"></td>
      <td><input class="l-prijs r" type="number" step="0.01" inputmode="decimal"></td>
      <td class="r l-tot">€ 0,00</td>
      <td><button type="button" class="link l-weg" aria-label="Lijn verwijderen">✕</button></td>`;
    tr.querySelector('.l-oms').value = d.omschrijving || '';
    tr.querySelector('.l-toel').value = d.toelichting || '';
    tr.querySelector('.l-aantal').value = d.aantal != null ? d.aantal : 1;
    tr.querySelector('.l-eenh').value = d.eenheid || '';
    tr.querySelector('.l-prijs').value = d.prijs != null ? d.prijs : '';
    tr.addEventListener('input', totaal);
    tr.querySelector('.l-weg').addEventListener('click', () => { tr.remove(); totaal(); });
    tbody.appendChild(tr);
    totaal();
    tr.querySelector(d.omschrijving ? '.l-aantal' : '.l-oms').focus();
  }
  $('l-nieuw').addEventListener('click', () => lijn());
  document.querySelectorAll('[data-snel]').forEach(b => b.addEventListener('click', () => {
    if (b.dataset.snel === 'arbeid') lijn({omschrijving: 'Arbeid: voegen opnieuw inzanden', aantal: 1, eenheid: 'uur', prijs: UUR});
    else lijn({omschrijving: 'Voegzand', aantal: 1, eenheid: 'zak', prijs: ''});
  }));
  function lijnen() {
    return [...tbody.querySelectorAll('tr')].map(tr => ({
      omschrijving: tr.querySelector('.l-oms').value.trim(), toelichting: tr.querySelector('.l-toel').value.trim(),
      aantal: getal(tr.querySelector('.l-aantal').value), eenheid: tr.querySelector('.l-eenh').value.trim(),
      prijs: getal(tr.querySelector('.l-prijs').value), tr
    }));
  }

  /* ---- totaal ---- */
  function totaal() {
    let excl = (getal(m2.value) >= 1 ? getal(prijs.value) : 0) + (rit.value === '' ? 0 : getal(rit.value));
    lijnen().forEach(l => { const t = Math.round(l.aantal * l.prijs * 100) / 100; l.tr.querySelector('.l-tot').textContent = eur(t); if (l.omschrijving) excl += t; });
    excl = Math.round(excl * 100) / 100;
    const incl = Math.round(excl * 1.21 * 100) / 100;
    $('t-excl').textContent = eur(excl); $('t-btw').textContent = eur(incl - excl); $('t-incl').textContent = eur(incl);
    $('t-vs').textContent = eur(Math.round(incl * 0.25 * 100) / 100);
  }

  /* ---- Teamleader ---- */
  const q = $('tl-q');
  if (q) {
    const res = $('tl-res');
    async function zoek() {
      res.hidden = false; res.innerHTML = '<li class="zacht">Zoeken…</li>';
      const j = await post({actie: 'zoek', q: q.value.trim()});
      if (!j.ok) { res.innerHTML = `<li class="fout">${esc(j.fout || 'Zoeken mislukt.')}</li>`; return; }
      if (!j.lijst.length) { res.innerHTML = '<li class="zacht">Geen contacten gevonden.</li>'; return; }
      res.innerHTML = j.lijst.map(c => `<li><button type="button" data-id="${esc(c.id)}"><b>${esc(c.naam)}</b><span>${esc([c.email, c.tel, c.adres].filter(Boolean).join(' · '))}</span></button></li>`).join('');
      res.querySelectorAll('button').forEach(b => b.addEventListener('click', () => kies(b.dataset.id)));
    }
    async function kies(id) {
      res.innerHTML = '<li class="zacht">Gegevens ophalen…</li>';
      const j = await post({actie: 'contact', id});
      if (!j.ok) { res.innerHTML = `<li class="fout">${esc(j.fout || 'Ophalen mislukt.')}</li>`; return; }
      const c = j.contact;
      tlContact = c.id;
      $('k-naam').value = c.naam; $('k-email').value = c.email; $('k-tel').value = c.tel; $('k-adres').value = c.adres;
      if (c.gemeente) { gem.value = c.gemeente; zetRit(); }
      res.hidden = true;
      const g = $('tl-gekozen'); g.hidden = false;
      g.innerHTML = `Klant uit Teamleader: <b>${esc(c.naam)}</b>. De offerte komt bij dit contact. <button type="button" class="link" id="tl-los">Ontkoppelen</button>`;
      $('tl-los').addEventListener('click', () => { tlContact = ''; g.hidden = true; });
    }
    $('tl-zoek').addEventListener('click', zoek);
    q.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); zoek(); } });
  }
  function esc(s) { return String(s).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c])); }

  /* ---- aanmaken ---- */
  const fout = $('om-fout');
  document.querySelectorAll('[data-wijze]').forEach(b => b.addEventListener('click', async () => {
    fout.hidden = true;
    const wijze = b.dataset.wijze;
    const data = {
      actie: 'maak', wijze, tl_contact_id: tlContact,
      klant: {naam: $('k-naam').value.trim(), email: $('k-email').value.trim(), tel: $('k-tel').value.trim(), adres: $('k-adres').value.trim(), gemeente: gem.value.trim()},
      m2: getal(m2.value), reiniging: aanpassen.checked ? getal(prijs.value) : null,
      verplaatsing: rit.value === '' ? null : getal(rit.value),
      types: [...document.querySelectorAll('input[name=type]:checked')].map(x => x.value),
      extra: lijnen().filter(l => l.omschrijving).map(({tr, ...l}) => l),
      opmerking: $('opmerking').value.trim()
    };
    const vraag = wijze === 'mailen' ? `Offerte aanmaken en mailen naar ${data.klant.email || '(geen e-mail)'}?` : 'Offerte aanmaken en nu laten tekenen?';
    if (!confirm(vraag)) return;
    document.querySelectorAll('[data-wijze]').forEach(x => x.disabled = true);
    const j = await post(data);
    document.querySelectorAll('[data-wijze]').forEach(x => x.disabled = false);
    if (!j.ok) { fout.textContent = j.fout || 'Er ging iets mis.'; fout.hidden = false; return; }
    if (wijze === 'tekenen') { location.href = j.url + '&teken=1'; return; }
    const k = $('om-klaar'); k.hidden = false;
    k.innerHTML = `<p><span class="status goed">✓</span> Offerte <b>${esc(j.nummer)}</b> aangemaakt${j.gemaild ? ' en gemaild naar de klant' : ' – <b>mailen is mislukt</b>, stuur de link zelf door'}.
      Teamleader: ${j.tl === 'ok' ? 'contact, deal en offerte aangemaakt' : esc(j.tl)}.</p>
      <div class="knoppen"><a class="knop licht" href="${esc(j.url)}" target="_blank" rel="noopener">Offerte bekijken</a><a class="knop licht" href="offerte-maken.php">Nieuwe offerte</a></div>`;
    document.querySelectorAll('[data-wijze]').forEach(x => x.hidden = true);
    k.scrollIntoView({behavior: 'smooth'});
  }));

  zetPrijs();
})();
