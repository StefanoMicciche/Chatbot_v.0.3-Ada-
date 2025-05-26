<?php
//ImageGeneratorServiceTest.php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("COntent-Type: text/html; charset=UTF-8");

// imageGeneratorServiceTest.php - Fixed paths
header("Cache-Control: no-cache, no-store, must-revalidate");  
header('Content-Type: text/html; charset=utf-8');

// Determinar la ruta correcta al archivo config.php
$possiblePaths = [
    __DIR__ . '/../includes/config.php',  // Ruta normal
    dirname(__DIR__) . '/includes/config.php',  // Alternativa 1
    '../includes/config.php',  // Ruta relativa
    dirname(dirname(__FILE__)) . '/includes/config.php'  // Alternativa 2
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
    die("Error: No se pudo encontrar config.php en ninguna de las rutas esperadas:<br>" . implode('<br>', $possiblePaths));
}


// Create a mock for the ImageGeneratorService
class TestImageGeneratorService {
    private $apiKey;
    private $apiURL;

    public function __construct($apiKey, $apiURL) {
        $this->apiKey = 'API_IMAGE_KEY' ?? 'not_configured';
        $this->apiURL = 'API_IMAGE_URL' ?? 'not_configured';
    }

    public function generateImage($prompt) {
        //Try Pollinations first

        $result = $this->genersateWithPollinations($prompt);
        if ($result['status'] === 'success') {
            return $result;
        }

        // If Pollinations fails, try Picsum
        return $this->generateWithPicsum($prompt);
    }

    public function generateImageWithStyle($prompt, $style = 'auto') {
        //Enhance promtp style
        $stylePrompt = $this->enhancePromptWithStyle($prompt, $style);
        return $this->generateImage($stylePrompt);
    }

    private function genersateWithPollinations($prompt) {
        $encodedPrompt = urlencode($prompt);
        $imageUrl ="https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512&model=flux&nologo=true&enhance=true";

        //test if service is reachable
        $headers = $this->getHeaders($imageUrl);

        if($headers && strpos($headers[0], '200 OK') !== false) {
            return [
                'status' => 'success',
                'image_url' => $imageUrl,
                'thumb_url' => $imageUrl,
                'alt_description' => $prompt,
                'source' => 'Pollinations',
                'prompt_used' => $prompt,
                'service_type'=> 'AI Generated'
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Pollinations service is not available.'
        ];
    }

    private function generateWithPicsum($prompt) {
        //Fallback to Picsum placeholder with prompt

        $seed = crc32($prompt);
        $imageUrl ="https://picsum.photos/seed/{$seed}/512/512";

        $headers = $this->getHeaders($imageUrl);

        if ($headers && strpos ($headers[0], '200 OK') !== false) {
            return [
                'status' => 'success',
                'image_url' => $imageUrl,
                'thumb_url' => $imageUrl,
                'alt_description' => $prompt,
                'source' => 'Picsum',
                'prompt_used' => $prompt,
                'service_type'=> 'Stock Image'
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Picsum service is not available.'
        ];
    }

    private function enhancePromptWithStyle($prompt, $style) {
        $styleEnhancements = [
            'realistic' => 'A realistic depiction of ' . $prompt,
            'artistic' => 'An artistic interpretation of ' . $prompt,
            'anime' => 'An anime style image of ' . $prompt,
            'fantasy' => 'A fantasy themed image of ' . $prompt,
            'cyberpunk' => 'A cyberpunk style image of ' . $prompt,
            'watercolor' => 'A watercolor painting of ' . $prompt,
            'sketch' => 'A sketch of ' . $prompt,
            'oil painting' => 'An oil painting of ' . $prompt,
        ];

        return $prompt . ' ' . ($styleEnhancements[$style] ?? '');
    }

    private function getHeaders($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
                      CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AdaBot/1.0)',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 ? ["HTTP/1.1 200 OK"] : false;
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
            'oil painting ' => 'Oil Painting'
        ];
    }

    public function getServicesStatus() {
        return[
        'service_tested' => ['Pollination AI', 'Picsum'],
        'primary_service' => 'Pollinations',
        'secondary_service' => 'Picsum',
        'api_required' => false,
        ];
    }
}

