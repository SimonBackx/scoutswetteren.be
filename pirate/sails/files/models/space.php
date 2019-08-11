<?php
namespace Pirate\Sails\Files\Models;
use Pirate\Wheel\Model;

class Space {
    public $name;
    public $key;
    public $secret;
    public $region;
    public $server;

    function __construct($name, $region, $server, $key, $secret) {
        $this->name = $name;
        $this->region = $region;
        $this->server = $server;

        $this->key = $key;
        $this->secret = $secret;
    }

    static function getDefault() {
        return new Space('scouts', 'ams3', 'digitaloceanspaces.com', 'TAG2ST7BTCVHZT7MT7QM', 'V3tNuv6wAOwAacXd553aD+XjAIMt66gEDmBl228T2is');
    }

    function getURL() {
        return 'https://'.$this->getHost();
    }

    function getHost() {
        return $this->name.'.'.$this->region.'.'.$this->server;
    }
}

class SpaceResponse {
    public $success;
    public $http_statuscode;
    public $body;

    function __construct($success = false, $http_statuscode = 0, $body = '') {
        $this->success = $success;
        $this->http_statuscode = $http_statuscode;
        $this->body = $body;
    }
}

class SpaceRequest {
    private $method;
    private $headers; // excl host
    
    private $host;
    private $space;

    private $path;
    private $querystring;

    private $body;
    private $body_file;

    private $hashed_body = null;

    private $datetime;

    function __construct($method, $space, $path = '/') {
        $this->space = $space;
        $this->host = $space->getHost();

        // Method uppercasen!
        $this->method = strtoupper($method);
        $this->headers = array();

        // Path moet met / beginnen!
        $this->path = $path;
        $this->querystring = '';
        // Todo: remove query string from path
        // 
        
        $this->datetime = new \DateTime();
        $this->datetime->setTimezone(new \DateTimeZone("UTC"));
    }

    // Stel de headers in, met uitzondering van de Host, Content-Length en Authorization headers
    function setHeaders($headers) {
        $this->headers = $headers;

    }

    // Enkel voor put requests!
    function setFile($path) {
        $this->method = 'PUT';

        // Todo: saferty check path to be in uploads
        // Please take note that hash-file will throw error on files >=2GB.
        // Misschien hashing van files vervangen door een system command?
        $this->body_file = $path;
        $this->body = null;
        
        // Hash the file
        $this->hashed_body = hash_file('sha256', $this->body_file);
    }

    function setText($text) {
        $this->body = $text;
        $this->body_file = null;
        
        // hash 
        $this->hashed_body = hash('sha256', $this->body);
    }

