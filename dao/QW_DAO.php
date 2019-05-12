<?php
namespace QW\DAO;

class QW_DAO {

    private $PDOX;
    private $p;

    public function __construct($PDOX, $p) {
        $this->PDOX = $PDOX;
        $this->p = $p;
    }

    function addCourse($admin, $courseName, $section, $icon) {
        $query = "INSERT INTO {$this->p}courses (name, section, owner, avatar) VALUES (:name, :section, :owner, :avatar)";
        $arr = array(':name' => $admin, ':section' => $section, ':owner' => $admin, ':avatar' => $icon);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteCourse($id) {
        $query = "DELETE FROM {$this->p}courses WHERE id = :id";
        $arr = array(':id' => $id);
        $this->PDOX->queryDie($query, $arr);

        $query = "DELETE FROM {$this->p}games WHERE course_id = :id";
        $this->PDOX->rowDie($query, $arr);
    }

    function saveEquilibrium($id, $eq) {
        $query = "UPDATE {$this->p}games SET equilibrium = :eq WHERE id = :id";
        $arr = array(':id' => $id, ':eq' => $eq);
        $this->PDOX->queryDie($query, $arr);
    }

    function addGame($name, $diff, $mode, $market_struct, $macro_econ, $limit, $numRounds, $intercept, $slope, $fixed, $cons, $maxq) {
        $query = "INSERT INTO {$this->p}games (name, difficulty, mode, market_struct, macro_econ, time_limit, num_rounds. demand_intercept, demand_slope, fixed_cost, const_cost, max_quantity) VALUES (:name, :diff, :mode, :market, :macro_econ, :lim, :rounds, :intercept, :slope, :fixed, :const_cost, :max)";
        $arr = array(':name'=>$name, ':diff'=>$diff, ':mode'=>$mode, ':market'=>$market_struct, ':macro_econ'=>$macro_econ, ':lim'=>$limit, ':rounds'=>$numRounds, ':intercept'=>$intercept, ':slope'=>$slope, ':fixed'=>$fixed, ':const_cost'=>$cons, ':max'=>$maxq);
        $this->PDOX->queryDie($query, $arr);
    }

    function updateGame($name, $diff, $mode, $market_struct, $macro_econ, $limit, $numRounds, $intercept, $slope, $fixed, $cons, $maxq, $id) {
        $query = "UPDATE {$this->p}games SET name = :name, difficulty = :diff, mode = :mode, market_struct = :market, macro_econ = :macro_econ, time_limit = :lim, num_rounds = :rounds, demand_intercept = :intercept, demand_slope = :slope, fixed_cost = :fixed, const_cost = :const_cost, max_quantity = :max WHERE course_id = :id";
        $arr = array(':name'=>$name, ':diff'=>$diff, ':mode'=>$mode, ':market'=>$market_struct, ':macro_econ'=>$macro_econ, ':lim'=>$limit, ':rounds'=>$numRounds, ':intercept'=>$intercept, ':slope'=>$slope, ':fixed'=>$fixed, ':const_cost'=>$cons, ':max'=>$maxq, ':id'=>$id);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteGame($id) {
        $query = "DELETE FROM {$this->p}games WHERE id = :id";
        $arr = array(':id' => $id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getPriceHist($id) {
        $query = "SELECT price_hist FROM {$this->p}games WHERE id = :id;";
        $arr = array(':id' => $id);
        return $this->PDOX->rowDie($query, $arr);
    }
 
    function gameExists($id) {
        $query = "SELECT live, market_struct FROM {$this->p}games WHERE id = :id;";
        $arr = array(':id' => $id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function playerCompletedGame($player) { 
        $query = "SELECT complete FROM {$this->p}gameSessionData WHERE player = :player LIMIT 1;";
        $arr = array(':player' => $player);
        return $this->PDOX->rowDie($query, $arr)["complete"];
    }

    function toggleSession($toggledOn, $id, $priceHist) {
        $query = "SELECT live, price_hist FROM {$this->p}games WHERE id = :id  LIMIT 1;";
        $arr = array(':id' => $id);
        if ($this->PDOX->rowDie($query, $arr)["live"]) {
            $query = "UPDATE {$this->p}games SET live = 0 WHERE id = :id";
            $this->PDOX->rowDie($query, $arr);
            $query = "DELETE FROM {$this->p}gameSessionData WHERE gameId = :id";
            $this->PDOX->rowDie($query, $arr);
        } else {
            $toggledOn=false;
            $query = "UPDATE {$this->p}games SET live = 1, price_hist = :hist WHERE id = :id";
            $arr = array(':id' => $id, ':hist'=>$priceHist);
            $this->PDOX->rowDie($query, $arr);
        }
        return $toggledOn;
    }

    function updateGameSessionData($groupId,$username,$quantity,$revenue,$profit,$percentReturn,$price,$totalCost,$complete,$gameId,$opponent,$unitCost) {
        $query = "SELECT * FROM {$this->p}gameSessionData WHERE groupId = :grpId AND player = :usr;";
        $arr = array(':grpId' => $groupId, ':usr'=>$username);
        $data = $this->PDOX->rowDie($query, $arr);

        if ($data) {
            $quantityHist   = $data['player_quantity'].",".$quantity;
            $revenueHist    = $data['player_revenue'].",".$revenue;
            $profitHist     = $data['player_profit'].",".$profit;
            $returnHist     = $data['player_return'].",".$percentReturn;
            $priceHist      = $data['price'].",".$price;
            $totalCostHist  = $data['total_cost'].",".$totalCost;

            $query = "UPDATE {$this->p}gameSessionData SET player_quantity = :quantity, player_revenue = :revenue, player_profit = :profit, player_return = :return, price = :price, total_cost = :cost, complete = :complete, gameId = :gmId  WHERE groupId = :grpId AND player = :usr";
            $arr = array(':grpId' => $groupId, ':usr'=>$username, ':quantity'=>$quantity, ':revenue'=>$revenue, ':profit'=>$profit, ':return'=>$percentReturn, ':price'=> $price, ':cost'=>$totalCost, ':complete'=>$complete,':gmId'=>$gameId);
            $this->PDOX->rowDie($query, $arr);
        } else {
            $query = "INSERT INTO {$this->p}gameSessionData (groupId, gameId, player, opponent, player_quantity, player_revenue, player_profit, player_return, price, unit_cost, total_cost) VALUES (:grpId, :gmId, :usr, :opp, :quantity, :revenue, :profit, :return, :price, :unitCost, :ttlCost)";
            $arr = array(':grpId' => $groupId, ':usr'=>$username ':opp'=>$opponent, ':quantity'=>$quantity, ':revenue'=>$revenue, ':profit'=>$profit, ':return'=>$percentReturn, ':price'=> $price, ':unitCost'=>$unitCost, ':ttlCost'=>$totalCost, ':complete'=>$complete,':gmId'=>$gameId);
            $this->PDOX->rowDie($query, $arr);
        }
    }

    function removeFromSession($id) {
        $query = "DELETE FROM {$this->p}gameSessionData WHERE groupId = :id";
        $arr = array(':id' => $id);
        $this->PDOX->queryDie($query, $arr);

        $query = "DELETE FROM {$this->p}sessions WHERE groupId = :id";
        $this->PDOX->queryDie($query, $arr);
    }

    function retrieveSessionData($val, $gameId, $groupId, $usr) {
        $query = "SELECT player, groupId, :val FROM {$this->p}gameSessionData WHERE gameId = :gmId;";
        $arr = array(':gmId' => $gameId);
        
        $data=[];
        foreach ($this->PDOX->rowDie($query, $arr) as $row) {
            $splitData = array_map('intval', explode(',', $row[$_POST['valueType']]));
            $splitWithName = array('username'=> $row['player'], 'group'=> $row['groupId'], 'data'=> $splitData);
            array_push($data, $splitWithName);
        }

        return json_encode($data);
    }

    function joinMultiplayerGame($sessionId,$username,$groupId) {
        $query = "SELECT * FROM {$this->p}sessions WHERE gameId = :gmId AND player2 IS NULL LIMIT 1;";
        $arr = array(':gmId' => $sessionId);
        $game = $this->PDOX->rowDie($query, $arr);

        if ($game) {
            $query = "UPDATE {$this->p}sessions SET player2 = :usr WHERE id = :id;";
            $arr = array(':usr' => $username, ':id' => $game['id']);
            $this->PDOX->rowDie($query, $arr);
            return [$game['groupId'], $game['player1']];
        } else {
            $query = "INSERT INTO {$this->p}sessions (groupId, gameId, player1) VALUES (:grpId, :gmId, :player1)";
            $arr = array(':grpId' => $groupId, ':gmId' => $sessionId, ':player1' => $username);
            $this->PDOX->queryDie($query, $arr);
        }
    }

    function multiplayerSubmission($groupId,$username,$quantity) {
        $query = "SELECT * FROM {$this->p}sessions WHERE groupId = :grpId LIMIT 1;";
        $arr = array(':grpId'=>$groupId);
        $session = $this->PDOX->rowDie($query,$arr);

        if ($session['p1']==NULL) {
            $query = "UPDATE {$this->p}sessions SET p1= :username, p1Data= :quantity WHERE id = :id;";
            $arr = array(':quantity' => $quantity, ':id' => $session['id']);
            $this->PDOX->rowDie($query, $arr);
        } 
        else {
            $query = "UPDATE {$this->p}sessions SET p1= :username, p1Data= :quantity WHERE id = :id;";
            $arr = array(':quantity' => $quantity, ':id' => $session['id']);
            $data = $this->PDOX->rowDie($query, $arr);

            // send back array with usernames and their respective submission data
            $submitData = [$data['p1'],$data['p1Data'],$username,$quantity];
            return json_encode($submitData);

        }
    }

    function getOpponentData($groupId) {
        $query = "SELECT * FROM {$this->p}sessions WHERE groupId = :grpId LIMIT 1;";
        $arr = array(':grpId'=>$groupId);
        $session = $this->PDOX->rowDie($query,$arr);

        $opponentData=[$session['p1'],$session['p1Data']];

        $query = "UPDATE {$this->p}sessions SET p1=NULL, p1Data=NULL WHERE id= :id;";
        $arr = array(':id'=>$session['id']);
        $this->PDOX->rowDie($query,$arr);

        return json_encode($opponentData);
    }

    function getCourses($owner) {
        $query = "SELECT * FROM {$this->p}courses WHERE owner= :owner;";
        $arr = array(':owner' => $owner);
        $result = $this->PDOX->allRowsDie($query,$arr);
        return $result;
    }

    function getCourseNameSection($id) {
        $query = "SELECT name, section FROM {$this->p}courses WHERE id = :id;";
        $arr = array(':id' => $id);
        return $this->PDOX->rowDie($query,$arr);
    }

    function getGames($course) {
        $query = "SELECT * FROM {$this->p}games WHERE course= :course;";
        $arr = array(':course' => $course);
        return $this->PDOX->allRowsDie($query,$arr);
    }

    function getGameInfo($game) {
        $query = "SELECT * FROM {$this->p}games WHERE id= :game;";
        $arr = array(':game'=>$game);
        return $this->PDOX->rowDie($query,$arr);
    }

    function sessionIsLive($id) {
        $query = "SELECT live FROM {$this->p}games WHERE id= :id LIMIT 1;";
        $arr = array(":id"=>$id);
        $result=- $this->PDOX->rowDie($query,$arr);
        return $result['live'];
    }

















}