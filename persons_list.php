<?php
session_start();
include '../database/database.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    session_destroy(); // Destroy the session if not logged in
    header("Location: login.php");
    exit();
}

// Check if the user is an admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'Y') {
    header("Location: issues_list.php"); // Redirect non-admin users to the issues list
    exit();
}

$conn = Database::connect(); // Establish the database connection
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Person Handler
    if (isset($_POST['add_person'])) {
        $fname = trim($_POST['fname']);
        $lname = trim($_POST['lname']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $admin = isset($_POST['admin']) ? 'Y' : 'N';

        try {
            $sql = "INSERT INTO iss_persons (fname, lname, email, mobile, admin) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$fname, $lname, $email, $mobile, $admin]);

            header("Location: persons_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error adding person: " . $e->getMessage();
        }
    }

    // Update Person Handler
    if (isset($_POST['update_person'])) {
        $id = $_POST['id'];
        $fname = trim($_POST['fname']);
        $lname = trim($_POST['lname']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $admin = isset($_POST['admin']) ? 'Y' : 'N';

        try {
            $sql = "UPDATE iss_persons SET fname = ?, lname = ?, email = ?, mobile = ?, admin = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$fname, $lname, $email, $mobile, $admin, $id]);

            header("Location: persons_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error updating person: " . $e->getMessage();
        }
    }

    // Delete Person Handler
    if (isset($_POST['delete_person'])) {
        $id = $_POST['id'];

        try {
            $sql = "DELETE FROM iss_persons WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);

            header("Location: persons_list.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error deleting person: " . $e->getMessage();
        }
    }
}

// Fetch all persons with sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'lname';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$sql = "SELECT * FROM iss_persons ORDER BY $sort_column $sort_order";
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
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPersonModal">+ Add Person</button>
            <form method="POST" action="logout.php">
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th><a href="?sort=id&order=<?= $sort_column === 'id' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">ID</a></th>
                    <th><a href="?sort=fname&order=<?= $sort_column === 'fname' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">First Name</a></th>
                    <th><a href="?sort=lname&order=<?= $sort_column === 'lname' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Last Name</a></th>
                    <th><a href="?sort=email&order=<?= $sort_column === 'email' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Email</a></th>
                    <th><a href="?sort=mobile&order=<?= $sort_column === 'mobile' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Mobile</a></th>
                    <th><a href="?sort=admin&order=<?= $sort_column === 'admin' && $sort_order === 'ASC' ? 'desc' : 'asc'; ?>">Admin</a></th>
                    <th>Actions</th>
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
                        <td>
                            <!-- Update Button -->
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updatePerson<?= $person['id']; ?>">Update</button>

                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($person['id']); ?>">
                                <button type="submit" name="delete_person" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Person Modal -->
    <div class="modal fade" id="addPersonModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Person</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name:</label>
                            <input type="text" name="fname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name:</label>
                            <input type="text" name="lname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mobile:</label>
                            <input type="text" name="mobile" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="admin" class="form-check-input" id="adminCheck">
                            <label class="form-check-label" for="adminCheck">Admin</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_person" class="btn btn-primary">Add Person</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php foreach ($persons as $person): ?>
        <div class="modal fade" id="updatePerson<?= $person['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Person</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($person['id']); ?>">
                            <div class="mb-3">
                                <label class="form-label">First Name:</label>
                                <input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($person['fname']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name:</label>
                                <input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($person['lname']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email:</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($person['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mobile:</label>
                                <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($person['mobile']); ?>" required>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="admin" class="form-check-input" id="adminCheck<?= $person['id']; ?>" <?= $person['admin'] === 'Y' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="adminCheck<?= $person['id']; ?>">Admin</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_person" class="btn btn-primary">Update Person</button>
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
