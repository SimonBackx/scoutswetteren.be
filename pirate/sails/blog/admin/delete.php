<?php
namespace Pirate\Sails\Blog\Admin;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Delete extends Page
{
    private $article = null;

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
        // Geen geldig id = nieuw event toevoegen
        $success = false;
        $fail = false;

        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->article->delete();

            if ($success) {
                $id = $this->article->id;
                header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/articles");
            } else {
                $fail = true;
            }

        }

        return Template::render('admin/blog/delete', array(
            'article' => $this->article,
            'success' => $success,
            'fail' => $fail,
        ));
    }
}
