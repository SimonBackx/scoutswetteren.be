<?php
namespace Pirate\Sails\Sponsors\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Sponsors\Models\Sponsor;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $sponsors = Sponsor::getSponsors();

        return Template::render('admin/sponsors/overview', array(
            'sponsors' => $sponsors
        ));
    }
}