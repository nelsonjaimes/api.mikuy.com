<?php

require_once 'data/MysqlManager.php';


class preliquidacion {

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
            case 'listamesas':
                  return self::getListaMesasOcupadas();
              break;

           
            default:
               throw new ApiException(
                    404,
                    0,
                 "El recurso al que intentas acceder no existe",
                 "http://localhost", "No se encontró el segmento \"preliquidacion/$urlSegments[0]\".");
        }


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
            case "imprimir":
                return self::imprimirPreliquidacion();
                break;
            
            default:
                throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento \"preliquidacion/$urlSegments[0]\".");
        }
    }

    public static function put($urlSegments) {

    }

    public static function delete($urlSegments) {

    }

    private static function imprimirPreliquidacion() {
        // Obtener parámetros de la petición
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);

        // Controlar posible error de parsing JSON
        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500,
                0,
                "Error interno en el servidor22222. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa: " . json_last_error_msg());
            throw $internalServerError;
        }

        // Verificar integridad de datos
        // TODO: Implementar restricciones de datos adicionales
        if (!isset($decodedParameters["num_mesa"])) 
             
        {
            // TODO: Crear una excepción individual por cada causa anómala
            throw new ApiException(
                400,
                0,
                "Verifique los datos de la Preliquidacion tengan formato correcto",
                "http://localhost",
                "Uno de los atributos de la Preliquidacion no está definido en los parámetros");
        }

        

         $dbResult=self::insertarPreliquidacionDB($decodedParameters);

        // Procesar resultado de la inserción
        if ($dbResult) {
            return [ "status" => 201,
                     "message" =>"imprimiendo preliquidacion"];
        }  
        else {
            throw new ApiException(
                500,
                0,
                "Error del servidorImprimirComanda",
                "http://localhost",
                "Error en la base de datos al ejecutar:imprimir-Preliquidacion");
        }
    }


    private static function insertarPreliquidacionDB($decodedParameters){
         
         
         $PRELIQUIDACION_MESA=$decodedParameters["num_mesa"];
         $OBJETO_PELIQUIDACION=self::getIdPreliquidacionXMesa($PRELIQUIDACION_MESA);
         $PRELIQUIDACION_ID=$OBJETO_PELIQUIDACION["id_Pedido"];
         $PRELIQUIDACION_NUMCOPIA=(int)$OBJETO_PELIQUIDACION["N_copia"];
         $PRELIQUIDACION_NUMCOPIA++;


        try {

        $pdo = MysqlManager::get()->getDb();
        $sentence = "INSERT INTO tbl_preliquidacion_movil
                    (Numero_preliquidacion) VALUES (?)";

            // Preparar sentencia
            $preparedStament=$pdo->prepare($sentence); 
            $preparedStament->bindParam(1, $PRELIQUIDACION_ID);
            
            if($preparedStament->execute()){

               $sentencia2="UPDATE tbl_pedido SET N_Copia=? WHERE id_Pedido=?";
               $preparedStament2=$pdo->prepare($sentencia2);
               $preparedStament2->bindParam(1,$PRELIQUIDACION_NUMCOPIA);
               $preparedStament2->bindParam(2,$PRELIQUIDACION_ID);

               return $preparedStament2->execute();


            }    
                   

        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor-InsertarPedidoDB",
                "http://localhost",
                "Ocurrió el siguiente error al intentar insertar el PedidoDB: " . $e->getMessage());
              
           }
      
     
    }


     private static function getIdPreliquidacionXMesa($numeroMesa) {
        try {
            
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia SELECT
            $sentence = "SELECT id_Pedido, N_copia FROM tbl_pedido WHERE Mesa=?";

            // Preparar sentencia
            $preparedSentence = $pdo->prepare($sentence);
            $preparedSentence->bindParam(1, $numeroMesa, PDO::PARAM_INT);
            // Ejecutar sentencia
            if ($preparedSentence->execute()) {
                $idPreliquidacion = $preparedSentence->fetch(PDO::FETCH_ASSOC);
                return $idPreliquidacion;
                
               

            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor -ObtenerIdPreliquidacionDB",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
            }

        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor- Conexion getIdPedidoDB",
                "http://localhost",
                "Ocurrió el siguiente error al consultar el afiliado: " . $e->getMessage());
        }
    }


   
    private static function getListaMesasOcupadas(){

        try{
          
            $pdo=MysqlManager::get()->getDb(); 
            $sentence = "SELECT id_Pedido,
                                Mesa,
                                id_Mozo,
                                nom_Mozo,
                                Importe_Total,
                                N_copia
                             FROM tbl_pedido";

            $preparedSentence = $pdo->prepare($sentence);
            

                if($preparedSentence->execute()){

                $listaDePLatillosXCategoria = $preparedSentence->fetchAll(PDO::FETCH_ASSOC);  

                return $listaDePLatillosXCategoria;

                }
                else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
              }                              


             $pdo->close(); 

            /**--Finalizacion del metodo----*/    
            } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al consultar las Mesas ocupadas: " . $e->getMessage());
            }      

    }


  
   

}


?>