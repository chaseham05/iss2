<?php
session_start();
include '../database/database.php'; // Include the database connection

$conn = Database::connect(); // Establish the database connection
$error_message = "";

// Handle issue operations (Update, Delete, Add Comment, Delete Comment, Create Issue)
if (isset($_POST['update_issue'])) {
    $id = $_POST['id'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $priority = $_POST['priority'];

    // Handle file upload if a new PDF is provided
    $pdf_attachment = null;
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'pdf') {
            $uploadDir = './uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $pdf_attachment = $uploadDir . $newFileName;
            move_uploaded_file($fileTmpPath, $pdf_attachment);
        }
    }

    // Update the issue in the database
    $sql = "UPDATE iss_issues SET short_description = ?, long_description = ?, priority = ?, pdf_attachment = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$short_description, $long_description, $priority, $pdf_attachment, $id]);
}

if (isset($_POST['create_issue'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['status'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];

    // Handle file upload
    $pdf_attachment = null;
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'pdf') {
            $uploadDir = './uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $pdf_attachment = $uploadDir . $newFileName;
            move_uploaded_file($fileTmpPath, $pdf_attachment);
        }
    }

    // Insert the new issue into the database
    $sql = "INSERT INTO iss_issues (short_description, long_description, priority, open_date, close_date, pdf_attachment) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$title, $description, $priority, $open_date, $close_date, $pdf_attachment]);
}

if (isset($_POST['delete_issue'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM iss_issues WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

if (isset($_POST['create_comment'])) {
    $issue_id = $_POST['create_issue_id'];
    $comment_text = $_POST['create_comment_text'];
    $author = $_POST['create_comment_author'];

    // Insert the new comment into the database
    $sql = "INSERT INTO iss_comments (iss_id, short_comment, per_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$issue_id, $comment_text, $author]);
}

if (isset($_POST['update_comment'])) {
    $id = $_POST['id'];
    $short_comment = $_POST['short_comment'];
    $author = $_POST['per_id'];

    // Update the comment in the database
    $sql = "UPDATE iss_comments SET short_comment = ?, per_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$short_comment, $author, $id]);
}

if (isset($_POST['delete_comment'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM iss_comments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
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
        <!-- Header Section -->
        <h2 class="text-center">Issues List</h2>
        <a href="persons_list.php" class="btn btn-secondary mb-3">Go to Persons List</a>

        <!-- Display Error Message -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
        <?php endif; ?>

        <!-- Issues Section -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Issues</h3>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createIssueModal">+ Create New Issue</button>
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
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id']; ?>">U</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                <button type="submit" name="delete_issue" class="btn btn-danger btn-sm">D</button>
                            </form>
                            <?php if (!empty($issue['pdf_attachment'])): ?>
                                <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">View PDF</a>
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
                                        <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">View PDF</a>
                                    <?php endif; ?>
                                    <hr>
                                    <h5>Comments</h5>
                                    <?php
                                    $comments_stmt = $conn->prepare("SELECT * FROM iss_comments WHERE iss_id = ? ORDER BY id DESC");
                                    $comments_stmt->execute([$issue['id']]);
                                    $issue_comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (!empty($issue_comments)): ?>
                                        <ul class="list-group">
                                            <?php foreach ($issue_comments as $comment): ?>
                                                <li class="list-group-item">
                                                    <p><strong>Author:</strong> <?= htmlspecialchars($comment['per_id']); ?></p>
                                                    <p><?= htmlspecialchars($comment['short_comment']); ?></p>
                                                </li>
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
                                        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Short Description:</label>
                                            <input type="text" name="short_description" class="form-control" value="<?= htmlspecialchars($issue['short_description']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Long Description:</label>
                                            <textarea name="long_description" class="form-control" required><?= htmlspecialchars($issue['long_description']); ?></textarea>
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
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Comments Section -->
        <h2 class="text-center mt-4">Comments</h2>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Comments</h3>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCommentModal">+ Create New Comment</button>
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
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readComment<?= $comment['iss_id']; ?>">R</button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateComment<?= $comment['iss_id']; ?>">U</button>
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
                                        <textarea name="short_comment" required><?= htmlspecialchars($comment['short_comment']); ?></textarea><br>
                                        <label>Author:</label>
                                        <input type="text" name="per_id" value="<?= htmlspecialchars($comment['per_id']); ?>" required><br>
                                        <button type="submit" name="update_comment" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
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

        <!-- Create Comment Modal -->
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