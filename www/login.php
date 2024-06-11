<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // Check if the username exists
        $stmt = $conn->prepare("SELECT id, password, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $is_admin);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                // Password is correct, start a session
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = $is_admin;

                // Redirect to home page or admin panel based on user role
                if ($is_admin) {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mt-8">Login</h1>
        <?php if (isset($error)) : ?>
            <div class="bg-red-200 text-red-700 p-2 rounded mt-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="mt-4">
            <div class="mb-4">
                <label for="username" class="block text-gray-700">Username</label>
                <input type="text" name="username" id="username" class="mt-1 p-2 border rounded w-full" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" name="password" id="password" class="mt-1 p-2 border rounded w-full" required>
            </div>
            <button type="submit" class="p-2 bg-blue-500 text-white rounded">Login</button>
        </form>
    </div>
</body>

</html>