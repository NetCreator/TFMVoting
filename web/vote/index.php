<?php
/** \file vote/index.php
 *  \brief This file manages the user's view in three states
 *         - not started, open, and published.
 */

require_once('../config.php');

?>

<html>
    <head>
        <title>Project Voting</title>
        <link rel="stylesheet" type="text/css" href="../style.css">
    </head>
    <body>
        <div id="header"><h1>Voting</h1></div>
        <div id="votingForm">
            <form action="">
                <div id="projectName"><?php echo $NAME ?></div>
                <div id="sliderBox">
                    <div>
                    <span>Input</span> // mouseover for description <br />
                    <input type="range" name="Impact" value="0" min="-10" max="10" />
                    <?php
                        /*
                        foreach($DEFAULT_CRITERIA as $criteria) {
                            print('<span on>');
                            print('' . $criteria[0]);
                            
                            print('</span>');
                        }
                        */
                    ?>
                    </div>
                </div>
            </form>
        </div>
    </body>
</html>