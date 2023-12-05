// jQuery required
function check_and_set_online(id) {
    $.ajax({
        type: 'POST',
        url: './check_and_set_online.php',
        data: "id=" + id,
        success: function() {
            // console.log("Online status refreshed!");
            setTimeout(() => {
                check_and_set_online(id);
            }, 1000 * 15);
        },
        error: function() {
            console.warn("Failed in refreshing online status!");
            setTimeout(() => {
                check_and_set_online(id);
            }, 1000);
        }
    });
}

function check_online(id) {
	$.ajax({
        type: 'POST',
        url: './check_and_set_online.php',
        data: "ver_id=" + id,
        success: function(response) {
            if(response == "0") {
            	document.getElementById("led-online").style.background = "red";
                document.getElementById("led-online").style.boxShadow = "0px 0px 4px red";
                document.getElementById("led-online").title = "Offline";
            } else if(response == "1") {
            	document.getElementById("led-online").style.background = "lightgreen";
                document.getElementById("led-online").style.boxShadow = "0px 0px 4px lightgreen";
                document.getElementById("led-online").title = "Online";
            } else {
            	document.getElementById("led-online").style.background = "yellow";
                document.getElementById("led-online").style.boxShadow = "0px 0px 4px yellow";
                document.getElementById("led-online").title = "";
            	console.warn("Unknown response");
            }
            // console.log(response);
            setTimeout(() => {
                check_online(id);
            }, 1000 * 8); // 8s
        },
        error: function() {
            console.warn("Failed in checking friends's online status!");
            setTimeout(() => {
                check_online(id);
            }, 1000);
        }
    });
}
