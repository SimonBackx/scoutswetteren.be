<?php
namespace Pirate\Sails\Leden\Models;

use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Users\Models\User;
use Pirate\Sails\AmazonSes\Classes\Mail;
use Pirate\Wheel\Model;

class Ouder extends Model
{
    public $id;
    public $gezin; // object
    public $titel;
    public $adres; // object
    public $user; // object

    // Deprecated
    public $voornaam;
    public $achternaam;
    public $gsm;
    public $email;

    //private $password;
    //private $set_password_key;

    static $titels = array('Mama', 'Papa', 'Voogd', 'Stiefmoeder', 'Stiefvader');

    public static function titelToGroepsadmin($functie)
    {
        $functie = strtolower($functie);
        $map = array(
            'mama' => 'moeder',
            'papa' => 'vader',
            'voogd' => 'voogd',
            'stiefmoeder' => 'moeder',
            'stiefvader' => 'vader',
        );

        if (!isset($map[$functie])) {
            return 'moeder';
        }

        return $map[$functie];
    }

    public function getGroepsadminRol()
    {
        return static::titelToGroepsadmin($this->titel);
    }

    public static $filters = array(
        'all' => array(
            'name' => 'Alle ouders',
            'where' => '',
        ),
        'inschrijving_onvolledig' => array(
            'name' => 'Inschrijving niet voltooid, niet betaald of steekkaart niet ingevuld',
            'where' => 'i.afrekening is null or s.laatst_nagekeken is null or i.afrekening_oke = 0',
        ),
        'steekkaart_leeg' => array(
            'name' => 'Steekkaart niet ingevuld',
            'where' => 's.laatst_nagekeken is null',
        ),
        'niet_betaald' => array(
            'name' => 'Nog niet betaald of inschrijving niet voltooid',
            'where' => 'i.afrekening is null or i.afrekening_oke = 0',
        ),
        'niet_voltooid' => array(
            'name' => 'Inschrijving niet voltooid (= geen afrekening aangemaakt)',
            'where' => 'i.afrekening is null',
        ),
        'verminderd_lidgeld' => array(
            'name' => 'Leden met verminderd lidgeld',
            'where' => 'g.scouting_op_maat = 1',
        ),
        'in_orde' => array(
            'name' => 'Inschrijving volledig in orde',
            'where' => 's.laatst_nagekeken is not null and i.afrekening_oke = 1 and i.afrekening is not null',
        ),
    );

    private $temporaryMagicToken = null;

    public function __construct($row = array())
    {
        if (count($row) == 0) {
            $this->user = new User();
            return;
        }

        $this->id = $row['id'];
        $this->user = new User($row);

        if (!empty($row['gezin_id'])) {
            $this->gezin = new Gezin($row);
        } else {
            $this->gezin = null;
        }

        $this->titel = $row['titel'];

        if (!empty($row['adres_id'])) {
            $this->adres = new Adres($row);
        } else {
            $this->adres = null;
        }

    }

    public function getAdres()
    {
        if (!isset($this->adres)) {
            return '';
        }
        return $this->adres->toString();
    }

    // empty array on success
    // array of errors on failure
    public function setProperties(&$data)
    {
        $errors = $this->user->setProperties($data, false);

        if (!in_array($data['titel'], self::$titels)) {
            $errors[] = 'Ongeldige titel';
        } else {
            $this->titel = $data['titel'];
        }

        $model = Adres::find($data['adres'], $data['gemeente'], $data['postcode'], $data['telefoon'], $errors);
        if (isset($model)) {
            $this->adres = $model;
        } else {
            if (strlen($data['adres']) < 2) {
                $data['adres'] = '';
            }
        }

        return $errors;
    }

    public static function oudersToFieldArray($original)
    {
        $arr = array();
        foreach ($original as $key => $value) {
            $arr[] = $value->getProperties();
        }
        return $arr;
    }

    public function getProperties()
    {
        return array(
            'titel' => $this->titel,
            'firstname' => $this->user->firstname,
            'lastname' => $this->user->lastname,
            'adres' => isset($this->adres) ? $this->adres->getAdres() : "",
            'gemeente' => isset($this->adres) ? $this->adres->gemeente : "",
            'postcode' => isset($this->adres) ? $this->adres->postcode : "",
            'telefoon' => isset($this->adres->telefoon) ? $this->adres->telefoon : "",
            'phone' => $this->user->phone,
            'mail' => $this->user->mail,
        );
    }

