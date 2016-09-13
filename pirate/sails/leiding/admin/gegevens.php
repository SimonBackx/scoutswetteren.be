<?php
namespace Pirate\Sail\Leiding\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

class Gegevens extends Page {
    private $user = null;

    function __construct($user = null) {
        $this->user = $user;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
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
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'totem' => $user->totem,
            'mail' => $user->mail,
            'phone' => $user->phone,
            'tak' => $user->tak
        );

        $allPermissions = Leiding::getPossiblePermissions();
        if (isset($_POST['firstname'], $_POST['lastname'], $_POST['totem'], $_POST['mail'], $_POST['phone'])) {
            $data['firstname'] = $_POST['firstname'];
            $data['lastname'] = $_POST['lastname'];
            $data['totem'] = $_POST['totem'];
            $data['mail'] = $_POST['mail'];
            $data['phone'] = $_POST['phone'];

            if (isset($_POST['tak'])) {
                $data['tak'] = $_POST['tak'];
            }

            if ($edit) {
                $data['permissions'] = array();
                
                foreach ($allPermissions as $code => $name) {
                    if (isset($_POST['permission_'.$code])) {
                        $data['permissions'][] = $code;
                    }
                }
            }

            // Nu één voor één controleren
            $errors = $user->setProperties($data, $edit);

            if (count($errors) == 0) {
                if ($user->save()) {
                    $success = true;
                
                    if ($edit) {
                        header("Location: https://".$_SERVER['SERVER_NAME']."/admin/leiding");
                    }
                } else {
                    $errors[] = 'Probleem bij opslaan';
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
                        $functies[] = 'Leiding ('.$user->tak.')';
                    }
                    break;
               
                default :
                    $functies[] = $allPermissions[$permission];
                break;
            }
        }

        return Template::render('leiding/gegevens', array(
            'edit' => $edit,
            'new' => $new,
            'leiding' => $data,
            'functies' => $functies,
            'permissions' => $permission_data,
            'errors' => $errors,
            'takken' => Leiding::$takken,
            'success' => $success,
            'id' => $user->id
        ));
    }
}