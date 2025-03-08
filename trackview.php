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
            <a class="button" role="button" href="./vehicle.php?id=<?php echo $vehicle; ?>">Back</a>
        </div>
        <h1>Portal</h1>
        <h2>Vehicle Track</h2>
        <?php echo "<h4 style=\"margin-bottom:0px;\">" . $user_database[$username]["vehicles"][$vehicle]["name"] . "</h4>"; ?>

        <hr>
        <main>
            <?php
            $filename = preg_replace("/[^A-Z0-9\- ]/", "", $_GET["name"]);
            echo "<h4>" . $filename . "</h4>";
            $filepath = join_paths([$portal_config["storage"]["gps_tracks"]["location"], $username, $vehicle, $filename . ".json"]);
            if (is_file($filepath)) {
                $trackpoints = load_location_file($filepath)["track"];
                if (sizeof($trackpoints) > 0) {
                    $first_datapoint = reset($trackpoints);
                    echo "<div id=\"map\" style=\"margin-top:50px;height:500px;width:100%;\"></div>";
                    echo "<script>
                        const map = L.map('map').setView([" . $first_datapoint["lat"] . ", " . $first_datapoint["lon"] . "], 12);
                        const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>'}).addTo(map);";
                    $index = 0; // This will be incremented to keep track of each marker.
                    foreach ($trackpoints as $time => $trackpoint) {
                        echo "var marker" . $index . " = L.marker([" . $trackpoint["lat"] . ", " . $trackpoint["lon"] . "]).addTo(map);";
                        echo "marker" . $index . ".bindPopup(\"" . date("Y-m-d H:i:s", $time) . "\");";
                    }
                    echo "</script>";
                } else {
                    echo "<p>This file does not contain any track points.</p>";
                }
            } else {
                echo "<p class=\"error\">The specified file is invalid.</p>";
                exit();
            }
            ?>
        </main>
    </body>
</html>
