<?php
include "./config.php";
include "./databases.php";

require_once $portal_config["payment"]["stripe"]["auth"]["provider"];


$stripe = new \Stripe\StripeClient($portal_config["payment"]["stripe"]["auth"]["account_key"]);
$endpoint_secret = $portal_config["payment"]["stripe"]["auth"]["client_secret"];

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch(\UnexpectedValueException $e) {
    echo "Invalid payload";
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    echo "Invalid signature";
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':

        $session = $event->data->object;

        $stripe_response = $stripe->checkout->sessions->retrieve($session->id, ['expand' => ['line_items']]); // Fetch the items included with this purchase.


        $raw_purchase_info = json_decode(json_encode($stripe_response), true); // Convert the purchase information from Stripe into a PHP array.


        // Figure out what username the product should be associated with.
        $associated_user = "";
        foreach ($raw_purchase_info["custom_fields"] as $field) { // Iterate through each field in the "custom fields" section.
            if ($field["key"] == "v0ltusername") { // Check to see if this field is the V0LT username.
                $associated_user = $field["text"]["value"]; // Record the username entered in the V0LT username field.
            }
        }

        $user_database = load_database("users");
        if (in_array($associated_user, array_keys($user_database)) == false) {
            echo "Invalid V0LT username";
            http_response_code(400);
            exit();
        }

        // Figure out what products were purchased.
        foreach ($raw_purchase_info["line_items"]["data"] as $item) { // Iterate through each purchased item in the purchase information.
            $product_id = str_replace(" ", "", strtolower($item["description"])); // Record this product's ID by taking its description, converting it to lowercase, and removing all spaces.
            if (in_array($product_id, array("portal-vehicle", "portalvehicle"))) {
                $user_database[$associated_user]["payment"]["vehicles"]["count"] = intval($item["quantity"]);
                $user_database[$associated_user]["payment"]["vehicles"]["expiration"] = ceil(max(time(), $user_database[$associated_user]["payment"]["vehicles"]["expiration"]) + (86400*32)); // Make the product expire 32 days after the current expiration date or current date (whichever is later).
            } else if (in_array($product_id, array("portal-storage", "portalstorage"))) {
                $user_database[$associated_user]["payment"]["storage"]["capacity_gb"] = intval($item["quantity"]);
                $user_database[$associated_user]["payment"]["storage"]["expiration"] = ceil(max(time(), $user_database[$associated_user]["payment"]["storage"]["expiration"]) + (86400*32)); // Make the product expire 32 days after the current expiration date or current date (whichever is later).
            }
        }
        save_database("users", $user_database);

    // ... handle other event types
    default:
        echo 'Received unknown event type ' . $event->type;
}

http_response_code(200);

?>
