'use strict';
const grid=document.querySelector('#grade-grid');
if(grid){
 const cells=[...grid.querySelectorAll('.grade-cell')],status=document.querySelector('#grade-status'),key='mad-draft-'+grid.dataset.draftKey;
 let saving=false,dirty=false;
 const normalize=v=>String(v??'').trim().replace(',','.');
 if(status){status.setAttribute('aria-live','polite');status.setAttribute('aria-atomic','true');}
 const valid=v=>/^(10(?:\.0)?|[5-9](?:\.[0-9])?)$/.test(normalize(v));
 const changed=()=>cells.filter(c=>normalize(c.value)!==normalize(c.dataset.original));
 function update(){const list=changed();dirty=list.length>0;for(const c of cells)c.classList.toggle('changed',list.includes(c));if(status)status.textContent=dirty?`${list.length} cambios pendientes.`:'Sin cambios pendientes.';try{if(dirty)sessionStorage.setItem(key,JSON.stringify(list.map(c=>({alumno:c.dataset.student,campo:c.dataset.field,revision:c.dataset.revision,valor:c.value}))));else sessionStorage.removeItem(key);}catch{if(status)status.textContent+=' El navegador no permite guardar el borrador.';}}
 for(const c of cells){c.addEventListener('input',update);c.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();cells[cells.indexOf(c)+(e.shiftKey?-1:1)]?.focus();}});c.addEventListener('paste',e=>{if(saving){e.preventDefault();return;}const text=e.clipboardData?.getData('text')||'';if(!/[\t\n]/.test(text))return;e.preventDefault();const start=c.closest('tr'),tableRows=[...grid.querySelectorAll('tbody tr')],rowIndex=tableRows.indexOf(start),colIndex=[...start.querySelectorAll('td')].indexOf(c.closest('td'));const pasted=text.trimEnd().split(/\r?\n/).map(r=>r.split('\t'));const targets=[];for(let r=0;r<pasted.length;r++)for(let col=0;col<pasted[r].length;col++){const target=tableRows[rowIndex+r]?.children[colIndex+col]?.querySelector('.grade-cell');if(!target){if(status)status.textContent='El bloque excede la tabla o incluye campos sin permiso. No se pegó ningún dato.';return;}targets.push([target,normalize(pasted[r][col])]);}if(targets.some(([,value])=>value!==''&&!valid(value))){if(status)status.textContent='El bloque contiene una nota inválida. Usa valores de 5 a 10 con un decimal. No se pegó ningún dato.';return;}for(const [target,value]of targets)target.value=value;update();});}
 document.querySelector('#draft-restore')?.addEventListener('click',()=>{if(saving)return;try{const draft=JSON.parse(sessionStorage.getItem(key)||'[]');if(!draft.length){status.textContent='No hay borrador en esta pestaña.';return;}const pairs=draft.map(d=>[d,cells.find(c=>c.dataset.student===String(d.alumno)&&c.dataset.field===String(d.campo))]);if(pairs.some(([d,c])=>!c||c.dataset.revision!==String(d.revision))){status.textContent='El borrador corresponde a notas que ya cambiaron. Revísalo antes de volver a capturar; no se aplicó.';return;}for(const[d,c]of pairs)c.value=d.valor;update();}catch{status.textContent='No se pudo recuperar el borrador.';}});
 document.querySelector('#draft-discard')?.addEventListener('click',async()=>{if(saving)return;const approved=window.madConfirm?await window.madConfirm('¿Descartar los cambios de esta página y su borrador?',{tone:'warning',confirmLabel:'Descartar cambios'}):false;if(approved){for(const c of cells)c.value=c.dataset.original;update();}});
 try{if(sessionStorage.getItem(key)&&status)status.textContent='Hay un borrador de esta página. Pulsa Recuperar borrador para revisarlo.';}catch{}
 window.addEventListener('beforeunload',e=>{if(dirty||saving){e.preventDefault();e.returnValue='';}});
 grid.addEventListener('submit',async e=>{
  e.preventDefault();if(saving||!status)return;
  const list=changed().filter(c=>normalize(c.value)!=='');
  if(!list.length){status.textContent='No hay notas nuevas para guardar. Vaciar una celda no elimina su nota.';return;}
  const bad=list.find(c=>!valid(c.value));
  if(bad){bad.focus();status.textContent='Revisa la celda seleccionada: usa notas de 5 a 10 con un decimal.';return;}
  const changes=list.map(c=>({alumno:Number(c.dataset.student),campo:Number(c.dataset.field),revision:Number(c.dataset.revision),valor:normalize(c.value)}));
  const data=new FormData(grid);data.set('action','grade_batch');data.set('cambios',JSON.stringify(changes));
  const controls=[...grid.querySelectorAll('button'),...document.querySelectorAll('[data-filter]')];
  const previous=controls.map(b=>b.disabled),readonly=cells.map(c=>c.readOnly);
  update();saving=true;controls.forEach(b=>b.disabled=true);cells.forEach(c=>c.readOnly=true);
  grid.setAttribute('aria-busy','true');status.textContent='Guardando calificaciones… Espera un momento.';
  try{
   const r=await fetch(grid.getAttribute('action'),{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json'}});
   const type=(r.headers.get('content-type')||'').toLowerCase();
   if(!type.includes('application/json'))throw Error(r.redirected?'Tu sesión pudo haber vencido. Vuelve a iniciar sesión antes de intentar guardar.':'El servidor no confirmó el guardado. Revisa las notas antes de volver a intentarlo.');
   let out;try{out=await r.json();}catch{throw Error('La respuesta del servidor está incompleta. No se pudo confirmar el guardado.');}
   if(!r.ok||!out.ok)throw Error(out.message||'No se pudo guardar.');
   if(!Array.isArray(out.actualizadas)||out.actualizadas.length!==changes.length)throw Error('No se pudo confirmar todo el lote. Revisa las notas antes de reintentar.');
   const seen=new Set();
   const verified=out.actualizadas.map(n=>{
    const original=changes.find(c=>c.alumno===Number(n.alumno)&&c.campo===Number(n.campo));
    const id=String(n.alumno)+':'+String(n.campo);
    if(!original||seen.has(id)||!Number.isInteger(Number(n.revision))||Number(n.revision)<original.revision||!valid(String(n.valor))||Number(n.valor)!==Number(original.valor))throw Error('La confirmación del guardado no coincide con las notas enviadas. Recarga y revisa antes de continuar.');
    seen.add(id);return n;
   });
   for(const n of verified){const c=cells.find(c=>Number(c.dataset.student)===Number(n.alumno)&&Number(c.dataset.field)===Number(n.campo));c.dataset.revision=String(n.revision);c.value=Number(n.valor).toFixed(1);c.dataset.original=c.value;}
   for(const c of cells)if(!normalize(c.value))c.value=c.dataset.original;
   update();status.textContent='Calificaciones guardadas. Actualizando promedios…';dirty=false;saving=false;
   try{sessionStorage.setItem('mad-notice','Calificaciones guardadas correctamente.');}catch{}
   location.reload();
  }catch(err){
   const message=err instanceof TypeError?'Se interrumpió la conexión. No se pudo confirmar el guardado; revisa las notas antes de reintentar.':err.message;
   status.textContent=message+' Tus cambios siguen en esta pantalla.';
  }finally{
   saving=false;controls.forEach((b,i)=>b.disabled=previous[i]);cells.forEach((c,i)=>c.readOnly=readonly[i]);grid.setAttribute('aria-busy','false');
  }
 });
}
