<?php
require '../database/database.php'; // Database connection

$conn = Database::connect();  // Establish the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    try {
        // Prepare the statement to insert a new issue
        $stmt = $conn->prepare("INSERT INTO iss_issues (short_description, long_description, priority) VALUES (:title, :description, :status)");
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status' => $status
        ]);
        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Issue</title>
</head>
<body>
    <h2>Create a New Issue</h2>
    <form method="POST">
        <label>Title:</label>
        <input type="text" name="title" required><br>
        <label>Description:</label>
        <textarea name="description" required></textarea><br>
        <label>Status:</label>
        <input type="text" name="status" required><br>
        <button type="submit">Create Issue</button>
    </form>
    <a href="issues_list.php">Back to Issues</a>
</body>
</html>