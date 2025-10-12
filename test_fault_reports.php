<?php
// Test script for fault reports functionality
echo "<h2>Fault Reports Test Page</h2>";

// 1. Check if database configuration exists
if (!file_exists('config.php')) {
    die("<div style='color:red'>Error: config.php not found</div>");
}

require_once 'config.php';

// 2. Test database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<div style='color:green'>✓ Connected to database successfully</div>";
    
    // 3. Check if fault_reports table exists
    $result = $conn->query("SHOW TABLES LIKE 'fault_reports'");
    if ($result->num_rows === 0) {
        echo "<div style='color:orange'>'fault_reports' table does not exist. Creating it now...</div>";
        
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
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql) === TRUE) {
            echo "<div style='color:green'>✓ Created 'fault_reports' table successfully</div>";
            
            // Add test data
            $testData = [
                [1, 'Leak', 'Water leak in the kitchen', 'uploads/faults/leak1.jpg', 'open', 'Not yet reviewed'],
                [1, 'Low Pressure', 'Low water pressure in bathroom', null, 'in_progress', 'Technician assigned'],
                [2, 'Discoloration', 'Brown water coming from tap', 'uploads/faults/water1.jpg', 'open', 'Needs investigation']
            ];
            
            $stmt = $conn->prepare("INSERT INTO fault_reports (user_id, category, description, photo_path, status, admin_notes) VALUES (?, ?, ?, ?, ?, ?)");
            $inserted = 0;
            
            foreach ($testData as $data) {
                $stmt->bind_param("isssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
                if ($stmt->execute()) {
                    $inserted++;
                }
            }
            
            echo "<div>Added $inserted test records to fault_reports</div>";
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
    } else {
        echo "<div style='color:green'>✓ 'fault_reports' table exists</div>";
    }
    
    // 4. Display existing fault reports
    echo "<h3>Current Fault Reports:</h3>";
    $result = $conn->query("SELECT * FROM fault_reports ORDER BY created_at DESC");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Category</th><th>Status</th><th>Created At</th><th>Description</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['description'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div style='color:orange'>No fault reports found in the database.</div>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
}

// 5. Test API endpoint
echo "<h3>Testing API Endpoint:</h3>";
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/waterbill/api/admin/fault-reports-mysqli.php';
echo "Testing API: <a href='$apiUrl' target='_blank'>$apiUrl</a><br>";

$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    echo "<div style='color:red'>API request failed: " . error_get_last()['message'] . "</div>";
} else {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<div style='color:green'>✓ API is working</div>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div style='color:red'>API response is not valid JSON: " . $response . "</div>";
    }
}

// 6. Next steps
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Check if the database tables were created successfully</li>";
echo "<li>Verify that test data was inserted</li>";
echo "<li>Check the API endpoint response above</li>";
echo "<li>Visit the <a href='/waterbill/frontend/admin/fault-reports.html' target='_blank'>Fault Reports Admin Page</a></li>";
echo "</ol>";
?>
