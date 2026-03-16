<?php 
    session_start();
    require_once "config.php";
    if(!isset($_SESSION['email'])){
        header("location: index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="user.css">
    <title>User page</title>
</head>
<body style="background: #fff;">

    <div class="header">
        <div class="header-left">
            <h1>User Dashboard</h1>
        </div>

        <div class="header-right">
            <img src="user.jpeg" alt="Admin" class="profile-img">
            <span class="username"><?= htmlspecialchars($_SESSION['name']); ?></span>
            <form action="logout.php" method="post">
                <button class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

        <h2 class="mobile_data_h2">Available Mobiles</h2>

    <table class="data_table">
        <tr>
            <th>Brand</th>
            <th>Model</th>
            <th>Price</th>
            <th>Quantity</th>
        </tr>

        <?php
        $result = $conn->query("SELECT * FROM mobiles");

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['brand']}</td>
                    <td>{$row['model']}</td>
                    <td>₹{$row['price']}</td>
                    <td>{$row['quantity']}</td>
                  </tr>";
        }
        ?>

    </table>

</body>
</html>