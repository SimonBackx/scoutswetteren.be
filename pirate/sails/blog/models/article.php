<?php
namespace Pirate\Model;
use Pirate\Model\Model;

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

    // Maximaal 5 artikels, pagina grootte = 4 
    // Detectie of volgende pagina bestaat is dus gewoon nagaan of er 5 zijn meegegeven
    static function getArticles($page = 1) {
        $page = intval($page);

        $articles = array();
        $query = 'SELECT * from articles order by published desc, edited desc LIMIT '.(($page-1)*4).', 5';
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