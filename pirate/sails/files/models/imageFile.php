<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\GDImage;
use Pirate\Model\Files\Album;

class ImageFile extends Model {
    public $id;
    public $file; // object
    public $image; // id 
    public $width;
    public $height;

    public $is_source;

    function __construct($row = null) {
        if (!isset($row)) {
            return;
        }

        $this->id = $row['imagefile_id'];
        $this->file = new File($row);
        $this->image = $row['imagefile_image'];
        $this->width = intval($row['imagefile_width']);
        $this->height = intval($row['imagefile_height']);

        $this->is_source = (intval($row['imagefile_is_source']) == 1);
    }

    function isLessThan(ImageFile $imagefile) {
        if ($imagefile->width > $this->width || $imagefile->height > $this->height) {
            return true;
        }
        return false;
    }

    function isGreaterThan(ImageFile $imagefile) {
        return !$this->isLessThan($imagefile);
    }

    // Nieuwe aanmaken vanaf gdImage
    // False on failure, object on success
    static function createFromGDImage(Image $image, GDImage $gdImage, &$errors, $path = 'images/', $should_be_saved_in_object_storage = true) {
        $path .= $gdImage->getWidth().'x'.$gdImage->getHeight().'/';
        $path .= $image->id.'.'.$gdImage->getExtension();

        if (!$gdImage->save($path, $errors)) {
            $errors[] = 'Fout bij opslaan verkleinde afbeelding.';
            return false;
        }

        $name = basename($path);
        $location = dirname($path).'/';

        $file = File::createFromFile($location, $name, $errors);
        $file->should_be_saved_in_object_storage = $should_be_saved_in_object_storage;

        if (!isset($file)) {
            $errors[] = 'Fatale fout... Interventie van webmaster nodig om schade te herstellen.';
            return false;
        }

        if (!$file->save()) {
            $errors[] = 'Opslaan in database mislukt';
            return null;
        }

        $imageFile = new ImageFile();
        $imageFile->file = $file;
        $imageFile->image = $image->id;
        $imageFile->width = $gdImage->getWidth();
        $imageFile->height = $gdImage->getHeight();
        $imageFile->is_source = false;

        // Auto_remove = true

        if ($imageFile->save()) {
            return $imageFile;
        }

        $errors[] = 'Opslaan in database mislukt.';
        return false;
    }

    // Nieuwe aanmaken vanaf file (dus het originele bestand)
    // False on failure, object on success
    static function createFromFile(Image $image, File $file, &$errors) {
        $imageFile = new ImageFile();
        $imageFile->file = $file;
        $imageFile->image = $image->id;

        $data = getimagesize($file->getPath());

        if (!$data || !isset($data[0], $data[1])) {
            return false;
        }

        $imageFile->width = $data[0];
        $imageFile->height = $data[1];
        $imageFile->is_source = true;

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
        $image = self::getDb()->escape_string($this->image);
        $width = self::getDb()->escape_string($this->width);
        $height = self::getDb()->escape_string($this->height);
        $is_source = 0;
        if ($this->is_source) {
            $is_source = 1;
        }

        $query = "INSERT INTO 
                image_files (`imagefile_file`, `imagefile_image`, `imagefile_width`, `imagefile_height`, `imagefile_is_source`)
                VALUES ('$file', '$image', '$width', '$height', '$is_source')";

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }
}