<?php
/* ============================================================
   run_worldwide_locations_migration.php
   ============================================================
   Executes the worldwide locations migration to add comprehensive
   global location data to the database.
   ============================================================ */

require_once __DIR__ . '/api/db.php';

try {
    $pdo = getDB();
    
    echo "Starting worldwide locations migration...\n";
    
    // Read the migration file
    $migrationSQL = file_get_contents(__DIR__ . '/database/worldwide_locations_migration.sql');
    
    if ($migrationSQL === false) {
        throw new Exception('Could not read migration file');
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSQL)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $pdo->beginTransaction();
    
    $successCount = 0;
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Skip duplicate entries (they might already exist)
                if ($e->getCode() !== '23000') { // Not a duplicate entry error
                    throw $e;
                }
            }
        }
    }
    
    $pdo->commit();
    
    echo "Migration completed successfully!\n";
    echo "Executed {$successCount} statements.\n";
    
    // Verify the data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations WHERE type = 'country'");
    $countryCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations WHERE type = 'region'");
    $regionCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations WHERE type = 'city'");
    $cityCount = $stmt->fetch()['count'];
    
    echo "\nDatabase now contains:\n";
    echo "- {$countryCount} countries\n";
    echo "- {$regionCount} regions/states\n";
    echo "- {$cityCount} cities\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>