/* Super Schoon – online offerte */
(function(){
  'use strict';
  var $ = function(id){ return document.getElementById(id); };
  var ID = document.body.dataset.id;

  function toast(t){ var e = $('toast'); e.textContent = t; e.hidden = false; clearTimeout(toast.t); toast.t = setTimeout(function(){ e.hidden = true; }, 5000); }
  function stuur(data){
    data.id = ID;
    return fetch('/offerte-actie.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)})
      .then(function(r){ return r.json().catch(function(){ return {ok: false}; }); })
      .catch(function(){ return {ok: false, fout: 'Geen verbinding. Probeer het opnieuw.'}; });
  }
  function fout(id, t){ var e = $(id); e.textContent = t || ''; e.hidden = !t; }

  /* dialoogvensters */
  document.querySelectorAll('[data-open]').forEach(function(b){
    b.addEventListener('click', function(){
      var d = $(b.dataset.open);
      if (d && d.showModal) { d.showModal(); if (b.dataset.open === 'd-aanvaarden') sigKlaar(); }
    });
  });
  document.querySelectorAll('[data-naar]').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      var d = a.closest('dialog'); if (d) d.close();
      var doel = document.querySelector(a.dataset.naar); if (doel) doel.scrollIntoView({behavior: 'smooth'});
    });
  });
  $('b-pdf').addEventListener('click', function(){ window.print(); });

  /* ter plaatse laten tekenen (vanuit het dashboard): meteen het aanvaardvenster openen */
  if (/[?&]teken=1/.test(location.search) && document.body.dataset.status === 'open') {
    var da = $('d-aanvaarden');
    if (da && da.showModal) setTimeout(function(){ da.showModal(); sigKlaar(); }, 300);
  }

  /* handtekening */
  var cv = $('a-sig'), ctx, getekend = false, bezig = false, vorige = null;
  function sigKlaar(){
    if (!cv) return;
    var r = cv.getBoundingClientRect(), dpr = Math.max(1, window.devicePixelRatio || 1);
    if (cv.width === Math.round(r.width * dpr)) return;
    cv.width = Math.round(r.width * dpr); cv.height = Math.round(r.height * dpr);
    ctx = cv.getContext('2d'); ctx.scale(dpr, dpr);
    ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#151515';
    getekend = false; cv.classList.add('leeg');
  }
  function punt(e){ var r = cv.getBoundingClientRect(); return {x: e.clientX - r.left, y: e.clientY - r.top}; }
  if (cv) {
    cv.addEventListener('pointerdown', function(e){ sigKlaar(); bezig = true; vorige = punt(e); cv.setPointerCapture(e.pointerId); ctx.beginPath(); ctx.arc(vorige.x, vorige.y, 1, 0, 7); ctx.fill(); });
    cv.addEventListener('pointermove', function(e){
      if (!bezig) return;
      var p = punt(e); ctx.beginPath(); ctx.moveTo(vorige.x, vorige.y); ctx.lineTo(p.x, p.y); ctx.stroke(); vorige = p;
      if (!getekend) { getekend = true; cv.classList.remove('leeg'); fout('a-fout', ''); }
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function(t){ cv.addEventListener(t, function(){ bezig = false; }); });
    $('sig-wis').addEventListener('click', function(){ ctx.clearRect(0, 0, cv.width, cv.height); getekend = false; cv.classList.add('leeg'); });
  }

  /* aanvaarden */
  var fa = $('f-aanvaarden');
  if (fa) fa.addEventListener('submit', function(e){
    if (e.submitter && e.submitter.value === 'sluit') return;
    e.preventDefault();
    var naam = $('a-naam').value.trim();
    if (naam.length < 2) return fout('a-fout', 'Vul je naam in.');
    if (!getekend) return fout('a-fout', 'Zet je handtekening in het vak.');
    if (!$('a-akkoord').checked) return fout('a-fout', 'Vink aan dat je akkoord gaat met de offerte en de algemene voorwaarden.');
    fout('a-fout', '');
    var knop = $('a-knop'); knop.disabled = true; knop.textContent = 'Bezig…';
    stuur({actie: 'aanvaarden', naam: naam, akkoord: true, handtekening: cv.toDataURL('image/png')}).then(function(j){
      if (j.ok) { try { window.ssTrack && window.ssTrack('aanvaard'); } catch (x) {} location.reload(); return; }
      knop.disabled = false; knop.textContent = 'Aanvaarden met betalingsverplichting';
      fout('a-fout', j.fout || 'Er ging iets mis. Probeer het opnieuw of bel 0468 43 00 53.');
    });
  });

  /* weigeren */
  var fw = $('f-weigeren');
  if (fw) fw.addEventListener('submit', function(e){
    if (e.submitter && e.submitter.value === 'sluit') return;
    e.preventDefault();
    stuur({actie: 'weigeren', reden: $('w-reden').value, toelichting: $('w-toel').value}).then(function(j){
      if (j.ok) { location.reload(); return; }
      fout('w-fout', j.fout || 'Er ging iets mis. Probeer het opnieuw.');
    });
  });

  /* vraag */
  $('f-vraag').addEventListener('submit', function(e){
    if (e.submitter && e.submitter.value === 'sluit') return;
    e.preventDefault();
    var t = $('v-tekst').value.trim();
    if (t.length < 3) return fout('v-fout', 'Typ je vraag.');
    stuur({actie: 'vraag', vraag: t}).then(function(j){
      if (j.ok) { $('d-vraag').close(); $('v-tekst').value = ''; fout('v-fout', ''); toast('Bedankt! We antwoorden zo snel mogelijk per e-mail.'); return; }
      fout('v-fout', j.fout || 'Versturen is mislukt. Mail ons op info@ssprojects.be.');
    });
  });

  /* herroepen */
  var fh = $('f-herroepen');
  if (fh) fh.addEventListener('submit', function(e){
    if (e.submitter && e.submitter.value === 'sluit') return;
    e.preventDefault();
    stuur({actie: 'herroepen', naam: $('h-naam').value.trim()}).then(function(j){
      if (j.ok) { location.reload(); return; }
      fout('h-fout', j.fout || 'Er ging iets mis. Mail ons op info@ssprojects.be.');
    });
  });
})();
