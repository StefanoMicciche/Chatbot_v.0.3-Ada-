<?php
// // WeatherServiceTest.php - Test class for WeatherService

// // Includes necessary files
require '../includes/config.php';
require '../includes/weatherService.php';

// // Function to show understadable arrays
// function echo_array($array) {
//     echo "<pre>";
//     print_r($array);
//     echo "</pre>";
// }

// echo "<h1>WeatherService Test</h1>";
// echo "<hr>";

// // Verify configuration
// echo "<h2>Verifying Configuration</h2>";
// echo "API URL configurated: " . (defined("WEATHER_API_URL") ? API_WEATHER_URL : "Not configured") . "<br>";
// echo "API Key configurated: " . (defined("WEATHER_API_KEY") ? API_WEATHER_KEY : "Configured") . "<br>";
// echo "cURL available: " . (function_exists('curl_version') ? "Yes" : "No") . "<br>";

// //Test basic connection to API
// echo "<h2>Testing Basic Connection to API</h2>";
// $test_url = API_WEATHER_URL . "?q=Madrid&appid=" . API_WEATHER_KEY;
// echo "URL de prueba: " . $test_url . "<br>";

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $test_url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_TIMEOUT, 5);

// echo "<h3>Testing cURL</h3>";
// $response = curl_exec($ch);

// if (curl_errno($ch)) {
//     echo "cURL error: " . curl_error($ch);
// } else {
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     echo "HTTP Code: " . $httpCode . "<br>";

//     if ($httpCode == 200) {
//         echo "Connection successful!<br>";
//         echo "Showing response: " . substr($response, 0, 100) . "...<br>"; 
//     } else {
//         echo "Error connecting to API. HTTP Code: " . $httpCode . "<br>";
//         echo "Response: " . $response . "<br>";
//     }
// }

// curl_close($ch);
// echo "<hr>";

// // Test service initialization
// echo "<h2>Testing Service Initialization</h2>";
// try {
//     echo "Initializing WeatherService...<br>";
//     $weatherService = new WeatherService(API_WEATHER_KEY, API_WEATHER_URL);
//     echo "WeatherService initialized successfully!<br>";
// } catch (Exception $e) {
//     echo "ERROR initializing WeatherService: " . $e->getMessage() . "<br>";
//     die("Testing cannot continue without a valid WeatherService instance.");
// }
// echo "<hr>";

// // Testing conversion Kelvin to Celsius
// echo "<h2>Testing Kelvin to Celsius Conversion</h2>";
// try {
//     $reflector = new ReflectionClass($weatherService);
//     $method = $reflector->getMethod('kelvinToCelsius');
//     $method->setAccessible(true);

//     echo "273.15K is 0°C: ";
//     $result = $method->invoke($weatherService, 273.15);
//     echo "Result = {$result} °C" . ($result == 0 ? "OK" : "ERROR") . "<br>";

//     echo "300K is 26.85°C: ";
//     $result = $method->invoke($weatherService, 300);
//     echo "Result = {$result} °C" . ($result == 26.85 ? "OK" : "ERROR") . "<br>";

// } catch (Exception $e) {
//     echo "ERROR testing kelvinToCelsius: " . $e->getMessage() . "<br>";
// }

// // Testing API with different cities

// echo "Código HTTP: " . $httpCode . "<br>";

// if ($httpCode == 200) {
//     echo "Connection succesfully<br>";
    
//     $data = json_decode($response, true);
//     echo "<h3>Full response of the API: </h3>";
//     echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
// }

// echo "<h2>Testing API with Different Cities</h2>";
// $testCities = [
//     'Madrid', 'New York', 'Tokyo', 'Sydney', 'UnknownCity'];

// foreach ($testCities as $city) {
//     echo "<h3>Testing Weather API with city: $city</h3>";

//     echo "Requesting weather data for ('$city')...<br>";
//     $result = $weatherService->getWeather($city);

//     echo "Type of result: " . gettype($result) . "<br>";
//     echo "Response status: " . ($resulst['status'] ?? 'Not defined') . "<br>";

