<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';

session_start();


// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch bookings for the logged-in user
$bookings_stmt = $conn->prepare("
    SELECT
        movies.title AS movie_title,
        movies.description AS movie_description,
        movies.release_date AS movie_release_date,
        movies.duration AS movie_duration,
        shows.id AS show_id,
        shows.show_time AS show_time,
        seats.seat_number AS seat_number
    FROM
        seats
    JOIN
        shows ON seats.show_id = shows.id
    JOIN
        movies ON shows.movie_id = movies.id
    WHERE
        seats.booked_by = ?
");
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings_stmt->close();

// Organize bookings by movie and then by show
$bookings = [];
while ($row = $bookings_result->fetch_assoc()) {
    $movie_title = $row['movie_title'];
    $show_id = $row['show_id'];

    if (!isset($bookings[$movie_title])) {
        $bookings[$movie_title] = [
            'description' => $row['movie_description'],
            'release_date' => $row['movie_release_date'],
            'duration' => $row['movie_duration'],
            'shows' => []
        ];
    }

    if (!isset($bookings[$movie_title]['shows'][$show_id])) {
        $bookings[$movie_title]['shows'][$show_id] = [
            'show_time' => $row['show_time'],
            'seats' => []
        ];
    }

    $bookings[$movie_title]['shows'][$show_id]['seats'][] = $row['seat_number'];
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'head.php'; ?>

<body>
    <?php include 'nav.php'; ?>
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mt-8">My Bookings</h1>

        <?php if (empty($bookings)) : ?>
            <p class="mt-4">You have no bookings.</p>
        <?php else : ?>
            <div class="mt-4">
                <?php foreach ($bookings as $movie_title => $movie) : ?>
                    <div class="bg-white p-4 shadow-lg rounded mb-4">
                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($movie_title); ?></h2>
                        <p class="text-gray-700 mt-2"><?php echo htmlspecialchars($movie['description']); ?></p>
                        <p class="text-gray-700 mt-2">Release Date: <?php echo htmlspecialchars($movie['release_date']); ?></p>
                        <p class="text-gray-700 mt-2">Duration: <?php echo htmlspecialchars($movie['duration']); ?> minutes</p>

                        <?php foreach ($movie['shows'] as $show_id => $show) : ?>
                            <div class="bg-gray-100 p-4 shadow rounded mt-4">
                                <p class="text-gray-700 mt-2">Show Time: <?php echo htmlspecialchars($show['show_time']); ?></p>
                                <p class="text-gray-700 mt-2">Seats: <?php echo htmlspecialchars(implode(', ', $show['seats'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>