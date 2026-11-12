'use strict';
/*
 * Medidor de seguridad de contraseñas.
 * Reglas: mínimo 8 caracteres con mayúscula, minúscula, número y símbolo.
 * Además muestra cuánto tardaría un equipo de ataque (un millón de intentos
 * por segundo contra la contraseña cifrada) en adivinarla.
 * Misma fórmula que app/password_strength.php (el servidor vuelve a revisarla).
 */
(()=>{
const RATE=1e6, MIN_YEARS=40, MIN_LEN=8, YEAR=31557600;
const COMMON=['password','passw0rd','contrasena','contraseña','clave','qwerty','asdfgh','zxcvbn','qwertyuiop','123456','1234567890','abc123','admin','administrador','usuario','escuela','maestro','maestra','alumno','profesor','mexico','hidalgo','amor','teamo','iloveyou','familia','hola','futbol','america','chivas','pumas','cruzazul','princesa','dragon','monkey','letmein','welcome','bienvenido','secreto','superman','batman','pokemon','miaula','aula','digital','mama','papa','hijo','hija','dios','jesus','maria','jose','juan','luis','carlos','angel','daniel','miguel','sofia','fernanda','guadalupe','lupita','corazon','estrella','mariposa','tequila','abcdef','abcd','1111','0000','1234','4321','2020','2021','2022','2023','2024','2025','2026','2027'];
const MAP={'á':'a','é':'e','í':'i','ó':'o','ú':'u','ü':'u','ñ':'n','@':'a','4':'a','3':'e','1':'i','!':'i','0':'o','$':'s','5':'s','7':'t'};
const norm=s=>[...s.toLowerCase()].map(c=>MAP[c]??c).join('');
const COMMON_NORM=COMMON.map(norm);

function strength(pass,personal){
 const chars=[...pass];const len=chars.length;
 if(!len)return{seconds:0,years:0,level:-1,label:'',time:''};
 let pool=0;
 if(/[a-zñáéíóúü]/.test(pass))pool+=26;
 if(/[A-ZÑÁÉÍÓÚÜ]/.test(pass))pool+=26;
 if(/[0-9]/.test(pass))pool+=10;
 if(/[^a-zA-Z0-9ñÑáéíóúüÁÉÍÓÚÜ]/.test(pass))pool+=33;
 pool=Math.max(pool,10);
 let eff=1,prevD=null;
 for(let i=1;i<len;i++){const d=chars[i].toLowerCase().codePointAt(0)-chars[i-1].toLowerCase().codePointAt(0);eff+=(d===0||(Math.abs(d)===1&&d===prevD))?.25:1;prevD=d;}
 const n=norm(pass),lower=pass.toLowerCase();
 const words=[...COMMON];
 for(const p of personal)for(const piece of String(p).split(/[\s@._+-]+/))if([...piece].length>=3)words.push(piece);
 const seen=new Set();
 for(let w of words){w=norm(w);if([...w].length<3||seen.has(w))continue;seen.add(w);if(n.includes(w)||lower.includes(w))eff-=([...w].length-1.5);}
 const stripped=n.replace(/[^a-zñ]/g,'');
 if(COMMON_NORM.includes(n)||(stripped&&COMMON.includes(stripped)&&[...stripped].length>=len-4))eff=Math.min(eff,2);
 eff=Math.max(eff,0);
 const log10s=eff*Math.log10(pool)-Math.log10(RATE);
 let seconds=log10s>300?Infinity:10**log10s;
 if(eff<=2)seconds=Math.min(seconds,.5);
 const years=seconds/YEAR;
 let level,label;
 if(years>=10000){level=4;label='Muy fuerte';}
 else if(years>=MIN_YEARS){level=3;label='Fuerte';}
 else if(years>=1){level=2;label='Regular';}
 else if(seconds>=86400){level=1;label='Débil';}
 else{level=0;label='Muy débil';}
 return{seconds,years,level,label,time:timeText(seconds)};
}
function timeText(s){
 if(s<1)return'al instante';
 const units=[[60,'segundo','segundos',1],[3600,'minuto','minutos',60],[86400,'hora','horas',3600],[2629800,'día','días',86400],[YEAR,'mes','meses',2629800]];
 for(const[limit,one,many,div]of units)if(s<limit){const k=Math.floor(s/div);return k+' '+(k===1?one:many);}
 const y=s/YEAR;
 if(y<1000){const k=Math.floor(y);return k+' '+(k===1?'año':'años');}
 if(y<1e6)return'miles de años';
 if(y<1e9)return'millones de años';
 return'más de mil millones de años';
}
window.madPasswordStrength=strength;

const PERSONAL_FIELDS=['nombre','primer_apellido','segundo_apellido','usuario','correo','telefono'];

function personalData(input){
 const form=input.form;const out=[];
 try{out.push(...JSON.parse(input.dataset.personal||'[]'));}catch{}
 if(form)for(const name of PERSONAL_FIELDS){const el=form.elements.namedItem(name);if(el&&'value' in el&&el.value)out.push(el.value);}
 return out;
}

function addToggle(input){
 if(input.closest('.password-wrap'))return;
 const wrap=document.createElement('span');wrap.className='password-wrap';
 input.parentNode.insertBefore(wrap,input);wrap.append(input);
 const btn=document.createElement('button');btn.type='button';btn.className='password-toggle';
 btn.setAttribute('aria-label','Mostrar contraseña');btn.textContent='Mostrar';
 btn.addEventListener('click',()=>{const show=input.type==='password';input.type=show?'text':'password';btn.textContent=show?'Ocultar':'Mostrar';btn.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');input.focus();});
 wrap.append(btn);
}

function setupMeter(input){
 const optional=!input.required;
 const box=document.createElement('div');box.className='pw-meter';box.dataset.level='-1';
 box.innerHTML=`<div class="pw-bar" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
<p class="pw-result" aria-live="polite"><strong class="pw-label">Escribe una contraseña</strong><span class="pw-time">Cumple la lista de abajo.</span></p>
<ul class="pw-checks">
<li data-check="len">Al menos 8 caracteres</li>
<li data-check="lower">Una letra minúscula</li>
<li data-check="upper">Una letra MAYÚSCULA</li>
<li data-check="digit">Un número</li>
<li data-check="symbol">Un símbolo especial (# $ % & * ! ?)</li>
<li data-check="personal">Sin tu nombre, usuario, años ni palabras comunes</li>
</ul>
`;
 const host=input.closest('.field')||input.parentNode;
 host.append(box);host.classList.add('pw-field');
 const help=host.querySelector(':scope > small');if(help&&!optional)help.hidden=true;
 const label=box.querySelector('.pw-label'),time=box.querySelector('.pw-time');
 const update=()=>{
  const v=input.value;const personal=personalData(input);const r=strength(v,personal);
  box.dataset.level=String(r.level);
  const lowerV=norm(v);
  const hasPersonal=v&&[...COMMON,...personal.flatMap(p=>String(p).split(/[\s@._+-]+/))].some(w=>{w=norm(w);return [...w].length>=3&&(lowerV.includes(w)||v.toLowerCase().includes(w));});
  const checks={len:[...v].length>=MIN_LEN,lower:/[a-zñáéíóúü]/.test(v),upper:/[A-ZÑÁÉÍÓÚÜ]/.test(v),digit:/[0-9]/.test(v),symbol:/[^a-zA-Z0-9ñÑáéíóúüÁÉÍÓÚÜ\s]/.test(v),personal:!!v&&!hasPersonal};
  for(const[k,ok]of Object.entries(checks))box.querySelector(`[data-check="${k}"]`).classList.toggle('ok',ok);
  if(!v){label.textContent=optional?'Sin cambios':'Escribe una contraseña';time.textContent=optional?'Déjala vacía para conservar la actual.':'Cumple la lista de abajo.';input.setCustomValidity('');return;}
  label.textContent=r.label;
  time.textContent=r.years>=MIN_YEARS?'¡Bien! Es una contraseña segura.':'Todavía es fácil de adivinar.';
  const names={len:'al menos 8 caracteres',upper:'una letra mayúscula',lower:'una letra minúscula',digit:'un número',symbol:'un símbolo especial'};
  const missing=['len','upper','lower','digit','symbol'].filter(k=>!checks[k]).map(k=>names[k]);
  input.setCustomValidity(missing.length?`A tu contraseña le falta: ${missing.join(', ')}.`:(r.years>=MIN_YEARS?'':'Tu contraseña es muy fácil de adivinar. Hazla más larga y evita tu nombre, años o palabras comunes.'));
 };
 input.addEventListener('input',update);
 if(input.form){
  for(const name of PERSONAL_FIELDS){const el=input.form.elements.namedItem(name);if(el&&el.addEventListener)el.addEventListener('input',update);}
  input.form.addEventListener('reset',()=>setTimeout(update));
 }
 update();
}

function setupMatch(input){
 const form=input.form;if(!form)return;
 const target=form.elements.namedItem(input.dataset.match);if(!target)return;
 const msg=document.createElement('small');msg.className='pw-match';msg.setAttribute('aria-live','polite');
 (input.closest('.field')||input.parentNode).append(msg);
 const update=()=>{
  if(!input.value){msg.textContent='';msg.className='pw-match';input.setCustomValidity('');return;}
  const ok=input.value===target.value;
  msg.textContent=ok?'✓ Las contraseñas coinciden.':'✗ Las contraseñas todavía no coinciden.';
  msg.className='pw-match '+(ok?'ok':'bad');
  input.setCustomValidity(ok?'':'Las contraseñas no coinciden.');
 };
 input.addEventListener('input',update);target.addEventListener('input',update);
 form.addEventListener('reset',()=>setTimeout(update));
}

function init(){
 document.querySelectorAll('input[data-strength], input[data-match]').forEach(addToggle);
 document.querySelectorAll('input[data-strength]').forEach(setupMeter);
 document.querySelectorAll('input[data-match]').forEach(setupMatch);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
