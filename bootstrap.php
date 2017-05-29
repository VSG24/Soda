<?php

/**
 * Load composer modules
 */
if (!file_exists($file = __DIR__. '/vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run this script.');
}
else
{
    require_once $file;
}

/**
 * Load the required configuration file
 */
if (!file_exists($file = __DIR__ . '/config/main.config.php')) {
    throw new RuntimeException('The main.php config file must exist in /config');
}
else
{
    require_once $file;
    if(PRETTY_ERROR_PAGES && ENVIRONMENT == 'dev')
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        if (Whoops\Util\Misc::isAjaxRequest()) {
            $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler);
        }
        $whoops->register();
    }
}

if(!GZIP_ENABLED || !ob_start("ob_gzhandler")) ob_start();

/**
 * Capture incoming requests, extract request parameters
 */
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

/**
 * Routes and routing config
 */
require_once __DIR__ . '/app/routes.php';
$dispatcher = new Phroute\Phroute\Dispatcher($router->getData());
try
{
    $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
}
catch (Phroute\Phroute\Exception\HttpRouteNotFoundException $e)
{
    // not found
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    require_once __DIR__ . '/app/views/error/404.php';
}
catch (\Exception | \ErrorException $e)
{
    // server error
    if(PRETTY_ERROR_PAGES && ENVIRONMENT == 'prod')
    {
        header( $_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error');
        require_once __DIR__ . '/app/views/error/500.php';
    }
    else
    {
        throw $e;
    }
}

/**
 * Flush the output
 */
@ob_flush();