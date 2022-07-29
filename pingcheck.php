<?php
$sqlhostname = "localhost";
$sqlusername = "voffice";
$sqlpassword = "voffice";

$ping = $_REQUEST["ping"];
$userid = $_GET['user'];
$pong = "";

if ($ping !== "") {
	$actionCheck = newActionCheck($userid);
	$pong = "PONG!" . $ping;
	if (gettype($actionCheck) == "array") {
		$pong .= "|NewAction";
	}
	echo $pong;
}

$sql = "UPDATE users SET lastping = CURRENT_TIMESTAMP() WHERE id = " . $userid;

timeoutCheck();

try {
	$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
	// set the PDO error mode to exception
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	
} catch(PDOException $e) {
	echo "Connection failed: " . $e->getMessage();
}
$conn = null;

function newActionCheck($uid) {
	$sqlhostname = "localhost";
	$sqlusername = "voffice";
	$sqlpassword = "voffice";
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT pkid FROM actions WHERE touser = " . $uid . " LIMIT 1";
		$stmt = $conn->query($sql);
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($output) {
			return $output;
		}
		
	} catch(PDOException $e) {
		echo "Connection failed: " . $e->getMessage();
	}
	$conn = null;
}

function timeoutCheck() {
	$sqlhostname = "localhost";
	$sqlusername = "voffice";
	$sqlpassword = "voffice";
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT id FROM users WHERE meetingroom = 0 AND online = 1 AND TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, lastping )) > 60";
		$stmt = $conn->query($sql);
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($output) {
			foreach($output[0] as $x => $x_value) {
				logoutUser($x_value);
			}
		}
		
	} catch(PDOException $e) {
		echo "Connection failed: " . $e->getMessage();
	}
	$conn = null;
}

function logoutUser($uid) {
	$sqlhostname = "localhost";
	$sqlusername = "voffice";
	$sqlpassword = "voffice";
	$sql = "UPDATE users SET online = 0, status = 'offline', lastupdated = CURRENT_TIMESTAMP() WHERE id = " . $uid;
	try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $conn->query($sql);
			$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
			logEntry("User " . $uid . " forcefully logged out due to inactivity.");
			return true;
			
		} catch(PDOException $e) {
			echo "Connection failed: " . $e->getMessage();
		}
		$conn = null;
}

function logEntry($event) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "INSERT INTO serverlog (event, timestamp) VALUES ('" . $event . "', CURRENT_TIMESTAMP());";
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
	} catch(PDOException $e) {
		return "Connection failed: " . $e->getMessage();
	}
	$conn = null;
}
?>