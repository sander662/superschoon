/* Super Schoon – bezoekersstatistieken zonder cookies. Er wordt niets op je toestel opgeslagen. */
(function(){
  'use strict';
  if (navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl) { window.ssTrack = function(){}; return; }
  function stuur(data){
    data.p = location.pathname;
    var body = JSON.stringify(data);
    try {
      if (navigator.sendBeacon && navigator.sendBeacon('/t.php', new Blob([body], {type: 'application/json'}))) return;
    } catch (e) {}
    try { fetch('/t.php', {method: 'POST', body: body, headers: {'Content-Type': 'application/json'}, keepalive: true, credentials: 'same-origin'}); } catch (e) {}
  }
  window.ssTrack = function(e, extra){ var d = extra || {}; d.e = e; stuur(d); };

  var q = new URLSearchParams(location.search);
  stuur({e: 'pv', r: document.referrer || '', u: q.get('utm_source') || '', c: q.get('utm_campaign') || ''});

  document.addEventListener('click', function(ev){
    var a = ev.target.closest && ev.target.closest('a[href]');
    if (!a) return;
    var href = a.getAttribute('href') || '', k;
    if (/^tel:/i.test(href)) k = 'tel';
    else if (/^mailto:/i.test(href)) k = 'mail';
    else if (/wa\.me|whatsapp/i.test(href)) k = 'whatsapp';
    else if (/(^|\.)(facebook|instagram)\.com$/i.test(a.host || '')) k = 'social';
    else if (a.host && a.host !== location.host) k = 'extern';
    else if (a.classList.contains('btn')) k = 'knop';
    else k = 'intern';
    var label = (a.textContent || a.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim().slice(0, 60);
    var doel = k === 'extern' ? a.href : href.split('#')[0];
    stuur({e: 'klik', k: k, x: doel.slice(0, 160), l: label});
  }, true);
})();
