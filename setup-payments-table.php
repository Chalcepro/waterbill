<?php
/**
 * This script sets up the payments table in the database.
 * Run this script once to ensure the required database structure exists.
 */

require_once 'config.php';

try {
    // Check if payments table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'payments'");
    
    if ($tableCheck->rowCount() === 0) {
        // Create payments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            method VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            transaction_id VARCHAR(100) NULL,
            receipt_image VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        echo "Payments table created successfully.\n";
        
        // Insert sample payment data (optional)
        $samplePayments = [
            [1, 5000.00, 'bank_transfer', 'approved', 'TXN' . time() . '001', null],
            [1, 3000.00, 'paystack', 'pending', 'TXN' . time() . '002', 'receipts/sample1.jpg']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO payments 
            (user_id, amount, method, status, transaction_id, receipt_image) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($samplePayments as $payment) {
            $stmt->execute($payment);
        }
        
        echo count($samplePayments) . " sample payments inserted.\n";
    } else {
        echo "Payments table already exists.\n";
    }
    
    // Check if system_settings table exists and has min_payment
    $settingsCheck = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    
    if ($settingsCheck->rowCount() > 0) {
        $minPayment = $pdo->query("SELECT value FROM system_settings WHERE name = 'min_payment'");
        
        if ($minPayment->rowCount() === 0) {
            $pdo->exec("INSERT INTO system_settings (name, value, description) 
                VALUES ('min_payment', '2000', 'Minimum payment amount in Naira')");
            echo "Added min_payment setting.\n";
        } else {
            echo "min_payment setting already exists.\n";
        }
    } else {
        echo "system_settings table does not exist. Creating...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        $pdo->exec("INSERT INTO system_settings (name, value, description) 
            VALUES ('min_payment', '2000', 'Minimum payment amount in Naira')");
        
        echo "Created system_settings table and added min_payment setting.\n";
    }
    
    echo "Database setup completed successfully.\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nYou can now access the payment history page.\n";
?>
