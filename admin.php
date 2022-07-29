<?php
// Start the session
session_start();

?>
<!DOCTYPE HTML>
<html>
	<head>
		<style>
			body { position: absolute; top: 0; left: 0; bottom: 0; right: 0; overflow: hidden; }
			#groups {position: absolute; top: 0; left: 0; right: 50%; bottom: 50%; border: 1px solid blue; }
			#users {position: absolute; top: 0; right: 0; left: 50%; bottom: 50%; border: 1px solid green; }
			#users_box {position: absolute; top: 80px; left: 0; right: 0; bottom: 0; overflow: auto; }
			#editor {position: absolute; bottom: 0; left: 0; right: 0; top: 50%; border: 1px solid black; }
			h4 {text-align: center; }
			.group_id {margin-right: 10px; font-weight: bold; color: lightblue; }
			.group_name {text-transform: capitalize; color: orange; margin-right: 10px;}
			#select_icon_box {position: absolute; top: 40px; right: 0; bottom: 0; left: 250px; border: 2px inset gray; display: flex; flex-wrap: wrap; flex-direction: row; }
			.icon_item {flex-grow: 1;}
			.icon_item:hover {cursor: pointer; }
			.userlist_id {margin-right: 10px; color: blue; }
		</style>
	</head>
	
	<body>
		<div id="currentuserid" style="display: none;"><?php echo $_GET['login']; ?></div>
		<div id="groups">
			<h4>Groups / Departments</h4>
			<button id="groups_add" onclick="newGroup();">Add New</button>
			<div id="groups_box">
			
			</div>
		</div>
		<div id="users">
			<h4>Users</h4>
			<button id="users_add" onclick="newUser();">Add New</button>
			<div id="users_box">
				<ul id="userlist">
				
				</ul>
			</div>
		</div>
		<div id="editor">
			<h4>Editing</h4>
			<div id="editor_box">
				<div id="user_editor_box">
					<p><span>User ID:</span><input type="text" size="2" id="edit_userid" disabled> </input></p>
					<label for="edit_group_input">Group:</label>
					<select name="edit_group_input" id="edit_group_input">
					
					</select>
					<br />
					<label for="edit_username_input">Username:</label>
					<input type="text" name="edit_username_input" id="edit_username_input" />
					<br />
					<label for="edit_title_input">Title:</label>
					<input type="text" size="40" name="edit_title_input" id="edit_title_input" />
					<br />
					<label for="edit_icon_input">Icon:</label>
					<button name="edit_icon_input" id="edit_icon_input" onclick="selectIcon();">Choose Icon</button><span id="icon_display"></span>
					<br />
					<label for="edit_meetlink_input">Meeting Link:</label>
					<input type="text" name="edit_meetlink_input" id="edit_meetlink_input" size="30" ></input>
					<br />
					<label for="edit_admin_input">Admin?</label>
					<select name="edit_admin_input" id="edit_admin_input">

					</select>
					<br />
					<label for="edit_meetingroom_input">Add as a Meeting Room?</label>
					<input type="checkbox" name="edit_meetingroom_input" id="edit_meetingroom_input" value="true"></input>
					<button id="edit_user_apply">Apply Changes</button>
				</div>
				<div id="group_editor_box">
					<label for="edit_group_name_input">Group Name:</label>
					<input type="text" name="edit_group_name_input" id="edit_group_name_input" />
					<input type="hidden" id="edit_group_name_groupid" />
					<br />
					<br />
					<button id="edit_group_apply">Apply Changes</button>
				</div>
			</div>
		</div>
		<script>
			
			var userdata;
			var groupdata;
					
			
			setEditMode('group');
			getUserData();
			window.setTimeout(getGroupData, 500);
			window.setTimeout(populatePage, 1000);
			
			function populatePage() {
				var groupsbox = document.getElementById("groups_box");
				groupsbox.innerHTML = "";
				var userlist = document.getElementById("userlist");
				userlist.innerHTML = "";
				// create groups list
				for (let i in groupdata) {
					var newGroup = document.createElement("div");
					newGroup.setAttribute("class", "group_row");
					var groupID = document.createElement("span");
					groupID.setAttribute("class", "group_id");
					groupID.innerHTML = groupdata[i].groupid;
					newGroup.appendChild(groupID);
					var groupName = document.createElement("span");
					groupName.setAttribute("class", "group_name");
					groupName.innerHTML = groupdata[i].name;
					newGroup.appendChild(groupName);
					var editbutton = document.createElement("button");
					editbutton.setAttribute("class", "group_editbutton");
					editbutton.setAttribute("onclick", "editGroupName(" + groupdata[i].groupid + ", '" + groupdata[i].name + "');");
					editbutton.innerHTML = "Edit";
					newGroup.appendChild(editbutton);
					var deletebutton = document.createElement("button");
					deletebutton.setAttribute("class", "group_deletebutton");
					deletebutton.setAttribute("onclick", "deleteGroup(" + groupdata[i].groupid + ");");
					deletebutton.innerHTML = "Delete";
					newGroup.appendChild(deletebutton);
					groupsbox.appendChild(newGroup);
					var newListGroup = document.createElement("li");
					newListGroup.setAttribute("class", "userlist_group");
					newListGroup.innerHTML = groupdata[i].name;
					var newList = document.createElement("ul");
					newList.setAttribute("id", "listgroup" + groupdata[i].groupid);
					newListGroup.appendChild(newList);
					userlist.appendChild(newListGroup);
				}
				
				// create users list
				for (let i in userdata) {
					var groupList = document.getElementById("listgroup" + userdata[i].groupid);
					var newUser = document.createElement("li");
					newUser.setAttribute("class", "userlist_user");
					var userID = document.createElement("span");
					userID.setAttribute("class", "userlist_id");
					userID.innerHTML = userdata[i].id;
					newUser.appendChild(userID);
					var userName = document.createElement("span");
					userName.setAttribute("class", "userlist_name");
					userName.innerHTML = userdata[i].name;
					newUser.appendChild(userName);
					var userIcon = document.createElement("span");
					userIcon.setAttribute("class", "userlist_icon");
					userIcon.innerHTML = userdata[i].icon;
					newUser.appendChild(userIcon);
					var editbutton = document.createElement("button");
					editbutton.setAttribute("class", "userlist_editbutton");
					editbutton.setAttribute("onclick", "editUser(" + userdata[i].id + ");");
					editbutton.innerHTML = "Edit";
					newUser.appendChild(editbutton);
					var deletebutton = document.createElement("button");
					deletebutton.setAttribute("class", "userlist_deletebutton");
					deletebutton.setAttribute("onclick", "deleteUser(" + userdata[i].id + ");");
					deletebutton.innerHTML = "Delete";
					newUser.appendChild(deletebutton);
					groupList.appendChild(newUser);
				}
			}
			
			function getUserData() {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					if (this.responseText != '') {
						userdata = JSON.parse(this.responseText);
						}
					}
				  }
				
				xmlhttp.open("GET", "server.php?data=users", true);
				xmlhttp.send();
			}
			
			function getGroupData() {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					if (this.responseText != '') {
						groupdata = JSON.parse(this.responseText);
						}
					}
				  }
				
				xmlhttp.open("GET", "server.php?data=groups", true);
				xmlhttp.send();
			}
			
			function setEditMode(mode) {
				var edituser = document.getElementById("user_editor_box");
				var editgroup = document.getElementById("group_editor_box");
				switch(mode) {
					case 'group':
						edituser.style.display = "none";
						editgroup.style.display = "block";
						break;
					case 'user':
						editgroup.style.display = "none";
						edituser.style.display = "block";
						break;
				}
			}
			
			
			function newGroup() {
				setEditMode('group');
				document.getElementById("edit_group_name_input").value = "";
				document.getElementById("edit_group_apply").addEventListener("click", addGroup);
			}
			
			function newUser() {
				setEditMode('user');
				document.getElementById("edit_userid").value = "";
				var groupSelect = document.getElementById("edit_group_input");
				groupSelect.innerHTML = "";
				var userNameInput = document.getElementById("edit_username_input");
				userNameInput.value = "";
				var userTitleInput = document.getElementById("edit_title_input");
				userTitleInput.value = "";
				var userIconInput = document.getElementById("edit_icon_input");
				var iconDisplay = document.getElementById("icon_display");
				iconDisplay.innerHTML = "";
				var saveButton = document.getElementById("edit_user_apply");
				var userMeetInput = document.getElementById("edit_meetlink_input");
				userMeetInput.value = "";

				for (let i in groupdata) {
					var option = document.createElement("option");
					option.setAttribute("value", groupdata[i].groupid);
					option.innerHTML = groupdata[i].name;
					groupSelect.appendChild(option);
				}
				var userAdminInput = document.getElementById("edit_admin_input");
				userAdminInput.innerHTML = "";
				var optionFalse = document.createElement("option");
				optionFalse.setAttribute("value", "false");
				optionFalse.innerHTML = "False";
				userAdminInput.appendChild(optionFalse);
				var optionTrue = document.createElement("option");
				optionTrue.setAttribute("value", "true");
				optionTrue.innerHTML = "True";
				userAdminInput.appendChild(optionTrue);
				userNameInput.innerHTML = "";
				userTitleInput.innerHTML = "";
				var userMeetingRoomInput = document.getElementById("edit_meetingroom_input");
				userMeetingRoomInput.checked = false;
				iconDisplay.innerHTML = "";
				document.getElementById("edit_user_apply").setAttribute("onclick", "addUser();");
			}
			
			function addGroup() {
				var outString = "name=" + document.getElementById("edit_group_name_input").value;
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					if (this.responseText) {
						console.log(this.responseText);
						getGroupData();
						getUserData();
						window.setTimeout(populatePage, 500);
						newGroup();
						}
					}
				  }
				
				xmlhttp.open("GET", "server.php?data=addgroup&" + outString, true);
				xmlhttp.send();
			}
			
			function selectIcon() {
				if (1 == 1) { alert("Function currently disabled."); return false; }
				var container = document.createElement("div");
				container.setAttribute("id", "select_icon_box");
				for (i = 0; i < 1000; i++) {
					var iconString = "&#" + (127746+i) + ";";
					var iconItem = document.createElement("div");
					iconItem.setAttribute("class", "icon_item");
					iconItem.innerHTML = iconString;
					iconItem.setAttribute("onclick", "addDisplayIcon('" + iconString + "');");
					container.appendChild(iconItem);
				}
				document.getElementById("editor_box").appendChild(container);
			}
			
			function addUser() {
				var userName = document.getElementById("edit_username_input").value;
				var groupid = document.getElementById("edit_group_input").value;
				var title = document.getElementById("edit_title_input").value;
				var icon = document.getElementById("icon_display").innerHTML;
				var adminCheck = document.getElementById("edit_admin_input").value;
				var meetingRoom = document.getElementById("edit_meetingroom_input").checked;
				var admin;
				if (adminCheck == "true") {
					admin = 1;
				}
				else {
					admin = 0;
				}
				if (meetingRoom == true) {
					meetingRoom = 1;
				}
				else {
					meetingRoom = 0;
				}
				var meetlink = document.getElementById("edit_meetlink_input").value;
				var outString = "name=" + userName;
				if (groupid != "") { outString += "&groupid=" + groupid; }
				if (title != "") { outString += "&title=" + title; }
				if (icon != "") { outString += "&icon=" + icon; }
				if (admin != "") { outString += "&admin=" + admin; }
				if (meetlink != "") { outString += "&meetlink=" + encodeURI(meetlink); }
				if (meetingRoom != "") { outString += "&meetingroom=" + meetingRoom; }
				
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					if (this.responseText) {
						console.log(this.responseText);
						getGroupData();
						getUserData();
						window.setTimeout(populatePage, 500);
						newUser();
						}
					}
				  }
				
				xmlhttp.open("GET", "server.php?data=adduser&" + outString, true);
				xmlhttp.send();
			}
			
			function addDisplayIcon(icon) {
				var iconDisplay = document.getElementById("icon_display");
				iconDisplay.innerHTML = icon;
				document.getElementById("select_icon_box").remove();
			}
			
			function editGroupName(gid, gname) {
				setEditMode('group');
				document.getElementById("edit_group_name_input").value = gname;
				document.getElementById("edit_group_name_groupid").value = gid;
				document.getElementById("edit_group_apply").setAttribute("onclick", "updateGroup();");
			}
			
			function editUser(uid) {
				setEditMode('user');
				var groupSelect = document.getElementById("edit_group_input");
				for (let i in userdata) {
					if (userdata[i].id == uid) {
						for (let j in groupdata) {
							var option = document.createElement("option");
							option.setAttribute("value", groupdata[j].groupid);
							option.innerHTML = groupdata[j].name;
							
							if (userdata[i].groupid == groupdata[j].groupid) {
								option.selected = "true";
							}
							groupSelect.appendChild(option);
						}
						document.getElementById("edit_username_input").value = userdata[i].name;
						document.getElementById("edit_title_input").value = userdata[i].title;
						document.getElementById("icon_display").innerHTML = userdata[i].icon;
						document.getElementById("edit_userid").value = userdata[i].id;
						var userMeetingRoomInput = document.getElementById("edit_meetingroom_input");
						if (userdata[i].meetingroom == 1) {
							userMeetingRoomInput.checked = true;
						}
						else {
							userMeetingRoomInput.checked = false;
						}
						var userAdminInput = document.getElementById("edit_admin_input");
						userAdminInput.innerHTML = "";
						var optionFalse = document.createElement("option");
						optionFalse.setAttribute("value", "false");
						optionFalse.innerHTML = "False";
						var optionTrue = document.createElement("option");
						optionTrue.setAttribute("value", "true");
						optionTrue.innerHTML = "True";
						if (userdata[i].admin == 0) { optionFalse.selected = "true"; }
						else { optionTrue.selected = "true"; }
						userAdminInput.appendChild(optionFalse);
						userAdminInput.appendChild(optionTrue);
						document.getElementById("edit_meetlink_input").value = userdata[i].meetlink;
					}
				}
				document.getElementById("edit_user_apply").setAttribute("onclick", "updateUser();");
			}
			
			function updateUser() {
				var userid = document.getElementById("edit_userid").value;
				var username = document.getElementById("edit_username_input").value;
				var groupid = document.getElementById("edit_group_input").value;
				var title = document.getElementById("edit_title_input").value;
				var icon = document.getElementById("icon_display").innerHTML;
				var adminCheck = document.getElementById("edit_admin_input").value;
				var meetlink = document.getElementById("edit_meetlink_input").value;
				var meetingRoom = document.getElementById("edit_meetingroom_input").checked;
				if (meetlink == "") {
					meetlink = null;
				}
				var admin;
				if (adminCheck == 'true') {
					admin = '1';
				}
				else { 
					admin = '0'; 
				}
				if (meetingRoom == true) {
					meetingRoom = 1;
				}
				else {
					meetingRoom = 0;
				}
				var outString = "userid=" + userid;
				for (let i in userdata) {
					if (userdata[i].id == userid) {
						if (userdata[i].name != username) { outString += "&name=" + username; }
						if (userdata[i].groupid != groupid) { outString += "&groupid=" + groupid; }
						if (userdata[i].title != title) { outString += "&title=" + title; }
						if (userdata[i].icon !== icon) { outString += "&icon=" + icon; }
						if (userdata[i].admin != admin) { outString += "&admin=" + admin; }
						if (userdata[i].meetlink !== meetlink) { outString += "&meetlink=" + meetlink; }
						if (userdata[i].meetingroom != meetingRoom) { outString += "&meetingroom=" + meetingRoom; }
					}
				}
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					if (this.responseText) {
						window.location.reload();
						}
					}
				  }
				
				xmlhttp.open("GET", "server.php?data=edituser&" + outString, true);
				xmlhttp.send();
			}
			
			function updateGroup() {
				var groupName = document.getElementById("edit_group_name_input").value;
				var groupID = document.getElementById("edit_group_name_groupid").value;
				var outString = "groupid=" + groupID + "&name=" + groupName;
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					if (this.responseText) {
						console.log(this.responseText);
						getGroupData();
						getUserData();
						window.setTimeout(populatePage, 500);
						setEditMode('group');
						}
					}
				  }
				
				xmlhttp.open("GET", "server.php?data=editgroup&" + outString, true);
				xmlhttp.send();
			}
		</script>
	</body>
</html>