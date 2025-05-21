<?php
// WeatherServiceTest.php - Test class for WeatherService

// Includes necessary files
require '../includes/config.php';
require '../includes/weatherService.php';

// Function to show understadable arrays
function echo_array($array) {
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

echo "<h1>WeatherService Test</h1>";
echo "<hr>";

// Verify configuration
echo "<h2>Verifying Configuration</h2>";
echo "API URL configurated: " . (defined("WEATHER_API_URL") ? API_WEATHER_URL : "Not configured") . "<br>";
echo "API Key configurated: " . (defined("WEATHER_API_KEY") ? API_WEATHER_KEY : "Configured") . "<br>";
echo "cURL available: " . (function_exists('curl_version') ? "Yes" : "No") . "<br>";

//Test basic connection to API
echo "<h2>Testing Basic Connection to API</h2>";
$test_url = API_WEATHER_URL . "?q=Madrid&appid=" . API_WEATHER_KEY;
echo "URL de prueba: " . $test_url . "<br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

echo "<h3>Testing cURL</h3>";
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "cURL error: " . curl_error($ch);
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP Code: " . $httpCode . "<br>";

    if ($httpCode == 200) {
        echo "Connection successful!<br>";
        echo "Showing response: " . substr($response, 0, 100) . "...<br>"; 
    } else {
        echo "Error connecting to API. HTTP Code: " . $httpCode . "<br>";
        echo "Response: " . $response . "<br>";
    }
}

curl_close($ch);
echo "<hr>";

// Test service initialization
echo "<h2>Testing Service Initialization</h2>";
try {
    echo "Initializing WeatherService...<br>";
    $weatherService = new WeatherService(API_WEATHER_KEY, API_WEATHER_URL);
    echo "WeatherService initialized successfully!<br>";
} catch (Exception $e) {
    echo "ERROR initializing WeatherService: " . $e->getMessage() . "<br>";
    die("Testing cannot continue without a valid WeatherService instance.");
}
echo "<hr>";

// Testing conversion Kelvin to Celsius
echo "<h2>Testing Kelvin to Celsius Conversion</h2>";
try {
    $reflector = new ReflectionClass($weatherService);
    $method = $reflector->getMethod('kelvinToCelsius');
    $method->setAccessible(true);

    echo "273.15K is 0°C: ";
    $result = $method->invoke($weatherService, 273.15);
    echo "Result = {$result} °C" . ($result == 0 ? "OK" : "ERROR") . "<br>";

    echo "300K is 26.85°C: ";
    $result = $method->invoke($weatherService, 300);
    echo "Result = {$result} °C" . ($result == 26.85 ? "OK" : "ERROR") . "<br>";

} catch (Exception $e) {
    echo "ERROR testing kelvinToCelsius: " . $e->getMessage() . "<br>";
}

// Testing API with different cities
echo "<h2>Testing API with Different Cities</h2>";
$testCities = [
    'Madrid', 'New York', 'Tokyo', 'Sydney', 'UnknownCity'];

foreach ($testCities as $city) {
    echo "<h3>Testing Weather API with city: $city</h3>";

    echo "Requesting weather data for ('$city')...<br>";
    $result = $weatherService->getWeather($city);

    echo "Type of result: " . gettype($result) . "<br>";
    echo "Response status: " . ($resulst['status'] ?? 'Not defined') . "<br>";

    if (isset($result['stauts']) && $result['status'] === 'success') {
        echo "Success!<br>";
        echo "City: " . ($result['city'] ?? 'N/A') . "<br>";
        echo "Country: " . ($result['country'] ?? 'N/A') . "<br>";
        echo "Temperature: " . ($result['temperature'] ?? 'N/A') . "°C<br>";
        echo "Humidity: " . ($result['humidity'] ?? 'N/A') . "%<br>";
        echo "Wind Speed: " . ($result['wind_speed'] ?? 'N/A') . " m/s<br>";
        echo "Description: " . ($result['description'] ?? 'N/A') . "<br>";
    
              if (isset($result['icon'])) {
            echo "Icono: <img src='https://openweathermap.org/img/wn/" . $result['icon'] . "@2x.png' alt='" . ($result['description'] ?? 'clima') . "'><br>";
        }
    } else {
        echo "Error: " . ($result['message'] ?? 'No error message') . "<br>";
    }

    echo "Response: <br>";
    echo_array($result);
    echo "<hr>";
} 

//Show how to interact with Ada
echo "<h2>Example of integration</h2>";
echo "<h3>Request proccessing: \" How's the weater in Madrid?</h3>";

echo "1. Requesting weather data for ('Madrid')...<br>";
$message = "How's the weather in Madrid?";
$pattern = '/(?:weather|time|temperature) (?:es|en) ([a-zA-Záéíóúñ\s]+)(?:\?)?/i';

if (preg_match($pattern, $message, $matches)) {
    echo "City request detected<br>";
    echo "City extracted: " . trim($matches[1]) . "<br>";

    echo "2. Consulting weather service...<br>";
    $result = $weatherService->getWeather(trim($matches[1]));

    if ($result['status'] === 'success') {
        echo "3. Formatting answer for the user...<br>";

        $response = "The weather in {$result['city']}, {$result['country']}: {$result['description']}. ";
        $response .= "Temperature is: {$result['temperature']}°C, (feels like {$result['feels_like']}°C). ";
        $response .= "Humidity: {$result['humidity']}%, Wind speed: {$result['wind_speed']} m/s. ";

        echo "4. Final answer of Ada: <br>";
        echo "<div style='background-color: #f0f0f0; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Ada:</strong> " . $response . "<br>";
        echo "</div>";
    }else {
        echo "Error obtaining weather data: " . $result['message'] . "<br>";
    }
}