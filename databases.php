<?php
include "./config.php";
if (!function_exists("lock_file")) { include "./utils.php"; } // Only import `utils.php` if it hasn't already been imported.


// This function gets the file path for a given database name from the configuration.
if (!function_exists("get_database_path")) {
    function get_database_path($database_name) {
        global $portal_config;
        if (in_array($database_name, array_keys($portal_config["databases"])) == false) { // Check to see if this database ID does not exist in the configuration.
            echo "<p class=\"error\">The database name '" . htmlspecialchars($database_name) . "' does not exist in the configuration.</p>";
            return false;
        }
        if (in_array("location", array_keys($portal_config["databases"][$database_name])) == false or strlen($portal_config["databases"][$database_name]["location"]) == 0) { // Check to see if this database ID does not have a defined location.
            echo "<p class=\"error\">The database name '" . htmlspecialchars($database_name) . "' does not have an associated location in the configuration.</p>";
            return false;
        }
        $database_path = $portal_config["databases"][$database_name]["location"];

        if (is_writable(dirname($database_path)) == false) { // Check to see if the database location directory is not writable.
            echo "<p class=\"error\">The database directory for '" . htmlspecialchars($database_name) . "' at '" . dirname($database_path) . "' is not writable to PHP.</p>";
            return false;
        }
        if (file_exists($database_path) and is_writable($database_path) == false) { // Check to see if the database location directory is not writable.
            echo "<p class=\"error\">The database '" . htmlspecialchars($database_name) . "' at '" . realpath($database_path) . "' is not writable to PHP.</p>";
            return false;
        }

        return $database_path;
    }
}

// This function loads a database from disk.
if (!function_exists("load_database")) {
    function load_database($database_name) {
        global $portal_config;
        $database_path = get_database_path($database_name);
        
        if (file_exists($database_path) == false) { // Check to see if the database file doesn't yet exist.
            if (file_put_contents($database_path, "{}") == false) {
                echo "<p class=\"error\">The database '" . htmlspecialchars($database_name) . "' could not be created at " . htmlspecialchars($database_path) . "'.</p>"; // Inform the user that the database failed to load.
            }
        }

        if (file_exists($database_path) == true) { // Check to see if the item database file exists. The database should have been created in the previous step if it didn't already exists.
            wait_for_unlocked($database_path);
            lock_file($database_path);
            $database_file_contents = file_get_contents($database_path);
            unlock_file($database_path);
            $database = json_decode($database_file_contents, true); // Load the database from the disk.
            return $database;
        } else {
            echo "<p class=\"error\">The database name '" . htmlspecialchars($database_name) . "' failed to load.</p>"; // Inform the user that the database failed to load.
            exit();
        }
    }
}

// This function saves a database to disk.
if (!function_exists("save_database")) {
    function save_database($name, $data) {
        global $portal_config;
        $database_path = get_database_path($name);

        if (is_array($data) == false) {
            return false; // The data supplied is not an array.
        }
        if ($database_path == false) {
            return false; // The database path could not be determined.
        }

        wait_for_unlocked($database_path);
        lock_file($database_path);
        $response = file_put_contents($database_path, json_encode($data, (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)));
        unlock_file($database_path);

        if ($response) {
            return true; // The file save was successful.
        } else {
            return false; // The file save failed.
        }
    }
}
?>
