<?php
require '../database/database.php'; // Database connection

$conn = Database::connect();  // Establish the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];

    $attachmentPath = null;
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileSize = $_FILES['pdf_attachment']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            die("Only PDF files are allowed.");
        }

        if ($fileSize > 2 * 1024 * 1024) {
            die("File size exceeds 2MB limit.");
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $attachmentPath = $destPath;
        } else {
            die("Error moving the uploaded file.");
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO iss_issues (short_description, long_description, priority, pdf_attachment, open_date, close_date) VALUES (:title, :description, :status, :pdf, :open_date, :close_date)");
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
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Issue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 text-center">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createIssueModal">+ Create New Issue</button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="createIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create a New Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
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
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Create Issue</button>
                            <a href="issues_list.php" class="btn btn-secondary">Back to Issues</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>