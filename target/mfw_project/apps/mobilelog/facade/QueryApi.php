<?php

namespace apps\mobilelog;

class MFacade_QueryApi
{
    public static function AGetList($start, $end, $mddId = null) {
        $sql = new \Ko_Tool_SQL();
        $sql->oWhere('date >= ? and date <= ? and host like ?', $start, $end, "%mafengwo.net%")->oForceInactive(true);
        if ($mddId) {
            $sql->oAnd('mdd_id = ?', $mddId);
        }
        $list = \apps\mobilelog\cdn\MDao::CdnLogCountDao()->aGetList($sql);
        return $list;
    }
}
