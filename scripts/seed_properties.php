<?php
/**
 * Property Seeder Script
 * 
 * Seeds the database with sample US properties using the API
 * 
 * Usage:
 *   php scripts/seed_properties.php
 *   php scripts/seed_properties.php --count=10
 *   php scripts/seed_properties.php --clear
 */

// Configuration
$API_BASE_URL = getenv('API_URL') ?: 'http://localhost:8080';
$DELAY_SECONDS = 1; // Delay between requests to respect Nominatim rate limits

// Sample US properties to seed
$PROPERTIES = [
    // Landmarks
    [
        'name' => 'Empire State Building',
        'address' => '20 W 34th St, New York, NY 10001'
    ],
    [
        'name' => 'Statue of Liberty',
        'address' => 'Liberty Island, New York, NY 10004'
    ],
    [
        'name' => 'Golden Gate Bridge',
        'address' => 'Golden Gate Bridge, San Francisco, CA 94129'
    ],
    [
        'name' => 'Space Needle',
        'address' => '400 Broad St, Seattle, WA 98109'
    ],
    [
        'name' => 'Willis Tower',
        'address' => '233 S Wacker Dr, Chicago, IL 60606'
    ],
    
    // Tech Company HQs
    [
        'name' => 'Apple Park',
        'address' => '1 Apple Park Way, Cupertino, CA 95014'
    ],
    [
        'name' => 'Google Headquarters',
        'address' => '1600 Amphitheatre Parkway, Mountain View, CA 94043'
    ],
    [
        'name' => 'Microsoft Campus',
        'address' => '1 Microsoft Way, Redmond, WA 98052'
    ],
    [
        'name' => 'Meta Headquarters',
        'address' => '1 Hacker Way, Menlo Park, CA 94025'
    ],
    [
        'name' => 'Amazon HQ',
        'address' => '410 Terry Ave N, Seattle, WA 98109'
    ],
    [
        'name' => 'Tesla Gigafactory Texas',
        'address' => '1 Tesla Rd, Austin, TX 78725'
    ],
    
    // Government Buildings
    [
        'name' => 'The White House',
        'address' => '1600 Pennsylvania Avenue NW, Washington, DC 20500'
    ],
    [
        'name' => 'US Capitol',
        'address' => 'First St SE, Washington, DC 20004'
    ],
    [
        'name' => 'The Pentagon',
        'address' => 'Washington Blvd, Arlington, VA 22202'
    ],
    
    // Universities
    [
        'name' => 'Harvard University',
        'address' => 'Massachusetts Hall, Cambridge, MA 02138'
    ],
    [
        'name' => 'MIT',
        'address' => '77 Massachusetts Ave, Cambridge, MA 02139'
    ],
    [
        'name' => 'Stanford University',
        'address' => '450 Serra Mall, Stanford, CA 94305'
    ],
    [
        'name' => 'Yale University',
        'address' => '149 Elm St, New Haven, CT 06511'
    ],
    
    // Sports Venues
    [
        'name' => 'Yankee Stadium',
        'address' => '1 E 161st St, Bronx, NY 10451'
    ],
    [
        'name' => 'Wrigley Field',
        'address' => '1060 W Addison St, Chicago, IL 60613'
    ],
    [
        'name' => 'Fenway Park',
        'address' => '4 Jersey St, Boston, MA 02215'
    ],
    
    // Entertainment
    [
        'name' => 'Hollywood Sign',
        'address' => 'Hollywood Sign, Los Angeles, CA 90068'
    ],
    [
        'name' => 'Disneyland',
        'address' => '1313 Disneyland Dr, Anaheim, CA 92802'
    ],
    [
        'name' => 'Magic Kingdom',
        'address' => '1180 Seven Seas Dr, Lake Buena Vista, FL 32830'
    ],
    [
        'name' => 'Universal Studios Hollywood',
        'address' => '100 Universal City Plaza, Universal City, CA 91608'
    ],
    
    // Hotels & Casinos
    [
        'name' => 'Bellagio Hotel',
        'address' => '3600 S Las Vegas Blvd, Las Vegas, NV 89109'
    ],
    [
        'name' => 'Caesars Palace',
        'address' => '3570 S Las Vegas Blvd, Las Vegas, NV 89109'
    ],
    
    // Airports
    [
        'name' => 'LAX Airport',
        'address' => '1 World Way, Los Angeles, CA 90045'
    ],
    [
        'name' => 'JFK Airport',
        'address' => 'Queens, NY 11430'
    ],
    [
        'name' => 'O\'Hare Airport',
        'address' => '10000 W O\'Hare Ave, Chicago, IL 60666'
    ],
    
    // Historic Sites
    [
        'name' => 'Alamo',
        'address' => '300 Alamo Plaza, San Antonio, TX 78205'
    ],
    [
        'name' => 'Mount Rushmore',
        'address' => '13000 SD-244, Keystone, SD 57751'
    ],
    [
        'name' => 'Independence Hall',
        'address' => '520 Chestnut St, Philadelphia, PA 19106'
    ],
    [
        'name' => 'Gateway Arch',
        'address' => 'Gateway Arch, St Louis, MO 63102'
    ],
    
    // Shopping & Retail
    [
        'name' => 'Mall of America',
        'address' => '60 E Broadway, Bloomington, MN 55425'
    ],
    [
        'name' => 'Times Square',
        'address' => 'Times Square, New York, NY 10036'
    ],
];

