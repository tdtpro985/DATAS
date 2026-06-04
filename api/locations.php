<?php
/* ============================================================
   api/locations.php — Worldwide Location Dropdown API
   ============================================================
   Provides searchable locations (countries, regions, provinces, cities, barangays)
   filtered by parent location using the unified locations table.
   ============================================================ */
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/db.php';
    $pdo = getDB();

    $action = $_GET['action'] ?? '';
    $country = $_GET['country'] ?? '';
    $region = $_GET['region'] ?? '';
    $province = $_GET['province'] ?? '';
    $city = $_GET['city'] ?? '';
    $search = $_GET['search'] ?? '';

    if ($action === 'countries') {
        // Get all countries
        $stmt = $pdo->prepare("
            SELECT country_code as code, name
            FROM locations
            WHERE type = 'country'
            ORDER BY name
        ");
        $stmt->execute();
        echo json_encode(['countries' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'regions') {
        // Get regions for a country
        if (!$country) {
            http_response_code(400);
            echo json_encode(['error' => 'country required']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT region_code as code, name
            FROM locations
            WHERE country_code = ? AND type = 'region'
            ORDER BY name
        ");
        $stmt->execute([$country]);
        echo json_encode(['regions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'provinces') {
        // Get provinces for a region
        if (!$country || !$region) {
            http_response_code(400);
            echo json_encode(['error' => 'country and region required']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT province_code as code, name
            FROM locations
            WHERE country_code = ? AND region_code = ? AND type = 'province'
            ORDER BY name
        ");
        $stmt->execute([$country, $region]);
        echo json_encode(['provinces' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'cities') {
        // Get cities for a province or region
        if (!$country || !$region) {
            http_response_code(400);
            echo json_encode(['error' => 'country and region required']);
            exit;
        }
        
        if ($province) {
            // Get cities for a specific province
            $stmt = $pdo->prepare("
                SELECT city_code as code, name
                FROM locations
                WHERE country_code = ? AND region_code = ? AND province_code = ? AND type = 'city'
                ORDER BY name
            ");
            $stmt->execute([$country, $region, $province]);
        } else {
            // Get cities directly under region (like NCR in Philippines, or city-states)
            $stmt = $pdo->prepare("
                SELECT city_code as code, name
                FROM locations
                WHERE country_code = ? AND region_code = ? AND type = 'city' AND province_code IS NULL
                ORDER BY name
            ");
            $stmt->execute([$country, $region]);
        }
        echo json_encode(['cities' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'barangays') {
        // Get barangays/districts for a city
        if (!$country || !$region || !$city) {
            http_response_code(400);
            echo json_encode(['error' => 'country, region and city required']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT barangay_code as code, name
            FROM locations
            WHERE country_code = ? AND region_code = ? AND city_code = ? AND type = 'barangay'
            ORDER BY name
        ");
        $stmt->execute([$country, $region, $city]);
        echo json_encode(['barangays' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Valid actions: countries, regions, provinces, cities, barangays']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Locations API error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>