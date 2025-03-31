<?php
// Configuration
$host = 'localhost';
$user = 'root';
$pass = ''; 
$dbname = 'cis355';

// SQL files to run
$sqlFiles = [
    'iss_persons.sql',
    'iss_issues.sql',
    'iss_comments.sql'
];

// Step 1: Connect to MySQL server
try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 2: Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✅ Database '$dbname' created or already exists.<br>";

    // Step 3: Use the new database
    $pdo->exec("USE `$dbname`");

    // Step 4: Run each SQL file
    foreach ($sqlFiles as $file) {
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            $pdo->exec($sql);
            echo "✅ Executed SQL from file: <strong>$file</strong><br>";
        } else {
            echo "⚠️ File not found: <strong>$file</strong><br>";
        }
    }

    echo "<br>🎉 All done! Your database is ready.";

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
