<?php
namespace Pirate\Sails\Blog\Models;
use Pirate\Wheel\Model;

class Article extends Model {
    public $id;
    public $title;
    public $published;
    public $edited = null;
    public $html;
    private $text;

    public $slug;

    public $author;
    public $editor;

    function __construct($row) {
        $this->id = $row['id'];
        $this->title = $row['title'];
        $this->published = new \DateTime($row['published']);

        if (!empty($row['edited'])) {
            $this->edited = new \DateTime($row['edited']);
        }

        $this->html = $row['html'];
        $this->text = $row['text'];

        $this->slug = $row['slug'];

        // Todo: aanpassen en hier referentie naar ander Model van maken
        $this->author = $row['author'];
        $this->editor = $row['editor'];
    }

    function getUrl() {
        return '/blog/'.datetimeToUrl($this->published).'/'.$this->slug;
    }

    static function getArticle($date, $slug) {
        $date = self::getDb()->escape_string($date);
        $slug = self::getDb()->escape_string($slug);

        $query = "SELECT * from articles where `published` = '$date' and `slug` = '$slug'";
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
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
    static function getArticles($page = 1) {
        $page = intval($page);

        $limit = 'LIMIT '.(($page-1)*4).', 5';
        if ($page < 1) {
            $limit = 'limit 150';
        }

        $articles = array();
        $query = 'SELECT * from articles order by published desc, edited desc '.$limit;
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $articles[] = new Article($row);
                }
            }
        }
        return $articles;
    }

    // Maximaal 5 artikels, pagina grootte = 4 
    // Detectie of volgende pagina bestaat is dus gewoon nagaan of er 5 zijn meegegeven
    static function searchArticles($needle) {
        $needle = self::getDb()->escape_string($needle);

        $articles = array();
        $query = 'SELECT * from articles  WHERE MATCH (title,`text`) AGAINST ("'.$needle.'" IN NATURAL LANGUAGE MODE);';
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $articles[] = new Article($row);
                }
            }
        }
        return $articles;
    }
}