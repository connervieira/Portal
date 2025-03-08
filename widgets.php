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
            $total_distance = calculate_vehicle_distance_total($username, $days, $user_database);
            echo "<div class=\"widget\"><div class=\"vertically_centered\">";
            echo "<p class=\"widget-title\">total distance</p>";
            echo "<p style=\"font-size: 2rem\">" . round($vehicle_distance/100)/10 . "km</p>";
            echo "<p class=\"widget-title\">past " . $widget["interval"] . " days</p>";
            echo "</div></div>";
        } else if ($widget["type"] == "storage_total") {
            // TODO: Display the total GPS track location storage usage.
        } else if ($widget["type"] == "storage_largest") {
            // TODO: Display the GPS track location storage usage for the vehicle that is closest to full.
        } else if ($widget["type"] == "storage_vehicle") {
            // TODO: Display the GPS track location storage usage for a specific vehicle.
        }
    }
} else {
    echo "<p><i>You have no widgets.</i></p>";
    echo "<a class=\"button\" href=\"./managewidgets.php\">Manage Widgets</a>";
}
?>
