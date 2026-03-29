<?php 
    session_start();
    require_once "config.php";

    if(!isset($_SESSION['email'])){
        header("location: index.php");
        exit();
    }

    $userEmail = $_SESSION['email'];
    $profileImage = "uploads/default_user.png";
    $message = '';
    $error = '';

    /* Fetch current user */
    $userStmt = $conn->prepare("SELECT id, profile_pic FROM users WHERE email = ?");
    $userStmt->bind_param("s", $userEmail);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();

    if ($user && !empty($user['profile_pic'])) {
        $profileImage = $user['profile_pic'];
    }

    /* Ensure purchases table exists */
    $conn->query(
        "CREATE TABLE IF NOT EXISTS purchases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            mobile_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            purchase_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (mobile_id)
        ) ENGINE=InnoDB"
    );

    /* Handle buy action */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_mobile'])) {
        $mobileId = intval($_POST['mobile_id']);
        $buyQuantity = max(1, intval($_POST['buy_quantity'] ?? 1));

        $checkStmt = $conn->prepare("SELECT brand, model, price, quantity FROM mobiles WHERE id = ?");
        $checkStmt->bind_param("i", $mobileId);
        $checkStmt->execute();
        $mobileResult = $checkStmt->get_result();

        if ($mobileResult->num_rows === 0) {
            $error = 'Selected mobile not found.';
        } else {
            $mobile = $mobileResult->fetch_assoc();

            if ($mobile['quantity'] < $buyQuantity) {
                $error = 'Not enough stock available.';
            } else {
                $total = round($mobile['price'] * $buyQuantity, 2);

                $conn->begin_transaction();

                $updateStmt = $conn->prepare("UPDATE mobiles SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                $updateStmt->bind_param("iii", $buyQuantity, $mobileId, $buyQuantity);
                $updateStmt->execute();

                if ($updateStmt->affected_rows === 0) {
                    $conn->rollback();
                    $error = 'Failed to update stock. Please try again.';
                } else {
                    $insertHist = $conn->prepare(
                        "INSERT INTO purchases (user_id, mobile_id, quantity, price, total, purchase_date)
                         VALUES (?, ?, ?, ?, ?, NOW())"
                    );
                    $insertHist->bind_param("iiiid", $user['id'], $mobileId, $buyQuantity, $mobile['price'], $total);

                    if ($insertHist->execute()) {
                        $conn->commit();
                        $message = "Successfully purchased {$buyQuantity} unit(s) of {$mobile['brand']} {$mobile['model']} for ₹{$total}.";
                    } else {
                        $conn->rollback();
                        $error = 'Unable to save purchase history. Please contact support.';
                    }
                }
            }
        }
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
                    <form action="logout.php" method="post" class="profile-item logout-form" onclick="this.submit()" role="button" tabindex="0">
                        <button type="submit" class="logout-link">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

        <h2 class="mobile_data_h2">Available Mobiles</h2>

    <?php if ($message): ?>
        <p class="success-message" style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php elseif ($error): ?>
        <p class="failed-message" style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <table class="data_table">
        <tr>
            <th>Brand</th>
            <th>Model</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Buy</th>
        </tr>

        <?php
        $result = $conn->query("SELECT * FROM mobiles");

        while ($row = $result->fetch_assoc()) {
            $mobileId = intval($row['id']);
            $brand = htmlspecialchars($row['brand']);
            $model = htmlspecialchars($row['model']);
            $price = number_format((float)$row['price'], 2);
            $quantity = intval($row['quantity']);

            echo "<tr>
                    <td>{$brand}</td>
                    <td>{$model}</td>
                    <td>₹{$price}</td>
                    <td>{$quantity}</td>
                    <td>
                        <form method='POST' class='buy-form'>
                            <input type='hidden' name='mobile_id' value='{$mobileId}'>
                            <input type='number' name='buy_quantity' value='1' min='1' max='{$quantity}' style='width:60px;' required>
                            <button type='submit' name='buy_mobile'" . ($quantity > 0 ? "" : " disabled") . ">Buy</button>
                        </form>
                    </td>
                  </tr>";
        }
        ?>

    </table>

    <h2 id="purchase_history" class="mobile_data_h2">Purchase History</h2>

    <table class="data_table">
        <tr>
            <th>Purchase ID</th>
            <th>Mobile</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Date</th>
        </tr>

        <?php
        $historyStmt = $conn->prepare(
            "SELECT p.id, p.quantity, p.price, p.total, p.purchase_date, m.brand, m.model
             FROM purchases p
             LEFT JOIN mobiles m ON p.mobile_id = m.id
             WHERE p.user_id = ?
             ORDER BY p.purchase_date DESC"
        );
        $historyStmt->bind_param("i", $user['id']);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();

        if ($historyResult->num_rows === 0) {
            echo "<tr><td colspan='6' style='text-align: center;'>No purchases yet.</td></tr>";
        } else {
            while ($order = $historyResult->fetch_assoc()) {
                $brand = htmlspecialchars($order['brand'] ?? 'Unknown');
                $model = htmlspecialchars($order['model'] ?? 'Unknown');
                $unitPrice = number_format((float)$order['price'], 2);
                $total = number_format((float)$order['total'], 2);
                $qty = intval($order['quantity']);
                $date = htmlspecialchars($order['purchase_date']);

                echo "<tr>
                        <td>{$order['id']}</td>
                        <td>{$brand} {$model}</td>
                        <td>{$qty}</td>
                        <td>₹{$unitPrice}</td>
                        <td>₹{$total}</td>
                        <td>{$date}</td>
                      </tr>";
            }
        }
        ?>

    </table>

    <script src="script.js"></script>
</body>
</html>