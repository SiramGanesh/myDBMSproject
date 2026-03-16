<?php
session_start();
require_once 'config.php';

/*  REGISTER  */
if (isset($_POST['register'])) {

    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $fpassword = "";
    $pattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z0-9@$!%*?&]{8,}$/";
    $role = $_POST['role'];

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
    } else {
        if (preg_match($pattern, $password)) {
            $fpassword = $password;
        }
        $fpassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, email, password, role)
                      VALUES ('$name', '$email', '$fpassword', '$role')");
        $_SESSION['active_form'] = 'login'; // go back to login after successful registeration
    }

    header("Location: index.php");
    exit();
}

/*  LOGIN  */
if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];

            if ($user['role'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: user_page.php");
            }
            exit();

        } else {
            $_SESSION['login_error'] = 'Incorrect password';
        }
    } else {
        $_SESSION['login_error'] = 'Email not found';
    }

    $_SESSION['active_form'] = 'login'; //goes back to login if login attempt fails
    header("Location: index.php");
    exit();
}
?>