<?php
include '../database/database.php';  // Include the database connection

$conn = Database::connect();  // Establish the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $admin = isset($_POST['admin']) ? $_POST['admin'] : 'N';

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
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form method="POST">
        <label>First Name:</label>
        <input type="text" name="fname" required><br>

        <label>Last Name:</label>
        <input type="text" name="lname" required><br>

        <label>Mobile:</label>
        <input type="text" name="mobile" required><br>

        <label>Email (Username):</label>
        <input type="email" name="email" required><br>

        <label>Password:</label>
        <input type="password" name="password" required><br>

        <label>Admin (Y/N):</label>
        <input type="text" name="admin" value="N"><br>

        <button type="submit">Register</button>
    </form>
    <a href="login.php">Already have an account? Login here</a>
</body>
</html>