// Sample notes to add to properties
$SAMPLE_NOTES = [
    'Excellent location with high visibility',
    'Prime commercial district with strong foot traffic',
    'Historic landmark with significant cultural value',
    'Strategic location near major transportation hubs',
    'High-growth area with strong development potential',
    'Well-maintained property with modern amenities',
    'Ideal for corporate headquarters or regional office',
    'Strong market fundamentals and appreciation potential',
    'Located in AAA-rated commercial district',
    'Excellent access to public transportation',
];

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
        'count' => null,
        'clear' => false,
        'with-notes' => false,
        'help' => false,
    ];
    
    foreach ($argv as $arg) {
        if (preg_match('/--count=(\d+)/', $arg, $matches)) {
            $options['count'] = (int)$matches[1];
        } elseif ($arg === '--clear') {
            $options['clear'] = true;
        } elseif ($arg === '--with-notes') {
            $options['with-notes'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        }
    }
    
    return $options;
}

// Show help message
function showHelp() {
    echo Color::BOLD . Color::CYAN . "\n🌱 Property Seeder Script\n" . Color::RESET;
    echo Color::WHITE . "Seeds the database with sample US properties\n\n" . Color::RESET;
    
    echo Color::YELLOW . "Usage:\n" . Color::RESET;
    echo "  php scripts/seed_properties.php [options]\n\n";
    
    echo Color::YELLOW . "Options:\n" . Color::RESET;
    echo "  --count=N       Seed N properties (default: all " . count($GLOBALS['PROPERTIES']) . ")\n";
    echo "  --with-notes    Add sample notes to each property\n";
    echo "  --clear         Clear all properties before seeding\n";
    echo "  --help, -h      Show this help message\n\n";
    
    echo Color::YELLOW . "Examples:\n" . Color::RESET;
    echo "  php scripts/seed_properties.php\n";
    echo "  php scripts/seed_properties.php --count=10\n";
    echo "  php scripts/seed_properties.php --count=5 --with-notes\n";
    echo "  php scripts/seed_properties.php --clear\n\n";
}

// Make HTTP request
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: $error");
    }
    
    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

// Clear all properties
function clearProperties($apiUrl) {
    echo Color::YELLOW . "\n⚠️  Clearing all properties...\n" . Color::RESET;
    echo Color::RED . "This will delete ALL properties and notes!\n" . Color::RESET;
    echo "Are you sure? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) !== 'yes') {
        echo Color::YELLOW . "Cancelled.\n" . Color::RESET;
        return false;
    }
    
    // Note: This would require a DELETE API endpoint
    // For now, we'll just inform the user
    echo Color::RED . "\n⚠️  Manual clearing required:\n" . Color::RESET;
    echo "Run: docker exec alvorada_db psql -U alvorada_user -d alvorada_db -c \"TRUNCATE properties, notes CASCADE;\"\n\n";
    
    return true;
}

