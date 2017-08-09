<?php
namespace apps\pay_core\esign;
class Mlib_BaseApi extends \Ko_Busi_Api
{
    private static $_callStaticCache = array();
    /**
     * @var \ReflectionMethod[]
     */
    private static $_refMethods = array();

    /**
     * 自动获取同namespace下的单例,也兼容Ko_Busi_Api,建议在类的phpdoc中增加如下注释
     * @ property <type> propertyName
     * 如: @ property MModel_Ota ModelOta
     * @example $this->Model_Ota
     * @example $this->Ota (需要在同目录下)
     * @param $sName
     * @return mixed
     */
    public function __get($sName)
    {

        $sCalledClass = get_called_class();
        $sNamespace = substr($sCalledClass, 0, strrpos($sCalledClass, '\\') - strlen($sCalledClass));
        $sFullClassName = $sNamespace .'\\M'.$sName;
        if(class_exists($sFullClassName)) {
            $this->$sName = $this->OInstance($sFullClassName);
            return $this->$sName;
        } else {
            return parent::__get($sName);
        }
    }

    /**
     * 自动获取同namespace下的单例,建议在类的phpdoc中增加如下注释
     *  @ method static <return type> oMethodName()
     * 如: @ method static MModel_Ota oModel_Ota()
     * @example $this->oModel_Ota()
     * @param $sName
     * @param $arguments
     * @return null
     */
    public static function __callStatic($sName, $arguments)
    {
        $sCalledClass = get_called_class();
        if(!isset(self::$_callStaticCache[$sCalledClass][$sName])) {
            $sNamespace = substr($sCalledClass, 0, strrpos($sCalledClass, '\\') - strlen($sCalledClass));
            $sFullClassName = $sNamespace.'\\M'.$sName;
            if(class_exists($sFullClassName)) {
                self::$_callStaticCache[$sCalledClass][$sName] = Mlib_BaseApi::OInstance($sFullClassName);
            } else {
                self::$_callStaticCache[$sCalledClass][$sName] = null;
            }
        }
        return self::$_callStaticCache[$sCalledClass][$sName];
    }
    /**
     * 获取一个类的单例实现
     * @param string $sClassName 类名
     * @return \stdClass
     */
    public static function OInstance($sClassName)
    {
        if (!isset(self::$_refMethods[__METHOD__])) {
            self::$_refMethods[__METHOD__] = new \ReflectionMethod('Ko_Tool_Singleton', 'OInstance');
        }
        $ret = self::$_refMethods[__METHOD__]->invoke(null, $sClassName);
        return $ret;
    }

}