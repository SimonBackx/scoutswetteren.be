<?php
namespace Pirate\Sail\Files;
use Pirate\Dependency\Dependency;
use Pirate\Model\Files\File;

class FilesDependencies extends Dependency {
    function check(&$output) {
        global $FILES_DIRECTORY;

        $error_reporting = error_reporting();
        //error_reporting(0);

        $size = $this->file_upload_max_size();
        if ($size < File::$max_size) {
            $output[] = array('success' => false, 'code' => 9, 'msg' => 'PHP.ini max upload / post size ('.(round($size/1048576*10)/10).' MiB) is smaller than allowed upload size ('.(round(File::$max_size/100000)/10) .' MB). Also check client_max_body_size on Nginx');
        } else {
            $output[] = array('success' => true, 'code' => 9, 'msg' => 'PHP.ini max upload / post size ('.(round($size/1048576*10)/10).' MiB) is >= allowed upload size ('.(round(File::$max_size/100000)/10).' MB). Also check client_max_body_size on Nginx.');
        }

        exec("zip -h", $o, $response);

        if ($response === 0) {  
            $output[] = array('success' => true, 'code' => 8, 'msg' => 'Zip is installed');
        } else {
            $output[] = array('success' => false, 'code' => 8, 'msg' => 'Zip is not installed');
        }

        if (!extension_loaded('imagick')) {
            $output[] = array('success' => false, 'code' => 0, 'msg' => 'Imagick not installed in php');
        } else {
            $output[] = array('success' => true, 'code' => 0, 'msg' => 'Imagick correctly installed in php');
        }

        // files.domain.com checken
        $domain = str_replace('www.','files.',$_SERVER['SERVER_NAME']);
        $records = dns_get_record($domain, DNS_A);
        $current_ip = $_SERVER['SERVER_ADDR'];
        $found = false;
        $found_ip = null;

        foreach ($records as $record) {
            $found_ip = $record['ip'];
            if ($record['ip'] == $current_ip) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            if (isset($found_ip)) {
                $output[] = array('success' => false, 'code' => 1, 'msg' => 'DNS records for '.$domain.' are not set correctly (found '.$found_ip.')');
            } else {
                $output[] = array('success' => false, 'code' => 1, 'msg' => 'DNS records for '.$domain.' are not set');

            }
        } else {
                $output[] = array('success' => true, 'code' => 1, 'msg' => 'DNS records for '.$domain.' are set to '.$found_ip);
        }

        if ($FILES_DIRECTORY !== false && is_dir($FILES_DIRECTORY)) {
            $output[] = array('success' => true, 'code' => 2, 'msg' => 'The files directory exists');
        

            // check upload a file
            $text = 'Hello world';
            $file_content = '<?php echo "'.$text.'"';
            $filename = 'files-dependency-test-'.time().'.php';
            if (file_put_contents($FILES_DIRECTORY.'/'.$filename, $file_content) === false) {
                // geen toegang tot schrijven
                $output[] = array('success' => false, 'code' => 3, 'msg' => 'The files directory has no write access');
            } else {
                // Toegang tot schrijven!
                $output[] = array('success' => true, 'code' => 3, 'msg' => 'The files directory has write access');
                
                // Testen of we deze pagina kunnen downloaden via files.domain.com
                $handle = curl_init('http://'.$domain.'/'.$filename);
                curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

                /* Get the HTML or whatever is linked in $url. */
                $response = curl_exec($handle);

                /* Check for 404 (file not found). */
                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                if($httpCode != 200 || $response === false) {
                    $output[] = array('success' => false, 'code' => 4, 'msg' => 'The files directory is not correctly linked to domain '.$domain);
                } else {
                    $output[] = array('success' => true, 'code' => 4, 'msg' => 'The files directory is linked to domain '.$domain);
                    
                    if ($response == $file_content) {
                        $output[] = array('success' => true, 'code' => 5, 'msg' => 'The files directory is protected against php files');
                    } else {
                        if ($response == $text) {
                            $output[] = array('success' => false, 'code' => 5, 'msg' => 'The files directory is not protected against php files');
                        } else {
                            $output[] = array('success' => false, 'code' => 5, 'msg' => 'Uploaded file not returned.');
                        }
                        
                    }

                }

                $port = curl_getinfo($handle, CURLINFO_PRIMARY_PORT);
                if ($port != 443) {
                    $output[] = array('success' => false, 'code' => 6, 'msg' => 'SSL redirection for '.$domain.' not set');
                } else {
                    $output[] = array('success' => true, 'code' => 6, 'msg' => 'SSL redirection for '.$domain.' set');
                }

                curl_close($handle);

                // Kijken of SSL certificaat oke is

                $handle = curl_init('https://'.$domain.'/'.$filename);
                curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);

                $response = curl_exec($handle);

                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                if ($response === false || $httpCode != 200) {
                    $output[] = array('success' => false, 'code' => 7, 'msg' => 'SSL certificate '.$domain.' not (correctly) set');
                } else {
                     $output[] = array('success' => true, 'code' => 7, 'msg' => 'SSL certificate '.$domain.' set');

                }

                curl_close($handle);

            }


            unlink($FILES_DIRECTORY.'/'.$filename);
        } else {
            $output[] = array('success' => false, 'code' => 2, 'msg' => 'The files directory doesn\'t exists');
        }

        error_reporting($error_reporting);
        return false;
    }

    function fix(&$errors) {
        // try to fix the dependencies
        // true on success
        // 
        $errors[] = 'Automatic fix impossible';
        return false;
    }

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    function file_upload_max_size() {
      static $max_size = -1;

      if ($max_size < 0) {
        // Start with post_max_size.
        $max_size = $this->parse_size(ini_get('post_max_size'));

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
          $max_size = $upload_max;
        }
      }
      return $max_size;
    }

    function parse_size($size) {
      $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
      $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
      if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
      }
      else {
        return round($size);
      }
    }
}