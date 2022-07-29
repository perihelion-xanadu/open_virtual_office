<?php
$sqlhostname = "localhost";
$sqlusername = "voffice";
$sqlpassword = "voffice";

$data = $_GET["data"];
switch ($data) {
	case "users":
		echo getUserData();
		break;
	case "groups":
		echo getGroupData();
		break;
	case "addgroup":
		$groupname = $_GET["name"];
		echo addGroup($groupname);
		break;
	case "adduser":
		echo addUser($_GET);
		break;
	case "editgroup":
		$groupid = $_GET["groupid"];
		$groupname = $_GET["name"];
		echo editGroup($groupid, $groupname);
		break;
	case "edituser":
		$userid = $_GET['userid'];
		echo editUser($userid, $_GET);
		break;
	case "updatestatus":
		$userid = $_GET['user'];
		$status = $_GET['status'];
		echo updateStatus($userid, $status);
		break;
	case "sendknock":
		$fromuser = $_GET['fromuser'];
		$touser = $_GET['touser'];
		echo createAction($fromuser, $touser, 'knock', null);
		break;
	case "loginuser":
		$userid = $_GET['user'];
		echo loginUser($userid);
		break;
	case "getaction":
		$userid = $_GET['user'];
		echo getAction($userid);
		break;
	case "knockresponse":
		$pkid = $_GET['pkid'];
		$response = $_GET['response'];
		echo knockResponse($pkid, $response);
		break;
	case "gohome":
		$uid = $_GET['user'];
		echo moveUser($uid, $uid);
		break;
	case "sendrejectmessage":
		$fromuser = $_GET['fromuser'];
		$touser = $_GET['touser'];
		$message = $_GET['message'];
		echo createAction($fromuser, $touser, 'rejectmessage', $message);
		break;
	case "sendreceipt":
		$pkid = $_GET['pkid'];
		echo deleteAction($pkid);
		break;
	case "addquickaccess":
		$userid = $_GET['userid'];
		$userid2 = $_GET['userid2'];
		echo addQuickAccess($userid, $userid2);
		break;
	case "getquickaccess":
		$userid = $_GET['userid'];
		echo getQuickAccess($userid);
		break;
	case "removequickaccess":
		$userid = $_GET['userid'];
		$userid2 = $_GET['userid2'];
		echo removeQuickAccess($userid, $userid2);
		break;
	case "editmeetlink":
		$userid = $_GET['userid'];
		$newlink = $_GET['newlink'];
		echo editMeetLink($userid, $newlink);
		break;
	case "logoutuser":
		echo logoutUser($_GET['userid'], $_GET['forced']);
		break;
	case "entermeetingroom":
		$userid = $_GET['userid'];
		$roomid = $_GET['roomid'];
		$meetlink = $_GET['meetlink'];
		if (moveUser($userid, $roomid) == 'SUCCESS') {
			echo createAction($roomid, $userid, 'joinmeet', $meetlink);
		}
		break;
	case "userdiff":
		echo getUserDiff();
		break;
	case "setonline":
		echo setUserOnline($_GET);
		break;
}

function knockResponse($pkid, $response) {
	global $sqlhostname, $sqlusername, $sqlpassword, $action;
	$sql = "SELECT * FROM actions WHERE pkid ='" . $pkid . "' LIMIT 1;";
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->query($sql);
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($output) {
			$action = $output[0];
		}
		
	} catch(PDOException $e) {
		return logEntry("knockResponse - Connection failed: " . $e->getMessage());
	}
	
	
	if ($response == 'allowin') {
		if (moveUser($action['fromuser'], $action['touser']) == 'SUCCESS') {
			deleteAction($action['pkid']);
			logEntry("User " . $action['fromuser'] . " allowed in and moved to office " . $action['touser'] . ". actionPKID=" . $action['pkid']);
		}
	}
	else if ($response == 'allowinmeet') {
		if (moveUser($action['fromuser'], $action['touser']) == 'SUCCESS') {
			if (deleteAction($action['pkid']) == 'SUCCESS') {
				logEntry("User " . $action['fromuser'] . " allowed in to meeting in office " . $action['touser'] . ". actionPKID=" . $action['pkid']);
				return createAction($action['touser'], $action['fromuser'], 'joinmeet', $_GET['meetlink']);
			}
		}			
	}
	else if ($response == 'rejectmessage') {
		deleteAction($action['pkid']);
		logEntry("User " . $action['fromuser'] . " rejected a knock from user " . $action['touser'] . " with a message. actionPKID=" . $action['pkid']);
		return json_encode($action);

	}
	else if ($response == 'rejectnomessage') {
		logEntry("User " . $action['fromuser'] . " rejected a knock from user " . $action['touser'] . ". actionPKID=" . $action['pkid']);
		return deleteAction($action['pkid']);
	}
	$conn = null;
}

