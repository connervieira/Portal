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
        <title>Portal - Vehicle</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <div class="navbar" role="navigation">
            <a class="button" role="button" href="./index.php">Back</a>
        </div>
        <h1>Portal</h1>
        <h2>Vehicle</h2>

        <hr>
        <main>
            <?php
            $user_database = load_database("users");

            $vehicle_id = $_GET["id"];
            if (in_array($vehicle_id, array_keys($user_database[$username]["vehicles"])) == true) {
                echo "<h4 style=\"margin-bottom:0px;\">" . $user_database[$username]["vehicles"][$vehicle_id]["name"] . "</h4>";
                $most_recent_location = most_recent_vehicle_location($vehicle_id, $user_database);
                if ($most_recent_location == false) {
                    echo "<p style=\"margin-top:0px;\">Never seen</p>";
                } else {
                    echo "<div style=\""; if (time() - $most_recent_location["time"] > 100) { echo "opacity:0.4;"; } echo "\">";
                    echo "    <p style=\"margin-top:0px;\">Last seen " . time() - $most_recent_location["time"] . " seconds ago</p>";
                    echo "    <table class=\"telemetry_table\">";
                    echo "        <tr>";
                    echo "            <td class=\"right\"><a href=\"https://www.openstreetmap.org/#map=17/" . $most_recent_location["lat"] . "/" . $most_recent_location["lon"]. "\" target=\"_blank\"><img src=\"assets/images/icons/location.svg\"></a></td>";
                    echo "            <td class=\"left\">(" . $most_recent_location["lat"] . ", " . $most_recent_location["lon"] . ")</td>";
                    echo "        </tr>";
                    echo "        <tr>";
                    echo "            <td class=\"right\"><img src=\"assets/images/icons/speed.svg\"></td>";
                    echo "            <td class=\"left\">" . intval($most_recent_location["speed"]) . " m/s</td>";
                    echo "        </tr>";
                    echo "        <tr>";
                    echo "            <td class=\"right\"><img src=\"assets/images/icons/altitude.svg\"></td>";
                    echo "            <td class=\"left\">" . intval($most_recent_location["altitude"]) . " meters</td>";
                    echo "        </tr>";
                    echo "    </table>";
                    echo "</div>";
                }
            } else {
                echo "<p class=\"error\">The selected vehicle is invalid.</p>";
            }
            ?>
        </main>
    </body>
</html>
