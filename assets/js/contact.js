/* Super Schoon – contactformulier */
'use strict';
(function(){
  const START=Date.now();
  const $=id=>document.getElementById(id);
  const form=$('contactform'); if(!form) return;
  const CHECKS={
    naam:()=>$('c-naam').value.trim().length>1?'':'Vul je naam in.',
    email:()=>/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test($('c-email').value.trim())?'':'Vul een geldig e-mailadres in, bv. naam@voorbeeld.be.',
    bericht:()=>$('c-bericht').value.trim().length>4?'':'Schrijf een kort bericht.',
    ok:()=>$('c-ok').checked?'':'Vink dit aan, zodat we je mogen antwoorden.'
  };
  const el={naam:'c-naam',email:'c-email',bericht:'c-bericht',ok:'c-ok'};
  function check(k){const m=CHECKS[k](); $('ce-'+k).textContent=m; if(k!=='ok') $(el[k]).setAttribute('aria-invalid',m?'true':'false'); return !m;}
  form.addEventListener('submit',async e=>{
    e.preventDefault();
    const fout=Object.keys(CHECKS).filter(k=>!check(k));
    if(fout.length){ $(el[fout[0]]).focus(); return; }
    const msg=$('c-msg'), knop=form.querySelector('button[type=submit]');
    knop.disabled=true; knop.textContent='Bezig met versturen…'; msg.hidden=true;
    let ok=false;
    try{
      const r=await fetch('verstuur.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        type:'contact',naam:$('c-naam').value.trim(),email:$('c-email').value.trim(),tel:$('c-tel').value.trim(),
        bericht:$('c-bericht').value.trim(),website:$('c-website').value,t:START})});
      const j=await r.json().catch(()=>({})); ok=r.ok&&j.ok;
    }catch(err){}
    knop.disabled=false; knop.textContent='Bericht versturen'; msg.hidden=false;
    if(ok){ msg.className='formmsg full ok'; msg.textContent='Bedankt, je bericht is verstuurd. We antwoorden zo snel mogelijk.'; form.reset(); }
    else { msg.className='formmsg full err'; msg.textContent='Je bericht kon niet verstuurd worden. Mail ons op info@ssprojects.be of bel 0468 43 00 53.'; }
  });
})();
