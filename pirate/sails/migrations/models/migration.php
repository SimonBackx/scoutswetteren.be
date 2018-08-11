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

    static function isUpToDate() {
        $executed = static::getExecutedMigrations();
        $migrations = include __DIR__.'/../../_bindings/migrations.php';
        $to_execute = [];
        
        foreach ($migrations as $migration) {
            $id = $migration->id;

            if (!isset($executed[$id])) {
                $to_execute[$migration->timestamp] = $migration;
            }
        }
        return count($to_execute) == 0;
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

        $first = null;

        foreach ($to_execute as $key => $migration) {
            
            require_once($migration->path);
            $className = '\Pirate\Classes\\'.ucfirst($migration->sail).'\\'.$migration->class;
            echo "Runing migration $className...\n";

            if (!self::getDb()->begin_transaction()) {
                echo "Failed to create transaction\n\n";
                return false;
            }

            try {
                if ($className::upgrade()) {
                    static::create($migration->id);

                    if (!self::getDb()->commit()) {
                        echo "Failed to rollback transaction\n\n";
                        throw new \Exception("Failed to rollback transaction");
                    } else {
                        echo "Successfully commited database changes\n\n";
                    }

                    echo "Succeeded $className\n\n";

                    if (!isset($first)) {
                        $first = $migration->id;
                    }


                    continue;
                }
            } catch (\Exception $ex) {
                echo $ex->getMessage()."\n";
            }
            echo "Failed $className\n\n";

            if (!self::getDb()->rollback()) {
                echo "Failed to rollback transaction\n\n";
            } else {
                echo "Successfully rollbacked database changes\n\n";
            }

            // Alle andere migraties ongedaan maken tot en met first
            if (isset($first)) {
                static::downgrade(null, $first);
            }
            return false;
        }

        return true;
    }

    // Doorloop alle migrations die er zijn
    static function downgrade($untilDatetime = null, $untilMigration = null) {
        $executed = static::getExecutedMigrations();
        $migrations = include __DIR__.'/../../_bindings/migrations.php';
        $to_execute = [];

        $found = false;
        
        foreach ($migrations as $migration) {
            $id = $migration->id;

            if (isset($executed[$id]) && (!isset($until) || $executed[$id]->executed_at >= $untilDatetime)) {
                if (isset($untilMigration) && $untilMigration == $id) {
                    $found = true;
                }
                $to_execute[$migration->timestamp] = $migration;
            }
        }

        if (isset($untilMigration) && !$found) {
            return true;
        }

        krsort($to_execute);

        foreach ($to_execute as $key => $migration) {
            require_once($migration->path);
            $className = '\Pirate\Classes\\'.ucfirst($migration->sail).'\\'.$migration->class;
            echo "Downgrading migration $className...\n";
            try {
                if ($className::downgrade()) {
                    $model = $executed[$migration->id];
                    $model->delete();
                    echo "Succeeded $className\n\n";

                    if (isset($untilMigration) && $untilMigration == $migration->id) {
                        // Laatste
                        break;
                    }
                    continue;
                }
            } catch (\Exception $ex) {
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
