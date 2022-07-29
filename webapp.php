<?php
// Start the session

require_once 'vendor/autoload.php';


?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="style.css">
		
	</head>
<body onload="initializeApp()" onbeforeunload="forceLogoutUser(currentUserID)">
	<button id="leftbar_toggle" onclick="toggleLeft()">Show Sidebar</button>
	<div id="leftbar" class="closed">
		<div id="leftbar_quick">
			<h3>Quick Access</h3>
			<ul id="list_quick">
			
			</ul>
		</div>
		<div id="leftbar_allusers">
			<h3>All Users</h3>
			<ul id="list_allusers">
			
			</ul>
		</div>
		<div id="leftbar_settings">
			<h3>Settings</h3>
			<label for="currentuser_status">Status: </label>
			<select id="settings_status_select" name="status_select" onchange="updateUserStatus(event);">
				<option value="auto" selected>Automatic [Default]</option>
				<option value="available">Available</option>
				<option value="afk">AFK</option>
				<option value="dnd">Do Not Disturb</option>
			</select>
			<br />
			<button onclick="openMeetLinkEditor();">Edit Meeting Link</button>
			<br />
			<button onclick="openAdminPage();" id="adminbutton" class="adminbutton">Admin Page</button>
			<br />
			<button onclick="logoutUserConfirm();" class="settings_logoutbutton">Log Out</button>
		</div>
	</div>
	
	<div id="mainview">
		<div id="layout_container">
			
		</div>
	</div>
	<div id="sysmon">
	</div>
<div id="currentuserid" style="display: none;" >
<?php
echo $_GET['login'];
?>
</div>
<div id="phpstatus" style="display: none;">
<?php

switch (connection_status()) {
	case CONNECTION_NORMAL:
		$txt = 'normal';
		break;
	case CONNECTION_ABORTED:
		$txt = 'abort';
		
		break;
	case CONNECTION_TIMEOUT:
		$txt = 'timeout';
		break;
	case (CONNECTION_ABORTED & CONNECTION_TIMEOUT):
		$txt = 'aborttimeout';
		break;
	default:
		$txt = 'unknown';
		break;
}
echo $txt;


?>
</div>
<div id="click_mask" class="disabled" ></div>
<audio id="sound_knock">
	<source src="triknock05.ogg" type="audio/ogg">
</audio>
<script src="script.js"></script>
</body>
</html>