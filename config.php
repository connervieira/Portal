<?php
$portal_config_filepath = "./config.json";

if (is_writable(dirname($portal_config_filepath)) == false) {
    echo "<p class=\"error\">The directory '" . realpath(dirname($portal_config_filepath)) . "' is not writable to PHP.</p>";
    exit();
}

// Load and initialize the database.
if (file_exists($portal_config_filepath) == false) { // Check to see if the database file doesn't exist.
    $portal_configuration_database_file = fopen($portal_config_filepath, "w") or die("Unable to create configuration database file."); // Create the file.

    $portal_config = array();
    $portal_config["auth"]["access"]["whitelist"] = [];
    $portal_config["auth"]["access"]["blacklist"] = [];
    $portal_config["auth"]["access"]["admin"] = ["admin"];
    $portal_config["auth"]["access"]["mode"] = "whitelist";
    $portal_config["auth"]["provider"]["core"] = "../dropauth/authentication.php";
    $portal_config["auth"]["provider"]["signin"] = "../dropauth/signin.php";
    $portal_config["auth"]["provider"]["signout"] = "../dropauth/signout.php";
    $portal_config["auth"]["provider"]["signup"] = "../dropauth/signup.php";
    $portal_config["payment"]["enabled"] = false; // Setting this to 'false' will disable payments, and unrestrict all user accounts. If you intend to self-host Portal, this should be disabled.
    $portal_config["payment"]["pricing"]["cost_per_vehicle"] = 25;
    $portal_config["payment"]["pricing"]["cost_per_GB"] = 5;
    $portal_config["payment"]["stripe"]["auth"]["account_key"] = "";
    $portal_config["payment"]["stripe"]["auth"]["client_secret"] = "";
    $portal_config["databases"]["users"]["location"] = "/var/www/protected/portal/users.json"; // Persistent user data storage.
    $portal_config["databases"]["vehicles"]["location"] = "/dev/shm/portal/vehicles/"; // Volatile vehicle information storage (images).
    $portal_config["storage"]["gps_tracks"]["location"] = "/var/www/protected/portal/tracks/"; // Persistent GPS track storage location.
    $portal_config["storage"]["gps_tracks"]["default_capacity"] = 1; // The default storage capacity for location tracks (in GB) per vehicle.
    $portal_config["storage"]["gps_tracks"]["auto_delete"] = true; // Determines whether old location tracks will be automatically deleted as space runs out.

    fwrite($portal_configuration_database_file, json_encode($portal_config, JSON_UNESCAPED_SLASHES)); // Set the contents of the database file to the placeholder configuration.
    fclose($portal_configuration_database_file); // Close the database file.
}

if (file_exists($portal_config_filepath) == true) { // Check to see if the item database file exists. The database should have been created in the previous step if it didn't already exists.
    $portal_config = json_decode(file_get_contents($portal_config_filepath), true); // Load the database from the disk.
} else {
    echo "<p class=\"error\">The configuration database failed to load.</p>"; // Inform the user that the database failed to load.
    exit(); // Terminate the script.
}


if (!function_exists("save_config")) { // Check to see if the save_config function needs to be created.
    function save_config($portal_config) {
        global $portal_config_filepath;
        file_put_contents($portal_config_filepath, json_encode($portal_config, JSON_UNESCAPED_SLASHES));
    }
}
?>
