var express = require('express');
var app = express();
var fs = require('fs');
var server = require('http').createServer(app);
var io = require('socket.io')(server);
var port = 8080;

server.listen(port, function(){
  console.log('listening on *:8080');
});

// Entire GameCollection Object holds all games and info
var gameCollection =  new function() {
    this.totalGameCount = 0,
    this.gameList = []
};

// object for individual game sessions.,, formed when admin toggles a game session on
// set id, initialize players and their data to null/empty
// add to the overall game collection
// seperate functions for creating single and multi player games
function buildMultiGame(socket) {
     var multiGameObject = {};
     multiGameObject.groupId = (Math.random()+1).toString(36).slice(2, 18);
     multiGameObject.gameId = socket.gameId;
     multiGameObject.full = false;
     multiGameObject.playerOne = null;
     multiGameObject.playerOneData = [];
     multiGameObject.playerTwo = null;
     multiGameObject.playerTwoData = [];
     // multiGameObject.playerThree = null;
     // multiGameObject.playerThreeData = [];
     gameCollection.totalGameCount ++;
     gameCollection.gameList.push({multiGameObject});

     console.log(multiGameObject);
     return multiGameObject;
}
function buildSingleGame(socket) {
     var singleGameObject = {};
     singleGameObject.groupId = (Math.random()+1).toString(36).slice(2, 18);
     singleGameObject.gameId = socket.gameId;
     singleGameObject.player = null;
     singleGameObject.playerData = [];
     gameCollection.totalGameCount ++;
     gameCollection.gameList.push({singleGameObject});
     console.log(singleGameObject);

     return singleGameObject;
}
// ------------------------------------

io.on('connection', function(socket) { 

    // when admin toggles session on/off. create/destroy game object
    socket.on('toggleGameSession', function(id, mode, sessionRunning) {
        socket.gameId = id;
        socket.gameMode = mode;

        // if sessionRunning is true, toggle off (remove all game objects with that gameId)
        if (sessionRunning) {
            for (var i = 0; i < gameCollection.totalGameCount; i++) {
                if ((gameCollection.gameList[i]['singleGameObject'] && gameCollection.gameList[i]['singleGameObject']['gameId'] == id)||(gameCollection.gameList[i]['multiGameObject'] && gameCollection.gameList[i]['multiGameObject']['gameId'] == id)) {
                    gameCollection.gameList.splice(i--,1);
                    gameCollection.totalGameCount--;
                }
            }
        }

        console.log("Toggling "+mode+" Session...");
        console.log(gameCollection);
    });

    // when student joins a game session (either single or multiplayer)
    socket.on('joinGame', function(joinInfo) {
        var tmpObj, multiPlayerAdded = false;
        socket.gameId = joinInfo['id'];

        // when all existing singleGameObjects are full, create new one and add player
        if (joinInfo['mode'] == 'single') { 
            tmpObj = buildSingleGame(socket);
            tmpObj.player = joinInfo['username'];
            socket.join(tmpObj['groupId']);
        }
        else {
            console.log('Joining multiplayer...');

            // first check to see if any existing multi games are not full
            for (var i = 0; i < gameCollection.totalGameCount; i++) {
                // if a non full multi game exists, add student as player two
                if (gameCollection.gameList[i]['multiGameObject'] && !gameCollection.gameList[i]['multiGameObject'].full) {
                    console.log('student joinging existing multi object\n');
                    tmpObj = gameCollection.gameList[i]['multiGameObject'];
                    tmpObj.playerTwo = joinInfo['username'];
                    tmpObj.full = true;
                    multiPlayerAdded = true;
                    socket.join(tmpObj['groupId']);
                    break;
                }
            }

             // if no non full multi games, build new one and make student player one 
            if (!multiPlayerAdded) {
                console.log('creating new multi object');
                tmpObj = buildMultiGame(socket);
                tmpObj.playerOne = joinInfo['username'];
                socket.join(tmpObj['groupId']);
            }

        }

        console.log(tmpObj);
        console.log(gameCollection);
        io.to(tmpObj['groupId']).emit('studentJoinedGame', tmpObj); // send the generated groupId back to client (student - game_main.php)
    });

    // when student exits game after joining, or hits "cancel" while waiting for opponent
    // inputs - username of student leaving & groupId & game mode (single, multi)
    socket.on('leaveGame', function(user, id) {
        const leavingUser = user;
        // find correct gameObject by passed id and remove the specified user (remove gameObject)
        // in multi mode, one player leaving will also end game for opponent
        // for (var i = 0; i < gameCollection.totalGameCount; i++) {
        //     if ((gameCollection.gameList[i]['singleGameObject'] && gameCollection.gameList[i]['singleGameObject']['groupId']==id)||(gameCollection.gameList[i]['multiGameObject'] && gameCollection.gameList[i]['multiGameObject']['groupId']==id)) {
        //         gameCollection.gameList.splice(i,1);
        //         gameCollection.totalGameCount--;
        //         break;
        //     }
        // }
        console.log(leavingUser+' is leaving!');
        // console.log(gameCollection);
        io.to(id).emit('gameExited', leavingUser);
    });

    // when student sumbits quantity during game
    socket.on('updateData', function(submitData) {
        var tmpObj;
        // find the correct gameObjcet in gameCollection for groupId
        for (var i = 0; i < gameCollection.totalGameCount; i++) {
            // single player submission
            if (submitData['mode'] == 'single' && gameCollection.gameList[i]['singleGameObject']) {
                tmpObj = gameCollection.gameList[i]['singleGameObject'];
                if (tmpObj['groupId'] == submitData['groupId']) {
                    tmpObj['playerData'].push(parseInt(submitData['value'], 10));

                    console.log('emiting single submission...');
                    io.to(tmpObj['groupId']).emit('singleplayerSubmission', submitData['value']);
                    break;
                }
            }
            // multi player submission
            else if (submitData['mode'] == 'multi' && gameCollection.gameList[i]['multiGameObject']) {
                tmpObj = gameCollection.gameList[i]['multiGameObject'];
                if (tmpObj['groupId'] == submitData['groupId']) {
                    // push submission to correct playerData array
                    if (tmpObj['playerOne'] == submitData['username'])
                        tmpObj['playerOneData'].push(parseInt(submitData['value'], 10));
                    else
                        tmpObj['playerTwoData'].push(parseInt(submitData['value'], 10));

                    io.to(tmpObj['groupId']).emit('multiplayerSubmission', {
                        username: submitData['username'],
                        id: tmpObj['groupId'],
                        p1: tmpObj['playerOne'],
                        p1Data: tmpObj['playerOneData'],
                        p2: tmpObj['playerTwo'],
                        p2Data: tmpObj['playerTwoData']
                    });
                    break;
                }
            }
        }

        // emit the game collection to the results page for display to instructor
        // only emit if single mode, or if multi mode and both players have submitted
        if (submitData['mode'] == 'single' || (submitData['mode'] == 'multi' && tmpObj['playerOneData'].length == tmpObj['playerTwoData'].length))
            io.emit('studentSubmitedQuantity', gameCollection);
        console.log("player data was updated");
        
    });

    // when admin initialy loads results page, calls this func to return data
    socket.on('instructorGrabData', function() {
        io.emit('studentSubmitedQuantity', gameCollection);
    });

    // if student refreshes page during game, this is automatically called to rejoin room
    socket.on('gameRefreshed', function(id) {
        socket.join(id);
    });

});

