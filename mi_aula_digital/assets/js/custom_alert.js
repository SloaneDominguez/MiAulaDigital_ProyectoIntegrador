document.addEventListener('DOMContentLoaded', () => {
  mostrarAlerta('Sistema de alertas cargado correctamente', 'success');
});

(function () {
  const style = document.createElement('style');

  style.innerHTML = `

.alert-container{
    position:fixed;
    top:25px;
    right:25px;
    width:350px;
    z-index:999999;
}


.alert-system{

    display:flex;
    align-items:center;
    gap:12px;

    padding:15px 18px;

    margin-bottom:12px;

    background:#ffffff;

    border-left:5px solid;

    border-radius:12px;

    box-shadow:0 10px 25px rgba(0,0,0,.15);

    font-family:'Segoe UI',sans-serif;

    animation:alertEntrada .3s ease;

}


.alert-icon{

    width:35px;
    height:35px;

    display:flex;
    justify-content:center;
    align-items:center;

    border-radius:50%;

    font-weight:bold;

}



.alert-content{

    display:flex;

    flex-direction:column;

}



.alert-title{

    font-weight:700;

    color:#0f172a;

    font-size:14px;

}



.alert-message{

    color:#64748b;

    font-size:13px;

    margin-top:3px;

}




.alert-success{

    border-left-color:#16a34a;

}


.alert-success .alert-icon{

    background:#dcfce7;

    color:#15803d;

}




.alert-error{

    border-left-color:#b91c1c;

}



.alert-error .alert-icon{

    background:#fee2e2;

    color:#b91c1c;

}





.alert-warning{

    border-left-color:#d97706;

}



.alert-warning .alert-icon{

    background:#fef3c7;

    color:#b45309;

}




.alert-info{

    border-left-color:#2563eb;

}



.alert-info .alert-icon{

    background:#dbeafe;

    color:#2563eb;

}




.alert-out{

    opacity:0;

    transform:translateX(40px);

    transition:.3s;

}




@keyframes alertEntrada{

from{

    opacity:0;

    transform:translateX(40px);

}

to{

    opacity:1;

    transform:translateX(0);

}

}


`;

  document.head.appendChild(style);

  window.mostrarAlerta = function (mensaje, tipo = 'info') {
    let contenedor = document.querySelector('.alert-container');

    if (!contenedor) {
      contenedor = document.createElement('div');

      contenedor.className = 'alert-container';

      document.body.appendChild(contenedor);
    }

    let icono = 'i';

    switch (tipo) {
      case 'success':
        icono = '✓';

        break;

      case 'error':
        icono = '✕';

        break;

      case 'warning':
        icono = '⚠';

        break;
    }

    const alerta = document.createElement('div');

    alerta.className = `alert-system alert-${tipo}`;

    alerta.innerHTML = `

            <div class="alert-icon">

                ${icono}

            </div>


            <div class="alert-content">

                <span class="alert-title">

                    Mi Aula Digital

                </span>


                <span class="alert-message">

                    ${mensaje}

                </span>


            </div>

        `;

    contenedor.appendChild(alerta);

    setTimeout(() => {
      alerta.classList.add('alert-out');

      setTimeout(() => {
        alerta.remove();
      }, 300);
    }, 4000);
  };
})();
