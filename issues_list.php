<?php
/**
 * Author: Chase Hamilton, Cis355
 * Description: This file handles the issues list page, including CRUD operations for issues and comments.
 * It also includes filtering, sorting, and file upload functionality.
 */

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    session_destroy(); // Destroy the session if not logged in
    header("Location: login.php");
    exit();
}

require '../database/database.php'; // Include the database connection

$conn = Database::connect(); // Establish the database connection
$error_message = "";

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Comment Handler
    if (isset($_POST['update_comment'])) {
        $id = $_POST['id'];
        $short_comment = trim($_POST['short_comment']);

        // Check if the user is an admin or the author of the comment
        $stmt = $conn->prepare("SELECT per_id FROM iss_comments WHERE id = ?");
        $stmt->execute([$id]);
        $comment_owner = $stmt->fetchColumn();

        if ($_SESSION['admin'] !== 'Y' && $_SESSION['user_id'] !== $comment_owner) {
            die("Unauthorized action. You do not have permission to update this comment.");
        }

        try {
            $sql = "UPDATE iss_comments SET short_comment = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$short_comment, $id]);

            header("Location: issues_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error updating comment: " . $e->getMessage();
        }
    }

    // Add Comment Handler
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

    // Delete Comment Handler
    if (isset($_POST['delete_comment'])) {
        $id = $_POST['id'];

        // Check if the user is an admin or the author of the comment
        $stmt = $conn->prepare("SELECT per_id FROM iss_comments WHERE id = ?");
        $stmt->execute([$id]);
        $comment_owner = $stmt->fetchColumn();

        if ($_SESSION['admin'] !== 'Y' && $_SESSION['user_id'] !== $comment_owner) {
            die("Unauthorized action. You do not have permission to delete this comment.");
        }

        try {
            $sql = "DELETE FROM iss_comments WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);

            header("Location: issues_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error deleting comment: " . $e->getMessage();
        }
    }

    // Handle issue operations (Update, Delete, Create Issue)
    if (isset($_POST['update_issue'])) {
        $id = $_POST['id'];
        $short_description = trim($_POST['short_description']);
        $long_description = trim($_POST['long_description']);
        $open_date = $_POST['open_date'];
        $close_date = $_POST['close_date'];
        $priority = $_POST['priority'];
        $org = trim($_POST['org']);
        $project = trim($_POST['project']);

        // Preserve the existing per_id if not provided in the form
        $per_id = isset($_POST['per_id']) && !empty($_POST['per_id']) ? $_POST['per_id'] : null;
        if (!$per_id) {
            $stmt = $conn->prepare("SELECT per_id FROM iss_issues WHERE id = ?");
            $stmt->execute([$id]);
            $per_id = $stmt->fetchColumn();
        }

        // Check if the user is an admin or the owner of the issue
        if ($_SESSION['admin'] !== 'Y' && $_SESSION['user_id'] !== $per_id) {
            die("Unauthorized action. You do not have permission to update this issue.");
        }

        // Handle PDF upload
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
        } else {
            // Use existing attachment if no new file is uploaded
            $existing = $conn->prepare("SELECT pdf_attachment FROM iss_issues WHERE id = ?");
            $existing->execute([$id]);
            $attachmentPath = $existing->fetchColumn();
        }

        // Update the issue
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
        $per_id = $_SESSION['user_id']; // Set the creator's user ID

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
            $sql = "INSERT INTO iss_issues (short_description, long_description, priority, pdf_attachment, open_date, close_date, per_id) 
                    VALUES (:title, :description, :status, :pdf, :open_date, :close_date, :per_id)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':status' => $status,
                ':pdf' => $attachmentPath,
                ':open_date' => $open_date,
                ':close_date' => $close_date,
                ':per_id' => $per_id
            ]);

            header("Location: issues_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error creating issue: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_issue'])) {
        $id = $_POST['id'];

        // Preserve filter and sorting parameters
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'open';
        $name_filter = isset($_GET['name_filter']) ? $_GET['name_filter'] : '';
        $sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'open_date';
        $sort_order = isset($_GET['order']) ? $_GET['order'] : 'desc';

        // Check if the user is an admin or the owner of the issue
        $stmt = $conn->prepare("SELECT per_id FROM iss_issues WHERE id = ?");
        $stmt->execute([$id]);
        $issue_owner = $stmt->fetchColumn();

        if ($_SESSION['admin'] !== 'Y' && $_SESSION['user_id'] !== $issue_owner) {
            die("Unauthorized action. You do not have permission to delete this issue.");
        }

        // Delete the issue
        try {
            $sql = "DELETE FROM iss_issues WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);

            // Redirect back with preserved filters
            header("Location: issues_list.php?filter=$filter&name_filter=" . urlencode($name_filter) . "&sort=$sort_column&order=$sort_order");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error deleting issue: " . $e->getMessage();
        }
    }
}

