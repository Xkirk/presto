package src.presto.parse;

import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.tree.*;

import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

/**
 * Created by kirk on 2017/7/17.
 */
public class SelectItemParser {
    public static List<String> selectItemsList = new ArrayList<>();//SQL中所有将被查询字段的List

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
            Select select = querySpecification.getSelect();
            if (select != null) {
                List<SelectItem> selectItemsList = select.getSelectItems();
                for (SelectItem selectItem : selectItemsList) {
                    parseSelectItem(selectItem);
                }
            }
        }
    }

    /**
     * 解析SelectItem
     *
     * @param selectItem
     */

    private static void parseSelectItem(SelectItem selectItem) {
        if (selectItem instanceof SingleColumn) {
            SingleColumn singleColumn = (SingleColumn) selectItem;
            Optional<String> alias = singleColumn.getAlias();
            if (alias.isPresent()) { //如果定义了别名,显示别名
                selectItemsList.add(alias.get());
            } else {//未定义别名
                Expression expression = singleColumn.getExpression();
                if (expression instanceof Identifier) {//若查询目标是一个字段,则直接显示字段
                    Identifier identifier = (Identifier) expression;
                    selectItemsList.add(identifier.getName());
                } else {//若查询目标是一个表达式,则显示表达式
                    String exprs = expression.toString();
                    selectItemsList.add(exprs.replace("\"", ""));
                }
            }
        }
    }

    public static List<String> parseSelectItems(String sql) {
        SqlParser parser = new SqlParser();
        selectItemsList = new ArrayList<>();
        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
        if (query != null) {
            parseQuery(query);
        }
        return selectItemsList;
    }
//    public static void main(String[] args) {
//        String sql = SQL.sql;
//        SqlParser parser = new SqlParser();
//        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
//        if (query != null) {
//            parseQuery(query);
//        }
//        for (String item :
//                selectItemsList) {
//            System.out.println(item);
//        }
//    }

//}
}