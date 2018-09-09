<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception) {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception) {
		if ($request->wantsJson() && !($exception instanceof ValidationException)) {
	        // Define the response
	        $response = [
				'message' => ($exception instanceof \QueryException) ? 'Problème trouvé dans la requête SQL effectuée' : $exception->getMessage(),
	        ];

	        // If the app is in debug mode
	        if (config('app.debug') && !$this->isHttpException($exception)) {
	            // Add the exception class name, message and stack trace to response
				$response['message'] = $exception->getMessage();
				$response['exception'] = get_class($exception);
	            $response['trace'] = $exception->getTrace();
	        }

            if ($this->isHttpException($exception))
                $status = $exception->getStatusCode();
            else if ($exception instanceof AuthenticationException)
                $status = 401;
            else
                $status = 400;

	        // Return a JSON response with the response array and status code
	        return response()->json($response, $status);
	    }
        else if ($exception instanceof AuthorizationException)
            return redirect('/');

        return parent::render($request, $exception);
    }
}
