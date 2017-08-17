package src.presto.parse;

import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.tree.*;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Optional;


/**
 * Created by kirk on 2017/7/19.
 */
public class LimitVerify {

    static Boolean limitFlag;
    public static String limitVerify(String sql) {
        limitFlag = true;
        SqlParser parser = new SqlParser();
        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
        if (query != null) {
            parseQuery(query);
        }
        if (!limitFlag) {
            sql = sql + "\n" + "limit 50001";
        }
        Date data = new Date();
        SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd kk:mm:ss");
        String dateAndTime = sdf.format(data);
        System.out.println(dateAndTime+"==>"+sql);
        return sql;
    }

    /**
     * 解析Query
     *
     * @param query
     */
    private static void parseQuery(Query query) {
        QueryBody queryBody = query.getQueryBody();
        if (queryBody != null) {
            parseQueryBody(queryBody);
        }
    }

    /**
     * 解析QueryBody
     *
     * @param queryBody
     */
    private static void parseQueryBody(QueryBody queryBody) {
        if (queryBody instanceof QuerySpecification) {
            QuerySpecification querySpecification = (QuerySpecification) queryBody;
            Optional<String> limit = querySpecification.getLimit();
            if (limit.isPresent()) {
                limitFlag = true;
            } else {
                limitFlag = false;
            }
        }
    }


}
