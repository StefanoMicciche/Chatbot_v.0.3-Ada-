<?php

require_once 'config.php';

class wikipediaService{
    private $language;

    public function __construct($language = DEFAULT_LANG) {
        $this->language = $language;
    }

     /**
     * Search information in Wikipedia
     * 
     * @param string $query Query to search
     * @return array Information from Wikipedia or error message
     */
}