    public function setGezin(Gezin $gezin)
    {
        $this->gezin = $gezin;
    }

    /*function getSetPasswordUrl() {
    if ($this->hasPassword()) {
    return "https://".$_SERVER['SERVER_NAME']."/ouders/wachtwoord-vergeten/".$this->set_password_key;
    }
    return "https://".$_SERVER['SERVER_NAME']."/ouders/account-aanmaken/".$this->set_password_key;
    }*/

    public function hasPassword()
    {
        return !empty($this->password);
    }

    public function generatePasswordRecoveryKey()
    {
        // Generate and put in $this->set_password_key
        return $this->user->generatePasswordRecoveryKey();
    }

    public function save()
    {
        if (!isset($this->adres)) {
            return false;
        }

        if (!$this->user->save()) {
            return false;
        }

        if (empty($this->gezin)) {
            return false;
        }
        $gezin = self::getDb()->escape_string($this->gezin->id);
        $adres = self::getDb()->escape_string($this->adres->id);
        $titel = self::getDb()->escape_string($this->titel);
        $user_id = self::getDb()->escape_string($this->user->id);

        if (empty($this->id)) {

            $query = "INSERT INTO
                ouders (`user_id`, `gezin`, `titel`, `adres`)
                VALUES ('$user_id','$gezin', '$titel','$adres')";
        } else {

            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE ouders
                SET
                 `user_id` = '$user_id',
                 `gezin` = '$gezin',
                 `titel` = '$titel',
                 `adres` = '$adres'
                 where id = '$id'
            ";
        }

        if (self::getDb()->query($query)) {
            if (empty($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }
        return false;
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                ouders WHERE id = '$id' ";

        if (self::getDb()->query($query)) {
            // We houden de user
            return true;
        }

        return false;
    }

    public static function temporaryLoginWithPasswordKey($key)
    {
        if (User::temporaryLoginWithPasswordKey($key)) {
            return Self::isLoggedIn();
        }

        return false;
    }

    // Returns true on success
    // Sets cookies if succeeded
    // isLoggedIn() etc kan gebruikt worden hierna
    public static function login($mail, $password)
    {
        if (User::login($mail, $password)) {
            return Self::isLoggedIn();
        }
        return false;
    }

    public static function loginWithMagicToken($mail, $magicToken)
    {
        if (User::loginWithMagicToken($mail, $magicToken)) {
            return Self::isLoggedIn();
        }
        return false;
    }

    public static function getOuderForId($id)
    {
        $id = self::getDb()->escape_string($id);

        $query = '
            SELECT o.*, a.*, g.*, u.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
                join users u on o.user_id = u.user_id
            where o.id = "' . $id . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new Ouder($row);
            }
        }
        return null;
    }

