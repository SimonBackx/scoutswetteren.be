<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\GDImage;

class ImageFile extends Model {
    public $id;
    public $file; // object
    public $image; // object

    public $width;
    public $height;

    function __construct($row = null, $image = null) {
        if (!isset($row)) {
            return;
        }

        $this->id = $row['imagefile_id'];
        $this->file = new File($row);
        $this->image = $image;
        $this->width = intval($row['imagefile_width']);
        $this->height = intval($row['imagefile_height']);
    }

    // Nieuwe aanmaken vanaf gdImage
    // False on failure, object on success
    static function create(Image $image, GDImage $gdImage, &$errors) {
        $path = 'images/';

        if (isset($image->date_taken)) {
            $path = 'images/'.$image->date_taken->format('Y/m/d').'/';
        }
        $path .= $gdImage->getWidth().'x'.$gdImage->getHeight().'/';
        $path .= $image->id.'.'.$gdImage->getExtension();

        if (!$gdImage->save($path)) {
            $errors[] = 'Fout bij opslaan herschaalde afbeelding.';
            return false;
        }

        $name = basename($path);
        $location = dirname($path).'/';

        $file = new File();
        if (!$file->from_file($location, $name, $errors)) {
            $errors[] = 'Fatale fout... Interventie van webmaster nodig om schade te herstellen.';
            return false;
        }

        $imageFile = new ImageFile();
        $imageFile->file = $file;
        $imageFile->image = $image;
        $imageFile->width = $gdImage->getWidth();
        $imageFile->height = $gdImage->getHeight();

        if ($imageFile->save()) {
            return $imageFile;
        }

        $errors[] = 'Opslaan in database mislukt.';
        return false;
    }

    // Nieuwe aanmaken vanaf file (dus het originele bestand)
    // False on failure, object on success
    static function createFromOriginal(Image $image, File $file, &$errors) {
        $imageFile = new ImageFile();
        $imageFile->file = $file;
        $imageFile->image = $image;

        $data = getimagesize($file->getPath());

        if (!$data || !isset($data[0], $data[1])) {
            return false;
        }

        $imageFile->width = $data[0];
        $imageFile->height = $data[1];

        if ($imageFile->save()) {
            return $imageFile;
        }

        $errors[] = 'Opslaan in database mislukt.';
        return false;
    }

    function save() {
        if (isset($this->id)) {
            return false;
        }

        $file = self::getDb()->escape_string($this->file->id);
        $image = self::getDb()->escape_string($this->image->id);
        $width = self::getDb()->escape_string($this->width);
        $height = self::getDb()->escape_string($this->height);

        $query = "INSERT INTO 
                image_files (`imagefile_file`, `imagefile_image`, `imagefile_width`, `imagefile_height`)
                VALUES ('$file', '$image', '$width', '$height')";

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }
}