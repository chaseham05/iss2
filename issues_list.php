<?php
session_start();
include '../database/database.php'; // Include the database connection

$conn = Database::connect(); // Establish the database connection
$error_message = "";


// Handle issue operations (Update, Delete, Add Comment, Delete Comment, Create Issue)
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

if (isset($_POST['create_issue'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];

    $attachmentPath = null;
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            die("Only PDF files are allowed.");
        }

        if ($_FILES['pdf_attachment']['size'] > 2 * 1024 * 1024) {
            die("File size exceeds 2MB limit.");
        }

        $uploadDir = './uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $attachmentPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($fileTmpPath, $attachmentPath)) {
            die("Error moving the uploaded file.");
        }
    }

    try {
        $sql = "INSERT INTO iss_issues (short_description, long_description, priority, pdf_attachment, open_date, close_date) 
                VALUES (:title, :description, :status, :pdf, :open_date, :close_date)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status' => $status,
            ':pdf' => $attachmentPath,
            ':open_date' => $open_date,
            ':close_date' => $close_date
        ]);

        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error creating issue: " . $e->getMessage();
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

if (isset($_POST['create_comment'])) {
    $issue_id = $_POST['create_issue_id'];
    $comment = trim($_POST['create_comment_text']);
    $author = $_POST['create_comment_author'];

    try {
        $sql = "INSERT INTO iss_comments (iss_id, short_comment, per_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$issue_id, $comment, $author]);

        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error creating comment: " . $e->getMessage();
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

if (isset($_POST['update_comment'])) {
    $id = $_POST['id'];
    $short_comment = trim($_POST['short_comment']);
    $long_comment = trim($_POST['long_comment']);
    $per_id = $_POST['per_id'];

    try {
        $sql = "UPDATE iss_comments SET short_comment = ?, long_comment = ?, per_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$short_comment, $long_comment, $per_id, $id]);

        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating comment: " . $e->getMessage();
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
                                    <p><strong>Description:</strong> <?= htmlspecialchars($issue['long_description']); ?></p>
                                    <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']); ?></p>
                                    <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']); ?></p>
                                    <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']); ?></p>
                                    <p><strong>Person:</strong> <?= htmlspecialchars($issue['per_id']); ?></p>
                                    <?php if (!empty($issue['pdf_attachment'])): ?>
                                        <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank"
                                            class="btn btn-outline-secondary btn-sm">View PDF</a>
                                    <?php endif; ?>

                                    <hr>
                                    <h5>Comments</h5>
                                    <button class="btn btn-success btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#createCommentModal<?= $issue['id']; ?>">+ Add New Comment</button>
                                    <?php
                                    // Fetch comments for the current issue, including the author's name
                                    $comments_stmt = $conn->prepare("
                                        SELECT c.id AS comment_id, c.short_comment, c.long_comment, c.per_id, CONCAT(p.fname, ' ', p.lname) AS author_name 
                                        FROM iss_comments c 
                                        LEFT JOIN iss_persons p ON c.per_id = p.id 
                                        WHERE c.iss_id = ? 
                                        ORDER BY c.id DESC
                                    ");
                                    $comments_stmt->execute([$issue['id']]);
                                    $issue_comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (!empty($issue_comments)): ?>
                                        <ul class="list-group">
                                            <?php foreach ($issue_comments as $comment): ?>
                                                <li class="list-group-item">
                                                    <p><strong>Author:</strong> 
                                                        <?= htmlspecialchars($comment['author_name'] ?? 'Unknown') . " (ID: " . htmlspecialchars($comment['per_id']) . ")"; ?>
                                                    </p>
                                                    <p><?= htmlspecialchars($comment['short_comment']); ?></p>
                                                    <div class="d-flex justify-content-end">
                                                        <!-- Read Comment Button -->
                                                        <button class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#readCommentModal<?= $comment['comment_id']; ?>">Read</button>
                                                        <!-- Update Comment Button -->
                                                        <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#updateCommentModal<?= $comment['comment_id']; ?>">Update</button>
                                                        <!-- Delete Comment Button -->
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?= $comment['comment_id']; ?>">
                                                            <button type="submit" name="delete_comment" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                </li>

                                                <!-- Read Comment Modal -->
                                                <div class="modal fade" id="readCommentModal<?= $comment['comment_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Comment Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>Author:</strong> <?= htmlspecialchars($comment['author_name'] ?? 'Unknown') . " (ID: " . htmlspecialchars($comment['per_id']) . ")"; ?></p>
                                                                <p><strong>Full Comment:</strong></p>
                                                                <p><?= nl2br(htmlspecialchars($comment['long_comment'])); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Update Comment Modal -->
                                                <div class="modal fade" id="updateCommentModal<?= $comment['comment_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Comment</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST">
                                                                    <input type="hidden" name="id" value="<?= $comment['comment_id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label>Short Comment:</label>
                                                                        <textarea name="short_comment" class="form-control" required><?= htmlspecialchars($comment['short_comment']); ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label>Long Comment:</label>
                                                                        <textarea name="long_comment" class="form-control" required><?= htmlspecialchars($comment['long_comment']); ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label>Author (Person ID):</label>
                                                                        <input type="text" name="per_id" class="form-control" value="<?= htmlspecialchars($comment['per_id']); ?>" required>
                                                                    </div>
                                                                    <button type="submit" name="update_comment" class="btn btn-primary">Save Changes</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>No comments available for this issue.</p>
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
                                        <input type="hidden" name="id" value="<?= $issue['id']; ?>">                                        <div class="mb-3">
                                            <label class="form-label">Short Description:</label>
                                            <input type="text" name="short_description" class="form-control"
                                                value="<?= htmlspecialchars($issue['short_description']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Long Description:</label>
                                            <textarea name="long_description" class="form-control"
                                                required><?= htmlspecialchars($issue['long_description']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Priority:</label>
                                            <select name="priority" class="form-control" required>
                                                <option value="Low" <?= $issue['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="Medium" <?= $issue['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="High" <?= $issue['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Attach PDF (Max 2MB):</label>
                                            <input type="file" name="pdf_attachment" class="form-control" accept="application/pdf">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="update_issue" class="btn btn-primary">Save Changes</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Create Comment Modal -->
                    <div class="modal fade" id="createCommentModal<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add New Comment</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="create_issue_id" value="<?= $issue['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Comment:</label>
                                            <textarea name="create_comment_text" class="form-control" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Author (Person ID):</label>
                                            <input type="text" name="create_comment_author" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="create_comment" class="btn btn-primary">Add Comment</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            </tbody>
        </table>

         <!-- Create Issue Modal -->
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
                                <select name="status" class="form-control" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Open Date:</label>
                                <input type="date" name="open_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
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
        <!-- Create Comment Modal - inserted by chatgpt -->
        <div class="modal fade" id="createCommentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Comment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Issue ID:</label>
                                <input type="number" name="create_issue_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comment:</label>
                                <textarea name="create_comment_text" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Author (Person ID):</label>
                                <input type="text" name="create_comment_author" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="create_comment" class="btn btn-primary">Add Comment</button>
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