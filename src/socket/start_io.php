<?php
/* start_io.php

- socket.io logic
- handles server side of all bidirectional communiaction with clients

Last Update:
*/
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use PHPSocketIO\SocketIO;

// composer autoload
require_once __DIR__ . '/../../vendor/autoload.php';

$io = new SocketIO(2020);

$io->on('connection', function($socket) {

    // when student joins a game session (either single or multiplayer)
    $socket->on('joinGame', function($joinInfo) use($socket) {
        // global $gameCollection; 
        global $io;

        // tmpObj will tempirarily hold the object created by the build game funcitons
        $tmpObj = new stdClass();; //$multiPlayerAdded = false;
        $groupId = substr(((string)rand()+1),2,18);

        // if single game, create new singleGameObject and add player
        if ($joinInfo['mode'] == 'single') {
            $tmpObj->groupId = $groupId;

            // use join to enter unique room for the game session
            // this allows events for different games running at the same time to be kept seperate
            $socket->join($tmpObj->groupId);

            // send the generated groupId back to client (student - game_main.php)
            $io->to($tmpObj->groupId)->emit('studentJoinedGame', $tmpObj); 
        }
        else { // join multiplayer game
            $mysqli = new mysqli('127.0.0.1', 'root', 'root', 'econ_sim_data');
            $result = $mysqli->query('SELECT * FROM Sessions WHERE gameId="'.$joinInfo['id'].'" AND player2 IS NULL LIMIT 1');

            if ($result->num_rows > 0) { // found session waiting for opponent, so join it
                $row = $result->fetch_assoc();
                $groupId = $row['groupId'];

                // set object with relavent data to send back to client
                $tmpObj->groupId = $groupId;
                $tmpObj->playerOne = $row['player1']; 
                $tmpObj->playerTwo = $joinInfo['username'];
                $tmpObj->full = true;

                // join the socket to same room as opponent
                $socket->join($tmpObj->groupId);

                // add opponent to player2 column in table
                $mysqli->query('UPDATE Sessions SET player2="'.$joinInfo['username'].'" WHERE id="'.$row['id'].'"');    

                // send the generated groupId back to client
                $io->to($tmpObj->groupId)->emit('studentJoinedGame', $tmpObj); 
            }
            else { // no one is waiting for opponent

                $socket->join($groupId);
                $tmpObj->full=false;

                $mysqli->query('INSERT INTO Sessions (groupId, gameId, player1) VALUES ("'.$groupId.'", "'.$joinInfo['id'].'", "'.$joinInfo['username'].'")');
            }

            $mysqli->close();
        }

        
    });

    // when student exits game after joining/hits "cancel" while waiting for opponent/ refreshes or closes tab/browser
    // inputs - username of student leaving & groupId
    $socket->on('leaveGame', function($user, $id) use($socket) {
        global $io;
        $io->to($id)->emit('gameExited', $user);
    });

    // rejoin room on refresh and then exit
    // must rejoin room first so that client will recieve message to exit game
    $socket->on('refresh', function($id, $user) use($socket) {
        global $io;
        $socket->join($id);
        $io->to($id)->emit('gameExited', $user);
    });

    // when student sumbits quantity during game
    $socket->on('updateData', function($submitData) use($socket) {
        global $io;
        $tmpObj = new stdClass;
        $tmpObj->bothSubmitted=false;
               
        if ($submitData['mode'] == 'single') {
             $io->to($submitData['groupId'])->emit('singleplayerSubmission', $submitData['value']);
        }
        else {
             $mysqli = new mysqli('127.0.0.1', 'root', 'root', 'econ_sim_data');
             $result = $mysqli->query('SELECT * FROM Sessions WHERE groupId="'.$submitData['groupId'].'" LIMIT 1');
             $row = $result->fetch_assoc();

            if ($row['p1'] == NULL) {
                $mysqli->query('UPDATE Sessions SET p1="'.$submitData['username'].'", p1Data="'.$submitData['value'].'" WHERE id="'.$row['id'].'"');   
                $tmpObj->p1=$submitData['username'];
                $io->to($submitData['groupId'])->emit('multiplayerSubmission', $tmpObj);
            } 
            else {
                $mysqli->query('UPDATE Sessions SET p1=NULL, p1Data=NULL WHERE id="'.$row['id'].'"');

                $tmpObj->bothSubmitted=true;
                $tmpObj->p1=$row['p1'];
                $tmpObj->p1Data=(int)$row['p1Data'];
                $tmpObj->p2=$submitData['username'];
                $tmpObj->p2Data=(int)$submitData['value'];

                $io->to($submitData['groupId'])->emit('multiplayerSubmission', $tmpObj);
            }
            $mysqli->close();
        }
        
    });

    // submissions from perfect competition games
    $socket->on('updateDataPerfect', function($submitData) use($socket) {
        global $io;
        $io->to($submitData['groupId'])->emit('perfectSubmission', $submitData);
    });
    // submissions from monopolisticly competetive games
    $socket->on('updateDataMonopComp', function($submitData) use($socket) {
        global $io;
        $io->to($submitData['groupId'])->emit('monopCompSubmission', $submitData);
    });

    // student client emits this message once it computes all values after submission and uses ajax to send to mysql
    $socket->on('studentSubmitedQuantity', function() use($socket) {
        global $io;
        // instructor client listens for this message to make ajax call to grab submission data from mysql
        $io->emit('studentSubmitedQuantity');
    });

    // when admin initialy loads results page, calls this func to return data
    $socket->on('instructorGrabData', function() use($socket) {
        global $io;
        // instructor client listens for this message to make ajax call to grab submission data from mysql
        $io->emit('studentSubmitedQuantity');
    });
   
});

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}