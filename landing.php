<?php
include "./config.php";

include $portal_config["auth"]["provider"]["core"];
if ($_SESSION['authid'] == "dropauth") { // Check to see if the user is signed in.
    header("Location: ./index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Portal - Landing</title>
        <link rel="stylesheet" href="./assets/styles/landing.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="<?php echo $portal_config["auth"]["provider"]["signup"]; ?>">Sign Up</a>
                <a class="button" role="button" href="<?php echo $portal_config["auth"]["provider"]["signin"]; ?>?redirect=<?php echo $_SERVER["REQUEST_URI"] ?>">Sign In</a>
            </div>
            <h1><b>V0LT</b> <span style="color:red;animation-delay:1s;">PORTAL</span></h1>
            <p class="intro">Welcome to Portal!<br><br>You've been directed to this page since you are not currently signed in.<br>To begin using Portal, either log in to an existing account, or create a new one.</p>
            <hr>

            <h2>Introduction</h2>
            <p>Portal is a fleet management utility for vehicles running <a href="https://v0lttech.com/predator.php">Predator</a> dash-cams (or similar compatible software/hardware). Portal allows owners or managers to view real-time images from their vehicles' dash-cams, as well as telemetry like location and speed. Portal even supports telemetry back-logging, which allows Predator to record telemetry offline, and automatically upload the back-log once connectivity is restored.</p>
            <p>Whether you just want to monitor one or two personal vehicles, or an entire business fleet, Portal's pay-as-you-go model scales to fit your needs. A flat-rate of $<?php echo $portal_config["payment"]["pricing"]["cost_per_vehicle"]; ?> per month per vehicle includes unlimited telemetry submissions (with a 10 second cooldown) and 1GB of location history storage. Portal also makes it easy to download and export your data in platform agnostic formats to make it easy to share edit data.</p>
            <div style="text-align:center;width:100%;">
                <img src="./assets/images/screenshots/dashboard.png" style="border: 10px solid; border-radius: 25px;width:80%;"><br><br>
                <img src="./assets/images/screenshots/track.png" style="border: 10px solid; border-radius: 25px;width:80%;">
            </div>
        </main>
    </body>
</html>
