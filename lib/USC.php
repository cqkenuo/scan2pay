<?php
/**
 * Class App
 */
Class USC {
    public static $app = array();
    protected static $start_time = 0;

    //call function in controller
    public static function run() {      //--{{{
        self::$start_time = microtime(true);

        try {
            self::loadController();
        }catch(Exception $e) {
            $errorMsg = $e->getMessage();
            $errorCode = $e->getCode();
            if (empty($errorCode)) {$errorCode = 500;}
            $title = "HTTP/1.0 {$errorCode} Internal Server Error";

            header("Content-type: text/html; charset=utf-8");
            header($title, true, $errorCode);

            echo <<<eof
<!DocType html>
<head>
<meta charset="utf-8">
<title>{$title}</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
<style>
body{max-width:768px;margin:0 auto}
h1{color:#FF0000}
.error{padding:4px;color:yellow;background-color:gray;min-height:40px}
</style>
</head>
<body>
    <h1>{$title}</h1>
    <h3>Exception:</h3>
    <p class="error">{$errorMsg}</p>
</body>
</html>
eof;
        }
    }       //--}}}

    //parse url to controller and action name
    protected static function getControllerAndAction($url, $config) {       //--{{{
        $arr = parse_url($url);
        $path = !empty($arr['path']) ? $arr['path'] : '/';

        @list(, $controller, $action) = explode('/', $path);
        if (empty($controller)) {
            $controller = !empty($config['defaultController']) ? $config['defaultController'] : 'site';
        }else if(preg_match('/\w+\.\w+/i', $controller)) { //not controller but some file not exist
            throw new Exception("File {$controller} not exist.", 404);
        }
        if (empty($action)) {
            $action = 'index';
        }

        return compact('controller', 'action');
    }   //--}}}

    protected static function loadController() {    //--{{{
        //configs
        $config = require_once __DIR__ . '/../conf/appConfig.php';

        //parse url to controller and action
        $requestUrl = $_SERVER['REQUEST_URI'];
        $arr = self::getControllerAndAction($requestUrl, $config);
        $controller = $arr['controller'];
        $action = $arr['action'];
        $start_time = self::$start_time;

        //set parameters
        self::$app = compact('config', 'controller', 'action', 'requestUrl', 'start_time');

        //call class and function
        $className = ucfirst($controller) . 'Controller';
        $funName = 'action' . ucfirst($action);
        $controllerFile = __DIR__ . "/../controller/{$className}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $cls = new $className();
            if (method_exists($className, $funName)) {
                $cls->$funName();
            }else {
                throw new Exception("Function {$funName} not exist in class {$className}.", 500);
            }
        }else {
            throw new Exception("Controller file {$className}.php not exist.", 500);
        }
    }   //--}}}

}