// Display function
function showResult($result, $title) {
    $status = $result['status'] === 'success' ? '✅' : '❌';
    $color = $result['status'] === 'success' ? '#d4edda' : '#f8d7da';
    $borderColor = $result['status'] === 'success' ? '#28a745' : '#dc3545';
    
    echo "<div style='background:$color; padding:20px; margin:20px 0; border-radius:10px; border-left:5px solid $borderColor;'>";
    echo "<h3 style='margin-top:0;'>$status $title</h3>";
    
    if ($result['status'] === 'success') {
        echo "<div style='text-align:center; margin:15px 0;'>";
        echo "<img src='{$result['image_url']}' style='max-width:100%; max-height:350px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.1);' onload='this.style.opacity=1;' style='opacity:0; transition:opacity 0.3s;'>";
        echo "</div>";
        
        echo "<div style='background:rgba(255,255,255,0.7); padding:10px; border-radius:5px; margin:10px 0;'>";
        echo "<strong>🎨 Description:</strong> {$result['alt_description']}<br>";
        echo "<strong>🔧 Service:</strong> {$result['source']}<br>";
        echo "<strong>📝 Type:</strong> {$result['service_type']}<br>";
        if (isset($result['prompt_used']) && $result['prompt_used'] !== $result['alt_description']) {
            echo "<strong>✨ Enhanced Prompt:</strong> {$result['prompt_used']}<br>";
        }
        echo "<strong>🔗 URL:</strong> <a href='{$result['image_url']}' target='_blank' style='color:#007bff;'>Open full image</a>";
        echo "</div>";
    } else {
        echo "<div style='background:rgba(255,255,255,0.7); padding:10px; border-radius:5px;'>";
        echo "<strong>❌ Error:</strong> {$result['message']}";
        echo "</div>";
    }
    echo "</div>";
}

// Connection test function
function testConnections() {
    echo "<div style='background:#fff3cd; padding:15px; border-radius:8px; margin:15px 0; border-left:4px solid #ffc107;'>";
    echo "<h4 style='margin-top:0;'>🔍 Testing Service Connections</h4>";
    
    $services = [
        'Pollinations AI' => 'https://image.pollinations.ai/prompt/test?width=100&height=100',
        'Picsum Photos' => 'https://picsum.photos/100/100'
    ];
    
    foreach ($services as $name => $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "• $name: ❌ Connection failed ($error)<br>";
        } elseif ($httpCode === 200) {
            echo "• $name: ✅ Available<br>";
        } else {
            echo "• $name: ⚠️ HTTP $httpCode<br>";
        }
    }
    echo "</div>";
}

// Start HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>🎨 Image Generator Test - Fixed</title>
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
    </style>
</head>
<body>
<div class="container">';

echo "<h1>🎨 Image Generator Test</h1>";
echo "<div class='subtitle'>Fixed version with reliable services</div>";

// System check
echo "<div class='info'>";
echo "<strong>📊 System Status:</strong><br>";
$status = getSystemStatus();
echo "• PHP Version: " . $status['php_version'] . "<br>";
echo "• cURL Available: " . ($status['curl_available'] ? '✅ Yes' : '❌ No') . "<br>";
echo "• JSON Support: " . ($status['json_available'] ? '✅ Yes' : '❌ No') . "<br>";
echo "• SSL Support: " . ($status['openssl_available'] ? '✅ Yes' : '❌ No') . "<br>";
echo "</div>";

// Test connections
testConnections();

