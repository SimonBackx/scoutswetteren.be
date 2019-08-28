<?php
namespace Pirate\Sails\Leiding\Models;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Settings\Models\Setting;
use Pirate\Sails\Users\Models\User;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Wheel\Mail;
use Pirate\Wheel\Model;

class Leiding extends Model
{
    public $id;
    public $user; // object
    public $totem;
    public $tak;
    public $permissions = array();

    // als didCheckLogin == false, dan hebben we nog niet gecontrolleerd of de huidige gebruiker een leider is
    /*private static $didCheckLogin = false;
    private static $currentUser = null;*/

    private static $allPermissions;
    private static $adminMenu;

    public function __construct($row = null)
    {
        if (!isset($row)) {
            $this->user = new User();
            return;
        }

        $this->id = $row['id'];
        $this->user = new User($row);
        $this->totem = $row['totem'];
        $this->tak = $row['tak'];
        $this->permissions = explode('±', $row['permissions']);

        if (count($this->permissions) == 1 && $this->permissions[0] == '') {
            $this->permissions = array();
        }
    }

    public static function getLeidingsverdeling()
    {
        $leidingsverdeling = Setting::getSetting('leidingsverdeling');

        if (isset($leidingsverdeling) && isset($leidingsverdeling->value)) {
            return new \DateTime($leidingsverdeling->value);
        }
        return null;
    }

    public static function disableLeidingsverdeling()
    {
        $leidingsverdeling = Setting::getSetting('leidingsverdeling');
        if (!isset($leidingsverdeling->id)) {
            return true;
        }
        return $leidingsverdeling->delete();
    }

    public static function setLeidingsverdeling(&$errors, $date, $time)
    {
        $leidingsverdeling = Setting::getSetting('leidingsverdeling');

        $datetime = \DateTime::createFromFormat('d-m-Y H:i', $date . ' ' . $time);
        if ($datetime !== false) {
            $leidingsverdeling->value = $datetime->format('Y-m-d H:i') . ':00';
            return $leidingsverdeling->save();
        } else {
            $errors[] = 'Ongeldige datum en/of tijdstip.';
            return false;
        }
    }

    public static function isLeidingZichtbaar()
    {
        $leidingsverdeling = Self::getLeidingsverdeling();

        if (!isset($leidingsverdeling)) {
            return true;
        }

        $now = new \DateTime("now");
        if ($now < $leidingsverdeling) {
            return false;
        }
        return true;
    }

    // Geeft lijst van contact personen (array(key -> name))
    public static function getContacts()
    {
        // Default van alle publieke contact personen
        //
        return Environment::getSetting('contacts');
    }

    /// Returns a list with objects with: name, mail, send_from (bool)
    public static function getContactEmails($contact_key)
    {
        $contacts = self::getContacts();
        if (!isset($contacts[$contact_key])) {
            throw new \Exception("Contact key not found: $contact_key");
        }

        $contact_data = $contacts[$contact_key];

        $list = [];

        if (!isset($contact_data['mail'])) {
            if (isset($contact_data['tak'])) {
                $leiding = Leiding::getLeiding($contact_data['permission'], $contact_data['tak']);
            } else {
                $leiding = Leiding::getLeiding($contact_data['permission']);
            }

            foreach ($leiding as $leider) {
                $list[] = (object) [
                    'mail' => $leider->user->mail,
                    'name' => $leider->user->firstname . ' ' . $leider->user->lastname,
                    'send_from' => false,
                ];
            }

            if (count($list) == 0) {
                // default to development mail
                $list[] = (object) [
                    'mail' => Environment::getSetting('development_mail.mail'),
                    'name' => Environment::getSetting('development_mail.name'),
                    'send_from' => false,
                ];
            }
        } else {
            $list[] = (object) [
                'mail' => $contact_data['mail'],
                'name' => $contact_data['name'],
                'send_from' => true,
            ];
        }
        return $list;
    }

    // Geeft e-mailadres voor een bepaalde contactpersoon
    public static function getContactEmail($contact_key, &$email, &$naam, &$send_from)
    {
        $list = static::getContactEmails($contact_key);
        if (count($list) == 0) {
            return false;
        }

        $email = $list[0]->mail;
        $naam = $list[0]->name;
        $send_from = $list[0]->send_from;

        return true;
    }

