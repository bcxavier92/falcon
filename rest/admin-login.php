<?php
    /**
     * Description: Attempts user login
     * Method: Post
     * 
     * Parameters:
     * username - Username for login
     * password - Password for login
     * fn-token - Falcon CSRF token
     * 
     * Response:
     * success - Boolean signifying login success
     * message - Descriptive message
     * redirect - URL to dashboard sent only when login is successful
     * 
     * Response Codes:
     * 500 - Error logging in
     * 400 - Bad request
     * 401 - Invalid credentials
     * 200 - Successful login
     * 
     */

    require "../core/falcon.php";

    Falcon::startSession();

    header('Content-type: application/json');

    if($_SERVER["REQUEST_METHOD"] !== "POST") {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid request method"]);
    }

    if(Falcon::getCurrentUser()) {
        Falcon::restDie(400, ["success" => false, "message" => "Already logged in"]);
    }

    if(!Falcon::isDefined($_POST, "username")) {
        Falcon::restDie(400, ["success" => false, "message" => "Username required"]);
    }

    if(!Falcon::isDefined($_POST, "password")) {
        Falcon::restDie(400, ["success" => false, "message" => "Password required"]);
    }

    if(!Falcon::isDefined($_POST, "fn-token")) {
        Falcon::restDie(400, ["success" => false, "message" => "CSRF token required"]);
    }

    if(!Falcon::validateCsrfToken("fn-admin-login", $_POST["fn-token"])) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid CSRF token"]);
    }

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $conn = Falcon::getMysqlConnection();

    // Get user uuid and check if user exists
    $uuid = Falcon::getUserUuid($conn, $username);
    if(!$uuid) {
        Falcon::restDie(401, ["success" => false, "message" => "Invalid credentials"]);
    }

    // Check if username and password are correct
    $validCredentials = Falcon::userCredentialsValid($conn, $username, $password);
    if(!$validCredentials) {
        Falcon::restDie(401, ["success" => false, "message" => "Invalid credentials"]);
    }

    // Check if user is admin
    $isAdmin = Falcon::isUserAdmin($conn, $uuid);
    if(!$isAdmin) {
        Falcon::restDie(401, ["success" => false, "message" => "Invalid credentials"]);
    }

    if(!Falcon::setCurrentUser($conn, $uuid)) {
        // Could not set user
        Falcon::restDie(500, ["success" => false, "message" => "Error while trying to login"]);
    }
    // Login success
    Falcon::restDie(200, ["success" => true, "message" => "success", "redirect" => Falcon::getBaseUrl() . "/falcon/admin/dashboard/"]);
?>