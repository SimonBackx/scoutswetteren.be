from fabric.api import *
from fabric.contrib.files import exists
from fabric.state import output
from fabric.contrib.project import rsync_project
from fabric.contrib.console import confirm
import yaml

output['running'] = False

try:
    stream = file('settings.yml', 'r')    # 'document.yaml' contains a single YAML document.
except IOError:
    print("Configfile settings.yml doesn\'t exist.")
    exit()

config = yaml.load(stream)

try:
    t = config["folder"]
    t = config["e-mail"]
except:
    print "Couldn't read settings.yml, wrong format"
    exit()

try:
    s = config["sass"]
    sass_enabled = True
except:
    sass_enabled = False

try:
    env.hosts = [config["server"]]
except:
    pass

local("ssh-add");

def mysql():
    try:
        with settings(hide('warnings', 'running', 'stdout'), warn_only=True):
            run('mysqladmin -u %s -p%s create %s' % (config["mysql"]["username"], config["mysql"]["password"], config["mysql"]["database"]))
        print "[MYSQL] Created mysql user"
    except:
        pass

def watchSass():
    if sass_enabled:
        local("sass --watch "+config["sass"]+" --scss --sourcemap=none --style=compressed")
    else:
        print("[SASS] SASS is not enabled, check settings.yml")

def compileSass():
    if sass_enabled:
        print("[SASS] Compiling sass...")
        local("sass "+config["sass"]+" --scss --sourcemap=none --style=compressed")
        print("[SASS] Done.")

def removeCustomMaintenance():
    print('[NGINX] Removing custom maintenance configuration...')

    with settings(hide('warnings', 'running', 'stdout', 'stderr'), warn_only=True):
        run("rm /etc/nginx/sites-enabled/"+config["folder"]+".maintenance.conf")
        run("rm /etc/nginx/sites-available/"+config["folder"]+".maintenance.conf")

def nginx():
    removeCustomMaintenance()

    print("[NGINX] Uploading and enabling configuration file /etc/nginx/sites-available/"+config["folder"]+".conf")
    put("nginx.production.conf", "/etc/nginx/sites-available/"+config["folder"]+".conf")
    run("ln -sf /etc/nginx/sites-available/"+config["folder"]+".conf /etc/nginx/sites-enabled/")
    run("service nginx reload")
    print ("[NGINX] Done. Nginx reloaded.")

def nginxMaintenance():
    print('[NGINX] Switching website to maintenance mode...')
    print('[NGINX] Removing current configuration...')

    with settings(hide('warnings', 'running', 'stdout', 'stderr'), warn_only=True):
        run("rm /etc/nginx/sites-enabled/"+config["folder"]+".conf")

    print('[NGINX] Adding default maintenance file... (nginx.maintenance.default.conf)')
    put("nginx.maintenance.default.conf", "/etc/nginx/sites-available/maintenance.default.conf")
    run("ln -sf /etc/nginx/sites-available/maintenance.default.conf /etc/nginx/sites-enabled/")
    
    print('[NGINX] Adding custom maintenance file...(nginx.maintenance.conf)')
    put("nginx.maintenance.conf", "/etc/nginx/sites-available/"+config["folder"]+".maintenance.conf")
    run("ln -sf /etc/nginx/sites-available/"+config["folder"]+".maintenance.conf /etc/nginx/sites-enabled/")

    print('[NGINX] Creating maintenance root in /var/www/maintenance')
    with settings(hide('warnings', 'running', 'stdout', 'stderr'), warn_only=True):
        run("mkdir /var/www/maintenance")
    
    with settings(hide('warnings', 'running', 'stdout', 'stderr'), warn_only=True):
        result = run("service nginx reload")
        if result.return_code == 0: 
            print ("[NGINX] Done. Nginx reloaded.")
            return
        else: #print error to user
            pass

    print("[NGINX] [WARNING] Something is wrong with the custom maintenance file")
    print("[NGINX] Quick guess: SSL certificates not yet generated, no problem!")
    print("YOU SHOULD NOT HAVE THIS ERROR IF YOU ALREADY GENERATED CERTIFICATES.")
    
    if not confirm("Do you want to enable maintenance mode and continue without a working HTTPS website? (ignore only on first deployment)", False):
        if confirm("Want to take a look to nginx.maintenance.conf?"):
            print "Fix the problem and retry"
            local("subl nginx.maintenance.conf")
        exit()

    removeCustomMaintenance()

    with settings(hide('warnings', 'running', 'stdout'), warn_only=True):
        result = run("service nginx reload")
        if result.return_code == 0: 
            print("[NGINX] Done. Nginx reloaded.")
            return
        else: #print error to user
            pass

        
    print ("[NGINX] Error. Something went wrong with the default maintenance configuration file.")
    run("systemctl status nginx")
    exit()

def uploadApp():
    print("[UPLOAD] Uploading app files...")
    uploading_directory = "/var/www/"+config["folder"]
    run("mkdir -p "+uploading_directory)
    run("mkdir -p "+uploading_directory+"/pirate")
    run("mkdir -p "+uploading_directory+"/public")
    
    with settings(hide('warnings', 'running', 'stdout')):
        rsync_project(remote_dir= uploading_directory+"/pirate", local_dir= "pirate/", delete= True)
        rsync_project(remote_dir= uploading_directory+"/public", local_dir= "public/", delete= True, extra_opts=" --chmod=a=rwx ")
    
    run("chown -R :www-data "+uploading_directory)
    print("[UPLOAD] Done.")

def letsencrypt():
    print("[LETSENCRYPT] Configuring letsencrypt...")

    with settings(hide('warnings', 'running', 'stdout')):
        run("sudo apt-get install letsencrypt")
    directory = "/var/www/maintenance"
    
    try:
        domains = ""
        first_domain = config["letsencrypt"][0]

        for domain in config["letsencrypt"]:
            print "\t * "+domain
            domains += " -d "+domain
    except:
        print ("[LETSENCRYPT] Letsencrypt not set in settings.yml")
        return

    if domains == "":
        print "[LETSENCRYPT] No letsencrypt domains set"
        return

    
    print("[LETSENCRYPT] Renewing certificates if needed. Serving from "+directory+" for authentication.")

    with settings(hide('warnings', 'running')):
        run("letsencrypt certonly --keep-until-expiring --agree-tos --email "+config["e-mail"]+" --webroot -w "+directory+domains)

    print("[LETSENCRYPT] Done.")


def deploy():
    compileSass()
    print('--')
    nginxMaintenance()
    print('--')
    uploadApp()
    print('--')
    letsencrypt()
    #print('--')
    #mysql()
    print('--')
    nginx()
