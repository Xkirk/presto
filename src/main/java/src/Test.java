package src;

import src.com.mafengwo.PrestoResult;

import java.util.HashMap;
/**
 * Created by kirk on 2017/6/29.
 */
public class Test {
    public static void main(String[] args) throws Exception{
        String sql = args[0].toString();
        PrestoClient pc = new PrestoClient();
        PrestoResult prestoresult = pc.query(sql,"xujing");
        System.out.println("Status:"+prestoresult.getStatus());
        if("failed".equals(prestoresult.getStatus())){
            System.out.println("Exception:"+prestoresult.getException());
            return;
        }
        System.out.println(prestoresult.getRsList().size()+"ROWS");
        for (HashMap<String, String> rsMap:
        prestoresult.getRsList()){
            System.out.println("hour:"+rsMap.get("hour")+";"+"cnt:"+rsMap.get("_col1"));
        }
    }
}
