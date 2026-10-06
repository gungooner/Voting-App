<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success($data = null, string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'  => $data,
        ], $status);
    }

    protected function created($data = null, string $message = 'Created successfully')
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent()
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    protected function notFound(string $message = 'Resource not found')
    {
        return $this->error($message, 404);
    }

    protected function forbidden(string $message = 'Unauthorized')
    {
        return $this->error($message, 403);
    }

    protected function unprocessable(string $message, $errors = null)
    {
        return $this->error($message, 422, $errors);
    }
}