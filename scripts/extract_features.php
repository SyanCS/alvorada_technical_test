<?php
/**
 * Feature Extraction Script
 * 
 * Runs AI-powered feature extraction on all properties (or specific ones)
 * Extracts structured data from unstructured property notes using Gemini AI
 * 
 * Usage:
 *   php scripts/extract_features.php
 *   php scripts/extract_features.php --limit=10
 *   php scripts/extract_features.php --property-id=5
 *   php scripts/extract_features.php --force-refresh
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Load autoloader
require_once __DIR__ . '/../src/Config/Autoloader.php';

use App\Config\Container;
use App\Services\FeatureExtractionService;
use App\Repositories\PropertyRepository;
use App\Contracts\PropertyRepositoryInterface;

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Colors for terminal output
class Color {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const BOLD = "\033[1m";
}

// Parse command line arguments
function parseArgs($argv) {
    $options = [
        'limit' => null,
        'property_id' => null,
        'force_refresh' => false,
        'help' => false,
    ];
    
    foreach ($argv as $arg) {
        if (preg_match('/--limit=(\d+)/', $arg, $matches)) {
            $options['limit'] = (int)$matches[1];
        } elseif (preg_match('/--property-id=(\d+)/', $arg, $matches)) {
            $options['property_id'] = (int)$matches[1];
        } elseif ($arg === '--force-refresh' || $arg === '-f') {
            $options['force_refresh'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        }
    }
    
    return $options;
}

// Show help message
function showHelp() {
    echo Color::BOLD . Color::CYAN . "\n🤖 Feature Extraction Script\n" . Color::RESET;
    echo Color::WHITE . "Extracts structured features from property notes using AI\n\n" . Color::RESET;
    
    echo Color::YELLOW . "Usage:\n" . Color::RESET;
    echo "  php scripts/extract_features.php [options]\n\n";
    
    echo Color::YELLOW . "Options:\n" . Color::RESET;
    echo "  --limit=N          Process only N properties (default: all)\n";
    echo "  --property-id=ID   Process only specific property by ID\n";
    echo "  --force-refresh    Re-extract features even if they already exist\n";
    echo "  --help, -h         Show this help message\n\n";
    
    echo Color::YELLOW . "Examples:\n" . Color::RESET;
    echo "  php scripts/extract_features.php\n";
    echo "  php scripts/extract_features.php --limit=5\n";
    echo "  php scripts/extract_features.php --property-id=10\n";
    echo "  php scripts/extract_features.php --force-refresh\n";
    echo "  php scripts/extract_features.php --limit=10 --force-refresh\n\n";
    
    echo Color::YELLOW . "Requirements:\n" . Color::RESET;
    echo "  - GEMINI_API_KEY must be set in .env file\n";
    echo "  - Properties must have notes added before extraction\n\n";
}

// Extract features for a single property
function extractPropertyFeatures($extractionService, $propertyId, $propertyName, $index, $total, $forceRefresh) {
    $progress = sprintf("[%d/%d]", $index, $total);
    
    echo Color::CYAN . $progress . Color::RESET . " Processing: " . Color::BOLD . $propertyName . Color::RESET;
    echo Color::MAGENTA . " (ID: $propertyId)" . Color::RESET . "... ";
    
    try {
        $startTime = microtime(true);
        
        // Check if features already exist
        if (!$forceRefresh && $extractionService->hasFeatures($propertyId)) {
            echo Color::YELLOW . "⊘ Already extracted (use --force-refresh to re-extract)" . Color::RESET . "\n";
            return ['success' => true, 'skipped' => true];
        }
        
        // Extract features using AI
        $feature = $extractionService->extractFeaturesFromProperty($propertyId, $forceRefresh);
        
        $duration = round(microtime(true) - $startTime, 2);
        
        echo Color::GREEN . "✓ Success" . Color::RESET;
        echo Color::WHITE . " ({$duration}s)" . Color::RESET;
        
        // Show confidence score if available
        if ($feature->getConfidenceScore() !== null) {
            $confidence = round($feature->getConfidenceScore() * 100);
            $confidenceColor = $confidence >= 80 ? Color::GREEN : ($confidence >= 60 ? Color::YELLOW : Color::RED);
            echo " " . $confidenceColor . "[Confidence: {$confidence}%]" . Color::RESET;
        }
        
        echo "\n";
        
        // Show extracted key features
        $features = [];
        if ($feature->getNearSubway() !== null) {
            $features[] = $feature->getNearSubway() ? "🚇 Near subway" : "No subway";
        }
        if ($feature->getParkingAvailable() !== null) {
            $features[] = $feature->getParkingAvailable() ? "🅿️ Parking" : "No parking";
        }
        if ($feature->getHasElevator() !== null) {
            $features[] = $feature->getHasElevator() ? "🛗 Elevator" : "No elevator";
        }
        if ($feature->getConditionRating() !== null) {
            $stars = str_repeat("⭐", $feature->getConditionRating());
            $features[] = "Condition: {$stars}";
        }
        if ($feature->getRecommendedUse() !== null) {
            $features[] = "Use: " . ucfirst($feature->getRecommendedUse());
        }
        
        if (!empty($features)) {
            echo Color::WHITE . "    " . implode(" | ", $features) . "\n" . Color::RESET;
        }
        
        return ['success' => true, 'skipped' => false, 'duration' => $duration];
        
    } catch (Exception $e) {
        echo Color::RED . "✗ Error: " . $e->getMessage() . Color::RESET . "\n";
        return ['success' => false, 'skipped' => false, 'error' => $e->getMessage()];
    }
}

// Main extraction function
function extractAllFeatures($options) {
    echo Color::BOLD . Color::GREEN . "\n🤖 Starting Feature Extraction\n" . Color::RESET;
    echo Color::WHITE . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" . Color::RESET;
    
    try {
        // Initialize container and services
        $container = Container::getInstance();
        $extractionService = $container->get(FeatureExtractionService::class);
        $propertyRepository = $container->get(PropertyRepositoryInterface::class);
        
        // Check if Gemini is configured
        $geminiService = $container->get(\App\Services\GeminiService::class);
        if (!$geminiService->isConfigured()) {
            echo Color::RED . "\n❌ Error: GEMINI_API_KEY not configured in .env file\n" . Color::RESET;
            echo Color::YELLOW . "Please add your Gemini API key to continue.\n\n" . Color::RESET;
            return;
        }
        
        echo Color::GREEN . "✓ Gemini API configured\n" . Color::RESET;
        
        // Get properties to process
        if ($options['property_id'] !== null) {
            // Process single property
            $property = $propertyRepository->findById($options['property_id']);
            if (!$property) {
                echo Color::RED . "\n❌ Error: Property with ID {$options['property_id']} not found\n\n" . Color::RESET;
                return;
            }
            $properties = [$property];
        } else {
            // Process all properties (with optional limit)
            $totalCount = $propertyRepository->count();
            $limit = $options['limit'] ?? $totalCount;
            $properties = $propertyRepository->findAll($limit, 0);
        }
        
        $totalProperties = count($properties);
        
        if ($totalProperties === 0) {
            echo Color::YELLOW . "\n⚠️  No properties found to process\n" . Color::RESET;
            echo Color::WHITE . "Run: php scripts/seed_properties.php --with-notes\n\n" . Color::RESET;
            return;
        }
        
        echo Color::WHITE . "Properties to process: $totalProperties\n" . Color::RESET;
        echo Color::WHITE . "Force refresh: " . ($options['force_refresh'] ? 'Yes' : 'No') . "\n" . Color::RESET;
        echo Color::YELLOW . "\n⏳ Processing...\n\n" . Color::RESET;
        
        $startTime = microtime(true);
        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;
        $totalDuration = 0;
        $errors = [];
        
        foreach ($properties as $index => $property) {
            $result = extractPropertyFeatures(
                $extractionService,
                $property->getId(),
                $property->getName(),
                $index + 1,
                $totalProperties,
                $options['force_refresh']
            );
            
            if ($result['success']) {
                if ($result['skipped']) {
                    $skippedCount++;
                } else {
                    $successCount++;
                    if (isset($result['duration'])) {
                        $totalDuration += $result['duration'];
                    }
                }
            } else {
                $failCount++;
                $errors[] = [
                    'property_id' => $property->getId(),
                    'property_name' => $property->getName(),
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }
            
            // Small delay between requests to avoid rate limiting
            if ($index < $totalProperties - 1 && !$result['skipped']) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        $endTime = microtime(true);
        $totalTime = round($endTime - $startTime, 2);
        $avgDuration = $successCount > 0 ? round($totalDuration / $successCount, 2) : 0;
        
        // Summary
        echo Color::BOLD . Color::GREEN . "\n✅ Extraction Complete!\n" . Color::RESET;
        echo Color::WHITE . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" . Color::RESET;
        echo Color::GREEN . "Extracted: $successCount\n" . Color::RESET;
        if ($skippedCount > 0) {
            echo Color::YELLOW . "Skipped:   $skippedCount (already extracted)\n" . Color::RESET;
        }
        if ($failCount > 0) {
            echo Color::RED . "Failed:    $failCount\n" . Color::RESET;
        }
        echo Color::WHITE . "Total time: {$totalTime}s\n" . Color::RESET;
        if ($successCount > 0) {
            echo Color::WHITE . "Avg time per extraction: {$avgDuration}s\n" . Color::RESET;
        }
        echo Color::WHITE . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" . Color::RESET;
        
        // Show errors if any
        if (!empty($errors)) {
            echo Color::RED . "\n❌ Errors:\n" . Color::RESET;
            foreach ($errors as $error) {
                echo Color::RED . "  • " . Color::RESET;
                echo Color::WHITE . "{$error['property_name']} (ID: {$error['property_id']}): " . Color::RESET;
                echo Color::YELLOW . "{$error['error']}\n" . Color::RESET;
            }
            echo "\n";
        }
        
        // Next steps
        if ($successCount > 0) {
            echo Color::CYAN . "\n🎯 Next Steps:\n" . Color::RESET;
            echo Color::WHITE . "  1. View extracted features in the property details pages\n" . Color::RESET;
            echo Color::WHITE . "  2. Run property scoring: " . Color::YELLOW . "php scripts/score_properties.php\n" . Color::RESET;
            echo Color::WHITE . "  3. View API results: " . Color::YELLOW . "curl http://localhost:8080/api/properties.php\n\n" . Color::RESET;
        }
        
    } catch (Exception $e) {
        echo Color::RED . "\n❌ Fatal Error: " . $e->getMessage() . "\n" . Color::RESET;
        echo Color::YELLOW . $e->getTraceAsString() . "\n\n" . Color::RESET;
        exit(1);
    }
}

// Main execution
try {
    $options = parseArgs($argv);
    
    if ($options['help']) {
        showHelp();
        exit(0);
    }
    
    extractAllFeatures($options);
    
} catch (Exception $e) {
    echo Color::RED . "\n❌ Fatal Error: " . $e->getMessage() . "\n" . Color::RESET;
    exit(1);
}
