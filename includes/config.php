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
define ('API_IMAGE_URL', 'https://api.starryai.com/user/generate');

//Ada configuration
define ('Bot_NAME', 'Ada');
define ('Bot_Version', '1.0');
define ('DEFAULT_LANG', 'en');
define ('DEBUG_MODE', true);
define ('SUPPORTED_LANGUAGES', ['en', 'es']);

//Logs Configuration
define ('LOGS_ERROR', 'true');
define ('ERROR_LOG_PATH', __DIR__ . 'logs/error.log');

//Cache Configuration
define ('CACHE_ENABLED', true);
define ('CACHE_DURATION', 86400); // 1 day in seconds

/**
 * Function to log errors
 * 
 * @param string $message Error message to log
 * @return string Formatted error message
 */
function logError($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    $formattedMessage = "$timestamp $message";
    
    if (LOGS_ERROR) {
        error_log($formattedMessage . PHP_EOL, 3, ERROR_LOG_PATH);
    }
    
    if (DEBUG_MODE) {
        return $message;
    } else {
        return "Intern error.";
    }
}

/**
 * Detect language of a message
 * 
 * @param string $message Message to analyze
 * @return string Detected language code (en/es)
 */
function detectLanguage($message) {
    // Palabras clave para inglés
    $englishKeywords = [
        'weather', 'temperature', 'climate', 'how', 'what', 'where', 'when', 
        'the', 'in', 'is', 'are', 'image', 'picture', 'generate', 'create', 
        'show', 'make'
    ];
    
    // Palabras clave para español
    $spanishKeywords = [
        'clima', 'tiempo', 'temperatura', 'cómo', 'como', 'qué', 'que', 
        'dónde', 'donde', 'cuándo', 'cuando', 'en', 'de', 'es', 'está', 
        'son', 'imagen', 'foto', 'generar', 'crear', 'mostrar', 'hacer'
    ];
    
    $message = strtolower($message);
    
    $englishCount = 0;
    $spanishCount = 0;
    
    // Contar palabras en inglés
    foreach ($englishKeywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            $englishCount++;
        }
    }
    
    // Contar palabras en español
    foreach ($spanishKeywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            $spanishCount++;
        }
    }
    
    // Detectar caracteres específicos del español
    if (preg_match('/[áéíóúñü¿¡]/i', $message)) {
        $spanishCount += 2;
    }
    
    return $englishCount > $spanishCount ? 'en' : 'es';
}

/**
 * Get translations for different messages
 * 
 * @param string $key Translation key
 * @param string $lang Language code
 * @return string Translated message
 */
function getTranslation($key, $lang = 'es') {
    $translations = [
        'weather_response' => [
            'en' => "The weather in {city}, {country}: {description}. Temperature is {temperature}°C (feels like {feels_like}°C). Humidity: {humidity}%, Wind: {wind_speed} m/s.",
            'es' => "El clima en {city}, {country}: {description}. La temperatura es de {temperature}°C (sensación térmica: {feels_like}°C). Humedad: {humidity}%, viento: {wind_speed} m/s."
        ],
        'weather_error' => [
            'en' => "Sorry, I couldn't get weather information for {city}. {error}",
            'es' => "Lo siento, no pude obtener información del clima para {city}. {error}"
        ],
        'weather_not_found' => [
            'en' => "City not found. Please check the spelling.",
            'es' => "Ciudad no encontrada. Por favor verifica la ortografía."
        ],
        'connection_error' => [
            'en' => "Could not connect to weather service.",
            'es' => "No se pudo conectar con el servicio del clima."
        ],
        'image_generated' => [
            'en' => "Here's an image of \"{prompt}\":",
            'es' => "Aquí tienes una imagen de \"{prompt}\":"
        ],
        'image_error' => [
            'en' => "Sorry, I couldn't generate an image for \"{prompt}\". {error}",
            'es' => "Lo siento, no pude generar una imagen de \"{prompt}\". {error}"
        ],
        'bot_greeting' => [
            'en' => "Hello! I'm Ada. I can help you with weather information, generate images, search Wikipedia, and have general conversations. What can I help you with today?",
            'es' => "¡Hola! Soy Ada. Puedo ayudarte con información del clima, generar imágenes, buscar en Wikipedia y tener conversaciones generales. ¿En qué puedo ayudarte hoy?"
        ]
    ];
    
    return $translations[$key][$lang] ?? $translations[$key]['es'] ?? $key;
}

/**
 * Format translation with variables
 * 
 * @param string $template Template string with placeholders
 * @param array $variables Variables to replace in template
 * @return string Formatted string
 */
function formatTranslation($template, $variables = []) {
    foreach ($variables as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    return $template;
}

/**
 * Create logs directory if it doesn't exist
 */
function ensureLogsDirectory() {
    $logsDir = dirname(ERROR_LOG_PATH);
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
}

// Initialize logs directory
ensureLogsDirectory();

/**
 * Get system configuration status
 * 
 * @return array System status information
 */
function getSystemStatus() {
    return [
        'php_version' => phpversion(),
        'curl_available' => function_exists('curl_init'),
        'json_available' => function_exists('json_encode'),
        'openssl_available' => extension_loaded('openssl'),
        'apis_configured' => [
            'hugging_face' => !empty(API_TOKEN) && API_TOKEN !== 'your_token_here',
            'weather' => !empty(API_WEATHER_KEY) && API_WEATHER_KEY !== 'tu_clave_openweathermap',
            'image' => !empty(API_IMAGE_KEY) && API_IMAGE_KEY !== 'your_starry_ai_api_key_here'
        ],
        'debug_mode' => DEBUG_MODE,
        'supported_languages' => SUPPORTED_LANGUAGES
    ];
}
?>