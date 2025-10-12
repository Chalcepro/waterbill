<?php
// Check database and user setup
$host = 'localhost';
$root_user = 'root';
$root_pass = '';  // Default XAMPP password is empty
$db_name = 'waterbill_db';
$db_user = 'waterbill_user';
$db_pass = 'securepassword123';

echo "<h2>Database Setup Check</h2>";

// 1. Try to connect as root (default XAMPP credentials)
try {
    $conn = new mysqli($host, $root_user, $root_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Root connection failed: " . $conn->connect_error);
    }
    
    echo "<div style='color:green'>✓ Connected to MySQL server as root</div>";
    
    // 2. Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$db_name'");
    if ($result->num_rows === 0) {
        echo "<div style='color:orange'>Database '$db_name' does not exist. Creating it now...</div>";
        
        if ($conn->query("CREATE DATABASE `$db_name`")) {
            echo "<div style='color:green'>✓ Created database '$db_name'</div>";
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    } else {
        echo "<div style='color:green'>✓ Database '$db_name' exists</div>";
    }
    
    // 3. Check if user exists
    $result = $conn->query("SELECT User FROM mysql.user WHERE User = '$db_user'");
    if ($result->num_rows === 0) {
        echo "<div style='color:orange'>User '$db_user' does not exist. Creating user...</div>";
        
        if ($conn->query("CREATE USER '$db_user'@'localhost' IDENTIFIED BY '$db_pass'")) {
            echo "<div style='color:green'>✓ Created user '$db_user'</div>";
        } else {
            throw new Exception("Error creating user: " . $conn->error);
        }
    } else {
        echo "<div style='color:green'>✓ User '$db_user' exists</div>";
    }
    
    // 4. Grant privileges
    if ($conn->query("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'")) {
        echo "<div style='color:green'>✓ Granted privileges to user '$db_user'</div>";
    } else {
        throw new Exception("Error granting privileges: " . $conn->error);
    }
    
    // 5. Flush privileges
    $conn->query("FLUSH PRIVILEGES");
    
    // 6. Test connection with new user
    $conn->close();
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Failed to connect with user '$db_user': " . $conn->connect_error);
    }
    
    echo "<div style='color:green'>✓ Successfully connected with user '$db_user'</div>";
    
    // 7. Create fault_reports table if not exists
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
        echo "<div style='color:green'>✓ 'fault_reports' table is ready</div>";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // 8. Insert test data if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM fault_reports");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
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
    }
    
    echo "<div style='margin-top:20px; color:green; font-weight:bold;'>✓ Database setup is complete!</div>";
    echo "<p><a href='/waterbill/frontend/admin/fault-reports.html' target='_blank'>Open Fault Reports Page</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
    
    // Show help for common issues
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<div style='margin-top:20px; color:orange;'>";
        echo "<h3>Access Denied Error</h3>";
        echo "<p>This usually means the database user doesn't have the correct permissions.</p>";
        echo "<p>Try these steps in phpMyAdmin:</p>";
        echo "<ol>";
        echo "<li>Login to phpMyAdmin (usually at <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a>)</li>";
        echo "<li>Go to the 'User accounts' tab</li>";
        echo "<li>Click 'Add user account'</li>";
        echo "<li>Set username to: $db_user</li>";
        echo "<li>Set password to: $db_pass</li>";
        echo "<li>Under 'Database for user account', select 'Create database with same name and grant all privileges'</li>";
        echo "<li>Click 'Go' to create the user and database</li>";
        echo "</ol>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='/waterbill/check_php_config.php'>Check PHP Configuration Again</a></p>";
?>
