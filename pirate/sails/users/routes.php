<?php
namespace Pirate\Sails\Users;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;
use Pirate\Sails\Users\Models\User;

class UsersRouter extends Route {
    function doMatch($url, $parts) {
        if ($match = $this->match($parts, '/gebruikers/wachtwoord-kiezen/@key', ['key' => 'string'])) {        
            if (User::temporaryLoginWithPasswordKey($match->params->key)) {        
                $this->setPage(new Pages\SetPassword());
                return true;
            }
            return false;
        }

        if ($match = $this->match($parts, '/gebruikers/wachtwoord-vergeten/@key', ['key' => 'string'])) {        
            if (User::temporaryLoginWithPasswordKey($match->params->key)) {        
                $this->setPage(new Pages\SetPassword());
                return true;
            }
            return false;
        }

        if ($match = $this->match($parts, '/gebruikers/wachtwoord-vergeten')) {        
            $this->setPage(new Pages\WachtwoordVergeten());
            return true;
        }

        if ($match = $this->match($parts, '/gebruikers/login/@mail/@key', ['mail' => 'string', 'key' => 'string'])) {        
            if (User::loginWithMagicToken($match->params->mail, $match->params->key)) {
                $this->setPage(new Pages\MagicLink());
                return true;
            }
            return false;
        }

        if (User::isLoggedIn()) {
            if ($match = $this->match($parts, '/gebruikers/logout')) {        
                $this->setPage(new Pages\Logout());
                return true;
            }
    
            if ($match = $this->match($parts, '/gebruikers/wachtwoord-wijzigen')) {        
                $this->setPage(new Pages\WachtwoordWijzigen());
                return true;
            }        
        } else {
            if ($match = $this->match($parts, '/gebruikers/login')) {        
                $this->setPage(new Pages\Login());
                return true;
            }
    
            if ($match = $this->match($parts, '/gebruikers/registreren')) {        
                $this->setPage(new Pages\Registreren());
                return true;
            }
        }

        return false;
    }
}