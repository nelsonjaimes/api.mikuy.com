<?php

require_once 'data/MysqlManager.php';


class comanda {

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
            case "imprimir":
                return self::imprimirComanda();
                break;
            
            default:
                throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento \"comanda/$urlSegments[0]\".");
        }
    }



    public static function put($urlSegments) {

    }

    public static function delete($urlSegments) {

    }

    private static function imprimirComanda() {
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
        if (!isset($decodedParameters["Mesa"]) ||
            !isset($decodedParameters["id_Mozo"])||
            !isset($decodedParameters["nom_Mozo"]) ||
            !isset($decodedParameters["Importe_Total"]) ||
            !isset($decodedParameters["fAtencion_Pedido"])||
            !isset($decodedParameters["Pax"])||
            !isset($decodedParameters["N_copia"])||
            !isset($decodedParameters["EST_DOC"])||
            !isset($decodedParameters["clienteTemp"])||
            !isset($decodedParameters["ls_platillospedido"]) ||
            !isset($decodedParameters["aumentar_pedido"])
             
            )
            

        {
            // TODO: Crear una excepción individual por cada causa anómala
            throw new ApiException(
                400,
                0,
                "Verifique los datos del afiliado tengan formato correcto",
                "http://localhost",
                "Uno de los atributos del afiliado no está definido en los parámetros");
        }

        $aumentar_pedido=$decodedParameters["aumentar_pedido"];
         if ($aumentar_pedido==0) {
                $dbResult=self::insertarPedidoDB($decodedParameters);

            }else{
            //el usuario a pedido mas platos
               $dbResult=self::ActualizarPedidoDB($decodedParameters);        
            }

      

        

        ;

        // Procesar resultado de la inserción
        if ($dbResult) {
            return ["status" => 201, "message" => "Imprimiendo comanda"];
        } else {
            throw new ApiException(
                500,
                0,
                "Error del servidorImprimirComanda",
                "http://localhost",
                "Error en la base de datos al ejecutar la inserción del afiliado.");
        }
    }


    private static function insertarPedidoDB($decodedParameters){
            // obteniendo los datos del Post
         $estado=true;
         $PEDIDO_ID=self::getIdPedido();
         $PEDIDO_NUMMESA=$decodedParameters["Mesa"];
         $PEDIDO_IDMOZO=$decodedParameters["id_Mozo"];
         $PEDIDO_NOMMOZO=$decodedParameters["nom_Mozo"];
         $PEDIDO_IMPORTETOTAL=$decodedParameters["Importe_Total"];
         $PEDIDO_FECHAHORA=$decodedParameters["fAtencion_Pedido"];
         $PEDIDO_NUMCOPIA=$decodedParameters["N_copia"];
         $PEDIDO_PAX=$decodedParameters["Pax"];
         $PEDIDO_ESTADO_DOCUMENTO=$decodedParameters["EST_DOC"];
         $PEDIDO_CLIENTETEMP=$decodedParameters["clienteTemp"];
         $PEDIDO_LSPLATILLOS=$decodedParameters["ls_platillospedido"];
         

        try {

        $pdo = MysqlManager::get()->getDb();
        $sentence = "INSERT INTO tbl_pedido (
                    id_Pedido, 
                    Mesa, 
                    id_Mozo,
                    nom_Mozo, 
                    Importe_Total, 
                    fAtencion_Pedido,
                    N_Copia,
                    Pax,
                    EST_DOC,
                    clienteTemp
                    )" .
                " VALUES (?,?,?,?,?,?,?,?,?,?)";

            // Preparar sentencia
            $preparedStament = $pdo->prepare($sentence); 
            $preparedStament->bindParam(1, $PEDIDO_ID);
            $preparedStament->bindParam(2, $PEDIDO_NUMMESA);
            $preparedStament->bindParam(3, $PEDIDO_IDMOZO);
            $preparedStament->bindParam(4, $PEDIDO_NOMMOZO);
            $preparedStament->bindParam(5, $PEDIDO_IMPORTETOTAL);
            $preparedStament->bindParam(6, $PEDIDO_FECHAHORA);
            $preparedStament->bindParam(7, $PEDIDO_NUMCOPIA);
            $preparedStament->bindParam(8, $PEDIDO_PAX);
            $preparedStament->bindParam(9, $PEDIDO_ESTADO_DOCUMENTO);
            $preparedStament->bindParam(10, $PEDIDO_CLIENTETEMP);  
               
           if($preparedStament->execute()){
                  
              $sentencia = "INSERT INTO tbl_pedido_detalle (
                                id_Pedido, 
                                EST_DOC,
                                id_Plato,
                                precio_Plato,
                                cantidad_Plato,
                                total_Plato,
                                obs_Plato
                                )  VALUES (?,?,?,?,?,?,?)";   


                $pdo2 = MysqlManager::get()->getDb();                   
                foreach ($PEDIDO_LSPLATILLOS as $platillo) {
                
                    $preparedStament2 = $pdo2->prepare($sentencia); 
                    $preparedStament2->bindParam(1,$PEDIDO_ID);
                    $preparedStament2->bindParam(2,$platillo["EST_DOC"]);
                    $preparedStament2->bindParam(3,$platillo["COD_PRD"]);
                    $preparedStament2->bindParam(4,$platillo["PRE_PRD"]);
                    $preparedStament2->bindParam(5,$platillo["cantidad_Plato"]);
                    $preparedStament2->bindParam(6,$platillo["totalPlato"]);
                    $preparedStament2->bindParam(7,$platillo["observacion"]);
                        

                     $preparedStament2->execute();

                 }

              $sentenciaComandaMovil="INSERT INTO tbl_comanda_movil(Numero_pedido) VALUES (?)";
              $preparedStament3=$pdo2->prepare($sentenciaComandaMovil);  
              $preparedStament3->bindParam(1,$PEDIDO_ID);  
              
               if($preparedStament3->execute()){

                 $sentenciaTblSerie="UPDATE tbl_serie_numeros 
                                    SET valor_Serie=? WHERE nom_Serie='PEDIDO'";
                 $prepareTblSerie=$pdo2->prepare($sentenciaTblSerie); 
                 $prepareTblSerie->bindParam(1,$PEDIDO_ID);                   
                 return $prepareTblSerie->execute();
              }   

            }

        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor-InsertarPedidoDB",
                "http://localhost",
                "Ocurrió el siguiente error al intentar insertar el PedidoDB: " . $e->getMessage());
        $estado=false;
        }


      return $estado;  

    }

    //el usuario esta aumentando los platillos 
    private static function ActualizarPedidoDB($decodedParameters){
            // obteniendo los datos del Post
         $estado=true;

         $PEDIDO_NUMMESA=$decodedParameters["Mesa"];
         $PEDIDO_IMPORTE1=(double) self::getImportePedidoActualizacion($PEDIDO_NUMMESA);
         $PEDIDO_IMPORTE2=(double)$decodedParameters["Importe_Total"];
         $PEDIDO_IMPORTE3=$PEDIDO_IMPORTE1+$PEDIDO_IMPORTE2; 

         $PEDIDO_ID=self::getIdActualizacion($PEDIDO_NUMMESA);

         $PEDIDO_IDMOZO=$decodedParameters["id_Mozo"];
         $PEDIDO_NOMMOZO=$decodedParameters["nom_Mozo"];
         $PEDIDO_FECHAHORA=$decodedParameters["fAtencion_Pedido"];
         $PEDIDO_NUMCOPIA=$decodedParameters["N_copia"];
         $PEDIDO_PAX=$decodedParameters["Pax"];
         $PEDIDO_ESTADO_DOCUMENTO=$decodedParameters["EST_DOC"];
         $PEDIDO_CLIENTETEMP=$decodedParameters["clienteTemp"];
         $PEDIDO_LSPLATILLOS=$decodedParameters["ls_platillospedido"];
         

        try {

        $pdo = MysqlManager::get()->getDb();

        $sentence="UPDATE tbl_pedido SET Importe_Total=?, N_Copia=0 WHERE Mesa=?";

        /*
        $sentence = "INSERT INTO tbl_pedido (
                    id_Pedido, 
                    Mesa, 
                    id_Mozo,
                    nom_Mozo, 
                    Importe_Total, 
                    fAtencion_Pedido,
                    N_Copia,
                    Pax,
                    EST_DOC,
                    clienteTemp
                    )" .
                " VALUES (?,?,?,?,?,?,?,?,?,?)"; */

            // Preparar sentencia
            $preparedStament = $pdo->prepare($sentence); 
            $preparedStament->bindParam(1, $PEDIDO_IMPORTE3);
            $preparedStament->bindParam(2, $PEDIDO_NUMMESA);

            /*
            $preparedStament->bindParam(3, $PEDIDO_IDMOZO);
            $preparedStament->bindParam(4, $PEDIDO_NOMMOZO);
            $preparedStament->bindParam(5, $PEDIDO_IMPORTETOTAL);
            $preparedStament->bindParam(6, $PEDIDO_FECHAHORA);
            $preparedStament->bindParam(7, $PEDIDO_NUMCOPIA);
            $preparedStament->bindParam(8, $PEDIDO_PAX);
            $preparedStament->bindParam(9, $PEDIDO_ESTADO_DOCUMENTO);
            $preparedStament->bindParam(10, $PEDIDO_CLIENTETEMP);  */ 



               
           if($preparedStament->execute()){
                  
              $sentencia = "INSERT INTO tbl_pedido_detalle (
                                id_Pedido, 
                                EST_DOC,
                                id_Plato,
                                precio_Plato,
                                cantidad_Plato,
                                total_Plato,
                                obs_Plato
                                )  VALUES (?,?,?,?,?,?,?)";   


                $pdo2 = MysqlManager::get()->getDb();                   
                foreach ($PEDIDO_LSPLATILLOS as $platillo) {
                
                    $preparedStament2 = $pdo2->prepare($sentencia); 
                    $preparedStament2->bindParam(1,$PEDIDO_ID);
                    $preparedStament2->bindParam(2,$platillo["EST_DOC"]);
                    $preparedStament2->bindParam(3,$platillo["COD_PRD"]);
                    $preparedStament2->bindParam(4,$platillo["PRE_PRD"]);
                    $preparedStament2->bindParam(5,$platillo["cantidad_Plato"]);
                    $preparedStament2->bindParam(6,$platillo["totalPlato"]);
                    $preparedStament2->bindParam(7,$platillo["observacion"]);
                        

                     $preparedStament2->execute();

                 }

                self::guardarEnTblPedidoActualizacion($PEDIDO_ID,$PEDIDO_LSPLATILLOS);    


              $sentenciaComandaMovil="INSERT INTO tbl_comanda_movil(Numero_pedido) VALUES (?)";
              $preparedStament3=$pdo2->prepare($sentenciaComandaMovil);  
              $preparedStament3->bindParam(1,$PEDIDO_ID);  
              
              return $preparedStament3->execute();
               
               /*if($preparedStament3->execute()){

                 $sentenciaTblSerie="UPDATE tbl_serie_numeros 
                                    SET valor_Serie=? WHERE nom_Serie='PEDIDO'";
                 $prepareTblSerie=$pdo2->prepare($sentenciaTblSerie); 
                 $prepareTblSerie->bindParam(1,$PEDIDO_ID);                   
                 $prepareTblSerie->execute();




              }  */ 



            }

        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor-InsertarPedidoDB",
            "http://localhost",
        "Ocurrió el siguiente error al intentar insertar el PedidoActualizadoDB: " . $e->getMessage());
        $estado=false;
        }


      return $estado;  

    }














  
    private static function getIdPedido() {
        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia SELECT
            $sentence = "SELECT valor_Serie  FROM tbl_serie_numeros 
                        WHERE nom_Serie='PEDIDO' ";

            // Preparar sentencia
            $preparedSentence = $pdo->prepare($sentence);
            // Ejecutar sentencia
            if ($preparedSentence->execute()) {
                $idPedido = $preparedSentence->fetch(PDO::FETCH_ASSOC);
               
                $idNewPedido=(int)$idPedido["valor_Serie"];
                $idNewPedido++;
                $idNewPedido2="00000".$idNewPedido;
                return $idNewPedido2;
                
               

            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor -ObtenerIdPedidoDB",
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



 private static function getImportePedidoActualizacion($numMesa) {
        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia SELECT
            $sentence = "SELECT  Importe_Total FROM tbl_pedido 
                        WHERE Mesa=? ";

            // Preparar sentencia
            $preparedSentence=$pdo->prepare($sentence);
            $preparedSentence->bindParam(1,$numMesa);
            // Ejecutar sentencia
            if ($preparedSentence->execute()) {
                $importePedido = $preparedSentence->fetch(PDO::FETCH_ASSOC);
               
               return $importePedido["Importe_Total"];
                
               

            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor -ObtenerIdPedidoDB",
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



private static function getIdActualizacion($numMesa) {
        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia SELECT
            $sentence = "SELECT  id_Pedido FROM tbl_pedido 
                        WHERE Mesa=? ";

            // Preparar sentencia
            $preparedSentence=$pdo->prepare($sentence);
            $preparedSentence->bindParam(1,$numMesa);
            // Ejecutar sentencia
            if ($preparedSentence->execute()) {
                $idPedido = $preparedSentence->fetch(PDO::FETCH_ASSOC);
               
               return $idPedido["id_Pedido"];
                
               

            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor -ObtenerIdPedidoDB",
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




    private static function guardarEnTblPedidoActualizacion($id_pedido,$lsPlatillo){
            try {
              $pdo = MysqlManager::get()->getDb();


                $sentencia = "INSERT INTO tbl_pedido_actualizacion (
                                id_pedido, 
                                EST_DOC,
                                id_Plato,
                                precio_Plato,
                                cantidad_Plato,
                                total_Plato,
                                obs_Plato
                                )  VALUES (?,?,?,?,?,?,?)";   


                $pdo = MysqlManager::get()->getDb();                   
                foreach ($lsPlatillo as $platillo) {
                
                    $preparedStament2 = $pdo->prepare($sentencia); 
                    $preparedStament2->bindParam(1,$id_pedido);
                    $preparedStament2->bindParam(2,$platillo["EST_DOC"]);
                    $preparedStament2->bindParam(3,$platillo["COD_PRD"]);
                    $preparedStament2->bindParam(4,$platillo["PRE_PRD"]);
                    $preparedStament2->bindParam(5,$platillo["cantidad_Plato"]);
                    $preparedStament2->bindParam(6,$platillo["totalPlato"]);
                    $preparedStament2->bindParam(7,$platillo["observacion"]);
                        

                     $preparedStament2->execute();

                 } 





                     

            }catch (PDOException $e) {
            throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor-guardarTblPedidodActualizacion",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
        }

    }





}


?>