    public static function getByUserId($id)
    {
        $id = self::getDb()->escape_string($id);

        $query = '
            SELECT o.*, a.*, g.*, u.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
                join users u on o.user_id = u.user_id
            where u.user_id = "' . $id . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new Ouder($row);
            }
        }
        return null;
    }

    public static function getOudersForGezin($gezin_id)
    {
        $gezin = self::getDb()->escape_string($gezin_id);

        $ouders = array();
        $query = '
            SELECT o.*, a.*, g.*, u.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
                join users u on o.user_id = u.user_id
            where o.gezin = "' . $gezin . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $ouders[] = new Ouder($row);
                }
            }
        }
        return $ouders;
    }

    /// True als er leden zijn ingeschreven voor huidig scoutsjaar, ookal is er nog geen afrekenign gemaakt
    public function isStillActive()
    {
        $leden = Lid::getLedenForOuder($this);
        foreach ($leden as $lid) {
            if ($lid->isIngeschreven()) {
                return true;
            }
        }
        return false;
    }

    public static function getOuders($filter = null, $tak = null, $return_leden = false, $scoutsjaar = null)
    {
        $where = '';

        if (!is_null($filter)) {
            if (is_array($filter)) {
                // Filter op veldnamen
                $fields = array('phone', 'mail');
                foreach ($fields as $field) {
                    if (isset($filter[$field])) {
                        if (!is_array($filter[$field])) {
                            $filter[$field] = array($filter[$field]);
                        }
                        foreach ($filter[$field] as $value) {
                            if (strlen($where) > 0) {
                                $where .= ' OR ';
                            }

                            $where .= 'u.' . $field . ' = "' . self::getDb()->escape_string($value) . '"';

                        }
                    }
                }
                if (!empty($where)) {
                    $where = "($where)";
                }
            } elseif (isset(self::$filters[$filter])) {
                // Filter op premade selectors

                $filter = self::$filters[$filter];

                if (!empty($filter['where'])) {
                    $where = '(' . $filter['where'] . ')';
                }
            }
        }
        if (!is_null($tak)) {
            if (strlen($where) > 0) {
                $where .= ' AND ';
            }

            $where .= 'i.tak = "' . self::getDb()->escape_string($tak) . '"';
        }

        if (strlen($where) > 0) {
            $where .= ' AND ';
        }

        $where .= 'i.datum_uitschrijving is null';

        if (strlen($where) > 0) {
            $where = 'WHERE ' . $where;
        }

        if (!isset($scoutsjaar)) {
            $scoutsjaar = intval(Inschrijving::getScoutsjaar());
        } else {
            $scoutsjaar = self::getDb()->escape_string($scoutsjaar);
        }

        $ouders = array();

        if ($return_leden) {
            $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join gezinnen g on g.gezin_id = l.gezin
                join ouders o on l.gezin = o.gezin
                join users u on o.user_id = u.user_id
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = ' . $scoutsjaar . ' and i.tak != ""
                left join steekkaarten s on s.lid = l.id
            ' . $where . '
            GROUP BY l.id, g.gezin_id, i.inschrijving_id, s.steekkaart_id
            order by year(l.geboortedatum) desc, l.voornaam;';
        } else {
            $query = '
            SELECT o.*, a.*, g.*, u.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
                join users u on o.user_id = u.user_id
                join leden l on l.gezin = o.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = ' . $scoutsjaar . '
                left join steekkaarten s on s.lid = l.id
            ' . $where . '
            GROUP BY o.id, g.gezin_id';
        }

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                if (!$return_leden) {
                    while ($row = $result->fetch_assoc()) {
                        $ouders[] = new Ouder($row);
                    }
                } else {
                    while ($row = $result->fetch_assoc()) {
                        $ouders[] = new Lid($row);
                    }
                }
            }
        }
        return $ouders;
    }

    /**
     * Controleert of de huidige bezoeker ingelogd is
     * @return Leiding Geeft leiding object van bezoeker terug indien de gebruiker ingelogd is. NULL indien niet ingelogd
     */
    private static function checkLogin()
    {
        $user = User::getUser();
        if (isset($user)) {
            // We zijn ingelogd.
            // Zijn we ook een leider?

            // Eerst cache checken
            $didCheckOuder = false;

            if (isset($user->didCheckOuder)) {
                $didCheckOuder = $user->didCheckOuder;
            }

            if ($didCheckOuder) {
                if (isset($user->ouder)) {
                    return $user->ouder;
                }

                // Al eens gekeken, en toen vonden we geen leiding
                return null;
            }

            // Eerste keer dat we kijken: zoeken of er een leider bestaat met hetzelfde id
            $ouder = self::getByUserId($user->id);
            $user->didCheckOuder = true;

            if (isset($ouder)) {
                // Save in memory cache
                $user->ouder = $ouder;
                return $ouder;
            }
            return null;
        }

        return null;
    }

    public static function isLoggedIn()
    {
        return !is_null(Self::checkLogin());
    }

    public static function getUser()
    {
        return self::checkLogin();
    }

    public function sendCreatedMail(User $created_by)
    {
        $mail = new Mail('Account scoutswebsite', 'user-new-gezin', array('user' => $this->user, 'creator' => $created_by));
        $mail->addTo(
            $this->user->mail,
            array(),
            $this->user->firstname . ' ' . $this->user->lastname
        );
        return $mail->send();
    }

    /// Return true when users are probably the same
    public function isProbablyEqual($ouder)
    {
        return $this->user->isProbablyEqual($ouder->user);
    }

    /// Voeg alle data van een ouder bij een andere ouder. Meegegeven ouder moet de oudste versie zijn
    public function merge($ouder)
    {
        // Voorlopig is er geen data dat overgezet moet worden
        return $ouder->delete();
    }

}
