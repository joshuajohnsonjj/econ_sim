<?php
/* index.php

- Simply redirects to proper UI based on instructor/student status.

*/
include "src/utils/sql_settup.php";
require_once "../tsugi/config.php";

use \Tsugi\Core\LTIX;
// use Tsugi\Core\WebSocket;

$LAUNCH = LTIX::session_start();

// Render view
$OUTPUT->header();
$OUTPUT->bodyStart();


// check if user is a student or instructor
if ( !$USER->instructor ) // redirect to student UI
    header("Location: src/student.php"); 
 else  // if instructor redirect to admin UI
    header("Location: src/admin_page.php");

// if (! WebSocket::enabled() ) 
// 	echo "web sockets not enables";
// else {
// 	echo "enabled";
// 	echo '<br><br><form action="#" id="messageForm">
//   <input type="text" size="80" name="message" placeholder="Message...">
//   <input type="submit" value="Send">
// </form>';
// }


$OUTPUT->footerStart();
?>



<script type="text/javascript">
	
// global_web_socket = tsugiNotifySocket("place_42");
// open_worked = false;

// if ( global_web_socket ) {
//     // We ony know if the open worked when this function
//     // is called so we show the send form here
//     global_web_socket.onopen = function(evt) {
//         console.log('Web socket available');
//         open_worked = true;
//     }
//     // Register close function
//     global_web_socket.onclose = function(evt) {
//         if ( open_worked ) {
//             console.log('Websocket has closed');
//         } else {
//             console.log('Websocket open failed');
//         }
//     }
//     global_web_socket.onmessage = function(evt) {
//         console.log('RECEIVE: ' + evt.data);
//     };
// } else {
//     console.log('Could not get a notification socket');
// }

// $( "#messageForm" ).submit(function( event ) {
//     // Stop form from submitting normally
//     event.preventDefault();

//     if ( global_web_socket && global_web_socket.readyState == global_web_socket.OPEN ) {
//         var form = $( this )
//         var message = form.find( "input[name='message']" ).val();
//         global_web_socket.send(message);
//     	console.log('sent message');
//     } 
//     form.find( "input[name='message']" ).val('');
// });

</script>

<?php
$OUTPUT->footerEnd();