<?php
include "./config.php";

include $portal_config["auth"]["provider"]["core"];
if ($_SESSION['authid'] !== "dropauth") { // Check to see if the user is not signed in.
    header("Location: ./landing.php");
    exit();
}
if (in_array($username, $portal_config["auth"]["access"]["admin"]) == false) {
    if ($portal_config["auth"]["access"]["mode"] == "whitelist") {
        if (in_array($username, $portal_config["auth"]["access"]["whitelist"]) == false) { // Check to make sure this user is not in blacklist.
            echo "<p>You are not permitted to access this utility.</p>";
            exit();
        }
    } else if ($portal_config["auth"]["access"]["mode"] == "blacklist") {
        if (in_array($username, $portal_config["auth"]["access"]["blacklist"]) == true) { // Check to make sure this user is not in blacklist.
            echo "<p>You are not permitted to access this utility.</p>";
            exit();
        }
    } else {
        echo "<p>The configured access mode is invalid.</p>";
        exit();
    }
}

include "./databases.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Portal - Delete Widget</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="widgetsmanage.php#newwidget">Back</a>
            </div>
            <h1>Portal</h1>
            <h2>Delete Widget</h2>
            <hr>

            <?php
            $widget = intval($_GET["widget"]);
            $confirm = $_GET["confirm"];
            $user_database = load_database("users");

            if ($widget < 0 or $widget-2 > sizeof($user_database[$username]["widgets"])) {
                echo "<p class=\"error\">Invalid widget ID.</p>";
                exit(); 
            }

            if (time() - $confirm < 0) {
                echo "<p class=\"error\">The confirmation timestamp is in the future. If you clicked an external link to get here, then it is possible someone is trying to manipulate you into deleting one of your Portal widgets.</p>";
            } else if (time() - $confirm < 30) {
                unset($user_database[$username]["widgets"][$widget]);
                if (save_database("users", $user_database)) {
                    echo "<p class=\"success\">The widget was successfully removed.</p>";
                    echo "<a class=\"button\" href=\"widgetsmanage.php\">Widgets</a>";
                    echo "<a class=\"button\" href=\"index.php\">Dashboard</a>";
                } else {
                    echo "<p class=\"error\">The user database could not be saved due to an internal error.</p>";
                }
            } else {
                echo "<p>Are you sure you would like to delete the <b>" . widget_type_to_name($user_database[$username]["widgets"][$widget]["type"]) . "</b> widget?</p>";
                echo "<a class=\"button\" href=\"widgetsdelete.php?widget=" . $widget . "&confirm=" . time() . "\">Confirm Deletion</a>";
            }
            ?>
        </main>
    </body>
</html>
