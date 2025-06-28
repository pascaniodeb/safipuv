<?php

// config/exchange_rates.php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Exchange Rates Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para las APIs de tasas de cambio del sistema SAFIPUV
    |
    */

    // 🔹 API para tasa VES/USD (DolarAPI Venezuela)
    'venezuela_api' => [
        'url' => env('VENEZUELA_EXCHANGE_API_URL', 'https://ve.dolarapi.com/v1/dolares/oficial'),
        'timeout' => env('VENEZUELA_API_TIMEOUT', 30),
        'rate_field' => 'promedio', // Campo que contiene la tasa en la respuesta JSON
    ],

    // 🔹 API para tasa COP/USD (TRM Colombia - datos.gov.co)
    'colombia_api' => [
        'url' => env('COLOMBIA_TRM_API_URL', 'https://www.datos.gov.co/resource/32sa-8pi3.json'),
        'timeout' => env('COLOMBIA_API_TIMEOUT', 30),
        'rate_field' => 'valor', // Campo que contiene la tasa en la respuesta JSON
        'date_param' => 'vigenciadesde', // Parámetro para filtrar por fecha
    ],

    // 🔹 Configuración del scheduler
    'schedule' => [
        'timezone' => env('EXCHANGE_RATES_TIMEZONE', 'America/Caracas'), // UTC-4
        'update_time' => env('EXCHANGE_RATES_UPDATE_TIME', '09:00'), // Hora de actualización
        'update_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], // Días laborales
        'retry_attempts' => env('EXCHANGE_RATES_RETRY_ATTEMPTS', 3), // Intentos si falla
    ],

    // 🔹 Configuración de caché
    'cache' => [
        'ttl' => env('EXCHANGE_RATES_CACHE_TTL', 1800), // 30 minutos en segundos
        'key_prefix' => 'safipuv_exchange_rates_',
    ],

    // 🔹 Configuración de logs
    'logging' => [
        'enabled' => env('EXCHANGE_RATES_LOGGING', true),
        'level' => env('EXCHANGE_RATES_LOG_LEVEL', 'info'), // debug, info, warning, error
        'channel' => env('EXCHANGE_RATES_LOG_CHANNEL', 'single'), // stack, single, daily
    ],

    // 🔹 Valores por defecto y validaciones
    'validation' => [
        'min_usd_rate' => env('MIN_USD_RATE', 1), // Tasa mínima válida para USD
        'max_usd_rate' => env('MAX_USD_RATE', 1000), // Tasa máxima válida para USD
        'min_cop_rate' => env('MIN_COP_RATE', 1000), // Tasa mínima válida para COP/USD
        'max_cop_rate' => env('MAX_COP_RATE', 10000), // Tasa máxima válida para COP/USD
    ],

    // 🔹 Configuración de notificaciones (opcional para el futuro)
    'notifications' => [
        'enabled' => env('EXCHANGE_RATES_NOTIFICATIONS', false),
        'email' => env('EXCHANGE_RATES_NOTIFICATION_EMAIL', 'tesorero@safipuv.org'),
        'threshold_change' => env('EXCHANGE_RATES_THRESHOLD_CHANGE', 5), // % de cambio para notificar
    ],

];