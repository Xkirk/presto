package src.presto.parse;

import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.tree.*;
import src.presto.schema.DateScopeMap;
import src.presto.schema.ScopeList;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;

/**
 * Created by kirk on 2017/7/17.
 */
public class ParseDtScope {
    private static final String EQUAL = "EQUAL";
    private static final String LESS_THAN = "LESS_THAN";
    private static final String LESS_THAN_OR_EQUAL = "LESS_THAN_OR_EQUAL";
    private static final String GREATER_THAN = "GREATER_THAN";
    private static final String GREATER_THAN_OR_EQUAL = "GREATER_THAN_OR_EQUAL";
    private static HashMap<String, ScopeList> dtScopte;

    public static void main(String[] args) throws ParseException {
        String sql = SQL.sql1;
        SqlParser parser = new SqlParser();
        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
        DateScopeMap dateScopeMap = new DateScopeMap();
        if (query != null) {
            HashMap<String, ScopeList> dtscope = new HashMap<>();
            dtscope.put("undefine", new ScopeList());
            dateScopeMap.setDtScope(dtscope);
            parseQuery(query, dateScopeMap);
            dateScopeMap.getAliasMap();
            dateScopeMap.getDtScope();
        }
        Set<String> keyList = dateScopeMap.getDtScope().keySet();
        HashMap<String, ScopeList> dtScope = dateScopeMap.getDtScope();
        for (String key : keyList) {
            ScopeList scopeList = dtScope.get(key);
            List<Long> strList = scopeList.getStrDate();
            List<Long> endList = scopeList.getEndDate();
            if (strList.size() > endList.size()) {
                Date date = new Date();
                SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd");
                endList.add(stringToUnixTime(sdf.format(date)));
            }
            if (strList.size() < endList.size()) {
                strList.add((long) 0);
            }
            long scope = 0;
            for (int i = 0; i < strList.size(); i++) {
                scope += endList.get(i) - strList.get(i) + oneDay();
            }
            System.out.println(key + ":" + String.valueOf(scope / 3600 / 24));
        }
    }

    /**
     * 解析Query
     *
     * @param query
     */
    private static void parseQuery(Query query, DateScopeMap dateScopeMap) {
        QueryBody queryBody = query.getQueryBody();
        if (queryBody != null) {
            parseQueryBody(queryBody, dateScopeMap);
        }
        Optional<With> with = query.getWith();
        if (with != null) {
            parseWith(with, dateScopeMap);
        }
    }

    /**
     * 递归解析QueryBody
     *
     * @param queryBody
     */
    private static void parseQueryBody(QueryBody queryBody, DateScopeMap dateScopeMap) {
        if (queryBody instanceof QuerySpecification) {
            QuerySpecification querySpecification = (QuerySpecification) queryBody;
            Optional<Relation> from = querySpecification.getFrom();
            if (from != null) {
                parseFrom(from, dateScopeMap);//此处调用意义在于递归向下寻找子查询,若存在子查询则会再次调用QueryBody的解析
            }
            Optional<Expression> where = querySpecification.getWhere();
            if (where != null && where.isPresent()) {
                parseWhere(where, from, dateScopeMap);
            }
        }
        if (queryBody instanceof Union) {
            Union union = (Union) queryBody;
            parseUnion(union, dateScopeMap);
        }
    }

    /**
     * 解析With中的内容
     *
     * @param with
     */
    private static void parseWith(Optional<With> with, DateScopeMap dateScopeMap) {
        if (with.isPresent()) {
            List<WithQuery> queries = with.get().getQueries();
            for (WithQuery withQuery : queries) {
                QueryBody queryBody = withQuery.getQuery().getQueryBody();
                if (queryBody instanceof QuerySpecification) {
                    parseQueryBody(queryBody, dateScopeMap);
                }
                if (queryBody instanceof Union) {
                    Union union = (Union) queryBody;
                    parseUnion(union, dateScopeMap);
                }
            }
        }
    }

    /**
     * 解析From
     *
     * @param from
     */
    private static void parseFrom(Optional<Relation> from, DateScopeMap dateScopeMap) {
        if (from.isPresent()) {
            Relation relation = from.get();
            if (relation instanceof AliasedRelation) {
                AliasedRelation aliasedRelation = (AliasedRelation) relation;
                parseAliasedRelation(aliasedRelation, dateScopeMap);
            }
            if (relation instanceof Join) {
                Join join = (Join) relation;
                parseJoin(join, dateScopeMap);
            }
            if (relation instanceof TableSubquery) {
                TableSubquery tableSubquery = (TableSubquery) relation;
                parseTableSubQuery(tableSubquery, dateScopeMap);
            }
        }
    }

