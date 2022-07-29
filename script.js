var currentUserID = Number(document.getElementById("currentuserid").innerHTML);
var currentUser;
var pageInit = 0;
var lastPing;
var avgPing;
var pingArr = new Array();
var userdata;
var groupdata;
var quickaccess;
var pingTime;
var activeAutoStatusTimer;
var meetlinkAlertCounter = 0;
var alertCounter = 0;
var loggingOut = 0;

function runAJAX(url, cFunction) {
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			if (this.responseText != '') {
				cFunction(this.responseText);
			}
		}
	}
	xmlhttp.open("GET", "server.php?" + url, true);
	xmlhttp.send();	
}

function loginUser() {
	var outString = "data=loginuser&user=" + currentUserID;
	runAJAX(outString, oneWay);
}

function oneWay(cData) {
	if (cData == "SUCCESS") {
		return true;
	}
}

function getUserData(cData) {
	userdata = JSON.parse(cData);
}

function getGroupData(cData) {
	groupdata = JSON.parse(cData);
}

function getQuickAccessData(cData) {
	quickaccess = JSON.parse(cData);
}

function checkMergeUserData(cData) {
	var diffdata = JSON.parse(cData);
	if (diffdata.length > 0) {
		for (let i in diffdata) {
			for (let j in userdata) {
				if (diffdata[i].id == userdata[j].id) {
					 userdata[j] = diffdata[i];
				}
			}
		}
		updatePage(diffdata);
	}
}

function getUserDataDiff() {
	runAJAX("data=userdiff", checkMergeUserData);
}

function updateUserStatus() {
	var curStatus = document.getElementById("settings_status_select").value;
	if (document.getElementById("settings_status_select").value == 'auto') {
		startAutoStatus();
		curStatus = "available";
	}
	var outString = "data=updatestatus&user=" + currentUserID + "&status=" + curStatus;
	runAJAX(outString, oneWay);
}

function initializeApp() {
	loginUser();
	runAJAX("data=users", getUserData);
	runAJAX("data=groups", getGroupData);
	runAJAX("data=getquickaccess&userid=" + currentUserID, getQuickAccessData);
	pageInit = 1;
	window.setTimeout(populatePage, 500);
	setInterval(sendPing, 2000);
	window.setTimeout(setInterval(getUserDataDiff, 2000), 10000);
}

