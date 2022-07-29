<?php
// Start the session

require_once 'vendor/autoload.php';


?>
<!DOCTYPE html>
<html>
	<head>
		<style>
		
		</style>		
	</head>
<body onload="loginUserList();">
	<div id="maincontainer">
		<h2>Login to CXT Virtual Office</h2>
		<p>Select your user name from the dropdown list below:</p>
		<select id="username">
		
		</select>
		<br />
		<button id="submit_username" onclick="loginNewUser();">Submit</button>
	</div>
<script>
async function loginUserList() {
	var selectList = document.getElementById("username");
	let myPromise = new Promise(function(resolve, reject) {
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText != '') {
					newuserdata = JSON.parse(this.responseText);
					resolve(newuserdata);				
				}
			}
		  }
		
		xmlhttp.open("GET", "server.php?data=users", true);
		xmlhttp.send();
	});
	var newuserdata = await myPromise;
	for (let i in newuserdata) {
		if (newuserdata[i].meetingroom == 0) { 
			var newOption = document.createElement("option");
			newOption.setAttribute("value", newuserdata[i].id);
			newOption.innerHTML = newuserdata[i].name;
			selectList.appendChild(newOption);
		}
	}
}

async function loginNewUser() {
	var userID = Number(document.getElementById("username").value);
	let myPromise = new Promise(function(resolve, reject) {
		var outString = "&id=" + userID + "&online=1";
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText != '') {
					resolve(this.responseText);				
				}
			}
		  }
		
		xmlhttp.open("GET", "server.php?data=setonline" + outString, true);
		xmlhttp.send();
	});
	var success = await myPromise;
	window.open("webapp.php?login=" + userID, "_self");
}

</script>
</body>
</html>