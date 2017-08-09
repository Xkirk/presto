<?php

namespace apps\gis;

class MFacade_Api
{

    const TYPE_GEOM_GADM    = \apps\gis\MApi::TYPE_GEOM_GADM;
    const TYPE_GEOM_SIMPLE  = \apps\gis\MApi::TYPE_GEOM_SIMPLE;
    const TYPE_GEOM_GAODE   = \apps\gis\MApi::TYPE_GEOM_GAODE;

    public static function getGadmByKeys($keys, $need_geom = 0, $limit = 10)
    {
        $_api = new MApi();
        return $_api->getGadmByKeys($keys, $need_geom, $limit);
    }

    public static function getGadmByGid($gid, $need_geom = 0)
    {
        $_api = new MApi();
        return $_api->getGadmByGid($gid, $need_geom);
    }

    public static function updateGadmByGid($gid, $data)
    {
        $_api = new MApi();
        return $_api->updateGadmByGid($gid, $data);
    }

    public static function getGadmByLatLng($lat, $lng, $geom_type = self::TYPE_GEOM_GADM)
    {
        $_api = new MApi();
        return $_api->getGadmByLatLng($lat, $lng, $geom_type);
    }

    public static function getGadmByRegionId($region_id, $need_geom = 0)
    {
        $_api = new MApi();
        return $_api->getGadmByRegionId($region_id, $need_geom);
    }

    public static function getUnionByGids($gids, $geom_type = self::TYPE_GEOM_GADM)
    {
        $_api = new MApi();
        return $_api->getUnionByGids($gids, $geom_type);
    }

    public static function getDifferenceByGid($gid1, $gid2)
    {
        $_api = new MApi();
        return $_api->getDifferenceByGid($gid1, $gid2);
    }

    /*
    *  添加一条GADM数据
    *  $data = array (
    *    iso        => 'CHN',   // character varying(3)
    *    adm_level  => 9,       // smallint
    *    id_0       => 0,       // numeric(10,0)
    *    id_1       => 0,       // numeric(10,0)
    *    id_2       => 0,       // numeric(10,0)
    *    id_3       => 0,       // numeric(10,0)
    *    id_4       => 0,       // numeric(10,0)
    *    id_5       => 0,       // numeric(10,0)
    *    object_id  => 0,       // numeric(10,0)
    *    name_0     => 'name0', // character varying(150)
    *    name_1     => 'name1', // character varying(150)
    *    name_2     => 'name2', // character varying(150)
    *    name_3     => 'name3', // character varying(150)
    *    name_4     => 'name4', // character varying(150)
    *    name_5     => 'name5', // character varying(150)
    *    engtype    => '',          // character varying(50)
    *    varname    => '',          // character varying(150)
    *    nl_name    => '',          // character varying(150)
    *    geom        => GeoJSON,    // GeoJSON
    *    geom_simple => GeoJSON,    // GeoJSON
    *    region_id   => 0,          // integer
    *    mddid       => 0,          // integer
    *  )
    */
    public static function addGadm($data)
    {
        $_api = new MApi();
        return $_api->addGadm($data);
    }

    //通过gid删除GADM数据
    public static function delGadmByGid($gid)
    {
        $_api = new MApi();
        return $_api->delGadmByGid($gid);
    }

    /*
    * 通过gid更新质点,区域面积
    * $geom_type: 1 geom, 2 geom_simple
    */
    public static function updateGeomInfoByGid($gid, $geom_type = 1)
    {
        $_api = new MApi();
        return $_api->updateGeomInfoByGid($gid, $geom_type);
    }

    //通过region_id查询线上Region服务的数据
    public static function getRegionByRegionId($region_id, $need_geom = 0)
    {
        $_api = new MApi();
        return $_api->getRegionByRegionId($region_id, $need_geom);
    }

    //通过mddid查询线上Region服务的数据
    public static function getRegionByMddid($mddid, $need_geom = 0)
    {
        $_api = new MApi();
        return $_api->getRegionByMddid($mddid, $need_geom);
    }

    //增加一个Region数据 （线上Region服务）
    public static function addRegion($data)
    {
        $_api = new MApi();
        return $_api->addRegion($data);
    }

    //通过Mddid更新线上Region数据
    public static function updateRegionByMddid($mddid, $data)
    {
        $_api = new MApi();
        return $_api->updateRegionByMddid($mddid, $data);
    }

    //通过Mddid更新线上Region数据
    public static function replaceRegionByMddid($mddid, $data)
    {
        $_api = new MApi();
        return $_api->replaceRegionByMddid($mddid, $data);
    }

    //商圈数据更新接口
    public static function replaceRegionByAreaId($area_id, $data)
    {
        $_api = new MApi();
        return $_api->replaceRegionByAreaId($area_id, $data);
    }

    //删除商圈数据
    public static function delRegionByAreaId($area_id)
    {
        $_api = new MApi();
        return $_api->delRegionByAreaId($area_id);
    }

    //通过商圈查询
    public static function getRegionByAreaId($area_id, $need_geom)
    {
        $_api = new MApi();
        return $_api->getRegionByAreaId($area_id, $need_geom);
    }

    //通过商圈查询
    public static function getRegionAreaIds()
    {
        $_api = new MApi();
        return $_api->getRegionAreaIds();
    }
}
