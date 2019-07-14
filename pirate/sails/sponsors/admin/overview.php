<?php
namespace Pirate\Sail\Sponsors\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\File;
use Pirate\Model\Sponsors\Sponsor;

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