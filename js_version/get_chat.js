const local_chat = [];

function get_msg(id) {
    return new Promise((resolve) => {
        $.ajax({
            type: 'POST',
            url: 'js_get_info.php',
            data: 'f=getByID&id=' + id,
            success: function(r) {
                document.getElementById("chatScreen").innerHTML += r;
                resolve(true);
            },
            error: function(_a, _b, _c) {
            	console.warn("Error in fetching messagge! ID:", id);
                resolve(false);
            }
        });
    });
}

function check_chat() {
    return new Promise((resolve) => {
        $.ajax({
            type: 'POST',
            url: 'js_get_info.php',
            data: 'f=getArr',
            success: async function(r) {
            	// console.log(r);
                let infos = JSON.parse(r);
                let len1 = local_chat.length;
                let len2 = infos.length;

                for (let i = 0; i < len1; i++) {
                    if(!infos.includes(local_chat[i])) {
                    	if(document.getElementById(local_chat[i])) {
                            document.getElementById(local_chat[i]).remove();
                        }
                        local_chat.erase(local_chat[i]);

                        const all_chat_divs = document.getElementById("chatScreen").getElementsByTagName("div");
                        let checkDayView = false; // dayView changed var

                        // If last div's class == 'dayView'
                        if(all_chat_divs[all_chat_divs.length-1].classList.contains("dayView")) {
                            all_chat_divs[all_chat_divs.length-1].remove();
                            checkDayView = true;
                        }

                        // Check if there are two consecutive dayView div
                        for (let i = 0; i < all_chat_divs.length - 1; i++) {
                            if (all_chat_divs[i].classList.contains('dayView') && all_chat_divs[i + 1].classList.contains('dayView')) {
                                all_chat_divs[i].remove();
                                checkDayView = true;
                                // break;
                            }
                        }

                        // If dayView changed
                        if(checkDayView) {
                            const allDayView = document.getElementById("chatScreen").getElementsByClassName("dayView");
                            if(allDayView.length > 0) {
                                let lastDayView = allDayView[allDayView.length-1].innerText;
                                // console.log("Client:", lastDayView);
                                $.ajax({
                                    type: 'POST',
                                    url: 'js_get_info.php',
                                    data: 'f=SET_dayView&d=' + lastDayView,
                                    success: function(r) {
                                        // console.log("Server:", r);
                                        console.log("Last day updated!");
                                    },
                                    error: function() {
                                        console.warn("Error in updating last day!");
                                        window.location.reload();
                                    }
                                });
                            }
                        }
                    }
                }

                let requiereds = [];

                for (let i = 0; i < len2; i++) {
                    if(!local_chat.includes(infos[i])) {
                        requiereds.push(infos[i]);
                    }
                }

                if(requiereds.length === 0) {
                    resolve(true);
                } else {
                    for (let i = 0; i < requiereds.length; i++) {
                        local_chat.push(requiereds[i]);
                        await get_msg(requiereds[i]);
                    }
                    resolve(true);
                }
            }, // end success function
            error: function(a, b, c) {
                resolve(false);
            }
        });
    });
}

async function call_chat(forced=false) {
    let check = await check_chat();

    if(check && !forced) {
        setTimeout(call_chat, 1000 * 7); // 7s
    } else if(!forced) {
        console.warn("Error in fetching DB!");
        call_chat();
    } else if(check && forced) {
        console.log("Forced call OK!");
    } else if(!check && forced) {
        console.warn("Forced call gone wrong!");
        call_chat(true);
    } else {
        console.warn("Call unknown!");
    }

}

