<?php
// Simple test to check what's causing the 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing includes...<br>";

try {
    require('inc/db_config.php');
    echo "db_config.php loaded OK<br>";
    
    if (isset($con)) {
        echo "Database connection variable exists<br>";
        if ($con) {
            echo "Database connection is active<br>";
        } else {
            echo "Database connection is NULL<br>";
        }
    } else {
        echo "Database connection variable NOT set<br>";
    }
    
    require('inc/essentials.php');
    echo "essentials.php loaded OK<br>";
    
    echo "All includes successful!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "<br>";
}

?>


