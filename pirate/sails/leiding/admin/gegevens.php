<?php
namespace Pirate\Sails\Leiding\Admin;

use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Gegevens extends Page
{
    private $user = null;

    public function __construct($user = null)
    {
        $this->user = $user;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $edit = false;
        $new = false;

        if (is_null($this->user)) {
            $user = Leiding::getUser();
        } else {
            $edit = true;
            $user = $this->user;

            if (!isset($this->user->id)) {
                $new = true;
            }
        }
        $permissions = $user->permissions;

        $errors = array();
        $success = false;

        $data = array(
            'firstname' => $user->user->firstname,
            'lastname' => $user->user->lastname,
            'totem' => $user->totem,
            'roepnaam' => $user->roepnaam,
            'mail' => $user->user->mail,
            'phone' => $user->user->phone,
            'tak' => $user->tak,
        );

        $allPermissions = Leiding::getPossiblePermissions();
        if (isset($_POST['firstname'], $_POST['lastname'], $_POST['totem'], $_POST['roepnaam'], $_POST['mail'], $_POST['phone'])) {
            $data['firstname'] = $_POST['firstname'];
            $data['lastname'] = $_POST['lastname'];
            $data['totem'] = $_POST['totem'];
            $data['roepnaam'] = $_POST['roepnaam'];
            $data['mail'] = $_POST['mail'];
            $data['phone'] = $_POST['phone'];

            if (isset($_POST['tak'])) {
                $data['tak'] = $_POST['tak'];
            }

            if ($edit) {
                $data['permissions'] = array();

                foreach ($allPermissions as $code => $name) {
                    if (isset($_POST['permission_' . $code])) {
                        $data['permissions'][] = $code;
                    }
                }
            }

            // Nu één voor één controleren
            $errors = $user->setProperties($data, $edit);

            if (count($errors) == 0) {
                // Check image

                if (File::isFileSelected('avatar_photo')) {
                    $photo = new Image();
                    $photo->upload('avatar_photo', [
                        ['width' => 120, 'height' => 120],
                        ['width' => 500, 'height' => 500],
                    ], $errors);

                    if (count($errors) == 0) {
                        $user->setPhoto($photo);
                    }
                }

                if (count($errors) == 0) {
                    if ($user->save()) {
                        $success = true;

                        if ($edit) {
                            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/leiding");
                        }
                    } else {
                        $errors[] = 'Probleem bij opslaan';
                    }
                }
            }
        }

        $functies = array();
        $permission_data = array();

        foreach ($allPermissions as $code => $name) {
            $permission_data[$code] = array('name' => $name, 'checked' => false);
        }

        foreach ($permissions as $permission) {
            $permission_data[$permission]['checked'] = true;

            switch ($permission) {
                case 'leiding':
                    if (empty($user->tak)) {
                        $functies[] = 'Leiding';
                    } else {
                        $functies[] = 'Leiding (' . $user->tak . ')';
                    }
                    break;

                default:
                    $functies[] = $allPermissions[$permission];
                    break;
            }
        }

        return Template::render('pages/leiding/gegevens', array(
            'edit' => $edit,
            'new' => $new,
            'leiding' => $data,
            'functies' => $functies,
            'permissions' => $permission_data,
            'errors' => $errors,
            'takken' => Inschrijving::getTakken(),
            'success' => $success,
            'id' => $user->id,
            'photo' => !is_null($user->getPhoto()) ? $user->getPhoto()->getBestFit(120, 120)->file : null,
        ));
    }
}
