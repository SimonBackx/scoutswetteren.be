<?php
namespace Pirate\Model\Migrations;
use Pirate\Model\Model;

class Migration extends Model {
    public $id;
    public $executed_at;

    function __construct($row) {
        $this->id = $row['migration_id'];
        $this->executed_at = new \DateTime($row['migration_executed_at']);
    }

    /**
     * Slaat op dat een bepaalde migration gelukt is
     * 
     */
    static function create($migration_id) {
        $id = self::getDb()->escape_string($migration_id);
        $date = new \DateTime();;
        $date_raw = $date->format('Y-m-d H:i:s');
        $executed_at = self::getDb()->escape_string($date_raw);

        $query = "INSERT INTO 
            migrations (`migration_id`, `migration_executed_at`)
            VALUES ('$id', '$executed_at')";

        if (self::getDb()->query($query)) {
            return new Migration([
                'migration_id' => $migration_id,
                'migration_executed_at' => $date_raw,
            ]);
        }

        return null;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                migrations WHERE migration_id = '$id' ";

        if (self::getDb()->query($query)) {
            return true;
        }
        return false;
    }

    // Doorloop alle migrations die er zijn
    static function upgrade() {
        $executed = static::getExecutedMigrations();
        $migrations = include __DIR__.'/../../_bindings/migrations.php';
        $to_execute = [];
        
        foreach ($migrations as $migration) {
            $id = $migration->id;

            if (!isset($executed[$id])) {
                $to_execute[$migration->timestamp] = $migration;
            }
        }

        ksort($to_execute);

        foreach ($to_execute as $key => $migration) {
            require_once($migration->path);
            $className = '\Pirate\Classes\Migrations\\'.$migration->class;
            echo "Runing migration $className...\n";
            try {
                if ($className::upgrade()) {
                    static::create($migration->id);
                    echo "Succeeded $className\n\n";
                    continue;
                }
            } catch (Exception $ex) {
                echo $ex->getMessage()."\n";
            }
            echo "Failed $className\n\n";
            return false;
        }

        return true;
    }

    static function getExecutedMigrations() {
        $query = 'SELECT * FROM migrations';
        if ($result = self::getDb()->query($query)) {
            $migrations = [];

            while ($row = $result->fetch_assoc()) {
                $m = new Migration($row);
                $migrations[$m->id] = $m;
            }
            return $migrations;
        }

        return [];
    }

}
