<?php

require '../database/database.php';
$conn = Database::connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $issue_id = $_POST['issue_id'];
    $comment = trim($_POST['comment']);
    $author = trim($_POST['author']);

    if (empty($comment) || empty($author)) {
        $error_message = "Comment and author fields are required.";
    } else {
        try {
            $sql = "INSERT INTO iss_comments (iss_id, short_comment, per_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$issue_id, $comment, $author]);

            header("Location: issues_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error adding comment: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Comment</title>
    <style>
        form {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input, textarea, button {
            width: 100%;
            margin: 5px 0;
            padding: 10px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #3498db;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .error-message {
            color: red;
        }
    </style>
</head>
<body>

<h2>Add Comment</h2>

<?php if (!empty($error_message)): ?>
    <p class="error-message"><?= $error_message ?></p>
<?php endif; ?>

<form method="POST" action="create_comment.php">
    <label for="issue_id">Issue ID:</label>
    <input type="text" name="issue_id" required>

    <label for="author">Author ID:</label>
    <input type="text" name="author" required>

    <label for="comment">Comment:</label>
    <textarea name="comment" rows="4" required></textarea>

    <button type="submit">Add Comment</button>
</form>

</body>
</html>
