'use strict';
/*
 * Registro en dos pasos: datos → código enviado al correo.
 * app.js envía los formularios; cuando el servidor responde con "verify",
 * llama a window.madShowVerify para mostrar el paso del código.
 */
(()=>{
const card=document.querySelector('[data-register]');
if(!card)return;
const formStep=card.querySelector('[data-register-step="form"]');
const codeStep=card.querySelector('[data-register-step="code"]');
const progress=name=>card.querySelector(`[data-progress="${name}"]`);
const codeInput=codeStep.querySelector('input[name="codigo"]');
const resendButton=codeStep.querySelector('[data-resend-button]');
const resendTimer=codeStep.querySelector('[data-resend-timer]');
const expiresText=codeStep.querySelector('[data-verify-expires-text]');
let expiresAt=0,resendAt=0,sendsLeft=0,tick=null;

const fmt=s=>`${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;
function clearFeedback(scope){scope.querySelectorAll('[data-feedback]').forEach(f=>{f.hidden=true;f.textContent='';f.classList.remove('success');});}
function render(){
 const now=Date.now();
 const left=Math.max(0,Math.round((expiresAt-now)/1000));
 const expiresEl=codeStep.querySelector('[data-verify-expires]');if(expiresEl)expiresEl.textContent=fmt(left);
 if(expiresText)expiresText.classList.toggle('expired',left===0);
 if(left===0&&expiresText&&expiresEl)expiresText.textContent='El código venció. Pide uno nuevo.';
 const wait=Math.max(0,Math.round((resendAt-now)/1000));
 if(sendsLeft<=0){resendButton.disabled=true;resendTimer.textContent=' (sin envíos disponibles)';}
 else if(wait>0){resendButton.disabled=true;resendTimer.textContent=` (${wait} s)`;}
 else{resendButton.disabled=false;resendTimer.textContent='';}
}
function startTimers(state){
 expiresAt=Date.now()+Math.max(0,Number(state.expires_in)||0)*1000;
 resendAt=Date.now()+Math.max(0,Number(state.resend_in)||0)*1000;
 sendsLeft=Number(state.sends_left)||0;
 if(expiresText&&!expiresText.querySelector('[data-verify-expires]')){
  expiresText.textContent='Vence en ';const s=document.createElement('span');s.dataset.verifyExpires='';expiresText.append(s,'.');
 }
 clearInterval(tick);render();tick=setInterval(render,1000);
}
function showStep(step){
 const code=step==='code';
 formStep.hidden=code;codeStep.hidden=!code;
 progress('datos').className=code?'done':'current';
 progress('codigo').className=code?'current':'';
 if(code){codeInput.value='';setTimeout(()=>codeInput.focus(),50);}
 else{clearInterval(tick);formStep.querySelector('input:not([type=hidden]):not([tabindex="-1"])')?.focus();}
 card.scrollIntoView({block:'start',behavior:'smooth'});
}
// Se llama desde app.js después de enviar el formulario de datos o de reenviar.
window.madShowVerify=(result,form)=>{
 const state=result.verify||{};
 codeStep.querySelector('[data-verify-email]').textContent=state.email||'';
 const dev=codeStep.querySelector('[data-dev-code]');
 if(state.dev_code){dev.hidden=false;dev.querySelector('[data-dev-code-value]').textContent=state.dev_code;}else dev.hidden=true;
 if(form.matches('[data-resend-form]')){
  const fb=form.querySelector('[data-feedback]');if(fb){fb.textContent=result.message||'';fb.classList.add('success');fb.hidden=false;}
  codeInput.value='';codeInput.focus();
 }else{clearFeedback(codeStep);showStep('code');}
 // app.js vuelve a habilitar los botones al terminar; los temporizadores se aplican después.
 setTimeout(()=>startTimers(state),0);
};

// Solo números en el código y envío automático al completar 6 dígitos.
codeInput.addEventListener('input',()=>{
 const clean=codeInput.value.replace(/\D+/g,'').slice(0,6);
 if(clean!==codeInput.value)codeInput.value=clean;
 const submit=codeInput.form.querySelector('button[type="submit"]');
 if(clean.length===6&&!submit.disabled)codeInput.form.requestSubmit(submit);
});

card.querySelector('[data-register-edit]').addEventListener('click',async event=>{
 const button=event.currentTarget;button.disabled=true;
 try{
  const data=new FormData();
  data.append('csrf',card.querySelector('input[name="csrf"]').value);
  data.append('tipo',card.querySelector('input[name="tipo"]').value);
  data.append('paso','cancelar');
  await fetch(codeStep.querySelector('form').getAttribute('action'),{method:'POST',body:data,headers:{'Accept':'application/json'},credentials:'same-origin'});
 }catch{}
 finally{button.disabled=false;}
 clearFeedback(formStep);showStep('form');
});

// Revisión del usuario mientras se escribe: dice exactamente qué corregir.
const userInput=card.querySelector('input[data-username]');
if(userInput){
 const help=userInput.parentElement.querySelector('small');
 const original=help?help.textContent:'';
 const reason=v=>{
  if(v==='')return '';
  if(v.includes('@')){const base=v.split('@')[0].replace(/[^A-Za-z0-9_.\-]/g,'');return 'Aquí no va tu correo. Usa un nombre corto'+(base.length>=4?', por ejemplo: '+base:', p. ej. nombre.apellido')+'. Podrás entrar con tu usuario o tu correo.';}
  if(/\s/.test(v))return 'Sin espacios: usa punto o guion bajo, p. ej. '+v.trim().replace(/\s+/g,'.');
  if(/[áéíóúüñ]/i.test(v))return 'Sin acentos ni ñ (escribe “n” en lugar de “ñ”).';
  if(/[^A-Za-z0-9_.\-]/.test(v))return 'Solo letras, números, punto, guion o guion bajo.';
  if(v.length<4)return 'Muy corto: mínimo 4 caracteres.';
  return '';
 };
 const check=event=>{
  const msg=reason(userInput.value);
  userInput.setCustomValidity(msg);
  // "Muy corto" se muestra hasta salir del campo, para no regañar mientras se escribe.
  const show=msg&&!(event&&event.type==='input'&&userInput.value.length<4&&/^Muy corto/.test(msg))?msg:'';
  userInput.setAttribute('aria-invalid',show?'true':'false');
  if(help){help.textContent=show||original;help.classList.toggle('field-error',!!show);}
 };
 userInput.addEventListener('input',check);userInput.addEventListener('blur',check);
}

// Si la página se recargó con un código pendiente, retoma los contadores.
if(!codeStep.hidden){
 startTimers({expires_in:codeStep.dataset.expires,resend_in:codeStep.dataset.resend,sends_left:codeStep.dataset.sendsLeft});
}
})();
