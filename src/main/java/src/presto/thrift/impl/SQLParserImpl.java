package src.presto.thrift.impl;

import org.apache.thrift.TException;
import src.presto.parse.*;
import src.presto.thrift.service.SQLParser;

import java.text.ParseException;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class SQLParserImpl implements SQLParser.Iface {

    /**
     * 解析SQL中的所有查询结果字段集合
     * e.g.  select id,dt as date from table where dt='20170801'
     *       将返回 id,date
     * @param sql
     * @return
     * @throws TException
     */
    @Override
    public List<String> getSelectItems(String sql) throws TException {
        return SelectItemParser.parseSelectItems(sql);
    }

    /**
     * 解析SQL中出现的实际将被查询的表集合(不包括查询with中自定义的表)
     * e.g.  select id,dt as date from table where dt='20170801' 将返回table
     * @param sql
     * @return
     * @throws TException
     */
    @Override
    public List<String> getTables(String sql) throws TException {
        return TableNameParser.getTargetTables(sql);
    }

    /**
     * 解析SQL中出现的with中自定义的表集合
     * e.g.
     * SQL:
     * with
     * userDefineTableA as (....),
     * userDefineTableB as (....)
     * 将返回 userDefineTableA,userDefineTableB
     * @param sql
     * @return
     * @throws TException
     */
    @Override
    public List<String> getWithTables(String sql) throws TException {
        return TableNameParser.getWithTables(sql);
    }

    /**
     * /**
     * 将对SQL进行校验,返回值是SQL的String,若SQL中未出现limit限制,将默认增加limit 50000的条件
     * @param sql
     * @return
     * @throws TException
     */
    @Override
    public String limitVerify(String sql) throws TException {
        return LimitVerify.limitVerify(sql);
    }

    /**
     * 获取SQL中所有表的查询时间段
     * 对于已定义别名的返回表名+时间段
     * 为定义别名的表将时间段累计到undefine的key中
     * @param sql
     * @return
     * @throws TException
     * @throws ParseException
     */
    @Override
    public Map<String, String> dateScope(String sql) throws TException {
        Map<String, String> dateScope = new HashMap<>();
        try {
            dateScope =  ParseDtScope.parseDtScope(sql);
        } catch (ParseException e) {
            e.printStackTrace();
        }
        return dateScope;
    }

    /**
     * 获取SQL查询中最大的时间跨度
     * @param sql
     * @return
     * @throws TException
     * @throws ParseException
     */
    @Override
    public int scope(String sql) throws TException {
        int scope = 0;
        try {
             scope = ParseDtScope.getScope(sql);
        } catch (ParseException e) {
            e.printStackTrace();
        }
        return scope;
    }

    /**
     * 获取SQL中join的次数
     * @param sql
     * @return
     * @throws TException
     */
    @Override
    public int joinTimes(String sql) throws TException {
        return JoinTimes.getJoinTimes(sql);
    }
}
