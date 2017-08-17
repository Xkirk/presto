package src.presto.utils;

/**
 * Created by kirk on 2017/7/12.
 */
public class SQL {
    public static String sql = "select attr from server_event_parquet a " +
            "left join " +
            "(select uid from page_event_parquet  where b.dt between '20170725' and '20170724') b "  +
            "on a.uid = b.uid " +
            "left join mobile_event_parquet c  on uid = uid " +
            "where a.dt<'20170725' and a.dt>'20170720' or b.dt between '20170701' and '20170702' and c.dt<'20170726' or c.dt ='20170727' and c.dt >'20170725' or c.dt > '20190101' and c.dt<'20190104'";
    public static String sql1 = "with show as(SELECT\n" +
            "    json_extract_scalar(event.attr,'$.url') as url,\n" +
            "\tjson_extract_scalar(event.attr,'$.bus_id') as attr_bus_id,\n" +
            "\tcount(distinct event.uuid) as standard_uv_uuid\n" +
            "FROM\n" +
            "\tpage_event_parquet event \n" +
            "WHERE\n" +
            "\tevent.dt<='20170814' \n" +
            "\tand event.dt>=date_format( date_add('day',-7,date_parse('20170814','%Y%m%d')) ,'%Y%m%d' )\n" +
            "\tand event.app_code='default' \n" +
            "\tand event.event_code='h5ToApp' \n" +
            "\tand json_extract_scalar(event.attr,'$.channel')='mdd' \n" +
            "\tand json_extract_scalar(event.attr,'$.sub_channel')='baike' \n" +
            "\tand json_extract_scalar(event.attr,'$.action')='show'\n" +
            "\tand json_extract_scalar(event.attr,'$.bus_id')!=''\n" +
            "GROUP BY\n" +
            "\t1,2\n" +
            "ORDER BY\n" +
            "\tstandard_uv_uuid desc\n" +
            "limit 50000\n" +
            "),\n" +
            "click as (SELECT\n" +
            "    json_extract_scalar(event.attr,'$.url') as url,\n" +
            "\tjson_extract_scalar(event.attr,'$.bus_id') as attr_bus_id,\n" +
            "\tcount(distinct event.uuid) as standard_uv_uuid\n" +
            "FROM\n" +
            "\tpage_event_parquet event \n" +
            "WHERE\n" +
            "\tevent.dt<='20170814' \n" +
            "    and event.dt>=date_format( date_add('day',-7,date_parse('20170814','%Y%m%d')) ,'%Y%m%d' )\n" +
            "\tand event.app_code='default' \n" +
            "\tand event.event_code='h5ToApp' \n" +
            "\tand json_extract_scalar(event.attr,'$.channel')='mdd' \n" +
            "\tand json_extract_scalar(event.attr,'$.sub_channel')='baike' \n" +
            "\tand json_extract_scalar(event.attr,'$.action')='click'\n" +
            "GROUP BY\n" +
            "\t1,2\n" +
            "ORDER BY\n" +
            "\tstandard_uv_uuid desc\n" +
            ")\n" +
            "\n" +
            "select\n" +
            "\tshow.url as url,\n" +
            "\tshow.attr_bus_id as \"目标地址\",\n" +
            "    show.standard_uv_uuid as \"展示show\",\n" +
            "    click.standard_uv_uuid as \"点击\",\n" +
            "    round(click.standard_uv_uuid*100.0/show.standard_uv_uuid,2) as \"点击率\"\n" +
            "from show\n" +
            "left join click on show.url=click.url and show.attr_bus_id = click.attr_bus_id\n" +
            "order by show.standard_uv_uuid desc";
}
