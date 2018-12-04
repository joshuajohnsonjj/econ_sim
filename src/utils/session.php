<?php 
/* session.php

- Handles ajax requests related to a particular game session, including loading admin results, toggleing the game session on/off,
entering students to game/ exiting, and handling student submissions.

Last Update:
Joshua Johnson - 8/1/18
Change query in remove_student to look for groupId rather than student AND sessionId
*/

ini_set('display_errors', 1); error_reporting(-1);
require_once "../../../tsugi/config.php";

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::session_start();

$mysqli = new mysqli('localhost', 'root', 'root', 'econ_sim_data');

if (mysqli_connect_error()) {
	die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
}

if (isset($_POST["checkExistance"])) { // Called when student tries to enter game
	// get current status of the "live" column for entered game id
	$result = $mysqli->query('SELECT live, market_struct FROM Games WHERE id="'.$_POST["id"].'" LIMIT 1');
	$row=$result->fetch_assoc();
	if ($row['live'])
		if ($mysqli->query('SELECT complete FROM GameSessionData WHERE player="'.$USER->email.'" LIMIT 1')->fetch_assoc()['complete'])
			header("Location: ../student.php?session=err3"); // player has already completed game for this session
		else
			if ($row['market_struct'] == 'perfect')
				header("Location: ../perfect_game.php?session=".$_POST['id']);
			else if ($row['market_struct'] == 'monopolistic')
				header("Location: ../monopolistic_game.php?session=".$_POST['id']);
			else
				header("Location: ../game_main.php?session=".$_POST['id']);
	else
		header("Location: ../student.php?session=err"); // session doesn't exist (is not toggled on by instuctor)
}

// called when admin starts/stops a session
// set the "live" column in Game table
else if ($_POST['action'] == 'toggle') {
	$toggledOn = false;
	// Get game's live status and change it to opposite of current value
	$result = $mysqli->query('SELECT live, price_hist FROM Games WHERE id='.$_POST["id"].' LIMIT 1');
	if ($result->fetch_assoc()['live']) {
		$mysqli->query("UPDATE Games Set live=0 WHERE id='".$_POST["id"]."'");
		// if going from live to not live, delete data from session
		$mysqli->query("DELETE FROM GameSessionData WHERE gameId=".$_POST['id']);
	}
	else {
		$toggledOn = true;
		$mysqli->query("UPDATE Games Set live=1, price_hist='".$_POST['priceHist']."' WHERE id='".$_POST["id"]."'");
	}
	echo $toggledOn;
}

// save game info to gameSessionData
else if ($_POST['action'] == 'update_gameSessionData') {

	$result = $mysqli->query('SELECT * FROM GameSessionData WHERE groupId="'.$_POST["groupId"].'" AND player="'.$_POST["username"].'"');

	// game session entry for this player exists already, so update it with new submission
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();

		// convert to comma separated string for sql storage - will be split into array by javascript before use
		$quantityHist	= $row['player_quantity'].",".$_POST['quantity'];
		$revenueHist	= $row['player_revenue'].",".$_POST['revenue'];
		$profitHist		= $row['player_profit'].",".$_POST['profit'];
		$returnHist		= $row['player_return'].",".$_POST['percentReturn'];
		$priceHist		= $row['price'].",".$_POST['price'];
		$totalCostHist	= $row['total_cost'].",".$_POST['totalCost'];

		$mysqli->query("UPDATE GameSessionData Set player_quantity='".$quantityHist."', player_revenue='".$revenueHist."', player_profit='".$profitHist."', player_return='".$returnHist."', price='".$priceHist."', total_cost='".$totalCostHist."', complete='".$_POST['complete']."', gameId='".$_POST['gameId']."' WHERE groupId='".$_POST['groupId']."' AND player='".$_POST['username']."'");

	} 
	// this is the student's first submission for this session, so create new row
	else { 
		$mysqli->query("INSERT INTO GameSessionData (groupId, gameId, player, opponent, player_quantity, player_revenue, player_profit, player_return, price, unit_cost, total_cost) VALUES ('".$_POST['groupId']."', '".$_POST['gameId']."', '".$_POST['username']."', '".$_POST['opponent']."', '".$_POST['quantity']."', '".$_POST['revenue']."', '".$_POST['profit']."', '".$_POST['percentReturn']."', '".$_POST['price']."', '".$_POST['unitCost']."', '".$_POST['totalCost']."')");
	}
}

// remove student from GameSessionData 
else if ($_POST['action'] == 'remove_student') {
	$mysqli->query("DELETE FROM GameSessionData WHERE `groupId`='".$_POST['groupId']."'");
	$mysqli->query("DELETE FROM Sessions WHERE `groupId`='".$_POST['groupId']."'");
}

// instructor results page uses this function to grab the session data and display it 
else if ($_POST['action'] == 'retrieve_gameSessionData') {

	$result = $mysqli->query('SELECT player, groupId, '.$_POST['valueType'].' FROM GameSessionData WHERE gameId="'.$_POST["gameId"].'"');

	$data=[];

	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$splitData = array_map('intval', explode(',', $row[$_POST['valueType']]));
			$splitWithName = array('username'=> $row['player'], 'group'=> $row['groupId'], 'data'=> $splitData);
			array_push($data, $splitWithName);
		}
	echo json_encode($data);

	} else { 
		echo "ERROR no match";
	}
}
$mysqli->close();