// Fetch all issues with filtering and sorting
$name_filter = isset($_GET['name_filter']) ? trim($_GET['name_filter']) : '';
$filter = isset($_GET['filter']) && $_GET['filter'] === 'all' ? 'all' : 'open';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'open_date';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$sql = "SELECT i.*, CONCAT(p.fname, ' ', p.lname) AS person_name 
        FROM iss_issues i 
        LEFT JOIN iss_persons p ON i.per_id = p.id 
        WHERE (:filter = 'all' OR i.close_date IS NULL) 
        AND (:name_filter = '' OR CONCAT(p.fname, ' ', p.lname) LIKE :name_filter_wildcard)
        ORDER BY $sort_column $sort_order";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':filter' => $filter,
    ':name_filter' => $name_filter,
    ':name_filter_wildcard' => "%$name_filter%"
]);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <!-- Page metadata and custom styles -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List - DSR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar, .table-dark {
            background-color: #343a40 !important;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bd2130;
            border-color: #a71d2a;
        }
        .modal-header {
            background-color: #343a40;
            color: white;
        }
        .modal-footer {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <!-- Main container -->
    <div class="container mt-3">
        <!-- Row with "Logout", "Issues List", "Go to Persons List", and "Create New Issue" -->
        <div class="row mt-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <!-- Logout Button -->
                <form method="POST" action="logout.php" class="me-auto">
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>

                <!-- Issues List Header -->
                <h2 class="text-center flex-grow-1 m-0">Issues List</h2>

                <!-- Buttons on the Right -->
                <div class="d-flex flex-wrap gap-2">
                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === 'Y'): ?>
                        <a href="persons_list.php" class="btn btn-secondary">Go to Persons List</a>
                    <?php endif; ?>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createIssueModal">+ Create New Issue</button>
                </div>
            </div>
        </div>

        <!-- Filter and Sort Controls -->
        <div class="row mt-3">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <h3>All Issues</h3>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <form method="GET" class="d-flex align-items-center">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
                        <input type="text" name="name_filter" class="form-control me-2" placeholder="Filter by name" value="<?= htmlspecialchars($name_filter); ?>">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                    <a href="?filter=open&name_filter=<?= urlencode($name_filter); ?>&sort=<?= $sort_column; ?>&order=<?= $sort_order; ?>" class="btn btn-outline-primary <?= $filter === 'open' ? 'active' : ''; ?>">Open Issues</a>
                    <a href="?filter=all&name_filter=<?= urlencode($name_filter); ?>&sort=<?= $sort_column; ?>&order=<?= $sort_order; ?>" class="btn btn-outline-secondary <?= $filter === 'all' ? 'active' : ''; ?>">All Issues</a>
                </div>
            </div>
        </div>

        <!-- Responsive Table -->
        <div class="table-responsive mt-2">
            <table class="table table-striped table-sm">
                <thead class="table-dark">
                    <tr>
                        <th><a href="?filter=<?= $filter; ?>&sort=id&order=<?= $sort_column === 'id' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">ID</a></th>
                        <th><a href="?filter=<?= $filter; ?>&sort=short_description&order=<?= $sort_column === 'short_description' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Short Description</a></th>
                        <th><a href="?filter=<?= $filter; ?>&sort=open_date&order=<?= $sort_column === 'open_date' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Open Date</a></th>
                        <th><a href="?filter=<?= $filter; ?>&sort=close_date&order=<?= $sort_column === 'close_date' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Close Date</a></th>
                        <th><a href="?filter=<?= $filter; ?>&sort=priority&order=<?= $sort_column === 'priority' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Priority</a></th>
                        <th><a href="?filter=<?= $filter; ?>&sort=person_name&order=<?= $sort_column === 'person_name' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Person</a></th>
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
                            <td><?= htmlspecialchars($issue['person_name']); ?></td>
                            <td>
                                <!-- Read Button -->
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>

                                <!-- Update Button -->
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id']; ?>" <?= (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'Y') && $_SESSION['user_id'] !== $issue['per_id'] ? 'disabled' : ''; ?>>U</button>

                                <!-- Delete Button -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                    <button type="submit" name="delete_issue" class="btn btn-danger btn-sm" <?= (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'Y') && $_SESSION['user_id'] !== $issue['per_id'] ? 'disabled' : ''; ?>>D</button>
                                </form>

                                <?php if (!empty($issue['pdf_attachment'])): ?>
                                    <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">View PDF</a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Read Issue Modal -->
                        <div class="modal fade" id="readIssue<?= $issue['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Issue Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>ID:</strong> <?= htmlspecialchars($issue['id']); ?></p>
                                        <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']); ?></p>
                                        <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']); ?></p>
                                        <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']); ?></p>
                                        <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']); ?></p>
                                        <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']); ?></p>
                                        <p><strong>Created By (Person ID):</strong> <?= htmlspecialchars($issue['per_id']); ?></p>
                                        <h6>Comments:</h6>
                                        <hr>
                                        <?php
                                        // Fetch comments for the current issue
                                        $stmt = $conn->prepare("SELECT c.*, CONCAT(p.fname, ' ', p.lname) AS author_name 
                                                                FROM iss_comments c 
                                                                LEFT JOIN iss_persons p ON c.per_id = p.id 
                                                                WHERE c.iss_id = ? 
                                                                ORDER BY c.id ASC");
                                        $stmt->execute([$issue['id']]);
                                        $issue_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (!empty($issue_comments)) {
                                            foreach ($issue_comments as $comment): ?>
                                                <div class="mb-2">
                                                    <p><strong>Comment ID:</strong> <?= htmlspecialchars($comment['id']); ?></p>
                                                    <p><strong>Comment:</strong> <?= htmlspecialchars($comment['short_comment']); ?></p>
                                                    <p><strong>Author:</strong> <?= htmlspecialchars($comment['author_name'] ?? 'Unknown'); ?> (ID: <?= htmlspecialchars($comment['per_id']); ?>)</p>
                                                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === 'Y' || $_SESSION['user_id'] === $comment['per_id']): ?>
                                                        <!-- Update Button -->
                                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateComment<?= $comment['id']; ?>">Update</button>

                                                        <!-- Delete Button -->
                                                        <form method="POST" style="display:inline;" class="ms-2">
                                                            <input type="hidden" name="id" value="<?= htmlspecialchars($comment['id']); ?>">
                                                            <button type="submit" name="delete_comment" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <hr>
                                            <?php endforeach;
                                        } else {
                                            echo '<p>No comments available for this issue.</p>';
                                        }
                                        ?>
                                        <h6>Add a Comment:</h6>
                                        <hr>
                                        <form method="POST">
                                            <input type="hidden" name="issue_id" value="<?= $issue['id']; ?>">
                                            <input type="hidden" name="author" value="<?= $_SESSION['user_id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Comment:</label>
                                                <textarea name="comment" class="form-control" required></textarea>
                                            </div>
                                            <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Update Issue Modal -->
                        <div class="modal fade" id="updateIssue<?= $issue['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Issue</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="modal-body">
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
                                                <label class="form-label">Open Date:</label>
                                                <input type="date" name="open_date" class="form-control" value="<?= htmlspecialchars($issue['open_date']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Close Date:</label>
                                                <input type="date" name="close_date" class="form-control" value="<?= htmlspecialchars($issue['close_date']); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Attach PDF (Max 2MB):</label>
                                                <input type="file" name="pdf_attachment" class="form-control" accept="application/pdf">
                                                <?php if (!empty($issue['pdf_attachment'])): ?>
                                                    <small class="text-muted">Current File: <a href="<?= htmlspecialchars($issue['pdf_attachment']); ?>" target="_blank">View PDF</a></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="update_issue" class="btn btn-primary">Update Issue</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create New Issue Modal -->
    <div class="modal fade" id="createIssueModal" tabindex="-1" aria-labelledby="createIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createIssueModalLabel">Create New Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
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
                            <label class="form-label">Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="Open">Open</option>
                                <option value="Closed">Closed</option>
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

    <?php foreach ($comments as $comment): ?>
        <div class="modal fade" id="updateComment<?= $comment['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Comment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($comment['id']); ?>">
                            <div class="mb-3">
                                <label class="form-label">Comment:</label>
                                <textarea name="short_comment" class="form-control" required><?= htmlspecialchars($comment['short_comment']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_comment" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php Database::disconnect(); ?>