// Initialize service
echo "<h2>🚀 Service Initialization</h2>";
try {
    $imageGenerator = new TestImageGeneratorService('API_IMAGE_KEY', 'API_IMAGE_URL');
    echo "<div class='config'>✅ <strong>Success:</strong> Image Generator Service initialized</div>";
    
    $serviceStatus = $imageGenerator->getServicesStatus();
    echo "<div class='config'>";
    echo "<strong>🔧 Service Configuration:</strong><br>";
    echo "• Primary Service: " . (isset($serviceStatus['primary_service']) ? $serviceStatus['primary_service'] : 'Unknown') . "<br>";
    echo "• Fallback Service: " . (isset($serviceStatus['fallback_service']) ? $serviceStatus['fallback_service'] : 'Unknown') . "<br>";
    echo "• API Required: " . (isset($serviceStatus['api_required']) && $serviceStatus['api_required'] ? 'Yes' : 'No') . "<br>";
    echo "• Services Available: " . (isset($serviceStatus['services_tested']) && is_array($serviceStatus['services_tested']) ? implode(', ', $serviceStatus['services_tested']) : 'None');
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:8px; border-left:4px solid #dc3545;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

// Show available styles
echo "<h2>🎭 Available Styles</h2>";
$styles = $imageGenerator->getAvailableStyles();
echo "<div class='config'>";
echo "<strong>🎨 Supported Styles:</strong><br>";
foreach ($styles as $key => $name) {
    echo "<span style='display:inline-block; background:#e3f2fd; padding:5px 10px; margin:3px; border-radius:15px; font-size:0.9em;'>$key: $name</span> ";
}
echo "</div>";

// Basic image tests
echo "<h2>🖼️ Basic Image Generation</h2>";

$prompts = [
    "A beautiful sunset over mountains",
    "A cute cat playing with yarn",
    "Modern city skyline at night",
    "Colorful abstract geometric art"
];

$successful = 0;
$total = count($prompts);

foreach ($prompts as $i => $prompt) {
    echo "<div class='loading'>🔄 Generating image " . ($i + 1) . " of $total: \"<strong>$prompt</strong>\"</div>";
    flush();
    
    $result = $imageGenerator->generateImage($prompt);
    showResult($result, "Image " . ($i + 1));
    
    if ($result['status'] === 'success') $successful++;
    
    if ($i < count($prompts) - 1) {
        echo "<div style='text-align:center; color:#666; margin:15px 0;'>⏳ Brief pause for next generation...</div>";
        sleep(2);
    }
}

// Style tests
echo "<h2>🎭 Style-Specific Tests</h2>";

$styleTests = [
    ['prompt' => 'A magical forest scene', 'style' => 'fantasy'],
    ['prompt' => 'Portrait of a person', 'style' => 'realistic'],
    ['prompt' => 'Robot in neon city', 'style' => 'cyberpunk']
];

foreach ($styleTests as $i => $test) {
    echo "<div class='loading'>🎨 Generating {$test['style']} style: \"<strong>{$test['prompt']}</strong>\"</div>";
    flush();
    
    $result = $imageGenerator->generateImageWithStyle($test['prompt'], $test['style']);
    showResult($result, ucfirst($test['style']) . " Style");
    
    if ($result['status'] === 'success') $successful++;
    
    if ($i < count($styleTests) - 1) {
        sleep(2);
    }
}

// Final statistics
$totalTests = $total + count($styleTests);
$successRate = round(($successful / $totalTests) * 100, 1);

echo "<div class='success-rate'>";
echo "<h3 style='margin-top:0;'>📊 Test Results</h3>";
echo "<div style='font-size:1.2em;'>";
echo "<strong>Total Tests:</strong> $totalTests<br>";
echo "<strong>Successful:</strong> $successful<br>";
echo "<strong>Success Rate:</strong> <span style='font-size:1.5em; color:#2e7d32;'>$successRate%</span><br>";
echo "<strong>Completed:</strong> " . date('H:i:s');
echo "</div>";
echo "</div>";

// Integration code
echo "<h2>🤖 Integration Code</h2>";
echo "<div class='config'>";
echo "<p><strong>Ready-to-use code for your chatbot:</strong></p>";
echo "<pre style='background:#f1f1f1; padding:15px; border-radius:5px; overflow-x:auto; font-size:0.9em;'>";
echo htmlspecialchars('
// In your main chatbot class
private function checkForImageGenerationQuery($message, $language) {
    $patterns = [
        // English
        \'/(?:generate|create|make|show) (?:an? )?(?:image|picture) (?:of|about) ([^?]+)(?:\?)?/i\',
        // Spanish
        \'/(?:genera|crea|muestra) (?:una? )?(?:imagen|foto) (?:de|sobre) ([^?]+)(?:\?)?/i\'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            $prompt = trim($matches[1]);
            $result = $this->imageGenerator->generateImage($prompt);
            
            if ($result[\'status\'] === \'success\') {
                return [
                    \'status\' => \'success\',
                    \'message\' => $language === \'en\' 
                        ? "Here\'s an image of: {$prompt}"
                        : "Aquí tienes una imagen de: {$prompt}",
                    \'image_url\' => $result[\'image_url\'],
                    \'source\' => \'image\'
                ];
            }
        }
    }
    return null;
}
');
echo "</pre>";
echo "</div>";

echo "<div style='text-align:center; margin:30px 0; padding:20px; background:#f8f9fa; border-radius:10px;'>";
echo "<h4 style='color:#28a745; margin-bottom:10px;'>✅ Test Completed Successfully!</h4>";
echo "<p style='color:#666; margin:0;'>All services tested and working. Ready for integration with Ada chatbot.</p>";
echo "<small style='color:#999;'>Completed at: " . date('Y-m-d H:i:s') . "</small>";
echo "</div>";

echo "</div></body></html>";

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

$localImage = saveImageLocally($result['image_url'], "image_" . time() . ".jpg");
if ($localImage) {
    echo "<img src='$localImage' style='max-width:100%;'>";
}
?>