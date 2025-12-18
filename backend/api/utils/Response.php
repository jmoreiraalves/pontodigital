<?php
// /api/utils/Response.php

class Response {
    public static function send($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    public static function error($message, $status = 400) {
        self::send([
            'success' => false,
            'message' => $message
        ], $status);
    }
    
    public static function success($data = null, $message = null) {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::send($response);
    }
}
?>