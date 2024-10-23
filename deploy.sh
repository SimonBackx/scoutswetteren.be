#!/bin/zsh

echo "Uploading app files...";


UPLOAD_DIRECTORY="/var/www/scoutswetteren.be"
PIRATE_CONFIG_LOCATION="docker/environments/scoutswetteren.dev"


ssh root@scoutswetteren.be "
    sudo ufw disable
    echo \"Creating folders in $UPLOAD_DIRECTORY\"
    mkdir -p $UPLOAD_DIRECTORY
    mkdir -p $UPLOAD_DIRECTORY/pirate
    mkdir -p $UPLOAD_DIRECTORY/public
"
# Files
rsync -zr --no-perms --info=progress2 "files/scoutswetteren.be/" root@scoutswetteren.be:"$UPLOAD_DIRECTORY/files"

rsync -zr --no-perms --info=progress2 --delete "pirate/" root@scoutswetteren.be:"$UPLOAD_DIRECTORY/pirate"
rsync -zr --no-perms --info=progress2 "$PIRATE_CONFIG_LOCATION/config.php" root@scoutswetteren.be:"$UPLOAD_DIRECTORY/pirate/config.php"
rsync -zr --no-perms --info=progress2 "$PIRATE_CONFIG_LOCATION/config.private.php" root@scoutswetteren.be:"$UPLOAD_DIRECTORY/pirate/config.private.php"
rsync -zr --no-perms --info=progress2 --delete "public/" root@scoutswetteren.be:"$UPLOAD_DIRECTORY/public"


echo "Fixing permissions..."



ssh root@scoutswetteren.be "
    chown -R :www-data $UPLOAD_DIRECTORY
    chown -R www-data:www-data $UPLOAD_DIRECTORY/files
    chown -R www-data:www-data $UPLOAD_DIRECTORY/pirate
    chown -R www-data:www-data $UPLOAD_DIRECTORY/pirate/sails/cache/tmp
    rm -rf $UPLOAD_DIRECTORY/pirate/sails/cache/tmp/*

    echo \"Updaging pirate...\";
    php \"$UPLOAD_DIRECTORY/pirate/run/update.php\"
    sudo ufw --force enable
"


echo "Done.";
