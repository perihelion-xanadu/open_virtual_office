


<!DOCTYPE HTML>
<html>
<head>

</head>
<body style="position: absolute; top: 0; left: 0; bottom: 0; right: 0; margin: 0; padding: 0;">
	<div class='container' style="position: relative; height: 100%; width: 100%; text-align: center; display: flex; flex-direction: column; align-items: center; ">
		<h2 class='pagetitle' style="margin-top: 25%;">You have been logged out.</h2>
		<button onclick="login();" style="flex-grow: 0;">Log back in</button>
	</div>
	<div id="userid" style="display: none;">
		<?php
			echo $_GET['userid'];
		?>
	</div>
	<script>
		function login() {
			var mainwindow = window.open("index.php?login=" + document.getElementById("userid").innerHTML, "_self");
		//	window.setTimeout(mainwindow.location.reload(), 1000);
		}
	</script>
</body>
</html>