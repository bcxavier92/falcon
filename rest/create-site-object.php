<?php
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
    if(!Falcon::validateCsrfToken("fn-create-site-object", $_POST["fn-token"])) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid CSRF token"]);
    }

    // Check object name defined
    if(!Falcon::isDefined($_POST, "object-name")) {
        Falcon::restDie(400, ["success" => false, "message" => "Object name is required"]);
    }

    // Check object name characters
    $objectName = trim($_POST["object-name"]);
    if(!preg_match("/^[a-z0-9_]+$/i", $objectName)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid characters in object name. Use (a-Z0-9_)"]);
    }

    // Check object name min length
    if(strlen($objectName) < 2) {
        Falcon::restDie(400, ["success" => false, "message" => "Object name length must be at least 2 characters"]);
    }

    // Check object name max length
    if(strlen($objectName) > 32) {
        Falcon::restDie(400, ["success" => false, "message" => "Object name too long. Stay at or below 32 characters."]);
    }

    $keys = [];
    $siteObjectTypes = [];
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
            if(in_array($key, array_keys($keys))) {
                Falcon::restDie(400, ["success" => false, "message" => "Duplicate key found"]);
            }

            // Limit key length to 32
            if(strlen($key) > 32) {
                Falcon::restDie(400, ["success" => false, "message" => "Key length too long. Stay at or below 32 characters."]);
            }

            // Validate type
            $type = $_POST[$postKey];
            $validTypes = [
                "object-ref" => "varchar(32)", 
                "lang-ref" => "varchar(255)", 
                "file" => "varchar(32)", 
                "short-text" => "varchar(255)", 
                "med-text" => "varchar(2048)", 
                "long-text" => "text(65535)", 
                "huge-text" => "mediumtext(16777215)", 
                "int" => "int(4)", 
                "big-int" => "bigint(8)", 
                "decimal" => "decimal(28, 10)", 
                "boolean" => "tinyint(1)"
            ];

            if(!in_array($type, array_keys($validTypes))) {
                Falcon::restDie(400, ["success" => false, "message" => "Invalid type"]);
            }

            // Push to object types array
            $siteObjectTypes[$key] = [$type, $validTypes[$type]];
        }
    }

    if(!count($keys)) {
        Falcon::restDie(400, ["success" => false, "message" => "Keys must be provided"]);
    }

    $conn = Falcon::getMysqlConnection();
    
    // Check that site object name is unique
    $stmtCheckObjectName = $conn->prepare("SELECT COUNT(*) FROM fn_site_objects WHERE object_name=?;");
    $stmtCheckObjectName->execute([
        $objectName
    ]);

    $objectExists = $stmtCheckObjectName->fetch(PDO::FETCH_COLUMN);
    if($objectExists) {
        Falcon::restDie(400, ["success" => false, "message" => "Site object by this name already exists"]);
    }

    // PDO not necessary because all values are strictly sanitized
    $strInsertObject = "INSERT INTO fn_site_objects (object_name, structure) VALUES ('$objectName', '";
    // PDO can't be used for executing this statement, but all parameter were strictly sanatized to (a-Z0-9_) earlier
    $strCreateObjectTable = "CREATE TABLE fn_so_$objectName (";

    $started = false;
    foreach($siteObjectTypes as $key => $type) {
        $falconType = $type[0];
        $mysqlType = $type[1];

        if($started) {
            $strInsertObject .= ",";
            $strCreateObjectTable .= ",";
        }

        $strInsertObject .= $falconType;
        // Underscore is added to the beginning of the key to prevent conflict with mysql keywords
        $strCreateObjectTable .= "_$key $mysqlType";

        $started = true;
    }

    $strInsertObject .= "');";
    $strCreateObjectTable .= ");";

    // Execute insert object
    $stmtInsertObject = $conn->prepare($strInsertObject);
    $successInsertObject = $stmtInsertObject->execute();

    if(!$successInsertObject) {
        Falcon::restDie(500, ["success" => false, "message" => "CRITICAL: Failed to insert object into Falcon site objects table"]);
    }

    // Execute create object table
    $stmtCreateObjectTable = $conn->prepare($strCreateObjectTable);
    $successCreateObjectTable = $stmtCreateObjectTable->execute();

    if(!$successCreateObjectTable) {
        Falcon::restDie(500, ["success" => false, "message" => "CRITICAL: Failed to create new site object table, but was inserted into Falcon site objects table. This will cause the object to not work properly. Please delete it and try again."]);
    }

    Falcon::restDie(201, ["success" => true, "message" => "success"]);
?>