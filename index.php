<?php
// Leer valores actuales (si hay valores guardados previamente)
$bpm = @file_get_contents("bpm.txt");
$battery = @file_get_contents("battery.txt");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Control Pulsómetro Bluetooth</title>
  <style>
    /* Estilos generales */
    body {
      background-color: #111;
      color: #0f0;
      font-family: 'Courier New', monospace;
      padding: 2em;
      text-align: center;
      position: relative;
      transition: background-color 0.5s ease;
    }

    h1 {
      font-size: 2.5em;
      margin-bottom: 1em;
      text-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
    }

    button {
      font-size: 1.2em;
      margin: 1.5em 0;
      padding: 0.8em 2em;
      background-color: #333;
      color: #fff;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: #555;
    }

    .data {
      font-size: 2em;
      margin-top: 2em;
      padding: 20px;
      background: rgba(0, 0, 0, 0.7);
      border-radius: 10px;
      display: inline-block;
    }

    .warning {
      color: red;
      font-size: 1.5em;
      margin-bottom: 1.5em;
    }

    #bpm, #battery {
      padding: 10px 20px;
      border-radius: 10px;
      background-color: rgba(0, 255, 0, 0.3);
      box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
      transition: background 0.5s ease;
    }

    #battery {
      margin-top: 1em;
    }

    /* Animaciones de temblor ajustadas */
    @keyframes shake {
      0% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      50% { transform: translateX(10px); }
      75% { transform: translateX(-10px); }
      100% { transform: translateX(0); }
    }

    @keyframes shake2 {
      0% { transform: translateX(0); }
      25% { transform: translateX(-20px); }
      50% { transform: translateX(20px); }
      75% { transform: translateX(-20px); }
      100% { transform: translateX(0); }
    }

    @keyframes shake3 {
      0% { transform: translateX(0); }
      25% { transform: translateX(-30px); }
      50% { transform: translateX(30px); }
      75% { transform: translateX(-30px); }
      100% { transform: translateX(0); }
    }

    .background-bpm {
      background: linear-gradient(to right, #32CD32, #ff6347); /* Verde a rojo */
      padding: 10px;
      border-radius: 10px;
    }

    /* Estilos para el número de BPM debajo */
    #bpm-number {
      font-family: 'Courier New', monospace;
      font-size: 72px;
      color: #fff;
      margin-top: 100px; /* Gran espacio entre el gráfico y el número */
      text-shadow: 0 0 10px #ffffff, 0 0 20px #ffffff;
    }
  </style>