function updatePage(diffdata) {
	for (let i in diffdata) {
		var officeInner = document.getElementById("office" + diffdata[i].id);
		var office = document.getElementById("office" + diffdata[i].id).parentElement;
		var officeNameList = office.getElementsByTagName("h5");
		var officeName = officeNameList[0].innerHTML;
		officeName.innerHTML = diffdata[i].name;
		var currentIcon = document.getElementById("person"+diffdata[i].id);
		if (currentIcon != undefined && diffdata[i].currentoffice != Number(currentIcon.parentElement.getAttribute("id").substring(6))) {
			// User moved
			var newOffice = document.getElementById("office" + diffdata[i].currentoffice);
			currentIcon.style.fontSize = "0";
			window.setTimeout(function() {
				currentIcon.parentElement.removeChild(currentIcon);
			}, 400);
			
		}
		if (diffdata[i].online == 0 && currentIcon != undefined) {
			// User logged out
			currentIcon.style.fontSize = "0";
			window.setTimeout(function() {
				currentIcon.parentElement.removeChild(currentIcon);
			}, 400);
			continue;
		}
		if (diffdata[i].online == 1 && currentIcon == undefined) {  
			// User logged in or moved
			var newIcon = document.createElement("div");
			var newOffice = document.getElementById("office" + diffdata[i].currentoffice);
			if (diffdata[i].id == currentUserID) {
				newIcon.setAttribute("class", "person current");
			}
			else {
				newIcon.setAttribute("class", "person");
			}
			var classList = newIcon.classList;
			newIcon.innerHTML = diffdata[i].icon;
			if (diffdata[i].meetingroom == 1) {
				if (diffdata[i].meetlink == '' || diffdata[i].meetlink == null) {
					newIcon.style.backgroundColor = "rgba(255,0,0,0.5)";
					newIcon.style.color = "rgba(218,165,32,0.5)";
				}
				else {
					newIcon.style.backgroundColor = "rgba(0,255,0,1)";
					newIcon.style.color = "rgba(218,165,32,1)";
				}
			}
			newIcon.setAttribute("id", "person" + diffdata[i].id);
			switch (diffdata[i].status) {
				case "available":
					classList.add("status_available");
					break;
				case "afk":
					classList.add("status_afk");
					break;
				case "dnd":
					classList.add("status_dnd");
					break;
			}
			newIcon.setAttribute("data-text", diffdata[i].name + ", " + diffdata[i].title);
			newIcon.setAttribute("onclick", "getUserActions('" + diffdata[i].id + "');");
			newOffice.appendChild(newIcon);
		}
		else if (currentIcon != undefined) {
			// Other user changes detected
			var classList = currentIcon.classList;
			classList.remove("status_available", "status_afk", "status_dnd");
			switch (diffdata[i].status) {
				case "available":
					classList.add("status_available");
					break;
				case "afk":
					classList.add("status_afk");
					break;
				case "dnd":
					classList.add("status_dnd");
					break;
			}
			currentIcon.setAttribute("data-text", diffdata[i].name + ", " + diffdata[i].title);
			if (diffdata[i].meetingroom == 1) {
				if (diffdata[i].meetlink == '' || diffdata[i].meetlink == null) {
					currentIcon.style.backgroundColor = "rgba(255,0,0,0.5)";
					currentIcon.style.color = "rgba(218,165,32,0.5)";
				}
				else {
					currentIcon.style.backgroundColor = "rgba(0,255,0,1)";
					currentIcon.style.color = "rgba(218,165,32,1)";
				}
			}
		}
	}
}

function sendPing() {
	var pingDate = new Date();
	pingTime = pingDate.getMilliseconds();
	var ping = uuidv4();
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			pingCheck(this.responseText);
		}
	};
	xmlhttp.open("GET", "pingcheck.php?ping=" + ping + "&user=" + currentUserID, true);
	xmlhttp.send();
}

function pingCheck(pong) {
	if (!pong.includes("PONG!")) { return "ERROR"; }
	var checkAction = 0;
	if (pong.includes("|NewAction")) {
		getWaitingAction();
	}				  
	var pongDate = new Date();
	var pongTime = pongDate.getMilliseconds();
	var diff = (pongTime - pingTime);
	lastPing = diff;
	pingArr.push(lastPing);
	var tempPingTotal = 0;
	if (pingArr.length > 10) {
		for (let i = 0; i<pingArr.length; i++) {
			tempPingTotal += pingArr[i];
		}
		avgPing = tempPingTotal / pingArr.length;
		pingArr.splice(pingArr.length-10);
		avgPing = avgPing.toFixed(0);
		document.getElementById("sysmon").innerHTML = "Avg Ping " + avgPing + " ms";
		if (avgPing >= 100) {
			document.getElementById("sysmon").style.color = "red";
		}
		else if (avgPing >= 40) {
			document.getElementById("sysmon").style.color = "blue";
		}
		else if (avgPing >= 20) {
			document.getElementById("sysmon").style.color = "darkgreen";
		}
		else if (avgPing >= 0) {
			document.getElementById("sysmon").style.color = "lightgreen";
		}
	}
}

function uuidv4() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
	var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
	return v.toString(16);
  });
}

function toggleLeft() {
	var leftBar = document.getElementById("leftbar");
	var leftBarButton = document.getElementById("leftbar_toggle");
	if (leftBar.getAttribute("class") == "open") {
		leftBar.setAttribute("class", "closed");
		leftBarButton.innerHTML = "Show Sidebar";
	}
	else {
		leftBar.setAttribute("class", "open");
		leftBarButton.innerHTML = "Hide Sidebar";
	}
}

