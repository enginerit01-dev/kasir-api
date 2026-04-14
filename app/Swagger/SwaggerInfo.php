<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Kasir API',
    description: 'Dokumentasi API untuk aplikasi kasir.'
)]
#[OA\Server(
    url: '/api',
    description: 'API base URL'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Masukkan bearer token hasil login.'
)]
class SwaggerInfo
{
}
