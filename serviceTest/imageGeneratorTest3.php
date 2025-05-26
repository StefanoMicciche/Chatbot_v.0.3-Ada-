<?php
//ImageGeneratorServiceTest.php - Corrected Version

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Content-Type: text/html; charset=UTF-8");

// Determine the correct path to config.php
$possiblePaths = [
    __DIR__ . '/../includes/config.php',
    dirname(__DIR__) . '/includes/config.php',
    '../includes/config.php',
    dirname(dirname(__FILE__)) . '/includes/config.php'
];

$configLoaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $configLoaded = true;
        echo "<!-- Config loaded from: $path -->";
        break;
    }
}

if (!$configLoaded) {
    die("Error: Cannot find config.php");
}

class TestImageGeneratorService {
    private $apiKey;
    private $apiURL;
    private $saveLocal;

    public function __construct($apiKey, $apiURL, $saveLocal = true) {
        $this->apiKey = $apiKey ?? 'not_configured';
        $this->apiURL = $apiURL ?? 'not_configured';
        $this->saveLocal = $saveLocal;
        
        // Create directory for local storage if it doesn't exist
        if ($this->saveLocal && !is_dir("generated_images")) {
            mkdir("generated_images", 0755, true);
        }
    }

    public function generateImage($prompt) {
        // Try to generate image with Pollinations first
        $result = $this->generateWithPollinations($prompt);
        if ($result['status'] === 'success') {
            return $result;
        }

        // If Pollinations fails, try with Picsum
        return $this->generateWithPicsum($prompt);
    }

    public function generateImageWithStyle($prompt, $style = 'auto') {
        $stylePrompt = $this->enhancePromptWithStyle($prompt, $style);
        return $this->generateImage($stylePrompt);
    }

