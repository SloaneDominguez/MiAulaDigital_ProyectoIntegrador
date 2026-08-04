document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formRegistroDocente');

  if (!form) return;

  const btnEnviar = document.querySelector('.btn-register');

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

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const password = document.getElementById('password').value;

    const confirmar = document.getElementById('confirmar_password').value;

    const correo = document.getElementById('correo').value.trim();

    const usuario = document.getElementById('usuario').value.trim();

    if (!form.checkValidity()) {
      mostrarAlerta('Completa todos los campos obligatorios.', 'error');

      return;
    }

    if (password.length < 8) {
      mostrarAlerta('La contraseña debe tener mínimo 8 caracteres.', 'error');

      return;
    }

    if (password !== confirmar) {
      mostrarAlerta('Las contraseñas no coinciden.', 'error');

      return;
    }

    const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regexCorreo.test(correo)) {
      mostrarAlerta('Correo electrónico inválido.', 'error');

      return;
    }

    const regexUsuario = /^[a-zA-Z0-9._]+$/;

    if (!regexUsuario.test(usuario)) {
      mostrarAlerta('Usuario inválido.', 'error');

      return;
    }

    btnEnviar.disabled = true;

    btnEnviar.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

    const datos = new FormData(form);

    try {
      const respuesta = await fetch('./process/registro_docente.php', {
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

      mostrarAlerta('No se pudo conectar con el servidor.', 'error');
    } finally {
      btnEnviar.disabled = false;

      btnEnviar.innerHTML =
        '<i class="fa-solid fa-paper-plane"></i> Enviar solicitud';
    }
  });
});
