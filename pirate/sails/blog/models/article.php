<?php
namespace Pirate\Sails\Blog\Models;

use Pirate\Sails\Users\Models\User;
use Pirate\Sails\Validating\Classes\DatabaseError;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Wheel\Model;

class Article extends Model
{
    public $id;
    public $title;
    public $published;
    public $edited = null;
    public $html;
    private $text;
    private $json;

    public $slug;

    public $author; // id
    public $editor; // id

    public function __construct($row = [])
    {
        if (empty($row)) {
            return;
        }
        $this->id = $row['id'];
        $this->title = $row['title'];
        $this->published = new \DateTime($row['published']);

        if (!empty($row['edited'])) {
            $this->edited = new \DateTime($row['edited']);
        }

        $this->html = $row['html'];
        $this->text = $row['text'];

        if (empty($row['json'])) {
            $this->json = null;
        } else {
            try {
                $this->json = json_decode($row['json'], false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $ex) {
                $this->json = null;
            }
        }

        $this->slug = $row['slug'];

        // Todo: aanpassen en hier referentie naar ander Model van maken
        $this->author = $row['author'];
        $this->editor = $row['editor'];
    }

    public function getUrl()
    {
        return '/blog/' . datetimeToUrl($this->published) . '/' . $this->slug;
    }

    public static function get($id)
    {
        $id = self::getDb()->escape_string($id);

        $query = "SELECT * from articles where `id` = '$id'";
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                if ($row = $result->fetch_assoc()) {
                    return new Article($row);
                }
            }
        }

