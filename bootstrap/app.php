<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir atau tidak valid. Silakan login kembali.'
                ], 401);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = 500;
                $message = 'Terjadi kesalahan pada sistem kami. Silakan coba lagi nanti.';

                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || 
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $statusCode = 404;
                    $message = 'Data atau rute spesifik yang Anda cari tidak ditemukan di dalam sistem.';
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                    $message = 'Mohon periksa kembali data yang Anda kirimkan, beberapa pengisian tidak valid.';
                } elseif ($e instanceof \DomainException || $e instanceof \InvalidArgumentException) {
                    $statusCode = 403; 
                    $message = $e->getMessage();
                } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException || 
                          $e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
                    $statusCode = 403;
                    $message = 'Anda tidak memiliki otoritas atau izin yang cukup untuk menggunakan fitur ini.';
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $statusCode = 405;
                    $message = 'Metode protokol tidak didukung (seperti penggunaan GET di rute bertipe POST).';
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                    if ($statusCode === 429) {
                        $message = 'Sistem mendeteksi terlalu banyak klik/permintaan. Silakan jeda beberapa saat.';
                    } else {
                        $message = $e->getMessage() ?: $message;
                    }
                }

                $response = [
                    'success' => false,
                    'message' => $message,
                ];

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $response['errors'] = $e->errors();
                }

                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ];
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
