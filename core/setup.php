<?php
    if(!defined("FalconLoaded")) die("Direct access prohibited");

    /**
     * Database defaults
     */

    define("FnMysqlDefaults", [
        "fn_users" => [
            "added_version" => "0.1",
            "columns" => [
                ["uuid", "VARCHAR(32) PRIMARY KEY"],
                ["username", "VARCHAR(24) UNIQUE KEY NOT NULL"],
                ["email", "VARCHAR(320) UNIQUE KEY NOT NULL"],
                ["password_hash", "VARCHAR(60) NOT NULL"],
                ["is_admin", "BOOL NOT NULL"]
            ]
        ],
        "fn_settings" => [
            "added_version" => "0.1",
            "columns" => [
                ["setting_key", "VARCHAR(255) PRIMARY KEY"],
                ["setting_value", "VARCHAR(2048)"],
            ],
            "defaults" => [
                "0.1" => [
                    ["fn_version", Falcon::getVersion()],
                    ["session_lifetime", "0"],
                    ["session_secure", "0"],
                    ["session_httponly", "1"],
                    ["session_samesite", "Strict"],
                    ["session_timeout", "28800"],
                    ["language_default", "en"],
                    ["language_codes", "en"],
                    ["language_file", ""],
                    ["language_url_format", "{baseUrl}/{lang}"],
                    ["language_url_ignore_default", "1"]
                ]
            ]
        ],
        "fn_site_object_categories" => [
            "added_version" => "0.1",
            "columns" => [
                ["category_name", "VARCHAR(32) PRIMARY KEY"],
                ["structure", "VARCHAR(1024)"],
                ["label", "VARCHAR(32) NOT NULL"]
            ]
        ],
        "fn_uploads" => [
            "added_version" => "0.1",
            "columns" => [
                ["uuid", "VARCHAR(32) PRIMARY KEY"],
                ["file_path", "VARCHAR(2048) NOT NULL"],
                ["alt_text", "VARCHAR(255)"]
            ]
        ]
    ]);

    class FnSetup {
        // Does updates if needed
        // This function returns nothing at the moment
        static function doUpdates($conn) {
            $dbVersion = Falcon::getSettings()["fn_version"];
            $currentVersion = Falcon::getVersion();

            // New version was installed, time to update
            if(Falcon::getNewerVersion($currentVersion, $dbVersion) === $currentVersion) {
                /**
                 * This section creates new tables
                 */
                foreach(FnMysqlDefaults as $table => $tableData) {
                    $tableVersion = $tableData["added_version"];
                    if(Falcon::getNewerVersion($dbVersion, $tableVersion) === $tableVersion) {
                        FnSetup::createTable($conn, $table);
                    }
                }

                /**
                 * This section inserts defaults
                 */
                foreach(FnMysqlDefaults as $table => $tableData) {
                    // Check if table has default values
                    if(isset($tableData["defaults"])) {
                        // Check if table has default values for a version newer than the database's version
                        $insertDefaults = false;
                        $versionWithDefaults = array_keys($tableData["defaults"]);
                        foreach($versionWithDefaults as $version) {
                            if(Falcon::getNewerVersion($dbVersion, $version) === $version) {
                                $insertDefaults = true;
                            }
                        }

                        // If there are defaults to insert, continue
                        if($insertDefaults) {
                            // Create the list of columns to be updated
                            $startedColumnNames = false;
                            $columnNames = "";
                            foreach($tableData["columns"] as $column) {
                                if($startedColumnNames) $columnNames .= ",";

                                $columnNames .= $column[0];

                                $startedColumnNames = true;
                            }
                    
                            // Create the query to insert the defaults
                            $strInsertDefaults = "INSERT IGNORE INTO $table ($columnNames) VALUES ";
                            $valuesList = [];

                            // Loop through each version's defaults
                            foreach($tableData["defaults"] as $version => $rows) {
                                // Only apply defaults for versions greater than the database version
                                if(Falcon::getNewerVersion($dbVersion, $version) == $version) {
                                    // Build the parameters string and append values to valuesList array
                                    $startedRows = false;
                                    foreach($rows as $row) {
                                        if($startedRows) $strInsertDefaults .= ",";
                                        $strInsertDefaults .= "(";
                
                                        $startedValues = false;
                                        foreach($row as $value) {
                                            if($startedValues) $strInsertDefaults .= ",";

                                            $strInsertDefaults .= "?";
                                            array_push($valuesList, $value);

                                            $startedValues = true;
                                        }

                                        $strInsertDefaults .= ")";
                                        $startedRows = true;
                                    }
                                }
                            }

                            $strInsertDefaults .= ";";

                            // Execute the insert query
                            $stmtInsertDefaults = $conn->prepare($strInsertDefaults);
                            $stmtInsertDefaults->execute($valuesList);
                        }
                    }
                }

                // Update version in database
                Falcon::writeSetting($conn, "fn_version", Falcon::getVersion());
            }
        }

        // Returns true if harness.php exists and has FnJsonPath defined, otherwise returns false
        static function hasBeenSetup() {
            if(file_exists(__DIR__ . "/harness.php")) {
                require __DIR__ . "/harness.php";

                if(defined("FnJsonPath")) {
                    return true;
                }
            }
            return false;
        }

        // Returns true if any of falcon's default tables exist, otherwise returns false
        static function hasFalconTables($conn) {
            $stmtShowTables = $conn->prepare("SHOW TABLES;");
            $stmtShowTables->execute();
            $tables = $stmtShowTables->fetchAll(PDO::FETCH_COLUMN);
            if($tables) {
                $falconTableNames = array_keys(FnMysqlDefaults);
                foreach($tables as $table) {
                    if(in_array($table, $falconTableNames)) {
                        return true;
                    }
                }
            }
            return false;
        }

        // Redirects to setup page
        static function redirectSetup() {
            // Gets the url for the core folder
            $setupUrl = str_replace("\\", "/", "http://" . $_SERVER["HTTP_HOST"] . substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT'])));

            // Removes "core" at end and adds "/admin/setup.php"
            $setupUrl = substr($setupUrl, 0, strlen($setupUrl) - 5) . "/admin/setup.php";

            header("Location: " . $setupUrl);
            die();
        }

        // Creates table in FnMysqlDefaults
        // Be cautious when comparing the return value of this function, because it will return truthy values on failure
        // True is only returned when there is complete success
        // The name of the table the error was encountered on will be returned on failure
        static function createTable($conn, $table) {
            $tableData = FnMysqlDefaults[$table];
            $started = false;
            $strCreateTable = "CREATE TABLE IF NOT EXISTS $table (";

            // Build the create table query
            foreach($tableData["columns"] as $column) {
                if($started) {
                    $strCreateTable .= ",";
                }

                $strCreateTable .= $column[0] . " " . $column[1];

                $started = true;
            }
            $strCreateTable .= ");";

            // Create the table
            $stmtCreateTable = $conn->prepare($strCreateTable);
            $createdSuccess = $stmtCreateTable->execute();

            //If reation failed, return table name for debugging purposes
            if(!$createdSuccess) {
                return $table;
            }
            return true;
        }

        // Sets up the database for a new installation
        // Be cautious when comparing the return value of this function, because it will return truthy values on failure
        // True is only returned when there is complete success
        // The name of the table the error was encountered on will be returned on failure
        static function setupNewDatabase($conn, $siteName, $baseUrl) {
            foreach(FnMysqlDefaults as $table => $tableData) {
                // Attempt to create table
                $tableCreated = FnSetup::createTable($conn, $table);

                // If tableCreated isn't exactly equal to true, the name of the table the error was encountered on is returned
                if($tableCreated !== true) {
                    return $tableCreated;
                }

                $hasDefaults = isset($tableData["defaults"]);

                // If table has defaults, insert them
                if($hasDefaults) {
                    // Build the column names
                    $columnNames = "";
                    $started = false;
                    foreach($tableData["columns"] as $column) {
                        if($started) {
                            $columnNames .= ",";
                        }
        
                        $columnNames .= $column[0];
        
                        $started = true;
                    }

                    // Build the insert query
                    $strInsertDefaults = "INSERT INTO $table ($columnNames) VALUES ";
                    $valuesList = [];
                    foreach($tableData["defaults"] as $version => $rows) {
                        $startedRows = false;
                        foreach($rows as $row) {
                            if($startedRows) $strInsertDefaults .= ",";
                            $strInsertDefaults .= "(";
    
                            $startedValues = false;
                            foreach($row as $value) {
                                if($startedValues) $strInsertDefaults .= ",";

                                $strInsertDefaults .= "?";
                                array_push($valuesList, $value);

                                $startedValues = true;
                            }

                            $strInsertDefaults .= ")";
                            $startedRows = true;
                        }
                    }

                    $strInsertDefaults .= ";";

                    // Execute the insert query
                    $stmtInsertDefaults = $conn->prepare($strInsertDefaults);
                    $insertSuccess = $stmtInsertDefaults->execute($valuesList);

                    // Return tables name if failed
                    if(!$insertSuccess) {
                        return $table;
                    }
                }
            }

            Falcon::writeSetting($conn, "session_domain", parse_url($baseUrl, PHP_URL_HOST));
            Falcon::writeSetting($conn, "site_name", $siteName);
            Falcon::writeSetting($conn, "base_url", $baseUrl);

            return true;
        }
    }
?>