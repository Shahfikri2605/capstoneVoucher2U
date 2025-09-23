<?php

require_once '../../databaseConnection/db_config.php';

/**
 * Placeholder function to get available vouchers from the database.
 * In a real scenario, this would query your `Voucher` table.
 * @return array A list of available vouchers.
 */
function get_available_vouchers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT Id, Title, Description, Points FROM Voucher LIMIT 5");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_available_vouchers: " . $e->getMessage());
        return [];
    }
}

/**
 * Placeholder function to get vouchers for a specific user from the database.
 * This would typically involve joining `UserVoucher` and `Voucher` tables.
 * @param int $userId The ID of the user.
 * @return array A list of vouchers for the specified user.
 */
function get_user_vouchers($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT v.Id, v.Title, v.Description, v.Points FROM Voucher v JOIN cart_item_history ch ON v.Id = ch.voucher_id WHERE ch.user_id = ? LIMIT 5");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_user_vouchers for user " . $userId . ": " . $e->getMessage());
        return [];
    }
}

// Add more functions here for other database interactions as needed
