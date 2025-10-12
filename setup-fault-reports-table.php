<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config.php';

// Function to execute SQL queries with error handling
function executeQuery($conn, $sql) {
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        echo "Error: " . $conn->error . "\n";
        return false;
    }
}

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to MySQL server\n";

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (executeQuery($conn, $sql)) {
    echo "✅ Database created or already exists\n";
}

// Select the database
if ($conn->select_db(DB_NAME) === false) {
    die("Error selecting database: " . $conn->error);
}

// Create fault_reports table
$sql = "CREATE TABLE IF NOT EXISTS `fault_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `photo_path` VARCHAR(255) NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'open',
    `admin_notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`user_id`), 
    INDEX (`status`),
    INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (executeQuery($conn, $sql)) {
    echo "✅ fault_reports table created or already exists\n";
}

// Check if table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM `fault_reports`");
$row = $result ? $result->fetch_assoc() : ['count' => 0];
$count = $row['count'];

if ($count == 0) {
    echo "Adding test data...\n";
    
    $testReports = [
        [1, 'leak', 'Water leaking from kitchen sink', null, 'open'],
        [1, 'no_water', 'No water supply since morning', 'uploads/faults/leak1.jpg', 'in-progress'],
        [2, 'billing', 'Incorrect water bill amount', null, 'resolved'],
        [3, 'low_pressure', 'Very low water pressure in bathroom', 'uploads/faults/pressure1.jpg', 'open']
    ];
    
    $stmt = $conn->prepare("INSERT INTO `fault_reports` 
        (`user_id`, `category`, `description`, `photo_path`, `status`, `created_at`) 
        VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 30) DAY)");
    
    if ($stmt) {
        foreach ($testReports as $report) {
            $stmt->bind_param("issss", $report[0], $report[1], $report[2], $report[3], $report[4]);
            if ($stmt->execute()) {
                echo "✅ Added report: {$report[1]} - {$report[2]}\n";
            } else {
                echo "❌ Failed to add report: " . $stmt->error . "\n";
            }
        }
        $stmt->close();
    } else {
        echo "❌ Failed to prepare statement: " . $conn->error . "\n";
    }
}

// Close the connection
$conn->close();

echo "\n✅ Setup completed!\n";

try {
    // Verify table structure
    echo "✅ fault_reports table is ready\n";
    
    // Show sample data
    echo "\nSample data in fault_reports table:\n";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $reports = $pdo->query("SELECT * FROM fault_reports ORDER BY created_at DESC LIMIT 5")->fetchAll();
    print_r($reports);
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\nPlease check your database credentials in config.php\n";
        echo "Current settings:\n";
        echo "DB_HOST: " . DB_HOST . "\n";
        echo "DB_NAME: " . DB_NAME . "\n";
        echo "DB_USER: " . DB_USER . "\n";
    }
}
