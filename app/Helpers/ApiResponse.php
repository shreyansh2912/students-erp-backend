<?php

if (!function_exists('apiSuccess')) {
    /**
     * Return a standardized success JSON response
     *
     * @param mixed $data The data to return
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    function apiSuccess($data = null, string $message = 'Operation successful', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
}

if (!function_exists('apiError')) {
    /**
     * Return a standardized error JSON response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $errors Additional error details (validation errors, etc.)
     * @return \Illuminate\Http\JsonResponse
     */
    function apiError(string $message = 'An error occurred', int $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
