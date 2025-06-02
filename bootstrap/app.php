<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Get the exception class name
            $className = get_class($e);

            // Get our custom handlers
            $handlers = App\Exceptions\ApiExceptionHandler::$handlers;

            // Check if we have a specific handler for this exception
            if (array_key_exists($className, $handlers)) {
                $method = $handlers[$className];
                $apiHandler = new App\Exceptions\ApiExceptionHandler();
                return $apiHandler->$method($e, $request);
            }

            // Fallback to default error response
            return response()->json([
                'error' => [
                    'type' => basename(get_class($e)),
                    'status' => $e->getCode() ?: 500,
                    'message' => $e->getMessage() ?: 'An unexpected error occurred',
                    'timestamp' => now()->toISOString(),
                    // Include debug info only in non-production environments
                    'debug' => app()->environment('local', 'testing') ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ] : null
                ]
            ], $e->getCode() ?: 500);
        });
    })
    ->create();