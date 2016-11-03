<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Files\ImageFile;
use Pirate\Model\Files\GDImage;

class Image extends Model {
    public $id;
    public $date_taken;
    public $sources = array();
    public $album; // id of null! -> geen object

    function __construct($row = null, $sources = array()) {
        if (!isset($row)) {
            $this->new = true;
            return;
        }

        $this->id = $row['image_id'];
        $this->date_taken = new \DateTime($row['image_date_taken']);

        $this->album = $row['image_album'];
        
        $this->sources = $sources;
    }

    function getSourcesJSON() {
        $sources = array();

        foreach ($this->sources as $source) {
            $sources[] = array('width' => $source->width, 'height' => $source->height, 'url' => $source->file->getPublicPath());
        }

        return json_encode($sources);
    }

    /**
     * Geeft grootste ImageFile die niet het origineel is
     * @return [type] [description]
     */
    function getSource() {
        $biggest = null;
        $biggest_source = null;
        foreach ($this->sources as $source) {
            if (!$source->file->is_source) {
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

    function addSource(ImageFile $source) {
        $this->sources[] = $source;
    }

    function setAlbum($id) {
        $this->album = $id;
    }

    function upload($form_name, $sizes, &$errors, Album $album_object = null) {
        
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

        ini_set('MAX_IFD_NESTING_LEVEL', 200);

        // Datum inlezen
        $this->date_taken = null;

        self::getDb()->autocommit(false);
        if (!$this->save()) {
            $errors[] = 'Fout bij opslaan Image in database.';
            return false;
        }

        $source = new File();

        $leiding_id = Leiding::getUser()->id;
        // Hier nog location manipuleren
        
        // Locatie waar source + thumbnails worden opgeslagen
        $path = Album::getPathForAlbum($album_object);
        $source->location = $path.'sources/';
        if (!$source->upload($form_name, $errors, array('jpeg', 'jpg', 'png', 'gif', 'bmp'), $this->id)) {
            self::getDb()->rollback();
            self::getDb()->autocommit(true);
            $errors[] = 'Upload failed';

            return false;
        }

        if ($source->extension == 'jpg' || $source->extension == 'jpeg') {
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


        $original = ImageFile::createFromOriginal($this, $source, $errors);
        if ($original === false) {
            $errors[] = 'Create from original failed';
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

                $gdImage = GDImage::createFromGDImage($original);

                if (isset($size['width'], $size['height'])) {
                    $gdImage->fit($actual_size);
                } else {
                    $gdImage->scale($actual_size);
                }

                $img = ImageFile::create($this, $gdImage, $errors, $path);
                if ($img === false) {

                    // TODO: alles ongedaan maken
                    return false;
                }
                $this->sources[] = $img;
            }

            $original->destroy();
        }
        // Hier nog resize toevoegen

        return true;
    }
    

    static function getImagesFromAlbum($album_id = null) {
        $where = '';
        if (!isset($album_id) || intval($album_id) == Album::$QUEUE_ID) {
            if (!Leiding::isLoggedIn()) {
                return null;
            }

            $id = self::getDb()->escape_string(Leiding::getUser()->id);
            $where = "WHERE i.image_album = '".Album::$QUEUE_ID."' and f.file_author = '$id'";
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

    function save() {

        if (!isset($this->date_taken)) {
            $date_taken = 'NULL';
        } else {
            $date_taken = '"'.self::getDb()->escape_string($this->date_taken->format('Y-m-d H:i:s')).'"';
        }

        if (!isset($this->album)) {
            $image_album = 'NULL';
        } else {
            $image_album = '"'.self::getDb()->escape_string($this->album).'"';
        }

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE images 
                SET 
                 image_date_taken = $date_taken,
                 image_album = $image_album
                 where image_id = '$id' 
            ";
        } else {
            $query = "INSERT INTO 
                images (`image_date_taken`, `image_album`)
                VALUES ($date_taken, $image_album)";
        }

        

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }
}