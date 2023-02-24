<?php
    if(!defined("FalconLoaded")) die("Direct access prohibited");

    global $fnDefaultLang, $fnLangCodes, $fnLangFile, $fnLangUrlFormat, $fnLangUrlIgnoreDefault, $fnLang, $fnLangJson;

    /**
     * Configuration options
     */
    
    $settings = Falcon::getSettings();
    $fnDefaultLang = $settings["language_default"] ?? null;
    $fnLangCodes = $settings["language_codes"] ?? null;
    $fnLangFile = Falcon::getBaseUrl() . "/" . $settings["language_file"] ?? null;
    $fnLangUrlFormat = $settings["language_url_format"] ?? null;
    $fnLangUrlIgnoreDefault = $settings["language_url_ignore_default"] ?? null;

    $fnLangJson = json_decode(file_get_contents($fnLangFile), true);
    $fnLang = $fnDefaultLang;
    if(isset($_GET["lang"])) {
        $langCode = strtolower($_GET["lang"]);
        if(in_array($langCode, explode(",", $fnLangCodes))) {
            $fnLang = $langCode;
        }
    }

    class FnLocale {
        // Gets current language code
        public static function getLangCode() {
            return $GLOBALS["fnLang"];
        }

        // If language code is not passed, gets base url of the current language
        // If language code is passed, gets base url of the provided language code
        public static function getLanguageBaseUrl($langCode = null) {
            if($langCode === null) $langCode = FnLocale::getLangCode();
            if($GLOBALS["fnLangUrlIgnoreDefault"] && $langCode === $GLOBALS["fnDefaultLang"]) {
                return Falcon::getBaseUrl();
            }

            $baseUrl = str_replace("{baseUrl}", Falcon::getBaseUrl(), $GLOBALS["fnLangUrlFormat"]);
            $baseUrl = str_replace("{lang}", $langCode, $baseUrl);
            return $baseUrl;
        }

        // Get text in translation file for the current language
        public static function getTranslation($id) {
            return $GLOBALS["fnLangJson"][$id][FnLocale::getLangCode()];
        }

        // Print the value of getLanguageBaseUrl()
        public static function printLanguageBaseUrl($langCode = null) {
            echo FnLocale::getLanguageBaseUrl($langCode);
        }

        // Print the value of getTranslation($id) 
        public static function printTranslation($id) {
            echo FnLocale::getTranslation($id);
        }
    }
?>