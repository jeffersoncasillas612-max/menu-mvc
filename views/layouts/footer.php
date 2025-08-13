<script>
// ❌ Bloquear clic derecho
// Previene que el menú contextual aparezca al dar clic derecho
document.addEventListener('contextmenu', event => event.preventDefault());

// ❌ Bloquear combinaciones de teclas comunes para abrir herramientas de desarrollador
// Detecta F12, Ctrl+Shift+I, Ctrl+U, Ctrl+Shift+J, Ctrl+Shift+C y los bloquea
// Estas teclas se usan para abrir consola, ver código fuente o inspeccionar elementos

// Definimos combinaciones prohibidas
const forbiddenKeys = [
    { key: 'F12' },
    { ctrlKey: true, shiftKey: true, key: 'I' },
    { ctrlKey: true, key: 'u' },
    { ctrlKey: true, shiftKey: true, key: 'J' },
    { ctrlKey: true, shiftKey: true, key: 'C' }
];

// Detectamos cada pulsación de tecla
document.addEventListener('keydown', function (event) {
    for (const combo of forbiddenKeys) {
        const match = Object.keys(combo).every(k => event[k] === combo[k]);
        if (match) {
            event.preventDefault();
            return false;
        }
    }
});

// ⚠️ Anti-inspector visual
// Si se abre el inspector de elementos, vaciamos el contenido visible y lo reemplazamos con un mensaje
(function () {
    let contenidoOriginal = document.body.innerHTML; // Guardamos el contenido real
    let oculto = false; // Estado actual de visibilidad

    // Detectar consola abierta usando manipulación de objetos
    function detectarInspeccion() {
        const devtools = /./;
        devtools.toString = function () {
            ocultarContenido();
        };
        console.log('%c', devtools); // Dispara la detección
    }

    // Mostrar mensaje de inspección bloqueada
    function ocultarContenido() {
        if (!oculto) {
            document.body.innerHTML = `
<div id="bloqueo" style="
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: radial-gradient(circle at center, #fdfdfd, #d1d5db);
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    z-index: 999999;
    animation: fadeIn 1s ease-in-out;
    font-family: 'Segoe UI', sans-serif;
">
    <div style="
        background: white;
        padding: 40px 50px;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        text-align: center;
        animation: zoomIn 0.6s ease-in-out;
    ">
        <div style="
            font-size: 80px;
            color: #dc2626;
            animation: pulse 1.2s infinite;
        ">🚫</div>
        <h2 style="color: #1f2937; margin-bottom: 10px; font-size: 24px;">¡Acción no permitida!</h2>
        <p style="color: #4b5563; font-size: 16px;">
            Por motivos de seguridad, se ha deshabilitado la inspección de elementos.
        </p>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes zoomIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>
`;


            oculto = true;
        }
    }

    // Restaurar la página al cerrar el inspector (evita errores por contenido vaciado)
    function restaurarContenido() {
        if (oculto) {
            location.reload(); // Recargar para restaurar contenido y funcionalidad
        }
    }

    // Revisamos cada medio segundo si el inspector está abierto
    setInterval(() => {
        const widthThreshold = window.outerWidth - window.innerWidth > 160;
        const heightThreshold = window.outerHeight - window.innerHeight > 160;
        const isDevToolsOpen = widthThreshold || heightThreshold;

        if (isDevToolsOpen) {
            ocultarContenido();
        } else {
            restaurarContenido();
        }
    }, 50);
})();

// ⏪ Evitar que el usuario navegue hacia atrás después del logout
// Esto evita que al cerrar sesión y dar clic en el botón "atrás", se pueda acceder a una página anterior de forma visual
if (window.history && window.history.pushState) {
    window.history.pushState(null, "", window.location.href);
    window.onpopstate = function () {
        window.history.pushState(null, "", window.location.href);
    };
}
</script>

</body>
</html>