function populatePage() {
	var layout = document.getElementById("layout_container");
	var allUsers = document.getElementById("list_allusers");
	// Build groups / departments
	for (let i in groupdata) {
		var newGroup = document.createElement("div");
		var groupname = document.createElement("h4");
		newGroup.setAttribute("id", "group_" + groupdata[i].groupid);
		newGroup.setAttribute("class", "group");
		newGroup.setAttribute("style", "background-image: linear-gradient(135deg, rgba(0,0,0,0.2), " + groupdata[i].color + ") !IMPORTANT; background-color: " + groupdata[i].color + " !IMPORTANT; border-color: " + groupdata[i].color + " !IMPORTANT;");
		groupname.innerHTML = groupdata[i].name.toUpperCase();
		groupname.setAttribute("class", "groupname");
		newGroup.appendChild(groupname);
		var officeCount = 0;
		for (let j in userdata) {
			if (userdata[j].groupid == groupdata[i].groupid) {
				officeCount++;
			}
		}
		layout.appendChild(newGroup);
		document.getElementById("group_" + groupdata[i].groupid).style.flexGrow = officeCount.toString();
		// build All Users list
		var newListGroup = document.createElement("li");
		newListGroup.setAttribute("class", "allusers_groupname");
		newListGroup.innerHTML = groupdata[i].name.toUpperCase();
		var newListGroupList = document.createElement("ol");
		newListGroupList.setAttribute("id", "allusers_group" + groupdata[i].groupid);
		newListGroup.appendChild(newListGroupList);
		allUsers.appendChild(newListGroup);
	}
	// Build offices
	for (let i in userdata) {
		var group = document.getElementById("group_" + userdata[i].groupid);
		var newoffice = document.createElement("div");
		var officename = document.createElement("h5");
		officename.setAttribute("class", "officename");
		newoffice.setAttribute("class", "office");
		if (userdata[i].id == currentUserID) {
			currentUser = userdata[i];
			autoStatusCheck();
			officename.innerHTML = userdata[i].name + "<br /><span id='homeoffice' onclick='goHome();'>(Home)</span>";
			var statusOptions = document.getElementById("settings_status_select").options;
			for (let k=0; k<statusOptions.length; k++) {
				if (userdata[k].autostatus == 0 && statusOptions[k].value == userdata[i].status) {
					statusOptions[k].selected = true;
				}
			}
			if (currentUser.admin == 1) { document.getElementById("adminbutton").setAttribute("class", "adminbuttonOn"); }
			if (currentUser.meetlink == null && meetlinkAlertCounter == 0) { needMeetLinkAlert(); meetlinkAlertCounter += 1; }
		}
		else {
			officename.innerHTML = userdata[i].name;
		}
		if (userdata[i].meetingroom == 1) {
			newoffice.setAttribute("class", "office meetingroom");
		}
		var officeInner = document.createElement("div");
		officeInner.setAttribute("id", "office" + userdata[i].id);
		officeInner.setAttribute("class", "office_inner");
		newoffice.appendChild(officeInner);
		newoffice.appendChild(officename);
		group.appendChild(newoffice);
		// build All Users list
		if (userdata[i].meetingroom == 0) {
			var groupList = document.getElementById("allusers_group" + userdata[i].groupid);
			var groupListUser = document.createElement("li");
			groupListUser.innerHTML = userdata[i].name;
			groupListUser.setAttribute("class", "allusers_user");
			groupListUser.setAttribute("onclick", "getUserActions('" + userdata[i].id + "', event);");
			groupList.appendChild(groupListUser);
		}
	}
	// Populate current users
	for (let i in userdata) {
		if (userdata[i].online == 1) {
			var otherOffice = document.getElementById("office" + userdata[i].currentoffice);
			var othericon = document.createElement("div");
			if (userdata[i].id == currentUserID) {
				othericon.setAttribute("class", "person current");
			}
			else {
				othericon.setAttribute("class", "person");
			}
			var classList = othericon.classList;
			othericon.innerHTML = userdata[i].icon;
			if (userdata[i].meetingroom == 1) {
				if (userdata[i].meetlink == '' || userdata[i].meetlink == null) {
					othericon.style.backgroundColor = "rgba(255,0,0,0.5)";
					othericon.style.color = "rgba(218,165,32,0.5)";
				}
				else {
					othericon.style.backgroundColor = "rgba(0,255,0,1)";
					othericon.style.color = "rgba(218,165,32,1)";
				}
			}
			othericon.setAttribute("id", "person" + userdata[i].id);
			switch (userdata[i].status) {
				case "available":
					classList.add("status_available");
					break;
				case "afk":
					classList.add("status_afk");
					break;
				case "dnd":
					classList.add("status_dnd");
					break;
			}
			othericon.setAttribute("data-text", userdata[i].name + ", " + userdata[i].title);
			othericon.setAttribute("onclick", "getUserActions('" + userdata[i].id + "');");
			otherOffice.appendChild(othericon);
		}				
	}
	var quickList = document.getElementById("list_quick");
	for (let i in quickaccess) {
		var listItem = document.createElement("li");
		for (let j in userdata) {
			if (userdata[j].id == quickaccess[i].userid2) {
				var userName = document.createElement("span");
				userName.innerHTML = userdata[j].name;
				userName.setAttribute("class", "quickaccess_name");
				listItem.appendChild(userName);
				var userStatus = document.createElement("span");
				switch (userdata[j].status) {
					case "available":
						userStatus.setAttribute("class", "qa_status_available");
						break;
					case "afk":
						userStatus.setAttribute("class", "qa_status_afk");
						break;
					case "dnd":
						userStatus.setAttribute("class", "qa_status_dnd");
						break;
					case "offline":
						userStatus.setAttribute("class", "qa_status_offline");
						break;
				}
				userStatus.innerHTML = userdata[j].status;
				listItem.appendChild(userStatus);
				listItem.setAttribute("onclick", "getUserActions('" + userdata[j].id + "');");
			}
		}
		quickList.appendChild(listItem);
	}
}

