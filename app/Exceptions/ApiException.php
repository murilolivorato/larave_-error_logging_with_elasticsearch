<?php

namespace App\Exceptions;

use App\Services\ElasticsearchService;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiException extends BaseApiExceptionHandler
{
    public static array $handlers = [
        AuthenticationException::class => 'handleAuthenticationException',
        ValidationException::class => 'handleValidationException',
        ModelNotFoundException::class => 'handleNotFoundException',
        NotFoundHttpException::class => 'handleNotFoundException',
        AuthorizationException::class => 'handleAuthorizationException',
        MethodNotAllowedHttpException::class => 'handleMethodNotAllowedHttpException',
        HttpException::class => 'handleHttpException',
        QueryException::class => 'handleQueryException',
        AccessDeniedHttpException::class => 'handleAuthenticationException'
    ];

    public function handleAuthenticationException(AuthenticationException|AccessDeniedHttpException $e, Request $request): JsonResponse
    {
        $this->logToElasticsearch($e);
        return response()->json([
            'error' => [
                'type' => self::getType($e),
                'status' => 401,
                'message' => $e->getMessage(),
            ]
        ]);
    }

    public function handleAuthorizationException(AuthorizationException $e, Request $request): JsonResponse
    {
        // log that sensitive stuff
        // should move this out to custom logger
        $source = 'Line: ' . $e->getLine();
        Log::notice(basename(get_class($e)) . ' - ' . $e->getMessage() . ' - ' .  'Line: ' . $e->getLine());

        return response()->json([
            'error' => [
                'type' => self::getType($e),
                'status' => 403,
                'message' => $e->getMessage()
            ]
        ]);
    }

    public function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e, Request $request): JsonResponse
    {
        return response()->json([
            'error' => [
                'type' => self::getType($e),
                'status' => 405,
                'message' => $e->getMessage()
            ]
        ]);
    }

    public function handleHttpException(HttpException $e, Request $request): JsonResponse
    {
        return response()->json([
            'error' => [
                'type' => self::getType($e),
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage()
            ]
        ]);
    }


    public function handleValidationException(ValidationException $e, Request $request): JsonResponse
    {
        foreach ($e->errors() as $key => $value)
            foreach ($value as $message) {
                $errors[] = [
                    'type' => self::getType($e),
                    'status' => 422,
                    'message' => $message,
                ];
            }

        return response()->json([
            'errors' => $errors
        ]);
    }

    public function handleNotFoundException(ModelNotFoundException|NotFoundHttpException $e, Request $request): JsonResponse
    {
        return response()->json([
            'error' => [
                'type' => self::getType($e),
                'status' => 404,
                'message' => 'Not Found ' . $request->getRequestUri()
            ]
        ]);
    }

    public function handleQueryException(QueryException $e, Request $request): JsonResponse
    {
        $code = $e->errorInfo[1];
        if ($code == 1451) {
            return response()->json([
                'error' => [
                    'type' => self::getType($e),
                    'status' => 409,
                    'message' => 'Este recurso não pode ser executado'
                ]
            ]);
        }

        // Default response for other codes
        return response()->json([
            'error' => [
                'type' => self::getType($e),
                'status' => 500,
                'message' => $e->getMessage()
            ]
        ]);
    }
}
