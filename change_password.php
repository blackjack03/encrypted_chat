<?php
include 'database.php';
include 'crypt.php';

session_start();

if(!isset($_SESSION['logged'])) {
    header("Location: dashboard.php");
}

$err = null;
$succ = false;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    die("<p style='color: red; font-weight: bold;'>Error during open DB!</p><br>");
}

if($_POST) {
    if(password_verify($_POST['oldPass'], $DB[$_SESSION['user_id']]["password"])) {
        $tk = decrypt_AES256($DB[$_SESSION['user_id']]["token"], $_POST['oldPass']);
        if($_POST['newPass'] != $_POST['rnewPass']) {
            $err = '<p style="color: red; font-weight: bold; font-size: 18px;">Passwords doesn\'t match!</p>';
        } else {
            $DB[$_SESSION['user_id']]["password"] = password_hash($_POST['newPass'], PASSWORD_BCRYPT);
            $DB[$_SESSION['user_id']]["token"] = encrypt_AES256($tk, $_POST['newPass']);
            if(saveDB() === false) { // Save new user in db
                die("Error during change password!");
            }
            $succ = true;
        }
    } else {
        $err = '<p style="color: red; font-weight: bold; font-size: 18px;">Wrong password!</p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: arial;
        }
    </style>
     <style>
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            margin: 0 auto;
            box-shadow: 0px 0px 12px blueviolet;
        }

        label {
            font-weight: bold;
            color: blueviolet;
        }

        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid blueviolet;
            background-color: #2c2c2c;
            color: white;
            font-size: 18px;
            outline: none;
            transition: 0.2s;
        }

        input[type="submit"] {
            background-color: blueviolet;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }

        input[type="submit"]:hover {
            background-color: darkviolet;
        }

        input[type="submit"]:active {
            transform: scale(0.92);
        }

        input[type="text"]:focus, input[type="password"]:focus {
            box-shadow: 0px 0px 3px blueviolet;
        }

        .back {
        	position: fixed;
            bottom: 5%;
            right: 3%;
            width: 100px;
            height: 45px;
            cursor: pointer;
            border: 1px solid green;
            background: green;
            font-size: 18px;
            font-family: arial;
            font-weight: bold;
            color: white;
            border-radius: 8px;
            box-shadow: 0px 0px 2px green;
            transition: 0.2s;
        }

        .back:hover {
        	box-shadow: 0px 0px 12px green;
        }

        .back:active {
        	transform: scale(0.93);
        }
    </style>
</head>
<body>
    <?php
        if($err !== null) {
            echo $err;
        } else if($succ) {
            echo '<p style="color: green; font-weight: bold;">Password changed successfully!</p>';
        }
    ?>
    <h1 class="title">CHANGE PASSWORD</h1>
    <form action="" method="post">
        <label for="oldPass">Old Password</label>
        <br>
        <input type="password" id="oldPass" name="oldPass" required>
        <br>
        <br>
        <label for="newPass">New Password</label>
        <br>
        <input type="password" id="newPass" name="newPass" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{15,}" title="Password must be at least 15 characters long, and contain at least one uppercase letter, one lowercase letter, one number, and one special character." autocomplete="off" required>
        <br>
        <br>
        <label for="rnewPass">Repeat New Password</label>
        <br>
        <input type="password" id="rnewPass" name="rnewPass" required>
        <br>
        <br>
        <input type="submit" value="CHANGE PASSWORD">
    </form>
    <a href="dashboard.php"><button class="back">&lt; BACK</button></a>
</body>
</html>
