package src.presto.parse;

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
    public static String sql1 = "with tfcdtl as \n" +
            "(\n" +
            "    select dt,platform,uuid as uvid \n" +
            "    from page_event_parquet \n" +
            "    where dt between '20170501' and '20170531'\n" +
            "    and app_code='flight'\n" +
            "    and platform in ('www','m')\n" +
            "    and event_code='index'\n" +
            "\n" +
            "    union all\n" +
            "\n" +
            "    select dt,'app' as platform,open_udid as uvid\n" +
            "    from mobile_event.page \n" +
            "    where dt between '20170501' and '20170531'\n" +
            "    and app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "    and attr_name='机票首页'\n" +
            "    --and attr_in='parent'\n" +
            "    \n" +
            "    union all\n" +
            "\n" +
            "    select dt,platform,uuid as uvid\n" +
            "    from logdata a\n" +
            "    where a.dt between '20170501' and '20170531'\n" +
            "    and platform in ('www','m')\n" +
            "    and host in ('www','m')\n" +
            "    and upage like '%/flight/list%'\n" +
            "\n" +
            "    union all\n" +
            "\n" +
            "    select dt,'app' as platform,open_udid as uvid\n" +
            "    from mobile_event.page \n" +
            "    left join page_event.page \n" +
            "    where dt between '20170501' and '20170531'\n" +
            "    and app_code in ('cn.mafengwo.www','com.mfw.roadbook')\n" +
            "    and attr_name='机票列表页'\n" +
            "    --and attr_in='parent'\n" +
            "    \n" +
            "    union all\n" +
            "\n" +
            "    select dt,platform,uuid as uvid \n" +
            "    from page_event_parquet \n" +
            "    where dt between '20170501' and '20170531'\n" +
            "    and app_code='flight'\n" +
            "    and platform in ('www','m')\n" +
            "    and event_code in ('list_select_airline','seat_select_seat','submit_order')\n" +
            "\n" +
            "    union all\n" +
            "\n" +
            "    select dt,platform,openudid as uvid \n" +
            "    from page_event_parquet a\n" +
            "    where a.dt between '20170501' and '20170531'\n" +
            "    and app_code='flight'\n" +
            "    and platform in ('app')\n" +
            "    and event_code in ('list_select_airline','seat_select_seat','submit_order')\n" +
            "),\n" +
            "\n" +
            "ord_bf as\n" +
            "(\n" +
            "    select mfw_order_id,mobile,cdate \n" +
            "    from sales.flight_order ord \n" +
            "    where cast(status as bigint)>=30\n" +
            "),\n" +
            "\n" +
            "isagent as \n" +
            "(\n" +
            "    select ord.mfw_order_id\n" +
            "    ,case when sum(pnr.ticket_num) >=20 then '同行' else '散户' end as agent_type\n" +
            "    from sales.flight_order ord\n" +
            "    left join ord_bf --每个订单下单日期前推30天（含当天），累计购买票数>=20为同行\n" +
            "        on ord.mobile=ord_bf.mobile \n" +
            "        and ord_bf.cdate<=ord.cdate \n" +
            "        and ord_bf.cdate>=date_format(date_parse(ord.cdate,'%Y-%m-%d') - interval '30' day,'%Y-%m-%d')\n" +
            "    left join (select mfw_order_id,count(1) as ticket_num from sales.flight_order_pnr group by mfw_order_id) pnr \n" +
            "        on ord_bf.mfw_order_id=pnr.mfw_order_id\n" +
            "    where date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d') between '20170501' and '20170531' \n" +
            "    group by ord.mfw_order_id \n" +
            "),\n" +
            "\n" +
            "usr as \n" +
            "(\n" +
            "    select ord.mfw_order_id\n" +
            "    ,case when count(distinct ord_bf.mfw_order_id)>=1 then '老用户' else '新用户' end as usr_type\n" +
            "    ,count(distinct ord_bf.mfw_order_id) as ord_bf_num\n" +
            "\n" +
            "    from sales.flight_order ord \n" +
            "    left join ord_bf \n" +
            "        on ord.mobile=ord_bf.mobile\n" +
            "        and ord_bf.cdate<=date_format(date_parse(ord.cdate,'%Y-%m-%d') - interval '7' day,'%Y-%m-%d') \n" +
            "    \n" +
            "    where date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d') between '20170501' and '20170531' \n" +
            "    group by ord.mfw_order_id\n" +
            "),\n" +
            "\n" +
            "goods as \n" +
            "(\n" +
            "    select mfw_order_id,\n" +
            "    sum(if(goods_id='17',1,0)) as accident_num,\n" +
            "    sum(if(goods_id='11',1,0)) as delay_num,\n" +
            "    sum(if(goods_id not in ('11','17'),1,0)) as others_num,\n" +
            "    sum(if(goods_id='17',cast(price as double)*cast(nums as bigint),0)) as accident_total_price,\n" +
            "    sum(if(goods_id='11',cast(price as double)*cast(nums as bigint),0)) as delay_total_price,\n" +
            "    sum(if(goods_id not in ('11','17'),cast(price as double)*cast(nums as bigint),0)) as others_total_price\n" +
            "\n" +
            "    from sales.flight_order_goods \n" +
            "    group by mfw_order_id\n" +
            "),\n" +
            "\n" +
            "ord as \n" +
            "(\n" +
            "    select \n" +
            "    case 'sum' when 'sum' then concat(substr('20170501',3,6),'-',substr('20170531',3,6)) when 'day' then date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%y%m%d') when 'week' then concat(date_format(date_add('day',1-dow(date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),'%y%m%d'),'-',date_format(date_add('day',7-dow(date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),'%y%m%d')) when 'month' then substr(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),3,4) end as dt,\n" +
            "    --ord.channel,\n" +
            "    --isagent.agent_type,\n" +
            "    --usr.usr_type,\n" +
            "    --usr.ord_bf_num,\n" +
            "    count(distinct ord.mobile) as mobile_num,\n" +
            "    count(distinct ord.uid) as uid_num,\n" +
            "    count(ord.mfw_order_id) as ord_num,\n" +
            "    sum(pnr.ticket_num) as ticket_num,\n" +
            "    sum(cast(ord.price as double)) as gmv,\n" +
            "    sum(if(cast(ord.status as bigint)>=33 and ord.is_cancel='0',pnr.ticket_num,null)) as success_ticket_num,\n" +
            "    round(100.0*sum(if(cast(ord.status as bigint)>=33 and ord.is_cancel='0',pnr.ticket_num,null))/sum(pnr.ticket_num),2) as success_ratio,\n" +
            "    round(avg(if(cast(ord.status as bigint)>=33,date_diff('minute',date_parse(if(ord.payment_time='null',null,ord.payment_time),'%Y-%m-%d %H:%i:%s'),date_parse(if(ord.ticket_time='null',null,ord.ticket_time),'%Y-%m-%d %H:%i:%s')),null)),2) as avg_success_time,\n" +
            "    round(100.0*sum(case when cast(ord.status as bigint)>=33 and date_diff('minute',date_parse(if(ord.payment_time='null',null,ord.payment_time),'%Y-%m-%d %H:%i:%s'),date_parse(if(ord.ticket_time='null',null,ord.ticket_time),'%Y-%m-%d %H:%i:%s'))<=30 then pnr.ticket_num end)/sum(if(cast(ord.status as bigint)>=33,pnr.ticket_num,null)),2) as halfhour_success_ratio,\n" +
            "    count(distinct case when discount.coupon>0 then discount.mfw_order_id else null end) as use_coupon_ord_num,\n" +
            "    sum(discount.coupon) as total_coupon,\n" +
            "    sum(accident_num) as accident_num,\n" +
            "    sum(delay_num) as delay_num,\n" +
            "    sum(others_num) as others_num,\n" +
            "    sum(accident_total_price) as accident_total_price,\n" +
            "    sum(delay_total_price) as delay_total_price,\n" +
            "    sum(others_total_price) as others_total_price\n" +
            "\n" +
            "    from sales.flight_order ord \n" +
            "    left join isagent on ord.mfw_order_id=isagent.mfw_order_id \n" +
            "    left join usr on ord.mfw_order_id=usr.mfw_order_id\n" +
            "    left join (select mfw_order_id,count(1) as ticket_num from sales.flight_order_pnr group by mfw_order_id) pnr on ord.mfw_order_id=pnr.mfw_order_id\n" +
            "    left join (select mfw_order_id,sum(cast(used_price as double)) as coupon from sales.flight_order_discount group by mfw_order_id) discount on ord.mfw_order_id=discount.mfw_order_id\n" +
            "    left join goods on ord.mfw_order_id=goods.mfw_order_id\n" +
            "    where date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d') between '20170501' and '20170531' \n" +
            "    and cast(ord.status as bigint)>=30\n" +
            "\n" +
            "    group by \n" +
            "    case 'sum' when 'sum' then concat(substr('20170501',3,6),'-',substr('20170531',3,6)) when 'day' then date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%y%m%d') when 'week' then concat(date_format(date_add('day',1-dow(date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),'%y%m%d'),'-',date_format(date_add('day',7-dow(date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),date_parse(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),'%Y%m%d')),'%y%m%d')) when 'month' then substr(date_format(date_parse(ord.cdate,'%Y-%m-%d'),'%Y%m%d'),3,4) end\n" +
            "    --,ord.channel\n" +
            "    --,isagent.agent_type\n" +
            "    --,usr.usr_type\n" +
            "    --,usr.ord_bf_num\n" +
            ")\n" +
            "\n" +
            "select\n" +
            "    tfc.dt as \"日期\"\n" +
            "    --,tfc.platform as \"来源\"\n" +
            "    ,tfc.uv as \"机票频道总uv\"\n" +
            "    --,ord.agent_type as \"同行／散户\"\n" +
            "    --,ord.usr_type as \"新老用户\"\n" +
            "    --,ord.ord_bf_num as \"7天前累计支付订单数\"\n" +
            "    ,ord.mobile_num as \"下单手机数\"\n" +
            "    ,ord.uid_num as \"下单uid数\"\n" +
            "    ,ord.ord_num as \"订单数\"\n" +
            "    ,round(100.0*ord.ord_num/tfc.uv,2) as \"转化率\"\n" +
            "    ,ord.gmv as GMV\n" +
            "    ,ord.ticket_num as \"机票数\"\n" +
            "    ,ord.success_ticket_num as \"出票成功数\"\n" +
            "    ,ord.success_ratio as \"出票率\"\n" +
            "    ,avg_success_time as \"平均出票时间/min\"\n" +
            "    ,halfhour_success_ratio as \"半小时出票率\"\n" +
            "    ,round(ord.gmv/ord.ord_num,2) as \"均单价\"\n" +
            "    ,round(ord.gmv/ord.ticket_num,2) as \"均票价\"\n" +
            "    ,round(ord.gmv/ord.mobile_num,2) as \"客单价\"\n" +
            "    ,round(1.0*ord.ord_num/ord.mobile_num,2) as \"客单数\"\n" +
            "    ,round(1.0*ord.ticket_num/ord.ord_num,2) as \"单张订单购买机票数\"\n" +
            "    ,ord.use_coupon_ord_num as \"使用优惠券订单数\"\n" +
            "    ,ord.total_coupon as \"优惠券总额\"\n" +
            "    ,ord.accident_num as \"意外险购买份数\"\n" +
            "    ,round(100.0*ord.accident_num/ord.ticket_num,2) as \"意外险覆盖率\"\n" +
            "    ,ord.delay_num as \"延误险购买份数\"\n" +
            "    ,round(100.0*ord.delay_num/ord.ticket_num,2) as \"延误险覆盖率\"\n" +
            "    ,ord.others_num as \"其他商品购买份数\"\n" +
            "    ,ord.accident_total_price as \"意外险总额\"\n" +
            "    ,ord.delay_total_price as \"延误险总额\"\n" +
            "    ,ord.others_total_price as \"其他商品总额\"\n" +
            "from \n" +
            "(\n" +
            "    select \n" +
            "    case 'sum' when 'sum' then concat(substr('20170501',3,6),'-',substr('20170531',3,6)) when 'day' then substr(dt,3,6) when 'week' then concat(date_format(date_add('day',1-dow(date_parse(dt,'%Y%m%d')),date_parse(dt,'%Y%m%d')),'%y%m%d'),'-',date_format(date_add('day',7-dow(date_parse(dt,'%Y%m%d')),date_parse(dt,'%Y%m%d')),'%y%m%d')) when 'month' then substr(dt,3,4) end as dt\n" +
            "    --,platform\n" +
            "    ,count(distinct uvid) as uv \n" +
            "    from tfcdtl\n" +
            "    group by \n" +
            "    case 'sum' when 'sum' then concat(substr('20170501',3,6),'-',substr('20170531',3,6)) when 'day' then substr(dt,3,6) when 'week' then concat(date_format(date_add('day',1-dow(date_parse(dt,'%Y%m%d')),date_parse(dt,'%Y%m%d')),'%y%m%d'),'-',date_format(date_add('day',7-dow(date_parse(dt,'%Y%m%d')),date_parse(dt,'%Y%m%d')),'%y%m%d')) when 'month' then substr(dt,3,4) end\n" +
            "    --,platform\n" +
            ") tfc \n" +
            "\n" +
            "left join ord \n" +
            "on tfc.dt=ord.dt \n" +
            "--and case when tfc.platform='m' then 'wap' when tfc.platform='www' then 'pc' else tfc.platform end =ord.channel\n" +
            "order by tfc.dt asc limit 1000";
}
