<?php

namespace App\Exceptions;

use App\Services\ElasticsearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

abstract class BaseApiExceptionHandler
{
    public function __construct(protected ElasticsearchService $elasticsearchService)
    {
    }

    /**
     * Log exception to Elasticsearch with comprehensive data
     */
    protected function logToElasticsearch(Throwable $e, Request $request, array $additionalData = []): void
    {
        try {
            $errorData = $this->buildErrorDocument($e, $request, $additionalData);
            $this->elasticsearchService->logError($errorData);
        } catch (Throwable $loggingError) {
            // Fallback to standard logging if Elasticsearch fails
            Log::error('Failed to log error to Elasticsearch', [
                'elasticsearch_error' => $loggingError->getMessage(),
                'original_error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Build comprehensive error document for Elasticsearch
     */
    private function buildErrorDocument(Throwable $e, Request $request, array $additionalData = []): array
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        return array_merge([
            'id' => $this->generateErrorId($e, $request),
            'type' => $this->getExceptionType($e),
            'message' => $e->getMessage(),
            'status' => $this->getStatusCode($e),
            'uri' => $request->getRequestUri(),
            'method' => $request->getMethod(),
            'user' => $this->getUserContext($request),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'trace' => $this->getFilteredTrace($e),
            'timestamp' => now()->toISOString(),
            'environment' => config('app.env'),
            'file' => $this->sanitizeFilePath($e->getFile()),
            'line' => $e->getLine(),
            'context' => $this->getRequestContext($request),
            'request_data' => $this->sanitizeRequestData($request),
            'headers' => $this->sanitizeHeaders($request),
            'response_time' => round($responseTime, 2),
            'memory_usage' => memory_get_peak_usage(true),
            'tags' => $this->generateTags($e, $request),
        ], $additionalData);
    }

    /**
     * Generate unique error ID for tracking
     */
    private function generateErrorId(Throwable $e, Request $request): string
    {
        return md5(
            $e->getFile() .
            $e->getLine() .
            $e->getMessage() .
            $request->getMethod() .
            $request->getRequestUri() .
            now()->format('Y-m-d-H')
        );
    }

    /**
     * Get exception type name
     */
    private function getExceptionType(Throwable $e): string
    {
        return class_basename($e);
    }

    /**
     * Get HTTP status code from exception
     */
    private function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        if (method_exists($e, 'getCode') && $e->getCode() > 0) {
            return $e->getCode();
        }

        return 500;
    }

    /**
     * Get user context from multiple authentication guards
     */
    private function getUserContext(Request $request): ?array
    {
        $guards = [
            'web' => fn($user) => [
                'id' => (string) $user->id,
                'type' => 'user',
                'name' => $user->name ?? 'Unknown',
                'email' => $user->email ?? null,
            ],
            'api' => fn($user) => [
                'id' => (string) $user->id,
                'type' => 'api_user',
                'name' => $user->name ?? 'Unknown',
                'email' => $user->email ?? null,
            ],
            'sanctum' => fn($user) => [
                'id' => (string) $user->id,
                'type' => 'sanctum_user',
                'name' => $user->name ?? 'Unknown',
                'email' => $user->email ?? null,
            ],
        ];

        foreach ($guards as $guard => $formatter) {
            if (auth($guard)->check()) {
                return $formatter(auth($guard)->user());
            }
        }

        return null;
    }

    /**
     * Get filtered stack trace (first 10 frames)
     */
    private function getFilteredTrace(Throwable $e): string
    {
        $trace = $e->getTrace();
        $filteredTrace = array_slice($trace, 0, 10);

        // Remove sensitive information from trace
        return json_encode(
            array_map(function ($frame) {
                unset($frame['args']); // Remove function arguments which might contain sensitive data
                return $frame;
            }, $filteredTrace),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Sanitize file path to remove sensitive system information
     */
    private function sanitizeFilePath(string $filePath): string
    {
        return str_replace(base_path(), '', $filePath);
    }

    /**
     * Get request context for tracking
     */
    private function getRequestContext(Request $request): array
    {
        return [
            'request_id' => $request->header('X-Request-ID') ?? Str::uuid()->toString(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'correlation_id' => $request->header('X-Correlation-ID'),
        ];
    }

    /**
     * Sanitize request data by removing sensitive information
     */
    private function sanitizeRequestData(Request $request): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'api_key',
            'secret', 'private_key', 'credit_card', 'ssn', 'social_security'
        ];

        $input = $request->except($sensitiveFields);
        $query = $request->query();

        // Remove sensitive data from nested arrays
        array_walk_recursive($input, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '[REDACTED]';
            }
        });

        return [
            'input' => $input,
            'query' => $query,
            'files' => $request->hasFile('*') ? array_keys($request->allFiles()) : [],
        ];
    }

    /**
     * Sanitize headers by removing sensitive information
     */
    private function sanitizeHeaders(Request $request): array
    {
        $headers = $request->headers->all();

        $sensitiveHeaders = [
            'authorization', 'cookie', 'x-api-key', 'x-auth-token'
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Generate tags for easier filtering and categorization
     */
    private function generateTags(Throwable $e, Request $request): array
    {
        $tags = [];

        // Environment tag
        $tags[] = config('app.env');

        // Exception type tag
        $tags[] = strtolower($this->getExceptionType($e));

        // HTTP method tag
        $tags[] = strtolower($request->getMethod());

        // Status code category
        $statusCode = $this->getStatusCode($e);
        if ($statusCode >= 400 && $statusCode < 500) {
            $tags[] = 'client_error';
        } elseif ($statusCode >= 500) {
            $tags[] = 'server_error';
        }

        // API vs Web request
        if ($request->expectsJson() || $request->is('api/*')) {
            $tags[] = 'api';
        } else {
            $tags[] = 'web';
        }

        // Authentication status
        if (auth()->check()) {
            $tags[] = 'authenticated';
        } else {
            $tags[] = 'guest';
        }

        return $tags;
    }
}