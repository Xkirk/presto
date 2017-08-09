<?php

namespace apps\gis;

class MApi extends \Ko_Busi_Api
{
    private $_db_handler;

    const TYPE_GEOM_GADM    = 1;
    const TYPE_GEOM_SIMPLE  = 2;
    const TYPE_GEOM_GAODE   = 3;

    public function __construct()
    {
        $this->_db_handler = \apps\pdo\MFacade_PostgreSQL::getInstance('mfwgis');
    }

    public function getGadmByKeys($keys, $need_geom = 0, $limit = 10)
    {
        $bind_data = array();

        $_sql = "SELECT gid,iso,adm_level,id_0,id_1,id_2,id_3,id_4,id_5,object_id,region_id,mddid,area,mtime,type,
            name_0,name_1,name_2,name_3,name_4,name_5,engtype,varname,nl_name,ST_AsGeoJSON(centroid) as centroid";

        if ($need_geom == self::TYPE_GEOM_GADM) {
            $_sql .= ",ST_AsGeoJSON(geom) as geom";
        }

        if ($need_geom == self::TYPE_GEOM_SIMPLE) {
            $_sql .= ",ST_AsGeoJSON(geom_simple) as geom_simple";
        }

        if ($need_geom == self::TYPE_GEOM_GAODE) {
            $_sql .= ",ST_AsGeoJSON(geom_gaode) as geom_gaode";
        }

        $_sql .= " FROM gadm WHERE 1=1";

        for ($i=0; $i<=5; $i++) {
            $_key_id = "id_{$i}";
            if (isset($keys[$_key_id])) {
                $_sql .= " AND {$_key_id}=:{$_key_id}";
                $bind_data[":{$_key_id}"] = (int)$keys[$_key_id];
            }
        }

        if ($keys['object_id']) {
            $_sql .= " AND object_id=:object_id";
            $bind_data[":object_id"] = (int)$keys['object_id'];

        }

        if ($limit) {
            $_sql .=" limit :limit";
            $bind_data[":limit"] = (int)$limit;
        }

        return $this->_db_handler->fetchAll($_sql, $bind_data);
    }

    public function getGadmByGid($gid, $need_geom = 0)
    {
        $_sql = "SELECT gid,iso,adm_level,id_0,id_1,id_2,id_3,id_4,id_5,object_id,region_id,mddid,area,mtime,type,
            name_0,name_1,name_2,name_3,name_4,name_5,engtype,varname,nl_name,ST_AsGeoJSON(centroid) as centroid";

        if ($need_geom == self::TYPE_GEOM_GADM) {
            $_sql .= ",ST_AsGeoJSON(geom) as geom";
        }

        if ($need_geom == self::TYPE_GEOM_SIMPLE) {
            $_sql .= ",ST_AsGeoJSON(geom_simple) as geom_simple";
        }

        if ($need_geom == self::TYPE_GEOM_GAODE) {
            $_sql .= ",ST_AsGeoJSON(geom_gaode) as geom_gaode";
        }

        $_sql .= " FROM gadm WHERE gid=:gid";

        return $this->_db_handler->fetchAll($_sql, array(":gid" => $gid));
    }


    public function getGadmByLatLng($lat, $lng, $geom_type = self::TYPE_GEOM_GADM)
    {
        $geom_filed = "geom";
        if ($geom_type == self::TYPE_GEOM_SIMPLE)
            $geom_filed = "geom_simple";
        else if ($geom_type == self::TYPE_GEOM_GAODE)
            $geom_filed = "geom_gaode";

        $_sql = "SELECT gid,iso,adm_level,id_0,id_1,id_2,id_3,id_4,id_5,object_id,region_id,mddid,area,mtime,type,
            name_0,name_1,name_2,name_3,name_4,name_5,engtype,varname,nl_name,ST_AsGeoJSON(centroid) as centroid";

        $_sql .= " FROM gadm WHERE ST_CoveredBy(ST_SetSRID(ST_MakePoint(:lng,:lat), 4326), {$geom_filed})";

        return $this->_db_handler->fetchAll($_sql, array(":lng" => $lng, ":lat" => $lat));
    }

    public function getGadmByRegionId($region_id, $need_geom = 0)
    {
        $_sql = "SELECT gid,iso,adm_level,id_0,id_1,id_2,id_3,id_4,id_5,object_id,region_id,mddid,area,mtime,type,
            name_0,name_1,name_2,name_3,name_4,name_5,engtype,varname,nl_name,ST_AsGeoJSON(centroid) as centroid";

        if ($need_geom == self::TYPE_GEOM_GADM) {
            $_sql .= ",ST_AsGeoJSON(geom) as geom";
        }

        if ($need_geom == self::TYPE_GEOM_SIMPLE) {
            $_sql .= ",ST_AsGeoJSON(geom_simple) as geom_simple";
        }

        if ($need_geom == self::TYPE_GEOM_GAODE) {
            $_sql .= ",ST_AsGeoJSON(geom_gaode) as geom_gaode";
        }

        $_sql .= " FROM gadm WHERE region_id=:region_id";

        return $this->_db_handler->fetchAll($_sql, array(":region_id" => $region_id));
    }

