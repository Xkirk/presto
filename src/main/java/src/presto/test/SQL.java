package src.presto.test;

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
    public static String sql1 = "select\n" +
            "\tshow.type \n" +
            "\t,show.title\n" +
            "\t,show.abtest_type\n" +
            "\t,show.abtest\n" +
            "\t,click.uv as \"点击UV\"\n" +
            "\t,show.uv as \"曝光UV\"\n" +
            "\t,round(click.uv * 100.0 / show.uv,2) as \"UV点击率（%）\"\n" +
            "\t,click.pv as \"点击次数\"\n" +
            "\t,show.pv as \"曝光次数\"\n" +
            "\t,round(click.pv * 100.0 / show.pv,2) as \"PV点击率（%）\"\n" +
            "from\n" +
            "(\n" +
            "\tselect\n" +
            "\t \tjson_extract_scalar(attr,'$.item_type') as type\n" +
            "\t\t,json_extract_scalar(attr,'$.item_title') as title\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest_type') as abtest_type\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest') as abtest\n" +
            "\t\t,count(distinct open_udid) as uv\n" +
            "\t\t,count(*) as pv\n" +
            "\tfrom\n" +
            "\t\tmobile_event.home_article_list_show\n" +
            "\twhere\n" +
            "\t\t dt between '20170804' and '20170810'\n" +
            "\t\tand dt > '20160630'\n" +
            "\t\tand app_ver >= '7.5.1'\n" +
            "\t\tand event_code = 'home_article_list_show'\n" +
            "\t\tand app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "\t\tand (json_extract_scalar(attr,'$.item_type') = 'all' or 'all' = 'all')\n" +
            "\tgroup by \n" +
            "\t\tjson_extract_scalar(attr,'$.item_type')\n" +
            "\t\t,json_extract_scalar(attr,'$.item_title')\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest_type')\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest')\n" +
            ") show\n" +
            "left join\n" +
            "(\n" +
            "\tselect \n" +
            "\t\tjson_extract_scalar(attr,'$.item_type') as type\n" +
            "\t\t,json_extract_scalar(attr,'$.item_title') as title\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest_type') as abtest_type\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest') as abtest\n" +
            "\t\t,count(distinct open_udid) as uv\n" +
            "\t\t,count(*) as pv\n" +
            "\tfrom\n" +
            "\t\tmobile_event.home_article_list_click\n" +
            "\twhere\n" +
            "\t\t dt between '20170804' and '20170810'\n" +
            "\t\tand dt > '20160630'\n" +
            "\t\tand app_ver >= '7.5.1'\n" +
            "\t\tand event_code = 'home_article_list_click'\n" +
            "\t\tand app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "\t\tand (json_extract_scalar(attr,'$.item_type') = 'all' or 'all' = 'all')\n" +
            "\tgroup by \n" +
            "\t\tjson_extract_scalar(attr,'$.item_type')\n" +
            "\t\t,json_extract_scalar(attr,'$.item_title')\n" +
            "\t\t,json_extract_scalar(attr,'$.abtest_type') \n" +
            "\t\t,json_extract_scalar(attr,'$.abtest')\n" +
            ") click\n" +
            "on \n" +
            "\tshow.type = click.type\n" +
            "\tand show.title = click.title\n" +
            "\tand show.abtest_type = click.abtest_type\n" +
            "\tand show.abtest = click.abtest\n" +
            "where\n" +
            "\tshow.pv >= 10000\n" +
            "order by \n" +
            "\tclick.pv * 100.0 / show.pv desc";
}
