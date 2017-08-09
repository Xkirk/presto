<?php

namespace apps\mobilelog\cdn;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'cdnLog' => array(
            'type' => 'db_split',
            'kind' => 'mobile_log_cdn',
            'key' => 'id',
            'split' => 'host',
            'issplitstring' => true
        ),
        'cdnLogCount' => array(
            'type' => 'db_single',
            'kind' => 'mobile_log_cdn_count',
            'key' => array(
                'mdd_id',
                'host',
                'date'
            )
        ),
    );

    public static function CdnLogDao()
    {
        return self::_GetDao('cdnLogDao');
    }

    public static function CdnLogCountDao()
    {
        return self::_GetDao('cdnLogCountDao');
    }

    private static function _GetDao($dao)
    {
        $factory = new MDao();
        return $factory->oGetDao($dao);
    }
}
