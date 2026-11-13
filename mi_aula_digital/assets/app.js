'use strict';
let toast=document.querySelector('#toast');
if(!toast){toast=document.createElement('div');toast.id='toast';toast.className='toast';toast.hidden=true;toast.setAttribute('role','status');toast.setAttribute('aria-live','polite');document.body.append(toast);}
// Notificación flotante. tone: 'success' | 'error' | 'info' (si no se indica, se deduce del texto).
const TOAST_ICONS={success:'<path d="m5 12 4 4L19 6"/>',error:'<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01"/>',info:'<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>'};
let toastTimer=null;
function notify(message,tone){
 if(!toast)return;
 const kind=tone||(/no se pudo|no pudimos|error|inválid|no puede|no tienes|demasiados|ya fue|venci|incorrect|revisa |no está disponible/i.test(message)?'error':'success');
 toast.className='toast toast--'+kind;
 toast.setAttribute('role',kind==='error'?'alert':'status');
 toast.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true">'+(TOAST_ICONS[kind]||TOAST_ICONS.info)+'</svg><span></span><button type="button" class="toast-close" aria-label="Cerrar notificación"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button>';
 toast.querySelector('span').textContent=message;
 const hide=()=>{toast.hidden=true;try{if(toast.matches(':popover-open'))toast.hidePopover();}catch{}};
 toast.querySelector('.toast-close').addEventListener('click',()=>{hide();clearTimeout(toastTimer);});
 toast.hidden=false;clearTimeout(toastTimer);
 // Capa superior: se ve arriba aunque haya una ventana (dialog) abierta.
 if(typeof toast.showPopover==='function'){toast.setAttribute('popover','manual');try{if(toast.matches(':popover-open'))toast.hidePopover();toast.showPopover();}catch{}}
 toast.classList.remove('toast--in');void toast.offsetWidth;toast.classList.add('toast--in');
 toastTimer=setTimeout(hide,kind==='error'?8000:4500);
}

// Si el logo subido no carga (archivo borrado o no subido al hosting), se muestra la casita
// predeterminada en lugar de un cuadro vacío. Revisa también imágenes que fallaron antes de este script.
(()=>{const house='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-6 9 6v11H3Z"/><path d="M9 20v-7h6v7M7 10h1m8 0h1"/></svg>';
 const fallback=img=>{const mark=img.closest('.brand-mark');if(!mark)return;mark.classList.remove('has-logo');mark.innerHTML=house;};
 for(const img of document.querySelectorAll('img[data-logo-fallback]')){if(img.complete&&img.naturalWidth===0)fallback(img);else img.addEventListener('error',()=>fallback(img),{once:true});}})();

// Confirmaciones visuales del sistema. Sustituye los confirm() nativos del navegador
// por una ventana centrada con fondo desenfocado y diseño consistente.
let madConfirmDialog=null,madConfirmBusy=false;
function ensureMadConfirmDialog(){
 if(madConfirmDialog)return madConfirmDialog;
 const dialog=document.createElement('dialog');
 dialog.className='mad-confirm-dialog';
 dialog.setAttribute('aria-labelledby','mad-confirm-title');
 dialog.setAttribute('aria-describedby','mad-confirm-message');
 dialog.innerHTML=`<div class="mad-confirm-card"><div class="mad-confirm-icon" aria-hidden="true">!</div><div class="mad-confirm-copy"><h2 id="mad-confirm-title">Confirmar acción</h2><p id="mad-confirm-message"></p></div><div class="mad-confirm-actions"><button type="button" class="mad-confirm-cancel">Cancelar</button><button type="button" class="mad-confirm-accept">Confirmar</button></div></div>`;
 document.body.append(dialog);madConfirmDialog=dialog;return dialog;
}
function madConfirm(message,{tone='primary',confirmLabel='Confirmar',title='Confirmar acción'}={}){
 if(madConfirmBusy)return Promise.resolve(false);
 madConfirmBusy=true;
 const dialog=ensureMadConfirmDialog();
 const titleEl=dialog.querySelector('#mad-confirm-title'),messageEl=dialog.querySelector('#mad-confirm-message'),accept=dialog.querySelector('.mad-confirm-accept'),cancel=dialog.querySelector('.mad-confirm-cancel');
 titleEl.textContent=title;messageEl.textContent=String(message||'¿Deseas continuar?');accept.textContent=confirmLabel;accept.dataset.tone=tone;
 return new Promise(resolve=>{
  const controller=new AbortController(),{signal}=controller;let finished=false;
  const finish=value=>{if(finished)return;finished=true;madConfirmBusy=false;controller.abort();if(dialog.open)dialog.close();resolve(value);};
  accept.addEventListener('click',()=>finish(true),{signal});cancel.addEventListener('click',()=>finish(false),{signal});
  dialog.addEventListener('cancel',event=>{event.preventDefault();finish(false);},{signal});
  dialog.addEventListener('click',event=>{if(event.target===dialog)finish(false);},{signal});
  if(!dialog.open)dialog.showModal();requestAnimationFrame(()=>cancel.focus());
 });
}
window.madConfirm=madConfirm;


// Alertas visuales para validaciones y avisos que antes quedaban en la burbuja nativa
// del navegador. Mantienen el mismo blur y lenguaje visual de las confirmaciones.
let madAlertDialog=null,madAlertBusy=false;
function ensureMadAlertDialog(){
 if(madAlertDialog)return madAlertDialog;
 const dialog=document.createElement('dialog');
 dialog.className='mad-alert-dialog';
 dialog.setAttribute('aria-labelledby','mad-alert-title');
 dialog.setAttribute('aria-describedby','mad-alert-message');
 dialog.innerHTML=`<div class="mad-confirm-card mad-alert-card"><div class="mad-confirm-icon" aria-hidden="true">!</div><div class="mad-confirm-copy"><h2 id="mad-alert-title">Revisa este dato</h2><p id="mad-alert-message"></p></div><div class="mad-alert-actions"><button type="button" class="mad-confirm-accept" data-tone="primary">Entendido</button></div></div>`;
 document.body.append(dialog);madAlertDialog=dialog;return dialog;
}
function madAlert(message,{title='Revisa este dato'}={}){
 if(madAlertBusy)return Promise.resolve();
 madAlertBusy=true;const dialog=ensureMadAlertDialog();
 dialog.querySelector('#mad-alert-title').textContent=title;
 dialog.querySelector('#mad-alert-message').textContent=String(message||'Revisa la información e inténtalo nuevamente.');
 const accept=dialog.querySelector('.mad-confirm-accept');
 return new Promise(resolve=>{
  const controller=new AbortController(),{signal}=controller;let finished=false;
  const finish=()=>{if(finished)return;finished=true;madAlertBusy=false;controller.abort();if(dialog.open)dialog.close();resolve();};
  accept.addEventListener('click',finish,{signal});
  dialog.addEventListener('cancel',event=>{event.preventDefault();finish();},{signal});
  if(!dialog.open)dialog.showModal();requestAnimationFrame(()=>accept.focus());
 });
}
window.madAlert=madAlert;

