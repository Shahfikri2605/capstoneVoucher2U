<?php
// Enhanced Debug file to help troubleshoot profile data and password issues
session_start();

echo "<h2>Enhanced Profile & Password Debug Information</h2>";
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
                Profile_image,
                About_me,
                Is_active
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

// 5. PASSWORD TESTING SECTION
echo "<h3>5. Password Testing:</h3>";
if (isset($_SESSION['Id']) && isset($pdo)) {
    try {
        $userId = $_SESSION['Id'];
        echo "Testing password functionality for user ID: $userId<br>";
        
        // Get current password hash
        $stmt = $pdo->prepare("SELECT Password FROM User WHERE Id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData && !empty($userData['Password'])) {
            echo "✅ User has a password hash stored<br>";
            echo "Password hash (first 20 chars): " . substr($userData['Password'], 0, 20) . "...<br>";
            echo "Password hash length: " . strlen($userData['Password']) . " characters<br>";
            
            // Check if it looks like a proper bcrypt hash
            if (strpos($userData['Password'], '$2y$') === 0) {
                echo "✅ Password appears to be properly hashed (bcrypt)<br>";
            } else {
                echo "⚠️ Password may not be properly hashed (not bcrypt format)<br>";
            }
            
            // Test password verification with some common test passwords
            echo "<br><strong>Password Testing (you can test with known passwords):</strong><br>";
            echo "<div id='passwordTests'>";
            echo "<input type='password' id='testPassword' placeholder='Enter current password to test'>";
            echo "<button onclick='testPassword()'>Test Password</button>";
            echo "<div id='passwordResult'></div>";
            echo "</div>";
            
        } else {
            echo "❌ User has no password hash or password is empty<br>";
        }
        
        // Test password hashing functionality
        echo "<br><strong>Password Hashing Test:</strong><br>";
        $testPassword = "TestPass123!";
        $hashedTest = password_hash($testPassword, PASSWORD_DEFAULT);
        $verifyTest = password_verify($testPassword, $hashedTest);
        
        if ($verifyTest) {
            echo "✅ PHP password_hash() and password_verify() working correctly<br>";
        } else {
            echo "❌ PHP password functions not working properly<br>";
        }
        
        // Test regex for password validation
        echo "<br><strong>Password Regex Validation Test:</strong><br>";
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/';
        $testPasswords = [
            'weak' => 'password',
            'medium' => 'Password123',
            'strong' => 'Password123!',
            'complex' => 'MyStr0ng@Pass'
        ];
        
        foreach ($testPasswords as $type => $pass) {
            $matches = preg_match($passwordRegex, $pass);
            echo "$type password '$pass': " . ($matches ? "✅ Valid" : "❌ Invalid") . "<br>";
        }
        
        // Check if the regex has the end anchor
        echo "<br><strong>Regex Analysis:</strong><br>";
        echo "Current regex: <code>$passwordRegex</code><br>";
        if (strpos($passwordRegex, '$') !== false) {
            echo "✅ Regex has end anchor ($)<br>";
        } else {
            echo "⚠️ Regex missing end anchor ($) - this could cause issues<br>";
            echo "Recommended regex: <code>/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/</code><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Password testing error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Cannot test passwords - no user ID in session or no database connection<br>";
}

echo "<hr>";

// 6. POST Data Simulation Test
echo "<h3>6. POST Data Simulation Test:</h3>";
echo "<form id='testForm' onsubmit='return false;'>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Test Value</th></tr>";
echo "<tr><td>Username</td><td><input type='text' name='Username' value='TestUser' /></td></tr>";
echo "<tr><td>Email</td><td><input type='email' name='Email' value='test@example.com' /></td></tr>";
echo "<tr><td>Phone</td><td><input type='text' name='Phone_number' value='1234567890' /></td></tr>";
echo "<tr><td>Address</td><td><input type='text' name='Address' value='Test Address' /></td></tr>";
echo "<tr><td>Current Password</td><td><input type='password' name='current_password' /></td></tr>";
echo "<tr><td>New Password</td><td><input type='password' name='new_password' value='NewPass123!' /></td></tr>";
echo "<tr><td>Confirm Password</td><td><input type='password' name='confirm_password' value='NewPass123!' /></td></tr>";
echo "</table>";
echo "<br><button onclick='simulateUpdate()'>Simulate Profile Update</button>";
echo "<div id='simulationResult'></div>";
echo "</form>";

echo "<hr>";

// 7. Check file paths
echo "<h3>7. File Path Check:</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Database config expected at: " . realpath('../../databaseConnection/db_config.php') . "<br>";
echo "Database config exists: " . (file_exists('../../databaseConnection/db_config.php') ? '✅ Yes' : '❌ No') . "<br>";

// Check for update_profile.php
$updateProfilePath = dirname(__FILE__) . '/update_profile.php';
echo "update_profile.php expected at: " . $updateProfilePath . "<br>";
echo "update_profile.php exists: " . (file_exists($updateProfilePath) ? '✅ Yes' : '❌ No') . "<br>";

echo "<hr>";

// 8. Provide recommendations
echo "<h3>8. Recommendations:</h3>";
if (!isset($_SESSION['Id'])) {
    echo "🔧 <strong>Issue:</strong> No user logged in<br>";
    echo "💡 <strong>Solution:</strong> Go to login page and log in first<br><br>";
}

if (!file_exists('../../databaseConnection/db_config.php')) {
    echo "🔧 <strong>Issue:</strong> Database config file not found<br>";
    echo "💡 <strong>Solution:</strong> Check the path to db_config.php<br><br>";
}

echo "📋 <strong>Next steps:</strong><br>";
echo "1. If no user is logged in: <a href='../LoginPage.html'>Login here</a><br>";
echo "2. If database issues: Check your db_config.php file<br>";
echo "3. If session issues: Clear browser cookies and try again<br>";
echo "4. Test password functionality using the tools above<br>";
echo "5. Check the simulation results to see what data would be sent<br>";

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
input, button {
    margin: 5px;
    padding: 5px;
}
button {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}
button:hover {
    background-color: #0056b3;
}
#passwordResult, #simulationResult {
    margin: 10px 0;
    padding: 10px;
    border-radius: 5px;
}
.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>

<script>
async function testPassword() {
    const password = document.getElementById('testPassword').value;
    const resultDiv = document.getElementById('passwordResult');
    
    if (!password) {
        resultDiv.innerHTML = '<div class="error">Please enter a password to test</div>';
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'test_password');
        formData.append('test_password', password);
        
        const response = await fetch('test_password_verify.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.text();
            resultDiv.innerHTML = '<div class="info">' + result + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="error">HTTP Error: ' + response.status + '</div>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<div class="error">Error: ' + error.message + '</div>';
    }
}

function simulateUpdate() {
    const form = document.getElementById('testForm');
    const formData = new FormData(form);
    const resultDiv = document.getElementById('simulationResult');
    
    let output = '<div class="info"><strong>Simulated POST data that would be sent:</strong><br>';
    
    for (let [key, value] of formData.entries()) {
        if (key.includes('password') && value) {
            output += key + ': [PROVIDED]<br>';
        } else {
            output += key + ': ' + value + '<br>';
        }
    }
    
    output += '<br><strong>Validation checks:</strong><br>';
    
    // Simulate validation
    const username = formData.get('Username');
    const email = formData.get('Email');
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    const currentPassword = formData.get('current_password');
    
    if (!username || !email) {
        output += '❌ Username and email are required<br>';
    } else {
        output += '✅ Username and email provided<br>';
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && emailRegex.test(email)) {
        output += '✅ Email format is valid<br>';
    } else {
        output += '❌ Invalid email format<br>';
    }
    
    // Password validation if provided
    if (newPassword) {
        if (!currentPassword) {
            output += '❌ Current password required for password change<br>';
        } else {
            output += '✅ Current password provided<br>';
        }
        
        if (newPassword !== confirmPassword) {
            output += '❌ New passwords do not match<br>';
        } else {
            output += '✅ New passwords match<br>';
        }
        
        if (newPassword.length < 8) {
            output += '❌ New password too short (minimum 8 characters)<br>';
        } else {
            output += '✅ New password length OK<br>';
        }
        
        // Test password strength
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/;
        if (passwordRegex.test(newPassword)) {
            output += '✅ New password meets complexity requirements<br>';
        } else {
            output += '❌ New password does not meet complexity requirements<br>';
        }
    }
    
    output += '</div>';
    resultDiv.innerHTML = output;
}
</script>