function closeActionMenu() {
	document.getElementById("action_menu_box").remove();
	disableMask();
}

function getUserActions(uid) {
	var menuBox = document.createElement("div");
	menuBox.style.top = (event.pageY-50) + "px";
	menuBox.style.left = event.pageX + "px";
	menuBox.setAttribute("id", "action_menu_box");
	var closeMenu = document.createElement("div");
	closeMenu.setAttribute("class", "action_menu_close");
	closeMenu.innerHTML = "&#128473;";
	closeMenu.setAttribute("onclick", "closeActionMenu();");
	menuBox.appendChild(closeMenu);
	var menuBoxTitle = document.createElement("p");
	menuBoxTitle.setAttribute("class", "menu_box_title");
	var selectedUser;
	for (let i in userdata) {
		if (userdata[i].id == uid) {
			selectedUser = userdata[i];
		}
	}
	
	menuBoxTitle.innerHTML = selectedUser.name;
	var menuBoxList = document.createElement("ul");
	menuBoxList.setAttribute("id", "menu_action_list");
	if (uid == currentUserID) {
		// User clicked on their own icon
		
	}
	else if (selectedUser.meetingroom == 1) {
		var action1 = document.createElement("li");
		action1.innerHTML = "Enter Meeting Room";
		if (selectedUser.meetlink == '' || selectedUser.meetlink == null) {
			action1.setAttribute("onclick", "noLinkAlert()");
		}
		else {
			action1.setAttribute("onclick", "enterMeetingRoom(" + uid + ", '" + selectedUser.meetlink + "');");
		}
		menuBoxList.appendChild(action1);
	}
	else {
		// User clicked on another's icon
		if (selectedUser.online == 1) {
			var action1 = document.createElement("li");
			action1.innerHTML = "Knock on Door";
			action1.setAttribute("onclick", "sendKnock(" + uid + ");");
			menuBoxList.appendChild(action1);
		}
		var action2 = document.createElement("li");
		var optionName = "Add to Quick Access";
		var optionClick = "addQuickAccess(" + uid + ");";
		for (let i in quickaccess) {
			if ((quickaccess[i].userid == currentUserID) && (quickaccess[i].userid2 == selectedUser.id)) {
				optionName = "Remove from Quick Access";
				optionClick = "removeQuickAccess(" + uid + ");";
			}
		}
		
		action2.innerHTML = optionName;
		action2.setAttribute("onclick", optionClick);
		menuBoxList.appendChild(action2);
	}
	menuBox.appendChild(menuBoxTitle);
	menuBox.appendChild(menuBoxList);
	document.body.appendChild(menuBox);
	enableMask();
	document.getElementById("click_mask").setAttribute("onclick", "closeActionMenu();");
}



