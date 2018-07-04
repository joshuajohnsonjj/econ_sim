<?php
/*
multiplayer.php

Handles requests sent by multiplayer game.
Often called continuously to detect changes in state.

*/
// require_once "../../tsugi/config.php";

// use \Tsugi\Core\LTIX;

// $LAUNCH = LTIX::session_start();

$mysqli = new mysqli('localhost', 'root', 'root', 'econ_sim_data');


if ($_POST['action'] == 'check') { // checks if opponent has submitted quantity for round
	$result = $mysqli->query("SELECT round_data FROM `Session".$_POST['id']."` WHERE usr='".$_POST['opponent']."'");
	if ($result->num_rows > 0)
		$roundData = $result->fetch_assoc()['round_data'];

	if ($roundData) { // opponent has submited
		echo $roundData;
	} else { //waiting for opponent
		echo NULL;
	}
}
 // if opponent has not yet submited, submit user, then continuously call above code until opponent has submited
else if ($_POST['action'] == 'submitThenWait') { // save user data to round_data and append to all_data

	// fist check to make sure opponent is still in session
	$result = $mysqli->query("SELECT opponent FROM Users WHERE email='".$_POST['email']."'");
	if ($result->fetch_assoc()['opponent'] != NULL) { // opponent is present so continue with game
		$result = $mysqli->query("SELECT all_data FROM `Session".$_POST['id']."` WHERE usr='".$_POST['email']."'");
		if ($result->num_rows > 0) {
			$data = $result->fetch_assoc()['all_data'].",".$_POST['data'];
			$sql = "UPDATE `Session".$_POST['id']."` SET all_data='".$data."', round_data='".(int)$_POST['data']."' WHERE usr='".$_POST['email']."'";
			if ($mysqli->query($sql) === FALSE)
				die("Error, couldn't update database");
		} 
	}
	else { // opponent has left session, so exit game and notify user
		echo "opponentLeft";
	}
}
// both users have now submited, so save the equilibrium along with 2nd user submission
else if ($_POST['action'] == 'bothSubmited') {
	$result = $mysqli->query("SELECT all_data FROM `Session".$_POST['id']."` WHERE usr='".$_POST['email']."'");
	if ($result->num_rows > 0) {
		$data = $result->fetch_assoc()['all_data'].",".$_POST['data']; // append to all_data and set round_data
		$sql = "UPDATE `Session".$_POST['id']."` SET all_data='".$data."', round_data='".(int)$_POST['data']."' WHERE usr='".$_POST['email']."'";
		if ($mysqli->query($sql) === FALSE)
			die("Error, couldn't update database - 1");
	} else 
		die("Error, couldn't update database - 2");
	$mysqli->query("UPDATE Games Set equilibrium='".$_POST['equilibrium']."' WHERE id=".$_POST['id']); // save equilibrium
}
// both users have submited and game screen has been updated so reset both round_data
else if ($_POST['action'] == 'removeData') { 
	if ($mysqli->query("UPDATE `Session".$_POST['id']."` SET round_data=NULL WHERE usr IN ('".$_POST['email']."','".$_SESSION['opponent']."')")===FALSE)
		echo $mysqli->error;
}
else if ($_POST['action'] == 'playerMatch') { // handles finding two players to match together
	$result = $mysqli->query("SELECT in_session, opponent FROM Users WHERE email='".$_POST['email']."'");
	$row = $result->fetch_assoc();

	$_SESSION['opponent'] = $row['opponent'];
	echo json_encode([$row['in_session'], $row['opponent']]);
}

$mysqli->close();