<?php

use Illuminate\Support\Facades\Http;

function createPremiumAccess($data) {
    $url = env('URL_COURSE_SERVICE'). '/api/mycourses/premium';

    try {
        $response = Http::post($url, $data);
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'course service not available'
        ];
    }
}