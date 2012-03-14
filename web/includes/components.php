<?php

/** \file components.php
 * \brief This file defines the major GUI components used in the system.
 * \defgroup comp GUI Components
 * \{
 */

require_once($include_path_root . "functions.php");

// Classes ---------------------------------------------------------------------

/** \brief The base class for a component that shows all entries in a project
 * set.
 */
abstract class Entry_Table{
    
    /** \brief Construct a new entry table to be built for a specific project
     * set.
     * \param name The name of the project set.
     * \param needsScores If this Entry_Table needs to query for the scores as
     * well.
     * \pre name must be a valid project set name.
     */
    function __construct($name, $needsScores){
        $this->name = $name;
        $this->criteria = DB_GetProjectSetCriteria($name);
        $this->needsScores = $needsScores;
        
        // Construct the MySQL query for pulling the entry's id, name, url, sensitive state, and total votes
        $query = "SELECT `id`, `name`, `url`, `sensitive`,";
        if ($needsScores) {
            $query .= " IFNULL ("
                . "(SELECT SUM(s.value)"
                . " FROM `vote` AS v"
                . " INNER JOIN `vote_subresults` AS s"
                . " ON s.voteid = v.id"
                . " WHERE v.entryid = e.id),"
            . " 0)";
        } else {
            $query .= " 0";
        }
        $query .= " AS totalScore"
            . " FROM `entry` AS e"
            . " WHERE `setname` = '"
            . mysql_real_escape_string($this->name)
            . "' ORDER BY `order` ASC";

        // Perform the constructed MySQL query
        $this->res = mysql_query($query) or die(mysql_error());
        
        // Get total scores
        $this->scores = array();
        if($needsScores){
            $this->scores = array();
            if(mysql_numrows($this->res) > 0){
                mysql_data_seek($this->res, 0);
                
                while($entry = mysql_fetch_array($this->res)){
                    array_push($this->scores, (int)$entry["totalScore"]);
                }
                
                rsort($this->scores);
            }
        }
    }
    
    // Getters -----------------------------------------------------------------
    
    /** \brief Get the criteria that this Entry_Table uses.
     * \return This returns the criteria in the same way that
     * DB_GetProjectSetCriteria does.
     */
    function getCriteria(){
        return $this->criteria;
    }
    
    /** \brief Get a sorted array of all scores for all projects.
     */
    function getAllTotalScores(){
        return $this->scores;
    }
    
    /** \brief Get the path to the image for the badge if the entry has a given
     * total score.
     * \param scoreTotal The total of the scores passed to the writeEntry
     * method
     * \return This returns a path to the image.  It should be valid anywhere.
     */
    function getBadgePath($scoreTotal){
        $allScores = $this->getAllTotalScores();
        
        if((int)$scoreTotal== $allScores[0]){
            return $badges_path_www . 'first.png';
        }
        else if((int)$scoreTotal == $allScores[1]){
            return $badges_path_www . 'second.png';
        }
        else if((int)$scoreTotal == $allScores[2]){
            return $badges_path_www . 'third.png';
        }
        else if((int)$scoreTotal == $allScores[count($allScores) - 1]){
            return $badges_path_www . 'last.png';
        }
        else{
            return $badges_path_www . 'blank.png';
        }
    }
    
    // Action ------------------------------------------------------------------
    
    /** \brief Call this to call the delegated subclass capabilities to generate
     * the table in the UI.
     */
    function generate(){        
        $this->writeStart();
        
        if(mysql_numrows($this->res) > 0){
            mysql_data_seek($this->res, 0);
            
            while($entry = mysql_fetch_array($this->res)){
                $scores = array();
                if($this->needsScores){
                    foreach($this->getCriteria() as $criteria){
                        $res = mysql_query("SELECT
                                                IFNULL(
                                                    SUM(
                                                        (SELECT SUM(s.value)
                                                            FROM `vote_subresults` AS s
                                                        WHERE s.voteid = v.id
                                                            AND s.criteriaid = " . $criteria["id"] . ")
                                                        ),
                                                    0) AS total
                                                FROM `vote` AS v
                                                WHERE v.entryid = " . $entry["id"] . "") or die(mysql_error());
                        
                        while($row = mysql_fetch_array($res)){
                            array_push($scores, $row["total"]);
                        }
                    }
                }
                
                $this->writeEntry($entry["id"], $entry["name"], $entry["url"], $entry["sensitive"], $entry["totalScore"], $scores);
            }
        }
        
        $this->writeEnd();   
    }
    
    // Delegate methods --------------------------------------------------------
    
    /** \brief This is called when we are beginning to generate the entry table.
     */
    abstract function writeStart();
    
    /** \brief Called for writing an individual cell.
     * \param id The ID of the entry itself.
     * \param name The name of the entry.
     * \param url The URL of the entry.
     * \param overallScores The total sum of all the scores.
     * \param scores All the totals for all the criterion.
     */
    abstract function writeEntry($id, $name, $url, $sensitive, $overallScore, $scores);
    
    /** \brief This is called when we are done writing the entry table.
     */
    abstract function writeEnd();
    
}

/** \brief A subclass of Entry_Table that displays a table specifically meant
 * for showing an archived project set.
 */
class Archive_Entry_Table extends Entry_Table{
    
