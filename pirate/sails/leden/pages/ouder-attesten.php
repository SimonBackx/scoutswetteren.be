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
        $dirs = array_reverse(glob($folder . '/*'));

        $attesten = [];

        foreach ($dirs as $dir) {
            $dirname = basename($dir);

            $group = [
                'name' => $dirname,
                'attesten' => [],
            ];

            $files = glob($dir . '/*');
            $regex = '/^(.*)\\((.*?)\\)$/';

            $jaarRegex = '/(20\d{2})$/';

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

                $matches = [];
                if (preg_match($jaarRegex, $name, $matches) === 1) {
                    //$ziekenfonds = trim($matches[1]);
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