    public function getUnionByGids($gids, $geom_type = self::TYPE_GEOM_GADM)
    {
        if (empty($gids)) {
            return array();
        }

        $geom_filed = "geom";
        if ($geom_type == self::TYPE_GEOM_SIMPLE)
            $geom_filed = "geom_simple";
        else if ($geom_type == self::TYPE_GEOM_GAODE)
            $geom_filed = "geom_gaode";

        $gids_sql = implode(",", $gids);
        $_sql = "SELECT ST_AsGeoJSON(ST_Union(ARRAY(SELECT {$geom_filed} FROM
            gadm WHERE gid in ({$gids_sql})))) as union_geom";
        return $this->_db_handler->fetchAll($_sql);
    }

    public function getDifferenceByGid($gid1, $gid2)
    {
        if (empty($gid1) || empty($gid2)) {
            return array();
        }

        $_sql = "SELECT ST_AsGeoJSON(ST_Difference((SELECT geom FROM gadm where gid=:gid1),
            (SELECT geom FROM gadm where gid=:gid2))) as diff_geom";

        return $this->_db_handler->fetchAll($_sql, array(":gid1" => $gid1, ":gid2" => $gid2));
    }

    public function updateGadmByGid($gid, $data)
    {
        $bind_data = array();

        if (empty($data))
            return 0;

        $_sql = "UPDATE gadm SET mtime=CURRENT_TIMESTAMP ";

        foreach ($data as $k => $v) {

            if ($k == 'geom' || $k == 'geom_simple' || $k == 'geom_gaode') {
                $_sql .= ", {$k}=ST_SetSRID(ST_Multi(ST_GeomFromGeoJSON(:{$k})), 4326)";
            } else if ($k == 'centroid') {
                $_sql .= ", {$k}=ST_SetSRID(ST_GeomFromGeoJSON(:{$k}), 4326)";
            } else {
                $_sql .= ", {$k}=:{$k} ";
            }

            $bind_data[":{$k}"] = $v;
        }

        $_sql .=  " WHERE gid=:gid";
        $bind_data[":gid"] = (int)$gid;

        $res = $this->_db_handler->execute($_sql, $bind_data);

        if (empty($res))
        {
            return 0;
        }

        return  $res->rowCount();
    }

    public function addGadm($data)
    {
        $bind_data = array();

        unset($data['gid']);
        unset($data['centroid']);

        if (empty($data['search_level']))
            $data['search_level'] = 2;  //默认值2，优先搜索

        $_sql = "INSERT INTO  gadm (";

        $_keys = array();
        foreach ($data as $k => $v) {
            $_keys[] = $k;
        }
        $_sql .=  implode(',', $_keys) . ") VALUES(";

        $_vals = array();
        foreach ($data as $k => $v) {

            $_sub_sql .= empty($_sub_sql) ? '' : ',';

            if ($k == 'geom' || $k == 'geom_simple' || $k == 'geom_gaode') {
                $_vals[] = "ST_SetSRID(ST_Multi(ST_GeomFromGeoJSON(:{$k})), 4326)";
            } else {
                $_vals[] = ":{$k}";
            }

            $bind_data[":{$k}"] = $v;
        }
        $_sql .= implode(',', $_vals) . ")";

        $res = $this->_db_handler->execute($_sql, $bind_data);

        if (empty($res))
        {
            return 0;
        }

        return  $this->_db_handler->lastInsertId("gadm_gid_seq");
    }

    public function delGadmByGid($gid)
    {
         return $this->_db_handler->delete('gadm', "gid=:gid", array(":gid" => (int)$gid));
    }

    public function updateGeomInfoByGid($gid, $geom_type)
    {
        $geom_filed = $geom_type == 1 ? "geom" : "geom_simple";

        $_sql = "UPDATE gadm SET centroid=ST_Centroid({$geom_filed}), area=st_area({$geom_filed}::geography)/1000000,
            mtime=CURRENT_TIMESTAMP WHERE gid=:gid";
        $res = $this->_db_handler->execute($_sql, array(":gid" => (int)$gid));

        if (empty($res))
        {
            return 0;
        }

        return  $res->rowCount();
    }

    public function getRegionByRegionId($region_id, $need_geom = 0)
    {
        $_sql = "SELECT id,region_id,area_id,mddid,p_id,type,admin_level,search_level,area,mtime";

        if ($need_geom == 1) {
            $_sql .= ",ST_AsGeoJSON(geom) as geom";
        }

        $_sql .= " FROM mfw_region WHERE type=1 AND region_id=:region_id";

        return $this->_db_handler->fetchAll($_sql, array(":region_id" => $region_id));
    }

    public function getRegionByMddid($mddid, $need_geom = 0)
    {
        $_sql = "SELECT id,region_id,area_id,mddid,p_id,type,admin_level,search_level,area,mtime";

        if ($need_geom == 1) {
            $_sql .= ",ST_AsGeoJSON(geom) as geom";
        }

        $_sql .= " FROM mfw_region WHERE type=2 AND mddid=:mddid";

        return $this->_db_handler->fetchAll($_sql, array(":mddid" => $mddid));
    }

    public function updateRegionByMddid($mddid, $data)
    {
        $bind_data = array();

        if (empty($data))
            return 0;

        $_sql = "UPDATE mfw_region SET mtime=CURRENT_TIMESTAMP ";

        foreach ($data as $k => $v) {

            if ($k == 'geom') {
                $_sql .= ", {$k}=ST_SetSRID(ST_Multi(ST_GeomFromGeoJSON(:{$k})), 4326)";
            } else {
                $_sql .= ", {$k}=:{$k} ";
            }

            $bind_data[":{$k}"] = $v;
        }

        $_sql .=  " WHERE type=2 AND mddid=:mddid";
        $bind_data[":mddid"] = (int)$mddid;

        $res = $this->_db_handler->execute($_sql, $bind_data);

        if (empty($res))
        {
            return 0;
        }

        return  $res->rowCount();
    }

    public function addRegion($data)
    {
        $bind_data = array();

        unset($data['id']);

        $_sql = "INSERT INTO mfw_region (";

        $_keys = array();
        foreach ($data as $k => $v) {
            $_keys[] = $k;
        }
        $_sql .=  implode(',', $_keys) . ") VALUES(";

        $_vals = array();
        foreach ($data as $k => $v) {

            $_sub_sql .= empty($_sub_sql) ? '' : ',';

            if ($k == 'geom') {
                $_vals[] = "ST_SetSRID(ST_Multi(ST_GeomFromGeoJSON(:{$k})), 4326)";
            } else {
                $_vals[] = ":{$k}";
            }

            $bind_data[":{$k}"] = $v;
        }
        $_sql .= implode(',', $_vals) . ")";

        $res = $this->_db_handler->execute($_sql, $bind_data);

        if (empty($res))
        {
            return 0;
        }

        return  $this->_db_handler->lastInsertId("mfw_region_id_seq");
    }

    //region后台绘制目的地区域更新方法
    public function replaceRegionByMddid($mddid, $data)
    {
        $region_data = $this->getRegionByMddid($mddid);
        if (empty($region_data)) {
            //to insert region
            $data['mddid'] = $mddid;
            $data['type'] = 2;
            $data['search_level'] = 2;
            $data['admin_level'] = 9;
            return $this->addRegion($data);
        } else {
            //to update region
            return $this->updateRegionByMddid($mddid, $data);
        }
    }

    //获取商圈数据
    public function getRegionByAreaId($area_id, $need_geom = 0)
    {
        $_sql = "SELECT id,region_id,area_id,mddid,p_id,type,admin_level,search_level,mtime";

        if ($need_geom == 1) {
            $_sql .= ",ST_AsGeoJSON(geom) as geom";
        }

        $_sql .= " FROM mfw_region WHERE type=4 AND area_id=:area_id";

        return $this->_db_handler->fetchAll($_sql, array(":area_id" => $area_id));
    }

    //更新商圈数据
    public function updateRegionByAreaId($area_id, $data)
    {
        $bind_data = array();

        if (empty($data))
            return 0;

        $_sql = "UPDATE mfw_region SET mtime=CURRENT_TIMESTAMP ";

        foreach ($data as $k => $v) {

            if ($k == 'geom') {
                $_sql .= ", {$k}=ST_SetSRID(ST_Multi(ST_GeomFromGeoJSON(:{$k})), 4326)";
            } else {
                $_sql .= ", {$k}=:{$k} ";
            }

            $bind_data[":{$k}"] = $v;
        }

        $_sql .=  " WHERE type=4 AND area_id=:area_id";
        $bind_data[":area_id"] = (int)$area_id;

        $res = $this->_db_handler->execute($_sql, $bind_data);

        if (empty($res))
        {
            return 0;
        }

        return  $res->rowCount();
    }

    //商圈数据更新接口
    public function replaceRegionByAreaId($area_id, $data)
    {
        $region_data = $this->getRegionByAreaId($area_id);
        if (empty($region_data)) {
            //to insert region
            $data['area_id'] = $area_id;
            $data['type'] = 4;
            return $this->addRegion($data);
        } else {
            //to update region
            return $this->updateRegionByAreaId($area_id, $data);
        }
    }

    //删除商圈数据
    public function delRegionByAreaId($area_id)
    {
         return $this->_db_handler->delete('mfw_region', "type=4 AND area_id=:area_id",
            array(":area_id" => (int)$area_id));
    }

    //获取所有商圈area_id，后台维护用
    public function getRegionAreaIds()
    {
        $_sql = "SELECT area_id FROM mfw_region WHERE type=4";
        $res = $this->_db_handler->fetchAll($_sql);
        return \Ko_Tool_Utils::AObjs2ids($res, 'area_id');
    }

}
