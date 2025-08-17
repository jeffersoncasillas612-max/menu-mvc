<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- ESTILOS -->
    <style>
        :root {
            --color-primario: #007bff;
            --color-hover: #0056b3;
            --fondo-general: #f0f2f5;
            --fondo-login: #fff;
            --borde: #ccc;
            --sombra: rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: var(--fondo-general);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: var(--fondo-login);
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 0 15px var(--sombra);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.8s ease-in-out;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
            color: #333;
        }

        .form-group {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid var(--borde);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 20px;
        }

        .form-group i {
            color: #888;
            margin-right: 10px;
        }

        .form-group input {
            border: none;
            outline: none;
            font-size: 16px;
            flex: 1;
            background: transparent;
        }

        .form-group .toggle-password {
            cursor: pointer;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: var(--color-primario);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        input[type="submit"]:hover {
            background: var(--color-hover);
        }

        .extra-links {
            text-align: center;
            margin-top: 12px;
        }

        .extra-links a {
            color: var(--color-primario);
            font-size: 0.9em;
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 20px 15px;
            }
        }

        /* --- Descargar App --- */
.download-app { 
    margin-top: 16px; 
    text-align: center;
}

.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    background: #16a34a;              /* verde */
    border-radius: 8px;
    color: #fff;
    font-weight: 700;
    text-decoration: none;
    transition: opacity .2s ease;
}
.btn-download:hover { opacity: .9; }

.btn-download small {
    font-weight: 600;
    opacity: .9;
}

.download-error {
    margin-top: 8px;
    font-size: .9rem;
    color: #b91c1c;                    /* rojo suave */
}

    </style>
</head>
<body>

    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form method="POST" action="index.php?c=<?= base64_encode('login') ?>&a=<?= base64_encode('verificar') ?>">
            <div class="form-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="correo" placeholder="Correo electrónico" required>
            </div>
            <div class="form-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="contrasena" id="password" placeholder="Contraseña" required>
                <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
            </div>
            <input type="submit" value="Ingresar">
            <div class="extra-links">
                <a href="index.php?vista=<?= base64_encode('usuarios/olvide_contrasena.php') ?>">¿Olvidaste tu contraseña?</a>
            </div>
        </form>

        <hr style="margin:18px 0;border:none;border-top:1px solid #eee">

<div class="download-app" id="appDownload" hidden>
  <a id="apkLink" class="btn-download" href="#" target="_blank" rel="noopener">
    <i class="fa-brands fa-android"></i>
    Descargar app Android <small id="apkVersion"></small>
  </a>
  <div id="apkChangelog" style="margin-top:6px;color:#555;font-size:.9rem;"></div>
</div>

<div class="download-app download-error" id="appDownloadFallback" hidden>
  No se pudo obtener la descarga automática.
  <a href="https://github.com/jeffersoncasillas612-max/ConDoc/releases" target="_blank" rel="noopener">
    Ir a lanzamientos
  </a>
</div>




    </div>

    <script>
  // Botón dinámico de descarga (lee el JSON público en Render)
  (async () => {
    try {
      // MISMA ORIGEN: si tu login se sirve en menu-mvc.onrender.com, basta ruta relativa:
      const url = "/condoc/version.json?ts=" + Date.now(); // evita caché
      const res = await fetch(url, { cache: "no-store" });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const data = await res.json();

      const android = data?.android;
      if (!android || !android.apkUrl) throw new Error("Sin campos android/apkUrl");

      // Rellena botón
      const link = document.getElementById("apkLink");
      const ver  = document.getElementById("apkVersion");
      const log  = document.getElementById("apkChangelog");

      link.href = android.apkUrl;
      ver.textContent = android.versionName ? `(v${android.versionName})` : "";
      if (android.changelog) {
        log.textContent = android.changelog.replaceAll("\\n", " · ");
      }

      document.getElementById("appDownload").hidden = false;
    } catch (e) {
      // Fallback visible si falla el fetch o el JSON
      document.getElementById("appDownloadFallback").hidden = false;
      console.error("Descarga APK:", e);
    }
  })();
</script>


    <script>
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");

        togglePassword.addEventListener("click", () => {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            togglePassword.classList.toggle("fa-eye");
            togglePassword.classList.toggle("fa-eye-slash");
        });
    </script>

    <?php include 'views/layouts/footer.php'; ?>
</body>
</html>
