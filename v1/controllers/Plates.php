<?php
require_once 'utils/Helper.php';
require_once 'data/MysqlManager.php';
class Plates {
    public static function get($urlSegments) {
            if (!isset($urlSegments[0])) {
            throw new ApiException(
                400,
                0,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados");
            }
        switch ($urlSegments[0]) {
            case PLATES_LIST : return self::getPlatesList();
              break;
            default:
               throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento Plates/".$urlSegments[0]);
        }
    }
     private static function getPlatesList(){
        $pdo=MysqlManager::get()->getDb(); 
        $consulta = "SELECT code,name,price,category FROM tbl_plates";
        $preparedSentence = $pdo->prepare($consulta);
        if($preparedSentence->execute()){
            return$preparedSentence->fetchAll(PDO::FETCH_ASSOC); 
           } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" .
                     $pdo->errorInfo()[2]
                );
              }
         $pdo->close();      
      }
}

?>