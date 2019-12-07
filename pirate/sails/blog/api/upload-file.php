<?php
namespace Pirate\Sails\Blog\Api;

use Pirate\Sails\Files\Models\Image;
use Pirate\Wheel\Page;

class UploadFile extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContentType()
    {
        return 'application/json';
    }

    public function getContent()
    {
        $image = new Image();
        $sizes = array(
            array('width' => 1280),
        );

        $errors = [];
        if ($image->upload('image', $sizes, $errors, null, false, false)) {
            $largest = $image->getSource();

            return json_encode([
                'success' => 1,
                'file' => [
                    'url' => $largest->file->getPublicPath(),
                    'width' => $largest->width,
                    'height' => $largest->height,
                    'size' => $largest->file->size,
                    'id' => $image->id,
                ],
            ]);
        }

        return json_encode([
            'success' => 0,
            'errors' => $errors,
        ]);
    }
}
