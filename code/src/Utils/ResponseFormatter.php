<?php

namespace Yega\GitHub\Utils;

class ResponseFormatter
{
    /**
     * Formatea respuesta de éxito estándar
     */
    public static function success(array $data, array $meta = []): array
    {
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ];
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return $response;
    }
    
    /**
     * Formatea respuesta de error estándar
     */
    public static function error(string $message, int $code = 500, array $context = []): array
    {
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ];
        
        if (!empty($context)) {
            $response['context'] = $context;
        }
        
        return $response;
    }
    
    /**
     * Formatea respuesta paginada
     */
    public static function paginated(array $data, int $page, int $perPage, int $total = null): array
    {
        return self::success($data, [
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => count($data),
                'total' => $total
            ]
        ]);
    }
}