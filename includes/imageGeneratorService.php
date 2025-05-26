<?php
// includes/ImageGeneratorService.php
// Fixed version with proper class instantiation and functions

require_once __DIR__ . '/config.php';

class ImageGeneratorService {
    private $apiKey;
    private $apiUrl;
    private $fallbackService; // Fixed: Added missing property declaration

    public function __construct(){
        $this->apiKey = API_IMAGE_KEY;
        $this->apiUrl = API_IMAGE_URL;
        $this->fallbackService = 'lexica'; // Fixed: Changed from $this->fallBackService
    }

    /**
     * Generate an image or search for an image based on a prompt.
     * 
     * @param string $prompt Image description
     * @return array Image information or error message
     */
    public function generateImage($prompt){
        try {
            // Option 1: Use Starry.AI to generate images
            $starryResult = $this->generateStarryAI($prompt);
            if($starryResult['status'] === 'success'){
                return $starryResult;
            }

            // If StarryAI fails, use fallback service
            logError('StarryAI failed, using fallback service: ' . $starryResult['message']);
            return $this->generateWithFallbackService($prompt); // Fixed: Method name consistency

        } catch (Exception $e) {
            logError('Error in ImageGeneratorService: ' . $e->getMessage());
            // Try with fallback service
            return $this->generateWithFallbackService($prompt); // Fixed: Method name consistency
        }
    }

