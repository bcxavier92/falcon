<?php
    /**
     * Description: Creates an individual site object
     * Method: Post
     * 
     * Parameters:
     * cat - Category to create site object in
     * _val-{columnName} - Value for specified column name
     * fn-token - Falcon CSRF token
     * 
     * Response:
     * success - Boolean signifying object created
     * message - Descriptive message
     * 
     * Response Codes:
     * 500 - Error creating object
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
    if(!Falcon::validateCsrfToken("fn-create-site-object", $_POST["fn-token"])) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid CSRF token"]);
    }

    // Check category name defined
    if(!Falcon::isDefined($_POST, "cat")) {
        Falcon::restDie(400, ["success" => false, "message" => "Category name is required"]);
    }

    // Check category name exists and get structure
    $conn = Falcon::getMysqlConnection();
    $cat = trim($_POST["cat"]);
    $stmtGetStructure = $conn->prepare("SELECT structure FROM fn_site_object_categories WHERE category_name=?;");
    $stmtGetStructure->execute([$cat]);
    $structure = $stmtGetStructure->fetch(PDO::FETCH_COLUMN);

    // If there is no structure site object does not exist
    if(!$structure) {
        Falcon::restDie(400, ["success" => false, "message" => "Object category does not exist"]);
    }

    // Get columns
    // SQL Injection isn't actually possible here even though prepared statements aren't being used,
    // because all category names have been previously sanitized
    $stmtGetColumns = $conn->prepare("DESCRIBE fn_so_$cat;");
    $stmtGetColumns->execute();
    $cols = $stmtGetColumns->fetchAll(PDO::FETCH_COLUMN);

    // Check columns exist
    if(!$cols) {
        Falcon::restDie(400, ["success" => false, "message" => "Could not get columns from site object table"]);
    }

    // Check columns array length > 0 just to be safe
    if(!count($cols)) {
        Falcon::restDie(400, ["success" => false, "message" => "Could not get columns from site object table"]);
    }

    // Iterate over inputs and set their values
    $colsStr = "";
    $valsStr = "";
    $valsArr = [];
    $types = explode(",", $structure);
    $allNull = true;
    $i = 0;
    foreach($types as $type) {
        $col = $cols[$i + 1];
        $substrCol = substr($col, 1);
        $val = null;

        // If value is defined then use it, otherwise just keep value as null since all site object columns are nullable
        if(Falcon::isDefined($_POST, "_val-$substrCol")) {
            // Validate type
            $val = $_POST["_val-$substrCol"] . ""; // Make sure this is a string by adding "" on the end
            if(!Falcon::validateSiteObjectType($type, $val)) {
                Falcon::restDie(400, ["success" => false, "message" => "Invalid value for type on input \"$substrCol\""]);
            }

            $allNull = false;
        }

        // Append column and value to query
        if($i > 0) {
            $colsStr .= ",";
            $valsStr .= ",";
        }

        $colsStr .= $col;
        $valsStr .= "?";
        array_push($valsArr, $val);

        $i++;
    }

    if($allNull) {
        Falcon::restDie(400, ["success" => false, "message" => "All inputs cannot be empty"]);
    }
    
    // Insert values
    $stmtInsertObject = $conn->prepare("INSERT INTO fn_so_$cat ($colsStr) VALUES ($valsStr);");
    $insertSuccess = $stmtInsertObject->execute($valsArr);

    if(!$insertSuccess) {
        Falcon::restDie(500, ["success" => false, "message" => "Critical: Could not create site object"]);
    }

    Falcon::restDie(201, ["success" => true, "message" => "success"]);
?>