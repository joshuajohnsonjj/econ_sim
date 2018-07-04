<?php
/*
index.php

Contains initial login form. On student login, index.php displays "enter session id" screen.
On admin login, redirects to admin_page.php
*/
ini_set('display_errors', 1); error_reporting(-1); 
include 'utils/sql_settup.php';
require_once "../../tsugi/config.php";

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::session_start();

if ($USER->instructor)
	header("Location: ..");

?>

<!doctype html>
<html class="no-js" lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Econ Sims</title>
    <link rel="stylesheet" href="../css/foundation.css">
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/css/themes/default.min.css"/>
  </head>
  <body style="background-color: #d3f6ff;">
  	<!-- hidden input containing session data -->
  	<input type="hidden" id="currentUser" value="<?= isset($_SESSION['username']) ? $_SESSION['username'] : NULL ?>">

  	<!-- TITLE BAR -->
  	<div class="title-bar" style="background-color: #0a4c6d">
	  <div class="title-bar-left">
	  	<div class="media-object" style="float: left;">
		    <div class="thumbnail" style="margin: 0; border: none;">
		      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
		    </div>
		</div>
	    <span class="title-bar-title">
	    	<h3 style="margin: 30px; font-weight: 500;">Economics Simulations</h3>
	    </span>
	  </div>
	  <div class="title-bar-right">
	  		<img src="../assets/img/default_usr_img.jpeg" style="height: 40px; border-radius: 18px; float: right;">
	  		<p style="margin-top: 10px; padding-right: 50px">Logged in as: <?= $USER->displayname ?></p>
	  </div>
	</div>
	<!-- end title bar -->

	<!-- MAIN CONTENT -->
	<div id="mainContent">
		<h3 style="margin: 80px auto auto; width: 300px; text-align: center; padding-bottom: 50px;">
			<b style="font-weight: 500">Welcome, Student!</b>
		</h3>
		<div style="margin: 0 auto auto; width: 500px">
			<h5 style="font-weight: 300">Enter Session ID to Begin: </h5>
			<div class="input-group">
			  <span class="input-group-label"><i class="fas fa-tag"></i></i></span>
			  <input id="gameIdInput" class="input-group-field" type="number">
			  <div class="input-group-button">
			    <button id="joinButton" type="button" class="button" onclick="enterGame()">
			    	<strong>Join Game </strong><i class="far fa-play-circle"></i>
			    </button>
			  </div> 
			</div>
		</div>
	</div>


	<!-- Bottom bar -->
	<footer class="footer"></footer>

	<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script>
    <script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script type="text/javascript">

    	if (window.location.search.includes("session=err2")) {
			alertify.set('notifier','delay', 4);
			alertify.set('notifier','position', 'top-center');
			alertify.error('<i class="fas fa-exclamation-triangle"></i>  Your Opponent Quit!');
    	}
    	else if (window.location.search.includes("session=err")) {
			alertify.set('notifier','delay', 4);
			alertify.set('notifier','position', 'top-center');
			alertify.error('<i class="fas fa-exclamation-triangle"></i>  Error! Session Doesn\'t Exist.');
    	}

    	function enterGame() {
    		var form = document.createElement("form");
		    form.setAttribute("method", "post");
		    form.setAttribute("action", "utils/session.php");

		    var params = {"id":$("#gameIdInput").val(), "checkExistance":"yes"};

		    for (var key in params) {
	            var hiddenField = document.createElement("input");
	            hiddenField.setAttribute("type", "hidden");
	            hiddenField.setAttribute("name", key);
	            hiddenField.setAttribute("value", params[key]);
			    form.appendChild(hiddenField);
			}

		    document.body.appendChild(form);
		    form.submit();
    	}

    </script>

  </body>

  <style type="text/css">
  	html, body {
  		height: 100%;
  		margin: 0;
  	}
  	a {
  		color: inherit;
  	}
  	a:hover {
  		color: inherit;
  	}
  	.game_options_content {
  		display: block;
  		text-align: center;
  		margin-top: 20%;
  	}
  	.card {
  		color: #fff;
  		height: 400px;
  		border-radius: 20px;
  	}
  	.cell {
  		height: 400px;
  	}
  	.mode-cell {
  		height: 150px;
  		border-radius: 20px;
  		color: #fff;
  		margin-left: auto;
  		margin-right: auto;
  	}
  	.mode_options_content {
  		text-align: center;
  		margin-top: 16%;
  	}
	.grid-container { filter: blur(10px); }
	#mainContent { min-height: calc(100vh - 319px); }
	.footer {
		background-color: #0a4c6d;
		height: 100px;
	}
	.footer, .push {
		height: 100px;
	}
	#login_modal {
		outline: none;
	}
  </style>
</html>