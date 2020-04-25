#!/bin/zsh

echo "Uploading app files...";


UPLOAD_DIRECTORY="/var/www/scoutssintjan.be"
PIRATE_CONFIG_LOCATION="docker/environments/scoutssintjan.dev"


ssh root@scoutswetteren.be "
    sudo ufw disable
    echo \"Creating folders in $UPLOAD_DIRECTORY\"
    mkdir -p $UPLOAD_DIRECTORY
    mkdir -p $UPLOAD_DIRECTORY/pirate
    mkdir -p $UPLOAD_DIRECTORY/public
"

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

#     run("chown -R www-data:www-data "+uploading_directory+"/files")
#     run("chown -R www-data:www-data "+uploading_directory+"/pirate/tmp")
#     run("chown -R www-data:www-data "+uploading_directory+"/pirate/sails/cache/tmp")
#     run("rm -rf "+uploading_directory+"/pirate/sails/cache/tmp/*")



# print("[UPLOAD] Uploading app files...")
#     uploading_directory = "/var/www/"+config["folder"]
#     run("mkdir -p "+uploading_directory)
#     run("mkdir -p "+uploading_directory+"/pirate")
#     run("mkdir -p "+uploading_directory+"/public")
#     
#     with settings(hide('warnings', 'running', 'stdout')):
#         rsync_project(remote_dir= uploading_directory+"/pirate", local_dir= "pirate/", delete= True)
#         rsync_project(remote_dir= uploading_directory+"/pirate/config.php", local_dir= config["pirate-config-location"]+"/config.php", delete= False)
#         rsync_project(remote_dir= uploading_directory+"/pirate/config.private.php", local_dir= config["pirate-config-location"]+"/config.private.php", delete= False)
#         rsync_project(remote_dir= uploading_directory+"/public", local_dir= "public/", delete= True)
#     
#     run("chown -R :www-data "+uploading_directory)
#     run("chown -R www-data:www-data "+uploading_directory+"/files")
#     run("chown -R www-data:www-data "+uploading_directory+"/pirate/tmp")
#     run("chown -R www-data:www-data "+uploading_directory+"/pirate/sails/cache/tmp")
#     run("rm -rf "+uploading_directory+"/pirate/sails/cache/tmp/*")
#     print("[UPLOAD] Done.")