// Evita las alertas nativas tipo “Completa este campo”. Se muestra un solo aviso
// diseñado y después se devuelve el foco al primer campo que requiere atención.
let validationAlertPending=false;
const validationFieldName=field=>{
 const label=field.closest('label')?.querySelector(':scope > span')?.textContent?.trim();
 return label||field.getAttribute('aria-label')||field.getAttribute('placeholder')||field.name||'este campo';
};
const validationMessageFor=field=>{
 const name=validationFieldName(field);
 if(field.validity?.customError&&field.validationMessage)return field.validationMessage;
 if(field.validity?.valueMissing)return `Completa el campo “${name}”.`;
 if(field.validity?.typeMismatch)return `Revisa el formato de “${name}”.`;
 if(field.validity?.tooShort)return `“${name}” todavía es demasiado corto.`;
 if(field.validity?.tooLong)return `“${name}” supera el tamaño permitido.`;
 if(field.validity?.patternMismatch)return `Revisa el formato de “${name}”.`;
 if(field.validity?.rangeUnderflow||field.validity?.rangeOverflow)return `Revisa el valor de “${name}”.`;
 return `Revisa el campo “${name}”.`;
};
document.addEventListener('invalid',event=>{
 event.preventDefault();
 const field=event.target;if(!(field instanceof HTMLElement))return;
 // El selector de alumno muestra su propio mensaje dentro del recuadro (assets/listas.js).
 if(field.closest('.student-picker.is-enhanced'))return;
 const wrap=field.closest('.field');wrap?.classList.add('is-invalid');
 const clear=()=>wrap?.classList.remove('is-invalid');field.addEventListener('input',clear,{once:true});field.addEventListener('change',clear,{once:true});
 if(validationAlertPending)return;validationAlertPending=true;
 const message=validationMessageFor(field);
 setTimeout(()=>madAlert(message).finally(()=>{validationAlertPending=false;try{field.focus({preventScroll:true});field.scrollIntoView({block:'center',behavior:'smooth'});}catch{field.focus();}}),0);
},true);

const sessionPing=document.querySelector('[data-session-ping]');
let sessionRedirecting=false;
function redirectExpiredSession(message='Tu sesión ya no está activa. Vuelve a iniciar sesión para continuar.'){
 if(!sessionPing||sessionRedirecting)return false;
 sessionRedirecting=true;notify(message);
 try{sessionStorage.setItem('mad-notice',message);}catch{}
 setTimeout(()=>location.assign(sessionPing.dataset.sessionLogin||'/'),900);
 return true;
}
for(const form of document.querySelectorAll('form[data-api]')){
 form.addEventListener('submit',async event=>{
  event.preventDefault();
  if(form.matches('[data-branding-form]')){const invalid=form.querySelector(':invalid');const section=invalid?.closest('[data-branding-section]');if(section&&!section.classList.contains('is-active'))document.querySelector(`[data-branding-go="${CSS.escape(section.id)}"]`)?.click();}
  if(!form.reportValidity())return;
  if(form.dataset.confirm){
   const submitter=event.submitter||form.querySelector('button[type="submit"]');
   const tone=submitter?.classList.contains('danger')?'danger':submitter?.classList.contains('warning')?'warning':submitter?.classList.contains('success')?'success':'primary';
   const confirmLabel=(submitter?.textContent||'Confirmar').trim();
   if(!(await madConfirm(form.dataset.confirm,{tone,confirmLabel})))return;
  }
  const buttons=[...form.querySelectorAll('button[type="submit"]')];const feedback=form.dataset.toast==='true'?null:formFeedback(form);
  buttons.forEach(b=>b.disabled=true);if(feedback){feedback.hidden=true;feedback.classList.remove('success');}
  try{
   if(form.matches('[data-branding-form]')||form.dataset.preserveScroll==='true'){try{
    sessionStorage.setItem('mad-preserve-scroll',String(window.scrollY));
    if(form.matches('[data-branding-form]')){
      const sections=[...form.querySelectorAll('[data-branding-section]')];
      const current=form.querySelector('[data-branding-section].is-active')||sections[0];
      if(current?.id){sessionStorage.setItem('mad-branding-section',current.id);sessionStorage.setItem('mad-branding-active-step',current.id);}
    }
   }catch{}}
   const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),headers:{'Accept':'application/json'},credentials:'same-origin'});
   const content=response.headers.get('content-type')||'';
   if(!content.includes('application/json'))throw new Error('El servidor no respondió correctamente. Revisa el hosting.');
   const result=await response.json();if(response.status===401&&sessionPing){redirectExpiredSession(result.message);return;}if(!response.ok||!result.ok)throw new Error(result.message||'No se pudo completar la operación.');
   if(result.invite&&window.madShowInvite){window.madShowInvite(result.invite);return;}
   if(result.verify&&window.madShowVerify){window.madShowVerify(result,form);return;}
   if(result.redirect){location.assign(result.redirect);return;}
   if(form.dataset.stay==='true'){if(feedback){feedback.textContent=result.message+(result.secret?'\n\n'+result.secret:'');feedback.classList.add('success');feedback.hidden=false;}return;}
   try{sessionStorage.setItem('mad-notice',result.message||'Cambios guardados.');}catch{}location.reload();
  }catch(error){if(feedback){showFormFeedback(form,error instanceof TypeError?'No se pudo conectar. Revisa tu conexión e inténtalo nuevamente.':error.message);}else notify(error.message,'error');}
  finally{buttons.forEach(b=>b.disabled=false);}
 });
}
for(const button of document.querySelectorAll('[data-open]'))button.addEventListener('click',()=>{
 const dialog=document.getElementById(button.dataset.open);if(!dialog)return;
 const form=dialog.querySelector('form');if(form){form.reset();const feedback=form.querySelector('[data-feedback]');if(feedback)feedback.hidden=true;const id=form.elements.namedItem('id');if(id)id.value='';}
 let data={};try{data=JSON.parse(button.dataset.values||'{}');}catch{notify('No se pudo abrir este registro.');return;}
 for(const [key,value]of Object.entries(data)){const field=form?.elements.namedItem(key);if(field&&'value'in field)field.value=value??'';}
 if(dialog.id==='student-modal'){const group=form?.elements.namedItem('grupo');if(group)group.disabled=!!data.id;}
 const title=dialog.querySelector('[data-dialog-title]');if(title)title.textContent=button.dataset.title||dialog.dataset.title||title.textContent;
 dialog.showModal();
});
for(const button of document.querySelectorAll('[data-close]'))button.addEventListener('click',()=>button.closest('dialog').close());
for(const dialog of document.querySelectorAll('dialog'))dialog.addEventListener('click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}});
for(const input of document.querySelectorAll('[data-search]')){
 const table=document.getElementById(input.dataset.search);if(!table)continue;
 const summary=document.createElement('p');summary.className='search-summary';summary.setAttribute('role','status');summary.hidden=true;table.closest('.table-wrap').after(summary);
 input.addEventListener('input',()=>{const term=input.value.toLocaleLowerCase('es').trim();let visible=0;for(const row of table.querySelectorAll('tbody tr')){row.hidden=!row.textContent.toLocaleLowerCase('es').includes(term);if(!row.hidden)visible++;}summary.hidden=!term;summary.textContent=visible?`${visible} ${visible===1?'coincidencia':'coincidencias'}`:'Sin coincidencias. Prueba otro nombre o dato.';});
}
for(const select of document.querySelectorAll('[data-filter]'))select.addEventListener('change',()=>select.form.submit());
for(const button of document.querySelectorAll('[data-copy]'))button.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(button.dataset.copy);notify('Código copiado.');}catch{notify('Selecciona el código y cópialo manualmente.');}});
const menu=document.querySelector('#sidebar');
const sidebarNav=document.querySelector('#sidebar .nav');
// Conserva exactamente la posición elegida por la persona en el menú lateral.
// No hacemos scroll automático hacia el enlace activo: si Dirección bajó hasta
// Personalización, el menú seguirá en ese mismo punto al entrar a otro módulo.
if(sidebarNav){
 const sidebarScrollKey='mad-sidebar-scroll';
 const saveSidebarScroll=()=>{try{sessionStorage.setItem(sidebarScrollKey,String(sidebarNav.scrollTop));}catch{}};
 try{const saved=Number(sessionStorage.getItem(sidebarScrollKey));if(Number.isFinite(saved))requestAnimationFrame(()=>{sidebarNav.scrollTop=Math.max(0,saved);});}catch{}
 sidebarNav.addEventListener('scroll',saveSidebarScroll,{passive:true});
 sidebarNav.querySelectorAll('a').forEach(link=>link.addEventListener('click',saveSidebarScroll));
 window.addEventListener('pagehide',saveSidebarScroll);
}
document.querySelector('[data-menu]')?.addEventListener('click',e=>{const open=menu.classList.toggle('open');e.currentTarget.setAttribute('aria-expanded',String(open));});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){menu?.classList.remove('open');document.querySelector('[data-menu]')?.setAttribute('aria-expanded','false');}});
document.addEventListener('click',e=>{if(menu?.classList.contains('open')&&!menu.contains(e.target)&&!e.target.closest('[data-menu]')){menu.classList.remove('open');document.querySelector('[data-menu]')?.setAttribute('aria-expanded','false');}});
try{const message=sessionStorage.getItem('mad-notice');if(message){sessionStorage.removeItem('mad-notice');notify(message);}}catch{}

