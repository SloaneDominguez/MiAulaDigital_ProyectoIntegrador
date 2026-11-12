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
 const master=[...select.options].map(o=>({value:o.value,text:o.text,key:o.dataset.grupo??'',selected:o.selected}));
 const groupSel=document.querySelector(`[data-options-group="${select.id}"]`);
 const textIn=document.querySelector(`[data-options-text="${select.id}"]`);
 const info=document.querySelector(`[data-options-count="${select.id}"]`);
 const rebuild=()=>{
  const g=groupSel?.value??'';const t=norm(textIn?.value);const current=select.value;
  select.innerHTML='';let n=0;
  for(const o of master){
   const placeholder=o.value==='';
   if(!placeholder&&((g!==''&&o.key!==g)||(t&&!norm(o.text).includes(t))))continue;
   const el=new Option(o.text,o.value);if(o.key)el.dataset.grupo=o.key;select.add(el);if(!placeholder)n++;
  }
  if(current!==''&&[...select.options].some(o=>o.value===current))select.value=current;
  else if(n===1&&t)select.selectedIndex=[...select.options].findIndex(o=>o.value!=='');
  else select.value='';
  if(info)info.textContent=n?`${n} ${n===1?'opción':'opciones'}`:'Sin coincidencias';
 };
 groupSel?.addEventListener('change',rebuild);textIn?.addEventListener('input',rebuild);
 select.form?.addEventListener('reset',()=>setTimeout(()=>{if(groupSel)groupSel.value='';if(textIn)textIn.value='';rebuild();}));
 rebuild();
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
  <a class="button primary" href="${esc(invite.mailto)}">Enviar por correo electrónico</a>
  <button type="button" class="button" data-copy-invite>Copiar mensaje</button>
 </div>
 <label class="field"><span>Mensaje de la invitación</span><textarea readonly rows="7">${esc(invite.texto)}</textarea></label>
 <div class="form-footer"><button type="button" class="button" data-x>Listo</button></div>`;
 dlg.querySelectorAll('[data-x]').forEach(b=>b.addEventListener('click',()=>{dlg.close();location.reload();}));
 dlg.querySelector('[data-copy-invite]').addEventListener('click',async e=>{try{await navigator.clipboard.writeText(invite.texto);e.target.textContent='¡Copiado!';}catch{dlg.querySelector('textarea').select();e.target.textContent='Selecciona y copia';}});
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
