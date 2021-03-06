<?php
namespace Pirate\Sails\Files\Models;

use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Files\Models\GDImage;
use Pirate\Sails\Files\Models\ImageFile;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\Model;

class Image extends Model
{
    public $id;
    public $date_taken;
    public $sources = array();
    public $album; // id of null! -> geen object
    public $title;

    public function __construct($row = null, $sources = array())
    {
        if (!isset($row)) {
            $this->new = true;
            return;
        }

        $this->id = $row['image_id'];

        $this->title = $row['image_title'];

        if (isset($row['image_date_taken'])) {
            $this->date_taken = new \DateTime($row['image_date_taken']);
        } else {
            $this->date_taken = null;
        }

        $this->album = $row['image_album'];

        $this->sources = $sources;
    }

    public function getSourcesJSON()
    {
        $sources = array();

        foreach ($this->sources as $source) {
            if ($source->is_source) {
                continue;
            }

            $sources[] = array('w' => $source->width, 'h' => $source->height, 'url' => $source->file->getPublicPath());
        }

        return json_encode($sources);
    }

    public function getBiggestSource()
    {
        if (count($this->sources) == 0) {
            return null;
        }

        $bestfit = null;

        for ($i = 0; $i < count($this->sources); $i++) {
            $source = $this->sources[$i];
            if (!$source->is_source && (!isset($bestfit) || $source->isGreaterThan($bestfit))) {
                $bestfit = $source;
            }
        }
        return $bestfit;
    }

    public function getBestfit($width, $height)
    {
        if (count($this->sources) == 0) {
            return null;
        }

        $bestfit = null;
        for ($i = 0; $i < count($this->sources); $i++) {
            $source = $this->sources[$i];
            if (!$source->is_source && $width <= $source->width && $height <= $source->height && (!isset($bestfit) || $source->isLessThan($bestfit))) {
                $bestfit = $source;
            }
        }

        if (!isset($bestfit)) {
            return $this->getBiggestSource();
        }
        return $bestfit;
    }

    /**
     * Geeft grootste ImageFile die niet het origineel is
     * @return [type] [description]
     */
    public function getSource()
    {
        $biggest = null;
        $biggest_source = null;
        foreach ($this->sources as $source) {
            if (!$source->is_source) {
                if (!isset($biggest) || $biggest->width < $source->width) {
                    $biggest = $source;
                }
            } else {
                if (!isset($biggest_source) || $biggest_source->width < $biggest_source->width) {
                    $biggest_source = $source;
                }
            }
        }

        if (!isset($biggest)) {
            return $biggest_source;
        }

        return $biggest;
    }

    /**
     * Geeft grootste ImageFile die niet het origineel is
     * @return [type] [description]
     */
    public function getRealSource()
    {
        foreach ($this->sources as $source) {
            if ($source->is_source) {
                return $source;
            }
        }

        return null;
    }

    public function addSource(ImageFile $source)
    {
        $this->sources[] = $source;
    }

    public function setAlbum($id)
    {
        $this->album = $id;
    }

    public static function getLonelyImagePath()
    {
        return 'images/';
    }

