'use strict';
/*
 * Listas de Mi Aula Digital:
 *  1. Filtros automáticos (se filtra mientras escribes, sin botón "Buscar").
 *  2. Tablas que en el celular se convierten en tarjetas (nada queda cortado).
 *  3. Listas desplegables con buscador (por grupo y después por nombre).
 *  4. Ventana para enviar invitaciones a padres/tutores.
 *  5. Selector para mover un grupo completo de alumnos.
 */
(()=>{
const norm=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase().trim();

/* ---------- 1. Filtros automáticos ---------- */
function setupListFilter(box){
 const target=document.getElementById(box.dataset.listFilter);if(!target)return;
 const q=box.querySelector('[data-q]');const selects=[...box.querySelectorAll('select[data-key]')];const count=box.querySelector('[data-count]');
 const items=()=>target.matches('table')?[...target.tBodies[0]?.rows??[]]:[...target.querySelectorAll('[data-item]')];
 let empty=document.createElement('div');empty.className='empty lf-empty';empty.hidden=true;empty.innerHTML='<strong>Sin resultados</strong>Prueba con otro nombre o quita algún filtro.';
 (target.closest('.table-wrap')||target).after(empty);
 const total=items().length;
 const apply=()=>{
  const term=norm(q?.value);let shown=0;
  for(const row of items()){
   let ok=!term||norm(row.textContent).includes(term);
   if(ok)for(const s of selects){if(s.value!==''&&(row.getAttribute('data-f-'+s.dataset.key)??'')!==s.value){ok=false;break;}}
   row.hidden=!ok;if(ok)shown++;
  }
  empty.hidden=shown>0||total===0;
  if(count)count.textContent=(term||selects.some(s=>s.value!==''))?`${shown} de ${total}`:`${total} en total`;
  try{sessionStorage.setItem('mad-lf-'+box.dataset.listFilter,JSON.stringify({q:q?.value||'',s:selects.map(s=>s.value)}));}catch{}
 };
 try{const saved=JSON.parse(sessionStorage.getItem('mad-lf-'+box.dataset.listFilter)||'null');if(saved&&box.dataset.remember!=='false'){if(q)q.value=saved.q||'';selects.forEach((s,i)=>{if(saved.s?.[i]!==undefined&&[...s.options].some(o=>o.value===saved.s[i]))s.value=saved.s[i];});}}catch{}
 q?.addEventListener('input',apply);selects.forEach(s=>s.addEventListener('change',apply));
 box.querySelector('[data-clear]')?.addEventListener('click',()=>{if(q)q.value='';selects.forEach(s=>s.value='');apply();q?.focus();});
 apply();
}

/* ---------- 2. Tablas en el celular ---------- */
function labelTable(table){
 if(table.classList.contains('grade-table')||table.dataset.labeled)return;
 const heads=[...(table.tHead?.rows[0]?.cells??[])].map(th=>th.textContent.trim());
 if(!heads.length)return;
 table.dataset.labeled='1';table.classList.add('rtable');
 for(const row of table.tBodies[0]?.rows??[])[...row.cells].forEach((td,i)=>{if(!td.hasAttribute('data-label')&&heads[i])td.setAttribute('data-label',heads[i]);});
}

/* ---------- 3. Listas desplegables con buscador ---------- */
function setupOptionFilter(select){
 const master=[...select.options].map(o=>({value:o.value,text:o.text,key:o.dataset.grupo??''}));
 const groupSel=document.querySelector(`[data-options-group="${select.id}"]`);
 const textIn=document.querySelector(`[data-options-text="${select.id}"]`);
 const info=document.querySelector(`[data-options-count="${select.id}"]`);
 const picker=select.closest('[data-student-picker]');
 const results=picker?.querySelector('[data-picker-results]');
 const chosen=picker?.querySelector('[data-picker-chosen]'),filters=picker?.querySelector('[data-picker-filters]');
 const LIMIT=40;
 const split=text=>{const i=text.lastIndexOf(' · ');return i>0?[text.slice(0,i),text.slice(i+3)]:[text,''];};
 const matches=()=>{const g=groupSel?.value??'';const t=norm(textIn?.value);return master.filter(o=>o.value!==''&&!(g!==''&&o.key!==g)&&!(t&&!norm(o.text).includes(t)));};
 // Mantiene el <select> con las opciones filtradas (es el que se envía con el formulario).
 const rebuild=()=>{
  const current=select.value;const list=matches();
  select.innerHTML='';
  for(const o of [master.find(m=>m.value==='')].filter(Boolean).concat(list)){const el=new Option(o.text,o.value);if(o.key)el.dataset.grupo=o.key;select.add(el);}
  select.value=list.some(o=>o.value===current)?current:'';
  render(list);
 };
 const render=list=>{
  if(info){
   info.classList.remove('is-error');
   info.textContent=!list.length?'Sin coincidencias. Revisa el nombre o cambia el grupo.':list.length>LIMIT?`Mostrando ${LIMIT} de ${list.length}. Escribe para acotar.`:`${list.length} ${list.length===1?'resultado':'resultados'}`;
  }
  if(!results)return;
  results.innerHTML='';
  for(const o of list.slice(0,LIMIT)){
   const [name,group]=split(o.text);
   const b=document.createElement('button');b.type='button';b.className='picker-item';b.setAttribute('role','option');b.dataset.value=o.value;
   b.setAttribute('aria-selected',String(o.value===select.value));
   b.innerHTML='<span class="picker-avatar" aria-hidden="true"></span><span class="picker-item-name"></span><span class="picker-chip"></span>';
   b.children[0].textContent=name.trim().charAt(0).toUpperCase();b.children[1].textContent=name;b.children[2].textContent=group;if(!group)b.children[2].remove();
   results.append(b);
  }
  results.hidden=!list.length;
 };
 const collapse=on=>{
  if(!chosen||!filters)return;
  const opt=select.selectedOptions[0];const has=on&&select.value!==''&&opt;
  if(has){const [name,group]=split(opt.text);chosen.querySelector('[data-picker-name]').textContent=name;chosen.querySelector('[data-picker-initial]').textContent=name.trim().charAt(0).toUpperCase();const chip=chosen.querySelector('[data-picker-group]');chip.textContent=group;chip.hidden=!group;}
  chosen.hidden=!has;filters.hidden=!!has;picker.classList.toggle('is-chosen',!!has);picker.classList.remove('has-error');
 };
 const choose=value=>{select.value=value;select.dispatchEvent(new Event('change',{bubbles:true}));chosen?.querySelector('[data-picker-change]')?.focus();};
 const clearError=()=>picker?.classList.remove('has-error');
 groupSel?.addEventListener('change',()=>{clearError();rebuild();});textIn?.addEventListener('input',()=>{clearError();rebuild();});
 if(picker&&results){
  picker.classList.add('is-enhanced');
  results.addEventListener('click',e=>{const it=e.target.closest('.picker-item');if(it)choose(it.dataset.value);});
  // Teclado: flecha abajo desde el buscador entra a la lista; flechas para moverse; Enter elige.
  textIn?.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();results.querySelector('.picker-item')?.focus();}else if(e.key==='Enter'){e.preventDefault();const items=results.querySelectorAll('.picker-item');if(items.length===1)choose(items[0].dataset.value);}});
  results.addEventListener('keydown',e=>{const items=[...results.querySelectorAll('.picker-item')];const i=items.indexOf(document.activeElement);if(e.key==='ArrowDown'){e.preventDefault();items[Math.min(i+1,items.length-1)]?.focus();}else if(e.key==='ArrowUp'){e.preventDefault();(i<=0?textIn:items[i-1])?.focus();}});
  // Sin alumno elegido: mensaje claro en lugar de la burbuja del navegador.
  select.addEventListener('invalid',e=>{e.preventDefault();picker.classList.add('has-error');if(info){info.textContent='Elige un alumno de la lista para continuar.';info.classList.add('is-error');}textIn?.focus();});
 }
 select.addEventListener('change',()=>collapse(true));
 chosen?.querySelector('[data-picker-change]')?.addEventListener('click',()=>{collapse(false);if(groupSel)groupSel.value='';if(textIn)textIn.value='';select.value='';rebuild();textIn?.focus();});
 // Si el modal se abre con un alumno ya elegido (botón de una fila), se muestra contraído.
 const dialog=select.closest('dialog');
 if(dialog)document.querySelectorAll(`[data-open="${dialog.id}"]`).forEach(b=>b.addEventListener('click',()=>setTimeout(()=>{if(textIn)textIn.value='';if(groupSel)groupSel.value='';const v=select.value;rebuild();select.value=v;collapse(select.value!=='');if(select.value==='')textIn?.focus();},0)));
 // No se limpia select.value aquí: el formulario ya lo reinicia y el botón que abre el modal puede traer un alumno elegido.
 select.form?.addEventListener('reset',()=>{
  // Se restaura la lista completa de inmediato (antes de que el botón que abre el modal
  // asigne el alumno de su fila); si quedara filtrada, ese alumno no existiría en la lista.
  if(groupSel)groupSel.value='';if(textIn)textIn.value='';
  select.innerHTML='';for(const o of master){const el=new Option(o.text,o.value);if(o.key)el.dataset.grupo=o.key;select.add(el);}
  setTimeout(()=>{rebuild();collapse(select.value!=='');});
 });
 rebuild();
 if(select.value!=='')collapse(true);
}

