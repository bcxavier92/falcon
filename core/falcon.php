<?php
    define("FalconLoaded", true);

    require __DIR__ . "/setup.php";

    Falcon::init();

    class Falcon {
        private static $fnConn = null;
        private static $fnSettings = null;

        static function init() {
            $skipInit = false;
            if(defined("FnSkipInit")) {
                if(FnSkipInit) {
                    $skipInit = true;
                }
            }

            // Falcon is not being loaded in a setup script, so check if falcon either needs to be setup or start up like usual
            // If this is a setup script, nothing should be done
            if(!$skipInit) { 
                // Checking if falcon is setup also loads the harness
                if(FnSetup::hasBeenSetup()) {
                    // Falcon has been setup, open the mysql connection and do updates if needed
                    self::$fnConn = Falcon::newMysqlConnection(); // Open the mysql connection
                    register_shutdown_function(["Falcon", "closeMysqlConnection"]); // Close connection on shutdown

                    Falcon::reloadSettings(self::$fnConn); // Loads settings into self::$fnSettings

                    FnSetup::doUpdates(self::$fnConn);
                } else {
                    // Falcon has not been setup, redirect to setup page
                    FnSetup::redirectSetup();
                }
            }
        }

        // Check if account with email exists
        static function accountEmailExists($conn, $email) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM fn_users WHERE email=?;");
            $stmt->execute([$email]);
            $exists = $stmt->fetch(PDO::FETCH_COLUMN);

            if($exists) return true;
            return false;
        }

        // Check if account with username exists
        static function accountUsernameExists($conn, $username) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM fn_users WHERE username=?;");
            $stmt->execute([$username]);
            $exists = $stmt->fetch(PDO::FETCH_COLUMN);

            if($exists) return true;
            return false;
        }

        // Check if account with uuid exists
        static function accountUuidExists($conn, $uuid) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM fn_users WHERE uuid=?;");
            $stmt->execute([$uuid]);
            $exists = $stmt->fetch(PDO::FETCH_COLUMN);

            if($exists) return true;
            return false;
        }

        // Create an account
        // No sanitization or validation is done here, do that before calling the function
        static function createAccount($conn, $uuid, $username, $email, $passwordHash, $isAdmin) {
            $stmt = $conn->prepare("INSERT INTO fn_users (uuid, username, email, password_hash, is_admin) VALUES (:uuid, :username, :email, :password_hash, :is_admin);");
            $success = $stmt->execute([
                "uuid" => $uuid,
                "username" => $username,
                "email" => $email,
                "password_hash" => $passwordHash,
                "is_admin" => $isAdmin
            ]);

            if($success) return true;
            return false;
        }

        // Close the connection - Runs on php script end
        static function closeMysqlConnection() {
            $fnConn = null;
        }

        // Defer script loading. Scripts will load in their order in the $scripts array
        static function deferScripts($scripts) {
            if(!count($scripts)) return;

            echo "\t<!-- Load Scripts Async -->\n\t<script async defer>";
            echo "function getScript(url,success){ var script=document.createElement('script'); script.src=url; var head=document.getElementsByTagName('head')[0], done=false; script.onload=script.onreadystatechange = function(){ if ( !done && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete') ) { done=true; success(); script.onload = script.onreadystatechange = null; head.removeChild(script); } }; head.appendChild(script); }\n";
    
            //Let javascript know what scripts to load
            echo "\tvar allScripts = [";
            $i = 0;
            foreach($scripts as $script) {
                echo ($i != 0 ? ", " : "") . "'$script'";
                $i++;
            }
            echo "];";

            //Load each script dependent on the previous in the array
            echo "\t
            var currentScript = 0; nextScript(); function nextScript() { getScript(allScripts[currentScript], function() { currentScript++; if(currentScript < allScripts.length) { nextScript(); } } ); }";

            echo "</script>\n\n";
        }

        // Destroy session
        static function destroySession() {

            // Remove PHPSESSID from browser
            // if(isset($_COOKIE[session_name()])) setcookie(session_name(), "", time() - 3600, "/");
    
            // Clear session from globals
            $_SESSION = array();

            // Clear session from disk
            if(session_id() != "") session_destroy();

            session_write_close();
        }
        
        // Generates a 16 character csrf token
        static function generateCsrfToken($formId) {
            $token = bin2hex(random_bytes(8));
            
            // Set fn_csrf_tokens in session to an empty array if it does not exist or isn't an array
            // fn_csrf_tokens is a multidimensional array of form ids and a list of their tokens
            if(!isset($_SESSION["fn_csrf_tokens"])) {
                $_SESSION["fn_csrf_tokens"] = array();
            } else if(!is_array($_SESSION["fn_csrf_tokens"])) {
                $_SESSION["fn_csrf_tokens"] = array();
            }
            
            // Add the form id as a key in fn_csrf_tokens if it doesn't exist already
            if(!isset($_SESSION["fn_csrf_tokens"][$formId])) {
                $_SESSION["fn_csrf_tokens"][$formId] = array();
            }

            // Determine next position
            $maxLength = 10;
            $nextLength = count($_SESSION["fn_csrf_tokens"][$formId]) + 1;

            // If token array for the form id is about to exceed max length, remove the difference from the beginning of the array
            if($nextLength > $maxLength) {
                $difference = $nextLength - $maxLength;
                $_SESSION["fn_csrf_tokens"][$formId] = array_slice($_SESSION["fn_csrf_tokens"][$formId], $difference);
            }

            // Add the token to the array
            array_push($_SESSION["fn_csrf_tokens"][$formId], $token);

            return $token;
        }

        // Generates a 32 character uuid
        static function generateUuid() {
            return bin2hex(random_bytes(16));
        }

        // Gets site base URL
        static function getBaseUrl() {
            return Falcon::getSettings()["base_url"];
        }

        // Returns user uuid if logged in or null if not logged in
        static function getCurrentUser() {
            if(isset($_SESSION["fn_uuid"])) {
                return $_SESSION["fn_uuid"];
            }
            return null;
        }

        // Gets html escaped site name
        static function getEscapedSiteName() {
            return htmlspecialchars(Falcon::getSettings()["site_name"], ENT_QUOTES);
        }

        // Gets falcon config file and returns an assoc array
        static function getJsonConfig() {
            return json_decode(file_get_contents(FnJsonPath), true);
        }

        // Get the open MySQL connection
        static function getMysqlConnection() {
            return self::$fnConn;
        }

        // Get the greater of the two version
        // Returns the greater of the two versions or false on failure
        static function getNewerVersion($v1, $v2) {
            $split1 = explode(".", $v1);
            $split2 = explode(".", $v2);
            $dec1 = substr_count($v1, ".");
            $dec2 = substr_count($v2, ".");
            $decimals = $dec1 > $dec2 ? $dec1 : $dec2;

            for($i = 0; $i <= $decimals; $i++) {
                // Make sure arrays aren't out of index
                $outOfIndex1 = count($split1) === $i;
                $outOfIndex2 = count($split2) === $i;

                // If one version runs out of characters at the end first, the other is smaller
                if($outOfIndex1 && !$outOfIndex2) {
                    return $v2;
                }
                if($outOfIndex2 && !$outOfIndex1) {
                    return $v1;
                }

                // Get values at position
                $val1 = $split1[$i];
                $val2 = $split2[$i];

                // Make sure values are integers >= 0
                if(!ctype_digit($val1) || !ctype_digit($val2)) {
                    return false;
                }

                // Compare
                if($val1 > $val2) {
                    return $v1;
                } else if($val2 > $val1) {
                    return $v2;
                }
            }

            // No greater version was found
            return false;
        }

        // Returns settings
        static function getSettings() {
            return self::$fnSettings;
        }

        // Returns user uuid on success or null on failure
        static function getUserUuid($conn, $username) {
            $stmt = $conn->prepare("SELECT uuid FROM fn_users WHERE username=?;");
            $stmt->execute([$username]);
            $uuid = $stmt->fetch(PDO::FETCH_COLUMN);
            if($uuid) {
                return $uuid;
            }
            return null;
        }

        // Get version number
        static function getVersion() {
            return "0.1";
        }

        // Get version name
        static function getVersionName() {
            return "Pygmy";
        }
    
        // Check if user is logged in as admin
        static function isAdmin() {
            $admin = false;

            // Admin is set to true if it is stored in session
            if(isset($_SESSION["fn_admin"])) {
                if($_SESSION["fn_admin"] === true) $admin = true;
            }

            return $admin;
        }

        // An alternative to isset and isempty, used mostly for checking params on http requests
        // Returns false for: !isset(), === null, === false, === "", and trim() === ""
        static function isDefined($arr, $key) {
            if(!isset($arr[$key])) return false;

            $val = $arr[$key];
            if($val === null) return false;
            if($val === false) return false;
            if($val === "") return false;
            if(trim($val) === "") return false;

            return true;
        }

        // Returns true if user is admin or false if not
        static function isUserAdmin($conn, $uuid) {
            $stmt = $conn->prepare("SELECT is_admin FROM fn_users WHERE uuid=?;");
            $stmt->execute([$uuid]);
            $isAdmin = $stmt->fetch(PDO::FETCH_COLUMN);

            if($isAdmin) return true;
            return false;
        }

        // Returns PDO connection on success or false on failure
        public static function newMysqlConnection() {
            $config = Falcon::getJsonConfig();
            $db = $config["mysqlDatabase"];
            $host = $config["mysqlHost"];
            $port = $config["mysqlPort"];
            $user = $config["mysqlUser"];
            $password = $config["mysqlPassword"];
            try {
                $conn = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                return false;
            }
            return $conn;
        }

        // Prints value of getBaseUrl()
        static function printBaseUrl() {
            echo Falcon::getBaseUrl();
        }

        // Print CSRF token in an input
        static function printFormCsrfToken($formId) {
            echo "<input type=\"text\" name=\"fn-token\" style=\"display: none\" value=\"" . Falcon::generateCsrfToken($formId) . "\">";
        }

        static function reloadSettings($conn) {
            $stmtGetSettings = $conn->prepare("SELECT setting_key, setting_value FROM fn_settings;");
            $stmtGetSettings->execute();
            self::$fnSettings = $stmtGetSettings->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Returns status code with message and kills the program
        static function restDie($status, $response) {
            http_response_code($status);
            die(json_encode($response));
        }

        // Require admin authorization for rest api
        static function restRequireAdminAuth() {
            if(!Falcon::isAdmin()) {
                Falcon::restDie(401, ["success" => false, "message" => "You do not have permission"]);
            }
        }

        // Attempt to set current user
        // Returns true on success or false if user does not exist
        static function setCurrentUser($conn, $uuid) {
            // Check if user exists
            $stmtGetCount = $conn->prepare("SELECT COUNT(*) FROM fn_users WHERE uuid=?;");
            $stmtGetCount->execute([$uuid]);
            $count = $stmtGetCount->fetch(PDO::FETCH_COLUMN);

            if($count != 1) {
                // User does not exist, return false
                return false;
            }

            // Destroy and restart session
            Falcon::destroySession();
            Falcon::startSession();
            session_regenerate_id(true);

            $_SESSION["fn_uuid"] = $uuid;

            // Check if admin
            if(Falcon::isUserAdmin($conn, $uuid)) {
                $_SESSION["fn_admin"] = true;
            }

            return true;
        }

        // Start session
        static function startSession() {
            $settings = Falcon::getSettings();
            $cookieParams = [
                "path" => "/",
                "lifetime" => $settings["session_lifetime"],
                "domain" => $settings["session_domain"],
                "secure" => $settings["session_secure"],
                "httponly" => $settings["session_httponly"],
                "samesite" => $settings["session_samesite"]
            ];
            session_set_cookie_params($cookieParams);

            session_start();

            Falcon::updateSessionInteraction();
        }

        // If session is timed out, destroy and recreate it
        // If not, update last interaction time
        static function updateSessionInteraction() {
            $time = time();

            // If last interaction time is set, use that. If not, this is a new session, and last interaction time is now.
            // If this is a new session, the last interaction time will be saved to session on the else portion of the timeout check
            $sessionLastInteraction = isset($_SESSION["fn_last_interaction"]) ? $_SESSION["fn_last_interaction"] : $time;
            
            $settings = Falcon::getSettings();
            $sessionTimeout = $settings["session_timeout"];
            $currentTime = time();

            // Check if session has timed out
            if($currentTime - $sessionLastInteraction > $sessionTimeout) {
                // Session has timed out, destroy it
                Falcon::destroySession();
                Falcon::startSession();
                session_regenerate_id(true);
            } else {
                // Update last interaction time
                $_SESSION["fn_last_interaction"] = $time;
            }
        }
        
        // Returns true or false if credentials are valid
        static function userCredentialsValid($conn, $username, $password) {
            $stmt = $conn->prepare("SELECT password_hash FROM fn_users WHERE username=?;");
            $stmt->execute([$username]);
            $hash = $stmt->fetch(PDO::FETCH_COLUMN);
            
            if($hash) {
                if(password_verify($password, $hash)) {
                    return true;
                }
            }
            return false;
        }
        
        // Returns true if csrf token is valid or false if not
        static function validateCsrfToken($formId, $token) {
            // Return false if token array does not exist in session
            if(!isset($_SESSION["fn_csrf_tokens"])) return false;
            if(!isset($_SESSION["fn_csrf_tokens"][$formId])) return false;

            $tokenSet = $_SESSION["fn_csrf_tokens"][$formId];
            //Reverse array to start with newest keys first, then compare
            foreach(array_reverse($tokenSet) as $t) {
                if($token === $t) return true;
            }
            return false;
        }

        // Site object type validation function
        // Returns true or false
        static function validateSiteObjectType($type, $val) {
            $val = "$val"; // Make sure $val is string for testing
            $valid = false;
            switch($type) {
                case "object-ref":
                    if(
                        preg_match("/^[a-z0-9_]+$/i", $val) &&
                        strlen($val) >= 2 &&
                        strlen($val) <= 32
                    ) $valid = true;
                    break;
                case "lang-ref":
                    if(
                        strlen($val) <= 255
                    ) $valid = true;
                    break;
                case "file":
                    if(
                        strlen($val) === 32 &&
                        preg_match("/^[a-fA-F0-9]+$/", $val)
                    ) $valid = true;
                    break;
                case "short-text":
                    if(
                        strlen($val) <= 255
                    ) $valid = true;
                    break;
                case "med-text":
                    if(
                        strlen($val) <= 2048
                    ) $valid = true;
                    break;
                case "long-text":
                    if(
                        strlen($val) <= 65535
                    ) $valid = true;
                    break;
                case "huge-text":
                    if(
                        strlen($val) <= 16777215
                    ) $valid = true;
                    break;
                case "int":
                    // Allow one negative sign at the beginning by checking for it and removing it before
                    // checking with ctype_digit
                    $testVal = $val;
                    if(substr($val, 0, 1) === "-") {
                        $testVal = substr($testVal, 1);
                    }

                    if(
                        ctype_digit($testVal) // Ctype digit allows numbers only, no decimals or negative signs
                    ) $valid = true;
                    break;
                case "big-int":
                    // Allow one negative sign at the beginning by checking for it and removing it before
                    // checking with ctype_digit
                    $testVal = $val;
                    if(substr($val, 0, 1) === "-") {
                        $testVal = substr($testVal, 1);
                    }

                    if(
                        ctype_digit($testVal) // Ctype digit allows numbers only, no decimals or negative signs
                    ) $valid = true;
                    break;
                case "decimal":
                    if(
                        is_numeric($val)
                    ) $valid = true;
                    break;
                case "boolean":
                    if(
                        $val === "true" ||
                        $val === "false"
                    ) $valid = true;
                    break;
                default:
                    break;
            }
            return $valid;
        }

        // Write a new setting to fn_settings
        // Type argument should only be passed if setting doesn't exist yet
        // Type options: string|bool|int|double
        // Returns true on success or false on failure
        static function writeSetting($conn, $key, $value) {
            $stmtSettingExists = $conn->prepare("SELECT COUNT(*) FROM fn_settings WHERE setting_key=?;");
            $stmtSettingExists->execute([$key]);
            $exists = $stmtSettingExists->fetch(PDO::FETCH_COLUMN);

            // setting_value is a varchar, so value should be a string
            $value = "$value";
            $arguments = [
                "setting_key" => $key,
                "setting_value" => $value
            ];

            if($exists) {
                // Setting exists, overwrite it
                

                $stmtUpdate = $conn->prepare("UPDATE fn_settings SET setting_value=:setting_value WHERE setting_key=:setting_key;");
                $success = $stmtUpdate->execute($arguments);

                // Return true or false
                if($success) {
                    return true;
                }
                return false;
            } else {
                //Setting doesn't exist, create it
                $stmtInsert = $conn->prepare("INSERT INTO fn_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value);");
                $success = $stmtInsert->execute($arguments);

                // Return true or false
                if($success) {
                    return true;
                }
                return false;
            }
        }
    }
?>