<?php

namespace apps\xauth;

class MFacade_Api
{
    private static $s_obj = null;

    public static function aGet($sToken)
    {
        $obj = self::_oGetObj();
        return $obj->get($sToken);
    }

    public static function aGetByUid($iUid, $iCid)
    {
        $obj = self::_oGetObj();
        return $obj->getByUid($iUid,$iCid);
    }

    public static function bIsPasswordChanged($sToken)
    {
        $obj = self::_oGetObj();
        return $obj->isPasswordChanged($sToken);
    }

    public static function vAdd($sToken, $sSecret, $iUid, $iCid)
    {
        $obj = self::_oGetObj();
        return $obj->add($sToken,$sSecret,$iUid,$iCid);
    }

    public static function vOnPasswordChanged($iUid)
    {
        $obj = self::_oGetObj();
        return $obj->onPasswordChanged($iUid);
    }

    private static function _oGetObj()
    {
        if (is_null(self::$s_obj))
        {
            self::$s_obj = new MpftokenApi();
        }
        return self::$s_obj;
    }
}