</head>
<body>
  <h1>Conectar Pulsómetro Bluetooth</h1>

  <div id="aviso" class="warning"></div>

  <!-- Botones de configuración -->
  <button onclick="conectar()">Seleccionar Dispositivo</button>
  <button onclick="toggleBattery()">Ocultar/Mostrar Batería</button>
  <button onclick="cambiarFondo()">Cambiar Fondo</button>

  <!-- Opciones de configuración adicional -->
  <div class="slider-container">
    <label for="glow">Intensidad del Glow: </label>
    <input type="range" id="glow" name="glow" min="0" max="100" value="50">
    <p>Valor Actual: <span id="glowValue">50</span></p>
  </div>

  <div>
    <label for="soloNumero">Solo Número (sin fondo): </label>
    <input type="checkbox" id="soloNumero" name="soloNumero">
  </div>

  <div>
    <label for="glowColor">Color del Glow: </label>
    <input type="color" id="glowColor" name="glowColor" value="#00ff00">
  </div>

  <div>
    <label for="borderColor">Color del Cuadro: </label>
    <input type="color" id="borderColor" name="borderColor" value="#00ff00">
  </div>

  <div>
    <label for="animacion">Desactivar Animación: </label>
    <input type="checkbox" id="animacion" name="animacion">
  </div>

  <div id="estado">Esperando conexión...</div>
  <div class="data" id="datos">
    <p>BPM: <span id="bpm"><?php echo $bpm ?: "Esperando..."; ?></span></p>
    <p id="battery" style="display: block;">Batería: <span><?php echo $battery ?: "Esperando..."; ?>%</span></p>
  </div>

  <!-- Añadir el número de BPM en la parte inferior -->
  <div id="bpm-number">--- BPM ---</div>

  <!-- Añadir el botón de "Aplicar cambios" -->
  <button onclick="aplicarCambios()">Aplicar Cambios</button>

  <script>
    let bpmChar, batteryChar;
    let dispositivo, servidor;

    // Comprobar si Web Bluetooth API está disponible
    if (!navigator.bluetooth) {
      document.getElementById("aviso").innerText = "¡Atención! Web Bluetooth API no está disponible en este navegador.";
      document.querySelector("button").disabled = true;
    }

    // Conectar al dispositivo Bluetooth
    async function conectar() {
      try {
        dispositivo = await navigator.bluetooth.requestDevice({
          acceptAllDevices: true,
          optionalServices: ['heart_rate', 'battery_service']
        });
        
        servidor = await dispositivo.gatt.connect();
        document.getElementById("estado").innerText = "Dispositivo conectado";

        const servicioBPM = await servidor.getPrimaryService('heart_rate');
        bpmChar = await servicioBPM.getCharacteristic('heart_rate_measurement');
        bpmChar.startNotifications();
        bpmChar.addEventListener('characteristicvaluechanged', manejarBPM);

        const servicioBat = await servidor.getPrimaryService('battery_service');
        batteryChar = await servicioBat.getCharacteristic('battery_level');
        manejarBateria();
        setInterval(manejarBateria, 5000);

      } catch (err) {
        document.getElementById("estado").innerText = "Error: " + err;
      }
    }

    // Manejo de BPM
    function manejarBPM(event) {
      const valor = event.target.value;
      const bpm = valor.getUint8(1); // Aquí es donde se obtiene el valor de BPM

      if (!isNaN(bpm)) {
        document.getElementById("bpm").innerText = bpm;
        document.getElementById("bpm-number").innerText = bpm + " BPM"; // Actualizar el número de BPM abajo
      } else {
        document.getElementById("bpm").innerText = "Esperando...";
        document.getElementById("bpm-number").innerText = "--- BPM ---";
      }

      // Enviar los datos al servidor
      enviarAlServidor(bpm);
    }

    // Manejo de la batería
    async function manejarBateria() {
      const val = await batteryChar.readValue();
      const battery = val.getUint8(0);
      document.getElementById("battery").innerText = battery;

      const bpm = document.getElementById("bpm").innerText;
      enviarAlServidor(bpm, battery);
    }

    // Función para enviar datos al servidor
    function enviarAlServidor(bpm, battery = null) {
      fetch("guardar.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `bpm=${bpm}&battery=${battery !== null ? battery : ''}`
      });
    }

    // Función para mostrar u ocultar la batería
    function toggleBattery() {
      const batteryElement = document.getElementById("battery");
      if (batteryElement.style.display === "none") {
        batteryElement.style.display = "block";
      } else {
        batteryElement.style.display = "none";
      }
    }

    // Función para cambiar el fondo
    function cambiarFondo() {
      document.body.style.backgroundColor = prompt("Introduce un color para el fondo del texto:", "#111");
    }

    // Guardar la configuración del glow y los colores
    function guardarConfiguracion() {
      const glowValue = document.getElementById('glow').value;
      const glowColor = document.getElementById('glowColor').value;
      const borderColor = document.getElementById('borderColor').value;
      const soloNumero = document.getElementById('soloNumero').checked;
      const animacion = document.getElementById('animacion').checked;

      localStorage.setItem('glowIntensity', glowValue);
      localStorage.setItem('glowColor', glowColor);
      localStorage.setItem('borderColor', borderColor);
      localStorage.setItem('soloNumero', soloNumero);
      localStorage.setItem('animacion', animacion);

      alert("Configuración guardada!");
    }

    function aplicarCambios() {
      const glowValue = document.getElementById('glow').value;
      const glowColor = document.getElementById('glowColor').value;
      const borderColor = document.getElementById('borderColor').value;
      const soloNumero = document.getElementById('soloNumero').checked;
      const animacion = document.getElementById('animacion').checked;

      // Guardar en localStorage
      localStorage.setItem('glowIntensity', glowValue);
      localStorage.setItem('glowColor', glowColor);
      localStorage.setItem('borderColor', borderColor);
      localStorage.setItem('soloNumero', soloNumero);
      localStorage.setItem('animacion', animacion);

      // Guardar en el servidor (como ya lo haces)
      fetch('guardar_config.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `glowIntensity=${glowValue}&glowColor=${glowColor}&borderColor=${borderColor}&soloNumero=${soloNumero}&animacion=${animacion}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          alert('Configuración guardada correctamente!');
        } else {
          alert('Error al guardar la configuración.');
        }
      })
      .catch(error => {
        alert('Error de red. No se pudieron guardar los cambios.');
      });
    }

  </script>
</body>
</html>