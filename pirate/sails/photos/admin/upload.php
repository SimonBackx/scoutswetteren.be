<?php
namespace Pirate\Sail\Photos\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Maandplanning\Event;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class Upload extends Page {
    function getStatusCode() {
        return 200;
    }

    function getHead() {
        return '<link rel="stylesheet" href="/css/photoswipe.css"><script src="/js/photoswipe.min.js"></script>';
    }

    function getContent() {
        $errors = array();
        $data = array();
        
        if (isset($_POST['delete_queue'])) {
            if (!Album::getQueueAlbum()->delete()) {
                $errors[] = 'Wissen mislukt.';
            }

            if (isset($_POST['group'])) {
                $data['group'] = $_POST['group'];
            }
        }
        $images = Image::getImagesFromAlbum(null);

        if (isset($_POST['group']) && !isset($_POST['delete_queue'])) {
            if (isset($_POST['album_name'])) {
                if (count($images) > 0) {
                    $data = array(
                        'album_name' => $_POST['album_name'],
                        'group' => $_POST['group']
                    ); 
                    $album = new Album();

                    $success = false;
                    if ($album->setProperties($data, $errors)) {
                        if ($album->createFromImageQueue()) {
                            $success = true;
                        } else {
                            $errors[] = 'Er ging iets mis bij het opslaan';
                        }
                    }

                    return Template::render('admin/photos/album', array(
                        'success' => $success,
                        'new' => true,
                        'data' => $data,
                        'groups' => Album::$groups,
                        'errors' => $errors
                    ));
                }
            }

            // eerste keer tonen -> name suggestion invullen
            if (count($images) == 0) {
                $errors[] = 'Je hebt geen foto\'s toegevoegd';
            } elseif (!Album::isValidGroup($_POST['group']))
                $errors[] = 'Geen groep geselecteerd.';
            else {
                $data = array(
                    'album_name' => Album::getNameSuggestion($_POST['group'], $images),
                    'group' => $_POST['group']
                );

                return Template::render('admin/photos/album', array(
                    'new' => true,
                    'data' => $data,
                    'groups' => Album::$groups
                ));
            }
        }
        // Alle albumloze afbeeldingen ophalen
        return Template::render('admin/photos/upload', array(
            'errors' => $errors,
            'max_upload_size' => File::$max_size,
            'images' => $images,
            'data' => $data,
            'groups' => Album::$groups
        ));
    }
}