function startAutoStatus() {
	activeAutoStatusTimer = window.setTimeout(autoAFK, 300000);
	window.addEventListener("mousemove", restartAFKTimer);
}

function restartAFKTimer() {
	window.removeEventListener("mousemove", restartAFKTimer);
	clearTimeout(activeAutoStatusTimer);
	activeAutoStatusTimer = window.setTimeout(autoAFK, 300000);
}

function autoAFK() {
	window.removeEventListener("mousemove", restartAFKTimer);
	var outString = "data=updatestatus&user=" + currentUserID + "&status=afk";
	runAJAX(outString, autoAFKcb);	
}

function autoAFKcb(cData) {
	if (cData == "SUCCESS") {
		window.addEventListener("mousemove", autoBackAFK);
	}
}

function autoBackAFK() {
	window.removeEventListener("mousemove", autoBackAFK);
	var outString = "data=updatestatus&user=" + currentUserID + "&status=available";
	runAJAX(outString, startAutoStatus);
}

function autoStatusCheck() {
	if (currentUser.autostatus == '1') {
		startAutoStatus();	
	}
}

function sendKnock(uid) {
	var outString = "data=sendknock&fromuser=" + currentUserID + "&touser=" + uid;
	runAJAX(outString, closeActionMenu);
}

function getWaitingAction() {
	var outString = "data=getaction&user=" + currentUserID;
	runAJAX(outString, actionAlertUser);
}

function actionAlertUser(cData) {
	if (alertCounter == 1) { return false; }
	else { 
		var data = JSON.parse(cData);
		switch (data[0].action) {
			case "knock":
				actionReceiveKnock(data[0]);
				break;
			case "rejectmessage":
				actionReceiveMessage(data[0]);
				break;
			case "joinmeet":
				actionJoinMeeting(data[0]);
				break;
		}
		alertCounter = 1;
	}
}

function actionReceiveKnock(data) {
	document.getElementById("sound_knock").play();
	if (document.getElementById("alert_window")) { return; }
	var fromUser = getUserName(data.fromuser);
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	alertWindow = document.getElementById("alert_window");
	var alertBody = document.getElementById("alert_body");
	var alertTitle = document.getElementById("alert_title");
	alertTitle.innerHTML = "User is Knocking";
	alertBody.innerHTML = "User <span class='alertusername'>" + fromUser + "</span> is knocking on your door.  <br /><span class='alerttimestamp'>" + data.info + "</span>";
	var alertOption1 = document.createElement("button");
	alertOption1.setAttribute("onclick", "knockAllowIn('" + data.pkid + "');");
	alertOption1.innerHTML = "Just Allow In";
	var alertOption2 = document.createElement("button");
	alertOption2.setAttribute("onclick", "knockAllowInMeet('" + data.pkid + "');");
	alertOption2.innerHTML = "Allow In and Start Meeting";
	if (currentUser.meetlink == '' || currentUser.meetlink == null) {
		alertOption2.setAttribute("disabled", true);
	}
	var alertOption3 = document.createElement("button");
	alertOption3.setAttribute("onclick", "knockRejectMessage('" + data.pkid + "');");
	alertOption3.innerHTML = "Reject With Message";
	var alertOption4 = document.createElement("button");
	alertOption4.setAttribute("onclick", "knockRejectNoMessage('" + data.pkid + "');");
	alertOption4.innerHTML = "Reject (No Message)";
	var alertOptionContainer = document.createElement("div");
	alertOptionContainer.setAttribute("class", "alertoptions");
	alertOptionContainer.appendChild(alertOption1);
	alertOptionContainer.appendChild(alertOption2);
	alertOptionContainer.appendChild(alertOption3);
	alertOptionContainer.appendChild(alertOption4);
	alertWindow.appendChild(alertOptionContainer);
	alertCounter = 0;
}

