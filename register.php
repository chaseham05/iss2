<?php
include '../database/database.php';  // Include the database connection

$conn = Database::connect();  // Establish the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $admin = isset($_POST['admin']) && $_POST['admin'] === 'Y' ? 'Y' : 'N';

    // Prepare the statement to insert user data
    $stmt = $conn->prepare("INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, admin) VALUES (:fname, :lname, :mobile, :email, :pwd_hash, :admin)");

    try {
        $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':mobile' => $mobile,
            ':email' => $email,
            ':pwd_hash' => $password,
            ':admin' => $admin
        ]);
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Register</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="fname" class="form-label">First Name:</label>
                                <input type="text" name="fname" id="fname" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="lname" class="form-label">Last Name:</label>
                                <input type="text" name="lname" id="lname" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="mobile" class="form-label">Mobile:</label>
                                <input type="text" name="mobile" id="mobile" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (Username):</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password:</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="admin" class="form-label">Admin (Y/N):</label>
                                <select name="admin" id="admin" class="form-select">
                                    <option value="N" selected>No</option>
                                    <option value="Y">Yes</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php">Already have an account? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
