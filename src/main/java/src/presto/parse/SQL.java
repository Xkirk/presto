package src.presto.parse;

/**
 * Created by kirk on 2017/7/12.
 */
public class SQL {
    public static String sql = "with \n" +
            "fav_uid as (\n" +
            "    select\n" +
            "        uid\n" +
            "    from\n" +
            "        semi_hotel.hotel_fav_app\n" +
            "    where\n" +
            "        dt='20170701'\n" +
            "    group by\n" +
            "        1\n" +
            "),\n" +
            "fav_check as (\n" +
            "     select\n" +
            "        uid,\n" +
            "        check_dt,\n" +
            "        num\n" +
            "    from\n" +
            "    (select\n" +
            "        uid,\n" +
            "        concat( json_extract_scalar(attr,'$.check_in'),'&', json_extract_scalar(attr,'$.check_out')) as check_dt,count(1) as num,\n" +
            "        rank() over (partition by uid order by count(1) desc) as rnk\n" +
            "    from\n" +
            "         mobile_event_parquet  a\n" +
            "    where\n" +
            "        a.dt>='20170701' and a.dt<='20170704'\n" +
            "        and catalog='default'\n" +
            "        and a.event_code='hotel_detail_price_refresh'\n" +
            "        and a.app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "    group by\n" +
            "        1,2\n" +
            "    )\n" +
            "    where\n" +
            "        rnk=1\n" +
            "),\n" +
            "max_dt as (\n" +
            "    select\n" +
            "        uid,\n" +
            "        json_extract_scalar(attr,'$.hotel_id') as hotel_id,  \n" +
            "        count(1) as num,  \n" +
            "        max(ctime) as ctime_max\n" +
            "    from\n" +
            "        mobile_event_parquet \n" +
            "    where\n" +
            "        dt>='20170701' and dt<='20170704' \n" +
            "        and catalog='default'\n" +
            "        and uid in (select uid from fav_uid )\n" +
            "        and event_code='hotel_detail_price_refresh'\n" +
            "        and app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "    group by \n" +
            "        1,2\n" +
            "\n" +
            "),\n" +
            "\n" +
            "price_refresh as (\n" +
            "    select\n" +
            "        a.dt,\n" +
            "        a.uid,\n" +
            "        b.num,\n" +
            "        c.check_dt,\n" +
            "        c.num as check_dt_num,\n" +
            "        json_extract_scalar(attr,'$.hotel_id') as hotel_id,\n" +
            "        json_extract_scalar(attr,'$.mddid') as mddid,\n" +
            "        json_extract_scalar(attr,'$.check_in') as check_in,\n" +
            "        json_extract_scalar(attr,'$.check_out') as check_out,\n" +
            "        json_extract_scalar(attr,'$.lowest_price') as price\n" +
            "from\n" +
            "    mobile_event_parquet  a\n" +
            "inner join\n" +
            "    max_dt b\n" +
            "on a.uid=b.uid\n" +
            "    and json_extract_scalar(attr,'$.hotel_id')=b.hotel_id\n" +
            "    and a.ctime=b.ctime_max\n" +
            "left join\n" +
            "    fav_check c\n" +
            "on a.uid=c.uid\n" +
            "where\n" +
            "    a.dt>='20170701' and a.dt<='20170704'\n" +
            "    and catalog='default'\n" +
            "    and a.event_code='hotel_detail_price_refresh'\n" +
            "    and a.app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "group by \n" +
            "    1,2,3,4,5,6,7,8,9,10\n" +
            ")\n" +
            "\n" +
            "select\n" +
            "    a.dt,a.uid,open_udid,poi_id,a.city_name,a.city_mddid,b.dt as refresh_dt,b.check_in,b.check_out,b.price,b.num,b.check_dt,b.check_dt_num\n" +
            "\n" +
            "from\n" +
            "    semi_hotel.hotel_fav_app a\n" +
            "left join\n" +
            "    price_refresh b\n" +
            "on a.poi_id=b.hotel_id and a.uid=b.uid\n" +
            "where\n" +
            "    a.dt='20170701'\n" +
            "    and a.dt<=b.dt\n" +
            "group by 1,2,3,4,5,6,7,8,9,10,11,12,13\n" +
            "--select\n" +
            " --   a.dt,a.uid,open_udid,poi_id,a.city_name,a.city_mddid,b.dt as refresh_dt,b.check_in,b.check_out,b.price,b.num,b.check_dt,b.check_dt_num\n" +
            "\n" +
            "--from\n" +
            " --   semi_hotel.hotel_fav_appqqqqq a";
}