/* ###### Autostart ###### */
(function reCall(re=false) {
    $.ajax({
        type: 'POST',
        url: 'js_get_info.php',
        data: 'f=getArr',
        success: async function(r) {
        	// console.log(r);
            document.getElementById("chatScreen").style.visibility = "hidden";

            let stop = false;

            if(r.startsWith("notStillAllowed")) {
                let friend_username = r.split(":")[1];
                document.getElementById("chatScreen").innerHTML = `<div id="notStillAllowed"><b>${friend_username}</b> has not yet authorized you to open a chat with him.<br>Wait for ${friend_username} to authorize you.</div>`;
                stop = true;
            } else if (r.startsWith("authorizeRequest")) {
                let friend_username = r.split(":")[1];
                document.getElementById("chatScreen").innerHTML = `<div id="authorizeRequest"><b>${friend_username}</b> wants to start a chat with you<br><form action="pgp_auth.php" method="POST"><input type="submit" id="authOK" value="AUTHORIZE" name="AUTHORIZE"><br><input type="submit" id="authNO" value="REJECT" name="REJECT"><br><input type="submit" id="authNOBL" value="REJECT AND BLOCK" name="REJECT_BLOCK"></form></div>`;
                stop = true;
            }

            if(stop) {
                console.log(r);
                document.getElementById("chatScreen").style.justifyContent = "center";
                document.querySelector(".loading").style.display = "none";
                document.getElementById("chatScreen").style.visibility = "visible";
                setTimeout(()=>{reCall(true);}, 60 * 1000); // 1m
                return;
            }

            if(re === true) {
                document.querySelector(".loading").style.display = "block";
                document.getElementById("chatScreen").style.visibility = "hidden";
                document.getElementById("chatScreen").style.justifyContent = "normal";
            }

            let infos = JSON.parse(r);
            for (let i = 0; i < infos.length; i++) {
                local_chat.push(infos[i]);
                await get_msg(infos[i]);
            }

            document.getElementById("chatScreen").style.visibility = "visible";
            document.querySelector(".loading").style.display = "none";
            call_chat();
        },
        error: function(a, b, c) {
            alert("Unknown error in fetching DB!");
        }
    });
})();


/* AUTOSCROLL */
var userScrolled = false;
const icon = document.querySelector(".lockScrollIcon i");

function autoScroll() {
    var chatScreen = document.getElementById('chatScreen');
    if (!userScrolled) {
        chatScreen.scrollTo({
            top: chatScreen.scrollHeight,
            behavior: 'smooth'
        });
    }
}

function change_scroll_block() {
    let isBlocked = (icon.classList.contains("fa-lock")) ? true : false;

    if(!isBlocked) {
        icon.classList.replace("fa-lock-open", "fa-lock");
        userScrolled = false;
        autoScroll();
    }
}

const chatScreen = document.getElementById('chatScreen');

chatScreen.addEventListener('wheel', function() {
    var isAtBottom = chatScreen.scrollTop + chatScreen.clientHeight >= chatScreen.scrollHeight;

    if(!isAtBottom) {
        userScrolled = true;
        icon.classList.replace("fa-lock", "fa-lock-open");
    } else {
    	userScrolled = false;
        icon.classList.replace("fa-lock-open", "fa-lock");
    }
});

chatScreen.addEventListener('touchmove', function() {
    var isAtBottom = chatScreen.scrollTop + chatScreen.clientHeight >= chatScreen.scrollHeight;

    if(!isAtBottom) {
        userScrolled = true;
        icon.classList.replace("fa-lock", "fa-lock-open");
    } else {
    	userScrolled = false;
        icon.classList.replace("fa-lock-open", "fa-lock");
    }
});

/* HTML NODE MODIFER OBSERVER */

const config = { childList: true, subtree: true };

function callback(mutationsList, observer) {
    for (let mutation of mutationsList) {
        if (mutation.type === 'childList' && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0) ) {
            autoScroll();
        }
    }
}

const observer = new MutationObserver(callback);
observer.observe(document.getElementById('chatScreen'), config);

/* /HTML NODE MODIFER OBSERVER */

chatScreen.addEventListener('scroll', function() {
    var isAtBottom = chatScreen.scrollTop + chatScreen.clientHeight >= chatScreen.scrollHeight;
    if (isAtBottom) {
        userScrolled = false;
        icon.classList.replace("fa-lock-open", "fa-lock");
    }
});

let lastHeight = 0;
chatScreen.addEventListener('wheel', function(event) {
    if (event.deltaY > 0) { // wheel down scroll
        if(lastHeight == chatScreen.scrollTop + chatScreen.clientHeight) {
          	userScrolled = false;
          	icon.classList.replace("fa-lock-open", "fa-lock");
        }
        lastHeight = chatScreen.scrollTop + chatScreen.clientHeight;
    }
});


setInterval(() => {
    var isAtBottom = chatScreen.scrollTop + chatScreen.clientHeight >= chatScreen.scrollHeight;
    if (isAtBottom) {
        userScrolled = false;
        icon.classList.replace("fa-lock-open", "fa-lock");
    }
    
    if(!userScrolled && chatScreen.scrollTop + chatScreen.clientHeight < chatScreen.scrollHeight) {
    	autoScroll();
    }
}, 1000);