    /**
     * 解析TableSubQuery
     *
     * @param tableSubquery
     * @param dateScopeMap
     */
    private static void parseTableSubQuery(TableSubquery tableSubquery, DateScopeMap dateScopeMap) {
        Query query = tableSubquery.getQuery();
        QueryBody queryBody = query.getQueryBody();
        if (queryBody != null) {
            parseQueryBody(queryBody, dateScopeMap);
        }
    }

    //-----------------------递归解析Join提取Join下存在的子查询,并进行Query层级的递归 Start--------------------------


    /**
     * 解析Join下的内容
     * ******|--getLeft --|--类型是Join调用Join
     * ******|            |--类型是AliasedRelation 调用parseAliasedRelation  |--调用parseTableSubQuery
     * Join**|
     * ******|--getRight--|--类型是Join调用Join
     * *******************|--类型是AliasedRelation 调用parseAliasedRelation  |--调用parseTableSubQuery
     *
     * @param join
     * @param dateScopeMap
     */
    private static void parseJoin(Join join, DateScopeMap dateScopeMap) {

        //调用getLeft
        Relation aliasedLeft = join.getLeft();
        if (aliasedLeft instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) aliasedLeft;
            parseAliasedRelation(aliasedRelation, dateScopeMap);
        }
        if (aliasedLeft instanceof Join) {
            parseJoinGetLeft((Join) aliasedLeft, dateScopeMap);
            parseJoinGetRight((Join) aliasedLeft, dateScopeMap);
        }

