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
        <title>Portal - Create Widget</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="widgetsmanage.php#newwidget">Back</a>
            </div>
            <h1>Portal</h1>
            <h2>Create Widget</h2>
            <hr>

            <?php
            $user_database = load_database("users");
            if ($_POST["submit"] == "Add") { // The user has submitted the full widget information from this page.
                $type = $_POST["type"];
                $interval = intval($_POST["interval"]);
                $vehicle = $_POST["vehicle"];
                $device = $_POST["device"];

                $widget_info = array();
                $valid = true; // This will be switched to 'false' if an invalid input is found.
                if ($type == "vehicle_count") {
                    $widget_info["type"] = $type;
                } else if ($type == "vehicles_online") {
                    if ($interval < 1 or $interval > 120) {
                        echo "<p class=\"warning\">The specified interval is outside of the expected range.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["interval"] = intval($interval);
                } else if ($type == "vehicles_active") {
                    if ($interval < 1 or $interval > 120) {
                        echo "<p class=\"warning\">The specified interval is outside of the expected range.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["interval"] = intval($interval);
                } else if ($type == "vehicle_distance") {
                    if (in_array($vehicle, array_keys($user_database[$username]["vehicles"])) == false) {
                        echo "<p class=\"warning\">The specified vehicle does not appear to exist.</p>";
                        $valid = false;
                    }
                    if ($interval < 1 or $interval > 120) {
                        echo "<p class=\"warning\">The specified interval is outside of the expected range.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["vehicle"] = $vehicle;
                    $widget_info["interval"] = $interval;
                } else if ($type == "vehicle_utilization") {
                    if (in_array($vehicle, array_keys($user_database[$username]["vehicles"])) == false) {
                        echo "<p class=\"warning\">The specified vehicle does not appear to exist.</p>";
                        $valid = false;
                    }
                    if ($interval < 1 or $interval > 120) {
                        echo "<p class=\"warning\">The specified interval is outside of the expected range.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["vehicle"] = $vehicle;
                    $widget_info["interval"] = $interval;
                } else if ($type == "vehicle_preview") {
                    if (in_array($vehicle, array_keys($user_database[$username]["vehicles"])) == false) {
                        echo "<p class=\"warning\">The specified vehicle does not appear to exist.</p>";
                        $valid = false;
                    }
                    if ($device !== preg_replace("/[^a-zA-Z0-9 \-_]/", "", $device) or strlen($device) > 30) { // Check to see if the device has any disallowed characters.
                        echo "<p class=\"warning\">The specified capture device is invalid.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["vehicle"] = $vehicle;
                    $widget_info["device"] = $device;
                } else if ($type == "total_distance") {
                    if ($interval < 1 or $interval > 120) {
                        echo "<p class=\"warning\">The specified interval is outside of the expected range.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["interval"] = $interval;
                } else if ($type == "storage_total") {
                    $widget_info["type"] = $type;
                } else if ($type == "storage_largest") {
                    $widget_info["type"] = $type;
                } else if ($type == "storage_vehicle") {
                    if (in_array($vehicle, array_keys($user_database[$username]["vehicles"])) == false) {
                        echo "<p class=\"warning\">The specified vehicle does not appear to exist.</p>";
                        $valid = false;
                    }
                    $widget_info["type"] = $type;
                    $widget_info["vehicle"] = $vehicle;
                } else {
                    echo "<p class=\"error\">Invalid widget type</p>";
                    exit();
                }
                if ($valid == true) {
                    array_push($user_database[$username]["widgets"], $widget_info); // Add this widget to the user's widgets.
                    if (save_database("users", $user_database)) {
                        echo "<p class=\"success\">The widget was successfully added.</p>";
                        echo "<a class=\"button\" href=\"widgetsmanage.php\">Widgets</a>";
                        echo "<a class=\"button\" href=\"index.php\">Dashboard</a>";
                    } else {
                        echo "<p class=\"error\">The user database could not be saved due to an internal error.</p>";
                    }
                } else {
                    echo "<p class=\"error\">The widget was not created.</p>";
                }
            } else if ($_POST["submit"] == "Create") { // The user has selected the option to create a new widget from `widgetsmanage.php`.
                echo "<form method=\"POST\">";
                echo "<h3>New Widget</h3>";
                $type = $_POST["type"];
                if ($type == "vehicle_count") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"vehicle_count\">" . widget_type_to_name($type) . "</option></select>";
                } else if ($type == "vehicles_online") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"vehicles_online\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"interval\" title=\"This widget will show vehicles that have submitted telemetry within the past N minutes (even if they are stationary).\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"5\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"5\"> minutes<br>";
                } else if ($type == "vehicles_active") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"vehicles_active\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"interval\" title=\"This widget will show vehicles that have moved within the past N minutes.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"5\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"5\"> minutes<br>";
                } else if ($type == "vehicle_distance") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"vehicle_distance\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"vehicle\">Vehicle:</label> <select name=\"vehicle\" id=\"vehicle\">";
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<option value=\"" . $vehicle . "\">" . $user_database[$username]["vehicles"][$vehicle]["name"] . " (" . substr($vehicle, 0, 6) . "...)</option>";
                    }
                    echo "</select>";
                    echo "<br><label for=\"interval\" title=\"The total distance traveled by a specific vehicle will be summed up for the past N days.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"30\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"30\"> days<br>";
                } else if ($type == "vehicle_utilization") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"vehicle_utilization\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"vehicle\">Vehicle:</label> <select name=\"vehicle\" id=\"vehicle\">";
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<option value=\"" . $vehicle . "\">" . $user_database[$username]["vehicles"][$vehicle]["name"] . " (" . substr($vehicle, 0, 6) . "...)</option>";
                    }
                    echo "</select>";
                    echo "<br><label for=\"interval\" title=\"The utilization percentage for a specific vehicle will be summed up for the past N days.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"30\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"30\"> days<br>";
                } else if ($type == "vehicle_preview") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"vehicle_preview\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"vehicle\">Vehicle:</label> <select name=\"vehicle\" id=\"vehicle\">";
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<option value=\"" . $vehicle . "\">" . $user_database[$username]["vehicles"][$vehicle]["name"] . " (" . substr($vehicle, 0, 6) . "...)</option>";
                    }
                    echo "</select>";
                    echo "<br><label for=\"device\" title=\"This is the capture device ID (as defined in the Predator configuration) that will be displayed in this widget.\">Device:</label> <input name=\"device\" id=\"device\" placeholder=\"main, rear, etc.\"><br>";
                } else if ($type == "total_distance") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"total_distance\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"interval\" title=\"The total distance traveled by all vehicles will be summed up for the past N days.\">Interval:</label> <input name=\"interval\" id=\"interval\" placeholder=\"30\" step=\"1\" min=\"1\" max=\"120\" type=\"number\" style=\"width:70px;\" value=\"3\"> days<br>";
                } else if ($type == "storage_total") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"storage_total\">" . widget_type_to_name($type) . "</option></select>";
                } else if ($type == "storage_largest") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"storage_largest\">" . widget_type_to_name($type) . "</option></select>";
                } else if ($type == "storage_vehicle") {
                    echo "<select id=\"type\" name=\"type\"><option value=\"storage_vehicle\">" . widget_type_to_name($type) . "</option></select>";
                    echo "<br><label for=\"vehicle\">Vehicle:</label> <select name=\"vehicle\" id=\"vehicle\">";
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<option value=\"" . $vehicle . "\">" . $user_database[$username]["vehicles"][$vehicle]["name"] . " (" . substr($vehicle, 0, 6) . "...)</option>";
                    }
                    echo "</select>";
                } else {
                    echo "<p class=\"error\">Invalid widget type</p>";
                    exit();
                }
                echo "<br><input class=\"button\" type=\"submit\" id=\"submit\" name=\"submit\" value=\"Add\">";
                echo "</form>";
            } else {
                header("Location: ./widgetsmanage.php#newwidget");
            }
            ?>
        </main>
    </body>
</html>