    public static function getPossiblePermissions()
    {
        if (isset(self::$allPermissions)) {
            return self::$allPermissions;
        }

        $permissions = array();
        $query = "SELECT * from permissions";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $permissions[$row['permissionCode']] = $row['permissionName'];
                }
            }
        }

        self::$allPermissions = $permissions;

        return $permissions;
    }

    public static function getLeiding($permission = null, $tak = null)
    {
        $permission_code = '';
        if (!is_null($permission)) {
            $permission_code = "WHERE p2.permissionCode = '" . self::getDb()->escape_string($permission) . "'";
        } else {
            $permission_code = "WHERE p.permissionId IS NULL OR p2.permissionId = p.permissionId";
            // TODO: Kan versneld worden als persmission = null -> dan dubbele joins weglaten
        }

        if (!is_null($tak)) {
            $permission_code .= " AND l.tak = '" . self::getDb()->escape_string($tak) . "'";
        }

        $leiding = array();
        $query = "SELECT l.*, u.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        join users u on u.user_id = l.user_id
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId

        left join _permissions_leiding _pl2 on _pl2._leidingId = l.id
        left join permissions p2 on p2.permissionId = _pl2._permissionId

        $permission_code
        group by l.id
        order by l.tak
        ";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $leiding[] = new Leiding($row);
                }
            }
        }

        return $leiding;
    }

    public static function getLeidingById($id)
    {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);

        $query = "SELECT l.*, u.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        join users u on u.user_id = l.user_id
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId
        where l.id = '$id'
        group by l.id";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 0) {
                return null;
            }
            $row = $result->fetch_assoc();
            return new Leiding($row);
        }

        return null;
    }

    public static function getByUserId($id)
    {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);

        $query = "SELECT l.*, u.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        join users u on u.user_id = l.user_id
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId
        where l.user_id = '$id'
        group by l.id";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 0) {
                return null;
            }
            $row = $result->fetch_assoc();
            return new Leiding($row);
        }

        return null;
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

    public static function getAdminMenu()
    {
        if (isset(self::$adminMenu)) {
            return self::$adminMenu;
        }

        include __DIR__ . '/../../_bindings/admin.php';

        $allButtons = [];
        $urls = [];
        $ignoreButtons = [];

        foreach ($admin_pages as $permission => $buttons) {
            if ($permission == '' || self::hasPermission($permission)) {
                foreach ($buttons as $button) {
                    $priority = isset($button['priority']) ? $button['priority'] : 0;
                    $button['priority'] = $priority;

                    if (isset($urls[$button['url']])) {
                        $o = $urls[$button['url']];
                        if ($priority <= $o->priority) {
                            continue;
                        }

                        // Remove old button
                        array_splice($allButtons[$o->priority], $o->index, 1);

                        // Nu alle oude priority indexen updaten
                        foreach ($urls as $key => $value) {
                            if ($value->priority == $o->priority && $value->index >= $o->index) {
                                $value->index--;
                            }
                        }
                    }

                    if (!isset($allButtons[$priority])) {
                        $allButtons[$priority] = [];
                    }

                    $urls[$button['url']] = (object) [
                        'priority' => $priority,
                        'index' => count($allButtons[$priority]),
                    ];

                    $allButtons[$priority][] = $button;
                }
            }
        }
        ksort($allButtons);

        $sortedButtons = [];

        foreach ($allButtons as $priority => $buttons) {
            $sortedButtons = array_merge($buttons, $sortedButtons);
        }

        return $sortedButtons;
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
            $didCheckLeiding = false;

            if (isset($user->didCheckLeiding)) {
                $didCheckLeiding = $user->didCheckLeiding;
            }

            if ($didCheckLeiding) {
                if (isset($user->leiding)) {
                    return $user->leiding;
                }

                // Al eens gekeken, en toen vonden we geen leiding
                return null;
            }

            // Eerste keer dat we kijken: zoeken of er een leider bestaat met hetzelfde id
            $leiding = self::getByUserId($user->id);
            $user->didCheckLeiding = true;

            if (isset($leiding)) {
                // Save in memory cache
                $user->leiding = $leiding;
                return $leiding;
            }
            return null;
        }

        return null;
    }

    public static function isLoggedIn()
    {
        return !is_null(self::checkLogin());
    }

    public static function getPermissions()
    {
        if (!self::isLoggedIn()) {
            return array();
        }
        return self::getUser()->permissions;
    }

    public static function getUser()
    {
        return self::checkLogin();
    }

    // Case sensitive
    public static function hasPermission($permission)
    {
        if ($permission != 'webmaster' && self::hasPermission('webmaster')) {
            return true;
        }
        return in_array($permission, self::getPermissions());
    }

    // empty array on success
    // array of errors on failure
    public function setProperties(&$data, $admin = false)
    {
        if (!isset($this->user->id) && isset($data['mail'])) {
            // Kijk of we een bestaande user kunnen koppelen
            $user = User::getForEmail($data['mail']);
            if (isset($user)) {
                $leiding = static::getByUserId($user->id);
                if (isset($leiding)) {
                    $errors[] = 'Er bestaat al een gebruiker met dit e-mailadres';
                } else {
                    $this->user = $user;
                    if (isset($data['phone']) && strlen($data['phone']) == 0) {
                        // Hou bestaand gsm-nummer
                        $data['phone'] = $user->phone;
                    }
                }
            }
        }
        $errors = $this->user->setProperties($data, $admin);

        if (strlen($data['totem']) == 0) {
            $this->totem = null;
        } elseif (Validator::isValidTotem($data['totem'])) {
            $this->totem = ucfirst(strtolower($data['totem']));
            $data['totem'] = $this->totem;
        } else {
            $errors[] = 'Ongeldige totem';
        }

        // Pas de permissions aan van de leiding (enkel als de huidige user groepsleiding is, niet de user die aangepast wordt)
        if (self::hasPermission('groepsleiding')) {
            if (isset($data['tak'])) {
                if (empty($data['tak'])) {
                    $this->tak = null;
                } else {
                    if (Inschrijving::isGeldigeTak($data['tak'])) {
                        $this->tak = $data['tak'];
                    } else {
                        $errors[] = 'Ongeldige tak';
                    }
                }
            }

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $possible = self::getPossiblePermissions();
                $ok = true;
                foreach ($data['permissions'] as $code) {
                    if (!isset($possible[$code])) {
                        $errors[] = 'Ongeldige functie';
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $this->permissions = $data['permissions'];
                }
            }

            if (!is_null($this->tak)) {
                $key = array_search('leiding', $this->permissions);
                if ($key === false) {
                    $data['permissions'][] = 'leiding';
                    $this->permissions = $data['permissions'];
                }
            } else {
                $key = array_search('leiding', $this->permissions);
                if ($key !== false) {
                    $errors[] = 'Selecteer een tak (of verwijder de functie leiding)';
                }
            }
        }

        return $errors;
    }

    public function sendPasswordEmail()
    {
        $mail = new Mail('Account scoutswebsite', 'user-new-leiding', array('user' => $this->user));
        $mail->addTo(
            $this->user->mail,
            array(),
            $this->user->firstname . ' ' . $this->user->lastname
        );
        return $mail->send();
    }

    public function save()
    {
        if (!$this->user->save()) {
            return false;
        }

        $user_id = self::getDb()->escape_string($this->user->id);

        if (!isset($this->tak)) {
            $tak = 'NULL';
        } else {
            $tak = "'" . self::getDb()->escape_string($this->tak) . "'";
        }

        if (!isset($this->totem)) {
            $totem = 'NULL';
        } else {
            $totem = "'" . self::getDb()->escape_string($this->totem) . "'";
        }

        self::getDb()->autocommit(false);

        // Permissions

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE leiding
                SET
                 `user_id` = '$user_id',
                 totem = $totem,
                 tak = $tak
                 where id = '$id'
            ";
        } else {
            $query = "INSERT INTO
                leiding (`user_id`, `totem`, `tak`)
                VALUES ('$user_id', $totem, $tak)";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            $new = false;
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
                $id = self::getDb()->escape_string($this->id);
                $new = true;
            }

            if (self::hasPermission('groepsleiding')) {
                $fail = false;

                $query = "DELETE
                    FROM _permissions_leiding
                    WHERE _leidingId = '$id'";

                if (!self::getDb()->query($query)) {
                    $fail = true;
                }

                // Toevoegen:
                if (count($this->permissions) > 0 && !$fail) {
                    $str = '';
                    foreach ($this->permissions as $code) {
                        if ($str != '') {
                            $str .= ",";
                        }
                        $str .= "'" . self::getDb()->escape_string($code) . "'";
                    }
                    $query = "INSERT INTO _permissions_leiding ( _leidingId, _permissionId )
                        select '$id', permissionId from permissions where permissionCode IN($str)";
                    if (!self::getDb()->query($query)) {
                        $fail = true;
                    }
                }

                if ($fail) {
                    self::getDb()->rollback();
                    self::getDb()->autocommit(true);
                    return false;
                }
            }

            if ($new) {
                $this->sendPasswordEmail();
            }

            self::getDb()->commit();
        } else {
            self::getDb()->rollback();
        }

        self::getDb()->autocommit(true);

        return $result;
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                leiding WHERE id = '$id' ";

        if (self::getDb()->query($query)) {
            // We houden de user
            return true;
        }

        return false;
    }

    public static function sendErrorMail($subject, $message, $log)
    {
        $webmasters = static::getLeiding('webmaster');

        $mail = new Mail(
            $subject,
            'error-log',
            array(
                'message' => $message,
                'log' => $log,
            )
        );

        foreach ($webmasters as $webmaster) {
            $mail->addTo(
                $webmaster->user->mail,
                array(),
                $webmaster->user->firstname . ' ' . $webmaster->user->lastname
            );
        }

        return $mail->send();
    }
}