function actionJoinMeeting(data) {
	var outString = "data=sendreceipt&pkid=" + data.pkid;
	runAJAX(outString, actionJoinMeetingCB);
}

function actionJoinMeetingCB(cData) {
	window.open(cData.info, "_blank");
	alertCounter = 0;
}

function actionReceiveMessage(data) {
	if (document.getElementById("alert_window")) { return; }
	var fromUser = getUserName(data.fromuser);
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	alertWindow = document.getElementById("alert_window");
	var alertBody = document.getElementById("alert_body");
	var alertTitle = document.getElementById("alert_title");
	alertTitle.innerHTML = "User sent a message.";
	alertBody.innerHTML = "User <span class='alertusername'>" + fromUser + "</span> sent you a message.  <br /><span class='alertmessage'>" + data.info + "</span>";
	var alertOption1 = document.createElement("button");
	alertOption1.innerHTML = "Acknowledged";
	alertOption1.setAttribute("onclick", "sendReceipt('" + data.pkid + "');");
	alertBody.appendChild(alertOption1);
	alertCounter = 0;
}

function getUserName(uid) {
	for (let i in userdata) {
		if (userdata[i].id == uid) {
			return userdata[i].name;
		}
	}
}

function enableMask() {
	document.getElementById("click_mask").setAttribute("class", "enabled");
}

function disableMask() {
	document.getElementById("click_mask").setAttribute("class", "disabled");
}

function createAlertWindow() {
	enableMask();
	document.getElementById("click_mask").setAttribute("onclick", "destroyAlertWindow();");
	var alertWindow = document.createElement("div");
	alertWindow.setAttribute("id", "alert_window");
	var alertWindowClose = document.createElement("p");
	alertWindowClose.setAttribute("class", "alert_window_close");
	alertWindowClose.innerHTML = "&#128473;";
	alertWindowClose.setAttribute("onclick", "destroyAlertWindow();");
	alertWindow.appendChild(alertWindowClose);
	var alertTitle = document.createElement("h3");
	alertTitle.setAttribute("id", "alert_title");
	var alertBody = document.createElement("p");
	alertBody.setAttribute("id", "alert_body");
	alertWindow.appendChild(alertTitle);
	alertWindow.appendChild(alertBody);
	return alertWindow;
}

function destroyAlertWindow() {
	document.getElementById("alert_window").remove();
	disableMask();
}

function knockAllowIn(pkid) {
	var outString = "data=knockresponse&pkid=" + pkid + "&response=allowin";
	runAJAX(outString, destroyAlertWindow);
}

function knockAllowInMeet(pkid) {
	var outString = "data=knockresponse&pkid=" + pkid + "&response=allowinmeet&meetlink=" + encodeURI(currentUser.meetlink);
	runAJAX(outString, function() {
		destroyAlertWindow();
		enableMask();
		var newAlertWindow = createAlertWindow();
		document.body.appendChild(newAlertWindow);
		alertWindow = document.getElementById("alert_window");
		var alertTitle = document.getElementById("alert_title");
		var alertBody = document.getElementById("alert_body");
		alertTitle.innerHTML = "Create a Meeting";
		alertBody.innerHTML = "Click the button to start your new meeting.";
		var alertOption1 = document.createElement("button");
		alertOption1.innerHTML = 'Create Meeting';
		alertOption1.setAttribute("onclick", "createMeetingNow();");
		alertWindow.appendChild(alertOption1);
	});	
}