// Seed a single property
function seedProperty($apiUrl, $property, $index, $total) {
    $progress = sprintf("[%d/%d]", $index, $total);
    
    echo Color::CYAN . $progress . Color::RESET . " Seeding: " . Color::BOLD . $property['name'] . Color::RESET . "... ";
    
    try {
        $response = makeRequest(
            "$apiUrl/property/create",
            'POST',
            $property
        );
        
        if ($response['code'] === 200) {
            // Try to extract property ID from response
            preg_match('/property\.html\?id=(\d+)/', $response['body'], $matches);
            $propertyId = $matches[1] ?? null;
            
            echo Color::GREEN . "✓ Success" . Color::RESET;
            if ($propertyId) {
                echo Color::MAGENTA . " (ID: $propertyId)" . Color::RESET;
            }
            echo "\n";
            
            return ['success' => true, 'id' => $propertyId];
        } else {
            echo Color::RED . "✗ Failed (HTTP {$response['code']})" . Color::RESET . "\n";
            return ['success' => false, 'id' => null];
        }
    } catch (Exception $e) {
        echo Color::RED . "✗ Error: " . $e->getMessage() . Color::RESET . "\n";
        return ['success' => false, 'id' => null];
    }
}

// Add note to property
function addNote($apiUrl, $propertyId, $note) {
    try {
        $ch = curl_init("$apiUrl/api/add_note.php");
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'property_id' => (int)$propertyId,
            'note' => $note
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200 || $httpCode === 201;
    } catch (Exception $e) {
        return false;
    }
}

// Main seeding function
function seedDatabase($apiUrl, $properties, $options) {
    $count = $options['count'] ?? count($properties);
    $count = min($count, count($properties));
    
    $propertiesToSeed = array_slice($properties, 0, $count);
    
    echo Color::BOLD . Color::GREEN . "\n🌱 Starting Property Seeding\n" . Color::RESET;
    echo Color::WHITE . "API URL: $apiUrl\n" . Color::RESET;
    echo Color::WHITE . "Properties to seed: $count\n" . Color::RESET;
    echo Color::WHITE . "Add notes: " . ($options['with-notes'] ? 'Yes' : 'No') . "\n" . Color::RESET;
    echo Color::YELLOW . "\n⏳ Please wait, this may take a few minutes...\n\n" . Color::RESET;
    
    $startTime = microtime(true);
    $successCount = 0;
    $failCount = 0;
    $seededIds = [];
    
    foreach ($propertiesToSeed as $index => $property) {
        $result = seedProperty($apiUrl, $property, $index + 1, $count);
        
        if ($result['success']) {
            $successCount++;
            if ($result['id']) {
                $seededIds[] = $result['id'];
            }
        } else {
            $failCount++;
        }
        
        // Respect Nominatim rate limit (1 request per second)
        if ($index < count($propertiesToSeed) - 1) {
            sleep($GLOBALS['DELAY_SECONDS']);
        }
    }
    
    // Add notes if requested
    if ($options['with-notes'] && !empty($seededIds)) {
        echo Color::CYAN . "\n📝 Adding sample notes...\n" . Color::RESET;
        
        $notesAdded = 0;
        foreach ($seededIds as $propertyId) {
            // Add 1-3 random notes per property
            $noteCount = rand(1, 3);
            $selectedNotes = array_rand(array_flip($GLOBALS['SAMPLE_NOTES']), $noteCount);
            
            if (!is_array($selectedNotes)) {
                $selectedNotes = [$selectedNotes];
            }
            
            foreach ($selectedNotes as $note) {
                if (addNote($apiUrl, $propertyId, $note)) {
                    $notesAdded++;
                }
                sleep(1); // Rate limiting
            }
        }
        
        echo Color::GREEN . "✓ Added $notesAdded notes\n" . Color::RESET;
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    // Summary
    echo Color::BOLD . Color::GREEN . "\n✅ Seeding Complete!\n" . Color::RESET;
    echo Color::WHITE . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" . Color::RESET;
    echo Color::GREEN . "Success: $successCount\n" . Color::RESET;
    if ($failCount > 0) {
        echo Color::RED . "Failed:  $failCount\n" . Color::RESET;
    }
    echo Color::WHITE . "Duration: {$duration}s\n" . Color::RESET;
    echo Color::WHITE . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" . Color::RESET;
    
    echo Color::CYAN . "\n🗺️  View your properties:\n" . Color::RESET;
    echo "  $apiUrl/map.html\n\n";
}

// Main execution
try {
    $options = parseArgs($argv);
    
    if ($options['help']) {
        showHelp();
        exit(0);
    }
    
    if ($options['clear']) {
        clearProperties($API_BASE_URL);
        exit(0);
    }
    
    seedDatabase($API_BASE_URL, $PROPERTIES, $options);
    
} catch (Exception $e) {
    echo Color::RED . "\n❌ Fatal Error: " . $e->getMessage() . "\n" . Color::RESET;
    exit(1);
}

