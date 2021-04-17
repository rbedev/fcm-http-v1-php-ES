<?php

/**
 * Se añade el archivo autoload para cargar las librerias de Google API Client
 */
require_once './vendor/autoload.php';

/**
 * Clase usada para enviar las notificaciones push a los dispositivos con un token registrado en la base de datos.
 * Se ha actualizado la clase para adaptarla a la versión HTTP V1 de Firebase.
 * Para saber más:
 * https://firebase.google.com/docs/cloud-messaging/migrate-v1
 */
final class FirebaseCloudMessaging
{

	/**
	 * ID del projecto en Firebase. 
	 * Se obtiene siguiendo los siguientes pasos:
	 * -Entrar en: https://console.firebase.google.com/
	 * -Seleccionar el projecto sobre el que realizar la configuración. Importante tener activado el servicio de Firebase Cloud Messaging
	 * -En la página del proyecto (https://console.firebase.google.com/project/{PROJECT_ID}/overview), 
	 * click en la esquina superior izquierda en el botón de la tuerca de configuración.
	 * -En el popup, entrar en "Configuración del proyecto".
	 * -En la vista, copiar el contenido en "General" de "ID del proyecto".
	 * -Dar valor a esta constante.
	 * 
	 */
	private const PROJECT_ID = "";

	/**
	 * URL para realizar la petición POST para enviar la notificación
	 */
	private const FCM_URL = "https://fcm.googleapis.com/v1/projects/" . self::PROJECT_ID . "/messages:send";

	/**
	 * ID del projecto en Firebase. 
	 * Se obtiene siguiendo los siguientes pasos:
	 * -Entrar en: https://console.firebase.google.com/
	 * -Seleccionar el projecto sobre el que realizar la configuración. Importante tener activado el servicio de Firebase Cloud Messaging
	 * -En la página del proyecto (https://console.firebase.google.com/project/{PROJECT_ID}/overview), 
	 * click en la esquina superior izquierda en el botón de la tuerca de configuración.
	 * -En el popup, entrar en "Configuración del proyecto".
	 * -En la vista, entrar en el tab "Cuentas de servicio"
	 * -Click en "Generar nueva clave privada"
	 * -Colocar el archivo descargado en la carpeta config de la API
	 * -Dar valor a esta constante con el nombre del archivo y la ruta dentro del proyecto
	 */
	private const GOOGLE_APPLICATION_CREDENTIALS = "./config/{file_name}.json";


	/**
	 * Se le envía por parámetros un array/string que contiene los tokens que reciben el mensaje.
	 * El segundo parámetro contiene el título de la notificación y el tercero el body.
	 */
	public static function sendNotification($device_token, $title, $message)
	{
		// Se crea el array con el contenido de la notificación.
		$message = self::createNotificationMessage($device_token, $title, $message);

		// Se obtiene el Oauth 2.0 token para añadirlo en la petición a la API de FCM
		$token = self::getOAuthToken();

		if ($token !== null) {

			// Se abre la conexión CURL
			$curlRequest = curl_init();
			// Se establece la URL y se indica que es de tipo POST
			curl_setopt($curlRequest, CURLOPT_URL, self::FCM_URL);
			curl_setopt($curlRequest, CURLOPT_POST, true);
			// Se añade Oauth 2.0 token
			curl_setopt($curlRequest, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token", 'Content-Type:application/json'));
			curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, true);
			// Disabling SSL Certificate support temporarly
			curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curlRequest, CURLOPT_POSTFIELDS, json_encode($message));
			// Se ejecuta la petición
			$result = curl_exec($curlRequest);
			// Se cierra la conexión CURL
			curl_close($curlRequest);

			// Se devuelve el resultado. 
			return $result;
		} else {
			return "No se ha podido obtener el Oauth 2.0 token";
		}
	}

	/**
	 * Se devuelve el array con el contenido de la notificación.
	 */
	private static function createNotificationMessage($device_token, $title, $message)
	{
		return [
			'message' => [
				'token' => $device_token,
				'notification' => [
					'body' => $message,
					'title' =>  $title,
				],
			],
		];
	}

	/**
	 * Con la configuración del archivo .json, se pide un token para poder realizar la petición a la API de FCM
	 */
	private static function getOAuthToken()
	{
		try {
			// Se crea el cliente de Google
			$client = new Google_Client();
			// Se le añade la configuración con el archivo descargado desde Firebase
			$client->setAuthConfig(self::GOOGLE_APPLICATION_CREDENTIALS);
			// Se indica el Scope del cliente
			$client->addScope(Google_Service_FirebaseCloudMessaging::CLOUD_PLATFORM);

			// En este punto se puede obtener el token que podemos haber guardado en una tabla en la base de datos, por ejemplo. 
			// Ahora mismo, devuelve nulo para poder obtener el token nuevo cada petición.
			$savedTokenJson =  self::readAuthToken();

			if ($savedTokenJson !== null) {
				// Existe un token y se le añade al cliente para comprobar que es valido y no ha expirado
				$client->setAccessToken($savedTokenJson);
				if ($client->isAccessTokenExpired()) {
					// Si el token ha expirado, se genera uno nuevo
					$accessToken =  self::generateOAuthToken($client);
					$client->setAccessToken($accessToken);
				}
			} else {
				// No se tiene un token aun y se genera uno
				$accessToken = self::generateOAuthToken($client);
				$client->setAccessToken($accessToken);
			}

			// De la respuesta, queremos solo el atributo "access_token".
			$oauthToken = $accessToken["access_token"];

			return $oauthToken;
		} catch (Google_Exception $e) {
			return "Exception";
		}
	}

	/**
	 * Se genera un nuevo token para asignarselo al cliente.
	 */
	private static function generateOAuthToken($client)
	{
		$client->fetchAccessTokenWithAssertion();
		$accessToken = $client->getAccessToken();

		// En este momento se puede guardar el contenido del token en la base de datos
		self::saveAuthToken($accessToken);

		// Se devuelve el token
		return $accessToken;
	}

	/**
	 * Función que buscaría en la base de datos el token guardado obtenido en una petición anterior.
	 */
	private static function readAuthToken()
	{
		return null;
	}

	/**
	 * Esta función es la encargada de guardar el token en la base de datos o en algún lugar seguro del servidor.
	 */
	private static function saveAuthToken($accessToken)
	{
		// $tokenJson = json_encode($accessToken);
	}
}
