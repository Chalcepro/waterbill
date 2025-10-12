<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'waterbill';
$username = 'root';
$password = '';

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'fault_reports'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table if it doesn't exist
        $sql = "
            CREATE TABLE `fault_reports` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `description` TEXT NOT NULL,
                `photo_path` VARCHAR(255) NULL,
                `status` ENUM('open', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'open',
                `admin_notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`user_id`),
                INDEX (`status`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        echo "Created fault_reports table successfully.\n";
    } else {
        // Table exists, check and add missing columns
        $columns = [
            'category' => "ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) NOT NULL AFTER `user_id`",
            'description' => "ADD COLUMN IF NOT EXISTS `description` TEXT NOT NULL AFTER `category`",
            'photo_path' => "ADD COLUMN IF NOT EXISTS `photo_path` VARCHAR(255) NULL AFTER `description`",
            'status' => "ADD COLUMN IF NOT EXISTS `status` ENUM('open', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'open' AFTER `photo_path`",
            'admin_notes' => "ADD COLUMN IF NOT EXISTS `admin_notes` TEXT NULL AFTER `status`",
            'created_at' => "ADD COLUMN IF NOT EXISTS `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `admin_notes`",
            'updated_at' => "ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
        ];
        
        foreach ($columns as $column => $alterSql) {
            try {
                $pdo->exec("ALTER TABLE `fault_reports` $alterSql;");
                echo "Added column '$column' to fault_reports table.\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'duplicate column name') === false) {
                    echo "Error adding column '$column': " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Add indexes if they don't exist
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_user_id` ON `fault_reports` (`user_id`);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_status` ON `fault_reports` (`status`);");
            echo "Added indexes to fault_reports table.\n";
        } catch (PDOException $e) {
            echo "Error adding indexes: " . $e->getMessage() . "\n";
        }
        
        // Add foreign key if it doesn't exist
        try {
            $pdo->exec("
                ALTER TABLE `fault_reports`
                ADD CONSTRAINT `fk_fault_reports_user`
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE;
            
            echo "Added foreign key to fault_reports table.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate foreign key constraint name') === false) {
                echo "Error adding foreign key: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Database schema is up to date.\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
