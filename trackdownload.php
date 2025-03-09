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


$filename = preg_replace("/[^A-Z0-9\- ]/", "", $_GET["name"]);
$filepath = join_paths([$portal_config["storage"]["gps_tracks"]["location"], $username, $vehicle, $filename . ".json"]);
if (is_file($filepath)) {
    $trackpoints = load_location_file($filepath)["track"];
    $gpx = locations_to_gpx($trackpoints);
    header('Content-Disposition: attachment; filename="' . $filename . '.gpx"');
    header('Content-Type: text/plain');
    header('Content-Length: ' . strlen($gpx));
    header('Connection: close');
    echo $gpx;
} else {
    echo "The specified file does not exist.";
    exit();
}

?>
