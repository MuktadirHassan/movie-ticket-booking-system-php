<?php
// Check if the user is logged in
$logged_in = isset($_SESSION['user_id']);
$username = $logged_in ? $_SESSION['username'] : null;
$is_admin = $logged_in ? $_SESSION['is_admin'] : false;
?>

<nav class="bg-gray-800 p-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="text-white text-lg font-bold">
            <a href="index.php">Absolute Cinema</a>
        </div>
        <div>
            <a href="index.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Home</a>
            <?php if ($logged_in) : ?>
                <a href="mybookings.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">My Bookings</a>
                <?php if ($is_admin) : ?>
                    <a href="admin.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Admin Panel</a>
                <?php endif; ?>
                <?php if ($is_admin) : ?>
                    <a href="all_bookings.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">All bookings</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="flex items-center">
            <?php if ($logged_in) : ?>
                <span class="text-gray-300 mr-4">Welcome, <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Logout</a>
            <?php else : ?>
                <a href="login.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Login</a>
                <a href="register.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>