    // Constructor -------------------------------------------------------------
    
    /** \brief Create a new Entry_Table for viewing an archived project set.
     * \param setName The name of the project set to view.
     * \pre You must have verifed that the use is actually allowed to view the
     * project set.
     */
    function __construct($setName){
        parent::__construct($setName, true);
    }
    
    // Implemented for Entry_Table ---------------------------------------------
    function writeStart(){
        echo '
            <table>';
    }
    
    function writeEntry($id, $name, $url, $sensitive, $overallScore, $scores){
        echo '
                <tr>
                    <td> ' . ((!$sensitive) ? $name : "Sensitive Project") . ' </td>
                    <td> <a href="' . ((!$sensitive) ? $url : "Sensitive Project") . '">' . ((!$sensitive) ? $url : "Sensitive Project") . '</a> </td>
                </tr>';
    }
    
    function writeEnd(){
        echo '
            </table>';
    }
    
}

/** \brief This Entry_Table subclass shows all the data the admin needs to edit
 * the entries in a project set except for adding a new entry.
 */
class Admin_Entry_Table extends Entry_Table{
    
    // Constructor -------------------------------------------------------------
    
    function __construct($name){
        parent::__construct($name, true);
    }
    
    // Implemented for Entry_Table ---------------------------------------------
    function writeStart(){
        print("<table>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>URL</th>
                    <th>Total Score</th>");
        
        foreach($this->getCriteria() as $crit){
            print("<th>" . htmlentities($crit["name"]) . "</th>");
        }
        
        print("</tr>");
    }
    
    function writeEntry($id, $name, $url, $sensitive, $overallScore, $scores){
        print("<tr>
                <td><img src='" . htmlspecialchars($this->getBadgePath($overallScore), ENT_QUOTES) . "'/></td>
                <td>" . htmlentities($name) . "</td>
                <td><a href=" . htmlentities($url) . ">" . htmlentities($url) . "</a></td>
                <td>" . htmlentities($overallScore) . "</td>");
        
        foreach($scores as $score){
            print("<td>" . htmlentities($score) . "</td>");
        }
        
        print("<td>
                    <form>
                        <input type='submit' value='^' />
                        <input type='hidden' name='projectSet' value='" . htmlspecialchars($this->name, ENT_QUOTES) . "'>
                    </form>
                </td>
                <td>
                    <form>
                        <input type='submit' value='v' />
                        <input type='hidden' name='projectSet' value='" . htmlspecialchars($this->name, ENT_QUOTES) . "'>
                    </form>
                </td>
                <td>
                    <form>
                        <input type='submit' value='Edit' />
                        <input type='hidden' name='projectSet' value='" . htmlspecialchars($this->name, ENT_QUOTES) . "'>
                    </form>
                </td>
              </tr>");
    }
    
    function writeEnd(){
        print("</table>");
    }
    
}

/** \brief A subclass of Entry_Table that shows all the entries for voting on.
 */
class Voting_Entry_Table extends Entry_Table {
    // Constructor -------------------------------------------------------------    
    function __construct($name){
        parent::__construct($name, false);
    }
    
    // Implemented for Entry_Table ---------------------------------------------
    function writeStart(){
        ECHO '
        <div id="votingForm">
            <form name="postVote" action="" method="post">';
    }
    
    function writeEntry($id, $name, $url, $sensitive, $overallScore, $scores){
        ECHO '
                <div id="project">
                    <h2>
                        <div id="projectUrl"><a href="' . $url . '">' . $url . '</a></div>
                        <div id="projectName">' . $name . '&nbsp;' . '<span id="totalScore' . $id . '">0</span>' . '</div>
                    </h2>
                    <table>
                        <div id="sliderBox">';
                            foreach($this->getCriteria() as $j => $crit) {
                                echo '
                            <tr>
                                <td>' . htmlspecialchars($crit["name"]) . '</td>
                                <td>
                                    <input type="range" class="sliderBar" id="sliderBar' . $id . '.' . $j . '" name="p' . $id . '.' . $crit["name"]
                                        . '" value="0" min="-10" max="10" onchange="displaySliderValue(\'sliderValue' . $id . '.' . $j . '\', this.value)" />
                                    <span id="sliderValue' . $id . '.' . $j . '"><script>document.write(document.getElementById(\'sliderBar' . $id . '.' . $j . '\').value)</script></span>
                                </td>
                            </tr>';
                            }
                            echo '
                        </div>
                    </table>
                </div>';
    }
    
    function writeEnd(){
        ECHO '
                <table>
                    <tr>
                        <td><input type="submit" name="submit" /></td>
                    </tr>
                </table>
            </form>
        </div>';
    }
}

/// \}

?>

