<?php

// This function checks if a given string is valid JSON.
function is_json($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}


function lock_file($file_path) {
    $locked_timestamp = round(microtime(true) * 1000);
    if (file_put_contents($file_path . ".lock", strval($locked_timestamp))) {
        return true;
    } else {
        echo "<p class=\"error\">The " . basename($file_path) . " file could not be locked.</p>";
        exit();
        return false;
    }
}

function unlock_file($file_path) {
    if (unlink($file_path . ".lock")) { // Remove the lock file for this file.
        return true;
    } else {
        echo "<p class=\"error\">The " . basename($file_path) . " file could not be unlocked.</p>";
        exit();
        return false;
    }
}

// This function returns "true" if the given file is unlocked, and "false" if the file is locked.
function is_file_unlocked($file_path) {
    if (file_exists($file_path . ".lock")) {
        $locked_timestamp = intval(file_get_contents(file_exists($file_path . ".lock"))); // Get the timestamp of when this file was locked (in milliseconds).
        if (round(microtime(true) * 1000) - $locked_timestamp > 5000) { // Check to see if it has been more than 5 seconds since the file was locked.
            // This case should generally never happen. In this case, it is likely that a script somewhere has terminated before the file could be properly unlocked.
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

// This function waits until the given file_path is unlocked.
function wait_for_unlocked($file_path) {
    for ($x = 0; $x <= 100; $x++) { // Run 100 times, checking to see if this file is unlocked.
        if (is_file_unlocked($file_path)) {
            return true;
        } else {
            usleep(50*1000); // Wait for 50 milliseconds.
        }
    }
    echo "<p class=\"error\">Failed to wait for unlock on '" . htmlspecialchars($file_path) . "'.</p>";
    exit();
}


// This function takes a vehicle ID, and returns the associated user.
function vehicle_to_user($vehicle_id, $user_database) {
    foreach (array_keys($user_database) as $user) {
        foreach (array_keys($user_database[$user]["vehicles"]) as $vehicle) {
            if ($vehicle == $vehicle_id) {
                return $user;
            }
        }
    }
    return false; // If the end of the loop is reached without identifying the associated user, then return 'false'.
}

// This function joins a list of paths, adding and removing forward slashes where necessary.
function join_paths($paths) {
    $trimmed_paths = array_map(function($path) {
        return rtrim($path, DIRECTORY_SEPARATOR);
    }, $paths);
    
    // Join the paths with the directory separator
    return implode(DIRECTORY_SEPARATOR, $trimmed_paths);
}


// This function checks to see if a string (like "UTC+05:00") is a valid UTC timezone offset.
function is_valid_timezone_offset($string) {
    $pattern = '/^UTC([+-])(\d{2}):(\d{2})$/';
    if (preg_match($pattern, $string, $matches)) {
        $hours = intval(substr($string, 4, 5));
        $minutes = intval(substr($string, 7, 8));
        return $hours <= 12 && $minutes < 60;
    } else {
        return false;
    }
}


// This function calculates the distance (in meters) between two coordinates.
function calculate_distance($lat1, $lon1, $lat2, $lon2) {
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $lon_delta = $lon2 - $lon1;
    $a = pow(cos($lat2) * sin($lon_delta), 2) + pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon_delta), 2);
    $b = sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon_delta);

    $angle = atan2(sqrt($a), $b);
    $earth_radius = 6371000;
    $distance = $angle * $earth_radius;
    return $distance;
}


// This function returns a sorted list of all GPS files associated with a given vehicle.
function list_gps_track_files($vehicle, $user_database) {
    global $portal_config;
    $user = vehicle_to_user($vehicle, $user_database);
    $track_directory = join_paths([$portal_config["storage"]["gps_tracks"]["location"], $user, $vehicle]);
    $track_files = scandir($track_directory);
    $sorted_files = array(); // This will hold a list of filtered files (only files with valid dates).
    foreach ($track_files as $file_name) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', substr(pathinfo(basename($file_name), PATHINFO_FILENAME), 0, 10))) {
            if (is_json(file_get_contents(join_paths([$track_directory, $file_name])))) { // Check to make sure the file is valid JSON.
                $sorted_files[] = $file_name;
            }
        }
    }
    sort($sorted_files); // Make sure the list of files is in chronological order.


    // Prepend each file name with the full directory path.
    $full_paths = array();
    foreach ($sorted_files as $file) {
        $full_paths[] = join_paths([$track_directory, $file]);
    }
    return $full_paths;
}


