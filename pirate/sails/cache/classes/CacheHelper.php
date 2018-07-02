<?php
namespace Pirate\Classes\Cache;

class CacheHelper {
    
    // Default 1 hour cache
    static function set($key, $value, $ttl = 60*60) {
        $value = var_export($value, true);
        $value = str_replace('stdClass::__set_state', '(object)', $value);
        $valid_until = time() + $ttl;
        // Write to temp file first to ensure atomicity
        $tmp = __DIR__."/../tmp/$key." . uniqid('', true) . '.tmp';
        file_put_contents($tmp, '<?php $val = ' . $value . '; $valid_until = '.$valid_until.';', LOCK_EX);
        rename($tmp, __DIR__."/../tmp/$key");
    }

    static function get($key) {
        clearstatcache();
        if (!file_exists(__DIR__."/../tmp/$key")) {
            return null;
        }

        @include __DIR__."/../tmp/$key";
        if (isset($valid_until)) {
            if ($valid_until < time()) {
                // Invalid
                unlink(__DIR__."/../tmp/$key");
                return null;
            }
        }

        return isset($val) ? $val : null;
    }
}
