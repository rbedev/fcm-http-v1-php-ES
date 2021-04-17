<?php

/**
 * Inicialización para mostrar los errores
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Se añade la clase FirebaseCloudMessaging
require_once './firebasecloudmessaging/fcm.php';

// Para probar, se puede añadir manualmente el device_token obtenido en una aplicación móvil.
$device_token = "{token del dispositivo móvil}";

$response = FirebaseCloudMessaging::sendNotification($device_token, "Título", "Cuerpo de la notificación");

var_dump($response);