//     if (isset($result['stauts']) && $result['status'] === 'success' ?? 'error') {
//         echo "Success!<br>";
//         echo "City: " . ($result['city'] ?? 'N/A') . "<br>";
//         echo "Country: " . ($result['country'] ?? 'N/A') . "<br>";
//         echo "Temperature: " . ($result['temperature'] ?? 'N/A') . "°C<br>";
//         echo "Feels Like: " . ($result['feels_like'] ?? 'N/A') . "°C<br>";
//         echo "Humidity: " . ($result['humidity'] ?? 'N/A') . "%<br>";
//         echo "Wind Speed: " . ($result['wind_speed'] ?? 'N/A') . " m/s<br>";
//         echo "Description: " . ($result['description'] ?? 'N/A') . "<br>";
//         echo "Icon: " . ($result['icon'] ?? 'N/A') . "<br>";
    
//               if (isset($result['icon'])) {
//             echo "Icono: <img src='https://openweathermap.org/img/wn/" . $result['icon'] . "@2x.png' alt='" . ($result['description'] ?? 'clima') . "'><br>";
//         }
//     } else {
//         echo "Error: " . ($result['message'] ?? 'No error message') . "<br>";
//     }

//     echo "Response: <br>";
//     echo_array($result);
//     echo "<hr>";
// } 

// //Show how to interact with Ada
// echo "<h2>Example of integration</h2>";
// echo "<h3>Request proccessing: \" How's the weater in Madrid?</h3>";

// echo "1. Requesting weather data for ('Madrid')...<br>";
// $message = "How's the weather in Madrid?";
// $pattern = '/(?:weather|time|temperature) (?:es|en) ([a-zA-Záéíóúñ\s]+)(?:\?)?/i';

// if (preg_match($pattern, $message, $matches)) {
//     echo "City request detected<br>";
//     echo "City extracted: " . trim($matches[1]) . "<br>";

//     echo "2. Consulting weather service...<br>";
//     $result = $weatherService->getWeather(trim($matches[1]));

//     if ($result['status'] === 'success') {
//         echo "3. Formatting answer for the user...<br>";

//         $response = "The weather in {$result['city']}, {$result['country']}: {$result['description']}. ";
//         $response .= "Temperature is: {$result['temperature']}°C, (feels like {$result['feels_like']}°C). ";
//         $response .= "Humidity: {$result['humidity']}%, Wind speed: {$result['wind_speed']} m/s. ";

//         echo "4. Final answer of Ada: <br>";
//         echo "<div style='background-color: #f0f0f0; padding: 10px; border-radius: 5px;'>";
//         echo "<strong>Ada:</strong> " . $response . "<br>";
//         echo "</div>";
//     }else {
//         echo "Error obtaining weather data: " . $result['message'] . "<br>";
//     }
// }

// Function to display JSON results
function displayJsonResult($data, $title = '') {
    echo "<div style='background:#f8f9fa; padding:10px; border-radius:5px; margin:10px 0;'>";
    if ($title) echo "<strong>$title:</strong><br>";
    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    echo "</div>";
}

//Show how to interact with Ada
echo "<h2>🤖 Example of integration</h2>";
echo "<h3>Request processing: \"How's the weather in...\"</h3>";

// Definir el mensaje de prueba AQUÍ (no más abajo)
$testMessage = "How's the weather in Madrid?";
$testMessage = "Como esta el clima en Londres?";
$testMessage = "Clima en Barcelona";
$testMessage = "Hows the weather in Venecia?";
$testMessage = "¿Cómo está el clima en Alicante?";


echo "1. Applying regex to detect weather query for: \"$testMessage\"<br>";

// Patrones mejorados para inglés y español
$patterns = [
    // Patrones en inglés
    '/(?:how\'s|how is|what\'s|what is) (?:the )?(?:weather|climate|temperature) (?:like )?(?:in|at|for) ([a-zA-Záéíóúñ\s]+)(?:\?)?/i',
    '/(?:weather|climate|temperature) (?:in|at|for) ([a-zA-Záéíóúñ\s]+)(?:\?)?/i',
    
    // Patrones en español  
    '/(?:cómo|como) (?:está|esta) (?:el )?(?:clima|tiempo) (?:en|de) ([a-zA-Záéíóúñ\s]+)(?:\?)?/i',
    '/(?:clima|tiempo|temperatura) (?:en|de) ([a-zA-Záéíóúñ\s]+)(?:\?)?/i'
];

$cityFound = null;
$patternMatched = false;
$matchedPatternIndex = -1;

// Probar cada patrón
foreach ($patterns as $index => $pattern) {
    if (preg_match($pattern, $testMessage, $matches)) {
        $patternMatched = true;
        $cityFound = trim($matches[1]);
        $matchedPatternIndex = $index;
        echo "✓ Weather query detected with pattern #" . ($index + 1) . "<br>";
        echo "✓ City extracted: <strong>" . $cityFound . "</strong><br>";
        break;
    }
}

