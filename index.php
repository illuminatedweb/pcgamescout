<?php
function save($mysqli, $title, $date, $email) {
	if (is_null($mysqli)) {
		return "database connection not found";
	}
	if (mysqli_connect_errno()) {
		return "database connection failed";
	}
	$query = "INSERT INTO alerts(title, email, date) VALUES ('";
	$query = $query . mysqli_real_escape_string ($mysqli, $title) . "', '";
	$query = $query . mysqli_real_escape_string ($mysqli, $email) . "', ";
	$query = $query . "STR_TO_DATE('" . mysqli_real_escape_string ($mysqli, $date) . "', '%m/%d/%Y'))";
	if (!mysqli_query($mysqli, $query)) {
		//return mysqli_error($mysqli) . " || " . $query;
		return "error saving, you may already be subscribed";
	}
	return "";
}
function subscribe($mysqli, $email) {
	if (is_null($mysqli)) {
		return "database connection not found";
	}
	if (mysqli_connect_errno()) {
		return "database connection failed";
	}
	$query = "INSERT INTO subscribers(newsletter, email) VALUES (TRUE, '";
	$query = $query . mysqli_real_escape_string ($mysqli, $email) . "')";
	if (!mysqli_query($mysqli, $query)) {
		//return mysqli_error($mysqli) . " || " . $query;
		return "that email address is already subscribed to updates";
	}
	return "";
}
function isValidDate($str) {
	$date = date_parse($str);
	if ($date["error_count"] == 0 && checkdate($date["month"], $date["day"], $date["year"])) {
		return true;
	}
	return false;
}
function isValidEmail($email) {
	// First, we check that there's one @ symbol, 
	// and that the lengths are right.
	if (!@ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
		// Email invalid because wrong number of characters 
		// in one section or wrong number of @ symbols.
		return false;
	}
	// Split it into sections to make life easier
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if
			(!@ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&
↪'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
					$local_array[$i])) {
			return false;
		}
	}
	// Check if domain is IP. If not, 
	// it should be valid domain name
	if (!@ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if
				(!@ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
↪([A-Za-z0-9]+))$",
						$domain_array[$i])) {
				return false;
			}
		}
	}
	return true;
}
$mysqli = new mysqli("localhost", "pcgame_dev", "MseE1xDd83eE1vame", "pcgame_scout");
$errors = array();
$title = '';
$date = '';
$email = '';
$subscribe = false;
$saved = false;
$subscribed = false;
if (isset($_GET['subscribe'])) {
	$subscribe = true;
}
if (isset($_GET['title'])) {
	$title = $_GET['title'];
	// only show missing title error if they're not subscribing
	if ($title == '' && !$subscribe) {
		$errors['title'] = 'title is required';	
	}
}
if (isset($_GET['date'])) {
	$date = $_GET['date'];
	if (!$subscribed) {	
		if ($date == '') {
			// only show missing date error if they're not subscribing
			if (!$subscribe) {
				$errors['date'] = 'date is required';
			}
		}
		else if (!isValidDate($date)) {
			$errors['date'] = 'invalid date';	
		}
	}
}
if (isset($_GET['email'])) {
	$email = $_GET['email'];
	if ($email == '') {
		$errors['email'] = 'email is required';
	}
	else if (!isValidEmail($email)) {
		$errors['email'] = 'invalid email';
	}	
}
// are we saving a game alert?
if ($title != '' && $date != '' && $email != '') {
	if (count($errors) == 0) {
		$errorSaving = save($mysqli, $title, $date, $email);
		if ($errorSaving != "") {
			$errors['save'] = $errorSaving;
		}
		else {
			$saved = true;
		}
	}
}
// are we subscribing an email address?
if ($email != '' && $subscribe) {
	$errorSubscribing = subscribe($mysqli, $email);
	if ($errorSubscribing) {
		// only show subscribe error if we didn't just save a game alert
		if (!$saved) {
			$errors['subscribe'] = $errorSubscribing;
		}
	}
	else {
		$subscribed = true;
	}
}
?>
<!DOCTYPE html>
<head>
	<title>PC Game Release Alerts - pcgamescout.com</title>
	<link href="/css/normalize.css" rel="stylesheet" type="text/css"/>
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css" />
	<link href="/css/styles.css" rel="stylesheet" type="text/css"/>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>
	<script>
		$(function() {
			$('#date').datepicker();
		});
	</script>
</head>
<body>
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	  ga('create', 'UA-30792454-3', 'auto');
	  ga('send', 'pageview');
	</script>
	<div class="outer">
		<div class="inner">
			<a href="/" class="logo"><img src="/img/logo.png"/></a>
			<span class="version">v0.1 (alpha)</span>
			<?php if ($saved) { ?>
				<p>All set!<br />We'll send you an email on <?php echo htmlspecialchars($date); ?> that <?php echo htmlspecialchars($title); ?> has been released.</p>
			<?php } else if ($subscribed) { ?>
				<p>Thanks!<br />We'll keep you updated on any pc game scout updates.</p>
			<?php } else { ?>
				<h1>PC Game Release Alerts</h1>
				<p>coming soon: auto-completion for game titles and release dates</p>
				<form method="get">
					<?php if (count($errors) > 0) {
						?><ul class="errors"><?php
						foreach ($errors as $error) {
							?><li><?php echo htmlspecialchars($error); ?></li><?php
						}
						?></ul><?php
					} ?>
					<fieldset>
						<input 
							   type="text" 
							   class="<?php if ($errors['title'] != '') { echo 'error'; } ?>" 
							   name="title" 
							   placeholder="pc game title" 
							   value="<?php echo htmlspecialchars($title); ?>"
							   />
						<input 
							   type="text" 
							   class="<?php if ($errors['date'] != '') { echo 'error'; } ?>" 
							   name="date" 
							   placeholder="release date" 
							   value="<?php echo htmlspecialchars($date); ?>"
							   id="date"
							   />
					</fieldset>
					<fieldset>
						<input 
							   type="email" 
							   class="<?php if ($errors['email'] != '') { echo 'error'; } ?>" 
							   name="email" 
							   placeholder="your email address" 
							   value="<?php echo htmlspecialchars($email); ?>"
							   />
					</fieldset>
					<fieldset class="subscribe">
						<input type="checkbox" name="subscribe" id="subscribe" checked="checked" />
						<label for="subscribe">subscribe to pc game scout updates</label>
					</fieldset>
					<fieldset class="alert">
						<input type="submit" class="button" value="Alert Me"/>
					</fieldset>
				</form>
			<?php } ?>
		</div>
	</div>
</body>
</html>