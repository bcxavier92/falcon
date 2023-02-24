<?php
    /**
     * Description: Simple script that outputs an svg and alters its colors.
     * Method: Get
     * 
     * Parameters:
     * name - Name of falcon svg icon
     * colors - Colors to replace (Format f7f7f7:94ac7e,000000,f76243)
     * 
     * Response:
     * Either outputs the contents of an svg image or json on failure
     * Content-type header changes accordingly
     * 
     * Response codes:
     * 400 - Bad request
     * 500 - Error
     * 200 - Success
     * 
     * Notes:
     * Svg url must have the same host as the site's base url
     * FnSkipInit is set to true to prevent database connection from opening
     * 
     */

    // Skip initialization
    define("FnSkipInit", true);
    require __DIR__ . "/../core/falcon.php";

    header('Content-type: application/json');

    // Check request method
    if($_SERVER["REQUEST_METHOD"] !== "GET") {
        Falcon::restDie(400, "Invalid request method");
    }

    // Check name is defined
    if(!Falcon::isDefined($_GET, "name")) {
        Falcon::restDie(400, "Icon name is required");
    }

    // Validate name
    $name = trim($_GET["name"]);
    if(!preg_match("/^[a-z0-9_-]+$/i", $name)) {
        Falcon::restDie(400, "Invalid characters in icon name. Use (a-Z0-9 _-)");
    }

    // Check if icon file exists
    $path = __DIR__ . "/../icon/$name.svg";
    if(!is_file($path)) {
        Falcon::restDie(400, "Icon does not exist");
    }

    // Get contents
    $contents = file_get_contents($path);

    // Check if color replacement was passed
    if(Falcon::isDefined($_GET, "colors")) {
        $swaps = explode(",", $_GET["colors"]);
        // Do no more than 5 color replacements
        if(count($swaps) <= 5) {
            // Loop through each color replacement
            foreach($swaps as $swap) {
                // Make sure exactly two colors were passed
                $colors = explode(":", $swap);
                if(count($colors) === 2) {
                    // Make sure that colors are hex codes (or at least close and not harmful)
                    if(
                        strlen($colors[0]) <= 6 && 
                        strlen($colors[1]) <= 6 &&
                        preg_match("/^[0-9a-f]+$/i", $colors[0]) &&
                        preg_match("/^[0-9a-f]+$/i", $colors[1])
                    ) {
                        $contents = str_replace("fill:#" . $colors[0], "fill:#" . $colors[1], $contents);
                    }
                }
            }
        }
    }

    header('Content-type: image/svg+xml');
    http_response_code(200);
    die($contents);
?>