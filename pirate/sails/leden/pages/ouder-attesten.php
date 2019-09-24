<?php
namespace Pirate\Sails\Leden\Pages;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class OuderAttesten extends Page
{
    public function getContent()
    {
        global $FILES_DIRECTORY;
        clearstatcache();
        $folder = $FILES_DIRECTORY . "/attesten";
        $dirs = glob($folder . '/*');

        $attesten = [];

        foreach ($dirs as $dir) {
            $dirname = basename($dir);

            $group = [
                'name' => $dirname,
                'attesten' => [],
            ];

            $files = glob($dir . '/*');
            $regex = '/^(.*)\\((.*?)\\)$/';

            foreach ($files as $file) {
                $filename = basename($file);
                $ext = substr($filename, -3);
                $withoutExt = substr($filename, 0, -4);
                $matches = [];

                $ziekenfonds = "";
                if (preg_match($regex, $withoutExt, $matches) === 1) {
                    $name = trim($matches[1]);
                    $ziekenfonds = trim($matches[2]);
                } else {
                    $name = trim($withoutExt);
                }

                $url = "attesten/" . rawurlencode($dirname) . "/" . rawurlencode($filename);

                if (isset($group['attesten'][$withoutExt])) {
                    $group['attesten'][$withoutExt]['url'][$ext] = "https://" . str_replace('www.', 'files.', $_SERVER['SERVER_NAME']) . "/" . $url;
                } else {
                    $group['attesten'][$withoutExt] = [
                        'name' => $name,
                        'ziekenfonds' => $ziekenfonds,
                        'url' => [
                            $ext => "https://" . str_replace('www.', 'files.', $_SERVER['SERVER_NAME']) . "/" . $url,
                        ],
                    ];
                }
            }

            $attesten[] = $group;

        }

        return Template::render('pages/leden/attesten', array('attesten' => $attesten));
    }
}
