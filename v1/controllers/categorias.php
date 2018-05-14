<?php

require_once 'data/MysqlManager.php';

class categorias {

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
            case 'listacompleta':
                  return self::getListaCategorias();
              break;

           
            default:
               throw new ApiException(
                    404,
                    0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento \"affiliates/$urlSegments[0]\".");
        }


    }

    public static function post($urlSegments) {

       
    }

    public static function put($urlSegments) {

    }

    public static function delete($urlSegments) {

    }


    
    /*---- Retorno de las  cateogorias completas -- */
     private static function getListaCategorias(){

       try{ 
             $pdo=MysqlManager::get()->getDb(); 
             $consulta = "SELECT id_categoria ,
                                 nom_categoria ,
                                 ord_lst_cat
                                 FROM  tbl_categoria";
   
            
               
                                  
               $preparedSentence = $pdo->prepare($consulta);
                if($preparedSentence->execute()){
                    $array=array(); 
                   $lsCategorias=$preparedSentence->fetchAll(PDO::FETCH_ASSOC); 

                    foreach ($lsCategorias as $cateogoria) {
                         $array2=array(
                              "id_categoria"=>$cateogoria['id_categoria'],
                              "nom_categoria"=>$cateogoria['nom_categoria'],
                              "ord_lst_cat"=>$cateogoria['ord_lst_cat'],
                          "ls_platillos"=>self::getListaPlatillosXCategoria($cateogoria['id_categoria'])
                            );    

                      $array[]=$array2;   
                    }

                    return $array;
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
                "Ocurrió el siguiente error al consultar el afiliado: " . $e->getMessage());
            }      
        

     }




private static function getListaPlatillosXCategoria($id){

        try{
          
            $pdo=MysqlManager::get()->getDb(); 
            $sentence = "SELECT COD_PRD ,
                                DES_PRD ,
                                COD_CAT ,
                                DES_CAT ,
                                PRE_PRD,
                                RUT_FOT
                             FROM tbl_productos WHERE COD_CAT=?";

            $preparedSentence = $pdo->prepare($sentence);
            $preparedSentence->bindParam(1, $id, PDO::PARAM_INT);

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
                "Ocurrió el siguiente error al consultar el afiliado: " . $e->getMessage());
            }      

    }






   
   

}


?>