        //调用getRight
        Relation aliasedRight = join.getRight();
        if (aliasedRight instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) aliasedRight;
            parseAliasedRelation(aliasedRelation, dateScopeMap);
        }
        if (aliasedRight instanceof Join) {
            parseJoinGetLeft((Join) aliasedRight, dateScopeMap);
            parseJoinGetRight((Join) aliasedRight, dateScopeMap);
        }
    }

    /**
     * 解析从Join getLeft接口返回的Join
     * <p>
     * parseJoinGetLeft***|-->类型是Join调用parseJoin
     * *******************|-->类型是AliasedRelation 调用parseAliasedRelation  |-->调用parseTableSubQuery
     *
     * @param join
     */
    private static void parseJoinGetLeft(Join join, DateScopeMap dateScopeMap) {
        Relation relation = join.getLeft();
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            parseAliasedRelation(aliasedRelation, dateScopeMap);
        }
        if (relation instanceof Join) {
            Join nextLeft = (Join) relation;
            parseJoin(nextLeft, dateScopeMap);
        }
    }

    /**
     * 解析从Join getRight接口返回的Join
     * parseJoinGetRight-->|--类型是Join调用parseJoin
     * ********************|--类型是AliasedRelation 调用parseAliasedRelation  |--调用parseTableSubQuery
     *
     * @param join
     */
    private static void parseJoinGetRight(Join join, DateScopeMap dateScopeMap) {
        Relation relation = join.getRight();
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            parseAliasedRelation(aliasedRelation, dateScopeMap);
        }
        if (relation instanceof Join) {
            Join nextRight = (Join) relation;
            parseJoin(nextRight, dateScopeMap);
        }
    }

    //-----------------------递归解析Join提取Join下存在的子查询,并进行Query层级的递归  End-----------------------|

    /**
     * 递归调用parseUnion,
     * 若Union的下层仍然是Union类型,则继续向下调用
     *
     * @param union
     */
    private static void parseUnion(Union union, DateScopeMap dateScopeMap) {
        List<Relation> relations = union.getRelations();
        for (Relation relation : relations) {
            if (relation instanceof QuerySpecification) {
                QuerySpecification querySpecification = (QuerySpecification) relation;
                parseQueryBody(querySpecification, dateScopeMap);
            }
            if (relation instanceof Union) {
                Union nextUnion = (Union) relation;
                parseUnion(nextUnion, dateScopeMap);
            }
            if (relation instanceof AliasedRelation) {
                AliasedRelation aliasedRelation = (AliasedRelation) relation;
                parseAliasedRelation(aliasedRelation, dateScopeMap);
            }
        }
    }


    /**
     * 解析AliasedRelation
     * aliasedRelation
     *
     * @param aliasedRelation
     * @param dateScopeMap
     */
    private static void parseAliasedRelation(AliasedRelation aliasedRelation, DateScopeMap dateScopeMap) {
        if (aliasedRelation.getRelation() instanceof TableSubquery) {
            TableSubquery tableSubquery = (TableSubquery) aliasedRelation.getRelation();
            parseTableSubQuery(tableSubquery, dateScopeMap);
        }
    }

    /**
     * 解析where
     *
     * @param where
     */
    private static void parseWhere(Optional<Expression> where, Optional<Relation> from, DateScopeMap dateScopeMap) {
        Expression expression = where.get();
        Relation relation = from.get();
        if (dateScopeMap.getAliasMap() != null) dateScopeMap.getAliasMap().clear();
        parseAliasedListByFrom(relation, dateScopeMap);
        if (expression instanceof LogicalBinaryExpression) {//如果where下是逻辑二叉树,则解析逻辑二叉树
            LogicalBinaryExpression lgcBinExps = (LogicalBinaryExpression) expression;
            parseLogicalBinaryExpression(dateScopeMap, lgcBinExps);
        } else {//如果where下只有一个条件表达式,直接解析条件表达式
            parseConditions(dateScopeMap, expression);
        }
    }

    /**
     * 此方法将解析一个别名与表名(子查询的映射)
     *
     * @param relation
     * @param dateScopeMap
     */
    private static void parseAliasedListByFrom(Relation relation, DateScopeMap dateScopeMap) {
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            setAliasdList(aliasedRelation, dateScopeMap);
        }
        if (relation instanceof Join) {
            Join join = (Join) relation;
            setAliasedListByJoin(join, dateScopeMap);
        }
    }

    /**
     * 将AliasedRelation下的别名和表名装载到AliasMap
     * 仅当AliasedRelation下的relation为Table才装载.若为子查询则会在其他递归层级中装载
     *
     * @param aliasedRelation
     */
    private static void setAliasdList(AliasedRelation aliasedRelation, DateScopeMap dateScopeMap) {
        if (aliasedRelation.getRelation() instanceof Table) {
            Table table = (Table) aliasedRelation.getRelation();
            String tableName = table.getName().toString();
            String alias = aliasedRelation.getAlias();
            dateScopeMap.getAliasMap().put(alias, tableName);
        }
    }

    //-----------------------解析Join下的AliasdRelation装载到AliasMap递归开始-------------------------------------

    /**
     * 递归Join,以解析出当前where层级下的 别名-表名AliasMap
     *
     * @param join
     * @param dateScopeMap
     */
    private static void setAliasedListByJoin(Join join, DateScopeMap dateScopeMap) {
        //调用getLeft
        Relation aliasedLeft = join.getLeft();
        if (aliasedLeft instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) aliasedLeft;
            setAliasdList(aliasedRelation, dateScopeMap);
        }
        if (aliasedLeft instanceof Join) {
            setAliasMapByJoinLeft((Join) aliasedLeft, dateScopeMap);
            setAliasMapByJoinRight((Join) aliasedLeft, dateScopeMap);
        }

        //调用getRight
        Relation aliasedRight = join.getRight();
        if (aliasedRight instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) aliasedRight;
            setAliasdList(aliasedRelation, dateScopeMap);
        }
        if (aliasedRight instanceof Join) {
            setAliasMapByJoinLeft((Join) aliasedRight, dateScopeMap);
            setAliasMapByJoinRight((Join) aliasedRight, dateScopeMap);
        }
    }

    /**
     * 解析从Join getLeft接口返回的Join
     * <p>
     * parseJoinGetLeft***|-->类型是Join调用setAliasedListByJoin
     * *******************|-->类型是AliasedRelation 调用setAliasdList
     *
     * @param join
     */
    private static void setAliasMapByJoinRight(Join join, DateScopeMap dateScopeMap) {
        Relation relation = join.getLeft();
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            setAliasdList(aliasedRelation, dateScopeMap);
        }
        if (relation instanceof Join) {
            Join nextLeft = (Join) relation;
            setAliasedListByJoin(nextLeft, dateScopeMap);
        }
    }

    /**
     * 解析从Join getRight接口返回的Join
     * parseJoinGetRight-->|--类型是Join调用setAliasedListByJoin
     * ********************|--类型是AliasedRelation 调用setAliasdList
     *
     * @param join
     */
    private static void setAliasMapByJoinLeft(Join join, DateScopeMap dateScopeMap) {
        Relation relation = join.getRight();
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            setAliasdList(aliasedRelation, dateScopeMap);
        }
        if (relation instanceof Join) {
            Join nextRight = (Join) relation;
            setAliasedListByJoin(nextRight, dateScopeMap);
        }
    }

    //-----------------------解析Join下的AliasdRelation装载到AliasMap递归结束-------------------------------------


    //-----------------------------------------表达式解析部分开始-------------------------------------------------

    /**
     * 解析Where下的表达式
     *
     * @param dateScopeMap
     * @param comparisonExpression
     */
    private static void parseComparisonExpression(DateScopeMap dateScopeMap, ComparisonExpression comparisonExpression) {

        Expression leftCompExps = comparisonExpression.getLeft();

        //表达式左侧是Identifier类型且为dt,解析右侧表达式
        if (isDt(leftCompExps)) {
            parseStringLiteral(dateScopeMap, comparisonExpression);
        }
    }

    /**
     * 解析Between语法
     *
     * @param betweenPredicate
     */
    private static void parseBetweenPredicate(DateScopeMap dateScopeMap, BetweenPredicate betweenPredicate) {
        Expression value = betweenPredicate.getValue();
        if (isDt(value)) {//仅当遇到时间字段时,获取between的时间段
            String tableName = "undefine";
            if (value instanceof DereferenceExpression) {
                DereferenceExpression dereferenceExpression = (DereferenceExpression) value;
                Identifier alias = (Identifier) dereferenceExpression.getBase();
                tableName = dateScopeMap.getTableNameByAlias(alias.getName());
            }
            HashMap<String, ScopeList> dtScopte = dateScopeMap.getDtScope();
            ScopeList scopeList = dtScopte.get(tableName) == null ? new ScopeList() : dtScopte.get(tableName);
            Expression minExp = betweenPredicate.getMin();
            Expression maxExp = betweenPredicate.getMax();
            if (minExp instanceof StringLiteral && maxExp instanceof StringLiteral) {
                String min = ((StringLiteral) minExp).getValue();
                String max = ((StringLiteral) maxExp).getValue();
                try {
                    scopeList.getStrDate().add(stringToUnixTime(min));
                    scopeList.getEndDate().add(stringToUnixTime(max));
                    dtScopte.put(tableName, scopeList);
                } catch (ParseException e) {
                    e.printStackTrace();
                }
            }
        }
    }


    /**
     * 解析比较表达式中的Value
     *
     * @param dateScopeMap
     * @param comparisonExpression
     */
    private static void parseStringLiteral(DateScopeMap dateScopeMap, ComparisonExpression comparisonExpression) {
        if (comparisonExpression.getRight() instanceof StringLiteral) {
            StringLiteral stringLiteral = (StringLiteral) comparisonExpression.getRight();
            Expression leftCompExps = comparisonExpression.getLeft();
            String tableName = "undefine";
            String type = comparisonExpression.getType().toString();
            String dateValue = stringLiteral.getValue();
            if (leftCompExps instanceof DereferenceExpression) {
                DereferenceExpression dereferenceExpression = (DereferenceExpression) leftCompExps;
                Identifier alias = (Identifier) dereferenceExpression.getBase();
                tableName = dateScopeMap.getTableNameByAlias(alias.getName());
            }
            HashMap<String, ScopeList> dtScopte = dateScopeMap.getDtScope();
            ScopeList scopeList = dtScopte.get(tableName) == null ? new ScopeList() : dtScopte.get(tableName);
            try {
                if (type.equalsIgnoreCase(EQUAL)) {
                    scopeList.getStrDate().add(stringToUnixTime(dateValue));
                    scopeList.getEndDate().add(stringToUnixTime(dateValue));
                }
                if (type.equalsIgnoreCase(GREATER_THAN)) {
                    scopeList.getStrDate().add(stringToUnixTime(dateValue) + oneDay());
                }
                if (type.equalsIgnoreCase(GREATER_THAN_OR_EQUAL)) {
                    scopeList.getStrDate().add(stringToUnixTime(dateValue));
                }
                if ((type.equalsIgnoreCase(LESS_THAN))) {
                    scopeList.getEndDate().add(stringToUnixTime(dateValue) - oneDay());//
                }
                if (type.equalsIgnoreCase(LESS_THAN_OR_EQUAL)) {
                    scopeList.getEndDate().add(stringToUnixTime(dateValue));
                }
                dtScopte.put(tableName, scopeList);
            } catch (ParseException e) {
                e.printStackTrace();
            }
        }
    }
    //-----------------------------------------表达式解析部分结束-----------------------------------------------

    //-------------------------------------逻辑树二叉树递归解析部分开始-------------------------------------------

    /**
     * 解析逻辑语法二叉树
     *
     * @param lgcBinExps
     */
    private static void parseLogicalBinaryExpression(DateScopeMap dateScopeMap, LogicalBinaryExpression lgcBinExps) {

        Expression left = lgcBinExps.getLeft();
        if (left instanceof LogicalBinaryExpression) {//如果左侧仍是逻辑树,则向下递归
            LogicalBinaryExpression next = (LogicalBinaryExpression) left;
            parseLgcBinLeft(dateScopeMap, next);
            parseLgcBinRight(dateScopeMap, next);
        } else {//若已经是表达式,则解析表达式
            parseConditions(dateScopeMap, left);
        }

        Expression right = lgcBinExps.getRight();
        if (right instanceof LogicalBinaryExpression) {//如果右侧仍是逻辑树,则向下递归
            LogicalBinaryExpression next = (LogicalBinaryExpression) right;
            parseLgcBinLeft(dateScopeMap, next);
            parseLgcBinRight(dateScopeMap, next);
        } else {//若已经是表达式结构,则解析表达式
            parseConditions(dateScopeMap, right);
        }
    }

    /**
     * 递归解析左侧逻辑树
     *
     * @param dateScopeMap
     * @param lgcBinExps
     */
    private static void parseLgcBinLeft(DateScopeMap dateScopeMap, LogicalBinaryExpression lgcBinExps) {
        Expression expression = lgcBinExps.getLeft();
        if (expression instanceof LogicalBinaryExpression) {
            LogicalBinaryExpression next = (LogicalBinaryExpression) expression;
            parseLogicalBinaryExpression(dateScopeMap, next);
        } else {
            parseConditions(dateScopeMap, expression);
        }
    }

    /**
     * 递归解析右侧逻辑树
     *
     * @param dateScopeMap
     * @param lgcBinExps
     */
    private static void parseLgcBinRight(DateScopeMap dateScopeMap, LogicalBinaryExpression lgcBinExps) {
        Expression expression = lgcBinExps.getRight();
        if (expression instanceof LogicalBinaryExpression) {
            LogicalBinaryExpression next = (LogicalBinaryExpression) expression;
            parseLogicalBinaryExpression(dateScopeMap, next);
        } else {
            parseConditions(dateScopeMap, expression);
        }
    }
    //-------------------------------------逻辑树二叉树递归解析部分结束-------------------------------------------

    /**
     * 解析Where中的条件表达式
     *
     * @param dateScopeMap
     * @param lgcBinExps
     */
    private static void parseConditions(DateScopeMap dateScopeMap, Expression lgcBinExps) {
        //'between'关键字
        if (lgcBinExps instanceof BetweenPredicate) {
            BetweenPredicate betweenPredicate = (BetweenPredicate) lgcBinExps;
            parseBetweenPredicate(dateScopeMap, betweenPredicate);
        }
        //'比较'('><=')关键字
        if (lgcBinExps instanceof ComparisonExpression) {
            parseComparisonExpression(dateScopeMap, (ComparisonExpression) lgcBinExps);
        }
        //'in'关键字
        if (lgcBinExps instanceof InPredicate) {
            InPredicate inPredicate = (InPredicate) lgcBinExps;
            Expression valueList = inPredicate.getValueList();
            if (valueList instanceof SubqueryExpression) {
                SubqueryExpression subqueryExpression = (SubqueryExpression) valueList;
                Query subQuery = subqueryExpression.getQuery();
                if (subQuery != null) {
                    parseQuery(subQuery, dateScopeMap);
                }
            }
        }
        //Like关键字
        if (lgcBinExps instanceof LikePredicate) {

        }

    }

    //---------------------------------辅助方法小分队   Start--------------------------------

    /**
     * 判断是否是时间字段dt
     *
     * @param expression
     * @return
     */
    private static boolean isDt(Expression expression) {
        String dt = "";
        if (expression instanceof Identifier) {
            Identifier identifier = (Identifier) expression;
            dt = identifier.getName();
        }
        if (expression instanceof DereferenceExpression) {
            DereferenceExpression dereferenceExpression = (DereferenceExpression) expression;
            dt = dereferenceExpression.getFieldName();
        }
        return dt.equalsIgnoreCase("dt");
    }

    /**
     * Unix时间戳一天的跨度
     *
     * @return
     * @throws ParseException
     */
    private static long oneDay() throws ParseException {
        return 1 * 3600 * 24;
    }

    /**
     * 将日期装换为Unix时间戳 (秒)
     *
     * @param date
     * @return
     * @throws ParseException
     */
    private static long stringToUnixTime(String date) throws ParseException {
        SimpleDateFormat simpleDateFormat = new SimpleDateFormat("yyyyMMdd");
        long unixTime = simpleDateFormat.parse(date).getTime() / 1000;
        return unixTime;
    }
    //---------------------------------辅助方法小分队   End--------------------------------

}

