package src.presto.thrift.client;

import org.apache.thrift.protocol.TBinaryProtocol;
import org.apache.thrift.protocol.TProtocol;
import org.apache.thrift.transport.TSocket;
import org.apache.thrift.transport.TTransport;
import src.presto.parse.SQL;
import src.presto.thrift.service.SQLParser;

import java.util.ArrayList;
import java.util.List;

public class SQLParserClient {
    public static final String SERVER_IP = "localhost";
    public static final int SERVER_PORT = 8090;
    public static final int TIMEOUT = 30000;
    public static void main(String[] args) {
       List<String> selectItems = getSelectItems(SQL.sql);
        for (String item :
                selectItems) {
            System.out.println("item:"+item);
        }
    }

    private static List<String> getSelectItems(String sql) {
        TTransport transport = null;
        List<String> selectItems = new ArrayList<>();
        try {
            transport = new TSocket(SERVER_IP, SERVER_PORT, TIMEOUT);
            TProtocol tProtocol = new TBinaryProtocol(transport);
            SQLParser.Client client = new SQLParser.Client(tProtocol);
            transport.open();
            selectItems = client.getSelectItems(sql);
        } catch (Exception e) {

        }
        return selectItems;
    }
}