function getAction($userid) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "SELECT * FROM actions WHERE touser = " . $userid . " LIMIT 1;";
		try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $conn->query($sql);
			$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($output) {
				return json_encode($output);
			}
		} catch(PDOException $e) {
			return logEntry("getAction - Connection failed: " . $e->getMessage());
		}
		$conn = null;
}

function loginUser($userid) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "UPDATE users SET online = 1, status = 'available', lastping = NULL, lastupdated=CURRENT_TIMESTAMP() WHERE id = " . $userid;
		try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			logEntry("User " . $userid . " has logged in.");
			return "SUCCESS";
		} catch(PDOException $e) {
			return logEntry("loginUser - Connection failed: " . $e->getMessage());
		}
		$conn = null;
}

function updateStatus($userid, $status) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	if ($status == "auto") {
			$sql = "UPDATE users SET autostatus = 1, status = 'available', lastupdated=CURRENT_TIMESTAMP() WHERE id = " . $userid;
		}
		else {
			$sql = "UPDATE users SET status = '" . $status . "', lastupdated=CURRENT_TIMESTAMP() WHERE id = " . $userid;
		}
		try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			logEntry("User status updated for user " . $userid . " to " . $status . ".");
			return "SUCCESS";
		} catch(PDOException $e) {
			return logEntry("updateStatus - Connection failed: " . $e->getMessage());
		}
		$conn = null;
}

function editUser($userid, $data) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "UPDATE users SET ";
		foreach ($data as $k => $v) {
			if ($k != 'userid' && $k != 'data') {
				$sql .= $k . "='" . $v . "'";
				$sql .= ",";
			}
		}
		$sql .= "lastupdated=CURRENT_TIMESTAMP()";
		$sql .= " WHERE id=" . $userid;
		try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			logEntry("User " . $userid . " edited successfully.");
			return "SUCCESS";
		} catch(PDOException $e) {
			return logEntry("editUser - Connection failed: " . $e->getMessage());
		}
		$conn = null;
}

function editGroup($id, $name) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "UPDATE `voffice`.`groups` SET name = '" . $groupname . "' WHERE groupid = " . $groupid . ";";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			logEntry("Group ID " . $id . " updated to " . $name . ".");
			return "SUCCESS";
		} catch(PDOException $e) {
			return logEntry("editGroup - Connection failed: " . $e->getMessage());
		}
		$conn = null;
}

