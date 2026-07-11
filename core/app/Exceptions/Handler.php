<?php

namespace App\Exceptions;

use App\Factories\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (ValidationException $exception, $request) {
            if (! $this->shouldReturnApiError($request)) {
                return null;
            }

            return ApiResponse::validation([]);
        });

        $this->renderable(function (AuthenticationException $exception, $request) {
            if (! $this->shouldReturnApiError($request)) {
                return null;
            }

            return ApiResponse::unauthorized();
        });

        $this->renderable(function (NotFoundHttpException $exception, $request) {
            if (! $this->shouldReturnApiError($request)) {
                return null;
            }

            return ApiResponse::notFound('Resource not found.');
        });

        $this->renderable(function (HttpException $exception, $request) {
            if (! $this->shouldReturnApiError($request)) {
                return null;
            }

            return $this->handleHttpException($exception, $request);
        });

        $this->renderable(function (Throwable $exception, $request) {
            if (! $this->shouldReturnApiError($request)) {
                return null;
            }

            return ApiResponse::internal();
        });

        $this->reportable(function (Throwable $exception) {
            if (app()->bound('sentry')) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                    // Injecting our custom Scrubber into Sentry options
                    $options = \Sentry\SentrySdk::getCurrentHub()->getClient()->getOptions();
                    $options->setBeforeSendCallback(function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
                        return \App\Services\SentryEventScrubber::scrub($event);
                    });
                });
                
                app('sentry')->captureException($exception);
            }
        });
    }

    /**
     * Handle HTTP exceptions to ensure custom messages are displayed.
     */
    protected function handleHttpException(HttpException $exception, $request): JsonResponse
    {
        $status = $exception->getStatusCode();

        if ($status === 404) {
            return ApiResponse::notFound('Resource not found.');
        }

        return ApiResponse::error(
            \App\Enums\ApiErrorType::INVALID_REQUEST,
            \App\Enums\ApiErrorCode::INVALID_PARAMETERS,
            $this->getDefaultHttpErrorMessage($status),
            $status
        );
    }

    /**
     * Get a default message for HTTP errors if no message is provided.
     */
    protected function getDefaultHttpErrorMessage(int $statusCode): string
    {
        $defaultMessages = [
            400 => 'The request is invalid.',
            401 => 'Unauthorized.',
            403 => 'Forbidden.',
            404 => 'Resource not found.',
            405 => 'Method not allowed.',
            419 => 'The request could not be completed.',
            429 => 'Too many requests.',
            500 => 'An internal error occurred.',
        ];

        return $defaultMessages[$statusCode] ?? 'An error occurred.';
    }

    protected function shouldReturnApiError($request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