if('IntersectionObserver' in window){const observer=new IntersectionObserver(entries=>{for(const entry of entries){if(!entry.isIntersecting)continue;const el=entry.target;observer.unobserve(el);const data=new FormData();data.set('csrf',el.dataset.csrf);data.set('action','notice_read');data.set('id',el.dataset.notice);fetch(el.dataset.readUrl,{method:'POST',body:data,credentials:'same-origin'}).catch(()=>{});}},{threshold:0.7});for(const item of document.querySelectorAll('[data-notice]'))observer.observe(item);}

for(const form of document.querySelectorAll('form[action$="/process/logout.php"]'))form.addEventListener('submit',()=>{try{for(const key of Object.keys(sessionStorage))if(key.startsWith('mad-draft-'))sessionStorage.removeItem(key);}catch{}});


// Mensajes persistentes dentro del formulario, también para descargas.
function formFeedback(form){
 let el=form.querySelector('[data-feedback]');
 if(!el){el=document.createElement('div');el.className='feedback';el.dataset.feedback='';el.hidden=true;form.append(el);}
 el.setAttribute('role','alert');el.setAttribute('aria-live','assertive');el.setAttribute('tabindex','-1');return el;
}
function showFormFeedback(form,message,success=false){
 const el=formFeedback(form);el.textContent=message;el.classList.toggle('success',success);el.hidden=false;el.focus();return el;
}
async function submitBackup(event){
 event.preventDefault();const form=event.currentTarget;
 if(form.dataset.busy==='true'||!form.reportValidity())return;
 form.dataset.busy='true';const buttons=[...form.querySelectorAll('button[type="submit"]')];
 const prior=buttons.map(b=>b.disabled);buttons.forEach(b=>b.disabled=true);
 const feedback=formFeedback(form);feedback.hidden=true;
 try{
  const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{Accept:'application/sql, application/json'}});
  const type=(response.headers.get('content-type')||'').toLowerCase();
  if(type.includes('application/json')){
   const result=await response.json();
   if(response.status===401&&sessionPing){redirectExpiredSession(result.message);return;}
   throw new Error(result.message||'No se pudo generar el respaldo. Inténtalo de nuevo.');
  }
  if(response.redirected||response.status===401||response.status===403)throw new Error('Tu sesión venció o no tienes permiso. Vuelve a iniciar sesión y solicita el respaldo.');
  if(!response.ok||!type.includes('application/sql'))throw new Error('No se pudo generar el respaldo. Inténtalo de nuevo; si continúa, contacta a Dirección.');
  const blob=await response.blob();if(!blob.size)throw new Error('El respaldo llegó vacío. Inténtalo nuevamente.');
  const header=response.headers.get('content-disposition')||'';
  const match=header.match(/filename="([a-zA-Z0-9_.-]+\.sql)"/);
  const file=match?match[1]:'miauladigital-respaldo.sql';
  const objectUrl=URL.createObjectURL(blob);const link=document.createElement('a');
  link.href=objectUrl;link.download=file;document.body.append(link);link.click();link.remove();
  setTimeout(()=>URL.revokeObjectURL(objectUrl),60000);
  const password=form.elements.namedItem('password');if(password)password.value='';
  showFormFeedback(form,'Respaldo generado. Revisa las descargas de tu navegador. Tus datos siguen intactos.',true);
 }catch(error){
  const message=error instanceof TypeError?'No se pudo conectar para descargar el respaldo. Revisa tu conexión e inténtalo nuevamente.':error.message;
  showFormFeedback(form,message);
  const password=form.elements.namedItem('password');if(password)password.value='';
 }finally{delete form.dataset.busy;buttons.forEach((b,i)=>b.disabled=prior[i]);}
}
for(const form of document.querySelectorAll('form[action$="/process/backup.php"]')){
 formFeedback(form);form.addEventListener('submit',submitBackup);
}

