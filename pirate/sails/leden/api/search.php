<?php
namespace Pirate\Sails\Leden\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;
use Pirate\Sails\Leden\Models\Lid;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class Search extends Page {
    private $needle;

    function __construct($needle) {
        $this->needle = $needle;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $data = array('results' => Lid::ledenToFieldArray(Lid::search($this->needle)));
        return Template::render('admin/leden/search', $data );
    }
}