<?php
namespace Pirate\Sail\Leden\Api;
use Pirate\Page\Page;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;

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
        return Template::render('leden/admin/search', $data );
    }
}