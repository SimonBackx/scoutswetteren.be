<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Imagick;

class GDImage extends Model {
    protected $extension = '';
    protected $image = null;
    protected $width;
    protected $height;

    public $quality = 60;

    static function createFromGDImage(GDImage $image, $quality = null) {
        $gd = new GDImage(clone $image->image, $image->extension, $image->width, $image->height);
        if (isset($quality)) {
            $gd->quality = $quality;
            if ($gd->extension == 'jpg' || $gd->extension == 'jpeg') {
                $gd->image->setImageCompression(Imagick::COMPRESSION_JPEG); 
                $gd->image->setImageCompressionQuality($gd->quality);
            }
        }
        return $gd;
    }

    static function createFromFile($path) {
        ini_set('memory_limit','200M');

        $data = getimagesize($path);

        if (!$data || !isset($data[0], $data[1])) {
            return null;
        }

        Imagick::setResourceLimit(imagick::RESOURCETYPE_MEMORY, 50*1000*1000); // Maximum ±50 megabyte
        $image = new Imagick($path);

        $width = $data[0];
        $height = $data[1];
        $ext = strtolower(substr(strrchr(basename($path),'.'),1));

        $gd = new GDImage($image, $ext, $width, $height);

        if ($ext == 'jpg' || $ext == 'jpeg') {
            $image->setImageCompression(Imagick::COMPRESSION_JPEG); 
            $image->setImageCompressionQuality($gd->quality);
        }

        $orientation = $image->getImageOrientation(); 
        if ($orientation != imagick::ORIENTATION_TOPLEFT && $orientation != imagick::ORIENTATION_UNDEFINED){
            $gd->correct($orientation);
        }
        
        return $gd;
    }

    function getWidth() {
        return $this->width;
    }

    function getHeight() {
        return $this->height;
    }

    function getSize() {
        return array('width' => $this->width, 'height' => $this->height);
    }

    function getExtension() {
        return $this->extension;
    }

    private function __construct($image, $ext, $width, $height) {
        $this->image = $image;
        $this->extension = $ext;
        $this->width = $width;
        $this->height = $height;
    }

    private function correct($orientation) {
        switch($orientation){
            case imagick::ORIENTATION_TOPRIGHT:
                $this->mirrorH();
                return;
            case imagick::ORIENTATION_BOTTOMRIGHT:
                $this->rotate(180);
                return;
            case imagick::ORIENTATION_BOTTOMLEFT:
                $this->mirrorV();
                return;
            case imagick::ORIENTATION_LEFTTOP:
                $this->mirrorV();
                $this->rotate(270);
                return;
            case imagick::ORIENTATION_RIGHTTOP:
                $this->rotate(270);
                return;
            case imagick::ORIENTATION_RIGHTBOTTOM:
                $this->mirrorH();
                $this->rotate(270);
                return;
            case imagick::ORIENTATION_LEFTBOTTOM:
                $this->rotate(90);
                return;
        }

        $this->image->setImageOrientation(imagick::ORIENTATION_TOPLEFT); 
    }

    function mirrorH() {
        $this->image->flopImage();
    }

    function mirrorV() {
        $this->image->flipImage();
    }

    // tegen de klok draaien
    function rotate($dir = 90){

        if (floor($dir / 180) != $dir / 180) {
            $new_width = $this->height;
            $this->height = $this->width;
            $this->width = $new_width;
        }
        $this->image->rotateimage("#000", -$dir);
    }

    function crop($size) {
        $x = floor( ( $this->width - $size['width'] ) / 2 );
        $y = floor( ( $this->height - $size['height'] ) / 2 );

        $this->image->cropImage($size['width'], $size['height'], $x, $y);

        $this->width = $size['width'];
        $this->height = $size['height'];
    }

    static function getExpectedSize($original, $size, $allow_crop = false) {

        if (isset($size['width']) && $original->width < $size['width']) {
            $size['width'] = $original->width;
            $size['height'] = $original->height;
            return $size;
        }
        if (isset($size['height']) && $original->height < $size['height']) {
            $size['width'] = $original->width;
            $size['height'] = $original->height;
            return $size;
        }

        if ($allow_crop && isset($size['width']) && isset($size['height'])) {
            return $size;
        }

        $new_width = $original->width;
        $new_height = $original->height;

        if (isset($size['width']) && $original->width > $size['width']) {
            $new_height = round($original->height / $original->width * $size['width']);
            $new_width = $size['width'];

            if (isset($size['height']) && $new_height < $size['height']) {
                $new_width = round($original->width/$original->height*$size['height']);
                $new_height = $size['height'];
            }
        } else {
            if (isset($size['height']) && $original->height > $size['height']) {
                $new_width = round($original->width/$original->height*$size['height']);
                $new_height = $size['height'];
            }
        }

        return array('width' => $new_width, 'height' => $new_height);
    }

    // scale + crop
    function fit($size) {
        $this->image->cropThumbnailImage($size['width'], $size['height']);

        $this->width = $size['width'];
        $this->height = $size['height'];
    }

    // scale with aspect ratio
    function scale($size) {
        $new_width = $size['width'];
        $new_height = $size['height'];

        $this->image->thumbnailImage($new_width, $new_height);

        $this->width = $new_width;
        $this->height = $new_height;

    }

    function save($path, &$errors) {
        global $FILES_DIRECTORY;
        $path = $FILES_DIRECTORY.'/'.$path;

        $error_reporting = error_reporting();
        error_reporting(0);

        
        $old = umask(0);
        $dir = dirname($path);

        $try = 0;
        $failed = true;
        while($try < 2) {
            $try++;
            
            if (is_dir($dir) || mkdir($dir, 0777, true)) {
                $failed = false;
                break;
            }
        }
        umask($old);

        if ($failed) {
            $errors[] = 'Kon mapstructuur niet aanmaken van thumbnail afbeelding.';
            error_reporting($error_reporting);
            return false;
        }

        $result = false;

        $result = $this->image->writeImage($path);
        $this->destroy();

        error_reporting($error_reporting);
        return $result;
    }

    function destroy() {
        $this->image->clear();
    }

}