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
        <link rel="stylesheet" href="./assets/styles/main.css">
        <link rel="stylesheet" href="./assets/fonts/lato/latofonts.css">
    </head>
    <body>
        <main>
            <div class="navbar" role="navigation">
                <a class="button" role="button" href="<?php echo $portal_config["auth"]["provider"]["signup"]; ?>">Sign Up</a>
                <a class="button" role="button" href="<?php echo $portal_config["auth"]["provider"]["signin"]; ?>?redirect=<?php echo $_SERVER["REQUEST_URI"] ?>">Sign In</a>
            </div>
            <h1>Portal</h1>
            <p>Welcome to Portal! You've been directed to this page since you are not currently signed in. To begin using Portal, either log in to an existing account, or create a new one.</p>
            <hr>

            <h2>Introduction</h2>
            <p>Portal is a fleet management utility for vehicles running <a href="https://v0lttech.com/predator.php">Predator</a> dash-cams. Portal allows owners or managers to view real-time images from their vehicles' dash-cams, as well as telemetry like location and speed. Whether you just want to monitor a one or two personal vehicles, or an entire business fleet, Portal's pay-as-you-go model scales to fit your needs.</p>
        </main>
    </body>
</html>
