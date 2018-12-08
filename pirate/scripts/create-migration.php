<?php
include __DIR__.'/../wheel/config.php';
include __DIR__.'/../wheel/functions.php';

// Usage php create-migration.php SAIL MIGRATION-NAME
if (!isset($argv[2])) {
    echo "Usage: php create-migration.php SAIL MIGRATION-NAME\n";
    return;
}

$sail = strtolower($argv[1]);
$name = strtolower($argv[2]);

$sails = include __DIR__.'/../sails/_bindings/sails.php';

if (!in_array($sail, $sails)) {
    echo "Unknown sail $sail\n\n";
    return;
}

$timestamp = time();

$location = realpath(__DIR__."/../sails/$sail")."/migrations/$timestamp.$name.php";

if (!file_exists(dirname($location))) {
    mkdir(dirname($location));
}

$classname = dashesToCamelCase($name, true).$timestamp;
$template = 
'<?php
namespace Pirate\Classes\\'.ucfirst($sail).';
use Pirate\Classes\Migrations\Migration;

class '.$classname.' extends Migration {

    static function upgrade(): bool {
        throw new \Exception("Migration upgrade is not implemented");
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}';

file_put_contents($location, $template);