function knockRejectMessage(pkid) {
	var outString = "data=knockresponse&pkid=" + pkid + "&response=rejectmessage";
	runAJAX(outString, knockRejectMessageCB);
}

function knockRejectMessageCB(cData) {
	var oldRequest = JSON.parse(cData);
	destroyAlertWindow();
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	document.getElementById("alert_title").innerHTML = "Enter your message to send.";
	var userInput = document.createElement("input");
	userInput.setAttribute("id", "alert_input");
	userInput.setAttribute("type", "text");
	userInput.setAttribute("size", "100");
	var alertBody = document.getElementById("alert_body");
	alertBody.appendChild(userInput);
	var button = document.createElement("button");
	button.setAttribute("onclick", "sendRejectMessage(" + oldRequest.fromuser + ");");
	button.innerHTML = "Send Message";
	alertBody.appendChild(button);
}

function knockRejectNoMessage(pkid) {
	var outString = "data=knockresponse&pkid=" + pkid + "&response=rejectnomessage";
	runAJAX(outString, destroyAlertWindow);
}

function goHome() {
	var outString = "data=gohome&user=" + currentUserID;
	runAJAX(outString, getUserDataDiff);
}

function createMeetingNow() {
	destroyAlertWindow();
	var meeting = window.open(currentUser.meetlink, "_blank");
}

function noLinkAlert(meetname) {
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	alertWindow = document.getElementById("alert_window");
	var alertTitle = document.getElementById("alert_title");
	alertTitle.innerHTML = "Missing Meeting Link";
	var alertBody = document.getElementById("alert_body");
	alertBody.innerHTML = "The requested meeting room does not have a meeting link defined.  Please see your admin to resolve the issue.";
	var buttonAck = document.createElement("button");
	buttonAck.setAttribute("onclick", "destroyAlertWindow();");
	buttonAck.innerHTML = "Acknowledged";
	alertBody.appendChild(buttonAck);
}

function sendRejectMessage(uid) {
	var message = document.getElementById("alert_input").value;
	var outString = "data=sendrejectmessage&fromuser=" + currentUserID + "&touser=" + uid + "&message=" + message;
	runAJAX(outString, destroyAlertWindow);
}

function sendReceipt(pkid) {
	var outString = "data=sendreceipt&pkid=" + pkid;
	runAJAX(outString, destroyAlertWindow);
}

function addQuickAccess(uid) {
	var outString = "data=addquickaccess&userid=" + currentUserID + "&userid2=" + uid;
	runAJAX(outString, closeActionMenu);
}

function removeQuickAccess(uid) {
	var outString = "&userid=" + currentUserID + "&userid2=" + uid;
	runAJAX(outString, closeActionMenu);
}



function openMeetLinkEditor() {
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	alertWindow = document.getElementById("alert_window");
	var alertTitle = document.getElementById("alert_title");
	var alertBody = document.getElementById("alert_body");
	alertTitle.innerHTML = "Edit Meeting Link";
	var currentLinkLabel = document.createElement("label");
	currentLinkLabel.setAttribute("for", "editmeet_curlink");
	currentLinkLabel.innerHTML = "Current URL:";
	alertBody.appendChild(currentLinkLabel);
	var currentLink = document.createElement("span");
	currentLink.innerHTML = currentUser.meetlink;
	currentLink.setAttribute("id", "editmeet_curlink");
	alertBody.appendChild(currentLink);
	var br = document.createElement("br");
	alertBody.appendChild(br);
	var newLinkLabel = document.createElement("label");
	newLinkLabel.setAttribute("for", "editmeet_newlink");
	newLinkLabel.innerHTML = "Enter New URL:";
	alertBody.appendChild(newLinkLabel);
	var newLink = document.createElement("input");
	newLink.setAttribute("type", "text");
	newLink.setAttribute("id", "editmeet_newlink");
	alertBody.appendChild(newLink);
	var editButton = document.createElement("button");
	editButton.innerHTML = "Apply Changes";
	editButton.setAttribute("onclick", "sendMeetLinkUpdate();");
	alertBody.appendChild(editButton);
	
}

