<?php
/* websocket_util.php


*/

ini_set('display_errors', 1); error_reporting(-1);
include 'sql_settup.php';


if ($_POST['action']=='join_multi') {
	$result = $mysqli->query('SELECT * FROM Sessions WHERE gameId="'.$_POST['sessionId'].'" AND player2 IS NULL LIMIT 1');

    if ($result->num_rows > 0) { // found user waiting for opponent, so join 
        $row = $result->fetch_assoc();

        // add opponent to player2 column in table
        $mysqli->query('UPDATE Sessions SET player2="'.$_POST['username'].'" WHERE id="'.$row['id'].'"');    

        // create array with important values to send back to browser
        // array contents: groupId, opponent
        $groupData = [$row['groupId'], $row['player1']];
        echo json_encode($groupData);
    }
    else { // no one is waiting for opponent, so create new game

        $mysqli->query('INSERT INTO Sessions (groupId, gameId, player1) VALUES ("'.$_POST['groupId'].'", "'.$_POST['sessionId'].'", "'.$_POST['username'].'")');
    }
}
else if ($_POST['action']=='submit_multi') {
	$result = $mysqli->query('SELECT * FROM Sessions WHERE groupId="'.$_POST['groupId'].'" LIMIT 1');
	$row = $result->fetch_assoc();

	if ($row['p1'] == NULL) {
	    $mysqli->query('UPDATE Sessions SET p1="'.$_POST['username'].'", p1Data="'.$_POST['quantity'].'" WHERE id="'.$row['id'].'"');   

	} 
	else {

	    $mysqli->query('UPDATE Sessions SET p1="'.$_POST['username'].'", p1Data="'.$_POST['quantity'].'" WHERE id="'.$row['id'].'"');

	    // send back array with usernames and their respective submission data
	    $submitData = [$row['p1'],$row['p1Data'],$_POST['username'],$_POST['quantity']];
	    echo json_encode($submitData);

	}
}
else if ($_POST['action']=='get_opponent_data') {
	$result = $mysqli->query('SELECT * FROM Sessions WHERE groupId="'.$_POST['groupId'].'" LIMIT 1');
	$row = $result->fetch_assoc();

	$opponentData=[$row['p1'],$row['p1Data']];

	$mysqli->query('UPDATE Sessions SET p1=NULL, p1Data=NULL WHERE id="'.$row['id'].'"');

	echo json_encode($opponentData);
}