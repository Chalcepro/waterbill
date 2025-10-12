<?php
// Simple database connection test
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'waterbill_db';

echo "<h3>Testing MySQL Connection</h3>";

// 1. Test basic MySQLi connection
$conn = @new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    echo "<div style='color:red'>MySQLi Connection Error: " . $conn->connect_error . "</div>";
} else {
    echo "<div style='color:green'>✓ MySQLi Connected Successfully</div>";
    
    // Check if database exists
    if ($conn->select_db($db)) {
        echo "<div style='color:green'>✓ Database '$db' exists</div>";
        
        // Check if fault_reports table exists
        $result = $conn->query("SHOW TABLES LIKE 'fault_reports'");
        if ($result->num_rows > 0) {
            echo "<div style='color:green'>✓ 'fault_reports' table exists</div>";
            
            // Show record count
            $count = $conn->query("SELECT COUNT(*) as count FROM fault_reports")->fetch_assoc()['count'];
            echo "<div>Number of fault reports: $count</div>";
        } else {
            echo "<div style='color:orange'>'fault_reports' table does not exist</div>";
        }
    } else {
        echo "<div style='color:orange'>Database '$db' does not exist</div>";
    }
}

// 2. Test PDO connection
try {
    $pdo = @new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "<div style='color:green'>✓ PDO Connected Successfully</div>";
} catch (PDOException $e) {
    echo "<div style='color:red'>PDO Connection Error: " . $e->getMessage() . "</div>";
}

// 3. Show PHP info link
echo "<p><a href='/waterbill/phpinfo.php' target='_blank'>View PHP Info</a> | ";
echo "<a href='/phpmyadmin' target='_blank'>Open phpMyAdmin</a></p>";

// 4. Show loaded extensions
if (function_exists('get_loaded_extensions')) {
    $extensions = get_loaded_extensions();
    echo "<h4>Loaded Extensions:</h4><ul>";
    foreach ($extensions as $ext) {
        echo "<li>$ext</li>";
    }
    echo "</ul>";
}

// 5. Show PHP version and OS info
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server OS: " . PHP_OS . "</p>";
?>
