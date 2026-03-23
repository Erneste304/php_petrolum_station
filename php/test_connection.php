<?php
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test 1: Check if we can query the database
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM station");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Database connected successfully!</p>";
    echo "<p>Number of stations: " . $result['count'] . "</p>";
    
    // Test 2: List all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . current($table) . "</li>";
    }
    echo "</ul>";
    
    // Test 3: Check if we can access specific tables
    $tables_to_test = ['station', 'employee', 'customer', 'fuel_type', 'sale'];
    
    echo "<h3>Table Record Counts:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table Name</th><th>Record Count</th></tr>";
    
    foreach ($tables_to_test as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>