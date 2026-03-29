<?php 
    session_start();
    require_once "config.php";
    if(!isset($_SESSION['email'])){
        header("location: index.php");
        exit();
    }

    /* ---------- DEFAULT IMAGE ---------- */

    $profileImage = "uploads/default_user.png";

    if(!empty($user['profile_pic'])){
        $profileImage = $user['profile_pic'];
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
            <h1>Admin Dashboard</h1>
        </div>

        <div class="header-right">
            <div class="profile-menu" id="profileMenu">
                <button type="button" class="profile-toggle" aria-expanded="false">
                    <img src="<?php echo $profileImage; ?>" alt="Admin" class="profile-img">
                    <span class="username"><?= htmlspecialchars($_SESSION['name']); ?></span>
                </button>

                <div class="profile-dropdown" aria-hidden="true">
                    <a href="myProfile.php" class="profile-item">My Profile</a>
                    <a href="#" class="profile-item">Purchases</a>
                    <form action="logout.php" method="post" class="profile-item logout-form" onclick="this.submit()" role="button" tabindex="0">
                        <button type="submit" class="logout-link">Logout</button>
                    </form>
                </div>
            </div>
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
    <script src="script.js"></script>
</body>
</html>