<?php
require_once 'config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'fault_reports'");
if ($result->num_rows === 0) {
    echo "The 'fault_reports' table does not exist. Creating it now...\n";
    
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS `fault_reports` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `category` varchar(100) NOT NULL,
        `description` text NOT NULL,
        `photo_path` varchar(255) DEFAULT NULL,
        `status` enum('open','in_progress','resolved','rejected') NOT NULL DEFAULT 'open',
        `admin_notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `fault_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "Successfully created 'fault_reports' table.\n";
        
        // Insert test data
        $testData = [
            [1, 'Leak', 'There is a water leak in the kitchen', 'uploads/faults/leak1.jpg', 'open', 'Not yet reviewed'],
            [1, 'Low Pressure', 'Water pressure is very low in the bathroom', null, 'in_progress', 'Technician assigned'],
            [2, 'Discoloration', 'Water is brown and smells bad', 'uploads/faults/discoloration1.jpg', 'open', 'Needs testing'],
        ];
        
        $stmt = $conn->prepare("INSERT INTO fault_reports (user_id, category, description, photo_path, status, admin_notes) VALUES (?, ?, ?, ?, ?, ?)");
        $inserted = 0;
        
        foreach ($testData as $data) {
            $stmt->bind_param("isssss", ...$data);
            if ($stmt->execute()) {
                $inserted++;
            }
        }
        
        echo "Inserted $inserted test records.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "'fault_reports' table exists.\n";
    
    // Count records
    $result = $conn->query("SELECT COUNT(*) as count FROM fault_reports");
    $row = $result->fetch_assoc();
    $count = $row['count'];
    
    echo "Number of records in fault_reports: $count\n";
    
    // Show table structure
    echo "\nTable structure:\n";
    $result = $conn->query("DESCRIBE fault_reports");
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Show sample data if exists
    if ($count > 0) {
        echo "\nSample data (first 3 records):\n";
        $result = $conn->query("SELECT * FROM fault_reports ORDER BY created_at DESC LIMIT 3");
        while ($row = $result->fetch_assoc()) {
            print_r($row);
            echo "\n";
        }
    } else {
        echo "\nNo records found in fault_reports table.\n";
    }
}

$conn->close();
?>
