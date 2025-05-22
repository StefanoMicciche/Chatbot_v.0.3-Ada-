<?php

//WeatherServiceTest.php - Version with Json format
//Avoid cache for testing
header("Cache-control: No cache, no store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: text/html; charset=utf-8');

//Show error for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

//Include configuration and service
require '../includes/config.php';
require '../includes/weatherService.php';

// Function to show JSON arrays
function displayJsonResult($data, $title = "Resultado")
{
    echo "<div class='result-container'>";
    echo "<h3 class='result-title'>$title</h3>";

    // Show status with color
    if (isset($data['status'])) {
        $statusClass = $data['status'] === 'success' ? 'status-success' : 'status-error';
        echo "<div class='status-badge $statusClass'>Status: " . $data['status'] . "</div>";
    }

    // Mostrar JSON formated
    echo "<pre class='json-display'>";
    echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre>";
    echo "</div>";
}

// Function to show information of the weather
function displayWeatherInfo($data)
{
    if ($data['status'] === 'success') {
        echo "<div class='weather-summary'>";
        echo "<>h4>Weather Summary</h4>";
        echo "<div class='weather-grid'>";
        echo "<div class='weather-item'><span class='label'>City:</span> " . ($data['city'] ?: 'N/A') . "</div>";
        echo "<div class='weather-item'><span class='label'>Country:</span> " . ($data['country'] ?: 'N/A') . "</div>";
        echo "<div class='weather-item'><span class='label'>Temperature:</span> " . ($data['temperature'] ?: 'N/A') . "°C</div>";
        echo "<div class='weather-item'><span class='label'>Feels Like:</span> " . ($data['feels_like'] ?: 'N/A') . "°C</div>";
        echo "<div class='weather-item'><span class='label'>Humidity:</span> " . ($data['humidity'] ?: 'N/A') . "%</div>";
        echo "<div class='weather-item'><span class='label'>Wind Speed:</span> " . ($data['wind_speed'] ?: 'N/A') . " m/s</div>";
        echo "<div class='weather-item'><span class='label'>Description:</span> " . ($data['description'] ?: 'N/A') . "</div>";
        echo "<div class='weather-item'><span class='label'>Icon:</span> " . ($data['icon'] ?: 'N/A') . "</div>";

        if ($data['icon']) {
            echo "<div class='weather-item weather-icon-container'>";
            echo "<span class='label'>Icono:</span>";
            echo "<img src='https://openweathermap.org/img/wn/" . $data['icon'] . "@2x.png' alt='" . $data['description'] . "' class='weather-icon'>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";
    }
}

echo '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Service Test - JSON Format</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f7fa;
            line-height: 1.6;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #34495e;
            margin-top: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
        }
        
        .test-info {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .result-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        
        .result-title {
            color: #2c3e50;
            margin-top: 0;
            font-size: 1.2em;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .json-display {
            background-color: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: "Fira Code", "Consolas", monospace;
            font-size: 14px;
            line-height: 1.5;
            border: 1px solid #4a5568;
        }
        
        .weather-summary {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .weather-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .weather-item {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .weather-icon-container {
            flex-direction: column;
            text-align: center;
        }
        
        .weather-icon {
            width: 50px;
            height: 50px;
        }
        
        .label {
            font-weight: bold;
            min-width: 80px;
        }
        
        .config-check {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #8a6d3b;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .test-cities {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .api-test {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .timestamp {
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
';

echo "<h1>Weather Service Test - JSON Format</h1>";

echo "<div class='test-info'>";
echo "<strong>Test information:</strong><br>";
echo "This test verifies functionality of WeatherService and its JSON format.<br>";
echo "<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Servidor:</strong> " . $_SERVER['SERVER_NAME'] . "<br>";
echo "</div>";

echo "<h2 1.  Verifying configuration</h2>";

$configData = [
    'enter_api_url' => defined('API_WEATHER_URL') ? API_WEATHER_URL : 'Not defined',
    'enter_api_key' => defined('API_WEATHER_KEY') && !empty(API_WEATHER_KEY),
    'weather_api_key_lenght' => defined('API_WEATHER_KEY') ? strlen(API_WEATHER_KEY) : 0,
    'curl_available' => function_exists('curl_init'),
    'php_version' => phpversion(),
    'systems_checks' => [
        'opcache_enable' => function_exists('opcache_get_status') ? opcache_get_status() : false,
        'timezone' => date_default_timezone_get(),
    ]
];

displayJsonResult($configData, "System Configuration");

// 2. Testing the API directly
echo "<h2>2. Testing the API directly</h2>";

if (defined(API_WEATHER_KEY) && !empty(API_WEATHER_KEY)) {
    $testCity = "Madrid";
    $apiUrl = API_WEATHER_URL . "?q=" . urlencode($testCity) . "&appid=" . API_WEATHER_KEY;

    echo "<div class='api-test'>";
    echo "<strong>Testing API URL:</strong> $apiUrl<br>";
    echo "<strong>City:</strong> $testCity<br>";
    echo "</div>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $startTime = microTime(true);
    $apiResponse = curl_exec($ch);
    $endTime = microTime(true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $apiResult = [
        'request_url' => $apiUrl,
        'http_code' => $httpCode,
        'response_time' => round(($endTime - $startTime) * 1000, 2),
        'response_size_bytes' => strlen($apiResponse),
        'curl_success' => $httpCode == !false,
        'json_valid' => false,
        'api_response' => null,
        'raw_response_preview' => substr($apiResponse, 0, 200) . '...',
    ];

    if ($apiResponse !== false) {
        $decodeResponse = json_decode($apiResponse, true);
        $apiTestResult['json_valid'] = json_last_error() === JSON_ERROR_NONE;

        if ($apiTestResult['json_valid']) {
            $apiTestResult['api_response_code'] = $decodeResponse['cod'] ?? 'not_provided';
            $apiResult['api_message'] = $decodeResponse['message'] ?? 'no_message';

            if ($httpCode == 200 && isset($decodeResponse['cod']) && $decodeResponse['cod'] == 200) {
                $apiTestResult['api_data_sample'] = [
                    'city' => $decodeResponse['name'] ?? null,
                    'coutry' => $decodeResponse['sys']['country'] ?? null,
                    'temperature' => $decodeResponse['main']['temp'] ?? null,
                    'feels_like' => $decodeResponse['main']['feels_like'] ?? null,
                    'humidity' => $decodeResponse['main']['humidity'] ?? null,
                    'wind_speed' => $decodeResponse['wind']['speed'] ?? null,
                    'description' => $decodeResponse['weather'][0]['description'] ?? null,
                    'icon' => $decodeResponse['weather'][0]['icon'] ?? null,
                ];
            }
        }
    }

    displayJsonResult($apiResult, "API Test Result");
} else {
    displayJsonResult([
        'satus' => 'error',
        'message' => 'API key not defined or empty. Please check your configuration.',
    ], "Configuration error");
}

// 
