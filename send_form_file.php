<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
}

$DB = null;
$DB_CHAT = null;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    echo "<p style='color: red; font-weight: bold;'>Error!</p><br>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://jackprogram.altervista.org/libraries/font_awesome_pro.js"></script>
    <title>Send Form</title>
    <style>
        body {
            margin: 0;
        }

        .tools {
            display: flex;
            flex-direction: row;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: space-around;
        }

        .tools form, .tools .form {
            display: flex;
            align-items: center;
            justify-content: space-around;
            width: 30%;
            height: 100%;
            flex-direction: row;
        }

        #msg {
        	width: 70%;
            height: 36px;
            resize: none;
            font-size: 17px;
            border-radius: 4px;
            border: 1px solid blueviolet;
            padding: 2px;
            font-size: 18px;
            outline: none;
            box-shadow: 0px 0px 1px blueviolet;
            transition: 0.2s;
        }

        /* Form to send style*/

        button[type="submit"] {
            background-color: blueviolet;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-left: 3px;
            transition: 0.2s;
        }

        button[type="submit"]:hover {
            background-color: darkviolet;
        }

        button[type="submit"]:active, #subFile:active {
            transform: scale(0.92);
        }

        #msg:hover {
        	box-shadow: 0px 0px 2px blueviolet;
        }

        #msg:focus {
            box-shadow: 0px 0px 5px blueviolet;
        }

        #subFile {
        	font-size: 17px;
            color: white;
            background: blueviolet;
            border: none;
            box-shadow: 0px 0px 1px blueviolet;
            outline: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 2px 4px;
            transition: 0.2s;
        }
        
        #subFile:hover {
        	box-shadow: 0px 0px 4px blueviolet;
        }
        
        /* For Files */

		input[type=file]::file-selector-button {
            margin-right: 20px;
            background: blueviolet;
            padding: 4px 12px;
            border: none;
            outline: none;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            /*margin-bottom: 2px;*/
            transition: background .2s ease-in-out;
        }

        input[type=file]::file-selector-button:hover {
          	background: #0d45a5;
        }

        /* /// */

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9;
            background: rgba(255, 255, 255, 0.65);
            display: none;
        }

        .loading div {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            height: 50%; /* Circle size */
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            border: 5px solid blueviolet;
            border-bottom-color: transparent; /* rgb(189, 129, 245) */
            animation: spin 1s linear infinite;
            /* box-shadow: 0px 0px 3px blueviolet; */
        }

        .loading div::after {
            content: "";
            display: block;
            padding-bottom: 100%;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
<script>
        if(sessionStorage.getItem("forceRfh") !== null) {
            window.parent.get_send_alert();
            sessionStorage.removeItem("forceRfh");
        }

        function createAndSubmitFile() {
            const textareaValue = document.getElementById('msg').value;
            if(textareaValue.trim() === "") {
                document.getElementById('msg').focus();
                return;
            }

            $(".loading").show();
            sessionStorage.setItem("forceRfh", "1");

            const blob = new Blob([textareaValue], { type: 'text/plain' });

            const tempFile = new File([blob], "temp.txt");

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(tempFile);
            document.getElementById('hiddenFileInput').files = dataTransfer.files;

            document.getElementById('secureUploadMsg').submit();
        }
    </script>
</head>
<body>
    <div class="tools">

        <div class="form">
            <textarea name="msg" id="msg" placeholder="Messagge..." style="font-size: 16px;" maxlength="1500000" required></textarea>
            <button type="submit" onclick="createAndSubmitFile();"><i class="fa-solid fa-paper-plane"></i></button>
        </div>

        <form id="secureUploadMsg" action="secure_send_form.php" method="post" enctype="multipart/form-data" style="display: none;">
            <input type="file" id="hiddenFileInput" name="uploadedFile">
        </form>

        <form action="send_file.php" method="post" enctype="multipart/form-data" id="formFile" style="flex-direction: column;">
            <div style="margin-bottom: 3px; border: 1px solid black; padding: 2px; border-radius: 6px;"><input type="file" name="fileToUpload" id="fileToUpload" required></div>
            <input type="submit" value="Upload File" name="submit" id="subFile">
        </form>
    </div>

    <div class="loading"><div></div></div>
    
    <script>
    	$(document).ready(function() {
        	$("#formFile").submit(function(event) {
                if (this.checkValidity()) {
                	$(".loading").show();
                    sessionStorage.setItem("forceRfh", "1");
                }
            });

            $('#msg').on('keydown', function(e) {
              	// ENTER without SHIFT (send form)
              	if (e.key === 'Enter' && !e.shiftKey) {
                	e.preventDefault();
                  	createAndSubmitFile();
              	}
          	});
        });
    </script>
</body>
</html>
