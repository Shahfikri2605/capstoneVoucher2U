<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS RedeemedVoucher (
        Id INT AUTO_INCREMENT PRIMARY KEY,
        voucher_code VARCHAR(255) NOT NULL UNIQUE,
        voucher_id INT NOT NULL,
        user_id INT NOT NULL,
        redemption_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'redeemed',
        FOREIGN KEY (voucher_id) REFERENCES Voucher(Id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES User(Id) ON DELETE CASCADE
    );";
    
    $pdo->exec($sql);
    echo "Table RedeemedVoucher created successfully.\n";

} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>
