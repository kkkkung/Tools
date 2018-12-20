<?php
/*
 * 工厂函数
 * 
 * 用以示方便的实例化其他类
 * 
 * 
 */
namespace app;

use app\Support\Str
/**
 * Class Factory.
 *
 * @method static \EasyWeChat\Payment\Application            payment(array $config)
 * @method static \EasyWeChat\MiniProgram\Application        miniProgram(array $config)

 */
class Factory
{
    /**
     * @param string $name
     * @param array  $config
     *
     * @return new class
     */
    public static function make($name, array $config)
    {
        $namespace = Str::studly($name);
        $classDir = "\\Path\\{$namespace}\\UnifiedClassName";
        return new $class($config);
    }
    /**
     * Dynamically pass methods to the application.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::make($name, ...$arguments);
    }
}