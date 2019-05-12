<?php
/* websocket_util.php


*/

ini_set('display_errors', 1); error_reporting(-1);
include 'sql_settup.php';
require_once "../../../tsugi/config.php";
require_once('../../dao/QW_DAO.php');

use \Tsugi\Core\LTIX;
use \QW\DAO\QW_DAO;

$LAUNCH = LTIX::session_start();

$p = $CFG->dbprefix;
$QW_DAO = new QW_DAO($PDOX, $p);

if ($_POST['action']=='join_multi') {
	$response = $QW_DAO->joinMultiplayerGame($_POST['sessionId'],$_POST['username'],$_POST['groupId']);

	if ($response)
		echo json_encode($response);

	// $result = $mysqli->query('SELECT * FROM Sessions WHERE gameId="'.$_POST['sessionId'].'" AND player2 IS NULL LIMIT 1');

 //    if ($result->num_rows > 0) { // found user waiting for opponent, so join 
 //        $row = $result->fetch_assoc();

 //        // add opponent to player2 column in table
 //        $mysqli->query('UPDATE Sessions SET player2="'.$_POST['username'].'" WHERE id="'.$row['id'].'"');    

 //        // create array with important values to send back to browser
 //        // array contents: groupId, opponent
 //        $groupData = [$row['groupId'], $row['player1']];
 //        echo json_encode($groupData);
 //    }
 //    else { // no one is waiting for opponent, so create new game

 //        $mysqli->query('INSERT INTO Sessions (groupId, gameId, player1) VALUES ("'.$_POST['groupId'].'", "'.$_POST['sessionId'].'", "'.$_POST['username'].'")');
 //    }
}
else if ($_POST['action']=='submit_multi') {
	$response = $QW_DAO->multiplayerSubmission($_POST['groupId'],$_POST['username'],$_POST['quantity']);
	// $result = $mysqli->query('SELECT * FROM Sessions WHERE groupId="'.$_POST['groupId'].'" LIMIT 1');
	// $row = $result->fetch_assoc();

	// if ($row['p1'] == NULL) {
	//     $mysqli->query('UPDATE Sessions SET p1="'.$_POST['username'].'", p1Data="'.$_POST['quantity'].'" WHERE id="'.$row['id'].'"');   

	// } 
	// else {

	//     $mysqli->query('UPDATE Sessions SET p1="'.$_POST['username'].'", p1Data="'.$_POST['quantity'].'" WHERE id="'.$row['id'].'"');

	//     // send back array with usernames and their respective submission data
	//     $submitData = [$row['p1'],$row['p1Data'],$_POST['username'],$_POST['quantity']];
	//     echo json_encode($submitData);

	// }

	if ($response)
		echo $response;
}
else if ($_POST['action']=='get_opponent_data') {
	$response = $QW_DAO->getOpponentData($_POST['groupId']);

	// $result = $mysqli->query('SELECT * FROM Sessions WHERE groupId="'.$_POST['groupId'].'" LIMIT 1');
	// $row = $result->fetch_assoc();

	// $opponentData=[$row['p1'],$row['p1Data']];

	// $mysqli->query('UPDATE Sessions SET p1=NULL, p1Data=NULL WHERE id="'.$row['id'].'"');

	echo $response;
}