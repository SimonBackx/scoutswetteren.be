<?php
namespace Pirate\Sails\Homepage\Models;

use Pirate\Sails\Validating\Classes\DatabaseError;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Wheel\Model;

class Slideshow extends Model
{
    public $id;

    public $priority;
    public $title;
    public $text;
    public $button; // null or array of text, url
    public $extra_button; // null or array of text, url

    public function __construct($row = array())
    {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['slideshow_id'];
        $this->title = $row['slideshow_title'];
        $this->text = $row['slideshow_text'];
        $this->priority = $row['slideshow_priority'];

        $this->button = null;
        $this->extra_button = null;

        if (isset($row['slideshow_button_text'])) {
            $this->button = [
                "text" => $row['slideshow_button_text'],
                "url" => $row['slideshow_button_url'],
            ];
        }

        if (isset($row['slideshow_extra_button_text'])) {
            $this->extra_button = [
                "text" => $row['slideshow_extra_button_text'],
                "url" => $row['slideshow_extra_button_url'],
            ];
        }
    }

    public static function getSlideshows()
    {
        $slideshows = array();
        $query = '
            SELECT * from slideshows
            order by slideshow_priority desc, slideshow_id desc';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $slideshows[] = new Slideshow($row);
                }
            }
        }
        return $slideshows;
    }

    public static function getById($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT * FROM slideshows
        WHERE slideshow_id = "' . $id . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                return new Slideshow($result->fetch_assoc());
            }
        }
        return null;
    }

    public function getData()
    {
        return [
            'title' => $this->title ?? '',
            'text' => $this->text ?? '',
            'priority' => $this->priority ?? 0,
            'button' => isset($this->button) ? true : false,
            'button_text' => isset($this->button) ? $this->button['text'] : '',
            'button_url' => isset($this->button) ? $this->button['url'] : '',
            'extra_button' => isset($this->extra_button) ? true : false,
            'extra_button_text' => isset($this->extra_button) ? $this->extra_button['text'] : '',
            'extra_button_url' => isset($this->extra_button) ? $this->extra_button['url'] : '',
        ];
    }

    public function save()
    {
        $title = self::getDb()->escape_string($this->title);
        $text = self::getDb()->escape_string($this->text);
        $priority = self::getDb()->escape_string($this->priority);

        if (!isset($this->button)) {
            $button_text = 'NULL';
            $button_url = 'NULL';
        } else {
            $button_text = "'" . self::getDb()->escape_string($this->button['text']) . "'";
            $button_url = "'" . self::getDb()->escape_string($this->button['url']) . "'";
        }

        if (!isset($this->extra_button)) {
            $extra_button_text = 'NULL';
            $extra_button_url = 'NULL';
        } else {
            $extra_button_text = "'" . self::getDb()->escape_string($this->extra_button['text']) . "'";
            $extra_button_url = "'" . self::getDb()->escape_string($this->extra_button['url']) . "'";
        }

        if (empty($this->id)) {
            $query = "INSERT INTO
                slideshows (`slideshow_title`, `slideshow_text`, `slideshow_priority`, `slideshow_button_text`, `slideshow_button_url`, `slideshow_extra_button_text`, `slideshow_extra_button_url`)
                VALUES ('$title', '$text', '$priority', $button_text, $button_url, $extra_button_text, $extra_button_url)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE slideshows
                SET
                 `slideshow_title` = '$title',
                 `slideshow_text` = '$text',
                 `slideshow_priority` = '$priority',
                 `slideshow_button_text` = $button_text,
                 `slideshow_button_url` = $button_url,
                 `slideshow_extra_button_text` = $extra_button_text,
                 `slideshow_extra_button_url` = $extra_button_url
                 where `slideshow_id` = '$id'
            ";
        }

        if (self::getDb()->query($query)) {
            if (empty($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        throw new DatabaseError(self::getDb()->error);
    }

    public function setProperties(&$data)
    {
        $errors = new ValidationErrors();

        if (isset($data['title'])) {
            if (is_string($data['title']) && strlen($data['title']) > 2) {
                $this->title = $data['title'];
            } else {
                $errors->extend(new ValidationError("Ongeldige titel ingevuld", "title"));
            }
        } else {
            $this->title = $this->title ?? "";
        }

        if (isset($data['text'])) {
            if (is_string($data['text'])) {
                $this->text = $data['text'];
            } else {
                $errors->extend(new ValidationError("Ongeldige tekst ingevuld", "text"));
            }
        } else {
            $this->text = $this->text ?? "";
        }

        if (isset($data['priority'])) {
            if (intval($data['priority']) >= 0 && intval($data['priority']) <= 4) {
                $this->priority = intval($data['priority']);
            } else {
                $errors->extend(new ValidationError("Ongeldige prioriteit ingevuld", "priority"));
            }
        } else {
            $this->priority = $this->priority ?? 1;
        }

        if (isset($data['button'])) {
            // Checked to add a button
            $this->button = [
                "text" => "",
                "url" => "",
            ];
            if (isset($data['button_text']) && is_string($data['button_text'])) {
                $this->button['text'] = $data['button_text'];
            } else {
                $errors->extend(new ValidationError("Ongeldige knop tekst ingevuld", "button_text"));
            }

            if (isset($data['button_url']) && is_string($data['button_url'])) {
                $this->button['url'] = $data['button_url'];
            } else {
                $errors->extend(new ValidationError("Ongeldige knop URL ingevuld", "button_url"));
            }
        } else {
            $this->button = null;
        }

        if (isset($data['extra_button'])) {
            // Checked to add a button
            $this->extra_button = [
                "text" => "",
                "url" => "",
            ];
            if (isset($data['extra_button_text']) && is_string($data['extra_button_text'])) {
                $this->extra_button['text'] = $data['extra_button_text'];
            } else {
                $errors->extend(new ValidationError("Ongeldige knop tekst ingevuld", "extra_button_text"));
            }

            if (isset($data['extra_button_url']) && is_string($data['extra_button_url'])) {
                $this->extra_button['url'] = $data['extra_button_url'];
            } else {
                $errors->extend(new ValidationError("Ongeldige knop URL ingevuld", "extra_button_url"));
            }
        } else {
            $this->extra_button = null;
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                slideshows WHERE slideshow_id = '$id' ";

        if (self::getDb()->query($query)) {
            return true;
        }

        return false;
    }
}