// This function returns the complete GPS log for the specified vehicle for the past N days.
function load_track_last_n_days($vehicle, $days, $user_database) {
    $all_location_files = list_gps_track_files($vehicle, $user_database);

    $relevant_files = array();
    foreach ($all_location_files as $file) {
        $age = floor((time() - strtotime(substr(basename($file), 0, 10)))/24/60/60); // Calculate how many days old this file is (rounded down).
        if ($age >= 0 and $age <= $days) { // Check to see if this file is within the time frame.
            $relevant_files[] = $file;
        }
    }

    $complete_track = array(); // This will hold every location point from the relevant files.
    foreach ($relevant_files as $file) {
        $file_contents = file_get_contents($file);
        $trackpoints = json_decode($file_contents, true);
        foreach (array_keys($trackpoints["track"]) as $timestamp) {
            $complete_track[$timestamp] = $trackpoints["track"][$timestamp];
        }
    }


    return $complete_track;
}

// This function returns the last known location of a given vehicle.
function most_recent_vehicle_location($vehicle, $user_database) {
    $all_location_files = list_gps_track_files($vehicle, $user_database);
    $last_file = end($all_location_files);
    if (strlen($last_file) == 0) { return false; } // Return 'false' if there are no files to read.
    $trackpoints = json_decode(file_get_contents($last_file), true);
    ksort($trackpoints["track"]);
    $final_trackpoint = end(array_keys($trackpoints["track"]));
    $trackpoints["track"][$final_trackpoint]["time"] = $final_trackpoint;
    return $trackpoints["track"][$final_trackpoint];
}

// This function returns a list of vehicle ID's that have been active in the past N seconds.
function active_vehicles($seconds, $user_database, $user) {
    $active_vehicles = array();

    foreach (array_keys($user_database[$user]["vehicles"]) as $vehicle) {
        $track = load_track_last_n_days($vehicle, 1, $user_database);
        if ($track != false) {
            $previous_timestamp = 0;
            $previous_location = array();
            foreach (array_keys($track) as $timestamp) {
                $time_difference = $timestamp - $previous_timestamp;
                if ($time_difference <= 2 * 60) { // Check to see if this timestamp is within a certain number of minutes of the previous timestamp.
                    if (in_array("lat", array_keys($previous_location)) and in_array("lon", array_keys($previous_location))) {
                        if (($previous_location["lat"] != 0.0 or $previous_location["lon"]) != 0.0 and ($track[$timestamp]["lat"] != 0.0 or $track[$timestamp]["lon"] != 0.0)) { // Make sure neither of the coordinates are null.
                            $distance = calculate_distance($previous_location["lat"], $previous_location["lon"], $track[$timestamp]["lat"], $track[$timestamp]["lon"]);
                            $speed = $distance/$time_difference; // Calculate the speed in meters per second.
                            if ($speed > 1 and $speed < 100) { // Check to see if the vehicle has moved at least 1 m/s, but less than 100 m/s since the last location.
                                if (time() - $timestamp <= $seconds) { // Check to see if this timestamp is within the specified
                                    $active_vehicles[] = $vehicle;
                                    break;
                                }
                            }
                        }
                    }
                }
                $previous_timestamp = $timestamp;
                $previous_location = $track[$timestamp];
            }
        }
    }
    return $active_vehicles;
}


// This function returns a list of vehicle ID's that have submitted data in the past N seconds.
function online_vehicles($seconds, $user_database, $user) {
    $online_vehicles = array();
    foreach (array_keys($user_database[$user]["vehicles"]) as $vehicle) {
        $most_recent_location = most_recent_vehicle_location($vehicle, $user_database);
        $age = time() - intval($most_recent_location["time"]); // Calculate how many days old this file is.
        if ($age < $seconds) {
            $online_vehicles[] = $vehicle;
        }
    }
    return $online_vehicles;
}


