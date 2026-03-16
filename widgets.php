<?php
// Note to developers: To implement new widgets, they must be added to the following files:
// 1. `widgets.php` (this file).
// 2. `widgetsmanage.php` (the starting point for users editing/deleting/creating widgets)
// 3. `widgetscreate.php` (the endpoint used by users to add widgets)

include "./config.php";

include $portal_config["auth"]["provider"]["core"];
if ($_SESSION['authid'] !== "dropauth") { // Check to see if the user is not signed in.
    header("Location: ./landing.php");
    exit();
}
if (in_array($username, $portal_config["auth"]["access"]["admin"]) == false) {
    if ($portal_config["auth"]["access"]["mode"] == "whitelist") {
        if (in_array($username, $portal_config["auth"]["access"]["whitelist"]) == false) { // Check to make sure this user is in the whitelist.
            echo "<p>You are not permitted to access this utility.</p>";
            exit();
        }
    } else if ($portal_config["auth"]["access"]["mode"] == "blacklist") {
        if (in_array($username, $portal_config["auth"]["access"]["blacklist"]) == true) { // Check to make sure this user is not in the blacklist.
            echo "<p>You are not permitted to access this utility.</p>";
            exit();
        }
    } else {
        echo "<p>The configured access mode is invalid.</p>";
        exit();
    }
}


