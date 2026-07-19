<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->appendToGroup('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        $middleware->alias([
            'auth.fournisseur' => \App\Http\Middleware\FournisseurMiddleware::class,
            'auth.admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth.pme' => \App\Http\Middleware\PmeTokenAuth::class,
            'auth.optional' => \App\Http\Middleware\OptionalSanctumAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('Validation échouée'),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('Non autorisé'),
                    'errors' => null,
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('Ressource introuvable'),
                    'errors' => null,
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('Ressource introuvable'),
                    'errors' => null,
                ], 404);
            }
        });
    })->create();
