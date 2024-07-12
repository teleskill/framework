<?php

namespace Teleskill\Framework\Core;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\App As SlimApp;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Core\App;
use Exception;
use Throwable;

class Router {
	
    const LOGGER_NS = self::class;
    
    private static Router $instance;

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : Router {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();
		}

		return self::$instance;
	}

	// Define Custom Error Handler
    public static function create() : SlimApp {
        $instance = self::getInstance();

        // create App
        $app = AppFactory::create();

        // Define Custom Error Handler
        $customErrorHandler = function (
            ServerRequestInterface $request,
            Throwable $exception,
            bool $displayErrorDetails,
            bool $logErrors,
            bool $logErrorDetails,
            ?LoggerInterface $logger = null
        ) use ($app) {
            try {
                $code = (int)$exception->getCode() ?? 500;

                Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);  
            } catch(Exception $e) {
                $code = 500;
            }

            $response = $app->getResponseFactory()->createResponse()->withStatus($code);

            //echo (string) $exception;
            
            return $response;
        };

        // Add parser json, Form data and xml middleware
        $app->addBodyParsingMiddleware();

        // Add Routing Middleware
        $app->addRoutingMiddleware();

        // Add Error Middleware
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setDefaultErrorHandler($customErrorHandler);

        // Register routes
        $routes = require App::basePath() . '/routes/routes.php';
        $routes($app);

        $app->run();

        return $app;
    }

}