    /**
     * Generate an image using StarryAI.
     * 
     * @param string $prompt Image description
     * @return array Image information or error message
     */
    private function generateStarryAI($prompt){
        if(empty($this->apiKey) || $this->apiKey === 'API_IMAGE_KEY'){
            return [
                'status' => 'error',
                'message' => 'StarryAI API key is not configured properly.'
            ];
        }

        // Prepare data for StarryAI
        $requestData = [
            'prompt' => $prompt, 
            'style' => 'auto', // Might be: auto, realistic, artistic, anime, etc.
            'aspect_ratio' => '1:1', // 16:9, 1:1, 4:5, etc.
            'quality' => 'standard', // Might be: standard, high.
            'steps' => 50, // Number of steps for image generation.
            'guidance_scale' => 8, // How much the model should follow the prompt.
        ];

        $response = $this->makeStarryAIRequest($requestData);
        
        // Fixed: Check if response is false first
        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to StarryAI service.'
            ];
        }

        // Fixed: Decode the JSON response
        $data = json_decode($response, true);
        
        // Fixed: Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Invalid JSON response from StarryAI: ' . json_last_error_msg()
            ];
        }

        // Process StarryAI response
        if (isset($data['status']) && $data['status'] === 'success') {
            return [
                'status' => 'success',
                'image_url' => $data['image_url'],
                'thumb_url' => $data['thumbnail_url'] ?? $data['image_url'],
                'alt_description' => $prompt,
                'generation_id' => $data['id'] ?? uniqid(),
                'source' => 'StarryAI',
                'style' => $requestData['style'],
                'prompt_used' => $prompt,
            ];
        } elseif (isset($data['error'])){
            return [
                'status' => 'error',
                'message' => $data['error']['message'] ?? 'Unknown error from StarryAI.',
            ];
        } else {
            return [
                'status' => 'error',
                'message' => "Invalid StarryAI response format."
            ];
        }
    }

    /**
     * Make StarryAI HTTP request.
     * 
     * @param array $data Request data
     * @return string|false Response string or false on error
     */
    private function makeStarryAIRequest($data){
        $ch = curl_init();

        // Specific configuration for StarryAI
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '/generate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'User-Agent: AdaBot/1.0'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            logError("cURL error in Starry AI request: " . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            logError("StarryAI request failed with HTTP code: $httpCode. Response: $response");
            return false;
        }

        return $response;
    }

    /**
     * Generate image using fallback service
     * 
     * @param string $prompt Image description
     * @return array Image information or error message
     */
    private function generateWithFallbackService($prompt) { // Fixed: Method name consistency
        switch ($this->fallbackService) {
            case 'lexica':
                return $this->searchLexicaImage($prompt);
            case 'pollinations':
                return $this->generateWithPollinations($prompt);
            default:
                return $this->searchLexicaImage($prompt);
        }
    }

    /**
     * Search for images using Lexica.art (fallback service)
     * 
     * @param string $prompt Image description
     * @return array Image information or error message
     */
    private function searchLexicaImage($prompt){
        $query = urlencode($prompt);
        $url = "https://lexica.art/api/v1/search?q={$query}";

        $response = $this->makeRequest($url);

        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Lexica.art service.'
            ];
        }

        $data = json_decode($response, true);
        
        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Invalid JSON response from Lexica.art: ' . json_last_error_msg()
            ];
        }

        if (empty($data['images'])) {
            return [
                'status' => 'error',
                'message' => 'No images found matching your description on Lexica.art.'
            ];
        }

        // Obtain a random image from the results (limit to first 10)
        $images = array_slice($data['images'], 0, 10);
        $image = $images[array_rand($images)];

        return [
            'status' => 'success',
            'image_url' => $image['src'],
            'thumb_url' => $image['src'],
            'alt_description' => $prompt,
            'original_prompt' => $image['prompt'] ?? $prompt,
            'source' => 'Lexica.art (Fallback)',
        ];
    }

    /**
     * Generate with Pollinations AI (free service)
     * 
     * @param string $prompt Image description
     * @return array Image information or error message
     */
    private function generateWithPollinations($prompt){
        // Fixed: Variable name typo
        $encodedPrompt = urlencode($prompt); // Was $encodePrompt
        $imageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512&model=flux";

        // Verify that the image can be loaded
        $headers = @get_headers($imageUrl, 1); // Added @ to suppress warnings
        
        if ($headers && strpos($headers[0], '200') !== false) {
            return [
                'status' => 'success',
                'image_url' => $imageUrl,
                'thumb_url' => $imageUrl,
                'alt_description' => $prompt,
                'source' => 'Pollinations AI (Fallback)',
                'prompt_used' => $prompt,
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Failed to generate image with Pollinations AI.'
        ];
    }

    /** 
     * Make HTTP request to a given URL.
     * 
     * @param string $url The URL to make request to
     * @return string|false Response string or false on error
     */
    private function makeRequest($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AdaBot/1.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Added to follow redirects

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            logError("cURL error in makeRequest: " . curl_error($ch) . " for URL: $url");
            curl_close($ch);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for successful HTTP response
        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        } else {
            logError("HTTP error $httpCode for URL: $url");
            return false;
        }
    }

    /**
     * Get available styles for image generation
     * 
     * @return array Array of available styles
     */
    public function getAvailableStyles() {
        return [
            'auto' => 'Automatic',
            'realistic' => 'Realistic',
            'artistic' => 'Artistic',
            'anime' => 'Anime',
            'digital_art' => 'Digital Art',
            'oil_painting' => 'Oil Painting',
            'watercolor' => 'Watercolor',
            'sketch' => 'Sketch',
            'cyberpunk' => 'Cyberpunk',
            'fantasy' => 'Fantasy',
        ];
    }

    /**
     * Generate image with specific style.
     * 
     * @param string $prompt Image description
     * @param string $style Style to apply
     * @return array Image information or error message
     */
    public function generateImageWithStyle($prompt, $style = 'auto') {
        // Enhance the prompt with style-specific descriptions
        $styledPrompt = $prompt;
        
        if ($style !== 'auto') {
            $styleDescriptions = [
                'realistic' => 'photorealistic, high detail, professional photography',
                'artistic' => 'artistic, creative, beautiful art, masterpiece',
                'anime' => 'anime style, manga, japanese animation',
                'digital_art' => 'digital art, concept art, digital painting',
                'oil_painting' => 'oil painting style, traditional art, painterly',
                'watercolor' => 'watercolor painting, soft colors, flowing paint',
                'sketch' => 'pencil sketch, black and white drawing, hand drawn',
                'cyberpunk' => 'cyberpunk style, neon, futuristic, sci-fi',
                'fantasy' => 'fantasy art, magical, ethereal, mystical'
            ];
            
            if (isset($styleDescriptions[$style])) {
                $styledPrompt = $prompt . ', ' . $styleDescriptions[$style];
            }
        }
        
        return $this->generateImage($styledPrompt);
    }

    /**
     * Check service status and configuration
     * 
     * @return array Status information
     */
    public function getServiceStatus() {
        return [
            'starry_ai' => [
                'configured' => !empty($this->apiKey) && $this->apiKey !== 'API_IMAGE_KEY',
                'api_key_length' => strlen($this->apiKey),
                'api_url' => $this->apiUrl
            ],
            'fallback_service' => $this->fallbackService,
            'available_services' => ['starry_ai', 'lexica', 'pollinations'],
            'curl_available' => function_exists('curl_init')
        ];
    }

    /**
     * Set fallback service
     * 
     * @param string $service Service name (lexica, pollinations)
     */
    public function setFallbackService($service) {
        $validServices = ['lexica', 'pollinations'];
        if (in_array($service, $validServices)) {
            $this->fallbackService = $service;
        }
    }

    function saveImageLocally($imageUrl, $filename) {
    $imageData = file_get_contents($imageUrl);
    if ($imageData !== false) {
        $localPath = "generated_images/" . $filename;
        
        // Crear carpeta si no existe
        if (!is_dir("generated_images")) {
            mkdir("generated_images", 0755, true);
        }
        
        file_put_contents($localPath, $imageData);
        return $localPath;
    }
    return false;
}

}
?>