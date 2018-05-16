<?php
require 'views/XmlView.php';
require 'views/JsonView.php';
require 'utils/ApiException.php';
require 'controllers/User.php';
require 'controllers/Reserve.php';
require 'controllers/Plates.php';
require_once 'utils/Helper.php';

$format = isset($_GET['format']) ? $_GET['format'] : 'json';
if (strcasecmp($format, 'xml') == 0) {
    $apiView = new XmlView();
} else {
    $apiView = new JsonView();
}
set_exception_handler(
    function (ApiException $exception) use ($apiView) {
        http_response_code($exception->getStatus());
        $apiView->render($exception->toArray());
    }
);

$resourceNotFound = new ApiException(404, 1000, "El recurso al que intentas acceder no existe", "http://localhost",
    "No existe un resource definido en: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

if (isset($_GET['PATH_INFO'])) {
    $urlSegments = explode('/', $_GET['PATH_INFO']);
} else {
    throw $resourceNotFound;
}

$resource = array_shift($urlSegments);
$apiResources = array(USER,PLATES,RESERVE);

if (!in_array($resource, $apiResources)) {
    throw $resourceNotFound;
}

$httpMethod = strtolower($_SERVER['REQUEST_METHOD']);
switch ($httpMethod) {
    case 'get':  
              if (!strcmp($resource,PLATES)) {
                $apiView->render(Plates::get($urlSegments));
              }
    break; 
    case 'post':
     print("1-");
               if(!strcmp($resource,USER)){
                $apiView->render(User::post($urlSegments));
                 }else if(!strcmp($resource,RESERVE)){
                        print("2-");
                        $apiView->render(Reserve::post($urlSegments));
                  }
    break;
    default:
        $methodNotAllowed = new ApiException(
            405,
            1001,
            "Acción no permitida",
            "http://localhost",
            "No se puede aplicar el método $_SERVER[REQUEST_METHOD] sobre el recurso \"$resource\"");
        $apiView->render($methodNotAllowed->toArray());

}
?>