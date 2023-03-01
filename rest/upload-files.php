<?php
    /**
     * Description: Creates an individual site object
     * Method: Post
     * 
     * Parameters:
     * $_FILES["file"] - Uploaded files
     * fn-token - Falcon CSRF token
     * 
     * Response:
     * success - Boolean signifying files uploaded
     * message - Descriptive message
     * 
     * Response Codes:
     * 500 - Error uploading files
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
    if(!Falcon::validateCsrfToken("fn-upload-files", $_POST["fn-token"])) {
        Falcon::restDie(400, ["success" => false, "message" => "Invalid CSRF token"]);
    }

    // Check that there are files
    if(!isset($_FILES["file"])) {
        Falcon::restDie(400, ["success" => false, "message" => "No files to upload"]);
    }

    // Check that file is an array
    if(!is_array($_FILES["file"])) {
        Falcon::restDie(400, ["success" => false, "message" => "No files to upload"]);
    }

    // Check quantity
    $countFiles = count($_FILES["file"]["name"]);
    if(!$countFiles) {
        Falcon::restDie(400, ["success" => false, "message" => "No files to upload"]);
    }

    // No more than 20 files at a time
    if($countFiles > 20) {
        Falcon::restDie(400, ["success" => false, "message" => "Cannot upload more than 20 files at a time"]);
    }

    // Generate and check uniqueness of uuids before uploading files
    $fileUuids = [];
    $uuidParams = "";
    for($i = 0; $i < $countFiles; $i++) {
        if($i > 0) $uuidParams .= ",";
        $uuidParams .= "?";
        array_push($fileUuids, Falcon::generateUuid());
    }

    // Check for duplicate uuids
    $conn = Falcon::getMysqlConnection();
    $stmtCheckUuids = $conn->prepare("SELECT COUNT(*) FROM fn_uploads WHERE uuid IN ($uuidParams);");
    $stmtCheckUuids->execute($fileUuids);
    $duplicate = $stmtCheckUuids->fetch(PDO::FETCH_COLUMN);

    if($duplicate) {
        Falcon::restDie(500, ["success" => false, "message" => "Duplicate UUID generated. Please try again"]);
    }

    // Upload files
    $foundRealFile = false; // Sometimes file array is set with empty contents, weed that out
    for($i=0;$i<$countFiles;$i++){
        $tmpName = $_FILES["file"]["tmp_name"][$i];
        $fileName = $_FILES["file"]["name"][$i];
 
        if($tmpName) {
            move_uploaded_file($_FILES["file"]["tmp_name"][$i], __DIR__ . "/../uploads/$fileName");

            $stmtInsertFile = $conn->prepare("INSERT INTO fn_uploads (uuid, file_path, alt_text) VALUES (:uuid, :file_path, :alt_text);");
            $stmtInsertFile->execute([
                "uuid" => $fileUuids[$i],
                "file_path" => $fileName,
                "alt_text" => ""
            ]);

            $foundRealFile = true;
        }
    }

    if(!$foundRealFile) {
        Falcon::restDie(400, ["success" => false, "message" => "No files to upload"]);
    }

    Falcon::restDie(201, ["success" => 201, "message" => "success"]);
?>