<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';

// Start the session
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$movie_id = isset($_GET['movie_id']) ? $_GET['movie_id'] : null;
$show_id = isset($_GET['show_id']) ? $_GET['show_id'] : null;

// Fetch all movies
$movies = [];
$movies_result = $conn->query("SELECT id, title FROM movies");
while ($row = $movies_result->fetch_assoc()) {
    $movies[] = $row;
}
$movies_result->free();

// Fetch shows based on selected movie
$shows = [];
if ($movie_id) {
    $shows_stmt = $conn->prepare("SELECT id, show_time FROM shows WHERE movie_id = ?");
    $shows_stmt->bind_param("i", $movie_id);
    $shows_stmt->execute();
    $shows_result = $shows_stmt->get_result();
    while ($row = $shows_result->fetch_assoc()) {
        $shows[] = $row;
    }
    $shows_stmt->close();
}

// Fetch bookings for the selected show
$bookings = [];
$seats = [];
if ($show_id) {
    $bookings_stmt = $conn->prepare("
        SELECT
            users.username AS username,
            movies.title AS movie_title,
            shows.show_time AS show_time,
            seats.seat_number AS seat_number,
            seats.id AS seat_id
        FROM
            seats
        JOIN
            shows ON seats.show_id = shows.id
        JOIN
            movies ON shows.movie_id = movies.id
        JOIN
            users ON seats.booked_by = users.id
        WHERE
            shows.id = ?
    ");
    $bookings_stmt->bind_param("i", $show_id);
    $bookings_stmt->execute();
    $bookings_result = $bookings_stmt->get_result();
    while ($row = $bookings_result->fetch_assoc()) {
        $bookings[$row['username']][] = $row;
    }
    $bookings_stmt->close();

    // Fetch all seats for the show
    $seats_stmt = $conn->prepare("SELECT seat_number, booked_by FROM seats WHERE show_id = ?");
    $seats_stmt->bind_param("i", $show_id);
    $seats_stmt->execute();
    $seats_result = $seats_stmt->get_result();
    while ($row = $seats_result->fetch_assoc()) {
        $seats[] = $row;
    }
    $seats_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'head.php'; ?>

<body>
    <style>
        .seat {
            width: 30px;
            height: 30px;
            margin: 5px;
        }

        .available {
            background-color: green;
        }

        .booked {
            background-color: red;
        }
    </style>
    <?php include 'nav.php'; ?>
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mt-8">All Bookings</h1>

        <!-- Filter Form -->
        <form method="get" class="mt-4">
            <div class="mb-4">
                <label for="movie_id" class="block text-gray-700">Select Movie</label>
                <select name="movie_id" id="movie_id" class="mt-1 p-2 border rounded w-full" onchange="this.form.submit()">
                    <option value="">Select a movie</option>
                    <?php foreach ($movies as $movie) : ?>
                        <option value="<?php echo $movie['id']; ?>" <?php if ($movie_id == $movie['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($movie_id) : ?>
                <div class="mb-4">
                    <label for="show_id" class="block text-gray-700">Select Show</label>
                    <select name="show_id" id="show_id" class="mt-1 p-2 border rounded w-full" onchange="this.form.submit()">
                        <option value="">Select a show</option>
                        <?php foreach ($shows as $show) : ?>
                            <option value="<?php echo $show['id']; ?>" <?php if ($show_id == $show['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($show['show_time']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </form>

        <!-- Bookings List -->
        <?php if (!empty($bookings)) : ?>
            <div class="mt-4">
                <h2 class="text-xl font-bold">Bookings for Selected Show</h2>
                <?php foreach ($bookings as $username => $user_bookings) : ?>
                    <div class="bg-white p-4 shadow-lg rounded mb-4">
                        <p class="text-gray-700">User: <?php echo htmlspecialchars($username); ?></p>
                        <p class="text-gray-700">Seats:
                            <?php foreach ($user_bookings as $booking) : ?>
                                <?php echo htmlspecialchars($booking['seat_number']) . ' '; ?>
                            <?php endforeach; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($show_id) : ?>
            <p class="mt-4">No bookings found for the selected show.</p>
        <?php endif; ?>

        <!-- Available Seats Grid -->
        <?php if (!empty($seats)) : ?>
            <div class="mt-4">
                <h2 class="text-xl font-bold">Available Seats for Selected Show</h2>
                <div class="grid grid-cols-10 gap-2">
                    <?php foreach ($seats as $seat) : ?>
                        <div class="seat <?php echo $seat['booked_by'] ? 'booked' : 'available'; ?>">
                            <?php echo htmlspecialchars($seat['seat_number']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($show_id) : ?>
            <p class="mt-4">No seats found for the selected show.</p>
        <?php endif; ?>
    </div>
</body>

</html>