        return null;
    }

    public static function getArticle($date, $slug)
    {
        $date = self::getDb()->escape_string($date);
        $slug = self::getDb()->escape_string($slug);

        $query = "SELECT * from articles where `published` = '$date' and `slug` = '$slug'";
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                if ($row = $result->fetch_assoc()) {
                    return new Article($row);
                }
            }
        }

        return null;
    }

    // Maximaal 5 artikels, pagina grootte = 4
    // Detectie of volgende pagina bestaat is dus gewoon nagaan of er 5 zijn meegegeven
    // Als pagina = 0 => laatste 150 artikels tonen (= archief)
    public static function getArticles($page = 1, $page_size = 4)
    {
        $page = intval($page);

        $limit = 'LIMIT ' . (($page - 1) * $page_size) . ', ' . ($page_size + 1);
        if ($page < 1) {
            $limit = 'limit 150';
        }

        $articles = array();
        $query = 'SELECT * from articles order by published desc, edited desc ' . $limit;
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $articles[] = new Article($row);
                }
            }
        }
        return $articles;
    }

    // Maximaal 5 artikels, pagina grootte = 4
    // Detectie of volgende pagina bestaat is dus gewoon nagaan of er 5 zijn meegegeven
    public static function searchArticles($needle)
    {
        $needle = self::getDb()->escape_string($needle);

        $articles = array();
        $query = 'SELECT * from articles  WHERE MATCH (title,`text`) AGAINST ("' . $needle . '" IN NATURAL LANGUAGE MODE);';
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $articles[] = new Article($row);
                }
            }
        }
        return $articles;
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    public function setProperties(&$data)
    {
        $errors = new ValidationErrors();

        if (isset($data['json'])) {
            try {
                $this->json = json_decode($data['json'], false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $ex) {
                throw new ValidationError("json is invalid", "json");
            }

            if (!isset($this->json->blocks)) {
                throw new ValidationError("Missing blocks in json", "json");
            }

            // Read title from json + read all text for search engine
            $found = false;
            $text = "";
            $html = "";

            foreach ($this->json->blocks as $block) {
                if (!isset($block->type)) {
                    throw new ValidationError("Missing block type in json", "json");
                }
                if ($block->type == 'header' && !$found) {
                    if (!isset($block->data)) {
                        throw new ValidationError("Missing block data in json", "json");
                    }
                    if (!isset($block->data->text)) {
                        throw new ValidationError("Missing block data text in json", "json");
                    }

                    if (isset($block->data->level) && $block->data->level == 1) {
                        $this->title = $block->data->text;
                        $found = true;
                        // Do not add this to text and html
                        continue;
                    }
                }

                if (isset($block->data, $block->data->text)) {
                    $text .= strip_tags($block->data->text) . "\n";
                }

                switch ($block->type) {
                    case 'header':
                        $html .= '<h' . $block->data->level . '>';
                        $html .= $block->data->text;
                        $html .= '</h' . $block->data->level . '>';
                        break;

                    case 'paragraph':
                        $html .= '<p>';
                        $html .= $block->data->text;
                        $html .= '</p>';
                        break;

                    case 'image':
                        $classes = '';
                        $classes .= $block->data->stretched ? ' stretched' : '';
                        $classes .= $block->data->withBorder ? ' with-border' : '';
                        $classes .= $block->data->withBackground ? ' with-background' : '';

                        $html .= '<div class="image-container' . $classes . '">';
                        $html .= '<img src="' . htmlspecialchars($block->data->file->url) . '" width="' . htmlspecialchars($block->data->file->width) . '" height="' . htmlspecialchars($block->data->file->height) . '" alt="' . htmlspecialchars($block->data->caption) . '" title="' . htmlspecialchars($block->data->caption) . '">';
                        $html .= '</div>';
                        break;

                    case 'list':
                        if ($block->data->style == 'ordered') {
                            $html .= '<ol>';
                        } else {
                            $html .= '<ul>';
                        }

                        foreach ($block->data->items as $item) {
                            $html .= '<li>';
                            $html .= $item;
                            $html .= '</li>';
                        }

                        if ($block->data->style == 'ordered') {
                            $html .= '</ol>';
                        } else {
                            $html .= '</ul>';
                        }
                        break;

                    case 'warning':
                        $html .= '<ul class="warning"><li>';
                        $html .= '<b>' . $block->data->title . '</b><br>';
                        $html .= $block->data->message;
                        $html .= '</li></ul>';
                        break;

                    case 'delimiter':
                        $html .= '<hr>';
                        break;
                }
            }

            if (!$found || empty($this->title)) {
                throw new ValidationError("Zorg ervoor dat je ten minste één titel in je artikel hebt staan met niveau H1. Dit wordt de titel van het artikel.", "json");
            }

            // Convert to TEXT for search engine
            $this->text = $text;

            // Convert to HTML
            $this->html = $html;

            if (empty($this->slug)) {
                $this->slug = sluggify($this->title);
            }

        } else {
            if (is_null($this->json)) {
                throw new ValidationError("json not present", "json");
            }
        }

        if (empty($this->author)) {
            $this->author = User::getUser()->id;
        }

        if (empty($this->published)) {
            $this->published = new \DateTime();
        }
        $this->edited = new \DateTime();
        $this->editor = User::getUser()->id;

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    public function getDefaultJson()
    {
        return (object) [
            'blocks' => [
                (object) [
                    'type' => "header",
                    'data' => (object) [
                        'text' => "Titel van jouw artikel",
                        'level' => 1,
                    ],
                ],
                (object) [
                    'type' => "paragraph",
                    'data' => (object) [
                        'text' => "Vul hier aan met tekst",
                    ],
                ],
            ],
            'version' => "2.16.0",
            'time' => time(),
        ];
    }

    public function getProperties()
    {
        return array(
            'json' => empty($this->json) ? json_encode($this->getDefaultJson()) : json_encode($this->json),
        );
    }

    public function save()
    {
        // Autofill fields
        if (empty($this->title)) {
            throw new \Exception("Invalid title");
        }
        if (empty($this->slug)) {
            throw new \Exception("Invalid slug");
        }

        $title = self::getDb()->escape_string($this->title);
        $html = self::getDb()->escape_string($this->html);
        $text = self::getDb()->escape_string($this->text);
        $slug = self::getDb()->escape_string($this->slug);
        $published = self::getDb()->escape_string($this->published->format('Y-m-d'));

        if (!isset($this->json)) {
            $json = "NULL";
        } else {
            try {
                $json = "'" . self::getDb()->escape_string(json_encode($this->json, JSON_THROW_ON_ERROR)) . "'";
            } catch (\JsonException $ex) {
                $json = "NULL";
            }
        }

        if (!isset($this->author)) {
            $author = "NULL";
        } else {
            $author = "'" . self::getDb()->escape_string($this->author) . "'";
        }

        if (!isset($this->editor)) {
            $editor = "NULL";
        } else {
            $editor = "'" . self::getDb()->escape_string($this->editor) . "'";
        }

        if (!isset($this->edited)) {
            $edited = "NULL";
        } else {
            $edited = "'" . self::getDb()->escape_string($this->edited->format('Y-m-d H:i:s')) . "'";
        }

        if (!isset($this->id)) {

            $query = "INSERT INTO
                articles (`title`, `published`, `edited`, `html`, `text`, `json`, `slug`, `author`, `editor`)
                VALUES ('$title', '$published', $edited, '$html', '$text', $json, '$slug', $author, $editor)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE articles
                SET
                 `title` = '$title',
                 `published` = '$published',
                 `edited` = $edited,
                 `html` = '$html',
                 `text` = '$text',
                 `json` = $json,
                 `slug` = '$slug',
                 `author` = $author,
                 `editor` = $editor
                 where id = '$id'
            ";
        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }

            return true;
        }
        throw new DatabaseError(self::getDb()->error);
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                articles WHERE `id` = '$id' ";

        // Linked tables will get deleted automatically + restricted when orders exist with this product

        return self::getDb()->query($query);
    }
}