// This function calculates the utilization percentage (time spent moving / time spent stopped) for a given vehicle over the past number of days.
function calculate_vehicle_utilization($vehicle, $days, $user_database) {
    $track = load_track_last_n_days($vehicle, $days, $user_database);
    $total_minutes = $days * 24 * 60; // Calculate the total number of minutes in this interval.

    $active_minutes = 0;
    $previous_timestamp = 0;
    $previous_location = array();
    foreach (array_keys($track) as $timestamp) {
        $time_difference = $timestamp - $previous_timestamp;
        if ($time_difference <= 2 * 60) { // Check to see if this timestamp is within a certain number of minutes of the previous timestamp.
            if (in_array("lat", array_keys($previous_location)) and in_array("lon", array_keys($previous_location))) {
                if (($previous_location["lat"] != 0.0 or $previous_location["lon"]) != 0.0 and ($track[$timestamp]["lat"] != 0.0 or $track[$timestamp]["lon"] != 0.0)) { // Make sure neither of the coordinates are null.
                    $distance = calculate_distance($previous_location["lat"], $previous_location["lon"], $track[$timestamp]["lat"], $track[$timestamp]["lon"]);
                    $speed = $distance/$time_difference; // Calculate the speed in meters per second.
                    if ($speed > 1 and $speed < 100) { // Check to see if the vehicle has moved at least 1 m/s, but less than 100 m/s since the last location.
                        $active_minutes += $time_difference/60;
                    }
                }
            }
        }
        $previous_timestamp = $timestamp;
        $previous_location = $track[$timestamp];
    }
    return $active_minutes / $total_minutes;
}

// This function calculates the total distance driven by a given vehicle over the past number of days.
function calculate_vehicle_distance($vehicle, $days, $user_database) {
    $track = load_track_last_n_days($vehicle, $days, $user_database);

    $total_distance = 0;

    $previous_timestamp = 0;
    $previous_location = array();
    foreach (array_keys($track) as $timestamp) {
        $time_difference = $timestamp - $previous_timestamp;
        if ($time_difference <= 2 * 60) { // Check to see if this timestamp is within a certain number of minutes of the previous timestamp.
            if (in_array("lat", array_keys($previous_location)) and in_array("lon", array_keys($previous_location))) {
                if (($previous_location["lat"] != 0.0 or $previous_location["lon"]) != 0.0 and ($track[$timestamp]["lat"] != 0.0 or $track[$timestamp]["lon"] != 0.0)) { // Make sure neither of the coordinates are null.
                    $distance = calculate_distance($previous_location["lat"], $previous_location["lon"], $track[$timestamp]["lat"], $track[$timestamp]["lon"]);
                    $speed = $distance/$time_difference; // Calculate the speed in meters per second.
                    if ($speed > 1 and $speed < 100) { // Check to see if the vehicle has moved at least 1 m/s, but less than 100 m/s since the last location.
                        $total_distance += $distance;
                    }
                }
            }
        }
        $previous_timestamp = $timestamp;
        $previous_location = $track[$timestamp];
    }
    return $total_distance;
}

// This function calculates the total distance travelled by all of the user's vehicles over the previous number of days.
function calculate_vehicle_distance_total($user, $days, $user_database) {
    $total_distance = 0;
    foreach (array_keys($user_database[$user]["vehicles"]) as $vehicle) {
        $total_distance += calculate_vehicle_distance($vehicle, $days, $user_database);
    }
    return $total_distance;
}


// This function returns the most recent image for a given vehicle and camera encoded in base64.
function fetch_camera_preview($vehicle, $user, $device) {
    global $portal_config;
    $image_location = join_paths([$portal_config["databases"]["vehicles"]["location"], $user, $vehicle, $device. ".jpg"]);
    if (file_exists($image_location)) {
        $image_data = "data:image/jpg;base64," . base64_encode(file_get_contents($image_location));
        return $image_data;
    } else {
        return false;
    }
}


// This function returns the location track file storage usage for a given user in bytes.
function get_location_storage_usage_total($user, $user_database) {
    $total_usage = 0; // This is a placeholder that will be incremented to keep track of the total disk usage.
    foreach (array_keys($user_database[$user]["vehicles"]) as $vehicle) {
        $gps_track_files = list_gps_track_files($vehicle, $user_database);
        foreach ($gps_track_files as $file) {
            $total_usage += filesize($file);
        }
    }
    return $total_usage;
}

// This function returns the GPS location track file storage usage for a particular vehicle.
function get_location_storage_usage_vehicle($vehicle, $user_database) {
    $total_usage = 0; // This is a placeholder that will be incremented to keep track of the total disk usage.
    $gps_track_files = list_gps_track_files($vehicle, $user_database);
    foreach ($gps_track_files as $file) {
        $total_usage += filesize($file);
    }
    return $total_usage;
}
?>
