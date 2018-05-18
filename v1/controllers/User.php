<?php 
require_once 'data/MysqlManager.php';
require_once 'utils/Helper.php';
  class User{
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
            case SIGN_IN:
                return self::signIn();
                break;
            case SIGN_UP: 
                return self::signUp(); 
                break;    
            default: 
                throw new ApiException(
                    404,0,
                    "El recurso al que intentas acceder no existe",
                    "http://localhost", "No se encontró el segmento \"usuarios/$urlSegments[0]\".");
        }
    }
  
    private static function signUp(){
        $decodedParameters = self::getDecodedParameters();
        $objectFields= array("email","name","lastname","gender","password");
         if (!self::isValidateFields($objectFields,$decodedParameters)){
            throw new ApiException(
                400,0,
                "Las credenciales del usuario deben estar definidas correctamente",
                "http://localhost",
                "El atributo \"id\" o \"password\" o ambos, están vacíos o no definidos"
            );
        }
        $email = $decodedParameters[$objectFields[0]];
        $name =  $decodedParameters[$objectFields[1]];
        $lastname=$decodedParameters[$objectFields[2]];
        $gender = $decodedParameters[$objectFields[3]];
        $password=$decodedParameters[$objectFields[4]];
        $verifyData= self::verifyExitsUser($email);
        if ($verifyData!=null) {
                throw new ApiException(
                    400, 0,
                    "El correo ya esta asociada a una cuenta,pruebe con otra",
                    "http://localhost",
                    "Hubo un error al inicar sesion, datos incorrectos");
         } 
        $state=self::sendRegisterUser($email, $name,$lastname,$gender,$password);
        if ($state) {
            $userData=self::sendAuthenticationUser($email, $password);
            return[ "status"=>200, 
                    "name" => $userData["name"],
                    "lastname"=>$userData["lastname"],
                    "email"=>$userData["email"],
                    "gender"=>$userData["gender"],
                    "token"=>$userData["token"]
                  ];
         }else{
               throw new ApiException(
                    400, 0,
                    "Error de servidor ,no se pudo registrar la cuenta",
                    "http://localhost",
                    "Hubo un error ejecutando el registro de usuario en la base de datos");
              }
     }        
        private static function sendRegisterUser($email, $name,$lastname,$gender,$password){
            $pdo = MysqlManager::get()->getDb();
            $sentence = "INSERT INTO tbl_user (email,name,lastname,gender,password,token)".
                " VALUES (?,?,?,?,?,?)";
            $token = openssl_random_pseudo_bytes(16);
            $token = bin2hex($token); 
            $hash =  password_hash($password, PASSWORD_DEFAULT);
            $preparedStament = $pdo->prepare($sentence); 
            $preparedStament->bindParam(1, $email);
            $preparedStament->bindParam(2, $name);
            $preparedStament->bindParam(3, $lastname);
            $preparedStament->bindParam(4, $gender);
            $preparedStament->bindParam(5,$hash);
            $preparedStament->bindParam(6,$token);
            if ($preparedStament->execute()) return true;
            else return false;
        }

       private static function getDecodedParameters(){
        $parameters = file_get_contents('php://input');
        $decodedParameters = json_decode($parameters, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $internalServerError = new ApiException(
                500, 0,
                "Error interno en el servidor. Contacte al administrador",
                "http://localhost",
                "Error de parsing JSON. Causa:" . json_last_error_msg());
            throw $internalServerError;
        }
        return $decodedParameters;        
       } 

      private static function signIn() {
        $decodedParameters = self::getDecodedParameters();
        $objectFields= array("email","password");	
        if (!self::isValidateFields($objectFields,$decodedParameters)){
            throw new ApiException(
                400,
                0,
                "Las credenciales del usuario deben estar definidas correctamente",
                "http://localhost",
                "El atributo \"id\" o \"password\" o ambos, están vacíos o no definidos"
            );
        }
		$email = $decodedParameters[$objectFields[0]];
    	$password =$decodedParameters[$objectFields[1]];
        $userData=self::sendAuthenticationUser($email, $password);
        return   [  "status" => 200, 
                    "name" => $userData["name"],
                    "lastname" => $userData["lastname"],
                    "email" => $userData["email"],
                    "gender" => $userData["gender"],
                    "token" => $userData["token"]
                  ];
        
    }

    /*Verification instance objects*/
    private static function isValidateFields($objectFields, $decodedParameters){
    	for ($i=0; $i <count($objectFields); $i++) { 
   			if(!isset($decodedParameters[$objectFields[$i]])){
   				return false;
   			}
   		}
        return true;
    }
     /*Verification database*/
     private static function sendAuthenticationUser($email, $password) {
            $userData = self::verifyExitsUser($email); 
            if ($userData!=null) {
               if (password_verify($password,$userData["password"])) {
                    return $userData;
                } else {
                    throw new ApiException(
                    400, 0,
                    "Contraseña incorrecta, vuelve a ingresar los datos",
                    "http://localhost",
                    "Hubo un error al inicar sesion, datos incorrectos");
                } 
            }else{
                throw new ApiException(
                    400, 0,
                    "Usuario no registrado, registrese para poder ingresar",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en Authentication");
             }
    }
     private static function verifyExitsUser($email){
            $pdo = MysqlManager::get()->getDb();
            $sentence = "SELECT * FROM tbl_user WHERE email=?";
            $preparedSentence = $pdo -> prepare($sentence);
            $preparedSentence -> bindParam(1, $email);
            if ($preparedSentence -> execute()) {
                return $preparedSentence -> fetch(PDO::FETCH_ASSOC);
            }else{
                 throw new ApiException(
                    500, 0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. verifyExitsUser:".
                    $pdo->errorInfo()[2]);
            }
     }
 }
?>
