# Simulator App
This program is a web based simulator app currently designed to be an educational aid for economics courses, with the potential for future additions to support other business related courses.

## Overview
The program currently supports the 4 market structures: Monopoly, Oligopoly, Monopolistic Competition, Perfect Competition.

Monopoly mode is a single player game in which students have one input option (production output), which entirely determines the outcome. Oligopoly is a 2-player game. Students are paired with each other as they enter the game, and they each make one decision (production output), which determines the outcome. Monopolistic Competition is single player, but has several difficulty options which determine how many decisions a student will make. Perfect Competition is single player and has two difficulty options.

Instructors have dedicated UI for creating games, starting/stopping sessions, and viewing real time results as a game session ensues.  

## Files
An outline of the files necessary to understand the essential functionality of the application.
#### File Map: 
- Begin index.php
	- Instructors to src/admin_page.php
		- To src/results.php
	- Students to student.php
		- Monopoly and Oligopoly market structures to src/game_main.php
		- Monopolistic Competition to src/monopolistic_game.php
		- Perfect Competition to src/perfect_game.php
#### File Descriptions:
* _index.php_ &rarr; Initially gets LTI info and redirects user to appropriate UI based on student/instructor status
* _src/socket/start.php_ &rarr; script called to start server for socket
* _src/socket/start_io.php_ &rarr; handles backend of socket set up - listens for emissions from client
* _src/socket/start_web.io_ &rarr; Starts up server
* _src/utils/add_course.php_ &rarr; Handles mysql queries for adding/deleting a course on instructor side
* _src/utils/game_util.php_ &rarr; Handles mysql queries for adding/deleting/updating a game inside a course on instructor side
* _src/utils/session.php_ &rarr; Handles mysql queries related to a game session, including trying to enter a game, toggling game on/offline (instructor side), updating session data when student submits for a round, removing a student if he/she quits, and retrieving the session game data for instructor's results page
* _src/utils/sql_settup.php_ &rarr; Contains queries to initially set up the necessary mysql tables, as well as several general helper functions to return certain data from these tables
* _src/admin_page.php_ &rarr; Contains UI for the instructor side. Starts in course view, allows for selecting saved course or creating new one.&rarr;Goes to games view, displaying saved games inside of a given course and allows for creation of new game as well as deletion of the parent course which will also delete all games in course&rarr;Goes to individual game view, containing options to start/stop session, view results, edit game, and delete game
* _src/game_main.php_ &rarr; Contains set up for monopoly and oligopoly games. Uses ajax to get/send info to mysql tables. Oligopoly utilizes socket io to communicate with opponent
* _src/monopolistic_game.php_ &rarr; Contains set up for monopolistic competition games
* _src/perfect_game.php_ &rarr; Set up for perfect competition game
* _src/results.php_ &rarr; Displays results from current game session to instructor in real time. Has tab to display average values of all students from each year on chart. Has tab to display individual values on table, with ability to select up to 2 students to compare graphically
* _src/student.php_ &rarr; Initial page student will see. All they can do from here is enter session id given to them by instructor in order to redirect to game screen



## Libraries/Frameworks Utilized
Several libraries and frameworks were utilized for some of the specialized features of the application:
* [Chartjs](https://www.chartjs.org/) - Javascript library used for creating the numerous responsive graphs throughout the gameplay as well as on the instructor results page
* [Datatables](https://datatables.net/) - A JQuery Javascript library utilized to create the table displaying individual student game data on the instructor results page
* [Alertifyjs](https://alertifyjs.com/) - A Javascript library used to provide notifications for certain error events related to gameplay or joining/leaving game sessions
* [Animate.css](https://daneden.github.io/animate.css/) - CSS animation library used for various embellishments throughout, i.e. reveal animations for graphs
* [Socket.io](https://socket.io/) - Javascript library used for bi-directional communication to enable multiplayer and real-time updates for instructor. Implemented in php as based on [this](https://github.com/walkor/phpsocket.io) repository.
* [Foundation](https://foundation.zurb.com/sites/docs/kitchen-sink.html#0) - The framework used as basis for front end design
* [Tsugi](https://tsugi.org/) - Framework used for LTI connection
