document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formRegistroTutor');

  if (!form) return;

  const btnEnviar = document.querySelector('.btn-register');

  /*
  =====================================
  MOSTRAR / OCULTAR CONTRASEÑAS
  =====================================
  */

  document.querySelectorAll('.toggle-password').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target);

      const icon = btn.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';

        icon.classList.remove('fa-eye');

        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';

        icon.classList.remove('fa-eye-slash');

        icon.classList.add('fa-eye');
      }
    });
  });

  /*
  =====================================
  RESTRICCIONES EN TIEMPO REAL
  =====================================
  */

  // Nombre: solo letras y espacios
  document.getElementById('nombre').addEventListener('input', function () {
    this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, '');
  });

  // Teléfono: solo números (10 dígitos)
  document.getElementById('telefono').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
  });

  // Usuario: letras, números, punto y guion bajo
  document.getElementById('usuario').addEventListener('input', function () {
    this.value = this.value.replace(/[^A-Za-z0-9._]/g, '');
  });

  /*
  =====================================
  ENVÍO DEL FORMULARIO
  =====================================
  */

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const nombre = document.getElementById('nombre').value.trim();

    const correo = document.getElementById('correo').value.trim();

    const telefono = document.getElementById('telefono').value.trim();

    const usuario = document.getElementById('usuario').value.trim();

    const password = document.getElementById('password').value;

    const confirmar = document.getElementById('confirmar_password').value;

    if (!form.checkValidity()) {
      mostrarAlerta('Completa correctamente todos los campos.', 'error');
      return;
    }

    /*
    =====================================
    VALIDAR NOMBRE
    =====================================
    */

    const regexNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/;

    if (!regexNombre.test(nombre)) {
      mostrarAlerta(
        'El nombre solo puede contener letras y espacios.',
        'error'
      );

      return;
    }

    /*
    =====================================
    VALIDAR CORREO
    =====================================
    */

    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regexCorreo.test(correo)) {
      mostrarAlerta('Correo electrónico inválido.', 'error');

      return;
    }

    /*
    =====================================
    VALIDAR TELÉFONO
    =====================================
    */

    if (!/^[0-9]{10}$/.test(telefono)) {
      mostrarAlerta(
        'El teléfono debe contener exactamente 10 dígitos.',
        'error'
      );

      return;
    }

    /*
    =====================================
    VALIDAR USUARIO
    =====================================
    */

    const regexUsuario = /^[A-Za-z0-9._]{4,20}$/;

    if (!regexUsuario.test(usuario)) {
      mostrarAlerta(
        'El usuario solo puede contener letras, números, punto y guion bajo.',
        'error'
      );

      return;
    }

    /*
    =====================================
    VALIDAR CONTRASEÑA
    =====================================
    */

    const regexPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,50}$/;

    if (!regexPassword.test(password)) {
      mostrarAlerta(
        'La contraseña debe contener al menos 8 caracteres, una mayúscula, una minúscula y un número.',
        'error'
      );

      return;
    }

    /*
    =====================================
    CONFIRMAR CONTRASEÑA
    =====================================
    */

    if (password !== confirmar) {
      mostrarAlerta('Las contraseñas no coinciden.', 'error');

      return;
    }

    /*
    =====================================
    ENVIAR DATOS
    =====================================
    */

    btnEnviar.disabled = true;

    btnEnviar.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Creando cuenta...';

    const datos = new FormData(form);

    try {
      const respuesta = await fetch('./process/registro_tutor.php', {
        method: 'POST',

        body: datos,
      });

      const resultado = await respuesta.json();

      if (resultado.success) {
        mostrarAlerta(resultado.message, 'success');

        form.reset();

        setTimeout(() => {
          window.location.href = 'index.php';
        }, 2500);
      } else {
        mostrarAlerta(resultado.message, 'error');
      }
    } catch (error) {
      console.error(error);

      mostrarAlerta('No fue posible conectar con el servidor.', 'error');
    } finally {
      btnEnviar.disabled = false;

      btnEnviar.innerHTML =
        '<i class="fa-solid fa-user-plus"></i> Crear cuenta';
    }
  });
});
