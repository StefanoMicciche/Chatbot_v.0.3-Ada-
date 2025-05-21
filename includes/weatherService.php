<?php

require_once __DIR__ . '/config.php';

class WeatherService {
    private $apiKey;
    private $apiUrl;

    public function __construct($apiKey, $apiUrl) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

     /**
     * Obtain weather data for a given location.
     * 
     * @param string $location Name of the city or location
     * @return array Data of the weather or error message
     */

     public function getWeather($location) {
        try {
            $url = $this->buildRequestUrl($location);
            $response = $this->makeRequest($url);

            if (!$response){
                return [
                    'status' => 'error',
                    'message' => 'No response from the weather service.'
                ];
            }
            $data = json_decode($response, true);

            // Check if the response contains an error
            if (isset($data['cod']) && $data['cod'] !== 200) {
                return [
                    'status' => 'error',
                    'message' => $data['message'] ?? 'Error retrieving weather data.'
                ];
            }

            // Format response
            return [
                'status' => 'success',
                'city' => isset($data['name']) ? $data['name'] : " ",
                'country' => isset($data['sys']['country']) ? $data['sys']['country'] : " ",
                'temperature' => isset($data['main']['temp']) ? $this->kelvinToCelsius($data['main']['temp']) : " ",
                'feels_like' => isset($data['main']['feels_like']) ? $this->kelvinToCelsius($data['main']['feels_like']) : " ",
                'humidity' => isset($data['main']['humidity']) ? $data['main']['humidity'] : " ",
                'wind_speed' => isset($data['wind']['speed']) ? $data['wind']['speed'] : " ",
                'description' => isset($data['weather'][0]['description']) ? $data['weather'][0]['description'] : " ",
                'icon' => isset($data['weather'][0]['icon']) ? $data['weather'][0]['icon'] : " ",
            ];
        } catch (exception $e) {
            logError('Error in WeatherService: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'An error occurred while retrieving weather data.'
            ];
        }
    }

        /**
     * Build the request URL for the weather API.
     */

    private function buildRequestUrl($location) {
        $location = urlencode($location);
        return "{$this->apiUrl}?q={$location}&appid={$this->apiKey}&lang=" . DEFAULT_LANG;
    }

    /**
     * Make a HTTP request to the API.
     */

    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);

        if (curl_errno($ch)){
            logError ("Error in WeatherService: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $response;
    }

    /**
     * Convert temperature from Kelvin to Celsius.
     */

     private function kelvinToCelsius($kelvin) {
        return round($kelvin - 273.15, 2);
}
}