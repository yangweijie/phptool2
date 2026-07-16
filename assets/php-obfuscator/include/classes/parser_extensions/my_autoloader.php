<?php declare(strict_types=1);

namespace PhpParser;
class Autoloader
{
    
    private static $registered = false;

    
    public static function register(bool $prepend = false) {
        if (self::$registered === true) {
            return;
        }

        spl_autoload_register([__CLASS__, 'autoload'], true, $prepend);
        self::$registered = true;
    }

    
    public static function autoload(string $class) {
        if (0 === strpos($class, 'PhpParser\\')) {
            global $yakpro_po_dirname;
            $fileName = $yakpro_po_dirname.'/'.PHP_PARSER_DIRECTORY.'/lib/'.strtr($class,'\\','/').'.php';
            if (file_exists($fileName)) {
                require $fileName;
            }
        }
    }
}

Autoloader::register();