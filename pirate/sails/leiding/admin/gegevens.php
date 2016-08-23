<?php
namespace Pirate\Sail\Leiding\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

class Gegevens extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $permissions = Leiding::getPermissions();
        $user = Leiding::getUser();

        $errors = array();
        $success = false;

        $data = array(
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'totem' => $user->totem,
            'mail' => $user->mail,
            'phone' => $user->phone,
        );

        if (isset($_POST['firstname'], $_POST['lastname'], $_POST['totem'], $_POST['mail'], $_POST['phone'])) {
            $data['firstname'] = $_POST['firstname'];
            $data['lastname'] = $_POST['lastname'];
            $data['totem'] = $_POST['totem'];
            $data['mail'] = $_POST['mail'];
            $data['phone'] = $_POST['phone'];

            // Nu één voor één controleren
            $errors = $user->setProperties($data);

            if (count($errors) == 0) {
                if ($user->save())
                    $success = true;
                else
                    $errors[] = 'Probleem bij opslaan';
            }
        }

        $functies = array();
        foreach ($permissions as $permission) {
            switch ($permission) {
                case 'leiding':
                    if (empty($user->tak)) {
                        $functies[] = 'Leiding';
                    } else {
                        $functies[] = 'Leiding ('.$user->tak.')';
                    }
                    break;
                
                case 'oudercomite':
                    $functies[] = 'Oudercomité';
                    break;

                case 'financieel':
                    $functies[] = 'Financieel verantwoordelijke';
                    break;

                default :
                    $functies[] = ucfirst($permission);
                break;
            }
        }

        return Template::render('leiding/gegevens', array(
            'leiding' => $data,
            'functies' => $functies,
            'errors' => $errors,
            'success' => $success
        ));
    }
}