document.addEventListener('DOMContentLoaded', () => {
  const botonesRol = document.querySelectorAll('.role-btn');

  const rol = document.getElementById('rolSeleccionado');

  const usuario = document.getElementById('usuario');

  const password = document.getElementById('password');

  const togglePassword = document.getElementById('togglePassword');

  const labelUsuario = document.getElementById('labelUsuario');

  const labelPassword = document.getElementById('labelPassword');

  const registerOption = document.getElementById('registerOption');

  const registerLink = document.getElementById('registerLink');

  const loginForm = document.getElementById('loginForm');

  const btnLogin = loginForm.querySelector('.btn-login');

  // =================================
  // SELECCIÓN DE ROL
  // =================================

  botonesRol.forEach((boton) => {
    boton.addEventListener('click', () => {
      const tipo = boton.dataset.role;

      rol.value = tipo;

      botonesRol.forEach((btn) => btn.classList.remove('active'));

      boton.classList.add('active');

      // DIRECCIÓN
      if (tipo === 'Direccion') {
        labelUsuario.textContent = 'Usuario';

        labelPassword.textContent = 'Contraseña';

        usuario.type = 'text';

        usuario.placeholder = 'Ingresa tu usuario';

        password.type = 'password';

        password.placeholder = 'Ingresa tu contraseña';
        togglePassword.classList.remove('fa-eye-slash');
        togglePassword.classList.add('fa-eye');

        registerOption.style.display = 'none';
      }

      // DOCENTE
      if (tipo === 'Docente') {
        labelUsuario.textContent = 'Usuario';

        labelPassword.textContent = 'Contraseña';

        usuario.type = 'text';

        usuario.placeholder = 'Ingresa tu usuario';

        password.type = 'password';

        password.placeholder = 'Ingresa tu contraseña';
        togglePassword.classList.remove('fa-eye-slash');
        togglePassword.classList.add('fa-eye');

        registerOption.style.display = 'block';

        registerLink.onclick = (e) => {
          e.preventDefault();

          window.location.href = 'registro_docente.php';
        };
      }

      // PADRE / TUTOR
      if (tipo === 'PadreTutor') {
        labelUsuario.textContent = 'Usuario';

        labelPassword.textContent = 'Contraseña';

        usuario.type = 'text';

        usuario.placeholder = 'Ingresa tu usuario';

        password.type = 'password';

        password.placeholder = 'Ingresa tu contraseña';

        togglePassword.classList.remove('fa-eye-slash');
        togglePassword.classList.add('fa-eye');

        registerOption.style.display = 'block';

        registerLink.onclick = (e) => {
          e.preventDefault();

          window.location.href = 'registro_tutor.php';
        };
      }
    });
  });

  // =================================
  // LOGIN AJAX
  // =================================

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (rol.value === '') {
      mostrarAlerta('Selecciona un tipo de usuario.', 'error');

      return;
    }

    btnLogin.disabled = true;

    const textoOriginal = btnLogin.innerHTML;

    btnLogin.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Iniciando...';

    try {
      const datos = new FormData(loginForm);

      const respuesta = await fetch('process/login_process.php', {
        method: 'POST',

        body: datos,
      });

      const resultado = await respuesta.json();

      if (resultado.success) {
        mostrarAlerta(resultado.message, 'success');

        setTimeout(() => {
          window.location.href = resultado.redirect;
        }, 800);
      } else {
        mostrarAlerta(resultado.message, 'error');
      }
    } catch (error) {
      console.error(error);

      mostrarAlerta('Ocurrió un error al iniciar sesión.', 'error');
    } finally {
      btnLogin.disabled = false;

      btnLogin.innerHTML = textoOriginal;
    }
  });

  // =================================
  // MOSTRAR / OCULTAR CONTRASEÑA
  // =================================

  togglePassword.addEventListener('click', () => {
    if (password.type === 'password') {
      password.type = 'text';

      togglePassword.classList.remove('fa-eye');

      togglePassword.classList.add('fa-eye-slash');
    } else {
      password.type = 'password';

      togglePassword.classList.remove('fa-eye-slash');

      togglePassword.classList.add('fa-eye');
    }
  });
});
