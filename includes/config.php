<?php

// Api configuration (Hugging Face AI)
define ('API_TOKEN', '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');
define('API_URL', 'https://api-inference.huggingface.co/models/facebook/blenderbot-400M-distill');

//API Weather configuration (WeatherStack)
define ('API_WEATHER_KEY', '5937c518d3da7732cfac1f864817ea18');
define ('API_WEATHER_URL', 'https://api.openweathermap.org/data/2.5/weather');

//define ('API_WEATHER_KEY', 'e4a5e890f9d0634bbf28610aed92f583');
//define ('API_WEATHER_URL', 'http://api.weatherstack.com/');

//API Image generation configuration (StarryAI)
define ('API_IMAGE_KEY', '5uSa-IemV7eHSW1UfwnvbHHpQxMPbg');
define ('API_IMAGE_URL', 'https://api.starryai.com');

//Ada configuration
define ('Bot_NAME', 'Ada');
define ('Bot_Version', '1.0');
define ('DEFAULT_LANG', 'en');
define ('DEBUG_MODE', true);

//Logs Configuration
define ('LOGS_ERROR', 'true');
define ('ERROR_LOG_PATH', __DIR__ . 'logs/error.log');

//Cache Configuration
define ('CACHE_ENABLED', true);
define ('CACHE_DURATION', 86400); // 1 day in seconds

// Función para manejo de errores
function logError($message) {
    if (LOGS_ERROR) {
        error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, ERROR_LOG_PATH);
    }
    
    if (DEBUG_MODE) {
        return $message;
    } else {
        return "Ha ocurrido un error interno.";
    }
}