if (sizeof($user_database[$username]["widgets"]) > 0) { // Check to see if this user doesn't have any widgets.
    foreach ($user_database[$username]["widgets"] as $widget) {
        if ($widget["type"] == "vehicle_count") {
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p style=\"font-size: 4rem\">" . sizeof($user_database[$username]["vehicles"]) . "</p>";
            echo "<p class=\"widget-title\">vehicles tracked</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicles_online" or $widget["type"] == "vehicles_active") { // Displays a list of vehicles that have uploaded information recently.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            if ($widget["type"] == "vehicles_active") { // Displays a list of actively moving vehicles.
                echo "<p class=\"widget-title\">active vehicles</p>";
                $active_vehicles = active_vehicles(60*$widget["interval"], $user_database, $username);
            } else if ($widget["type"] == "vehicles_online") { // Displays a list of vehicles that have uploaded information recently (even if they are not moving).
                echo "<p class=\"widget-title\">online vehicles</p>";
                $active_vehicles = online_vehicles(60*$widget["interval"], $user_database, $username);
            }
            if (sizeof($active_vehicles) > 0) {
                $displayed_vehicles = 0;
                foreach ($active_vehicles as $vehicle) {
                    if ($displayed_vehicles >= 5) {
                        echo "<p style=\"font-size: 1rem\">" . sizeof($active_vehicles) - $displayed_vehicles . " more<p>";
                        break;
                    }
                    echo "<p style=\"font-size: 1.5rem\">" . $user_database[$username]["vehicles"][$vehicle]["name"] . "</p>";
                    $displayed_vehicles += 1;
                }
            } else {
                echo "<p style=\"font-size: 3rem\">None</p>";
            }
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_distance") { // Displays the distance a specific vehicle has driven over the past N days.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">vehicle distance</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $vehicle_distance = calculate_vehicle_distance($widget["vehicle"], $widget["interval"], $user_database);
                echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                echo "<p style=\"font-size: 2rem\">" . round($vehicle_distance/100)/10 . "km</p>";
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_utilization") { // Displays the percentage of time spent moving against time spent stationary over the past N days.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">vehicle utilization</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $utilization = calculate_vehicle_utilization($widget["vehicle"], $widget["interval"], $user_database);
                echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                echo "<p style=\"font-size: 2rem\">" . number_format($utilization * 100, 2) . "%</p>";
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_idle_time") { // Displays the minutes spent idling (online but stationary).
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">vehicle idle minutes</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $idle_minutes = calculate_vehicle_idle_minutes($widget["vehicle"], $widget["interval"], $user_database);
                echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                echo "<p style=\"font-size: 1.4rem\">" . number_format($idle_minutes, 1) . " minutes</p>";
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_idle_percentage") { // Displays the percentage of time idling against the total time online.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">vehicle idle percentage</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $idle_minutes = calculate_vehicle_idle_minutes($widget["vehicle"], $widget["interval"], $user_database);
                $active_minutes = calculate_vehicle_active_minutes($widget["vehicle"], $widget["interval"], $user_database);
                $percentage_idle = $idle_minutes / ($idle_minutes+$active_minutes);
                echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                echo "<p style=\"font-size: 2rem\">" . number_format($percentage_idle*100, 2) . "%</p>";
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_active_time") { // Displays the minutes spent active (online and moving).
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">vehicle active minutes</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $active_minutes = calculate_vehicle_active_minutes($widget["vehicle"], $widget["interval"], $user_database);
                echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                echo "<p style=\"font-size: 1.4rem\">" . number_format($active_minutes, 1) . " minutes</p>";
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_active_percentage") { // Displays the percentage of time active against the total time online.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">vehicle active percentage</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $idle_minutes = calculate_vehicle_idle_minutes($widget["vehicle"], $widget["interval"], $user_database);
                $active_minutes = calculate_vehicle_active_minutes($widget["vehicle"], $widget["interval"], $user_database);
                $percentage_active = $active_minutes / ($idle_minutes+$active_minutes);
                echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                echo "<p style=\"font-size: 2rem\">" . number_format($percentage_active*100, 2) . "%</p>";
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "vehicle_preview") { // Displays a preview of the camera for a specific vehicle.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">preview</p>";
            if (in_array($widget["vehicle"], array_keys($user_database[$username]["vehicles"]))) {
                $latest_image = fetch_camera_preview($widget["vehicle"], $username, $widget["device"]);
                if ($latest_image != false) {
                    $image_age = time() - filemtime(join_paths([$portal_config["databases"]["vehicles"]["location"], $username, $widget["vehicle"], $widget["device"] . ".jpg"]));
                    echo "<p style=\"font-size: 1.4rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
                    echo "<img src=\"" . $latest_image . "\">";
                    if ($image_age > 120) {
                        echo "<p class=\"widget-title\">" . round($image_age/60) . " minutes ago</p>";
                    }
                } else {
                    echo "<p class=\"error\">failed to load image</p>";
                }
            } else {
                echo "<br><p class=\"error\" style=\"font-size: 1rem\">This vehicle does not exist.</p>";
            }
            echo "</div></div>";
        } else if ($widget["type"] == "total_distance") { // Displays the total distance all vehicles have driven in the past N days.
            $total_distance = calculate_vehicle_distance_total($username, $widget["interval"], $user_database);
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">total distance</p>";
            echo "<p style=\"font-size: 2rem\">" . round($total_distance/100)/10 . "km</p>";
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "storage_total") { // Displays the total storage used for all vehicle track files combined.
            $total_storage_usage = get_location_storage_usage_total($username, $user_database);
            $total_storage_gb = $total_storage_usage / (1000**3); // Convert the storage usage from bytes into bytes into GB.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">total storage usage</p>";
            echo "<p style=\"font-size: 2rem\">" . number_format($total_storage_gb, 2) . " GB</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "storage_largest") {
            $response = get_location_storage_usage_largest($username, $user_database);
            $largest_vehicle = $response[0];
            $largest_usage = $response[1];
            $largest_usage_gb = $largest_usage / (1000**3); // Convert the storage usage from bytes into bytes into GB.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">largest storage</p>";
            echo "<p style=\"font-size: 1.5rem\">" . $user_database[$username]["vehicles"][$largest_vehicle]["name"] . "</p>";
            echo "<p style=\"font-size: 2rem\">" . number_format($largest_usage_gb, 2) . " GB</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "storage_vehicle") {
            $storage_usage = get_location_storage_usage_vehicle($widget["vehicle"], $user_database);
            $storage_usage_gb = $storage_usage / (1000**3); // Convert the storage usage from bytes into bytes into GB.
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">storage usage</p>";
            echo "<p style=\"font-size: 1.5rem\">" . $user_database[$username]["vehicles"][$widget["vehicle"]]["name"] . "</p>";
            echo "<p style=\"font-size: 2rem\">" . number_format($storage_usage_gb, 2) . " GB</p>";
            echo "</div></div>";
        }
    }
} else {
    echo "<p><i>You have no widgets.</i></p>";
    echo "<a class=\"button\" href=\"./managewidgets.php\">Manage Widgets</a>";
}
?>