    public function upload($form_name, $sizes, &$errors, Album $album_object = null, $sponsorify = false, $object_storage = true)
    {
        $should_be_saved_in_object_storage = $object_storage;

        if (isset($this->album) && !isset($album_object)) {
            if ($this->album == Album::$QUEUE_ID) {
                $album_object = Album::getQueueAlbum();
            } else {
                $album_object = Album::getAlbum($this->album);
            }
        } else {
            if (!isset($this->album)) {
                $album_object = null;
            }
        }

        if (isset($album_object) && $album_object->id == Album::$QUEUE_ID) {
            $should_be_saved_in_object_storage = false;
        }

        ini_set('MAX_IFD_NESTING_LEVEL', 200);

        // Datum inlezen
        $this->date_taken = null;

        self::getDb()->autocommit(false);
        if (!$this->save()) {
            $errors[] = 'Fout bij opslaan Image in database.';
            return false;
        }

        $source = new File();
        $source->should_be_saved_in_object_storage = $should_be_saved_in_object_storage;

        // Als het een album heeft should_be_saved_on_server op true zetten (enkel voor sources)
        if (isset($album_object)) {
            $source->should_be_saved_on_server = $object_storage;
            // Hier nog niet wijzigen voor alle andere bestanden, want upload is nog niet 100% zeker
        }

        $leiding_id = Leiding::getUser()->id;
        // Hier nog location manipuleren

        // Locatie waar source + thumbnails worden opgeslagen
        if (isset($album_object)) {
            $path = $album_object->getPath();
        } else {
            $path = Self::getLonelyImagePath();
        }

        $source->location = $path . 'sources/';
        if (!$source->upload($form_name, $errors, array('jpeg', 'jpg', 'png', 'gif', 'bmp', 'tiff'), $this->id)) {
            self::getDb()->rollback();
            self::getDb()->autocommit(true);

            return false;
        }

        if (($source->extension == 'jpg' || $source->extension == 'jpeg') && function_exists('exif_read_data')) {
            $error_reporting = error_reporting();
            error_reporting(0);
            $exif_data = exif_read_data($source->getPath());
            error_reporting($error_reporting);

            if (isset($exif_data['DateTimeOriginal'])) {
                $this->date_taken = \DateTime::createFromFormat('Y:m:d H:i:s', $exif_data['DateTimeOriginal']);
                if (!$this->save()) {
                    $errors[] = 'Fout bij aanpassen date taken';
                    return false;
                }
            }
        }

        // gebeurd al in alle gevallen van true:
        //self::getDb()->commit();
        //self::getDb()->autocommit(true);

        $original = ImageFile::createFromFile( /*image: */$this, /*file: */ $source, $errors);
        if ($original === false) {
            $errors[] = 'Create from file failed';
            // TODO: alles ongedaan maken
            return false;
        }

        $this->sources = array();
        //$this->sources[] = $original; -> deze nooit doorgeven!

        if (count($sizes) > 0) {
            $original = GDImage::createFromFile($source->getPath());

            $previousSize = array();
            foreach ($sizes as $size) {
                $actual_size = GDImage::getExpectedSize($original, $size);
                if ($actual_size === $previousSize) {
                    continue;
                }
                $previousSize = $actual_size;

                $quality = 60;

                if ($actual_size["width"] < 400 || $actual_size["height"] < 400) {
                    $quality = 60;
                }

                if ($actual_size["width"] <= 100 || $actual_size["height"] <= 100) {
                    $quality = 60;
                }

                if ($sponsorify) {
                    $quality = 100;
                    $gdImage = GDImage::createFromGDImage($original);
                } else {
                    $gdImage = GDImage::createFromGDImage($original, $quality);
                }

                if (isset($size['width'], $size['height'])) {
                    $gdImage->fit($actual_size);
                } else {
                    $gdImage->scale($actual_size);
                }

                if ($sponsorify) {
                    $gdImage->blackAndWhite();
                    $gdImage->level();
                }

                $img = ImageFile::createFromGDImage($this, $gdImage, $errors, $path, $should_be_saved_in_object_storage);
                if ($img === false) {
                    // TODO: alles ongedaan maken
                    return false;
                }
                $this->sources[] = $img;
            }

            $original->destroy();
        }

        // Hele album beschikbaar maken zodat zip file kan worden aangepast
        if (isset($album_object) && !$album_object->isQueue()) {
            $album_object->setSourcesShouldBeSavedOnServer(true);
        }

        return true;
    }

    public static function getImagesFromAlbum($album_id = null)
    {
        $where = '';
        if (!isset($album_id) || intval($album_id) == Album::$QUEUE_ID) {
            if (!Leiding::isLoggedIn()) {
                return null;
            }

            $id = self::getDb()->escape_string(Leiding::getUser()->id);
            $where = "WHERE i.image_album = '" . Album::$QUEUE_ID . "' and f.file_author = '$id'";
        } else {
            $id = self::getDb()->escape_string($album_id);
            $where = "WHERE i.image_album = '$id'";
        }

        $query = "
            SELECT * FROM images i
                JOIN image_files i_f on i_f.imagefile_image = i.image_id
                JOIN files f on f.file_id = i_f.imagefile_file
                LEFT JOIN albums a on i.image_album = a.album_id
            $where
            ORDER BY i.image_id
        ";

        return self::getImagesForQuery($query);
    }

