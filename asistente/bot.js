document.addEventListener("DOMContentLoaded", () => {
  const robot = document.getElementById("robot-img");
  const chat = document.getElementById("chat-container");
  const cerrar = document.getElementById("cerrar-chat");
  const enviar = document.getElementById("chat-enviar");
  const input = document.getElementById("chat-input");
  const mensajes = document.getElementById("chat-mensajes");

  let abierto = false;

  robot.addEventListener("click", () => {
    if (!abierto) {
      chat.classList.remove("hidden");
      setTimeout(() => chat.classList.add("visible"), 10);
      abierto = true;

      const saludos = [
        "¡Hola! Soy Teodoro 🤖, tu asistente. 😄 ¿Cómo estás hoy?",
        "¡Hey! Qué gusto verte 😎. Pregúntame lo que quieras.",
        "¡Holi! ✨ Estoy listo para ayudarte paso a paso."
      ];
      agregarMensaje("🤖 " + saludos[Math.floor(Math.random() * saludos.length)]);
    } else {
      chat.classList.remove("visible");
      setTimeout(() => chat.classList.add("hidden"), 300);
      abierto = false;
    }
  });

  cerrar.addEventListener("click", () => {
    chat.classList.remove("visible");
    setTimeout(() => chat.classList.add("hidden"), 300);
    abierto = false;
  });

  enviar.addEventListener("click", enviarMensaje);
  input.addEventListener("keypress", e => {
    if (e.key === "Enter") enviarMensaje();
  });

  function enviarMensaje() {
    const texto = input.value.trim();
    if (texto === "") return;
    agregarMensaje("🧍‍♂️ Tú: " + escapeHtml(texto));
    input.value = "";

    const pensando = document.createElement("p");
    pensando.className = "pensando";
    pensando.innerHTML = "🤖 Teodoro está pensando... 💭";
    mensajes.appendChild(pensando);
    mensajes.scrollTop = mensajes.scrollHeight;

    fetch("asistente/responder.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "mensaje=" + encodeURIComponent(texto)
    })
    .then(res => res.json())
    .then(data => {
      pensando.remove();
      // data.respuesta viene con <br> por nl2br, lo colocamos en HTML
      agregarMensaje("🤖 " + (data.respuesta || "Ocurrió un error al obtener la respuesta."));
    })
    .catch(() => {
      pensando.remove();
      agregarMensaje("🤖 Ocurrió un error 😅");
    });
  }

  function agregarMensaje(msj) {
    const p = document.createElement("p");
    p.innerHTML = msj;
    mensajes.appendChild(p);
    mensajes.scrollTop = mensajes.scrollHeight;
  }

  // Evitar inyección simple
  function escapeHtml(text) {
    return text.replace(/[&<>"'`=\/]/g, function (s) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
      }[s];
    });
  }
});
