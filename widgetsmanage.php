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
        <title>Portal - Manage Widgets</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="index.php">Back</a>
            </div>
            <h1>Portal</h1>
            <h2>Manage Widgets</h2>
            <hr>

            <?php
            $user_database = load_database("users");

            foreach ($user_database[$username]["widgets"] as $id => $widget) {
                echo "<form method=\"POST\" action=\"editwidget.php?widget=" . $id . "\">";
                $type = $widget["type"];
                $name = widget_type_to_name($type);
                echo "<h3>" . $name . "</h3>";
                echo "<a href=\"widgetsdelete.php?widget=" . $id . "\" class=\"button\" style=\"background:#990000;\">Delete</a><br><br>";
                if (in_array($type, array("vehicle_count", "storage_total", "storage_largest"))) {
                    echo "<p><i>This widget has no options</i></p>";
                } else if ($type == "vehicle_distance") {
                    echo "<label for=\"vehicle\">Vehicle:</label> <select name=\"vehicle\" id=\"vehicle\">";
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<option value=\"" . $vehicle . "\"";
                        if ($widget["vehicle"] == $vehicle) { echo " selected"; }
                        echo ">" . $user_database[$username]["vehicles"][$vehicle]["name"] . " (" . substr($vehicle, 0, 6) . "...)</option>";
                    }
                    echo "</select>";
                    echo "<label for=\"interval\" title=\"The total distance traveled by a specific vehicle will be summed up for the past N days.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"30\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"" . intval($widget["interval"]) . "\"> days<br>";
                } else if ($type == "vehicle_preview") {
                    echo "<label for=\"vehicle\">Vehicle:</label> <select name=\"vehicle\" id=\"vehicle\">";
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<option value=\"" . $vehicle . "\"";
                        if ($widget["vehicle"] == $vehicle) { echo " selected"; }
                        echo ">" . $user_database[$username]["vehicles"][$vehicle]["name"] . " (" . substr($vehicle, 0, 6) . "...)</option>";
                    }
                    echo "</select><br>";
                    echo "<label for=\"device\" title=\"This is the capture device ID (as defined in the Predator configuration) that will be displayed in this widget.\">Device:</label> <input name=\"device\" id=\"device\" value=\"" . $widget["device"] . "\" placeholder=\"main, rear, etc.\"><br>";
                    echo "<input type=\"submit\" value=\"Update\" name=\"submit\" id=\"submit\" class=\"button\">";
                } else if ($type == "vehicles_active") {
                    echo "<label for=\"interval\" title=\"Vehicles that have uploaded information within the past N minutes will be counted as active.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"5\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"" . intval($widget["interval"]) . "\"> minutes<br>";
                    echo "<input type=\"submit\" value=\"Update\" name=\"submit\" id=\"submit\" class=\"button\">";
                } else if ($type == "vehicles_online") {
                    echo "<label for=\"interval\" title=\"Vehicles that have moved within the past N minutes will be counted as active.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"5\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"" . intval($widget["interval"]) . "\"> minutes<br>";
                    echo "<input type=\"submit\" value=\"Update\" name=\"submit\" id=\"submit\" class=\"button\">";
                } else if ($type == "total_distance") {
                    echo "<label for=\"interval\" title=\"The total distance traveled by all vehicles will be summed up for the past N days.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"30\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"" . intval($widget["interval"]) . "\"> days<br>";
                    echo "<input type=\"submit\" value=\"Update\" name=\"submit\" id=\"submit\" class=\"button\">";
                }
                echo "</form>";
                echo "<hr style=\"width:50%;\">";
            }
            ?>
            <form method="POST" action="widgetscreate.php">
                <h3 id="newwidget">New Widget</h3>
                <label for="type">Type:</label> <select name="type" id="type">
                <option value="vehicle_count" title="Displays the total number of vehicles registered">Vehicle Count</option>
                <option value="vehicles_online" title="Displays vehicles that have submitted data over the past N minutes (even if they are stationary)">Vehicles Online</option>
                <option value="vehicles_active" title="Displays vehicles that have moved in the past N minutes">Vehicles Active</option>
                <option value="vehicle_distance" title="Displays the total distance traveled by a specific vehicle over the past N days">Vehicle Distance</option>
                <option value="vehicle_preview" title="Displays a dash-cam preview for a specific camera on a specific vehicle">Vehicle Preview</option>
                <option value="total_distance" title="Displays the total distance travel by all vehicles over the past N days">Total Distance</option>
                <option value="storage_total" title="Displays the total location track storage used for all vehicles">Storage Total</option>
                <option value="storage_largest" title="Displays the location track storage usage for the vehicle that's closest to being full">Storage Largest</option>
                <option value="storage_vehicle" title="Displays the location track storage usage for a specific vehicle">Storage Vehicle</option>
                </select>
                <br><input class="button" type="submit" id="submit" name="submit" value="Create">
            </form>
        </main>
    </body>
</html>
