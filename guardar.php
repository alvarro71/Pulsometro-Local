<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bpm = isset($_POST['bpm']) ? $_POST['bpm'] : null;
    $battery = isset($_POST['battery']) ? $_POST['battery'] : null;
    
    // Guardamos los datos en los archivos
    if ($bpm !== null) {
        file_put_contents("bpm.txt", $bpm);  // Guardamos el BPM en bpm.txt
    }

    if ($battery !== null && is_numeric($battery)) {
        file_put_contents("battery.txt", $battery);  // Guardamos el nivel de batería en battery.txt
    }

    echo json_encode(['status' => 'success']);
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Leer los archivos bpm.txt y battery.txt
    $bpm = file_get_contents('bpm.txt');
    $battery = file_get_contents('battery.txt');

    // Responder con los valores leídos de los archivos
    echo json_encode(['status' => 'success', 'bpm' => $bpm, 'battery' => $battery]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>
