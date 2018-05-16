<?php
require_once "View.php";
class JsonView extends View {

    public function render($body) {
         // Set de estado de le respuesta
        if (isset($body["status"])) {
            http_response_code($body["status"]);
        }

        header('Content-Type: application/json; charset=utf8');
        $jsonResponse = json_encode($body, JSON_PRETTY_PRINT, JSON_UNESCAPED_UNICODE);

        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON en JsonView.php. Causa: " . json_last_error_msg());
            throw $internalServerError;
        }
        echo $jsonResponse;
        exit;
    }
}