if ($patternMatched && $cityFound) {
    echo "2. Requesting weather data for ('$cityFound')...<br>";
    
    // Verify that $weatherService exists
    if (!isset($weatherService)) {
        echo "⚠️ WeatherService not initialized. Initializing now...<br>";
        try {
            $weatherService = new WeatherService(API_WEATHER_KEY, API_WEATHER_URL);
            echo "✓ WeatherService initialized successfully<br>";
        } catch (Exception $e) {
            echo "✗ Error initializing WeatherService: " . $e->getMessage() . "<br>";
            echo "</div></body></html>";
            exit;
        }
    }
    
    $result = $weatherService->getWeather($cityFound);
    
    if ($result['status'] === 'success') {
        echo "✓ Weather data obtained successfully<br>";
        echo "3. Formatting response for the user...<br>";
        
        $response = "The weather in {$result['city']}, {$result['country']}: {$result['description']}. ";
        $response .= "Temperature is {$result['temperature']}°C (feels like {$result['feels_like']}°C). ";
        $response .= "Humidity: {$result['humidity']}%, Wind: {$result['wind_speed']} m/s.";
        
        echo "4. Final response from Ada:<br>";
        echo "<div style='background:#d1e7dd; padding:15px; border-radius:8px; border-left:4px solid #0f5132; margin:10px 0;'>";
        echo "🤖 <strong>Ada:</strong> $response";
        
        if (isset($result['icon']) && !empty($result['icon'])) {
            echo "<br><img src='https://openweathermap.org/img/wn/{$result['icon']}@2x.png' alt='{$result['description']}' style='width:50px;height:50px;'>";
        }
        echo "</div>";
        
        // Show JSON response for debugging
        echo "<h4>📋 Complete response data:</h4>";
        displayJsonResult($result, "Weather Service Response for Integration Example");
        
    } else {
        echo "✗ Error obtaining weather data: " . $result['message'] . "<br>";
        echo "<div style='background:#f8d7da; padding:10px; border-radius:5px; color:#721c24;'>";
        echo "🤖 <strong>Ada:</strong> Sorry, I couldn't get weather information for $cityFound. " . $result['message'];
        echo "</div>";
        
        // Show the error in JSON format
        displayJsonResult($result, "Error Response");
    }
} else {
    echo "✗ Weather query not detected with current patterns<br>";
    echo "<div style='background:#fff3cd; padding:10px; border-radius:5px; color:#8a6d3b; margin:10px 0;'>";
    echo "<strong>⚠️ Pattern matching failed</strong><br>";
    echo "The message \"<em>$testMessage</em>\" doesn't match any weather patterns.<br><br>";
    
    echo "<strong>🔍 Available patterns test:</strong><br>";
    
    // Define test messages to check against the patterns
    $testMessages = [
        "How's the weather in Madrid?",
        "What's the weather like in London?", 
        "Weather in Tokyo",
        "Temperature in Paris",
        "¿Cómo está el clima en Madrid?",
        "Clima en Barcelona",
        "Hows the weather in Venecia?",
    ];
    
    echo "<ul style='margin:10px 0;'>";
    foreach ($testMessages as $testMsg) {
        $matchFound = false;
        $matchingPattern = -1;
        
        foreach ($patterns as $patternIndex => $pattern) {
            if (preg_match($pattern, $testMsg, $testMatches)) {
                $matchFound = true;
                $matchingPattern = $patternIndex + 1;
                $extractedCity = isset($testMatches[1]) ? trim($testMatches[1]) : 'N/A';
                break;
            }
        }
        
        $statusIcon = $matchFound ? "✓" : "✗";
        $statusColor = $matchFound ? "green" : "red";
        $patternInfo = $matchFound ? " (Pattern #$matchingPattern, City: $extractedCity)" : "";
        
        echo "<li style='color:$statusColor; margin:5px 0;'>";
        echo "$statusIcon \"<em>$testMsg</em>\"$patternInfo";
        echo "</li>";
    }
    echo "</ul>";
    
    echo "<strong>🔧 Debugging info:</strong><br>";
    echo "• Total patterns available: " . count($patterns) . "<br>";
    echo "• Test message: \"$testMessage\"<br>";
    echo "• Pattern matched: " . ($patternMatched ? "Yes (#" . ($matchedPatternIndex + 1) . ")" : "No") . "<br>";
    
    echo "</div>";
}
?>