function setUserOnline($data) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "UPDATE voffice.users SET online = " . $data['online'] . ", lastupdated = CURRENT_TIMESTAMP() WHERE id = " . $data['id'];
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("Set online status to " . $data['online'] . " for user " . $data['id'] . ".");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("setUserOnline - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function getUserData() {
	global $sqlhostname, $sqlusername, $sqlpassword;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT U.id, U.name, U.title, U.icon, U.groupid, U.currentoffice, U.status, U.autostatus, U.online, U.meetingroom, U.meetlink, U.admin,  COUNT(QA.userid2) AS QACount FROM voffice.users AS U LEFT JOIN quickaccess AS QA ON QA.userid = U.id GROUP BY U.id ORDER BY U.name asc";
		$stmt = $conn->query($sql);
		
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if ($output) {
			return json_encode($output);
		}
		
	} catch(PDOException $e) {
		return logEntry("getUserData - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function getUserDiff() {
	global $sqlhostname, $sqlusername, $sqlpassword;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT U.id, U.name, U.title, U.icon, U.groupid, U.currentoffice, U.status, U.autostatus, U.online, U.meetingroom, U.meetlink, U.admin,  COUNT(QA.userid2) AS QACount FROM voffice.users AS U LEFT JOIN quickaccess AS QA ON QA.userid = U.id WHERE lastupdated >= ADDTIME(CURRENT_TIMESTAMP(), '-10') GROUP BY U.id ORDER BY U.name asc";
		$stmt = $conn->query($sql);
		
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if ($output) {
			return json_encode($output);
		}
		
	} catch(PDOException $e) {
		return logEntry("getUserDiff - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function getGroupData() {
	global $sqlhostname, $sqlusername, $sqlpassword;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT * FROM voffice.groups ORDER BY name ASC";
		$stmt = $conn->query($sql);
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($output) {
			return json_encode($output);
		}
	} catch(PDOException $e) {
		return logEntry("getGroupData - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function addGroup($groupname) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	try {
			$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "INSERT INTO `voffice`.`groups` (`name`, `color`) VALUES ('" . $groupname . "', NULL)";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			logEntry("Group " . $groupname . " added successfully.");
			return "SUCCESS";
		} catch(PDOException $e) {
			return logEntry("addGroup - Connection failed: " . $e->getMessage());
		}
		$conn = null;
}

function addUser($data) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "INSERT INTO voffice.users (";
	foreach ($data as $k => $v) {
		if ($k != 'userid' && $k != 'data') {
			$sql .= "`" . $k . "`";
			$sql .= ",";
		}
	}
	$sql .= "lastupdated";
	$sql .= ") VALUES (";
	foreach ($data as $k => $v) {
		if ($k != 'userid' && $k != 'data') {
			$sql .= "'" . $v . "'";
			$sql .= ",";
		}
	}
	$sql .= "CURRENT_TIMESTAMP()";
	$sql .= ")";
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("New user " . $data['name'] . " added.");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("addUser - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function moveUser($uid, $office) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "UPDATE users SET currentoffice = " . $office . ", lastupdated = CURRENT_TIMESTAMP() WHERE id = " . $uid;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("User " . $uid . " moved to office " . $office . ".");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("moveUser - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function deleteAction($pkid) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "DELETE FROM actions WHERE pkid = " . $pkid;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("Action with PKID " . $pkid . " has been deleted.");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("deleteAction - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function createAction($fromuser, $touser, $action, $data) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "";
	switch ($action) {
		case "knock":
			$sql = "INSERT INTO actions (action, fromuser, touser, info, created) VALUES ('knock', " . $fromuser . ", " . $touser . ", CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())";
			break;
		case "rejectmessage":
			$sql = "INSERT INTO actions (action, fromuser, touser, info, created) VALUES ('rejectmessage', " . $fromuser . ", " . $touser . ", '" . $data . "', CURRENT_TIMESTAMP());";
			break;
		case "joinmeet":
			$sql = "INSERT INTO actions (action, fromuser, touser, info, created) VALUES ('joinmeet', " . $fromuser . ", " . $touser . ", '" . $data . "', CURRENT_TIMESTAMP());";
			break;
	}
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("Created action " . $action . " from user " . $fromuser . " to user " . $touser . " with data:  " . $data . ".");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("createAction - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function addQuickAccess($userid, $userid2) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "INSERT INTO quickaccess (userid, userid2) VALUES (" . $userid . ", " . $userid2 . ");";
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("Quick Access added to user " . $userid . " for user " . $userid2 . ".");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("addQuickAccess - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function getQuickAccess($userid) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "SELECT * FROM quickaccess WHERE userid = " . $userid;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->query($sql);
		$output = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($output) {
			return json_encode($output);
		}
		
	} catch(PDOException $e) {
		return logEntry("getQuickAccess - Connection failed: " . $e->getMessage());
	}
	
	$conn = null;
}

function removeQuickAccess($userid, $userid2) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "DELETE FROM quickaccess WHERE userid = " . $userid . " AND userid2 = " . $userid2;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("Quick Access entry removed from user " . $userid . " for user " . $userid2 . ".");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("removeQuickAccess - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function editMeetLink($userid, $newlink) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "UPDATE users SET meetlink = '" . $newlink . "', lastupdated=CURRENT_TIMESTAMP() WHERE id = " . $userid;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		logEntry("Meeting Link updated for user " . $userid . " to '" . $newlink . "'.");
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("editMeetLink - Connection failed: " . $e->getMessage());
	}
	$conn = null;
}

function logoutUser($uid, $forced) {
	global $sqlhostname, $sqlusername, $sqlpassword;
	$sql = "UPDATE users SET online = 0, status = 'offline', lastupdated = CURRENT_TIMESTAMP() WHERE id = " . $uid;
	try {
		$conn = new PDO("mysql:host=$sqlhostname;dbname=voffice", $sqlusername, $sqlpassword);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		if ($forced == '1') {
			logEntry("Forcefully logged out user " . $uid . ".");
		}
		else { logEntry("User " . $uid . " logged out."); }
		return "SUCCESS";
	} catch(PDOException $e) {
		return logEntry("logoutUser - Connection failed: " . $e->getMessage());
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
		return;
	} catch(PDOException $e) {
		return "Connection failed: " . $e->getMessage();
	}
	$conn = null;
}

?>