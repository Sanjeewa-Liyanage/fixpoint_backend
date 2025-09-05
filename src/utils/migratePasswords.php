<?php
/**
 * Password Migration Utility
 * 
 * This script migrates existing plain text passwords to hashed passwords.
 * Run this once to upgrade your existing user passwords to use proper hashing.
 * 
 * Usage: php src/utils/migratePasswords.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../database/connection.php';

try {
    $conn = DatabaseConnection::getConnection();
    
    // Get all users with plain text passwords (not starting with $2y$ which is bcrypt)
    $sql = "SELECT user_id, password FROM users WHERE password NOT LIKE '$2y$%' AND password IS NOT NULL AND password != ''";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migratedCount = 0;
    $skippedCount = 0;
    
    foreach ($users as $user) {
        // Check if password looks like it's already hashed
        if (password_get_info($user['password'])['algo'] !== null) {
            echo "Skipping user ID {$user['user_id']} - password already appears to be hashed\n";
            $skippedCount++;
            continue;
        }
        
        // Hash the plain text password
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        // Update the user's password
        $updateSql = "UPDATE users SET password = :hashed_password WHERE user_id = :user_id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bindValue(':hashed_password', $hashedPassword);
        $updateStmt->bindValue(':user_id', $user['user_id']);
        
        if ($updateStmt->execute()) {
            echo "Successfully migrated password for user ID: {$user['user_id']}\n";
            $migratedCount++;
        } else {
            echo "Failed to migrate password for user ID: {$user['user_id']}\n";
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Passwords migrated: {$migratedCount}\n";
    echo "Passwords skipped: {$skippedCount}\n";
    echo "Total users processed: " . ($migratedCount + $skippedCount) . "\n";
    
} catch (Exception $e) {
    echo "Error during password migration: " . $e->getMessage() . "\n";
    exit(1);
}
