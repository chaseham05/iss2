<?php
session_start();
include '../database/database.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    session_destroy(); // Destroy the session if not logged in
    header("Location: login.php");
    exit();
}

$conn = Database::connect(); // Establish the database connection
$error_message = "";

// Fetch all persons
$sql = "SELECT * FROM iss_persons ORDER BY lname ASC";
$persons = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Persons List - DSR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-3">
        <h2 class="text-center">Persons List</h2>

        <!-- Back to Issues List Link -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="issues_list.php" class="btn btn-secondary">Back to Issues List</a>
            <form method="POST" action="logout.php">
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($persons as $person) : ?>
                    <tr>
                        <td><?= htmlspecialchars($person['id']); ?></td>
                        <td><?= htmlspecialchars($person['fname']); ?></td>
                        <td><?= htmlspecialchars($person['lname']); ?></td>
                        <td><?= htmlspecialchars($person['email']); ?></td>
                        <td><?= htmlspecialchars($person['mobile']); ?></td>
                        <td><?= htmlspecialchars($person['admin']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php Database::disconnect(); ?>
