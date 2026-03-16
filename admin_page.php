<?php 
session_start();
require_once "config.php";

if (!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}

/* HANDLE INSERT FIRST */
if (isset($_POST['add_mobile'])) {
    
    $brand = strtoupper($_POST['brand']);
    $model = strtoupper($_POST['model']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    /* STEP 1: Check if mobile already exists */
    $checkStmt = $conn->prepare(
        "SELECT quantity FROM mobiles WHERE brand = ? AND model = ?"
    );
    $checkStmt->bind_param("ss", $brand, $model);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Mobile exists → update quantity
        $updateStmt = $conn->prepare(
            "UPDATE mobiles 
             SET quantity = quantity + ?, price = ?
             WHERE brand = ? AND model = ?"
        );
        $updateStmt->bind_param("idss", $quantity, $price, $brand, $model);

        if ($updateStmt->execute()) {
            header("Location: admin_page.php?updated=1");
            exit();
        } else {
            header("Location: admin_page.php?error=1");
            exit();
        }

    } else {
        // Mobile does not exist → insert new
        $insertStmt = $conn->prepare(
            "INSERT INTO mobiles (brand, model, price, quantity) 
             VALUES (?, ?, ?, ?)"
        );
        $insertStmt->bind_param("ssdi", $brand, $model, $price, $quantity);

        if ($insertStmt->execute()) {
            header("Location: admin_page.php?success=1");
            exit();
        } else {
            header("Location: admin_page.php?error=1");
            exit();
        }
    }
}

if (isset($_POST['update_mobile'])) {
    $id = intval($_POST['id']);
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

    $stmt = $conn->prepare("UPDATE mobiles SET brand = ?, model = ?, price = ?, quantity = ? WHERE id = ?");
    $stmt->bind_param("ssdii", $brand, $model, $price, $quantity, $id);

    if ($stmt->execute()) {
        header("Location: admin_page.php?updated=1");
        exit();
    } else {
        header("Location: admin_page.php?update_error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <title>Admin page</title>
</head>
<body style="background: #fff;">
    
<div class="header">
    <div class="header-left">
        <h1>Admin Dashboard</h1>
    </div>

    <div class="header-right">
        <div class="profile-menu" id="profileMenu">
            <button type="button" class="profile-toggle" aria-expanded="false">
                <img src="user.jpeg" alt="Admin" class="profile-img">
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


<div class="admin-box">

    <form class="admin-form" method="POST">
        <table class="form-table">
            <tr>
                <td><label>Mobile Brand:</label></td>
                <td><input type="text" name="brand" required></td>
            </tr>
            <tr>
                <td><label>Mobile Name:</label></td>
                <td><input type="text" name="model" required></td>
            </tr>
            <tr>
                <td><label>Price:</label></td>
                <td><input type="number" name="price" required></td>
            </tr>
            <tr>
                <td><label>Quantity Available:</label></td>
                <td><input type="number" name="quantity" required></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;">
                    <button type="submit" name="add_mobile">Add Mobile</button>
                </td>
            </tr>
        </table>
    </form>

    <h2 class="mobile_data_h2">Available Mobiles</h2>

    <table class="data_table">
        <tr>
            <th>Brand</th>
            <th>Model</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Actions</th>
        </tr>

        <?php
        $result = $conn->query("SELECT * FROM mobiles");

        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $brand = htmlspecialchars($row['brand']);
            $model = htmlspecialchars($row['model']);
            $price = htmlspecialchars($row['price']);
            $quantity = htmlspecialchars($row['quantity']);
            echo "<tr data-id='{$id}'>
                    <td>{$brand}</td>
                    <td>{$model}</td>
                    <td>₹{$price}</td>
                    <td>{$quantity}</td>
                    <td><button type='button' class='action-btn edit-btn'>Edit</button></td>
                  </tr>";
        }
        ?>

    </table>

        <!-- SUCCESS / ERROR MESSAGE -->
    <?php if (isset($_GET['success'])) { ?>
        <p  class="success-message">Mobile added successfully!</p>
    <?php } elseif (isset($_GET['error'])) { ?>
        <p class="failed-message" style="color:red;">Error adding mobile</p>
    <?php } ?>

    <?php if (isset($_GET['updated'])) { ?>
        <p  class="success-message">Mobile updated successfully!</p>
    <?php } elseif (isset($_GET['update_error'])) { ?>
        <p class="failed-message" style="color:red;">Error updating mobile</p>
    <?php } ?>

</div>
<script src="script.js"></script>
</body>
</html>