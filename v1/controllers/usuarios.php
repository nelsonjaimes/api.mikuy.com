<?php

require_once 'data/MysqlManager.php';

/**
 * Controlador del recurso "/usuarios"
 */
class usuarios {

    public static function get($urlSegments) {

    }

    public static function post($urlSegments) {

        if (!isset($urlSegments[0])) {
            throw new ApiException(
                400,
                0,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados"
            );
        }

        switch ($urlSegments[0]) {
          
            case "login":
                return self::authUsuario();
                break;
            default:
                throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento \"usuarios/$urlSegments[0]\".");
        }
    }

    public static function put($urlSegments) {

    }

    public static function delete($urlSegments) {

    }

    

    private static function authUsuario() {

        // Obtener parámetros de la petición
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        // Controlar posible error de parsing JSON
        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa: " . json_last_error_msg());
            throw $internalServerError;
        }

        // Verificar integridad de datos
        if (!isset($decodedParameters["nom_user"]) ||
            !isset($decodedParameters["password_user"])
        ) {
            throw new ApiException(
                400,
                0,
                "Las credenciales del usuario deben estar definidas correctamente",
                "http://localhost",
                "El atributo \"id\" o \"password\" o ambos, están vacíos o no definidos"
            );
        }

        $userId = $decodedParameters["nom_user"];
        $password = $decodedParameters["password_user"];

        // Buscar usuario en la tabla
        $dbResult = self::findUsuarioByCredentials($userId, $password);

        // Procesar resultado de la consulta
        if ($dbResult != NULL) {
            return [
                 
                "cod_user" => $dbResult["id_Mozo"],
                "nom_user" => $dbResult["nom_Mozo"],
                "tipo_user" =>"mozo",
                "token_user" =>uniqid(rand(), TRUE)
                
            ];
        } else {
            throw new ApiException(
                400,
                0,
                "Número de identificación o contraseña no existen",
                "http://localhost",
                "Puede que no exista un usuario creado con el id \"$userId\" o que la contraseña \"$password\" sea incorrecta."
            );
        }
    }

    

    private static function findUsuarioByCredentials($userId, $password) {
        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia SELECT
            $sentence = "SELECT * FROM tbl_mozo 
                        WHERE nom_Mozo=?";

            // Preparar sentencia
            $preparedSentence = $pdo->prepare($sentence);
            $preparedSentence->bindParam(1, $userId, PDO::PARAM_INT);

            // Ejecutar sentencia
            if ($preparedSentence->execute()) {
                $usuarioData = $preparedSentence->fetch(PDO::FETCH_ASSOC);

                // Verificar contraseña
                if ($password==$usuarioData["dni_Mozo"]) {
                    return $usuarioData;
                } else {
                    return null;
                }

            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos.Usuarios:" . $pdo->errorInfo()[2]
                );
            }

        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al consultar el usuario: " . $e->getMessage());
        }
    }

}


?>