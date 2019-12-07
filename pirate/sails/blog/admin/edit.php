<?php
namespace Pirate\Sails\Blog\Admin;

use Pirate\Sails\Blog\Models\Article;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Edit extends Page
{
    public $article;
    public function __construct($article = null)
    {
        $this->article = $article;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $new = false;
        if (!isset($this->article)) {
            $this->article = new Article();
            $new = true;
        }
        $data = $this->article->getProperties();

        $allset = true;
        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                $allset = false;
                break;
            }

            $data[$key] = $_POST[$key];
        }

        $errors = [];

        // Als alles geset is
        if ($allset) {
            // todo

            try {
                $this->article->setProperties($data);
                if (!$this->article->save()) {
                    throw new ValidationError("Opslaan mislukt");
                }

                header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/articles");
            } catch (ValidationErrorBundle $ex) {
                foreach ($ex->getErrors() as $error) {
                    $errors[] = $error->message;
                }
            }

        } else {
            // Read from existing product

            // Add default prices placeholders if not set
        }

        return Template::render('admin/blog/edit', array(
            'errors' => $errors,
            'article' => $this->article,
            'data' => $data,
            'new' => $new,
        ));
    }
}
