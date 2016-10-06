<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;

class GDImage extends Model {
    private $extension = '';
    private $image = null;
    private $width;
    private $height;

    public $quality = 75;

    public $current_image_is_reused = false;

    static function createFromGDImage(GDImage $image) {
        $gd = new GDImage($image->image, $image->extension, $image->width, $image->height);
        $gd->current_image_is_reused = true;
        return $gd;
    }

    static function createFromFile($path) {
        ini_set('memory_limit','200M');

        $data = getimagesize($path);

        if (!$data || !isset($data[0], $data[1])) {
            return null;
        }

        $width = $data[0];
        $height = $data[1];
        $ext = strtolower(substr(strrchr(basename($path),'.'),1));

        switch($ext){
            case 'jpg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'png': 
                $image = imagecreatefrompng($path);
                imagesavealpha($image, true);
                imagealphablending($image, false);
                break;
            case 'gif': 
                $image = imagecreatefromgif($path);
                break;
            default: 
                return null;
        }

        $gd = new GDImage($image, $ext, $width, $height);

        if ($ext == 'jpg' || $ext == 'jpeg') {
            $exif = @exif_read_data($path);
            if (isset($exif['Orientation'])){
                $gd->correct($exif['Orientation']);
            }
        }

        return $gd;
    }

    function getWidth() {
        return $this->width;
    }

    function getHeight() {
        return $this->height;
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
            case 2:
                $this->mirrorH();
                return;
            case 3:
                $this->rotate(180);
                return;
            case 4:
                $this->mirrorV();
                return;
            case 5:
                $this->mirrorV();
                $this->rotate(270);
                return;
            case 6:
                $this->rotate(270);
                return;
            case 7:
                $this->mirrorH();
                $this->rotate(270);
                return;
            case 8:
                $this->rotate(90);
                return;
        }
    }

    function mirrorH() {
        $image = imageflip ( $this->image , IMG_FLIP_HORIZONTAL );
        $this->destroy();

        $this->image = $image;
    }

    function mirrorV() {
        $image = imageflip ( $this->image , IMG_FLIP_VERTICAL );
        $this->destroy();
        $this->image = $image;
    }

    function rotate($dir = 90){
        // Tegen de klok in draaien
        
        if (floor($dir/180) != $dir/180) {
            $width_temp = $this->width;
            $this->width = $this->height;
            $this->height = $width_temp;
        }
        $image = imagerotate ($this->image, $dir, 0);
        $this->destroy();

        $this->image = $image;
    }

    function crop($size) {
        $img = imagecreatetruecolor($size['width'], $size['height']);
        $x = floor( ( $this->width - $size['width'] ) / 2 );
        $y = floor( ( $this->height - $size['height'] ) / 2 );

        imagecopyresampled( $img , $this->image, 0, 0, $x, $y, $size['width'], $size['height'] , $size['width'], $size['height']);

        $this->destroy();
        $this->image = $img;

    }

    // scale + crop
    function fit($size) {
        $this->scale($size);
        $this->crop($size);
    }

    // scale with aspect ratio
    function scale($size) {
        $new_width = $this->width;
        $new_height = $this->height;

        if (isset($size['width']) && $this->width > $size['width']) {
            $new_height = round($this->height / $this->width * $size['width']);
            $new_width = $size['width'];
        }

        if (isset($size['height']) && $this->height > $size['height']) {
            $new_width = round($this->width/$this->height*$size['height']);
            $new_height = $size['height'];
        }
        
        $image = imagecreatetruecolor($new_width, $new_height);
        
        if ($this->extension == 'png') {
            imagesavealpha($image, true);
            imagealphablending($image, false);
        }
        
        imagecopyresampled($image, $this->image, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);
        $this->destroy();

        $this->image = $image;
        $this->width = $new_width;
        $this->height = $new_height;

    }

    function save($path) {
        global $FILES_DIRECTORY;
        $path = $FILES_DIRECTORY.'/'.$path;

        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 755, true)) {
            return false;
        }        
        $result = false;

        switch($this->extension){
            case 'jpg': $result = imagejpeg($this->image, $path, $this->quality); 

            break;
            case 'jpeg': $result = imagejpeg($this->image, $path, $this->quality); 

            break;
            case 'png': $result = imagepng($this->image, $path, 9); 

            break;
            case 'gif': $result = imagegif($this->image, $path); 

            break;
        }

        $this->destroy();

        return $result;
    }

    function destroy() {
        if (!$this->current_image_is_reused) {
            imagedestroy($this->image);
        }
        $this->current_image_is_reused = false;
    }
}