<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf06000f61a7631363ad613bde64d3c3d
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Palasthotel\\WordPress\\UseMemcached\\' => 35,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Palasthotel\\WordPress\\UseMemcached\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Palasthotel\\WordPress\\UseMemcached\\AdminBar' => __DIR__ . '/../..' . '/classes/AdminBar.inc',
        'Palasthotel\\WordPress\\UseMemcached\\AdminNotices' => __DIR__ . '/../..' . '/classes/AdminNotices.inc',
        'Palasthotel\\WordPress\\UseMemcached\\Ajax' => __DIR__ . '/../..' . '/classes/Ajax.inc',
        'Palasthotel\\WordPress\\UseMemcached\\Assets' => __DIR__ . '/../..' . '/classes/Assets.inc',
        'Palasthotel\\WordPress\\UseMemcached\\Memcache' => __DIR__ . '/../..' . '/classes/Memcache.inc',
        'Palasthotel\\WordPress\\UseMemcached\\ObjectCacheFileHandler' => __DIR__ . '/../..' . '/classes/ObjectCacheFileHandler.inc',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf06000f61a7631363ad613bde64d3c3d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf06000f61a7631363ad613bde64d3c3d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf06000f61a7631363ad613bde64d3c3d::$classMap;

        }, null, ClassLoader::class);
    }
}
