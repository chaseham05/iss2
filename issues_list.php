<?php
session_start();
include '../database/database.php'; // Include the database connection

$conn = Database::connect(); // Establish the database connection
$error_message = "";


// Handle issue operations (Update, Delete, Add Comment, Delete Comment)
if (isset($_POST['update_issue'])) {
    $id = $_POST['id'];
    $short_description = trim($_POST['short_description']);
    $long_description = trim($_POST['long_description']);
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = trim($_POST['org']);
    $project = trim($_POST['project']);
    $per_id = $_POST['per_id'];

    // inserted by chatgpt — PDF Upload Handling
    $attachmentPath = null;

    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileSize = $_FILES['pdf_attachment']['size'];
        $fileType = $_FILES['pdf_attachment']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            die("Only PDF files are allowed.");
        }

        if ($fileSize > 2 * 1024 * 1024) {
            die("File size exceeds 2MB limit.");
        }

        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadFileDir = './uploads/';
        $destPath = $uploadFileDir . $newFileName;
        
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        } 

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $attachmentPath = $destPath;
        } else {
            die("Error moving the uploaded file.");
        }
    } else {
        // fallback: use existing attachment if updating
        $existing = $conn->prepare("SELECT pdf_attachment FROM iss_issues WHERE id = ?");
        $existing->execute([$id]);
        $attachmentPath = $existing->fetchColumn();
    }

    // inserted by chatgpt — updated query with PDF
    try {
        $sql = "UPDATE iss_issues SET short_description=?, long_description=?, open_date=?, close_date=?, priority=?, org=?, project=?, per_id=?, pdf_attachment=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$short_description, $long_description, $open_date, $close_date, $priority, $org, $project, $per_id, $attachmentPath, $id]);

        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating issue: " . $e->getMessage();
    }
}


if (isset($_POST['delete_issue'])) {
    $id = $_POST['id'];

    try {
        $sql = "DELETE FROM iss_issues WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error deleting issue: " . $e->getMessage();
    }
}

if (isset($_POST['add_comment'])) {
    $issue_id = $_POST['issue_id'];
    $comment = trim($_POST['comment']);
    $author = $_POST['author'];

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

if (isset($_POST['delete_comment'])) {
    if (!empty($_POST['id'])) {
        $id = $_POST['id'];
        echo "Attempting to delete comment with ID: $id<br>";

        try {
            $sql = "DELETE FROM iss_comments WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo "Comment deleted successfully.<br>";
            } else {
                echo "Comment with ID $id not found.<br>";
            }

            header("Location: issues_list.php");
            exit();
        } catch (PDOException $e) {
            echo "Error deleting comment: " . $e->getMessage();
        }
    } else {
        echo "No comment ID provided for deletion.";
    }
}


// Fetch all issues
$sql = "SELECT * FROM iss_issues ORDER BY open_date DESC";
$issues = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch all comments
$comments_sql = "SELECT * FROM iss_comments ORDER BY iss_id ASC";
$comments = $conn->query($comments_sql);

