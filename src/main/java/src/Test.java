package src;

import java.sql.ResultSet;
import java.util.ArrayList;

/**
 * Created by kirk on 2017/6/29.
 */
public class Test {
    public static void main(String[] args) throws Exception{
        String sql = "select hour,count(*) from server_event_parquet where dt='20170629' group by hour order by hour";
//        ArrayList<Object> objList = PrestoConnector.prestoClient(args[0].toString());
        PrestoConnector.prestoClient(args[0].toString(),"hive","default","xujing");
//        for (Object obj:objList) {
//            ResultSet rs = (ResultSet)obj;
//            System.out.println(rs.getString(1)+":"+rs.getString(2));
//        }
    }
}
