<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include 'db.php';
include 'user.php';


// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
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
        $stmt = $conn->prepare("INSERT INTO shows (movie_id, show_time) VALUES (?, ?)");
        $stmt->bind_param("is", $movie_id, $show_time);

        if ($stmt->execute()) {
            $successMessage = "Show added successfully!";
        } else {
            $showError = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all movies for the show form
$movies = [];
$result = $conn->query("SELECT id, title FROM movies");
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
$result->free();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>

<body>
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
                    <?php foreach ($movies as $movie) : ?>
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
    </div>
    <!-- list all the shows and movies -->
    <div class="container mx-auto mt-8">
        <h2 class="text-xl font-bold">Movies</h2>
        <table class="w-full mt-4">
            <thead>
                <tr>
                    <th class="border p-2">Title</th>
                    <th class="border p-2">Description</th>
                    <th class="border p-2">Release Date</th>
                    <th class="border p-2">Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM movies");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='border p-2'>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td class='border p-2'>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td class='border p-2'>" . htmlspecialchars($row['release_date']) . "</td>";
                    echo "<td class='border p-2'>" . htmlspecialchars($row['duration']) . "</td>";
                    echo "</tr>";
                }
                $result->free();
                ?>
            </tbody>
        </table>
        <h2 class="text-xl font-bold mt-8">Shows</h2>
        <table class="w-full mt-4">
            <thead>
                <tr>
                    <th class="border p-2">Movie</th>
                    <th class="border p-2">Show Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT shows.show_time, movies.title FROM shows JOIN movies ON shows.movie_id = movies.id");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='border p-2'>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td class='border p-2'>" . htmlspecialchars($row['show_time']) . "</td>";
                    echo "</tr>";
                }
                $result->free();
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>