    private function generateWithPollinations($prompt) {
        $encodedPrompt = urlencode($prompt);
        $imageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512&model=flux&nologo=true&enhance=true&seed=" . rand(1, 10000);

        // Verify if image is valid
        $validation = $this->validateAndProcessImage($imageUrl, $prompt, 'Pollinations');
        
        if ($validation['status'] === 'success') {
            return [
                'status' => 'success',
                'image_url' => $validation['final_url'],
                'thumb_url' => $validation['final_url'],
                'alt_description' => $prompt,
                'source' => 'Pollinations',
                'prompt_used' => $prompt,
                'service_type' => 'AI Generated',
                'local_path' => $validation['local_path'] ?? null
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Pollinations: ' . $validation['message']
        ];
    }

    private function generateWithPicsum($prompt) {
        $seed = crc32($prompt);
        $imageUrl = "https://picsum.photos/seed/{$seed}/512/512";

        $validation = $this->validateAndProcessImage($imageUrl, $prompt, 'Picsum');
        
        if ($validation['status'] === 'success') {
            return [
                'status' => 'success',
                'image_url' => $validation['final_url'],
                'thumb_url' => $validation['final_url'],
                'alt_description' => $prompt,
                'source' => 'Picsum',
                'prompt_used' => $prompt,
                'service_type' => 'Stock Photo',
                'local_path' => $validation['local_path'] ?? null
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Picsum: ' . $validation['message']
        ];
    }

    /**
     * Validate that image is valid and process it
     */
    private function validateAndProcessImage($imageUrl, $prompt, $source) {
        try {
            // Configure cURL options
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $imageUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ImageBot/1.0)',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_MAXREDIRS => 5
            ]);

            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            // Verify cURL url execution
            if ($error) {
                return [
                    'status' => 'error',
                    'message' => "Error de conexión: $error"
                ];
            }

            // Verify HTTP code
            if ($httpCode !== 200) {
                return [
                    'status' => 'error',
                    'message' => "HTTP Error: $httpCode"
                ];
            }

            // Verify that is an image
            if (!$this->isValidImageContent($contentType, $imageData)) {
                return [
                    'status' => 'error',
                    'message' => "Contenido no válido. Tipo: " . ($contentType ?? 'desconocido')
                ];
            }

            // Save locally
            $localPath = null;
            $finalUrl = $imageUrl;
            
            if ($this->saveLocal) {
                $savedPath = $this->saveImageLocally($imageData, $prompt, $source);
                if ($savedPath) {
                    $localPath = $savedPath;
                    $finalUrl = $savedPath; // Usar la ruta local como URL principal
                }
            }

            return [
                'status' => 'success',
                'final_url' => $finalUrl,
                'original_url' => $imageUrl,
                'local_path' => $localPath,
                'content_type' => $contentType,
                'size' => strlen($imageData)
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => "Excepción: " . $e->getMessage()
            ];
        }
    }

    /**
     * Verify that the image content is valid
     */
    private function isValidImageContent($contentType, $imageData) {
        // Verify tye MIME
        $validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $isValidMime = false;
        
        foreach ($validTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                $isValidMime = true;
                break;
            }
        }

        if (!$isValidMime) {
            return false;
        }

        // Verify is not empty
        if (empty($imageData) || strlen($imageData) < 100) {
            return false;
        }

        // Verify type of archive
        $signatures = [
            'jpeg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89\x50\x4E\x47"],
            'gif' => ["GIF87a", "GIF89a"],
            'webp' => ["RIFF"]
        ];

        $header = substr($imageData, 0, 10);
        foreach ($signatures as $format => $sigs) {
            foreach ($sigs as $sig) {
                if (strpos($header, $sig) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Save image locally
     */
    private function saveImageLocally($imageData, $prompt, $source) {
        try {
            $filename = $this->generateSafeFilename($prompt, $source);
            $localPath = "generated_images/" . $filename;
            
            if (file_put_contents($localPath, $imageData) !== false) {
                return $localPath;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generate a safe filename based on the prompt and source
     */
    private function generateSafeFilename($prompt, $source) {
        $safePrompt = preg_replace('/[^a-zA-Z0-9\s]/', '', $prompt);
        $safePrompt = preg_replace('/\s+/', '_', trim($safePrompt));
        $safePrompt = substr($safePrompt, 0, 30);
        
        $timestamp = date('Y-m-d_H-i-s');
        $extension = '.jpg';
        
        return strtolower($source) . '_' . $safePrompt . '_' . $timestamp . $extension;
    }

    private function enhancePromptWithStyle($prompt, $style) {
        $styleEnhancements = [
            'realistic' => $prompt . ', photorealistic, detailed, high quality',
            'artistic' => $prompt . ', artistic, creative, stylized',
            'anime' => $prompt . ', anime style, manga, Japanese animation',
            'fantasy' => $prompt . ', fantasy, magical, mystical, enchanted',
            'cyberpunk' => $prompt . ', cyberpunk, neon, futuristic, sci-fi',
            'watercolor' => $prompt . ', watercolor painting, artistic, soft colors',
            'sketch' => $prompt . ', pencil sketch, drawing, artistic',
            'oil_painting' => $prompt . ', oil painting, classical art, brushstrokes'
        ];

        return $styleEnhancements[$style] ?? $prompt;
    }

    public function getAvailableStyles() {
        return [
            'auto' => 'Automatic',
            'realistic' => 'Realistic',
            'artistic' => 'Artistic',
            'anime' => 'Anime',
            'fantasy' => 'Fantasy',
            'cyberpunk' => 'Cyberpunk',
            'watercolor' => 'Watercolor',
            'sketch' => 'Sketch',
            'oil_painting' => 'Oil Painting'
        ];
    }

    public function getServicesStatus() {
        return [
            'services_tested' => ['Pollinations AI', 'Picsum'],
            'primary_service' => 'Pollinations',
            'fallback_service' => 'Picsum',
            'api_required' => false,
            'local_storage' => $this->saveLocal
        ];
    }

    /**
     * Try connectivity with external services
     */
    public function testServices() {
        $services = [
            'Pollinations' => 'https://image.pollinations.ai/prompt/test?width=100&height=100',
            'Picsum' => 'https://picsum.photos/100/100'
        ];
        
        $results = [];
        foreach ($services as $name => $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $results[$name] = [
                'status' => $error ? 'error' : ($httpCode === 200 ? 'success' : 'warning'),
                'http_code' => $httpCode,
                'error' => $error
            ];
        }
        
        return $results;
    }
}

// Function to display better results
function showResult($result, $title) {
    $status = $result['status'] === 'success' ? '✅' : '❌';
    $color = $result['status'] === 'success' ? '#d4edda' : '#f8d7da';
    $borderColor = $result['status'] === 'success' ? '#28a745' : '#dc3545';
    
    echo "<div style='background:$color; padding:20px; margin:20px 0; border-radius:10px; border-left:5px solid $borderColor;'>";
    echo "<h3 style='margin-top:0;'>$status $title</h3>";
    
    if ($result['status'] === 'success') {
        echo "<div style='text-align:center; margin:15px 0;'>";
        echo "<img src='{$result['image_url']}' style='max-width:100%; max-height:350px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.1);' onload='this.style.opacity=1; console.log(\"Image loaded successfully\");' onerror='this.style.border=\"3px solid red\"; this.alt=\"Error loading image\"; console.error(\"Failed to load image: {$result['image_url']}\");' style='opacity:0; transition:opacity 0.3s;'>";
        echo "</div>";
        
        echo "<div style='background:rgba(255,255,255,0.7); padding:10px; border-radius:5px; margin:10px 0;'>";
        echo "<strong>🎨 Description:</strong> {$result['alt_description']}<br>";
        echo "<strong>🔧 Service:</strong> {$result['source']}<br>";
        echo "<strong>📝 Type:</strong> {$result['service_type']}<br>";
        
        if (isset($result['local_path'])) {
            echo "<strong>💾 Local archive:</strong> {$result['local_path']}<br>";
        }
        
        if (isset($result['prompt_used']) && $result['prompt_used'] !== $result['alt_description']) {
            echo "<strong>✨ Improved prompt:</strong> {$result['prompt_used']}<br>";
        }
        
        echo "<strong>🔗 URL original:</strong> <a href='{$result['image_url']}' target='_blank' style='color:#007bff;'>Open full image</a>";
        echo "</div>";
        
        // Button to try the image in a new window
        echo "<div style='text-align:center; margin:10px 0;'>";
        echo "<button onclick='window.open(\"{$result['image_url']}\", \"_blank\", \"width=600,height=600\")' style='background:#007bff; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;'>🔍 See in a new window</button>";
        echo "</div>";
        
    } else {
        echo "<div style='background:rgba(255,255,255,0.7); padding:10px; border-radius:5px;'>";
        echo "<strong>❌ Error:</strong> {$result['message']}";
        echo "</div>";
    }
    echo "</div>";
}

// Function to get system status
function getSystemsStatus() {
    return [
        'php_version' => phpversion(),
        'curl_available' => extension_loaded('curl'),
        'json_available' => extension_loaded('json'),
        'openssl_available' => extension_loaded('openssl'),
        'gd_available' => extension_loaded('gd'),
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}

// Function to try connections to services
function testConnections($imageGenerator) {
    echo "<div style='background:#fff3cd; padding:15px; border-radius:8px; margin:15px 0; border-left:4px solid #ffc107;'>";
    echo "<h4 style='margin-top:0;'>🔍 Trying connection with services</h4>";
    
    $results = $imageGenerator->testServices();
    
    foreach ($results as $name => $result) {
        $icon = $result['status'] === 'success' ? '✅' : ($result['status'] === 'warning' ? '⚠️' : '❌');
        $message = $result['error'] ?: "HTTP {$result['http_code']}";
        echo "• $name: $icon $message<br>";
    }
    echo "</div>";
}

// HTML init
echo '<!DOCTYPE html>
<html>
<head>
    <title>🎨 Image Generator - Corrected Version</title>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-style: italic;
        }
        h2 { 
            color: #007bff; 
            border-bottom: 2px solid #007bff; 
            padding-bottom: 8px; 
            margin-top: 40px;
        }
        .info { 
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border-left: 4px solid #2196f3;
        }
        .config { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
        }
        .loading {
            text-align: center;
            padding: 15px;
            background: #fff8e1;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .success-rate {
            background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #4caf50;
        }
        .debug-info {
            background: #f1f3f4;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">';

echo "<h1>🎨 Image Generator</h1>";

// System verification
echo "<div class='info'>";
echo "<strong>📊 System Status:</strong><br>";
$status = getSystemsStatus();
foreach ($status as $key => $value) {
    $displayKey = str_replace('_', ' ', ucfirst($key));
    $displayValue = is_bool($value) ? ($value ? '✅ Sí' : '❌ No') : $value;
    echo "• $displayKey: $displayValue<br>";
}
echo "</div>";

// Service init
echo "<h2>🚀 Service Initialization</h2>";
try {
    $imageGenerator = new TestImageGeneratorService('API_IMAGE_KEY', 'API_IMAGE_URL', true);
    echo "<div class='config'>✅ <strong>Success:</strong> Image generation service initialized</div>";
    
    $serviceStatus = $imageGenerator->getServicesStatus();
    echo "<div class='config'>";
    echo "<strong>🔧 Service Configuration:</strong><br>";
    foreach ($serviceStatus as $key => $value) {
        $displayKey = str_replace('_', ' ', ucfirst($key));
        $displayValue = is_array($value) ? implode(', ', $value) : ($value === true ? 'Sí' : ($value === false ? 'No' : $value));
        echo "• $displayKey: $displayValue<br>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:8px; border-left:4px solid #dc3545;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

// Try connections
testConnections($imageGenerator);

// Available styles
echo "<h2>🎭 Available Styles</h2>";
$styles = $imageGenerator->getAvailableStyles();
echo "<div class='config'>";
echo "<strong>🎨 Supported Styles:</strong><br>";
foreach ($styles as $key => $name) {
    echo "<span style='display:inline-block; background:#e3f2fd; padding:5px 10px; margin:3px; border-radius:15px; font-size:0.9em;'>$key: $name</span> ";
}
echo "</div>";

// Basic image generation tests
echo "<h2>🖼️ Basic image generator test</h2>";

$prompts = [
    "Beautiful landscape with mountains and a river",
    "A cat playing with a ball of yarn",
    "A futuristic city skyline at night",
    "A serene beach with palm trees and a sunset",
];

$successful = 0;
$total = count($prompts);

foreach ($prompts as $i => $prompt) {
    echo "<div class='loading'>🔄 Generating image " . ($i + 1) . " de $total: \"<strong>$prompt</strong>\"</div>";
    flush();
    
    $result = $imageGenerator->generateImage($prompt);
    showResult($result, "Image " . ($i + 1));
    
    if ($result['status'] === 'success') $successful++;
    
    if ($i < count($prompts) - 1) {
        echo "<div style='text-align:center; color:#666; margin:15px 0;'>⏳ Brief pause for generation... </div>";
        sleep(2);
    }
}

// Style-specific tests
echo "<h2>🎭 Specific styles test</h2>";

$styleTests = [
    ['prompt' => 'A magic forest', 'style' => 'fantasy'],
    ['prompt' => 'Person portrait', 'style' => 'realistic']
];

foreach ($styleTests as $i => $test) {
    echo "<div class='loading'>🎨 Generating Style {$test['style']}: \"<strong>{$test['prompt']}</strong>\"</div>";
    flush();
    
    $result = $imageGenerator->generateImageWithStyle($test['prompt'], $test['style']);
    showResult($result, "Style " . ucfirst($test['style']));
    
    if ($result['status'] === 'success') $successful++;
    
    if ($i < count($styleTests) - 1) {
        sleep(2);
    }
}

// Final results
$totalTests = $total + count($styleTests);
$successRate = $totalTests > 0 ? round(($successful / $totalTests) * 100, 1) : 0;

echo "<div class='success-rate'>";
echo "<h3 style='margin-top:0;'>📊 Test results</h3>";
echo "<div style='font-size:1.2em;'>";
echo "<strong>Total Tests:</strong> $totalTests<br>";
echo "<strong>Success:</strong> $successful<br>";
echo "<strong>Success Rate:</strong> <span style='font-size:1.5em; color:#2e7d32;'>$successRate%</span><br>";
echo "<strong>Completado:</strong> " . date('H:i:s');
echo "</div>";
echo "</div>";

echo "<div style='text-align:center; margin:30px 0; padding:20px; background:#f8f9fa; border-radius:10px;'>";
echo "<h4 style='color:#28a745; margin-bottom:10px;'>✅ Test  Completed!</h4>";
echo "<p style='color:#666; margin:0;'>Services tested and ready for integration.</p>";
echo "<small style='color:#999;'>Completed: " . date('Y-m-d H:i:s') . "</small>";
echo "</div>";

echo "</div></body></html>";
?>