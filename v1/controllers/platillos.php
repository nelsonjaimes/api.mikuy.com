<?php

require_once 'data/MysqlManager.php';

class platillos {

    public static function get($urlSegments) {
                //parametro en blanco
            if (!isset($urlSegments[0])) {
            throw new ApiException(
                400,
                0,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados");
            }
         //determinamos la accion a realizar
        switch ($urlSegments[0]) {
            case 'listaplatos':
                  return self::getListaPlatos();
              break;

           
            default:
               throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento \"platillos/$urlSegments[0]\".");
        }


    }

    public static function post($urlSegments) {

       
    }

    public static function put($urlSegments) {

    }

    public static function delete($urlSegments) {

    }


    
    /*---- Retorno de las  platos completos -- */
     private static function getListaPlatos(){

       try{ 
             $pdo=MysqlManager::get()->getDb(); 
             $consulta = "SELECT id_Plato ,
                                 nom_Plato ,
                                 precio_Plato,
                                 stock_Plato,
                                 desc_Plato,
                                 id_Categoria
                                 FROM  tbl_platos";
              
               $preparedSentence = $pdo->prepare($consulta);
                if($preparedSentence->execute()){
                   $lsPlatos=$preparedSentence->fetchAll(PDO::FETCH_ASSOC); 
                    return $lsPlatos;
                } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
              }

              $pdo->close();      

           } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al consultar el plato: " . $e->getMessage());
            }      
        

     }

}

?>