function sendMeetLinkUpdate() {
	var newLink = document.getElementById("editmeet_newlink").value;
	const isValidUrl = urlString=> {
		try { 
			return Boolean(new URL(urlString)); 
		}
		catch(e){ 
			return false; 
		}
	}
	if (newLink == "") { alert("Cannot be empty!"); return false; }
	else if (isValidUrl(newLink) == false) { alert("Not a valid URL, try again."); return false; }
	else {
		var outString = "data=editmeetlink&userid=" + currentUserID + "&newlink=" + encodeURI(newLink);
		runAJAX(outString, destroyAlertWindow);
	}
}

function logoutUserConfirm() {
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	alertWindow = document.getElementById("alert_window");
	var alertTitle = document.getElementById("alert_title");
	var alertBody = document.getElementById("alert_body");
	alertTitle.innerHTML = "Are you sure you want to log out?";
	var alertOption1 = document.createElement("button");
	alertOption1.setAttribute("value", "yes");
	alertOption1.innerHTML = "Yes";
	alertOption1.setAttribute("onclick", "logoutUser();");
	var alertOption2 = document.createElement("button");
	alertOption2.setAttribute("value", "no");
	alertOption2.innerHTML = "No";
	alertOption2.setAttribute("onclick", "destroyAlertWindow();");
	alertBody.appendChild(alertOption1);
	alertBody.appendChild(alertOption2);
}

function logoutUser() {
	var userID = currentUserID;
	loggingout = 1;
	var outString = "data=logoutuser&forced=0&userid=" + currentUserID;
	runAJAX(outString, function() {
		destroyAlertWindow();
		window.open("index.php?userid=" + userID, "_self");
	});
}

function openAdminPage() {
	window.open("admin.php?login=" + currentUserID, "_blank");
}

function forceLogoutUser(uid) {
	if (loggingout == 1) { return false; }
	var outString = "data=logoutuser&forced=1&userid=" + uid;
	runAJAX(outString, oneWay);
}

function updateStyle() {
	var newStyle = document.getElementById("settings_style_select").value;
	if (newStyle == 'light') {
		document.getElementById("mainview").style.backgroundColor = "rgb(255,255,255)";
		document.getElementById("mainview").style.color = "black";
		document.getElementById("leftbar").style.backgroundColor = "rgba(250,250,250,0.9)";
		document.getElementById("leftbar").style.color = "black";
	}
	else {
		document.getElementById("mainview").style.backgroundColor = "rgb(0,0,0)";
		document.getElementById("mainview").style.color = "white";
		document.getElementById("leftbar").style.backgroundColor = "rgba(30,30,30,0.9)";
		document.getElementById("leftbar").style.color = "white";
	}
}

function enterMeetingRoom(uid, url) {
	var outString = "data=entermeetingroom&userid=" + currentUserID + "&roomid=" + uid + "&meetlink=" + encodeURI(url);
	runAJAX(outString, closeActionMenu);
}

function needMeetLinkAlert() {
	var alertWindow = createAlertWindow();
	document.body.appendChild(alertWindow);
	alertWindow = document.getElementById("alert_window");
	var alertTitle = document.getElementById("alert_title");
	alertTitle.innerHTML = "Missing Meeting Link";
	var alertBody = document.getElementById("alert_body");
	alertBody.innerHTML = "Before you can host meetings, you will need to create a one-time meeting in Google Calendar and add Google Meet.  Obtain the meeting link (example:  https://meet.google.com/ayz-sjgy-xyz) and use the left sidebar menu to Edit Meeting Link, and paste your meeting link there.";
	var buttonAck = document.createElement("button");
	buttonAck.setAttribute("onclick", "destroyAlertWindow();");
	buttonAck.innerHTML = "Acknowledged";
	alertBody.appendChild(buttonAck);
}