    static function getMIMETypes() {
        return array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/plain',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',

            // images
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'tar' => 'application/x-tar',
            '7z' => 'application/x-7z-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'mpeg' => 'audio/mpeg',
            'mov' => 'video/quicktime',
            'mp4' => 'video/mp4',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

            // macOS
            'pages' => 'application/x-iwork-pages-sffpages',
            'numbers' => 'application/x-iwork-numbers-sffnumbers',
            'keynote' => 'application/x-iwork-keynote-sffkey',
        );
    }

    // Return alle headers die verzonden moeten worden met uitzondering van de Authorization header
    function getHeaders() {
        // Todo: cachen!
        
        $arr = array(
            'Host' => $this->host,
            'x-amz-content-sha256' => $this->getHashedPayload(),
            'x-amz-date' => $this->getISO8601Date(),
            // Content-Type
            // Cache-Control
            // Content-Encoding
            // todo: content type bij file bepalen hierzo -> ofwel manueel? of op basis van file extension?
        );

        if (isset($this->body_file)) {
            $arr['Content-Length'] = filesize($this->body_file);
            $ext = strtolower(substr(strrchr($this->body_file,'.'),1));
            $types = Self::getMIMETypes();

            foreach ($types as $_ext => $mime) {
                if ($ext == $_ext) {
                    $arr['Content-Type'] = $mime;
                    break;
                }
            }
        } else {
            $arr['Content-Length'] = strlen($this->body);
            $arr['Content-Type'] = 'text/plain';
        }

        $arr = array_merge($arr, $this->headers);

        // Alfabetisch sorteren (niet case sensitive!)
        ksort($arr, SORT_STRING | SORT_FLAG_CASE);

        return $arr;
    }

    /**
     *  Parts of the canonical request
     */

    function getCanonicalHeaders() {
        // The list of request headers and their values, newline separated, lower-cased, and trimmed of whitespace.
        $headers = $this->getHeaders();
        
        $canonicalHeaders = '';

        $first = true;
        foreach ($headers as $key => $value) {
            if (!$first) {
                $canonicalHeaders .= "\n";
            } else {
                $first = false;
            }

           $canonicalHeaders .= strtolower($key).':'.trim(str_replace('  ', ' ', $value));
        }


        return $canonicalHeaders."\n";
    }

    function getSignedHeaders() {
        // The list of header names without their values, sorted alphabetically, lower-cased, and semicolon-separated.
        $headers = $this->getHeaders();
        $signedHeaders = '';

        $first = true;
        foreach ($headers as $key => $value) {
            if (!$first) {
                $signedHeaders .= ';';
            } else {
                $first = false;
            }

            $signedHeaders .= strtolower($key);
        }

        return $signedHeaders;
    }

    function getDate() {
        return $this->datetime->format('Ymd');
    }

    function getISO8601Date() {
        $current = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $v = $this->datetime->format('Ymd\THis\Z');
        date_default_timezone_set($current);
        return $v;
    }

    function getHashedPayload() {
        // The SHA256 hash of the request body.
        return $this->hashed_body;
    }

    function getCanonicalURI() {
        // The path component of the request URI.
        return $this->path;
    }

    function getCanonicalQueryString() {
        /* Sort the parameter names by character code point in ascending order. For example, a parameter name that begins with the uppercase letter F precedes a parameter name that begins with a lowercase letter b.

        URI-encode each parameter name and value according to the following rules:

        Do not URI-encode any of the unreserved characters that RFC 3986 defines: A-Z, a-z, 0-9, hyphen ( - ), underscore ( _ ), period ( . ), and tilde ( ~ ).
        Percent-encode all other characters with %XY, where X and Y are hexadecimal characters (0-9 and uppercase A-F). For example, the space character must be encoded as %20 (not using '+', as some encoding schemes do) and extended UTF-8 characters must be in the form %XY%ZA%BC.
        Build the canonical query string by starting with the first parameter name in the sorted list.

        For each parameter, append the URI-encoded parameter name, followed by the equals sign character (=), followed by the URI-encoded parameter value. Use an empty string for parameters that have no value.

        Append the ampersand character (&) after each parameter value, except for the last value in the list.*/
        return $this->querystring;
    }

    function getCanonicalRequest() {
        $data = $this->method."\n".$this->getCanonicalURI()."\n".$this->getCanonicalQueryString()."\n".$this->getCanonicalHeaders()."\n".$this->getSignedHeaders()."\n".$this->getHashedPayload();
        return $data;
    }

    /**
     * Authorization
     */
    function getSignature() {
        $stringToSign = "AWS4-HMAC-SHA256" . "\n" . $this->getISO8601Date() . "\n" . $this->getDate() . "/" . $this->space->region . "/s3/aws4_request" . "\n" .
            hash('sha256', $this->getCanonicalRequest());

        $dateKey =              hash_hmac('sha256', $this->getDate(),       "AWS4" . $this->space->secret,  true);
        $dateRegionKey =        hash_hmac('sha256', $this->space->region,   $dateKey,                       true);
        $dateRegionServiceKey = hash_hmac('sha256', "s3",                   $dateRegionKey,                 true);
        $signingKey =           hash_hmac('sha256', "aws4_request",         $dateRegionServiceKey,          true);

        return hash_hmac('sha256', $stringToSign, $signingKey, false);
    }

    function getAuthorizationHeader() {
        return 'AWS4-HMAC-SHA256 Credential='.$this->space->key.'/'.$this->getDate().'/'.$this->space->region.'/s3/aws4_request, SignedHeaders='.$this->getSignedHeaders().', Signature='.$this->getSignature();
    }

    function send() {

        $headers = $this->getHeaders();
        $headers['Authorization'] = $this->getAuthorizationHeader();

        $curl_headers = array();
        foreach ($headers as $key => $value) {
            $curl_headers[] = $key.': '.$value;
        }

        // Remove accept header:
        $curl_headers[] = 'Accept:';

        $options = array(
            //CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $curl_headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLINFO_HEADER_OUT => true,

            // temp for testing!
            CURLOPT_SSL_VERIFYPEER => true,
        );

        if (isset($this->body_file)) {
            $fp = fopen($this->body_file, 'rb');
            $options[CURLOPT_PUT] = 1; // Anders werkt uploaden niet, helaas
            $options[CURLOPT_INFILE] = $fp;
            $options[CURLOPT_INFILESIZE] = filesize($this->body_file);
        } else {
            $options[CURLOPT_POSTFIELDS] = $this->body;
        }


        try {
            $curl = curl_init($this->space->getURL().$this->path);
            curl_setopt_array($curl, $options);

            $result = curl_exec($curl);
            
            if ($result === false) {
                return new SpaceResponse(false, 0, curl_error($curl));
            }

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
            /*echo '<pre>';
            echo $headers;
            echo $this->body;
            echo '</pre>';*/

            curl_close($curl);
            return new SpaceResponse(true, $status, $result);
        }
        catch (Exception $e) {
            return new SpaceResponse(false, $status, $e->getMessage());
        }
        return new SpaceResponse(false, $status, '???');
    }

}
?>