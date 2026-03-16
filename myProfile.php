<?php 
session_start();
require_once "config.php";

if (!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="myProfile.css">
    <title>My Profile</title>
</head>
<body>
    <div class='profile-container'>
        <h1>My Profile</h1>
        <img src="user.jpeg" alt="Admin" class="profile-img">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
    </div>
</body>
</html>