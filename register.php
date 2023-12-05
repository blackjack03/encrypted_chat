<?php
include 'database.php';
include 'crypt.php';

session_start();

if(isset($_SESSION['logged'])) {
    header("Location: dashboard.php");
}

function select_Timezone($selected = '') {

	// Create a list of timezone
	$OptionsArray = timezone_identifiers_list();
	$select= '<select name="SelectContacts" id="SelectContacts">
					<option value="" disabled selected>Please Select Timezone</option>';

	foreach ($OptionsArray as $key => $row) {
		$select .= '<option value="' . $row . '"';
		$select .= ($row == $selected ? " selected" : "");
		$select .= '>' . $row . '</option>';
	}

	$select .= '</select>';
	return $select;
}


$DB = null;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    die("<p style='color: red; font-weight: bold;'>Error during open DB!</p><br>");
}

if($_POST && isset($DB)) {
    $check = true;

    if(isset($DB[md5($_POST['username'])])) {
        echo "<p style='color: red;'>Username was already taken!</p>";
        $check = false;
    }

    if($_POST['password'] != $_POST['rp_password']) {
        echo "<p style='color: red;'>Passwords don't match!</p>";
        $check = false;
    }

    if(empty($_POST['SelectContacts'])) {
        echo "<p style='color: red;'>Select a valid timezone!</p>";
        $check = false;
    }    

    if($check) {
        // Create new User
        $userName = trim($_POST['username']);
        $hash_password = password_hash($_POST['password'], PASSWORD_BCRYPT); // or PASSWORD_ARGON2ID

        if($hash_password === null) {
            die("Error during password hashing!<br>");
        }

        $token = add_entropy(gen_random_bytes(32));

        $obj = new stdClass;
        $obj->username = $userName;
        $obj->type = "USER";
        $obj->password = $hash_password;
        $obj->token = encrypt_AES256($token, $_POST['password']);
        $obj->friends = array();
        $obj->blocked = array();
        $obj->timezone = encrypt_AES256($_POST['SelectContacts'], $token);

        /* Generate and Save end-to-end private and public keys */
        generate_EndToEnd_keys(md5($userName), $token);

        /* PUBLIC KEY FINGERPRINT */
        $public_key_text = file_get_contents('endtoend_keys/public_' . md5($userName) . '.pem');
        $obj->public_key_fingerprint = encrypt_AES256(sha256($public_key_text), $_POST['password']);
        $_SESSION['fingerprint'] = sha256($public_key_text);

        /* Add and Save new user in the DB */
        $DB[md5($userName)] = $obj; // Add new user
        if(saveDB() === false) { // Save new user in db
            die("Error during creating new user!");
        }

        $_SESSION['logged'] = 1;
        $_SESSION['user_id'] = md5($_POST['username']);
        $_SESSION['username'] = $userName;
        // $_SESSION['password'] = $_POST['password'];
        $_SESSION['type'] = $obj->type;
        $_SESSION['timezone'] = $_POST['SelectContacts'];
        $_SESSION['token'] = $token;

        header("Location: dashboard.php");
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <link rel="stylesheet" href="style.css">
    <title>Sign-Up - TOR Encrypted Chat</title>
    <style>
        form {
            background-color: #1f1f1f;
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

        input[type="text"]:focus, input[type="password"]:focus, select:focus {
            box-shadow: 0px 0px 3px blueviolet;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid blueviolet;
            background-color: #2c2c2c;
            color: white;
            font-size: 16px;
            appearance: none;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none; /* Firefox */
        }

        /*select:after {
            content: "▼";
            font-size: 20px;
            position: absolute;
            right: 10px;
            top: 15px;
            pointer-events: none;
        }*/
    </style>
</head>
<body>
    <a href="index.php" style="text-decoration: none;"><h1 class="title"><span style="color: blueviolet; text-shadow: 0px 0px 8px blueviolet;">TOR</span> <span class="underline">Encrypted</span> CHAT</h1></a>

    <form action="" method="POST" style="background: white; padding: 8px;">
        <label for="username">Username:</label>
        <br>
        <input type="text" id="username" name="username" pattern="[A-Za-z0-9-_ ]+" autocomplete="off" required>
        <br>
        <label for="password">Password:</label>
        <br>
        <input type="password" id="password" name="password" style="margin-bottom: 14px;" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{15,}" title="Password must be at least 15 characters long, and contain at least one uppercase letter, one lowercase letter, one number, and one special character." autocomplete="off" required>
        <br>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.2;">
            <strong style="margin-left: -20px;">AT LEAST:</strong>
            <li>15 characters</li>
            <li>One Uppercase letter</li>
            <li>One lowercase letter</li>
            <li>One number</li>
            <li>One special character</li>
        </ul>
        <br>
        <label for="rp_password">Repeat password:</label>
        <br>
        <input type="password" id="rp_password" name="rp_password" autocomplete="off" required>
        <br>
        <label for="SelectContacts">Select Timezone:</label>
        <br>
        <?php echo select_timezone(/*date_default_timezone_get()*/); ?>
        <br>
        <br>
        <input type="submit" value="SIGN-UP">
    </form>
</body>
</html>
