<?php
require 'views/XmlView.php';
require 'views/JsonView.php';
require 'utils/ApiException.php';
require 'controllers/categorias.php';
require 'controllers/comanda.php';
require 'controllers/preliquidacion.php';
require 'controllers/usuarios.php';
require 'controllers/User.php';
require 'controllers/platillos.php';
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
$apiResources = array(USER,'comanda','preliquidacion','usuarios','platillos');

if (!in_array($resource, $apiResources)) {
    throw $resourceNotFound;
}

$httpMethod = strtolower($_SERVER['REQUEST_METHOD']);
switch ($httpMethod) {
    case 'get':  break; 
    case 'post':
               printf($resource);
               if($resource==USER){
                $apiView->render(User::post($urlSegments));
                }
    break;
    case 'put': break;
    case 'delete':break;
        
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