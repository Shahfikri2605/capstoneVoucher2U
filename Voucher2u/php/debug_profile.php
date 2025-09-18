<?php
// Debug file to help troubleshoot profile data issues
session_start();

echo "<h2>Profile Debug Information</h2>";
echo "<hr>";

// 1. Check if session is working
echo "<h3>1. Session Check:</h3>";
if (isset($_SESSION)) {
    echo "✅ Session is working<br>";
    echo "Session ID: " . session_id() . "<br>";
    
    if (isset($_SESSION['Id'])) {
        echo "✅ User ID in session: " . $_SESSION['Id'] . "<br>";
    } else {
        echo "❌ No User ID in session<br>";
    }
    
    if (isset($_SESSION['userName'])) {
        echo "✅ Username in session: " . $_SESSION['userName'] . "<br>";
    } else {
        echo "❌ No Username in session<br>";
    }
    
    if (isset($_SESSION['userEmail'])) {
        echo "✅ Email in session: " . $_SESSION['userEmail'] . "<br>";
    } else {
        echo "❌ No Email in session<br>";
    }
    
    echo "<br>All session data:<br>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
} else {
    echo "❌ Session not working<br>";
}

echo "<hr>";

// 2. Check database connection
echo "<h3>2. Database Connection Check:</h3>";
try {
    require_once '../../databaseConnection/db_config.php';
    echo "✅ Database config file loaded successfully<br>";
    
    if (isset($pdo)) {
        echo "✅ PDO connection exists<br>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result && $result['test'] == 1) {
            echo "✅ Database connection working<br>";
        } else {
            echo "❌ Database query failed<br>";
        }
        
    } else {
        echo "❌ PDO connection not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 3. Check User table structure
echo "<h3>3. User Table Check:</h3>";
if (isset($pdo)) {
    try {
        // Check if User table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'User'");
        $table = $stmt->fetch();
        
        if ($table) {
            echo "✅ User table exists<br>";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE User");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<br>User table structure:<br>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
            // Count users
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM User");
            $count = $stmt->fetch();
            echo "Total users in table: " . $count['count'] . "<br>";
            
            // Show first few users (without sensitive data)
            $stmt = $pdo->query("SELECT Id, Username, Email FROM User LIMIT 3");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($users) {
                echo "<br>Sample users:<br>";
                echo "<pre>" . print_r($users, true) . "</pre>";
            }
            
        } else {
            echo "❌ User table does not exist<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error checking User table: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";

// 4. Test profile query if user is logged in
echo "<h3>4. Profile Query Test:</h3>";
if (isset($_SESSION['Id']) && isset($pdo)) {
    try {
        $userId = $_SESSION['Id'];
        echo "Testing profile query for user ID: $userId<br>";
        
        $stmt = $pdo->prepare("
            SELECT 
                Id, 
                Username, 
                Email, 
                Phone_number, 
                Address, 
                Points, 
                Profile_image 
            FROM User 
            WHERE Id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ Profile query successful<br>";
            echo "<br>User data:<br>";
            echo "<pre>" . print_r($user, true) . "</pre>";
        } else {
            echo "❌ No user found with ID: $userId<br>";
            
            // Check what user IDs actually exist
            $stmt = $pdo->query("SELECT Id, Username FROM User");
            $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<br>Available user IDs:<br>";
            foreach ($allUsers as $u) {
                echo "ID: " . $u['Id'] . " - " . $u['Username'] . "<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Profile query error: " . $e->getMessage() . "<br>";
    }
} else {
    if (!isset($_SESSION['Id'])) {
        echo "❌ Cannot test - no user ID in session<br>";
    }
    if (!isset($pdo)) {
        echo "❌ Cannot test - no database connection<br>";
    }
}

echo "<hr>";

// 5. Check file paths
echo "<h3>5. File Path Check:</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Database config expected at: " . realpath('../../databaseConnection/db_config.php') . "<br>";
echo "Database config exists: " . (file_exists('../../databaseConnection/db_config.php') ? '✅ Yes' : '❌ No') . "<br>";

echo "<hr>";

// 6. Provide recommendations
echo "<h3>6. Recommendations:</h3>";
if (!isset($_SESSION['Id'])) {
    echo "🔧 <strong>Issue:</strong> No user logged in<br>";
    echo "💡 <strong>Solution:</strong> Go to login page and log in first<br><br>";
}

if (!file_exists('../../databaseConnection/db_config.php')) {
    echo "🔧 <strong>Issue:</strong> Database config file not found<br>";
    echo "💡 <strong>Solution:</strong> Check the path to db_config.php<br><br>";
}

echo "📋 <strong>Next steps:</strong><br>";
echo "1. If no user is logged in: <a href='LoginPage.html'>Login here</a><br>";
echo "2. If database issues: Check your db_config.php file<br>";
echo "3. If session issues: Clear browser cookies and try again<br>";
echo "4. For testing: Use the testProfilePage.js instead<br>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
table {
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
}
th {
    background-color: #f2f2f2;
}
pre {
    background-color: #f4f4f4;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>