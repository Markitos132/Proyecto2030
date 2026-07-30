<?php
require 'conexion.php';

$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos) {
    http_response_code(400);
    echo json_encode(["error" => "JSON inválido"]);
    exit;
}

if ($datos['tipo'] === 'medicion') {
    $stmt = $pdo->prepare("INSERT INTO mediciones 
        (session_id, individuo, especie, fecha, hora, temperatura, temp_min, temp_max, alerta)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $datos['session_id'],
        $datos['individuo'],
        $datos['especie'],
        $datos['fecha'],
        $datos['hora'],
        $datos['temperatura'],
        $datos['temp_min'],
        $datos['temp_max'],
        $datos['alerta']
    ]);
    
    echo json_encode(["ok" => true, "mensaje" => "Medición guardada"]);
}

if ($datos['tipo'] === 'fin_sesion') {
    $stmt = $pdo->prepare("UPDATE sesiones SET 
        estado = 'finalizada',
        hora_fin = ?
        WHERE session_id = ?");
    
    $stmt->execute([
        $datos['hora'],
        $datos['session_id']
    ]);
    
    echo json_encode(["ok" => true, "mensaje" => "Sesión finalizada"]);
}
?>