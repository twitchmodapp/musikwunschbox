(function(){
 'use strict';
 function render(el){
  const endpoint=el.getAttribute('data-tmc-endpoint');
  if(!endpoint){el.textContent='Musikwünsche können nicht geladen werden (API-Adresse fehlt).';return;}
  fetch(endpoint,{credentials:'same-origin'}).then(function(response){
   if(!response.ok)throw new Error('HTTP '+response.status);
   return response.json();
  }).then(function(rows){
   if(!Array.isArray(rows))throw new Error('Ungültige API-Antwort');
   el.replaceChildren();
   const current=rows.filter(s=>s.status==='waiting'||s.status==='playing');
   const played=rows.filter(s=>s.status==='played');
   function section(heading,items,empty){
    const block=document.createElement('section');block.className='tmc-section';
    const h=document.createElement('h3');h.textContent=heading;block.append(h);
    if(!items.length){const p=document.createElement('p');p.textContent=empty;block.append(p);}
    items.forEach(function(s,index){
    const item=document.createElement('div');item.className='tmc-item';
    const cover=document.createElement('div');cover.className='tmc-cover';
    if(s.cover_url){const img=document.createElement('img');img.src=s.cover_url;img.alt='Cover zu '+(s.artist||'')+' – '+(s.title||'');img.loading='lazy';img.referrerPolicy='no-referrer';img.onerror=function(){cover.replaceChildren('♫');cover.classList.add('tmc-placeholder');};cover.append(img);}
    else {cover.textContent='♫';cover.classList.add('tmc-placeholder');}
    const detail=document.createElement('div');detail.className='tmc-detail';
    const title=document.createElement('strong');title.className='tmc-title';title.textContent=s.title||'';
    const artist=document.createElement('div');artist.className='tmc-artist';artist.textContent=s.artist||'';
    const user=document.createElement('small');user.className='tmc-user';user.textContent='Gewünscht von '+(s.username||'Unbekannt');
    const badge=document.createElement('span');badge.className='tmc-status '+(s.status==='playing'?'tmc-playing':'tmc-waiting');badge.textContent=s.status==='played'?'✓ Gespielt':(s.status==='playing'?'Wird gespielt':'Wartet');if(s.status==='played')badge.classList.add('tmc-played');
    const number=document.createElement('span');number.className='tmc-number';number.textContent=(index+1)+'.';
    detail.append(title,artist,user,badge);item.append(number,cover,detail);block.append(item);
    });el.append(block);}
   section('Aktuelle Musikwünsche',current,'Aktuell sind keine Musikwünsche offen.');
   section('Gespielte Musikwünsche',played,'In den letzten 12 Stunden wurde noch kein Titel als gespielt markiert.');
  }).catch(function(error){
   el.textContent='Musikwünsche konnten nicht geladen werden. Bitte Seite später erneut laden.';
   console.error('Twitch Musik Wunschliste:',error);
  });
 }
 function updateStatus(wrapper){
  const url=wrapper.getAttribute('data-tmc-status-endpoint');
  const label=wrapper.querySelector('.tmc-public-state');
  if(!url||!label)return;
  fetch(url,{credentials:'same-origin',cache:'no-store'}).then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();}).then(function(data){
   if(typeof data.enabled!=='boolean')throw new Error('Ungültiger Status');
   const next=data.enabled?'Musikwünsche sind geöffnet – du kannst aktuell Musikwünsche einreichen.':'Musikwünsche sind geschlossen – momentan werden keine neuen Musikwünsche angenommen.';
   if(label.textContent!==next)label.textContent=next;
   label.classList.toggle('tmc-public-open',data.enabled);
   label.classList.toggle('tmc-public-closed',!data.enabled);
  }).catch(function(){
   const next='Der aktuelle Status der Musikwünsche ist momentan nicht verfügbar.';
   if(label.textContent!==next)label.textContent=next;
   label.classList.remove('tmc-public-open','tmc-public-closed');
  });
 }
 function init(){document.querySelectorAll('.tmc-public[data-tmc-status-endpoint]').forEach(function(wrapper){updateStatus(wrapper);setInterval(function(){updateStatus(wrapper);},10000);});document.querySelectorAll('.tmc-list[data-tmc-endpoint]').forEach(function(el){render(el);setInterval(function(){render(el);},10000);});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