// Un error al cerrar sesión también se queda dentro de la aplicación.
for(const form of document.querySelectorAll('form[action$="/process/logout.php"]')){
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(form.dataset.busy==='true')return;form.dataset.busy='true';
  const button=form.querySelector('button[type="submit"]');if(button)button.disabled=true;
  try{
   const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{Accept:'application/json'}});
   if((response.headers.get('content-type')||'').includes('application/json')){const result=await response.json();throw new Error(result.message||'No se pudo cerrar sesión.');}
   if(!response.ok||!response.redirected)throw new Error('No se pudo confirmar el cierre de sesión. Inténtalo nuevamente.');
   const target=new URL(response.url,location.href);if(target.origin!==location.origin)throw new Error('El servidor devolvió una dirección inesperada.');
   location.assign(target.href);
  }catch(error){showFormFeedback(form,error instanceof TypeError?'No se pudo conectar. Intenta cerrar sesión nuevamente.':error.message);}
  finally{delete form.dataset.busy;if(button)button.disabled=false;}
 });
}

// Mantiene la sesión mientras la persona realmente está usando el panel.
// Una pestaña oculta o sin interacción no renueva la sesión indefinidamente.
if(sessionPing){
 let lastInteraction=Date.now(),sessionProblem=false;
 const touch=()=>{lastInteraction=Date.now();};
 for(const eventName of ['pointerdown','keydown','input','scroll','touchstart','focus']){
  window.addEventListener(eventName,touch,{passive:true});
 }
 const checkSession=async()=>{
  if(sessionProblem||document.visibilityState!=='visible'||Date.now()-lastInteraction>10*60*1000)return;
  try{
   const response=await fetch(sessionPing.dataset.sessionPing,{method:'GET',credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});
   const type=(response.headers.get('content-type')||'').toLowerCase();
   if(!type.includes('application/json'))return;
   const result=await response.json();
   if(response.status===401||!response.ok||!result.ok){
    sessionProblem=true;
    redirectExpiredSession(result.message||'Tu sesión ya no está activa. Vuelve a iniciar sesión para continuar.');
    return;
   }
   if(String(result.user)!==String(sessionPing.dataset.sessionUser)||String(result.role)!==String(sessionPing.dataset.sessionRole)){
    sessionProblem=true;
    redirectExpiredSession('La sesión cambió en otra pestaña. Vuelve a iniciar sesión para continuar.');
   }
  }catch{/* Un fallo temporal de red no cierra la sesión ni interrumpe el trabajo. */}
 };
 setInterval(checkSession,5*60*1000);
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible'){touch();checkSession();}});
}



try{
  const isBranding=!!document.querySelector('[data-branding-editor]');
  if(isBranding){try{history.scrollRestoration='manual';}catch{}}
  const savedScroll=sessionStorage.getItem('mad-preserve-scroll')||sessionStorage.getItem('mad-branding-scroll');
  const savedSection=sessionStorage.getItem('mad-branding-section');
  if(savedScroll!==null||savedSection){
    sessionStorage.removeItem('mad-preserve-scroll');sessionStorage.removeItem('mad-branding-scroll');sessionStorage.removeItem('mad-branding-section');
    const restore=()=>{
      const exact=savedScroll!==null?Math.max(0,Number(savedScroll)||0):null;
      const target=savedSection?document.getElementById(savedSection):null;
      const top=exact!==null?exact:(target?Math.max(0,target.getBoundingClientRect().top+window.scrollY-18):0);
      window.scrollTo(0,top);
    };
    requestAnimationFrame(restore);setTimeout(restore,120);setTimeout(restore,360);
  }
}catch{}
// Editor visual de la escuela: vista previa instantánea sin ejecutar HTML/CSS del usuario.

// Ponderaciones de ciclo: muestra el total en vivo y evita guardar una distribución distinta de 100%.
const cycleModal=document.querySelector('#cycle-modal');
if(cycleModal){
 const weightInputs=[...cycleModal.querySelectorAll('input[name^="peso_"]')];
 const footerButton=cycleModal.querySelector('button[type="submit"]');
 const note=cycleModal.querySelector('[data-weight-total]');
 const refreshWeights=()=>{
  const total=weightInputs.reduce((sum,input)=>sum+(parseFloat(input.value)||0),0);
  const valid=Math.abs(total-100)<0.05 && weightInputs.every(input=>{const n=parseFloat(input.value);return Number.isFinite(n)&&n>0&&n<=100;});
  if(note){note.textContent=`Total actual: ${total.toFixed(2)}% ${valid?'✓ Correcto':'· Debe sumar 100%'}`;note.classList.toggle('good',valid);note.classList.toggle('warn',!valid);}
  if(footerButton)footerButton.disabled=!valid;
 };
 weightInputs.forEach(i=>i.addEventListener('input',refreshWeights));
 document.querySelectorAll('[data-open="cycle-modal"]').forEach(btn=>btn.addEventListener('click',()=>setTimeout(()=>{refreshWeights();const isNew=!cycleModal.querySelector('input[name="id"]')?.value;cycleModal.querySelectorAll('[data-new-only]').forEach(el=>{el.hidden=!isNew;el.querySelectorAll('input').forEach(i=>i.disabled=!isNew);});},40)));
 refreshWeights();
}

