<?php
namespace Pirate\Wheel;
use Pirate\Wheel\Page404;

class Route {
    private $matchedPage;

    /// Returns an object with the matched parameters
    function match($parts, $url, $params = []) {
        $expected_parts = explode('/', trim($url, "/"));
        if (count($parts) != count($expected_parts)) {
            return false;
        }

        $matched = (object) [
            'matched' => true,
            'params' => (object) [],
        ];

        foreach($parts as $index => $part) {
            $expected_part = $expected_parts[$index];
            if (substr($expected_part, 0, 1) == "@") {
                $key = substr($expected_part, 1);
                if (isset($params[$key])) {

                    if ($params[$key] == 'string') {
                        if (strlen($part) > 0) {
                            // matched
                            $matched->params->$key = $part;
                            continue;
                        }
                    }

                    // invalid type

                    return false;
                }
            }

            if ($expected_part != $part) {
                return false;
            }
        }
        return $matched;
    }

    /// Call this method if you want to skip overwriring getPage method and duplicating your routing logic
    function setPage($page) {
        $this->matchedPage = $page;
    }

    /// Use this for more advanced routing
    function doMatch($url, $parts) {
        return false;
    }

    function getPage($url, $parts) {
        if (isset($this->matchedPage)) {
            return $this->matchedPage;
        }
        return new Page404();
    }
}

class AdminRoute extends Route {
    /**
     * Geef een lijst van alle available pages terug per permission.
     * Permission '' is voor iedereen
     */
    static function getAvailablePages() {
        return [];
    }
}