/* ---------- 4. Invitaciones ---------- */
function esc(s){const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;}
function showInvite(invite){
 let dlg=document.getElementById('invite-dialog');
 if(!dlg){dlg=document.createElement('dialog');dlg.id='invite-dialog';dlg.className='dialog invite-dialog';document.body.append(dlg);}
 dlg.innerHTML=`<div class="dialog-head"><h2>Enviar invitación</h2><button type="button" class="icon-button" data-x aria-label="Cerrar">✕</button></div>
 <p class="help">La cuenta de <strong>${esc(invite.nombre)}</strong> está lista. Envíale este mensaje para que cree su contraseña. El enlace funciona una sola vez y vence el <strong>${esc(invite.vence)}</strong>.</p>
 <dl class="invite-data"><div><dt>Usuario</dt><dd>${esc(invite.usuario)}</dd></div><div><dt>Correo</dt><dd>${esc(invite.correo)}</dd></div></dl>
 <div class="invite-actions">
  <button type="button" class="button primary" data-send-invite>Enviar por correo electrónico</button>
  <button type="button" class="button" data-copy-invite>Copiar mensaje</button>
 </div>
 <label class="field"><span>Mensaje de la invitación</span><textarea readonly rows="7">${esc(invite.texto)}</textarea></label>
 <div class="form-footer"><button type="button" class="button" data-x>Listo</button></div>`;
 dlg.querySelectorAll('[data-x]').forEach(b=>b.addEventListener('click',()=>{dlg.close();location.reload();}));
 // Envío real desde el servidor (Gmail SMTP). Antes era un enlace mailto: que solo abría el programa de correo.
 const sendBtn=dlg.querySelector('[data-send-invite]');
 const toastMsg=(text,tone)=>{if(typeof notify==='function')notify(text,tone);else alert(text);};
 sendBtn.addEventListener('click',async()=>{
  const csrf=document.querySelector('input[name="csrf"]')?.value||'';
  const body=new FormData();body.append('csrf',csrf);body.append('action','invite_email');body.append('usuario',String(invite.id||''));body.append('codigo',String(invite.codigo||''));
  sendBtn.disabled=true;sendBtn.classList.add('is-loading');sendBtn.innerHTML='<span class="btn-spinner" aria-hidden="true"></span>Enviando…';
  try{
   const r=await fetch(document.querySelector('form[action$="process/action.php"]')?.getAttribute('action')||'../process/action.php',{method:'POST',body,headers:{'Accept':'application/json'},credentials:'same-origin'});
   const j=await r.json().catch(()=>({ok:false,message:'El servidor no respondió correctamente.'}));
   if(!r.ok||!j.ok)throw new Error(j.message||'No se pudo enviar el correo.');
   toastMsg(j.message,j.sent?'success':'info');
   sendBtn.classList.remove('is-loading');
   if(j.sent){sendBtn.classList.add('is-done');sendBtn.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Correo enviado';}
   else{sendBtn.disabled=false;sendBtn.textContent='Enviar por correo electrónico';}
  }catch(e){
   toastMsg((e instanceof TypeError?'No se pudo conectar. Revisa tu conexión.':e.message)+' Puedes usar “Copiar mensaje”.','error');
   sendBtn.classList.remove('is-loading');sendBtn.disabled=false;sendBtn.textContent='Reintentar envío';
  }
 });
 dlg.querySelector('[data-copy-invite]').addEventListener('click',async e=>{try{await navigator.clipboard.writeText(invite.texto);e.target.textContent='¡Copiado!';if(typeof notify==='function')notify('Mensaje copiado. Pégalo en WhatsApp o en tu correo.','success');}catch{dlg.querySelector('textarea').select();e.target.textContent='Selecciona y copia';}});
 document.querySelectorAll('dialog[open]').forEach(d=>{if(d!==dlg)d.close();});
 dlg.showModal();
}
window.madShowInvite=showInvite;

/* ---------- 5. Mover grupo completo ---------- */
function setupMassMove(form){
 let data;try{data=JSON.parse(form.querySelector('script[type="application/json"]').textContent);}catch{return;}
 const fc=form.elements.namedItem('ciclo_origen'),fg=form.elements.namedItem('grupo_origen'),tc=form.elements.namedItem('ciclo_destino'),tg=form.elements.namedItem('grupo_destino');
 const list=form.querySelector('[data-mm-list]'),all=form.querySelector('[data-mm-all]'),summary=form.querySelector('[data-mm-summary]');
 const groupById=Object.fromEntries(data.groups.map(g=>[String(g.id),g]));
 const suggest=()=>{const g=groupById[fg.value];if(!g)return;const next=data.groups.find(x=>x.grado===g.grado+1&&x.letra===g.letra)||data.groups.find(x=>x.grado===g.grado+1);if(next)tg.value=String(next.id);};
 const render=()=>{
  const students=data.enroll[`${fc.value}:${fg.value}`]||[];
  list.innerHTML=students.length?students.map(([id,name])=>`<label class="mm-item"><input type="checkbox" name="alumnos[]" value="${id}" checked> <span>${esc(name)}</span></label>`).join(''):'<p class="help">No hay alumnos activos en ese grupo y ciclo.</p>';
  if(all){all.checked=students.length>0;all.disabled=!students.length;}
  update();
 };
 const update=()=>{const n=list.querySelectorAll('input:checked').length;const g=groupById[tg.value];summary.textContent=n?`Se moverán ${n} ${n===1?'alumno':'alumnos'} a ${g?g.label:'—'} en el ciclo ${tc.selectedOptions[0]?.text??''}.`:'Selecciona al menos un alumno.';};
 fc.addEventListener('change',render);fg.addEventListener('change',()=>{suggest();render();});tg.addEventListener('change',update);tc.addEventListener('change',update);
 list.addEventListener('change',update);all?.addEventListener('change',()=>{list.querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=all.checked);update();});
 suggest();render();
}

function init(){
 document.querySelectorAll('.table-wrap table').forEach(labelTable);
 document.querySelectorAll('[data-list-filter]').forEach(setupListFilter);
 document.querySelectorAll('select[data-options-filterable]').forEach(setupOptionFilter);
 document.querySelectorAll('form[data-mass-move]').forEach(setupMassMove);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