if (!$comments) {
    $comments = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Issues List - DSR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>


    <div class="container mt-3">
        <h2 class="text-center">Issues List</h2>
        <a href="persons_list.php" class="btn btn-secondary mb-3">Go to Persons List</a>
        <!-- Display Error Message -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
        <?php endif; ?>

        <!-- "+" Button to Add Issue -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Issues</h3>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createIssueModal">+ Create New Issue</button> <!-- inserted by chatgpt -->
            </div>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Close Date</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?= htmlspecialchars($issue['id']); ?></td>
                        <td><?= htmlspecialchars($issue['short_description']); ?></td>
                        <td><?= htmlspecialchars($issue['open_date']); ?></td>
                        <td><?= htmlspecialchars($issue['close_date']); ?></td>
                        <td><?= htmlspecialchars($issue['priority']); ?></td>
                        <td>
                            <!-- Read Button -->
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>
                            <!-- Update Button -->
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#updateIssue<?= $issue['id']; ?>">U</button>
                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                <button type="submit" name="delete_issue" class="btn btn-danger btn-sm">D</button>
                            </form>
                            <?php if (!empty($issue['pdf_attachment'])): ?>
                                <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank"
                                    class="btn btn-outline-secondary btn-sm">View PDF</a>
                            <?php endif; ?>

                        </td>

                    </tr>

                    <!-- Read Modal -->
                    <div class="modal fade" id="readIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Issue Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Description:</strong> <?= htmlspecialchars($issue['long_description']); ?>
                                    </p>
                                    <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']); ?></p>
                                    <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']); ?></p>
                                    <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']); ?></p>
                                    <?php if (!empty($issue['pdf_attachment'])): ?>
                                        <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank"
                                            class="btn btn-outline-secondary btn-sm">View PDF</a>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Modal -->
                    <div class="modal fade" id="updateIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Issue</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                        <label>Short Description:</label>
                                        <input type="text" name="short_description"
                                            value="<?= htmlspecialchars($issue['short_description']); ?>" required><br>
                                        <label>Long Description:</label>
                                        <textarea name="long_description"
                                            required><?= htmlspecialchars($issue['long_description']); ?></textarea><br>
                                        <label for="pdf_attachment">Attach PDF (Max 2MB):</label>
                                        <!-- inserted by chatgpt -->
                                        <input type="file" name="pdf_attachment" accept="application/pdf" />
                                        <!-- inserted by chatgpt -->

                                        <button type="submit" name="update_issue" class="btn btn-primary">Save
                                            Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            </tbody>
        </table>

        <h2 class="text-center mt-4">Comments</h2>
        <!-- "+" Button to Add Comment -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Comments</h3>
            <a href="create_comment.php" class="btn btn-success">+ Create New Comment</a>
        </div>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>Issue ID</th>
                    <th>Comment</th>
                    <th>Author</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?= htmlspecialchars($comment['iss_id']); ?></td>
                        <td><?= htmlspecialchars($comment['short_comment']); ?></td>
                        <td><?= htmlspecialchars($comment['per_id']); ?></td>
                        <td>
                            <!-- Read Button -->
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                data-bs-target="#readComment<?= $comment['iss_id']; ?>">R</button>
                            <!-- Update Button -->
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#updateComment<?= $comment['iss_id']; ?>">U</button>
                            <!-- Delete Button -->
                            <!-- Delete Comment Button -->
                            <form method="POST" action="issues_list.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $comment['id']; ?>">
                                <button type="submit" name="delete_comment" class="btn btn-danger btn-sm">D</button>
                            </form>

                        </td>
                    </tr>

                    <!-- Read Comment Modal -->
                    <div class="modal fade" id="readComment<?= $comment['iss_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Comment Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Comment:</strong> <?= htmlspecialchars($comment['short_comment']); ?></p>
                                    <p><strong>Author:</strong> <?= htmlspecialchars($comment['per_id']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Comment Modal -->
                    <div class="modal fade" id="updateComment<?= $comment['iss_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Comment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $comment['iss_id']; ?>">
                                        <label>Comment:</label>
                                        <textarea name="short_comment"
                                            required><?= htmlspecialchars($comment['short_comment']); ?></textarea><br>
                                        <label>Author:</label>
                                        <input type="text" name="per_id"
                                            value="<?= htmlspecialchars($comment['per_id']); ?>" required><br>
                                        <button type="submit" name="update_comment" class="btn btn-primary">Save
                                            Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Create Issue Modal - inserted by chatgpt -->
<div class="modal fade" id="createIssueModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Create New Issue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title:</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description:</label>
            <textarea name="description" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Priority:</label>
            <input type="text" name="status" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Open Date:</label>
            <input type="date" name="open_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Close Date:</label>
            <input type="date" name="close_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Attach PDF (Max 2MB):</label>
            <input type="file" name="pdf_attachment" class="form-control" accept="application/pdf">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="create_issue" class="btn btn-primary">Create Issue</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php Database::disconnect(); ?>