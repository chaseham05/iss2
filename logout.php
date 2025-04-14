<?php
/**
 * Author: Chase Hamilton, Cis355
 * Description: This file handles user logout by destroying the session and redirecting to the login page.
 */

// Start the session
session_start();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
?>