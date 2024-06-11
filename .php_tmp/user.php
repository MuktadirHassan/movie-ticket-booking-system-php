<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';

session_start();

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_info = [];

if ($is_logged_in) {
    // Fetch user information from the database
    $stmt = $conn->prepare("SELECT username, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($username, $is_admin);
    $stmt->fetch();
    $user_info = [
        'username' => $username,
        'is_admin' => $is_admin
    ];
    $stmt->close();
}


?>

<div class="container mx-auto">
    <h1 class="text-2xl font-bold mt-8">User Information</h1>
    <?php if ($is_logged_in) : ?>
        <div class="bg-green-200 text-green-700 p-2 rounded mt-4">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user_info['username']); ?></p>
            <p><strong>Role:</strong> <?php echo $user_info['is_admin'] ? 'Admin' : 'User'; ?></p>
        </div>
    <?php else : ?>
        <div class="bg-red-200 text-red-700 p-2 rounded mt-4">
            <p>You are not logged in.</p>
            <div class="mt-4">
                <a href="login.php" class="p-2 bg-blue-500 text-white rounded">Login</a>
                <a href="register.php" class="p-2 bg-blue-500 text-white rounded">Register</a>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($is_logged_in) : ?>
        <div class="mt-4">
            <a href="logout.php" class="p-2 bg-red-500 text-white rounded">Logout</a>
        </div>
    <?php endif; ?>
</div>