<?php 

session_start();
require_once "config.php";

if (!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}

$email = $_SESSION['email'];

/* ---------- HANDLE IMAGE UPLOAD ---------- */

if(isset($_POST['upload'])){

    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){

        $fileName = $_FILES['profile_image']['name'];
        $tmpName = $_FILES['profile_image']['tmp_name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if(in_array($ext,$allowed)){

            $newName = "user_".time().".".$ext;
            $uploadPath = "uploads/".$newName;

            move_uploaded_file($tmpName,$uploadPath);

            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE email=?");
            $stmt->bind_param("ss",$uploadPath,$email);
            $stmt->execute();

        } else if ($_FILES['profile_image']['error'] == 1 && $_FILES['profile_image']['error'] == 2){
            echo "Image was too big.";

        } else if ($_FILES['profile_image']['error'] == 3){
	    echo "File partially uploaded.";

        } else if ($_FILES['profile_image']['error'] == 4){
            echo "No file uploaded.";

        } else {
            echo "Only image files allowed.";
	    }
    }

}

/* ---------- FETCH USER DATA ---------- */

$stmt = $conn->prepare("SELECT name,email,profile_pic FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
<link rel="stylesheet" href="myProfile.css">
<title>My Profile</title>
</head>

<body>

    <button onclick="history.back()" class="back-btn">← Back</button>

    <div class="profile-container">

        <h1>My Profile</h1>

        <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img">

        <form method="POST" enctype="multipart/form-data">

        <input type="file" name="profile_image" class="file-input" required>

        <button type="submit" name="upload" class="upload-btn">
        Upload Photo
        </button>

        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>

    </div>

</body>
</html>