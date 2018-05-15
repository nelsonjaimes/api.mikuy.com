<?php 
require_once 'utils/Helper.php';
require_once 'data/MysqlManager.php';
date_default_timezone_set('America/Lima');
class Reserve{
	public static function post($urlSegments) {
		print("3-");
            if (!isset($urlSegments[0])) {
            throw new ApiException(
                "error",40001,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados");
            }
        
        switch ($urlSegments[0]) {
            case MAKE: return self::makeServation();
              break;
            case LIST_RESERVATION: 
  			printf("entro...");
            return self::getListReservation();  
              break; 
            default:
               throw new ApiException(
                    "error",40001,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", 
                    "No se encontró el segmento Plates/".$urlSegments[0]);
        }
    }

    private static function getListReservation(){
    	 $decodedParameters = self::getDecodedParameters();
    	 $objectFields= array("email");
    	  if (!self::isValidateFields($objectFields,$decodedParameters)){
            throw new ApiException(
                "error",40001,
                "Las credenciales del usuario deben estar definidas correctamente",
                "http://localhost",
                "El atributo \"id\" o \"password\" o ambos, están vacíos o no definidos"
            );
        }

        $emailUser =  $decodedParameters[$objectFields[0]];
        self::changeStateReservation($emailUser);
    }
    
    private static function changeStateReservation($emailUser){
	    try{
	    	$pdo = MysqlManager::get()->getDb();
	    	$consulta = "SELECT codereserve,state,unix FROM tbl_reserve WHERE emailuser=?";
	    	$consultChangeState = "UPDATE tbl_reserve SET state=? WHERE codereserve=?";
      		$preparedSentence = $pdo->prepare($consulta);
      		$preapreChangeState = $pdo->prepare($consultChangeState);
      		$preparedSentence->bindParam(1,$emailUser); 
      		$preparedSentence->execute();
			$resultList = $preparedSentence->fetchAll(PDO::FETCH_ASSOC);
			$currentUnix= time();
			$disable=0;
			foreach ($resultList as $reserve) {
				 $unix= (int)$reserve['unix'];
				 $state = $reserve['state'];
				 $codereserve =$reserve['codereserve'];
				 if ($state) {
				 	 if (self::isExpireReservation($unix,$currentUnix)) {
				 	 	 $preapreChangeState->bindParam(1,$disable); 
				 	 	 $preapreChangeState->bindParam(2,$codereserve);
      					 $preapreChangeState->execute();
				 	 }	    	
				 }
			}
	    }catch(PDOException $e){
	    	  throw new ApiException(
	                    "error",50001,
	                    "No se puedo acceder a los estados de reservaciones, error de servidor.",
	                    "http://localhost",
	                    "Hubo un error ejecutando una sentencia SQL en la base de datos/Reservation:".
	                     $e->getMessage());	
	          }  	
    }

    private static function isExpireReservation($unix,$currentUnix){
        printf("\npre_4\n");
    	$diference = $currentUnix - $unix;
  		$diference = $diference/60;
  		printf("\nminutes:".$diference."\n");
  		if ($diference > TIME_MAX_RESERVATION) return true;
  		return false;		
    }		

    private static function makeServation(){
    	$decodedParameters = self::getDecodedParameters();
        print("5-");
        $objectFields= array("emailuser","amount","platesList");
         if (!self::isValidateFields($objectFields,$decodedParameters)){
            throw new ApiException(
                "error",40001,
                "Las credenciales del usuario deben estar definidas correctamente",
                "http://localhost",
                "El atributo \"id\" o \"password\" o ambos, están vacíos o no definidos"
            );
        }
        print("6-");
        $codeReserve = self::generateCodeReserve();
        printf("genera:".$codeReserve);
        $emailUser =  $decodedParameters[$objectFields[0]];
        $amount = $decodedParameters[$objectFields[1]];
        $platesList = $decodedParameters[$objectFields[2]];
        $datehour = date("d/m/y g:i a");
        $resultConfirmate= self::sendReservationDb($codeReserve,$emailUser,$datehour,$amount,$platesList);
        if ($resultConfirmate) {
        	return[ "status"=>"ok", 
                    "message" => "Se realizó la reservatión correctamente",
                    "code_reserve"=>$codeReserve,
                    "amount"=>$amount,
                    "date_hour"=>$datehour	
                  ];
        }
    }
	private static function sendReservationDb($codeReserve,$emailUser,$datehour,$amount,$platesList){
		try{
			$pdo = MysqlManager::get()->getDb();
            $sentence = "INSERT INTO tbl_reserve(codereserve,emailuser,datehour,amount,unix) 
            VALUES (?,?,?,?,?)";
            $unix =time();
            $unix = (string)$unix;
			$preparedStament = $pdo->prepare($sentence); 	
            $preparedStament->bindParam(1,$codeReserve);
            $preparedStament->bindParam(2,$emailUser);
            $preparedStament->bindParam(3,$datehour);
            $preparedStament->bindParam(4,$amount);
            $preparedStament->bindParam(5,$unix);
            $preparedStament->execute();
            $stateDetailReserve=self::sendDatailReservationDb($platesList,$codeReserve);
            return $stateDetailReserve;
          }catch(PDOException $e){
          	  throw new ApiException(
                    "error",50001,
                    "No se pudo realizar la reservación, error de servidor.",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos/Reservation:".
                     $e->getMessage());	
          }  
        }

     private static function sendDatailReservationDb($platesList, $codeReserve){
         try{
         	printf("send7_");
     		$pdo = MysqlManager::get()->getDb();
            $sentence = "INSERT INTO tbl_detail_reserve(code_reserve,code_plate,count_plate) VALUES (?,?,?)";
            printf("send8_");
            $preparedStament = $pdo->prepare($sentence); 
            foreach ($platesList as $plate) {
            		$preparedStament->bindParam(1,$codeReserve);
		            $preparedStament->bindParam(2,$plate['code']);
		            $preparedStament->bindParam(3,$plate['acount']);
		            $preparedStament->execute();
            }
            printf("send9_");
            return true;
		  }catch(PDOException $e){
		  	 throw new ApiException(
		                  "error",50001,
		                  "No se pudo realizar la reservación,error de servidor",
		                  "http://localhost",
		                  "Hubo un error ejecutando una sentencia SQL /tbl DetailReservation:".
		                   $e->getMessage());
		  }   	          
     }   
     private static function getDecodedParameters(){
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                "error",50001,
                "Error interno en el servidor,Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa:" . json_last_error_msg());
            throw $internalServerError;
        }
        return $decodedParameters;        
       } 

       private static function generateCodeReserve(){
       	try{
       		printf("7");
     		$pdo = MysqlManager::get()->getDb();
     		$sentence = "SELECT COUNT(*) as num FROM tbl_reserve";	
     		$preparedSentence = $pdo->prepare($sentence); 
     		$preparedSentence->execute();
     		$rows = $preparedSentence->fetch(PDO::FETCH_ASSOC);
     		$newCode = (int) $rows["num"]+1;
     		$newCode ="R".$newCode;
     		printf("code:".$newCode);
     		return $newCode;
     		}catch(PDOException $e){
		  	 throw new ApiException(
		           "error",50001,
		            "No se pudo generar un codigo de reservación,error de servidor",
		            "http://localhost",
		            "Hubo un error ejecutando una sentencia SQL /tbl DetailReservation:".
		             $e->getMessage());
		  }

       }

        private static function isValidateFields($objectFields, $decodedParameters){
    	for ($i=0; $i <count($objectFields); $i++) { 
            printf($objectFields[$i]);
   			if(!isset($decodedParameters[$objectFields[$i]])){
   				return false;
   			}
   		}
        return true;
       }     
}
?>