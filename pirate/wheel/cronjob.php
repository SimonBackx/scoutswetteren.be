<?php
namespace Pirate\Cronjob;

class Cronjob {
    function needsRunning() {

    }

    function run() {

    }
}   

class Cronjobs {

    function run() {
        // 10 minuten tijd
        set_time_limit(600);

        include(__DIR__.'/../sails/_bindings/cronjobs.php');
        if (!isset($cronjobs)) {
            echo "Cronjobs not found\n";
            return false;
        }

        foreach ($cronjobs as $module => $crons) {
            $ucfirst_module = ucfirst($module);

            foreach ($crons as $name => $interval) {
                require(__DIR__."/../sails/$module/cronjobs/$name.php");
                $classname = "\\Pirate\\Cronjob\\$ucfirst_module\\".Self::dashesToCamelCase($name, true);

                $cron = new $classname();
                
                if ($cron->needsRunning()) {
                    $cron->run();
                }
            }
           
        }

        echo "Cronjobs finished\n";

    }
    
    static function dashesToCamelCase($string, $capitalizeFirstCharacter = false) {

        $str = str_replace('-', '', ucwords($string, '-'));

        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }

    static function install() {
        $dir = '/usr/bin/php '.realpath(__DIR__.'/../run/cronjobs.php');
        // 10 seconden: OnCalendar=*:*:0/10
        // 15 minuten: OnCalendar=*:0/15
        $timer = 
"[Unit]
Description=Pirate CMS Cronjob timer

[Timer]
OnUnitActiveSec=60s
OnBootSec=60s

[Install]
WantedBy=timers.target";

        $service = 
"[Unit]
Description=Pirate CMS Cronjobs

[Service]
Type=oneshot
ExecStart=$dir";

        // Opslaan in /etc/systemd/system
        file_put_contents('/etc/systemd/system/pirate.service', $service);
        file_put_contents('/etc/systemd/system/pirate.timer', $timer);
        
        exec("systemctl daemon-reload");

        exec("systemctl enable pirate.timer");

        exec("systemctl start pirate.timer");
    
        return true;
    }
}