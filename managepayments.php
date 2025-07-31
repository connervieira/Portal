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
        <title>Portal - Manage Payments</title>
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="management.php">Back</a>
            </div>
            <h1>Portal</h1>
            <h2>Manage Payments</h2>
            <hr>

            <?php
            if ($portal_config["payment"]["enabled"] == true) {
                echo "<p>Use the button below to cancel or update existing recurring payments.</p>";
                echo "<a class=\"button\" href=\"" . $portal_config["payment"]["stripe"]["link"]["management"] . "\">Manage Payments</a><br>";
                $user_database = load_database("users");

                echo "<p><i>Unless cancelled, subscriptions auto-renew every month.</i></p>";
            } else {
                echo "<p>Payments have been disabled on this instance.</p>";
                exit();
            }
            ?>

            <hr style="width:80%">

            <h3>Vehicles</h3>
            <p>Each vehicle that receives telemetry requires an active subscription. If the number of registered vehicles exceeds the subscription quantity, then telemetry ingest will be disabled on all vehicles.</p>
            <?php
            if (time() - $user_database[$username]["payment"]["vehicles"]["expiration"] < 0) {
                echo "<p style=\"margin-bottom:0px;margin-top:0px;\" class=\"good\"><b>Status</b>: Active</p>";
                echo "<p style=\"margin-bottom:0px;margin-top:0px;\" "; if (sizeof(array_keys($user_database[$username]["vehicles"])) > $user_database[$username]["payment"]["vehicles"]["count"]) { echo " class=\"error\""; } echo "><b>Vehicle count</b>: " . sizeof(array_keys($user_database[$username]["vehicles"])) . " registered / " . $user_database[$username]["payment"]["vehicles"]["count"] . " permitted</p>";
                echo "<p style=\"margin-top:0px;\"><b>Expires</b>: in " . floor(($user_database[$username]["payment"]["vehicles"]["expiration"] - time())/86400) . " days</p>";
            } else if ($user_database[$username]["payment"]["vehicles"]["expiration"] > 0) {
                echo "<a class=\"button\" href=\"" . $portal_config["payment"]["stripe"]["link"]["vehicles"] . "\">Purchase Subscription</a>";
                echo "<p style=\"margin-bottom:0px;margin-top:0px;\" class=\"error\"><b>Status</b>: Expired (" . ceil((time() - $user_database[$username]["payment"]["vehicles"]["expiration"])/86400) . " days ago)</p>";
            } else {
                echo "<a class=\"button\" href=\"" . $portal_config["payment"]["stripe"]["link"]["vehicles"] . "\">Purchase Subscription</a>";
                echo "<p><b>Status</b>: Not active</p>";
            }
            ?>

            <hr style="width:80%">
            <h3>Storage</h3>
            <p>By default, each vehicle has 1GB of free storage for historical vehicle data logs. This should be plenty for the majority of users, but you can purchase additional storage by the gigabyte as needed. Each gigabyte purchase applies to all vehicles individually, and is not shared between vehicles. For example, if you have 3 vehicles and purchase 5GB of storage, each vehicle individual will get 6GB of storage (1GB included + 5GB purchased) for a total of 18GB across your account.</p>
            <?php
            $storage_usage = get_location_storage_usage_largest($username, $user_database)[1]/1000000000; // Get the current storage usage in GB.
            $storage_usage = round($storage_usage * 1000)/1000;
            $storage_capacity = storage_capacity($username, $user_database)/1000000000;
            $storage_capacity = round($storage_capacity * 1000)/1000;
            if (time() - $user_database[$username]["payment"]["storage"]["expiration"] < 0) {
                echo "<p style=\"margin-bottom:0px;margin-top:0px;\" class=\"good\"><b>Status</b>: Active</p>";
                echo "<p style=\"margin-top:0px;\"><b>Expires</b>: in " . floor(($user_database[$username]["payment"]["storage"]["expiration"] - time())/86400) . " days</p>";
            } else if ($user_database[$username]["payment"]["storage"]["expiration"] > 0) {
                echo "<a class=\"button\" href=\"" . $portal_config["payment"]["stripe"]["link"]["storage"] . "\">Purchase Subscription</a><br><br>";
                echo "<p style=\"margin-bottom:0px;margin-top:0px;\" class=\"error\"><b>Status</b>: Expired (" . ceil((time() - $user_database[$username]["payment"]["storage"]["expiration"])/86400) . " days ago)</p>";
            } else {
                echo "<a class=\"button\" href=\"" . $portal_config["payment"]["stripe"]["link"]["storage"] . "\">Purchase Subscription</a><br><br>";
                echo "<p style=\"margin-bottom:0px;margin-top:0px;\"><b>Status</b>: Not active</p>";
            }
            echo "<p style=\"margin-bottom:0px;margin-top:0px;\"><b>Capacity</b>: " . $storage_capacity . " GB per vehicle (" . $storage_capacity * $user_database[$username]["payment"]["vehicles"]["count"] . " GB total)</p>";
            echo "<p style=\"margin-bottom:0px;margin-top:0px;\"><b>Usage</b>: " . $storage_usage . " GB / " . $storage_capacity . " GB</p>";
            echo "<p><i>The storage usage shown above is for the vehicle with the most capacity used. There may be vehicles with less of their capacity used.</i></p>";
            ?>
        </main>
    </body>
</html>