const brandingEditor=document.querySelector('[data-branding-editor]');
if(brandingEditor){
 const form=brandingEditor.querySelector('form[data-branding-form]');
 const preview=brandingEditor.querySelector('[data-brand-preview]');
 const loginPreview=brandingEditor.querySelector('[data-login-preview]');
 const named=name=>form?.elements.namedItem(name);
 const field=name=>{const item=named(name);if(typeof RadioNodeList!=='undefined'&&item instanceof RadioNodeList)return form?.querySelector(`[name="${CSS.escape(name)}"]:checked`)||null;return item;};
 const value=name=>{const item=named(name);if(typeof RadioNodeList!=='undefined'&&item instanceof RadioNodeList)return String(item.value||'').trim();return String(item?.value??'').trim();};

 // Navegación guiada del editor: muestra una sola sección para que sea más fácil entenderlo.
 const workflow=brandingEditor.querySelector('[data-branding-workflow]');
 const sections=[...brandingEditor.querySelectorAll('[data-branding-section]')];
 const stepButtons=[...brandingEditor.querySelectorAll('[data-branding-go]')];
 const activateSection=(id,{preserveScroll=true}={})=>{
   const target=sections.find(section=>section.id===id)||sections[0];if(!target)return;
   const beforeY=window.scrollY;
   sections.forEach(section=>section.classList.toggle('is-active',section===target));
   stepButtons.forEach(button=>{const active=button.dataset.brandingGo===target.id;button.classList.toggle('is-active',active);button.setAttribute('aria-current',active?'step':'false');});
   try{sessionStorage.setItem('mad-branding-active-step',target.id);}catch{}
   // Cambiar de apartado jamás debe mandar la página arriba. Restauramos el punto
   // de lectura después de cambiar el contenido y no usamos scrollIntoView().
   if(preserveScroll)requestAnimationFrame(()=>window.scrollTo({top:beforeY,left:0,behavior:'auto'}));
 };
 stepButtons.forEach(button=>button.addEventListener('click',()=>activateSection(button.dataset.brandingGo)));
 sections.forEach((section,index)=>{
   const nav=document.createElement('div');nav.className='branding-section-nav';
   const prev=document.createElement('button');prev.type='button';prev.dataset.stepPrev='';prev.textContent='← Anterior';prev.disabled=index===0;prev.addEventListener('click',()=>index>0&&activateSection(sections[index-1].id));
   const next=document.createElement('button');next.type='button';next.dataset.stepNext='';next.textContent=index===sections.length-1?'Revisar y guardar':'Siguiente →';next.disabled=index===sections.length-1;next.addEventListener('click',()=>index<sections.length-1&&activateSection(sections[index+1].id));
   nav.append(prev,next);section.append(nav);
 });
 brandingEditor.classList.add('is-stepper-ready');
 let initialStep='';try{initialStep=sessionStorage.getItem('mad-branding-active-step')||'';}catch{}
 activateSection(initialStep||sections[0]?.id||'branding-01',{preserveScroll:false});

 const previewModeButtons=[...brandingEditor.querySelectorAll('[data-preview-mode]')];
 const setPreviewMode=mode=>{
   const showLogin=mode==='login';
   preview?.classList.toggle('is-preview-hidden',showLogin);
   loginPreview?.classList.toggle('is-preview-hidden',!showLogin);
   previewModeButtons.forEach(button=>button.classList.toggle('is-active',button.dataset.previewMode===mode));
 };
 previewModeButtons.forEach(button=>button.addEventListener('click',()=>setPreviewMode(button.dataset.previewMode)));
 setPreviewMode('panel');

 const presets={
  rojo:{modo_texto:'manual',color_principal:'#d00032',color_secundario:'#9e1634',color_fondo:'#f5f3f4',color_superficie:'#ffffff',color_texto:'#050505',color_sidebar:'#050002',color_sidebar_texto:'#ffffff',color_hero:'#050002',color_hero_texto:'#ffffff',color_anuncio:'#fff7f9',color_anuncio_texto:'#2d2025',color_exito:'#168a58',color_advertencia:'#c87900',color_peligro:'#b20f35',color_info:'#2c63c7'},
  azul:{modo_texto:'manual',color_principal:'#155eef',color_secundario:'#173ea5',color_fondo:'#f2f6ff',color_superficie:'#ffffff',color_texto:'#101828',color_sidebar:'#0b1f47',color_sidebar_texto:'#ffffff',color_hero:'#102a5c',color_hero_texto:'#ffffff',color_anuncio:'#f0f5ff',color_anuncio_texto:'#13264d',color_exito:'#168a58',color_advertencia:'#d18a00',color_peligro:'#c5224b',color_info:'#155eef'},
  verde:{modo_texto:'manual',color_principal:'#0f9f6e',color_secundario:'#087a56',color_fondo:'#f1f8f5',color_superficie:'#ffffff',color_texto:'#10251e',color_sidebar:'#092f25',color_sidebar_texto:'#ffffff',color_hero:'#0b3b2d',color_hero_texto:'#ffffff',color_anuncio:'#edf9f4',color_anuncio_texto:'#163c30',color_exito:'#0f9f6e',color_advertencia:'#d79a20',color_peligro:'#b4234b',color_info:'#2877c7'},
  morado:{modo_texto:'manual',color_principal:'#7c3aed',color_secundario:'#5b21b6',color_fondo:'#f7f4ff',color_superficie:'#ffffff',color_texto:'#24143a',color_sidebar:'#22113f',color_sidebar_texto:'#ffffff',color_hero:'#30165a',color_hero_texto:'#ffffff',color_anuncio:'#f5efff',color_anuncio_texto:'#352050',color_exito:'#2f9d72',color_advertencia:'#d18a00',color_peligro:'#b42369',color_info:'#5b5fe8'},
  negro:{modo_texto:'manual',color_principal:'#c99b32',color_secundario:'#8f6919',color_fondo:'#f4f2ed',color_superficie:'#fffdf7',color_texto:'#111111',color_sidebar:'#080808',color_sidebar_texto:'#f8e8bd',color_hero:'#0b0b0b',color_hero_texto:'#f8e8bd',color_anuncio:'#fff9e9',color_anuncio_texto:'#31260f',color_exito:'#3b9b6c',color_advertencia:'#c78c18',color_peligro:'#b01f3e',color_info:'#2b66c9'},
  claro:{modo_texto:'manual',color_principal:'#546780',color_secundario:'#39495c',color_fondo:'#f6f8fb',color_superficie:'#ffffff',color_texto:'#172033',color_sidebar:'#ffffff',color_sidebar_texto:'#172033',color_hero:'#eef3f8',color_hero_texto:'#172033',color_anuncio:'#f5f7fa',color_anuncio_texto:'#293447',color_exito:'#248a62',color_advertencia:'#c78719',color_peligro:'#b32145',color_info:'#316fc5'}
 };
 const hexRgb=hex=>{const h=(hex||'#000000').replace('#','');return [0,2,4].map(i=>parseInt(h.slice(i,i+2),16)||0);};
 const lum=hex=>hexRgb(hex).map(v=>{const c=v/255;return c<=.03928?c/12.92:((c+.055)/1.055)**2.4;}).reduce((s,c,i)=>s+c*[.2126,.7152,.0722][i],0);
 const contrast=(a,b)=>{const x=lum(a),y=lum(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05);};
 const bestText=bg=>contrast(bg,'#ffffff')>=contrast(bg,'#111111')?'#ffffff':'#111111';
 const rgba=(hex,a)=>{const [r,g,b]=hexRgb(hex);return `rgba(${r},${g},${b},${a})`;};
 const text=(selector,value)=>{const el=brandingEditor.querySelector(selector);if(el)el.textContent=value;};
 // Logo de la vista previa: la imagen subida (si existe) o la casita predeterminada con el color principal.
 let previewLogoImg=brandingEditor.querySelector('[data-preview-logo] img');
 const previewHouse='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-6 9 6v11H3Z"/><path d="M9 20v-7h6v7M7 10h1m8 0h1"/></svg>';
 const paintPreviewLogo=(accent,secondary,sidebarText)=>{
  const box=brandingEditor.querySelector('[data-preview-logo]');if(!box)return;
  const removeLogo=!!field('quitar_logo')?.checked;
  const useImage=!!previewLogoImg&&!removeLogo;
  if(useImage){
   if(box.firstElementChild!==previewLogoImg)box.replaceChildren(previewLogoImg);
   box.style.setProperty('background','#ffffff','important');box.style.removeProperty('border');
  }else{
   if(!box.querySelector('svg'))box.innerHTML=previewHouse;
   const ink=bestText(accent);
   box.style.setProperty('background',`linear-gradient(145deg,${accent},${secondary})`,'important');
   box.style.setProperty('border',`1px solid ${rgba('#ffffff',.35)}`);
   box.querySelector('svg')?.style.setProperty('color',ink,'important');
  }
  const brand=brandingEditor.querySelector('.preview-brand');
  brand?.querySelectorAll('strong,small').forEach(el=>el.style.setProperty('color',sidebarText,'important'));
 };
 const update=()=>{
  if(!form||!preview)return;
  const v=n=>value(n);
  const accent=v('color_principal')||'#d00032',secondary=v('color_secundario')||'#9e1634',bg=v('color_fondo')||'#f5f3f4',surface=v('color_superficie')||'#ffffff',textColor=v('color_texto')||'#050505',textMode='manual';
  const sidebar=v('color_sidebar')||'#050002',sidebarText=v('color_sidebar_texto')||'#ffffff',hero=v('color_hero')||'#050002',heroText=v('color_hero_texto')||'#ffffff',announcement=v('color_anuncio')||'#fff7f9',announcementText=v('color_anuncio_texto')||'#2d2025';
  // Los textos se controlan por zonas; ya no existe el modo global que podía
  // convertir toda la interfaz en blanco sobre blanco o negro sobre negro.
  const effectiveBackgroundText=textColor;
  const effectiveSurfaceText=textColor;
  const effectiveAnnouncementText=announcementText;
  const effectiveHeroText=heroText;
  const effectiveSidebarText=sidebarText;
  const success=v('color_exito')||'#168a58',warning=v('color_advertencia')||'#c87900',danger=v('color_peligro')||'#d00032',info=v('color_info')||'#2c63c7';
  const cardRadius=Math.max(12,Number(v('radio_tarjetas'))||24),buttonRadius=Math.max(6,Number(v('radio_botones'))||12),blur=Math.max(0,Number(v('desenfoque'))||0),opacity=Math.max(.38,Math.min(1,(Number(v('opacidad_cristal'))||82)/100)),shadow=Math.max(0,Number(v('intensidad_sombra'))||0),fontScale=Number(v('escala_fuente'))||100,sidebarWidth=Math.max(220,Number(v('ancho_sidebar'))||248),density=v('densidad')||'comoda';
  const densityFactor={compacta:.78,comoda:1,amplia:1.24}[density]||1;
  preview.style.setProperty('--preview-accent',accent);preview.style.setProperty('--preview-secondary',secondary);preview.style.setProperty('--preview-bg',bg);preview.style.setProperty('--preview-surface',surface);preview.style.setProperty('--preview-text',effectiveBackgroundText);preview.style.setProperty('--preview-text-choice',textColor);preview.style.setProperty('--preview-text-mode',textMode);preview.style.setProperty('--preview-surface-text',effectiveSurfaceText);preview.style.setProperty('--preview-sidebar',sidebar);preview.style.setProperty('--preview-sidebar-text',effectiveSidebarText);preview.style.setProperty('--preview-hero',hero);preview.style.setProperty('--preview-hero-text',effectiveHeroText);preview.style.setProperty('--preview-announcement',announcement);preview.style.setProperty('--preview-announcement-text',effectiveAnnouncementText);preview.style.setProperty('--preview-success',success);preview.style.setProperty('--preview-warning',warning);preview.style.setProperty('--preview-danger',danger);preview.style.setProperty('--preview-info',info);
  preview.style.color=effectiveBackgroundText;
  const shell=brandingEditor.querySelector('.preview-shell');
  if(shell){const miniatureSidebar=Math.round(78+((sidebarWidth-220)/80)*72);const previewFont=fontScale/100;shell.style.gridTemplateColumns=`${miniatureSidebar}px minmax(0,1fr)`;shell.style.borderRadius=`${Math.max(8,cardRadius*.75)}px`;shell.style.setProperty('--preview-font-scale',previewFont.toFixed(3));shell.style.background=bg;shell.style.boxShadow=`0 ${Math.max(3,shadow*.7)}px ${Math.max(10,shadow*3)}px ${rgba(effectiveBackgroundText,Math.min(.28,shadow/75))}`;}
  const previewLogout=brandingEditor.querySelector('[data-preview-logout]');if(previewLogout){previewLogout.style.background=`linear-gradient(145deg,${danger},${rgba(danger,.78)})`;previewLogout.style.color=effectiveSidebarText;previewLogout.style.borderRadius=`${buttonRadius}px`;previewLogout.style.boxShadow=`0 3px 8px ${rgba(danger,.18)}`;}
  const main=brandingEditor.querySelector('.preview-main');
  if(main){main.style.padding=`${Math.round(12*densityFactor)}px`;main.style.gap=`${Math.round(6*densityFactor)}px`;main.style.background=bg;main.style.color=effectiveBackgroundText;}
  const sidebarPreview=brandingEditor.querySelector('.preview-sidebar');
  if(sidebarPreview){sidebarPreview.style.padding=`${Math.round(10*densityFactor)}px`;sidebarPreview.style.background=`linear-gradient(160deg,${sidebar},${rgba(secondary,.20)}),${sidebar}`;sidebarPreview.style.color=effectiveSidebarText;}
  brandingEditor.querySelectorAll('.preview-nav').forEach((el,i)=>{el.style.color=effectiveSidebarText;el.style.borderRadius=`${buttonRadius}px`;el.style.padding=`${Math.max(4,Math.round(7*densityFactor))}px 8px`;el.style.fontSize=`${Math.max(6,7*fontScale/100)}px`;if(i===0){el.style.background=`linear-gradient(145deg,${accent},${secondary})`;el.style.color=effectiveSidebarText;}});
  const cards=brandingEditor.querySelectorAll('.preview-card,.preview-hero,.preview-announcement,.preview-login-card');
  cards.forEach(el=>{el.style.borderRadius=`${Math.max(6,cardRadius*.55)}px`;el.style.backdropFilter=`blur(${blur}px)`;el.style.webkitBackdropFilter=`blur(${blur}px)`;el.style.boxShadow=`0 ${Math.max(2,shadow*.45)}px ${Math.max(6,shadow*1.8)}px ${rgba(textColor,Math.min(.18,shadow/100))}`;});
  brandingEditor.querySelectorAll('.preview-card').forEach(el=>{const glassAlpha=Math.min(.88,Math.max(.18,opacity*.64));const glassEdge=Math.min(.96,Math.max(.30,opacity*.92));el.style.background=`linear-gradient(145deg,${rgba(surface,glassAlpha)},${rgba(surface,Math.max(.08,glassAlpha-.22))})`;el.style.setProperty('backdrop-filter',`blur(${blur}px) saturate(170%)`);el.style.setProperty('-webkit-backdrop-filter',`blur(${blur}px) saturate(170%)`);el.style.borderColor=rgba(effectiveSurfaceText,.18);el.style.color=effectiveSurfaceText;});
  const heroEl=brandingEditor.querySelector('.preview-hero');if(heroEl){heroEl.style.background=`linear-gradient(145deg,${rgba(hero,Math.max(.62,opacity*.88))},${rgba(secondary,Math.max(.20,opacity*.34))})`;heroEl.style.color=effectiveHeroText;heroEl.style.padding=`${Math.round(14*densityFactor)}px`;heroEl.querySelector('small')?.style.setProperty('color',rgba(effectiveHeroText,.82));heroEl.querySelector('strong')?.style.setProperty('color',effectiveHeroText);heroEl.querySelector('p')?.style.setProperty('color',rgba(effectiveHeroText,.82));}
  const notice=brandingEditor.querySelector('.preview-announcement');if(notice){notice.style.background=rgba(announcement,opacity);notice.style.color=effectiveAnnouncementText;notice.style.borderLeftColor=accent;notice.style.borderColor=rgba(accent,.16);notice.style.padding=`${Math.round(10*densityFactor)}px`;notice.querySelector('strong')?.style.setProperty('color',effectiveAnnouncementText);notice.querySelector('p')?.style.setProperty('color',rgba(effectiveAnnouncementText,.78));}
  const actionColors={primary:accent,success,warning,danger,info};
  for(const el of brandingEditor.querySelectorAll('[data-preview-action]')){const c=actionColors[el.dataset.previewAction]||accent;const actionText=textColor;el.style.setProperty('--preview-action-text',actionText);el.style.background=`linear-gradient(145deg,${c},${rgba(c,.78)})`;el.style.borderRadius=`${buttonRadius}px`;el.style.color=actionText;el.style.boxShadow=`0 4px 12px ${rgba(c,.20)}`;}
  brandingEditor.querySelectorAll('[data-preview-status]').forEach(el=>{const c=actionColors[el.dataset.previewStatus]||accent;el.style.color=textColor;el.style.borderColor=c;});
  const previewTable=brandingEditor.querySelector('.preview-table');if(previewTable){previewTable.style.color=effectiveSurfaceText;previewTable.style.borderColor=rgba(effectiveSurfaceText,.18);}
  brandingEditor.querySelectorAll('.preview-role-messages span').forEach(el=>{el.style.color=effectiveSurfaceText;el.style.background=rgba(surface,Math.max(.55,opacity));});
  const previewBrand=brandingEditor.querySelector('.preview-brand');if(previewBrand)previewBrand.style.color=effectiveSidebarText;
  paintPreviewLogo(accent,secondary,effectiveSidebarText);
  text('[data-preview-name]',v('nombre_sistema')||'Mi Aula Digital');text('[data-preview-school]',v('nombre_escuela')||v('lema')||'Un espacio para aprender.');text('[data-preview-menu-label]',v('menu_etiqueta')||'Tu espacio');text('[data-preview-eyebrow]',v('hero_etiqueta'));text('[data-preview-hero-title]',(v('hero_titulo')||'Hola, {nombre}.').replaceAll('{nombre}','Andrea'));text('[data-preview-hero-text]',v('hero_direccion'));text('[data-preview-notices-title]',v('panel_avisos_titulo'));text('[data-preview-notice-text]',v('panel_avisos_vacio_txt')||'Ejemplo de un anuncio para toda la comunidad escolar.');text('[data-preview-login-title]',v('login_titulo'));text('[data-preview-login-text]',v('login_texto'));text('[data-preview-login-card-title]',v('login_tarjeta_titulo'));text('[data-preview-login-card-text]',v('login_tarjeta_texto'));text('[data-preview-card-1-title]',v('panel_lista_titulo')||'Calificaciones');text('[data-preview-card-1-text]',v('panel_lista_texto')||'Promedios y periodos');text('[data-preview-card-2-title]',v('panel_titulo')||'Comunidad');text('[data-preview-card-2-text]',v('panel_subtitulo')||'Alumnos y familias');text('[data-preview-card-3-title]',v('panel_avisos_titulo')||'Anuncios');text('[data-preview-card-3-text]',v('panel_avisos_vacio_txt')||'Información reciente');text('[data-preview-role=direccion]',v('hero_direccion'));text('[data-preview-role=docente]',v('hero_docente'));text('[data-preview-role=familia]',v('hero_familia'));
  const loginTitle=brandingEditor.querySelector('[data-preview-login-title]');if(loginTitle)loginTitle.style.color=textColor;
  const loginText=brandingEditor.querySelector('[data-preview-login-text]');if(loginText)loginText.style.color=rgba(textColor,.82);
  const loginCard=brandingEditor.querySelector('.preview-login-card');if(loginCard){loginCard.style.background=rgba(surface,Math.min(.9,Math.max(.24,opacity*.72)));loginCard.style.color=effectiveSurfaceText;loginCard.style.borderColor=rgba(surface,.85);loginCard.style.borderRadius=`${Math.max(6,cardRadius*.58)}px`;loginCard.style.backdropFilter=`blur(${blur}px)`;loginCard.style.webkitBackdropFilter=`blur(${blur}px)`;loginCard.querySelector('b')?.style.setProperty('color',effectiveSurfaceText);loginCard.querySelector('small')?.style.setProperty('color',rgba(effectiveSurfaceText,.70));}
  for(const input of form.querySelectorAll('input[type=color]')){const code=brandingEditor.querySelector(`[data-color-text="${CSS.escape(input.name)}"]`);if(code&&document.activeElement!==code)code.value=input.value;}
  brandingEditor.querySelectorAll('[data-preview-nav]').forEach(nav=>{const key=nav.dataset.previewNav;const current=brandingEditor.querySelector(`select[name="${CSS.escape(key)}"]`)?.closest('.icon-picker')?.querySelector('.icon-picker-current');const holder=nav.querySelector('[data-preview-nav-icon]');if(holder&&current)holder.innerHTML=current.innerHTML;});
  for(const input of form.querySelectorAll('[data-range-output]')){const out=brandingEditor.querySelector(`[data-range-value="${CSS.escape(input.dataset.rangeOutput)}"]`);if(out)out.textContent=input.value;}
  const status=brandingEditor.querySelector('[data-contrast-status]');if(status){const ratios=[['texto/fondo',contrast(effectiveBackgroundText,bg)],['menú',contrast(effectiveSidebarText,sidebar)],['bienvenida',contrast(effectiveHeroText,hero)],['anuncios',contrast(effectiveAnnouncementText,announcement)],['éxito',contrast(textColor,success)],['advertencia',contrast(textColor,warning)],['eliminar',contrast(textColor,danger)],['información',contrast(textColor,info)]];const weak=ratios.filter(([,r])=>r<3);status.classList.toggle('warn',weak.length>0);status.classList.toggle('good',weak.length===0);status.textContent=weak.length?`Revisa contraste: ${weak.map(([n,r])=>`${n} ${r.toFixed(1)}:1`).join(' · ')}.`:`Contraste visual correcto en las combinaciones principales.`;}
  if(loginPreview){const pos=v('fondo_login_posicion')||'center';const posMap={center:'50% 50%',top:'50% 18%',bottom:'50% 82%',left:'18% 50%',right:'82% 50%'};loginPreview.style.backgroundColor=bg;loginPreview.style.color=effectiveBackgroundText;loginPreview.style.setProperty('--preview-accent',accent);loginPreview.style.borderRadius=`${cardRadius}px`;loginPreview.style.boxShadow=`0 ${Math.max(3,shadow*.7)}px ${Math.max(12,shadow*3)}px ${rgba(textColor,.18)}`;const loginImage=loginPreview.querySelector('[data-preview-login-background]');if(loginImage){loginImage.style.objectPosition=posMap[pos]||'50% 50%';loginImage.style.transform='scale(1.18)';loginImage.style.opacity=String(Math.min(.62,Math.max(.22,opacity*.60)));}else{loginPreview.style.backgroundImage='none';loginPreview.style.backgroundPosition='center';}loginPreview.style.padding=`${Math.round(22*densityFactor)}px`; }
  const presetFont={sistema:"system-ui,-apple-system,'Segoe UI',sans-serif",moderna:"Verdana,Geneva,sans-serif",redondeada:"'Trebuchet MS','Segoe UI',sans-serif",serif:"Georgia,'Times New Roman',serif"}[v('tipografia')]||"system-ui,-apple-system,'Segoe UI',sans-serif";
  const font=presetFont;
  for(const root of brandingEditor.querySelectorAll('.branding-preview')){root.style.fontFamily=font;root.style.color=effectiveBackgroundText;root.style.setProperty('--preview-font-family',font);if(!root.closest('.preview-shell'))root.style.setProperty('--preview-font-scale',(fontScale/100).toFixed(3));}
  // La escala se controla únicamente con la variable CSS del preview.
  // No aplicamos tamaños inline a cada hijo porque eso provocaba doble escalado
  // (por ejemplo 108% terminaba viéndose exageradamente grande).
  for(const root of brandingEditor.querySelectorAll('.preview-shell,.login-mini-preview')){
    root.style.setProperty('--preview-font-scale',(fontScale/100).toFixed(3));
    root.style.setProperty('--preview-blur',`${blur}px`);
    root.style.setProperty('--preview-opacity',String(opacity));
    root.style.setProperty('--preview-card-radius',`${Math.max(8,cardRadius*.55)}px`);
    root.style.setProperty('--preview-button-radius',`${buttonRadius}px`);
    root.style.setProperty('--preview-shadow-strength',String(shadow));
  }
 };
 let updateFrame=0;
 const scheduleUpdate=()=>{ if(updateFrame) return; updateFrame=requestAnimationFrame(()=>{updateFrame=0;update();}); };
 form?.addEventListener('input',scheduleUpdate);form?.addEventListener('change',scheduleUpdate);
 for(const hexInput of form?.querySelectorAll('[data-color-text]')||[]){
   hexInput.addEventListener('input',()=>{
     const value=String(hexInput.value||'').trim();
     if(/^#[0-9A-Fa-f]{6}$/.test(value)){
       const color=field(hexInput.dataset.colorText);
       if(color){color.value=value.toLowerCase();scheduleUpdate();}
     }
   });
   hexInput.addEventListener('blur',()=>{
     const color=field(hexInput.dataset.colorText);
     if(color)hexInput.value=color.value;
   });
 }
 const presetButtons=[...brandingEditor.querySelectorAll('[data-theme-preset]')];
 const presetComparableKeys=['color_principal','color_secundario','color_fondo','color_superficie','color_texto','color_sidebar','color_sidebar_texto','color_hero','color_hero_texto','color_anuncio','color_anuncio_texto'];
 const syncPresetSelection=()=>{
  let selected='';
  for(const [name,preset] of Object.entries(presets)){
   if(presetComparableKeys.every(key=>String(value(key)||'').toLowerCase()===String(preset[key]||'').toLowerCase())){selected=name;break;}
  }
  for(const btn of presetButtons){
   const preset=presets[btn.dataset.themePreset];
   btn.style.setProperty('--preset-color',preset?.color_principal||'#64748b');
   btn.style.setProperty('--preset-secondary',preset?.color_secundario||preset?.color_principal||'#64748b');
   const active=btn.dataset.themePreset===selected;btn.classList.toggle('is-selected',active);btn.setAttribute('aria-pressed',String(active));
  }
 };
 for(const btn of presetButtons)btn.addEventListener('click',()=>{
  const preset=presets[btn.dataset.themePreset];if(!preset)return;
  for(const [key,value]of Object.entries(preset)){const input=field(key);if(input)input.value=value;}
  const modeField=field('modo_texto');if(modeField)modeField.value='manual';
  syncPresetSelection();scheduleUpdate();notify(`Tema “${btn.textContent.trim()}” aplicado a la vista previa. Guarda para activarlo.`);
 });
 form?.addEventListener('input',()=>requestAnimationFrame(syncPresetSelection));
 form?.addEventListener('change',()=>requestAnimationFrame(syncPresetSelection));
 syncPresetSelection();
 const logoInput=field('logo');logoInput?.addEventListener('change',()=>{const file=logoInput.files?.[0];if(!file)return;const img=previewLogoImg||document.createElement('img');img.src=URL.createObjectURL(file);img.alt='Vista previa del logo';previewLogoImg=img;scheduleUpdate();});
 const bgInput=field('fondo_login');bgInput?.addEventListener('change',()=>{const file=bgInput.files?.[0];if(!file||!loginPreview)return;const img=brandingEditor.querySelector('[data-preview-login-background]');if(img){img.src=URL.createObjectURL(file);img.hidden=false;img.style.objectPosition=String(field('fondo_login_posicion')?.value||'center');}});
 update();
}
