<?php
    /**
     * Description: Creates site object category
     * Method: Post
     * 
     * Parameters:
     * category-name - Category name
     * _key-{keyName} - Input key name and data type (ex: "_key-name" => "short-text")
     * object-label - Name of key that will be used as label for objects
     * fn-token - Falcon CSRF token
     * 
     * Response:
     * success - Boolean signifying object category created
     * message - Descriptive message
     * 
     * Response Codes:
     * 500 - Error creating object category
     * 400 - Bad request
     * 201 - Successful creation
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
    if(!Falcon::validateCsrfToken("fn-create-site-object-category", $_POST["fn-token"])) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid CSRF token"]);
    }

    // Check category name defined
    if(!Falcon::isDefined($_POST, "category-name")) {
        Falcon::restDie(400, ["success" => false, "message" => "Object name is required"]);
    }

    // Check object label defined
    if(!Falcon::isDefined($_POST, "object-label")) {
        Falcon::restDie(400, ["success" => false, "message" => "Object label is required"]);
    }

    // Make sure object label exists as an input key
    // This does not need to be sanitized here because if it is in $_POST and starts with "_key-" it will be sanitized
    // below in the foreach loop. By checking if $_POST has the index of _key-$objectLabel it is garunteed it will be
    // checked later.
    $objectLabel = $_POST["object-label"];
    if(!Falcon::isDefined($_POST, "_key-$objectLabel")) {
        Falcon::restDie(400, ["success" => false, "message" => "Object label not found"]);
    }

    // Check category name characters
    $catName = trim($_POST["category-name"]);
    if(!preg_match("/^[a-z0-9_]+$/i", $catName)) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid characters in category name. Use (a-Z0-9_)"]);
    }

    // Check category name min length
    if(strlen($catName) < 2) {
        Falcon::restDie(400, ["success" => false, "message" => "Category name length must be at least 2 characters"]);
    }

    // Check category name max length
    if(strlen($catName) > 32) {
        Falcon::restDie(400, ["success" => false, "message" => "Category name too long. Stay at or below 32 characters."]);
    }

    $types = [];
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
            if(in_array($key, array_keys($types))) {
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
                "huge-text" => "mediumtext", 
                "int" => "int(4)", 
                "big-int" => "bigint(8)", 
                "decimal" => "decimal(28, 10)", 
                "boolean" => "tinyint(1)"
            ];

            if(!in_array($type, array_keys($validTypes))) {
                Falcon::restDie(400, ["success" => false, "message" => "Invalid type"]);
            }

            // Push to types array
            $types[$key] = [$type, $validTypes[$type]];
        }
    }

    if(!count($types)) {
        Falcon::restDie(400, ["success" => false, "message" => "Keys must be provided"]);
    }

    $conn = Falcon::getMysqlConnection();
    
    // Check that category name is unique
    $stmtCheckCatName = $conn->prepare("SELECT COUNT(*) FROM fn_site_object_categories WHERE category_name=?;");
    $stmtCheckCatName->execute([
        $catName
    ]);

    $categoryExists = $stmtCheckCatName->fetch(PDO::FETCH_COLUMN);
    if($categoryExists) {
        Falcon::restDie(400, ["success" => false, "message" => "Category by this name already exists"]);
    }

    // PDO not necessary because all values are strictly sanitized
    $strInsertCategory = "INSERT INTO fn_site_object_categories (category_name, structure, label) VALUES ('$catName', '";
    // PDO can't be used for executing this statement, but all parameter were strictly sanatized to (a-Z0-9_) earlier
    $strCreateObjectTable = "CREATE TABLE fn_so_$catName (object_index INT PRIMARY KEY AUTO_INCREMENT,";

    $started = false;
    foreach($types as $key => $type) {
        $falconType = $type[0];
        $mysqlType = $type[1];

        if($started) {
            $strInsertCategory .= ",";
            $strCreateObjectTable .= ",";
        }

        $strInsertCategory .= $falconType;
        // Underscore is added to the beginning of the key to prevent conflict with mysql keywords
        $strCreateObjectTable .= "_$key $mysqlType";

        $started = true;
    }

    $strInsertCategory .= "', '" . $objectLabel . "');";
    $strCreateObjectTable .= ");";

    // Execute insert object
    $stmtInsertCategory = $conn->prepare($strInsertCategory);
    $successInsertCategory = $stmtInsertCategory->execute();

    if(!$successInsertCategory) {
        Falcon::restDie(500, ["success" => false, "message" => "CRITICAL: Failed to insert category into Falcon site object categories table"]);
    }

    // Execute create object table
    $stmtCreateObjectTable = $conn->prepare($strCreateObjectTable);
    $successCreateObjectTable = $stmtCreateObjectTable->execute();

    if(!$successCreateObjectTable) {
        Falcon::restDie(500, ["success" => false, "message" => "CRITICAL: Failed to create new site object table, but was inserted into Falcon site objects table. This will cause the object to not work properly. Please delete it and try again."]);
    }

    Falcon::restDie(201, ["success" => true, "message" => "success"]);
?>