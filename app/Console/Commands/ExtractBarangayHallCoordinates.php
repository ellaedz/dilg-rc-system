<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class ExtractBarangayHallCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gis:extract-barangay-halls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract verified barangay hall coordinates from OpenStreetMap for Santa Cruz, Laguna';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗺️  Starting Barangay Hall Coordinate Extraction from OpenStreetMap...');
        $this->newLine();

        // Load barangays from config
        $barangays = config('santa_cruz_barangays.barangays', []);
        
        if (empty($barangays)) {
            $this->error('❌ No barangays found in config/santa_cruz_barangays.php');
            return 1;
        }

        $this->info('📋 Found ' . count($barangays) . ' barangays in Santa Cruz, Laguna');
        $this->newLine();

        // Load boundary GeoJSON for centroid fallback
        $geojsonPath = public_path('gis/boundary.geojson');
        $boundaryData = null;
        
        if (File::exists($geojsonPath)) {
            $boundaryData = json_decode(File::get($geojsonPath), true);
            $this->info('✅ Loaded boundary.geojson for centroid fallback');
        } else {
            $this->warn('⚠️  boundary.geojson not found - will use config centroids for fallback');
        }
        
        $this->newLine();

        // Process each barangay
        $results = [];
        $osmFoundCount = 0;
        $fallbackCount = 0;

        $progressBar = $this->output->createProgressBar(count($barangays));
        $progressBar->start();

        foreach ($barangays as $barangay) {
            $barangayName = $barangay['name'];
            
            // Try to find barangay hall from OpenStreetMap
            $osmResult = $this->queryOpenStreetMap($barangayName);
            
            if ($osmResult) {
                // Found in OSM
                $results[] = [
                    'barangay' => $barangayName,
                    'office_name' => 'Barangay Hall - ' . $barangayName,
                    'latitude' => $osmResult['latitude'],
                    'longitude' => $osmResult['longitude'],
                    'address' => $osmResult['address'] ?? ($barangayName . ', Santa Cruz, Laguna'),
                    'osm_type' => $osmResult['osm_type'],
                    'osm_id' => $osmResult['osm_id'],
                    'source' => 'OpenStreetMap',
                    'validation_status' => 'Verified from OSM'
                ];
                $osmFoundCount++;
            } else {
                // Use fallback (centroid from GeoJSON or config)
                $centroid = $this->getBarangayCentroid($barangayName, $boundaryData, $barangay);
                
                $results[] = [
                    'barangay' => $barangayName,
                    'office_name' => 'Barangay Hall - ' . $barangayName,
                    'latitude' => $centroid['latitude'],
                    'longitude' => $centroid['longitude'],
                    'address' => $barangayName . ', Santa Cruz, Laguna',
                    'osm_type' => null,
                    'osm_id' => null,
                    'source' => $centroid['source'],
                    'validation_status' => 'Needs manual validation'
                ];
                $fallbackCount++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Generate config file
        $this->info('📝 Generating config/santa_cruz_barangay_halls.php...');
        $this->generateConfigFile($results);

        // Generate GeoJSON file
        $this->info('🗺️  Generating public/gis/barangay_halls.geojson...');
        $this->generateGeoJSONFile($results);

        $this->newLine();
        $this->info('✅ EXTRACTION COMPLETE!');
        $this->newLine();

        // Summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Barangays', count($barangays)],
                ['Found in OpenStreetMap', $osmFoundCount],
                ['Using Centroid Fallback', $fallbackCount],
            ]
        );

        $this->newLine();
        
        // List results
        $this->info('📍 BARANGAY HALL COORDINATES:');
        $this->newLine();
        
        $tableData = [];
        foreach ($results as $result) {
            $tableData[] = [
                $result['barangay'],
                number_format($result['latitude'], 6),
                number_format($result['longitude'], 6),
                $result['source'],
                $result['validation_status']
            ];
        }
        
        $this->table(
            ['Barangay', 'Latitude', 'Longitude', 'Source', 'Status'],
            $tableData
        );

        $this->newLine();
        
        // Barangays needing validation
        if ($fallbackCount > 0) {
            $this->warn('⚠️  BARANGAYS NEEDING MANUAL VALIDATION:');
            foreach ($results as $result) {
                if ($result['validation_status'] === 'Needs manual validation') {
                    $this->line('   • ' . $result['barangay']);
                }
            }
            $this->newLine();
        }

        $this->info('📂 FILES GENERATED:');
        $this->line('   • config/santa_cruz_barangay_halls.php');
        $this->line('   • public/gis/barangay_halls.geojson');
        $this->newLine();

        $this->info('🔄 NEXT STEPS:');
        $this->line('   1. Review config/santa_cruz_barangay_halls.php');
        $this->line('   2. Manually validate coordinates marked as "Needs manual validation"');
        $this->line('   3. Update GIS API to use new config file');
        $this->line('   4. Test /api/gis/barangay-offices endpoint');
        $this->newLine();

        return 0;
    }

    /**
     * Query OpenStreetMap / Overpass API for barangay hall
     */
    protected function queryOpenStreetMap(string $barangayName): ?array
    {
        // Overpass API endpoint
        $overpassUrl = 'https://overpass-api.de/api/interpreter';
        
        // Build Overpass QL query
        // Search for barangay halls in Santa Cruz, Laguna area
        // Bounding box for Santa Cruz: approximately 14.20 to 14.35 lat, 121.37 to 121.45 lon
        $query = <<<OVERPASS
[out:json][timeout:25];
(
  // Search for townhall/government offices
  node["amenity"="townhall"]["name"~"$barangayName",i](14.20,121.37,14.35,121.45);
  node["office"="government"]["name"~"$barangayName",i](14.20,121.37,14.35,121.45);
  node["building"="public"]["name"~"Barangay.*$barangayName",i](14.20,121.37,14.35,121.45);
  node["government"="administrative"]["name"~"$barangayName",i](14.20,121.37,14.35,121.45);
  
  // Search ways (buildings)
  way["amenity"="townhall"]["name"~"$barangayName",i](14.20,121.37,14.35,121.45);
  way["office"="government"]["name"~"$barangayName",i](14.20,121.37,14.35,121.45);
  way["building"="public"]["name"~"Barangay.*$barangayName",i](14.20,121.37,14.35,121.45);
);
out center;
OVERPASS;

        try {
            // Query Overpass API with timeout
            $response = Http::timeout(30)
                ->post($overpassUrl, [
                    'data' => $query
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            if (empty($data['elements'])) {
                return null;
            }

            // Get the first result
            $element = $data['elements'][0];
            
            // Extract coordinates
            if (isset($element['lat']) && isset($element['lon'])) {
                // Node
                $lat = $element['lat'];
                $lon = $element['lon'];
            } elseif (isset($element['center'])) {
                // Way with center
                $lat = $element['center']['lat'];
                $lon = $element['center']['lon'];
            } else {
                return null;
            }

            // Extract address if available
            $address = null;
            if (isset($element['tags']['addr:full'])) {
                $address = $element['tags']['addr:full'];
            } elseif (isset($element['tags']['addr:street'])) {
                $address = $element['tags']['addr:street'] . ', ' . $barangayName . ', Santa Cruz, Laguna';
            }

            return [
                'latitude' => $lat,
                'longitude' => $lon,
                'address' => $address,
                'osm_type' => $element['type'],
                'osm_id' => $element['id']
            ];

        } catch (\Exception $e) {
            // API error or timeout - return null to use fallback
            return null;
        }
    }

    /**
     * Get barangay centroid from GeoJSON or config fallback
     */
    protected function getBarangayCentroid(string $barangayName, ?array $boundaryData, array $configBarangay): array
    {
        // Try to get centroid from GeoJSON
        if ($boundaryData && isset($boundaryData['features'])) {
            foreach ($boundaryData['features'] as $feature) {
                $properties = $feature['properties'] ?? [];
                
                // Check multiple possible property keys
                $possibleKeys = ['name', 'Name', 'NAME', 'barangay', 'Barangay', 'BARANGAY', 'brgy', 'Brgy', 'BRGY'];
                
                foreach ($possibleKeys as $key) {
                    if (isset($properties[$key]) && strcasecmp($properties[$key], $barangayName) === 0) {
                        // Found matching barangay - calculate centroid
                        $centroid = $this->calculateCentroid($feature['geometry']);
                        
                        if ($centroid) {
                            return [
                                'latitude' => $centroid['lat'],
                                'longitude' => $centroid['lon'],
                                'source' => 'boundary.geojson centroid fallback'
                            ];
                        }
                    }
                }
            }
        }

        // Fallback to config center coordinates
        return [
            'latitude' => $configBarangay['center_lat'],
            'longitude' => $configBarangay['center_lon'],
            'source' => 'config centroid fallback'
        ];
    }

    /**
     * Calculate centroid from GeoJSON geometry
     */
    protected function calculateCentroid(array $geometry): ?array
    {
        if ($geometry['type'] === 'Polygon') {
            $coordinates = $geometry['coordinates'][0]; // Outer ring
        } elseif ($geometry['type'] === 'MultiPolygon') {
            $coordinates = $geometry['coordinates'][0][0]; // First polygon, outer ring
        } else {
            return null;
        }

        // Calculate average of all points
        $latSum = 0;
        $lonSum = 0;
        $count = count($coordinates);

        foreach ($coordinates as $coord) {
            $lonSum += $coord[0]; // GeoJSON is [lon, lat]
            $latSum += $coord[1];
        }

        return [
            'lat' => $latSum / $count,
            'lon' => $lonSum / $count
        ];
    }

    /**
     * Generate PHP config file
     */
    protected function generateConfigFile(array $results): void
    {
        $configPath = config_path('santa_cruz_barangay_halls.php');
        
        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * Santa Cruz, Laguna - Barangay Hall Coordinates\n";
        $content .= " * \n";
        $content .= " * This file contains verified barangay hall GPS coordinates extracted from:\n";
        $content .= " * - OpenStreetMap (when available)\n";
        $content .= " * - Boundary GeoJSON centroid (fallback)\n";
        $content .= " * \n";
        $content .= " * Generated: " . now()->format('F d, Y H:i:s') . "\n";
        $content .= " * Command: php artisan gis:extract-barangay-halls\n";
        $content .= " * \n";
        $content .= " * IMPORTANT:\n";
        $content .= " * Coordinates marked as 'Needs manual validation' should be verified\n";
        $content .= " * with the Local Government Unit (LGU) before production deployment.\n";
        $content .= " */\n\n";
        $content .= "return [\n";
        
        foreach ($results as $result) {
            $content .= "    [\n";
            $content .= "        'barangay' => '" . $result['barangay'] . "',\n";
            $content .= "        'office_name' => '" . $result['office_name'] . "',\n";
            $content .= "        'latitude' => " . $result['latitude'] . ",\n";
            $content .= "        'longitude' => " . $result['longitude'] . ",\n";
            $content .= "        'address' => '" . $result['address'] . "',\n";
            $content .= "        'osm_type' => " . ($result['osm_type'] ? "'" . $result['osm_type'] . "'" : 'null') . ",\n";
            $content .= "        'osm_id' => " . ($result['osm_id'] ? $result['osm_id'] : 'null') . ",\n";
            $content .= "        'source' => '" . $result['source'] . "',\n";
            $content .= "        'validation_status' => '" . $result['validation_status'] . "',\n";
            $content .= "    ],\n";
        }
        
        $content .= "];\n";
        
        File::put($configPath, $content);
    }

    /**
     * Generate GeoJSON file
     */
    protected function generateGeoJSONFile(array $results): void
    {
        $geojsonPath = public_path('gis/barangay_halls.geojson');
        
        // Ensure directory exists
        $directory = dirname($geojsonPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        $features = [];
        
        foreach ($results as $result) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$result['longitude'], $result['latitude']]
                ],
                'properties' => [
                    'barangay' => $result['barangay'],
                    'office_name' => $result['office_name'],
                    'address' => $result['address'],
                    'source' => $result['source'],
                    'validation_status' => $result['validation_status'],
                    'osm_type' => $result['osm_type'],
                    'osm_id' => $result['osm_id']
                ]
            ];
        }
        
        $geojson = [
            'type' => 'FeatureCollection',
            'name' => 'Santa Cruz Barangay Halls',
            'crs' => [
                'type' => 'name',
                'properties' => [
                    'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'
                ]
            ],
            'features' => $features
        ];
        
        File::put($geojsonPath, json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
