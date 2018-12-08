<?php
namespace Pirate\Sail\Users;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Users\User;

class UsersRouter extends Route {
    function doMatch($url, $parts) {
        if ($match = $this->match($parts, '/gebruikers/account-aanmaken/@key', ['key' => 'string'])) {        
            if (User::temporaryLoginWithPasswordKey($match->params->key)) {        
                $this->setPage(new Pages\SetPassword());
                return true;
            }
            return false;
        }

        if ($match = $this->match($parts, '/gebruikers/wachtwoord-vergeten/@key', ['key' => 'string'])) {        
            if (User::temporaryLoginWithPasswordKey($match->params->key)) {        
                $this->setPage(new Pages\WachtwoordVergeten());
                return true;
            }
            return false;
        }

        if ($match = $this->match($parts, '/gebruikers/login')) {        
            $this->setPage(new Pages\Login());
            return true;
        }

        if ($match = $this->match($parts, '/gebruikers/login/@mail/@key', ['mail' => 'string', 'key' => 'string'])) {        
            if (User::loginWithMagicToken($match->params->mail, $match->params->key)) {
                $this->setPage(new Pages\MagicLink());
                return true;
            }
            return false;
        }

        if ($match = $this->match($parts, '/gebruikers/logout')) {        
            $this->setPage(new Pages\Logout());
            return true;
        }

        return false;
    }
}