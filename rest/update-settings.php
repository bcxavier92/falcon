<?php
    /**
     * Description: Updates settings by all deleting fn_settings rows and replacing them
     * Method: Post
     * 
     * Parameters:
     * _key-{setting} - All parameters that start with "_key-" will be treated as the setting key
     * fn-token - Falcon CSRF token
     * 
     * Response:
     * Gives success boolean and message
     * 
     * Response codes:
     * 400 - Bad request
     * 500 - Error
     * 201 - Success
     * 
     */
    require "../core/falcon.php";

    header('Content-type: application/json');

    Falcon::startSession();
    Falcon::restRequireAdminAuth();

    // Check method
    if($_SERVER["REQUEST_METHOD"] !== "POST") {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid request method"]);
    }

    // Make sure csrf is passed
    if(!Falcon::isDefined($_POST, "fn-token")) {
        Falcon::restDie(400, ["success" => false, "message" => "CSRF token is required"]);
    }

    // Verify csrf token
    if(!Falcon::validateCsrfToken("fn-settings-update", $_POST["fn-token"])) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid CSRF token"]);
    }

    // Get settings from post data
    $settings = [];
    foreach(array_keys($_POST) as $postKey) {
        // If post key starts with "_key-", it is a setting key
        if(substr($postKey, 0, 5) === "_key-") {
            $key = substr($postKey, 5);

            // Key length must be 2 characters
            if(strlen($key) < 2) {
                Falcon::restDie(400, ["success" => false, "message" => "Key length must be at least 2 characters"]);
            }

            // Allow only (a-Z0-9_) for key names
            if(!preg_match("/^[a-z0-9_]+$/i", $key)) {
                Falcon::restDie(400, ["success" => false, "message" => "Invalid characters in key. Use (a-Z0-9_)"]);
            }

            // Prevent duplicate keys
            if(in_array($key, array_keys($settings))) {
                Falcon::restDie(400, ["success" => false, "message" => "Duplicate key found"]);
            }

            // Limit key length to 255
            if(strlen($key) > 255) {
                Falcon::restDie(400, ["success" => false, "message" => "Key length too long. Stay at or below 255 characters."]);
            }

            // Limit value length to 2048
            $value = $_POST[$postKey];
            if(strlen($value) > 2048) {
                Falcon::restDie(400, ["success" => false, "message" => "Value length too long. Stay at or below 2048 characters."]);
            }

            // Prevent falcon version from being altered
            if($key === "fn_version" && $value !== Falcon::getVersion()) {
                Falcon::restDie(400, ["success" => false, "message" => "Cannot alter Falcon version"]);
            }

            $settings[$key] = $value;
        }
    }

    // Make sure there are settings to update
    if(!count($settings)) {
        Falcon::restDie(400, ["success" => false, "message" => "No settings were passed"]);
    }

    if(!in_array("fn_version", array_keys($settings))) {
        Falcon::restDie(400, ["success" => false, "message" => "Cannot delete falcon version"]);
    }

    // Prepare query
    $strReplaceSettings = "DELETE FROM fn_settings; INSERT INTO fn_settings (setting_key, setting_value) VALUES ";

    $startedRows = false;
    $args = [];
    foreach($settings as $key => $value) {
        if($startedRows) $strReplaceSettings .= ",";

        $strReplaceSettings .= "(?,?)";
        array_push($args, $key, $value);

        $startedRows = true;
    }

    $strReplaceSettings .= ";";

    $stmtReplaceSettings = Falcon::getMysqlConnection()->prepare($strReplaceSettings);
    $success = $stmtReplaceSettings->execute($args);

    if(!$success) {
        Falcon::restDie(500, ["success" => false, "message" => "Could not update settings"]);
    }

    Falcon::restDie(201, ["success" => true, "message" => "success"]);
?>