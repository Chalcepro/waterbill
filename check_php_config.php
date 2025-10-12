<?php
// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if an extension is loaded
function check_extension($name) {
    if (extension_loaded($name)) {
        return "<span style='color:green'>✓ $name is loaded</span>";
    } else {
        return "<span style='color:red'>✗ $name is NOT loaded</span>";
    }
}

// Check PHP version
echo "<h2>PHP Version: " . phpversion() . "</h2>";

// Check important extensions
echo "<h3>Checking Required Extensions:</h3>";
echo "<ul>";
echo "<li>" . check_extension('mysqli') . "</li>";
echo "<li>" . check_extension('pdo_mysql') . "</li>";
echo "<li>" . check_extension('pdo') . "</li>";
echo "</ul>";

// Check PHP.ini location
echo "<h3>PHP Configuration:</h3>";
echo "<p>Loaded Configuration File: " . php_ini_loaded_file() . "</p>";

// Check database connection
echo "<h3>Database Connection Test:</h3>";
if (file_exists('config.php')) {
    require_once 'config.php';
    
    // Test MySQLi
    echo "<h4>Testing MySQLi:</h4>";
    try {
        $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_error) {
            throw new Exception("Connection failed: " . $mysqli->connect_error);
        }
        echo "<div style='color:green'>✓ MySQLi connected successfully</div>";
        $mysqli->close();
    } catch (Exception $e) {
        echo "<div style='color:red'>MySQLi Error: " . $e->getMessage() . "</div>";
    }
    
    // Test PDO
    echo "<h4>Testing PDO:</h4>";
    try {
        $pdo = @new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div style='color:green'>✓ PDO connected successfully</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red'>PDO Error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color:red'>config.php not found</div>";
}

// Show loaded extensions
echo "<h3>All Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";

// Show PHP info link
echo "<p><a href='/waterbill/phpinfo.php' target='_blank'>View Full PHP Info</a></p>";
?>
