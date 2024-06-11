<?php include 'header.php'; ?>
<?php include 'user.php'; ?>

<?php
// show movies
$movies = [];
$result = $conn->query("SELECT id, title, description, release_date, duration FROM movies");
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
?>

<body>
    <div class="container mx-auto">
        <h1 class="text-4xl font-bold text-center mt-10">Absolute Cinema</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-10">
            <?php foreach ($movies as $movie) : ?>
                <div class="bg-white p-4 shadow-lg rounded">
                    <h2 class="text-xl font-bold"><?php echo htmlspecialchars($movie['title']); ?></h2>
                    <p class="text-gray-700 mt-2"><?php echo htmlspecialchars($movie['description']); ?></p>
                    <p class="text-gray-700 mt-2">Release Date: <?php echo htmlspecialchars($movie['release_date']); ?></p>
                    <p class="text-gray-700 mt-2">Duration: <?php echo htmlspecialchars($movie['duration']); ?> minutes</p>
                    <a href="book.php?movie_id=<?php echo $movie['id']; ?>" class="block bg-blue-500 text-white p-2 mt-4 rounded">Book Now</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>