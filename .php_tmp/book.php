<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';
include 'user.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch movie and show details
$movie_id = $_GET['movie_id'] ?? null;
if (!$movie_id) {
    header('Location: index.php');
    exit();
}

$movie_stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$movie_stmt->bind_param("i", $movie_id);
$movie_stmt->execute();
$movie = $movie_stmt->get_result()->fetch_assoc();
$movie_stmt->close();

$shows_stmt = $conn->prepare("SELECT * FROM shows WHERE movie_id = ?");
$shows_stmt->bind_param("i", $movie_id);
$shows_stmt->execute();
$shows = $shows_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shows_stmt->close();

// Handle seat booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_id'], $_POST['seats'])) {
    $show_id = $_POST['show_id'];
    $selected_seats = $_POST['seats'];
    $user_id = $_SESSION['user_id'];

    foreach ($selected_seats as $seat_number) {
        $seat_stmt = $conn->prepare("UPDATE seats SET is_booked = TRUE, booked_by = ? WHERE show_id = ? AND seat_number = ? AND is_booked = FALSE");
        $seat_stmt->bind_param("iis", $user_id, $show_id, $seat_number);
        $seat_stmt->execute();
        $seat_stmt->close();
    }

    $successMessage = "Seats booked successfully!";
}

// Fetch available seats for the selected show
$show_id = $_GET['show_id'] ?? null;
$seats = [];
if ($show_id) {
    $seats_stmt = $conn->prepare("SELECT * FROM seats WHERE show_id = ?");
    $seats_stmt->bind_param("i", $show_id);
    $seats_stmt->execute();
    $seats = $seats_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $seats_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Seats</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mt-8">Book Seats for <?php echo htmlspecialchars($movie['title']); ?></h1>

        <?php if (isset($successMessage)) : ?>
            <div class="bg-green-200 text-green-700 p-2 rounded mt-4">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <h2 class="text-xl font-bold mt-8">Select Show</h2>
        <form method="get" class="mt-4">
            <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
            <select name="show_id" onchange="this.form.submit()" class="mt-1 p-2 border rounded w-full">
                <option value="">Select a show</option>
                <?php foreach ($shows as $show) : ?>
                    <option value="<?php echo $show['id']; ?>" <?php echo isset($show_id) && $show_id == $show['id'] ? 'selected' : ''; ?>>
                        <?php echo $show['show_time']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($show_id && $seats) : ?>
            <h2 class="text-xl font-bold mt-8">Available Seats</h2>
            <form method="post" class="mt-4">
                <input type="hidden" name="show_id" value="<?php echo $show_id; ?>">
                <div class="grid grid-cols-8 gap-2">
                    <?php foreach ($seats as $seat) : ?>
                        <div class="p-2 border rounded <?php echo $seat['is_booked'] ? 'bg-gray-300' : 'bg-green-300'; ?>">
                            <label>
                                <?php if (!$seat['is_booked']) : ?>
                                    <input type="checkbox" name="seats[]" value="<?php echo $seat['seat_number']; ?>">
                                <?php endif; ?>
                                <?php echo $seat['seat_number']; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="p-2 bg-blue-500 text-white rounded mt-4">Book Selected Seats</button>
            </form>
        <?php elseif ($show_id) : ?>
            <p class="mt-4">No seats available for this show.</p>
        <?php endif; ?>
    </div>
</body>

</html>