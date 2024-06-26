<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo "<div class='container mx-auto'><h1 class='text-2xl font-bold mt-8'>You are not authorized to access this page.</h1></div>";
    exit();
}

$movieError = $showError = '';
$successMessage = '';

// Handle form submission for adding movies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $release_date = $_POST['release_date'];
    $duration = $_POST['duration'];

    if (empty($title) || empty($release_date) || empty($duration)) {
        $movieError = "Title, Release Date, and Duration are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO movies (title, description, release_date, duration) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $description, $release_date, $duration);

        if ($stmt->execute()) {
            $successMessage = "Movie added successfully!";
        } else {
            $movieError = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle form submission for adding shows
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_show'])) {
    $movie_id = $_POST['movie_id'];
    $show_time = $_POST['show_time'];

    if (empty($movie_id) || empty($show_time)) {
        $showError = "Movie and Show Time are required.";
    } else {
        // Start a transaction
        $conn->begin_transaction();

        try {
            // Insert the new show into the shows table
            $stmt = $conn->prepare("INSERT INTO shows (movie_id, show_time) VALUES (?, ?)");
            $stmt->bind_param("is", $movie_id, $show_time);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            $show_id = $stmt->insert_id;
            $stmt->close();

            // Insert seats for the new show
            $seat_stmt = $conn->prepare("INSERT INTO seats (show_id, seat_number, is_booked) VALUES (?, ?, FALSE)");
            for ($i = 1; $i <= 64; $i++) {
                $seat_number = str_pad($i, 2, '0', STR_PAD_LEFT);
                $seat_stmt->bind_param("is", $show_id, $seat_number);
                if (!$seat_stmt->execute()) {
                    throw new Exception($seat_stmt->error);
                }
            }
            $seat_stmt->close();

            // Commit the transaction
            $conn->commit();
            $successMessage = "Show and seats added successfully!";
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            $showError = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete requests for movies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_movie'])) {
    $movie_id = $_POST['movie_id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete shows and seats associated with the movie
        $conn->query("DELETE FROM seats WHERE show_id IN (SELECT id FROM shows WHERE movie_id = $movie_id)");
        $conn->query("DELETE FROM shows WHERE movie_id = $movie_id");
        // Delete the movie
        $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->bind_param("i", $movie_id);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();
        $successMessage = "Movie deleted successfully!";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $movieError = "Error: " . $e->getMessage();
    }
}

// Handle delete requests for shows
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_show'])) {
    $show_id = $_POST['show_id'];

    try {
        // Delete seats associated with the show
        $conn->query("DELETE FROM seats WHERE show_id = $show_id");
        // Delete the show
        $stmt = $conn->prepare("DELETE FROM shows WHERE id = ?");
        $stmt->bind_param("i", $show_id);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();

        $successMessage = "Show deleted successfully!";
    } catch (Exception $e) {
        $showError = "Error: " . $e->getMessage();
    }
}

// Fetch all movies and their shows for display
$movies_with_shows = [];
$movie_result = $conn->query("SELECT * FROM movies");
while ($movie = $movie_result->fetch_assoc()) {
    $show_result = $conn->query("SELECT * FROM shows WHERE movie_id = " . $movie['id']);
    $shows = [];
    while ($show = $show_result->fetch_assoc()) {
        $shows[] = $show;
    }
    $movie['shows'] = $shows;
    $movies_with_shows[] = $movie;
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'head.php'; ?>

<body>
    <?php include 'nav.php'; ?>
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mt-8">Admin Panel</h1>
        <?php if ($successMessage) : ?>
            <div class="bg-green-200 text-green-700 p-2 rounded mt-4">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        <h2 class="text-xl font-bold mt-8">Add Movie</h2>
        <?php if ($movieError) : ?>
            <div class="bg-red-200 text-red-700 p-2 rounded mt-4">
                <?php echo htmlspecialchars($movieError); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="mt-4">
            <input type="hidden" name="add_movie" value="1">
            <div class="mb-4">
                <label for="title" class="block text-gray-700">Title</label>
                <input type="text" name="title" id="title" class="mt-1 p-2 border rounded w-full" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700">Description</label>
                <textarea name="description" id="description" class="mt-1 p-2 border rounded w-full"></textarea>
            </div>
            <div class="mb-4">
                <label for="release_date" class="block text-gray-700">Release Date</label>
                <input type="date" name="release_date" id="release_date" class="mt-1 p-2 border rounded w-full" required>
            </div>
            <div class="mb-4">
                <label for="duration" class="block text-gray-700">Duration (in minutes)</label>
                <input type="number" name="duration" id="duration" class="mt-1 p-2 border rounded w-full" required>
            </div>
            <button type="submit" class="p-2 bg-blue-500 text-white rounded">Add Movie</button>
        </form>

        <h2 class="text-xl font-bold mt-8">Add Show</h2>
        <?php if ($showError) : ?>
            <div class="bg-red-200 text-red-700 p-2 rounded mt-4">
                <?php echo htmlspecialchars($showError); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="mt-4">
            <input type="hidden" name="add_show" value="1">
            <div class="mb-4">
                <label for="movie_id" class="block text-gray-700">Movie</label>
                <select name="movie_id" id="movie_id" class="mt-1 p-2 border rounded w-full" required>
                    <option value="">Select a movie</option>
                    <?php foreach ($movies_with_shows as $movie) : ?>
                        <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="show_time" class="block text-gray-700">Show Time</label>
                <input type="datetime-local" name="show_time" id="show_time" class="mt-1 p-2 border rounded w-full" required>
            </div>
            <button type="submit" class="p-2 bg-blue-500 text-white rounded">Add Show</button>
        </form>

        <h2 class="text-xl font-bold mt-8">Movies and Shows</h2>
        <?php if ($movieError) : ?>
            <div class="bg-red-200 text-red-700 p-2 rounded mt-4">
                <?php echo htmlspecialchars($movieError); ?>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
            <?php foreach ($movies_with_shows as $movie) : ?>
                <div class="border rounded p-4">
                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <p><?php echo htmlspecialchars($movie['description']); ?></p>
                    <p><strong>Release Date:</strong> <?php echo htmlspecialchars($movie['release_date']); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($movie['duration']); ?> minutes</p>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="delete_movie" value="1">
                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                        <button type="submit" class="p-2 bg-red-500 text-white rounded">Delete Movie</button>
                    </form>
                    <?php if (count($movie['shows']) > 0) : ?>
                        <h4 class="mt-4 font-bold">Shows</h4>
                        <ul>
                            <?php foreach ($movie['shows'] as $show) : ?>
                                <li class="border rounded p-2 mt-2">
                                    <p><strong>Show Time:</strong> <?php echo htmlspecialchars($show['show_time']); ?></p>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="delete_show" value="1">
                                        <input type="hidden" name="show_id" value="<?php echo $show['id']; ?>">
                                        <button type="submit" class="p-2 bg-red-500 text-white rounded">Delete Show</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>