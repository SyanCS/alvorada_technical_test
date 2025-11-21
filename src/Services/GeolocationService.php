<?php

namespace App\Services;

use App\Config\AppConfig;
use Exception;

/**
 * Geolocation Service
 * Handles address enrichment using OpenStreetMap Nominatim API
 * Returns latitude, longitude, and additional geolocation data
 * Configuration loaded from centralized config
 */
class GeolocationService
{
    private string $baseUrl;
    private string $reverseUrl;
    private string $userAgent;
    private int $timeout;
    private HttpClient $httpClient;
    
    /**
     * Constructor - Initialize HTTP client with config
     */
    public function __construct()
    {
        $config = AppConfig::getInstance();
        
        // Load geolocation configuration
        $this->baseUrl = $config->get('geolocation.base_url');
        $this->reverseUrl = $config->get('geolocation.reverse_url');
        $this->userAgent = $config->get('geolocation.user_agent');
        $this->timeout = $config->get('geolocation.timeout');
        
        $this->httpClient = new HttpClient(
            $this->userAgent,
            $this->timeout,
            true,
            true
        );
    }
    
    /**
     * Geocode an address and return enriched data
     * 
     * @param string $address The address to geocode
     * @return array Associative array with lat, lon, and extra_field
     * @throws Exception If geocoding fails
     */
    public function geocodeAddress(string $address): array
    {
        if (empty(trim($address))) {
            throw new Exception("Address cannot be empty");
        }

        try {
            $response = $this->makeApiRequest($address);
            
            if (empty($response)) {
                throw new Exception("No results found for the provided address");
            }

            // Get the first (best) result
            $result = $response[0];

            // Extract required data
            $latitude = (float) $result['lat'];
            $longitude = (float) $result['lon'];

            // Prepare extra field with additional data
            $extraData = [
                'display_name' => $result['display_name'] ?? '',
                'type' => $result['type'] ?? '',
                'class' => $result['class'] ?? '',
                'importance' => $result['importance'] ?? 0,
                'place_id' => $result['place_id'] ?? null,
                'osm_type' => $result['osm_type'] ?? '',
                'osm_id' => $result['osm_id'] ?? null,
                'boundingbox' => $result['boundingbox'] ?? [],
            ];

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'extra_field' => json_encode($extraData),
                'display_name' => $result['display_name'] ?? $address
            ];

        } catch (Exception $e) {
            error_log("GeolocationService Error: " . $e->getMessage());
            throw new Exception("Failed to geocode address: " . $e->getMessage());
        }
    }

    /**
     * Make API request to Nominatim
     * 
     * @param string $address
     * @return array
     * @throws Exception
     */
    private function makeApiRequest(string $address): array
    {
        // Build query parameters
        $params = [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
            'extratags' => 1,
        ];

        try {
            return $this->httpClient->get($this->baseUrl, $params);
        } catch (Exception $e) {
            throw new Exception("Nominatim API request failed: " . $e->getMessage());
        }
    }

    /**
     * Reverse geocode: Get address from coordinates
     * 
     * @param float $latitude
     * @param float $longitude
     * @return array
     * @throws Exception
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $params = [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => 1,
            ];

            $data = $this->httpClient->get($this->reverseUrl, $params);

            return [
                'address' => $data['display_name'] ?? 'Unknown location',
                'address_details' => $data['address'] ?? []
            ];

        } catch (Exception $e) {
            error_log("ReverseGeocode Error: " . $e->getMessage());
            throw new Exception("Failed to reverse geocode: " . $e->getMessage());
        }
    }

    /**
     * Validate coordinates
     * 
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public function validateCoordinates(float $latitude, float $longitude): bool
    {
        return (
            $latitude >= -90 && 
            $latitude <= 90 && 
            $longitude >= -180 && 
            $longitude <= 180
        );
    }

    /**
     * Calculate distance between two coordinates (in kilometers)
     * Uses Haversine formula
     * 
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in kilometers
     */
    public function calculateDistance(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2
    ): float {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

