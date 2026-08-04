(function () {
  // =====================================
  // CREAR ESTILOS AUTOMÁTICAMENTE
  // =====================================

  const style = document.createElement('style');

  style.innerHTML = `


    .alert-container {

        position: fixed;

        top: 25px;

        right: 25px;

        width: 350px;

        z-index: 999999;

    }



    .alert-system {

        display: flex;

        align-items: center;

        gap: 12px;

        padding: 15px 18px;

        margin-bottom: 12px;

        background: #ffffff;

        border-left: 5px solid;

        border-radius: 12px;

        box-shadow: 0 10px 25px rgba(0,0,0,.15);

        font-family: 'Segoe UI', sans-serif;

        animation: alertEntrada .3s ease;

    }



    .alert-icon {

        width: 35px;

        height: 35px;

        display: flex;

        justify-content: center;

        align-items: center;

        border-radius: 50%;

        font-weight: bold;

        flex-shrink: 0;

    }



    .alert-content {

        display: flex;

        flex-direction: column;

    }



    .alert-title {

        font-weight: 700;

        color: #0f172a;

        font-size: 14px;

    }



    .alert-message {

        color: #64748b;

        font-size: 13px;

        margin-top: 3px;

    }



    /* =============================
       TIPOS DE ALERTA
    ============================= */



    .alert-success {

        border-left-color: #16a34a;

    }


    .alert-success .alert-icon {

        background: #dcfce7;

        color: #15803d;

    }





    .alert-error {

        border-left-color: #b91c1c;

    }


    .alert-error .alert-icon {

        background: #fee2e2;

        color: #b91c1c;

    }





    .alert-warning {

        border-left-color: #d97706;

    }


    .alert-warning .alert-icon {

        background: #fef3c7;

        color: #b45309;

    }





    .alert-info {

        border-left-color: #2563eb;

    }


    .alert-info .alert-icon {

        background: #dbeafe;

        color: #2563eb;

    }





    @keyframes alertEntrada {


        from {

            opacity:0;

            transform:translateX(40px);

        }


        to {

            opacity:1;

            transform:translateX(0);

        }


    }





    .alert-out {

        opacity:0;

        transform:translateX(40px);

        transition:.3s;

    }



    `;

  document.head.appendChild(style);

  // =====================================
  // DETECTAR TIPO DE MENSAJE
  // =====================================

  function detectarTipo(mensaje) {
    mensaje = String(mensaje).toLowerCase();

    if (
      mensaje.includes('correctamente') ||
      mensaje.includes('exitoso') ||
      mensaje.includes('guardado') ||
      mensaje.includes('creado') ||
      mensaje.includes('actualizado') ||
      mensaje.includes('eliminado')
    ) {
      return 'success';
    }

    if (
      mensaje.includes('error') ||
      mensaje.includes('fallo') ||
      mensaje.includes('falló') ||
      mensaje.includes('no se pudo') ||
      mensaje.includes('incorrecto')
    ) {
      return 'error';
    }

    if (
      mensaje.includes('selecciona') ||
      mensaje.includes('seleccione') ||
      mensaje.includes('complete') ||
      mensaje.includes('obligatorio') ||
      mensaje.includes('faltan')
    ) {
      return 'warning';
    }

    return 'info';
  }

  // =====================================
  // MOSTRAR ALERTA
  // =====================================

  function mostrarAlerta(mensaje) {
    let contenedor = document.querySelector('.alert-container');

    if (!contenedor) {
      contenedor = document.createElement('div');

      contenedor.className = 'alert-container';

      document.body.appendChild(contenedor);
    }

    const tipo = detectarTipo(mensaje);

    const alerta = document.createElement('div');

    alerta.className = `alert-system alert-${tipo}`;

    let icono = 'i';

    if (tipo === 'success') {
      icono = '✓';
    }

    if (tipo === 'error') {
      icono = '✕';
    }

    if (tipo === 'warning') {
      icono = '⚠';
    }

    alerta.innerHTML = `


            <div class="alert-icon">

                ${icono}

            </div>



            <div class="alert-content">


                <span class="alert-title">

                    Mi Aula Digital

                </span>



                <span class="alert-message">

                    ${mensaje ?? 'Proceso realizado correctamente'}

                </span>


            </div>



        `;

    contenedor.appendChild(alerta);

    setTimeout(() => {
      alerta.classList.add('alert-out');

      setTimeout(() => {
        alerta.remove();
      }, 400);
    }, 4000);
  }

  // =====================================
  // RECUPERAR ALERTA DESPUÉS DE RECARGA
  // =====================================

  document.addEventListener('DOMContentLoaded', () => {
    const mensajeGuardado = sessionStorage.getItem('alerta_sistema');

    if (mensajeGuardado) {
      sessionStorage.removeItem('alerta_sistema');

      setTimeout(() => {
        mostrarAlerta(mensajeGuardado);
      }, 400);
    }
  });

  // =====================================
  // REEMPLAZAR ALERT NATIVO
  // =====================================

  window.alert = function (mensaje) {
    sessionStorage.setItem(
      'alerta_sistema',

      mensaje
    );

    mostrarAlerta(mensaje);
  };

  window.mostrarAlerta = mostrarAlerta;
})();
