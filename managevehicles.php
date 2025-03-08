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
        <title>Portal - Manage Vehicles</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="index.php">Back</a>
            </div>
            <h1>Portal</h1>
            <h2>Manage Vehicles</h2>
            <hr>

            <?php
            $user_database = load_database("users");

            if (in_array($_GET["delete"], array_keys($user_database[$username]["vehicles"]))) {
                if (time() - intval($_GET["confirm"]) < 0) {
                    echo "<p> class=\"error\">The confirmation timestamp is in the future. If you clicked an external link to get here, it's possible someone is trying to trick you into a deleting a vehicle.</p>";
                    echo "<hr style=\"width:100px;\">";
                } else if (time() - intval($_GET["confirm"]) <= 30) {
                    unset($user_database[$username]["vehicles"][$_GET["delete"]]);
                    foreach ($user_database[$username]["widgets"] as $index => $widget) { // Iterate over each widget to remove any that are associated with this vehicle.
                        if (in_array("vehicle", array_keys($widget)) and $widget["vehicle"] == $_GET["delete"]) {
                            unset($user_database[$username]["widgets"][$index]);
                        }
                    }
                    $vehicle_persistent_directory = join_paths([$portal_config["storage"]["gps_tracks"]["location"], $username, $_GET["delete"]]);
                    echo $vehicle_persistent_directory;
                    if (is_dir($vehicle_persistent_directory)) {
                        if (delete_directory($vehicle_persistent_directory) == false) {
                            echo "<p class=\"error\">Failed to remove the persistent vehicle directory. This is likely a server-side issue, so consider contact customer support.</p>";
                            exit();
                        }
                    }
                    $vehicle_volatile_directory = join_paths([$portal_config["databases"]["vehicles"]["location"], $username, $_GET["delete"]]);
                    echo $vehicle_volatile_directory;
                    if (is_dir($vehicle_volatile_directory)) {
                        if (delete_directory($vehicle_volatile_directory) == false) {
                            echo "<p class=\"error\">Failed to remove the volatile vehicle directory. This is likely a server-side issue, so consider contact customer support.</p>";
                            exit();
                        }
                    }
                    save_database("users", $user_database);
                    header("Location: ./managevehicles.php");
                    exit();
                } else {
                    echo "<p>Are you sure you would like to delete <b>" . $user_database[$username]["vehicles"][$_GET["delete"]]["name"] . " (" . $_GET["delete"] . ")</b>? This will permanently erase all data associated with this vehicle, including location history and maintenance information.</p>";
                    echo "<a class=\"button\" href=\"?delete=" . $_GET["delete"] . "&confirm=" . time() . "\">Delete</a> <a class=\"button\" href=\"managevehicles.php\">Cancel</a>";
                    echo "<hr style=\"width:100px;\">";
                }
            } else if ($_POST["submit"] == "Create") {
                $valid = true; // Assume the information for this vehicle is valid until an invalid value is encountered.

                // Generate a new ID.
                $all_vehicle_ids = array();
                foreach (array_keys($user_database) as $user) {
                    foreach (array_keys($user_database[$user]["vehicles"]) as $vehicle_id) {
                        array_push($all_vehicle_ids, $vehicle_id);
                    }
                }
                $new_id = "";
                $iteration_count = 0;
                while (strlen($new_id) == 0 or in_array($new_id, $all_vehicle_ids)) { // Generate IDs until a unique one is created.
                    if ($iteration_count > 10000) {
                        echo "<p class=\"error\">Failed to generate unique vehicle ID (finished on '" . htmlspecialchars($new_id) . "'). This should never happen, and likely indicates a bug.</p>";
                        exit();
                    }
                    $new_id = bin2hex(random_bytes(12));
                    $iteration_count += 1;
                }

                $name = $_POST["vehicle>new>name"];
                $year = intval($_POST["vehicle>new>year"]);
                $make = $_POST["vehicle>new>make"];
                $model = $_POST["vehicle>new>model"];
                $vin = strtoupper($_POST["vehicle>new>vin"]);

                if (strlen($name) > 100 or $name !== preg_replace("/[^a-zA-Z0-9\- \/'\(\).,]/", "", $name)) {
                    echo "<p class=\"error\">The supplied vehicle nickname for the new vehicle is invalid.</p>";
                    $valid = false;
                }
                if (($year < 1800 or $year > intval(date("Y"))+5) and $year !== 0) {
                    echo "<p class=\"error\">The supplied vehicle year for the new vehicle is invalid.</p>";
                    $valid = false;
                }
                if (strlen($make) > 100 or $make !== preg_replace("/[^a-zA-Z0-9\- \/'\(\).,]/", "", $make)) {
                    echo "<p class=\"error\">The supplied vehicle make for the new vehicle is invalid.</p>";
                    $valid = false;
                }
                if (strlen($model) > 100 or $model !== preg_replace("/[^a-zA-Z0-9\- \/'\(\).,]/", "", $model)) {
                    echo "<p class=\"error\">The supplied vehicle model for the new vehicle is invalid.</p>";
                    $valid = false;
                }
                if (strlen($vin) > 30 or $vin !== preg_replace("/[^A-Z0-9]/", "", $vin)) {
                    echo "<p class=\"error\">The supplied vehicle model for the new vehicle is invalid.</p>";
                    $valid = false;
                }

                if ($valid == true) {
                    $user_database[$username]["vehicles"][$new_id] = array();
                    $user_database[$username]["vehicles"][$new_id]["name"] = $name;
                    $user_database[$username]["vehicles"][$new_id]["year"] = $year;
                    $user_database[$username]["vehicles"][$new_id]["make"] = $make;
                    $user_database[$username]["vehicles"][$new_id]["model"] = $model;
                    $user_database[$username]["vehicles"][$new_id]["vin"] = $vin;

                    save_database("users", $user_database);
                    echo "<p>The new vehicle was successfully created.</p>";
                    echo "<hr style=\"width:100px;\">";
                } else {
                    echo "<p class='error'>The configuration was not updated.</p>";
                    echo "<hr style=\"width:100px;\">";
                }
            } else if ($_POST["submit"] == "Edit") {
                $all_valid = true;
                foreach (array_keys($user_database[$username]["vehicles"]) as $id) { // Iterate over each existing vehicle to handle edits.
                    $valid = true; // Assume the information for this vehicle is valid until an invalid value is encountered.

                    $name = $_POST["vehicle>" . $id . ">name"];
                    $year = intval($_POST["vehicle>" . $id . ">year"]);
                    $make = $_POST["vehicle>" . $id . ">make"];
                    $model = $_POST["vehicle>" . $id . ">model"];
                    $vin = strtoupper($_POST["vehicle>" . $id . ">vin"]);

                    if (strlen($name) > 100 or $name !== preg_replace("/[^a-zA-Z0-9\- \/'\(\).,]/", "", $name)) {
                        echo "<p class=\"error\">The supplied vehicle nickname for \"" . substr($id, 0, 8) . "...\" is invalid.</p>";
                        $valid = false; $all_valid = false;
                    }
                    if (($year < 1800 or $year > intval(date("Y"))+5) and $year !== 0) {
                        echo "<p class=\"error\">The supplied vehicle year for \"" . substr($id, 0, 8) . "...\" is invalid.</p>";
                        $valid = false; $all_valid = false;
                    }
                    if (strlen($make) > 100 or $make !== preg_replace("/[^a-zA-Z0-9\- \/'\(\).,]/", "", $make)) {
                        echo "<p class=\"error\">The supplied vehicle make for \"" . substr($id, 0, 8) . "...\" is invalid.</p>";
                        $valid = false; $all_valid = false;
                    }
                    if (strlen($model) > 100 or $model !== preg_replace("/[^a-zA-Z0-9\- \/'\(\).,]/", "", $model)) {
                        echo "<p class=\"error\">The supplied vehicle model for \"" . substr($id, 0, 8) . "...\" is invalid.</p>";
                        $valid = false; $all_valid = false;
                    }
                    if (strlen($vin) > 30 or $vin !== preg_replace("/[^A-Z0-9]/", "", $vin)) {
                        echo "<p class=\"error\">The supplied vehicle VIN for \"" . substr($id, 0, 8) . "...\" is invalid.</p>";
                        $valid = false; $all_valid = false;
                    }

                    if ($valid == true) {
                        $user_database[$username]["vehicles"][$id]["name"] = $name;
                        $user_database[$username]["vehicles"][$id]["year"] = $year;
                        $user_database[$username]["vehicles"][$id]["make"] = $make;
                        $user_database[$username]["vehicles"][$id]["model"] = $model;
                        $user_database[$username]["vehicles"][$id]["vin"] = $vin;
                    }

                }
                save_database("users", $user_database);
                if ($all_valid == true) {
                    echo "<p class=\"pulse\">All edits have been successfully submitted.</p>";
                    echo "<hr style=\"width:100px;\">";
                } else {
                    echo "<p class=\"error\">One or more invalid values was encountered. Please review the previous errors.</p>";
                    echo "<hr style=\"width:100px;\">";
                }
            }


            ?>
            <form method="POST">
                <?php
                foreach ($user_database[$username]["vehicles"] as $id => $vehicle) {
                    echo "<h3>" . $vehicle["name"] . "</h3>";
                    echo "<h4 style=\"margin-bottom:0px;\"><span class=\"hidden\">$id</span></h4>";
                    echo "<p class=\"pulse\" style=\"margin-top:0px;padding-top:0px;\"><i>Hover to reveal ID</i></p>";
                    echo "<label for=\"vehicle>$id>name\">Name</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9\- \/'\(\).,]+\" max=\"100\" name=\"vehicle>$id>name\" id=\"vehicle>$id>name\" value=\"" . $vehicle["name"] . "\" required><br><br>";
                    echo "<label for=\"vehicle>$id>year\">Year</label>: <input type=\"number\" step=\"1\" min=\"1900\" max=\"" . intval(date("Y"))+1 . "\" name=\"vehicle>$id>year\" id=\"vehicle>$id>year\" placeholder=\"2014\" value=\""; if ($vehicle["year"] !== 0) { echo $vehicle["year"]; } echo "\"><br>";
                    echo "<label for=\"vehicle>$id>make\">Make</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9\- \/'\(\).,]+\" max=\"100\" name=\"vehicle>$id>make\" id=\"vehicle>$id>make\" placeholder=\"Toyota\" value=\"" . $vehicle["make"] . "\"><br>";
                    echo "<label for=\"vehicle>$id>model\">Model</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9\- \/'\(\).,]+\" max=\"100\" name=\"vehicle>$id>model\" id=\"vehicle>$id>model\" placeholder=\"Camry\" value=\"". $vehicle["model"] . "\"><br>";
                    echo "<label for=\"vehicle>$id>vin\">VIN</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9]+\" max=\"20\" name=\"vehicle>$id>vin\" id=\"vehicle>$id>vin\" placeholder=\"Vehicle Identification Number\" value=\"" . $vehicle["vin"] . "\">";
                    echo "<br><a class=\"button\" href=\"?delete=$id\">Delete</a> ";
                    echo "<input class=\"button\" type=\"submit\" id=\"submit\" name=\"submit\" value=\"Edit\"><br>";
                    echo "<hr style=\"width:100px;\">";
                }
                ?>
            </form>
            <form method="POST">
                <h3>New Vehicle</h3>
                <?php
                echo "<label for=\"vehicle>new>name\">Name</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9\- \/'\(\).,]+\" max=\"100\" name=\"vehicle>new>name\" id=\"vehicle>new>name\" placeholder=\"Recognizable Nickname\" required><br><br>";
                echo "<label for=\"vehicle>new>year\">Year</label>: <input type=\"number\" step=\"1\" min=\"1900\" max=\"" . intval(date("Y"))+1 . "\" name=\"vehicle>new>year\" id=\"vehicle>new>year\" placeholder=\"2014\"><br>";
                echo "<label for=\"vehicle>new>make\">Make</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9\- \/'\(\).,]+\" max=\"100\" name=\"vehicle>new>make\" id=\"vehicle>new>make\" placeholder=\"Manufacturer\"><br>";
                echo "<label for=\"vehicle>new>model\">Model</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9\- \/'\(\).,]+\" max=\"100\" name=\"vehicle>new>model\" id=\"vehicle>new>model\" placeholder=\"Camry\"><br>";
                echo "<label for=\"vehicle>new>vin\">VIN</label>: <input type=\"text\" pattern=\"[a-zA-Z0-9]+\" max=\"20\" name=\"vehicle>new>vin\" id=\"vehicle>new>vin\" placeholder=\"Vehicle Identification Number\"><br>";
                ?>

                <br><input class="button" type="submit" id="submit" name="submit" value="Create">
            </form>
        </main>
    </body>
</html>