    private static function getImagesForQuery($query)
    {
        if ($result = self::getDb()->query($query)) {
            $images = array();
            while ($row = $result->fetch_assoc()) {
                if (count($images) == 0 || $row['image_id'] != $images[count($images) - 1]->id) {
                    $images[] = new Image($row);
                }
                $imageFile = new ImageFile($row);
                $images[count($images) - 1]->addSource($imageFile);
            }

            return $images;
        }

        return null;
    }

    public static function getImage($id)
    {
        $id = self::getDb()->escape_string($id);

        $query = "
            SELECT * FROM images i
                JOIN image_files i_f on i_f.imagefile_image = i.image_id
                JOIN files f on f.file_id = i_f.imagefile_file
                LEFT JOIN albums a on i.image_album = a.album_id
            WHERE i.image_id = '$id'
            ORDER BY i.image_id
        ";

        if ($result = self::getDb()->query($query)) {
            $image = null;
            while ($row = $result->fetch_assoc()) {
                if (!isset($image)) {
                    $image = new Image($row);
                }
                $imageFile = new ImageFile($row);
                $image->addSource($imageFile);
            }
            return $image;
        }

        return null;
    }

    public function save()
    {

        if (!isset($this->date_taken)) {
            $date_taken = 'NULL';
        } else {
            $date_taken = '"' . self::getDb()->escape_string($this->date_taken->format('Y-m-d H:i:s')) . '"';
        }

        if (!isset($this->album)) {
            $image_album = 'NULL';
        } else {
            $image_album = '"' . self::getDb()->escape_string($this->album) . '"';
        }

        $image_title = self::getDb()->escape_string($this->title);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE images
                SET
                 image_date_taken = $date_taken,
                 image_album = $image_album,
                 image_title = '$image_title'
                 where image_id = '$id'
            ";
        } else {
            $query = "INSERT INTO
                images (`image_date_taken`, `image_album`, `image_title`)
                VALUES ($date_taken, $image_album, '$image_title')";
        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }

    public function delete(&$errors = array())
    {
        if (!isset($this->id)) {
            $errors[] = 'Deze foto bestaat niet.';
            return false;
        }

        if (isset($this->album)) {

            $images = Self::getImagesFromAlbum($this->album);
            if (count($images) == 1 && $this->album != Album::$QUEUE_ID) {
                $errors[] = 'Je kan de laatste foto van dit album niet verwijderen.';
                return false;
            }

            // Coverfoto wijzigen indien verwijderd
            $album = Album::getAlbum($this->album);
            if (isset($album) && isset($album->cover) && $album->cover->id == $this->id) {
                $i = 0;
                while ($album->cover->id == $this->id && $i < count($images)) {
                    $album->cover = $images[$i];
                    $i++;
                }
                $album->save();
                $album->onImageDeleted($this);
            }
        }

        $id = self::getDb()->escape_string($this->id);

        $query = "DELETE files FROM images left join image_files on image_files.imagefile_image = images.image_id left join files on files.file_id = image_files.imagefile_file WHERE images.image_id = '$id'";

        if (!self::getDb()->query($query)) {
            $errors[] = 'Er ging iets mis bij het aanpassen van de database.';
            return false;
        }

        $query = "DELETE FROM images WHERE images.image_id = '$id'";

        if (!self::getDb()->query($query)) {
            $errors[] = 'Er ging iets mis bij het aanpassen van de database.';
            return false;
        }

        foreach ($this->sources as $source) {

            if ($source->delete() === false) {
                $errors[] = 'De foto is verwijderd uit de database, maar niet volledig uit het bestandssysteem (door een interne fout).';
                return false;
            }
        }

        return true;
    }
}
