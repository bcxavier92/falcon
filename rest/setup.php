<?php
    /**
     * Description: Sets up Falcon
     * Method: Post
     * 
     * Parameters:
     * site-name - Website name
     * json-path - Location of json file to store mysql credentials
     * mysql-host - Mysql host
     * mysql-port - Mysql port
     * mysql-database - Mysql database
     * mysql-user - Mysql user
     * mysql-password - Mysql password
     * username - Admin account username
     * email - Admin account email
     * password - Admin account password
     * confirm-password - Admin account confirm password
     * 
     * Response:
     * success - Boolean signifying setup success
     * message - Descriptive message
     * redirect - URL to login page sent only when setup is successful
     * 
     * Response codes:
     * 500 - Server error
     * 400 - Bad requires
     * 201 - Successful setup
     * 
     * NOTES:
     * No session is necessary here
     * $conn must be killed manually since it is started in this script and not handled by falcon.php
     * 
     */

    define("FnSkipInit", true);
    require "../core/falcon.php";

    header('Content-type: application/json');

    // Kill if falcon has already been setup
    if(FnSetup::hasBeenSetup()) {
        Falcon::restDie(400, ["success" => false, "message" => "Falcon already setup"]);
    }

    // Check method
    if($_SERVER["REQUEST_METHOD"] !== "POST") {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid request method"]);
    }

    // Check all parameters defined
    if(!Falcon::isDefined($_POST, "site-name")) {
        Falcon::restDie(400, ["success" => false, "message" => "Site name required"]);
    }

    if(!Falcon::isDefined($_POST, "json-path")) {
        Falcon::restDie(400, ["success" => false, "message" => "Json file location required"]);
    }

    if(!Falcon::isDefined($_POST, "mysql-host")) {
        Falcon::restDie(400, ["success" => false, "message" => "MySQL host required"]);
    }

    if(!Falcon::isDefined($_POST, "mysql-port")) {
        Falcon::restDie(400, ["success" => false, "message" => "MySQL port required"]);
    }

    if(!Falcon::isDefined($_POST, "mysql-database")) {
        Falcon::restDie(400, ["success" => false, "message" => "MySQL database required"]);
    }

    if(!Falcon::isDefined($_POST, "mysql-user")) {
        Falcon::restDie(400, ["success" => false, "message" => "MySQL user required"]);
    }

    if(!Falcon::isDefined($_POST, "mysql-password")) {
        Falcon::restDie(400, ["success" => false, "message" => "MySQL password required"]);
    }

    if(!Falcon::isDefined($_POST, "username")) {
        Falcon::restDie(400, ["success" => false, "message" => "Admin username required"]);
    }

    if(!Falcon::isDefined($_POST, "email")) {
        Falcon::restDie(400, ["success" => false, "message" => "Admin email required"]);
    }

    if(!Falcon::isDefined($_POST, "password")) {
        Falcon::restDie(400, ["success" => false, "message" => "Admin password required"]);
    }

    if(!Falcon::isDefined($_POST, "confirm-password")) {
        Falcon::restDie(400, ["success" => false, "message" => "Admin password confirmation required"]);
    }

    // Check site name characters (a-Z0-9 _-)
    $siteName = trim($_POST["site-name"]);
    if(!preg_match("/^[a-zA-Z0-9_\- ]+$/i", $siteName)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid characters in site name. Use a-Z0-9 -_"]);
    }

    // Check site name length
    if(strlen($siteName) > 50) {
        Falcon::restDie(400, ["success" => false, "message" => "Site name too long. Stay at or below 50 characters."]);
    }

    // Check if json path is valid
    // Realpath is used here to determine absolute pathname and returns false if path is invalid
    $jsonPath = realpath(trim($_POST["json-path"]));
    if(!$jsonPath) {
        Falcon::restDie(400, ["success" => false, "message" => "JSON path does not exist or has invalid permissions"]);
    }

    // Check that json path is file
    if(!is_file($jsonPath)) {
        Falcon::restDie(400, ["success" => false, "message" => "JSON path is not a file"]);
    }

    // Check that file has .json file extension
    if(strtolower(pathinfo($jsonPath, PATHINFO_EXTENSION)) !== "json") {
        Falcon::restDie(400, ["success" => false, "message" => "JSON file has invalid extension"]);
    }

    // Make sure that file is empty
    $jsonContents = file_get_contents($jsonPath);
    // Strlen is used because technically "0" would be falsey
    if(strlen($jsonContents)) {
        Falcon::restDie(400, ["success" => false, "message" => "JSON file must be empty"]);
    }

    // Check username characters (a-Z0-9_)
    $username = trim($_POST["username"]);
    if(!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid characters in username. Use a-Z0-9_"]);
    }

    // Check username length
    if(strlen($username) > 24) {
        Falcon::restDie(400, ["success" => false, "message" => "Username too long. Stay at or below 24 characters."]);
    }

    // Validate email
    $email = trim($_POST["email"]);
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid email address"]);
    }

    // Sanitize email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Check email length
    if(strlen($email) > 320) {
        Falcon::restDie(400, ["success" => false, "message" => "Email too long. Stay at or below 320 characters."]);
    }

    // Check password length
    $password = $_POST["password"];
    if(strlen($password) > 50) {
        Falcon::restDie(400, ["success" => false, "message" => "Password too long. Stay at or below 50 characters."]);
    }

    // Check password confirmation matches
    $confirmPassword = $_POST["confirm-password"];
    if($password !== $confirmPassword) {
        Falcon::restDie(400, ["success" => false, "message" => "Passwords do not match"]);
    }

    $mysqlHost = trim($_POST["mysql-host"]);
    $mysqlPort = trim($_POST["mysql-port"]);
    $mysqlDatabase = trim($_POST["mysql-database"]);
    $mysqlUser = trim($_POST["mysql-user"]);
    $mysqlPassword =trim($_POST["mysql-password"]);

    // Dont know the actual max length for these, but this is just a catch all to make sure MySQL doesn't get overwhelemed
    // by a ridiculously long string
    // mysqlPort has quotes put around it in the length check, in case an int gets passed it is converted to a string
    if(strlen($mysqlHost) > 1000 || strlen("$mysqlPort") > 10 || strlen($mysqlDatabase) > 1000 || strlen($mysqlUser) > 1000 || strlen($mysqlPassword) > 1000) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid MySQL credentials"]);
    }

    // Validate host
    if(!filter_var($mysqlHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid MySQL hostname"]);
    }

    // Validate port is a number >= 0
    if(!ctype_digit($mysqlPort)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid MySQL port"]);
    }

    // Validate database name
    if(!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $mysqlDatabase)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid MySQL database name. Must use a-Z0-9_ and start with a letter."]);
    }

    // Try MySQL connection
    $conn = null;
    try {
        $conn = new PDO("mysql:host=$mysqlHost;port=$mysqlPort;charset=utf8mb4", $mysqlUser, $mysqlPassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {  }

    // Send 500 if connection fails
    if(!$conn) {
        Falcon::restDie(500, ["success" => false, "message" => "Could not connect to MySQL database. Please check the provided credentials and try again."]);
    }

    // Check if database exists
    $stmtShowDatabase = $conn->prepare("SHOW DATABASES LIKE ?;");
    $stmtShowDatabase->execute([$mysqlDatabase]);
    $databaseExists = $stmtShowDatabase->fetch(PDO::FETCH_ASSOC);

    // Send 400 if database does not exist
    if(!$databaseExists) {
        $conn = null;
        Falcon::restDie(400, ["success" => false, "message" => "Database does not exist"]);
    }

    // Use the database
    // Prepared statements don't work here, but the database name has already been sanitized and checked
    // This code isn't meant to check if the user has permission to use the database
    // If the user doesn't have permission, future queries will fail
    $stmtUseDatabase = $conn->prepare("USE $mysqlDatabase;");
    $useSuccess = $stmtUseDatabase->execute();
    if(!$useSuccess) {
        $conn = null;
        Falcon::restDie(500, ["success" => false, "message" => "Could not use database."]);
    }

    // Check if database already has falcon tables
    if(FnSetup::hasFalconTables($conn)) {
        $conn = null;
        Falcon::restDie(400, ["success" => false, "message" => "Database already has falcon tables. Please delete them and try again."]);
    }

    // Check if harness exists
    $harnessPath = __DIR__ . "/../core/harness.php";
    if(is_file($harnessPath)) {
        $conn = null;
        Falcon::restDie(400, ["success" => false, "message" => "harness.php already exists"]);
    }

    /**
     * All checks passed. Start the setup
     */

    // Get Base url
    // Break uri apart and find the last position of "falcon" - This is where falcon is located on the server
    $slashSplit = explode("/", $_SERVER['REQUEST_URI']);
    $lastPos = 0;
    for($i = 0; $i < count($slashSplit); $i++) {
        if($slashSplit[$i] === "falcon") {
            $lastPos = $i;
        }
    }

    // Build base url
    $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    for($i = 0; $i < count($slashSplit); $i++) {
        if($i < $lastPos) {
            $baseUrl .= $slashSplit[$i] . "/";
        } else {
            break;
        }
    }

    // Remove the trailing slash
    $baseUrl = substr($baseUrl, 0, -1);

    // Create harness.php
    $jsonPathForwardSlashes = str_replace("\\", "/", $jsonPath);
    $harnessWriteSuccess = file_put_contents($harnessPath, "<?php define(\"FnJsonPath\", \"$jsonPathForwardSlashes\"); ?>");
    if(!$harnessWriteSuccess) {
        $conn = null;
        Falcon::restDie(500, ["success" => false, "message" => "Critical: Could not write to harness.php - Check file permissions"]);
    }

    // Store mysql credentials in json file
    $mysqlCredentials = [
        "mysqlHost" => $mysqlHost,
        "mysqlPort" => $mysqlPort,
        "mysqlDatabase" => $mysqlDatabase,
        "mysqlUser" => $mysqlUser,
        "mysqlPassword" => $mysqlPassword
    ];

    $jsonWriteSuccess = file_put_contents($jsonPath, json_encode($mysqlCredentials));
    if(!$jsonWriteSuccess) {
        $conn = null;
        Falcon::restDie(500, ["success" => false, "message" => "Critical: Could not write to json file - Check file permissions"]);
    }

    // Setup database
    $dbSetupResult = FnSetup::setupNewDatabase($conn, $siteName, $baseUrl);

    // A !== true check is required here because the table name will be returned on failure, which is a truthy value
    if($dbSetupResult !== true) {
        $conn = null;
        Falcon::restDie(500, ["success" => false, "message" => "Critical: Error setting up table \"dbSetupResult\""]);
    }

    // Create admin account
    // No need to check for duplicate values because the table was just created
    $uuid = Falcon::generateUuid();
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $accountCreated = Falcon::createAccount($conn, $uuid, $username, $email, $passwordHash, true);

    if(!$accountCreated) {
        $conn = null;
        Falcon::restDie(500, ["success" => false, "message" => "Critical: Could not create admin account"]);
    }

    $conn = null;
    Falcon::restDie(201, ["success" => true, "message" => "success", "redirect" => "$baseUrl/falcon/admin/"]);
?>