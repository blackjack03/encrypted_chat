<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
    die();
}

if(!isset($_GET['fname']) || !isset($_GET['TYPE']) || !isset($_GET['id']) || !isset($_GET['t'])) {
    header("Location: dashboard.php");
    die();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://jackprogram.altervista.org/libraries/font_awesome_pro.js"></script>
    <script>
        /* PHP - to - JS */
        const php_query = {
            fname: "<?php echo $_GET['fname']; ?>",
            id: "<?php echo $_GET['id']; ?>"
        };
        const timestamp = <?php echo $_GET['t']; ?> * 1000;
    </script>
    <style>
        body {
            background-color: rgb(25, 25, 25);
            font-family: Arial, Helvetica, sans-serif;
        }

        img, video {
            width: auto;
            height: auto;
            max-width: 80vw;
            max-height: 80vh;
        }
        
        img, video, audio {
        	box-shadow: 0px 0px 18px blueviolet;
            display: none;
        }

        .time {
            font-size: 17px;
            font-weight: bold;
            color: white;
            margin-top: 9px;
        }

        .title {
            text-align: center;
            color: blueviolet;
            text-shadow: 0px 0px 4px blueviolet;
        }
        
        .divMAIN {
        	width: auto;
            height: auto;
			max-height: 80vh;
        }

        .container {
            height: auto;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #time {
            width: 100%;
            height: auto;
            text-align: center;
            color: white;
            margin-top: 15px;
            font-weight: bold;
            font-size: 17px;
        }

        #dwnFile {
            display: none;
            /* display: inline-block; */
            margin: 0;
            padding: 0;
        }

        #bar {
            width: 50%;
            height: 50px;
            margin: 0 auto;
            background-color: lightgray;
            border-radius: 8px;
        }

        #barFill {
            background-color: green;
            border-radius: 8px;
            height: 50px;
            width: 0%;
            box-shadow: 0px 0px 6px green;
            transition: 0.5s;
        }

        #percBar {
            font-size: 20px;
            font-weight: bold;
            width: 100%;
            text-align: center;
            color: white;
            text-shadow: 0px 0px 3px white;
        }

        #dwnButton {
            /*position: fixed;
            top: 30px;
            right: 10px;*/
            height: 40px;
            width: auto;
            padding: 6px;
            outline: none;
            font-size: 20px;
            font-weight: bold;
            color: white;
            background-color: blueviolet;
            box-shadow: 0px 0px 3px blueviolet;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }

        #dwnButton:hover {
            box-shadow: 0px 0px 8px blueviolet;
        }

        #dwnButton:active {
            transform: scale(0.95);
        }

        .dwnDtnCont {
            width: 100%;
            height: auto;
            text-align: center;
            margin: 0 auto;
        }
    </style>
    <script>
        /**
         * @param {number} timestamp
         */
        function formatTimestamp(timestamp) {
            let date = new Date(timestamp);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0');
            let year = String(date.getFullYear()).substr(2);
            let hours = String(date.getHours()).padStart(2, '0');
            let minutes = String(date.getMinutes()).padStart(2, '0');
            return `${month}/${day}/${year} - ${hours}:${minutes}`;
        }

        async function isBlobUrlValid(blobUrl) {
            try {
                const response = await fetch(blobUrl);
                return response.ok;
            } catch (error) {
                return false;
            }
        }

        /** DECRYPTED FILE */
        var FILE = "";
        var fileNameDigested = "";
        var mimeType = "";
        var generalType = "";
        var fileSize = 0;
        var chunkSize = 0;


        /* Standard Error */
        function stderr(e='unk error') {
            console.error("Standard error: " + e);
            // todo
        }

        function parseFile(fileIsInCache=false) {
            if(!fileIsInCache) {
                var byteCharacters = atob(FILE[1]);
                var byteNumbers = new Array(byteCharacters.length);

                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }

                var byteArray = new Uint8Array(byteNumbers);

                var blob = new Blob([byteArray], {type: mimeType});
            }

            var blobURL = (!fileIsInCache) ? URL.createObjectURL(blob) : sessionStorage.getItem(fileNameDigested);

            let isVisible = false;
            if(generalType == "video" || generalType == "audio" || generalType == "image") {
                isVisible = true;
                document.getElementById(generalType).src = blobURL;
                document.getElementById(generalType).style.display = "block";
            }

            if(generalType == "image") {
                document.getElementById(generalType).addEventListener("click", () => {
                    window.open(blobURL, "_blank");
                });
                document.getElementById(generalType).style.cursor = "pointer";
            }

            document.getElementById("dwnFile").href = blobURL;
            document.getElementById("dwnFile").style.display = "inline-block";

            sessionStorage.setItem(fileNameDigested, blobURL);

            document.getElementById("time").innerText = ((!isVisible) ? "File sended on: " : "") + formatTimestamp(timestamp);

            // Free
            FILE = "";
        }


        /**
         * @param {number} rep
         */
        function getBigFile(rep) {
            console.log(rep);
            $.ajax({
                type: 'POST',
                url: 'js_get_big_file.php',
                data: `q=${JSON.stringify(php_query)}&f=get_chunk&r=${rep}`,
                success: function(r) {
                    if(r == "-1") {
                        stderr("Decrypt error");
                        return;
                    } else if (r == "::EOF") {
                        console.log("File reading complete!");
                        if (FILE.endsWith("::EOF")) {
                        	FILE = FILE.substring(0, FILE.length - 5).split(",");
                            mimeType = FILE[0].split(";")[0].split(":")[1];
                            generalType = FILE[0].split(":")[1].split("/")[0];
                    	}
                        $("#bar, #percBar").fadeOut("fast");
                        parseFile();
                    } else {
                        FILE += r;
                        let perc = ((chunkSize * (rep + 1)) / fileSize) * 100;
                        if(perc > 100) {
                            perc = 100;
                        }
                        document.getElementById("barFill").style.width = perc + "%";
                        document.getElementById("percBar").innerText = Math.round(perc) + "%";
                        getBigFile(rep + 1);
                    }
                },
                error: function() {
                    stderr("Error in get file's chunks!");
                    getBigFile(rep);
                }
            });
        }

        $(document).ready(function() {
            $.ajax({
                type: 'POST',
                url: 'js_get_big_file.php',
                data: `q=${JSON.stringify(php_query)}&f=get_file_name`,
                success: function(resp) {
                    if(resp == "-1") {
                        stderr("Decrypt error");
                        return;
                    }

                    let split_resp = resp.split("::");
                    let r = atob(split_resp[0]);

                    fileNameDigested = split_resp[1];
                    fileSize = parseInt(split_resp[2]);
                    chunkSize = parseInt(split_resp[3]);

                    document.title = "View: " + r;
                    document.getElementById("fn").innerText = "View: " + r;
                    document.getElementById("dwnFile").setAttribute('download', r);

                    if(sessionStorage.getItem(fileNameDigested) !== null) {
                        isBlobUrlValid(sessionStorage.getItem(fileNameDigested)).then(isValid => {
                            console.log(isValid);
                            if(isValid) {
                                parseFile(true);
                            } else {
                                getBigFile(0);
                            }
                        });
                    } else {
                        getBigFile(0);
                    }
                },
                error: function() {
                    stderr("Error in get file's name!");
                }
            });

        });
    </script>
    <title>Loading...</title>
</head>
<body>
    <h2 class="title" id="fn">Loading...</h2>
    <div id="percBar">0%</div>
    <div id="bar"><div id="barFill"></div></div>
    <div class="container">
        <img src="" id="image" alt="">
        <video src="" id="video" controls></video>
        <audio src="" id="audio" controls></audio>
    </div>
    <div id="time"></div>
    <br><br>
    <div class="dwnDtnCont"><a href="" download="" id="dwnFile" style="margin: auto;"><button id="dwnButton"><i class="fa-solid fa-file-arrow-down"></i> DOWNLOAD FILE</button></a></div>
</body>
</html>
