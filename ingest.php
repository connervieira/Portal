<?php
// This page ingests information received from Predator clients.
// Clients expect anything returned from this endpoint to be valid JSON.

include "./config.php";
include "./utils.php";
include "./databases.php";

$received_data = strval($_POST["data"]); // Get the submitted image data.
$data = json_decode($received_data, true); // Decode the JSON data received.

$user_database = load_database("users");
$associated_user = vehicle_to_user($_POST["identifier"], $user_database);

if ($associated_user == false) {
    echo "{\"success\": false, \"type\": \"client\", \"code\": \"permission_denied\", \"reason\": \"Permission denied\"}";
    exit();
} else {
    // Handle image data:
    if (in_array("image", array_keys($data))) { // Check to see if there is image data associated with this submission.
        // TODO: Check to see if it has been at least 10 seconds since the last image submission.
        foreach (array_keys($data["image"]) as $image) {
            $image_data = base64_decode($data["image"][$image]); // Decode the image from the received data.

            $mime_type = finfo_buffer(finfo_open(), $image_data, FILEINFO_MIME_TYPE);

            if (strtolower($mime_type) == "image/jpeg") { // Check to see if this image is a JPG.
                // TODO: Validate that this image is smaller than a certain size.
                $image_location = join_paths([$portal_config["databases"]["vehicles"]["location"], $associated_user, $_POST["identifier"], $image . ".jpg"]);
                if (is_dir(dirname($image_location)) == false) { // Check to see if the volatile storage directory needs to be initialized.
                    mkdir(dirname($image_location), 0777, true);
                }
                file_put_contents($image_location, $image_data);
            } else {
                echo "{\"success\": false, \"type\": \"client\", \"code\": \"image_invalid\", \"reason\": \"Invalid image data.\"}";
                exit();
            }
        }
    }

    // Handle GPS data:
    $location_storage_usage = get_location_storage_usage_vehicle($_POST["identifier"], $user_database);
    $max_storage_capacity = $portal_config["storage"]["gps_tracks"]["default_capacity"]*1000*1000*1000; // Calculate the max file capacity in bytes.
    if ($location_storage_usage >= $max_storage_capacity) { // Check to see if the storage capacity is full.
        if ($portal_config["storage"]["gps_tracks"]["auto_delete"]) { // Check to see if auto-delete is enabled.
            $gps_track_files = list_gps_track_files($_POST["identifier"], $user_database); // Get a list of all GPS track files associated with this vehicle.
            if (sizeof($gps_track_files) >= 2) { // Check to make sure there is are least 2 files that we can delete.
                $oldest_file = $gps_track_files[0];
                if (file_exists($oldest_file)) { // Check to make sure the oldest file actually exists.
                    if (unlink($oldest_file) == false) { // Check to see if the oldest file failed to be deleted.
                        echo "{\"success\": false, \"type\": \"server\", \"code\": \"clearing_space_failed\", \"reason\": \"Failed to delete the oldest GPS track file. This may indicate a server-side bug, so consider contacting V0LT.\"}";
                        exit();
                    }
                } else {
                    echo "{\"success\": false, \"type\": \"server\", \"code\": \"clearing_space_failed\", \"reason\": \"The oldest GPS track file could not be removed. This may indicate a server-side bug, so consider contacting V0LT.\"}";
                    exit();
                }
            } else {
                echo "{\"success\": false, \"type\": \"server\", \"code\": \"clearing_space_failed\", \"reason\": \"There are not enough GPS track files that can be removed to clear up sufficient storage. This may indicate a server-side bug, so consider contacting V0LT.\"}";
                exit();
            }
        } else {
            echo "{\"success\": false, \"type\": \"server\", \"code\": \"insufficient_space\", \"reason\": \"There is no available capacity to store this datapoint.\"}";
            exit();
        }
    }
    if (is_valid_timezone_offset($data["system"]["timezone"])) {
        $client_timezone = $data["system"]["timezone"];
    } else {
        echo "{\"success\": false, \"type\": \"client\", \"code\": \"timezone_offset_invalid\", \"reason\": \"The timezone offset is invalid.\"}";
        exit();
    }
    $point_time = intval($data["location"]["time"]);
    if (time() - $point_time >= 60*60*24*365*5 or time() - $point_time <= -1*60*60*25*5) { // Check to see if the timestamp is more than 5 years into the past, or more than 5 days into the future.
        echo "{\"success\": false, \"type\": \"client\", \"code\": \"timestamp_invalid\", \"reason\": \"Invalid timestamp\"}";
        exit();
    }
    $track_location = join_paths([$portal_config["storage"]["gps_tracks"]["location"], $associated_user, $_POST["identifier"], gmdate("Y-m-d", $point_time) . " UTC.json"]);
    if (is_dir(dirname($track_location)) == false) { // Check to see if the persistent track storage directory needs to be initialized.
        mkdir(dirname($track_location), 0777, true);
    }
    if (file_exists($track_location) == false) { // Check to see if this specific file needs to be initalized.
        file_put_contents($track_location, "{
            \"meta\": {
                \"timezone\": \"" . $client_timezone . "\",
                \"version\": \"1\"
            },
            \"track\": {
            }
        }");
    }
    if (file_exists($track_location)) { // Check to see if this specific file needs to be initalized.
        $gps_track = json_decode(file_get_contents($track_location), true); // Load the existing GPS track.
        $gps_track["track"][$point_time] = array(
            "lat" => round(floatval($data["location"]["lat"])*100000)/100000,
            "lon" => round(floatval($data["location"]["lon"])*100000)/100000,
            "alt" => round(floatval($data["location"]["alt"])*10)/10,
            "spd" => round(floatval($data["location"]["spd"])*10)/10,
            "head" => round(floatval($data["location"]["head"]))
        );
        ksort($gps_track["track"]);

        file_put_contents($track_location, json_encode($gps_track));
        echo "{\"success\": true}";
    } else {
        echo "{\"success\": false, \"type\": \"server\", \"code\": \"trackfile_creation_failed\", \"reason\": \"The track file could not be created.\"}";
        exit();
    }
}
?>
