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
$user_database = load_database("users");

$vehicle = $_GET["vehicle"];
if (in_array($vehicle, array_keys($user_database[$username]["vehicles"])) == false) {
    echo "<p class=\"error\">The selected vehicle is invalid.</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Portal - Vehicle Track</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">

        <link rel="stylesheet" href="./assets/js/leaflet/leaflet.css" />
        <script src="./assets/js/leaflet/leaflet.js"></script>
    </head>
    <body>
        <div class="navbar" role="navigation">
        </div>
        <h1>Portal</h1>
        <h2>Delete Vehicle Track</h2>
        <hr>
        <main>
            <?php
            $filename = preg_replace("/[^A-Z0-9\- ]/", "", $_GET["name"]);
            echo "<h4>" . $filename . "</h4>";
            $filepath = join_paths([$portal_config["storage"]["gps_tracks"]["location"], $username, $vehicle, $filename . ".json"]);
            if (is_file($filepath)) {
                if (time() - $_GET["confirm"] < 0) {
                    echo "<p class=\"error\">The confirmation timestamp is in the future. If you clicked an external link to get here, then it is possible someone is trying to trick you into deleting a GPS track file for one of your Portal vehicles.</p>";
                } else if (time() - $_GET["confirm"] < 30) {
                    if (unlink($filepath)) {
                        echo "<p class=\"success\">The file has been successfully deleted.</p>";
                        echo "<a class=\"button\" role=\"button\" href=\"./vehicle.php?id=" . $vehicle . "\">Back</a>";
                    } else {
                        echo "<p class=\"error\">The file could not be deleted due to an internal error. This is likely a server-side issue, so consider contacting customer support.</p>";
                        echo "<a class=\"button\" role=\"button\" href=\"./vehicle.php?id=" . $vehicle . "\">Back</a>";
                    }
                } else {
                    echo "<p>Are you sure you want to delete the <b>" . $filename . "</b> file for <b>" . $user_database[$username]["vehicles"][$vehicle]["name"] . "</b>?</p>";
                    echo "<a class=\"button\" role=\"button\" href=\"trackdelete.php?vehicle=$vehicle&name=$filename&confirm=" . time() . "\">Delete</a>";
                    echo "<a class=\"button\" role=\"button\" href=\"./vehicle.php?id=" . $vehicle . "\">Cancel</a>";
                }
            } else {
                echo "<p class=\"error\">The specified file does not exist.</p>";
            }
            ?>
        </main>
    </body>
</html>
