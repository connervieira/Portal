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
        <title>Portal - Dashboard</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <div class="navbar" role="navigation">
            <a class="button" role="button" href="<?php echo $portal_config["auth"]["provider"]["signout"]; ?>">Logout</a>
            <a class="button" role="button" href="./management.php">Management</a>
            <?php
            if (in_array($username, $portal_config["auth"]["access"]["admin"]) == true) { // Check to see if this user is an instance administrator.
                echo '<a class="button" role="button" href="./configure.php">Configure</a>';
            }
            ?>
        </div>
        <h1>Portal</h1>
        <h2>Dashboard</h2>
        <main>

            <?php
            $user_database = load_database("users");
            if (in_array($username, array_keys($user_database)) == false) { // Check to see if this user is not yet in the database.
                $user_database[$username] = array(
                    "vehicles" => array(
                    ),
                    "settings" => array(
                        "auto_delete_location_logs" => true
                    ),
                    "widgets" => array(
                        array("type" => "vehicle_count"),
                        array("type" => "vehicles_active", "interval" => 5),
                        array("type" => "total_distance", "interval" => 30),
                    ),
                    "payment" => array(
                    )
                );

                save_database("users", $user_database);
            }
            ?>

            <hr>
            <div id="widgets">
                <?php
                include "./widgets.php";
                ?>
            </div>
            <hr>
            <div id="vehicles">
                <?php
                if (sizeof($user_database[$username]["vehicles"]) == 0) {
                    echo "<p><i>You have no vehicles.</i></p>";
                    echo "<a class=\"button\" href=\"./managevehicles.php\">Manage Vehicles</a>";
                } else {
                    foreach (array_keys($user_database[$username]["vehicles"]) as $vehicle) {
                        echo "<div class=\"vehicle_card\">";
                        echo "<h4>" . $user_database[$username]["vehicles"][$vehicle]["name"] . "</h4>";
                        echo "<a class=\"button\" href=\"vehicle.php?id=" . $vehicle . "\">View</a><br>";
                        echo "<br></div>";
                    }
                }
                ?>
            </div>
        </main>
    </body>
</html>
