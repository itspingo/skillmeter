<?php

namespace App\Http\Traits;

trait ApiResponses
{
    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message, $code, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    protected function validationErrorResponse($errors, $message = 'Validation failed')
    {
        return $this->errorResponse($message, 422, $errors);
    }
}
