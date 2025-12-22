<?php

if (!function_exists('successJson')) {
    /**
     * Return a success JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    function successJson($data = null, string $message = 'Success', int $statusCode = 200)
    {
        return \App\Helpers\ResponseHelper::success($data, $message, $statusCode);
    }
}

if (!function_exists('errorJson')) {
    /**
     * Return an error JSON response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return \Illuminate\Http\HttpJsonResponse
     */
    function errorJson(string $message = 'Error occurred', $errors = null, int $statusCode = 400)
    {
        return \App\Helpers\ResponseHelper::error($message, $errors, $statusCode);
    }
}
