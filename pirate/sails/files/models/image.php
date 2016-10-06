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

    function __construct($row = null) {
        if (!isset($row)) {
            $this->new = true;
            return;
        }

        $this->id = $row['image_id'];
        $this->date_taken = new \DateTime($row['image_date_taken']);
    }


    function upload($form_name, $sizes, &$errors) {
        $source = new File();

        // Hier nog location manipuleren

        if (!$source->upload($form_name, $errors)) {
            return false;
        }

        // Fixing exif data error
        ini_set('MAX_IFD_NESTING_LEVEL', 200);

        // Datum inlezen
        $this->date_taken = null;

        if ($source->extension == 'jpg' || $source->extension == 'jpeg') {
            $exif_data = @exif_read_data($source->getPath());
            
            if (isset($exif_data['DateTimeOriginal'])) {
                $this->date_taken = \DateTime::createFromFormat('Y:m:d H:i:s', $exif_data['DateTimeOriginal']);
            }
        }

        if (!$this->save()) {
            $errors[] = 'Fout bij opslaan Image in database.';
            return false;
        }

        $original = ImageFile::createFromOriginal($this, $source, $errors);
        if (!isset($original)) {
            return false;
        }
        $this->sources = array();
        $this->sources[] = $original;

        if (count($sizes) > 0) {
            $original = GDImage::createFromFile($source->getPath());

            foreach ($sizes as $size) {
                $gdImage = GDImage::createFromGDImage($original);
                if (isset($size['width'], $size['height'])) {
                    $gdImage->fit($size);
                } else {
                    $gdImage->scale($size);
                }

                $img = ImageFile::create($this, $gdImage, $errors);
                if (!isset($img)) {
                    return false;
                }
                $this->sources[] = $img;
            }

            $original->destroy();
        }

        

        // Hier nog resize toevoegen

        return true;
    }



    function save() {
        if (isset($this->id)) {
            return false;
        }

        if (!isset($this->date_taken)) {
            $date_taken = 'NULL';
        } else {
            $date_taken = '"'.self::getDb()->escape_string($this->date_taken->format('Y-m-d H:i:s')).'"';
        }

        $query = "INSERT INTO 
                images (`image_date_taken`)
                VALUES ($date_taken)";

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }
}