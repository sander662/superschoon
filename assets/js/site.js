/* Super Schoon – menu op gsm */
(function(){
  var btn=document.querySelector('.menu-btn'), nav=document.getElementById('nav');
  if(!btn||!nav) return;
  btn.addEventListener('click',function(){
    var open=nav.classList.toggle('open');
    btn.setAttribute('aria-expanded',String(open));
    btn.setAttribute('aria-label',open?'Menu sluiten':'Menu openen');
  });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&nav.classList.contains('open')){ nav.classList.remove('open'); btn.setAttribute('aria-expanded','false'); btn.focus(); } });
})();
