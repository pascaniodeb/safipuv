<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        // Personalizar error 403: Acceso denegado
        if ($exception instanceof AuthorizationException) {
            return response()->view('errors.403', [], 403);
        }

        // Personalizar error 404: No encontrado
        if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }

        // Personalizar error 500: Error interno del servidor
        if ($exception instanceof HttpException && $exception->getStatusCode() === 500) {
            return response()->view('errors.500', [], 500);
        }

        // Fallback: cualquier otro error
        return parent::render($request, $exception);
    }
}