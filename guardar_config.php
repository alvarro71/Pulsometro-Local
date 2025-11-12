<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $glowIntensity = $_POST['glowIntensity'];
    $glowColor = $_POST['glowColor'];
    $borderColor = $_POST['borderColor'];
    $soloNumero = $_POST['soloNumero'] ? 'true' : 'false';

    $configData = [
        'glowIntensity' => $glowIntensity,
        'glowColor' => $glowColor,
        'borderColor' => $borderColor,
        'soloNumero' => $soloNumero
    ];

    file_put_contents('config.txt', json_encode($configData));

    echo json_encode(['status' => 'success']);
}
?>
