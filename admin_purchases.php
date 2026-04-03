<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}

/* Check if admin */
$userEmail = $_SESSION['email'];
$userStmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
$userStmt->bind_param("s", $userEmail);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    header("location: user_page.php");
    exit();
}

/* Fetch profile image */
$profileImage = "uploads/default_user.png";
$imgStmt = $conn->prepare("SELECT profile_pic FROM users WHERE email = ?");
$imgStmt->bind_param("s", $userEmail);
$imgStmt->execute();
$imgResult = $imgStmt->get_result();
$imgRow = $imgResult->fetch_assoc();
if ($imgRow && !empty($imgRow['profile_pic'])) {
    $profileImage = $imgRow['profile_pic'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <title>All User Purchases</title>
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
                    <a href="admin_page.php" class="profile-item">Manage Mobiles</a>
                    <form action="logout.php" method="post" class="profile-item logout-form" onclick="this.submit()" role="button" tabindex="0">
                        <button type="submit" class="logout-link">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mobile_data_h2">All User Purchases</h2>

    <table class="data_table">
        <tr>
            <th>Purchase ID</th>
            <th>User</th>
            <th>Mobile</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Date</th>
        </tr>

        <?php
        $allPurchasesStmt = $conn->prepare(
            "SELECT p.id, p.quantity, p.price, p.total, p.purchase_date, 
                    u.name AS user_name, u.email AS user_email,
                    m.brand, m.model
             FROM purchases p
             LEFT JOIN users u ON p.user_id = u.id
             LEFT JOIN mobiles m ON p.mobile_id = m.id
             ORDER BY p.purchase_date DESC"
        );
        $allPurchasesStmt->execute();
        $allPurchasesResult = $allPurchasesStmt->get_result();

        if ($allPurchasesResult->num_rows === 0) {
            echo "<tr><td colspan='7' style='text-align: center;'>No purchases yet.</td></tr>";
        } else {
            while ($purchase = $allPurchasesResult->fetch_assoc()) {
                $userName = htmlspecialchars($purchase['user_name'] ?? 'Unknown');
                $userEmail = htmlspecialchars($purchase['user_email'] ?? '');
                $brand = htmlspecialchars($purchase['brand'] ?? 'Unknown');
                $model = htmlspecialchars($purchase['model'] ?? 'Unknown');
                $unitPrice = number_format((float)$purchase['price'], 2);
                $total = number_format((float)$purchase['total'], 2);
                $qty = intval($purchase['quantity']);
                $date = htmlspecialchars($purchase['purchase_date']);

                echo "<tr>
                        <td>{$purchase['id']}</td>
                        <td>{$userName} ({$userEmail})</td>
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

    <div class='aggregate-stats'>
        <?php
        $statsStmt = $conn->prepare(
            "SELECT 
                (SELECT COUNT(*) FROM purchases) AS total_purchases,
                (SELECT IFNULL(SUM(total), 0) FROM purchases) AS total_revenue,
                (SELECT IFNULL(AVG(total), 0) FROM purchases) AS avg_purchase_value"
        );
        $statsStmt->execute();
        $statsResult = $statsStmt->get_result();
        $stats = $statsResult->fetch_assoc();

        echo "<div><p><strong>Total Purchases:</strong> " . intval($stats['total_purchases']) . "</p>"."</div>";
        echo "<div><p><strong>Total Revenue:</strong> ₹" . number_format((float)$stats['total_revenue'], 2) . "</p>"."</div>";
        echo "<div><p><strong>Average Purchase Value:</strong> ₹" . number_format((float)$stats['avg_purchase_value'], 2) . "</p>"."</div>";
        ?>

    </div>

    <script src="script.js"></script>
</body>
</html>
