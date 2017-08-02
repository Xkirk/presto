package src.presto.thrift.impl;
import org.apache.thrift.TException;
import src.presto.parse.SelectItemParser;
import src.presto.thrift.service.SQLParser;

import java.util.List;
import java.util.Map;

public class SQLParserImpl implements SQLParser.Iface {

    @Override
    public List<String> getSelectItems(String sql) throws TException {
        return SelectItemParser.parseSelectItems(sql);
    }

    @Override
    public List<String> getTables(String sql) throws TException {
        return null;
    }

    @Override
    public String limitVerify(String sql) throws TException {
        return null;
    }

    @Override
    public Map<String, String> dateScope(String sql) throws TException {
        return null;
    }

    @Override
    public List<String> scope(String sql) throws TException {
        return null;
    }
}
