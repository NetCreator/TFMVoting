<?php

/** \file functions.php
 * \brief This file implements the interfacing with the MySQL database.
 * \defgroup database Database Connection
 * \{
 */

// Requires --------------------------------------------------------------------
require_once("config.php");

// Functions -------------------------------------------------------------------

/** \brief Connect to the database for the data.
 *
 * This never returns a failure, as it dies on failure.
 * \param host The host to connect to.
 * \param user The username to log in with.
 * \param password The password to log in to the database with.
 * \param database The database in which all of the table data resides.
 */
function DB_Start($host = DB_HOST, $user = DB_USERNAME, $password = DB_PASSWORD, $database = DB_DATABASE){
    mysql_connect($host, $user, $password) or die(mysql_error());
    mysql_selectdb($database) or die(mysql_error());
}

function DB_End(){
    mysql_close();
}

function DB_GetProjectSetCriteria($projectSetName){
    
}

/** \brief This returns an associative array of the different states of the
 * project set that is requested.
 *
 * The returned associative array will have the keys "resultsVisible",
 * "votingOpen", and "archived".
 * \param projectSetName The name of the project set to query the states of.
 * \return This returns an associative array of the states that are associated
 * with the project set.  This returns false on error, such as not finding the
 * specified project set.
 */
function DB_GetProjectSetStates($projectSetName){
    $res = mysql_query("SELECT `resultsVisible`, `votingOpen`, `archived`  FROM `set` WHERE `name` = '" . mysql_real_escape_string($projectSetName) . "'") or die(mysql_error());
    $row = mysql_fetch_array($res);
    return $row;
}

/** \brief Get the most recently made project set name from the database.
 * \return This returns the name of the current project or false if there is no
 * current project.
 */
function DB_GetCurrentProjectSetName(){
    $res = mysql_query("SELECT `name`  FROM `set` ORDER BY `date` DESC LIMIT 1") or die(mysql_error());
    $row = mysql_fetch_row($res);
    if($row){
        return $row[0];
    }
    else{
        return false;
    }
}

/** \brief Get all the project set names from the database.
 * \return This returns an array of names of the projects.
 */
function DB_GetAllProjectSetNames(){
    $res = mysql_query("SELECT `name` FROM `set` ORDER BY `date` DESC") or die(mysql_error());
    $names = array();
    
    while($row = mysql_fetch_row($res)){
        array_push($names, $row[0]);
    }
    
    return $names;
}

/** \brief See if a given project set even exists.
 * \param projectSetName The name of the project set to test for.
 * \return This returns a boolean stating if the project exists.
 */
function DB_GetProjectSetExists($projectSetName){
    $res = mysql_query("SELECT `name` FROM `set` WHERE `name` = '" . mysql_real_escape_string($projectSetName) . "' LIMIT 1") or die(mysql_error());
    $row = mysql_fetch_row($res);
    return (boolean)$row;
}

function DB_GetAllProjectSetStates(){
    
}

/** \brief This gets all the basic data associated with the entries in a project
 * \param projectSetName The name of the project set to use.
 * \return This returns a two dimensional table with a the first column being
 * the id and the second column being the name. The third column is the
 * url. If the query did not work, false is returned.
 */
function DB_GetProjectEntries($projectSetName){
    $res = mysql_query("SELECT `id`, `name`, `url` WHERE `setname` = `" . mysql_real_escape_string($projectSetName) . "`");
    return mysql_fetch_array($res);
}

function DB_GetProjectEntriesWithVotes($projectSetName){
    
}

function DB_GetEntryData($connData, $entryID){
    
}

/// \}

/** \defgroup utils Miscillaneous Utilities
 * \brief These are miscellaneous utilities to reduce duplicate code.
 * \{
 */

/** \brief This function returns the string "true" or "false" depending on the
 * input.
 * \return The literal string of the boolean value.
 */
function UT_BoolString($boolVal){
    return $boolVal ? "true" : "false";
}

/// \}

?>