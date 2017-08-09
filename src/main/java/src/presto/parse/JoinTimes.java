package src.presto.parse;

import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.tree.*;
import src.presto.test.SQL;

import java.util.List;
import java.util.Optional;

/**
 * Created by kirk on 2017/7/19.
 */
public class JoinTimes {

    public static int joinTimes = 0;//SQL中所有将被查询的表的List

//    public static void main(String[] args) {
//        String sql = SQL.sql;
//        SqlParser parser = new SqlParser();
//        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
//        if (query != null) {
//            parseQuery(query);
//        }
//        System.out.println("JoinTimes:" + joinTimes);
//    }

    public static int getJoinTimes(String sql) {
        joinTimes = 0;
        SqlParser parser = new SqlParser();
        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
        if (query != null) {
            parseQuery(query);
        }
        return joinTimes;
    }

    /**
     * 解析With中的内容
     *
     * @param with
     */
    private static void parseWith(Optional<With> with) {
        if (with.isPresent()) {
            List<WithQuery> queries = with.get().getQueries();
            for (WithQuery withQuery : queries) {
                QueryBody queryBody = withQuery.getQuery().getQueryBody();
                if (queryBody instanceof QuerySpecification) {
                    QuerySpecification querySpecification = (QuerySpecification) queryBody;
                    Optional<Relation> from = querySpecification.getFrom();
                    parseFrom(from);
                }
                if (queryBody instanceof Union) {
                    Union union = (Union) queryBody;
                    parseUnion(union);
                }
            }
        }
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
        Optional<With> with = query.getWith();
        if (with != null) {
            parseWith(with);
        }
    }

    /**
     * 解析Join下的内容
     * *******************|--类型是Table调用parseTable获取TableName
     * ******|--getLeft --|--类型是Join调用Join
     * ******|            |--类型是AliasedRelation 调用parseAliasedRelation  |--调用parseTableSubQuery
     * Join**|
     * ******|            |--类型是Table调用parseTable获取TableName
     * ******|--getRight--|--类型是Join调用Join
     * *******************|--类型是AliasedRelation 调用parseAliasedRelation  |--调用parseTableSubQuery
     *
     * @param join
     */
    private static void parseJoin(Join join) {
        joinTimes += 1;
        //调用getLeft
        Relation aliasedLeft = join.getLeft();
        if (aliasedLeft instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) aliasedLeft;
            parseAliasedRelation(aliasedRelation);
        }
        if (aliasedLeft instanceof Join) {
            joinTimes += 1;
            parseJoinGetLeft((Join) aliasedLeft);
            parseJoinGetRight((Join) aliasedLeft);
        }

        //调用getRight
        Relation aliasedRight = join.getRight();
        if (aliasedRight instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) aliasedRight;
            parseAliasedRelation(aliasedRelation);
        }
        if (aliasedRight instanceof Join) {
            joinTimes += 1;
            parseJoinGetLeft((Join) aliasedRight);
            parseJoinGetRight((Join) aliasedRight);
        }
    }

    /**
     * 解析从Join getLeft接口返回的Join
     * <p>
     * *******************|-->类型是Table调用parseTable获取TableName
     * parseJoinGetLeft***|-->类型是Join调用parseJoin
     * *******************|-->类型是AliasedRelation 调用parseAliasedRelation  |-->调用parseTableSubQuery
     *
     * @param join
     */
    private static void parseJoinGetLeft(Join join) {
        Relation relation = join.getLeft();
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            parseAliasedRelation(aliasedRelation);
        }
        if (relation instanceof Join) {
            Join nextLeft = (Join) relation;
            parseJoin(nextLeft);
        }
    }

    /**
     * 解析从Join getRight接口返回的Join
     * ********************|--类型是Table调用parseTable获取TableName
     * parseJoinGetRight-->|--类型是Join调用parseJoin
     * ********************|--类型是AliasedRelation 调用parseAliasedRelation  |--调用parseTableSubQuery
     *
     * @param join
     */
    private static void parseJoinGetRight(Join join) {
        Relation relation = join.getRight();
        if (relation instanceof AliasedRelation) {
            AliasedRelation aliasedRelation = (AliasedRelation) relation;
            parseAliasedRelation(aliasedRelation);
        }
        if (relation instanceof Join) {
            Join nextRight = (Join) relation;
            parseJoin(nextRight);
        }
    }

    /**
     * 递归调用parseUnion,
     * 若Union的下层仍然是Union类型,则继续向下调用
     *
     * @param union
     */
    public static void parseUnion(Union union) {

        List<Relation> relations = union.getRelations();
        for (Relation relation :
                relations) {
            if (relation instanceof QuerySpecification) {
                QuerySpecification querySpecification = (QuerySpecification) relation;
                Optional<Relation> from = querySpecification.getFrom();
                if (from != null) {
                    parseFrom(from);
                }
            }
            if (relation instanceof Union) {
                Union nextUnion = (Union) relation;
                parseUnion(nextUnion);
            }
            if (relation instanceof AliasedRelation) {
                AliasedRelation aliasedRelation = (AliasedRelation) relation;
                parseAliasedRelation(aliasedRelation);
            }
        }
    }

    /**
     * 解析From
     *
     * @param from
     */
    private static void parseFrom(Optional<Relation> from) {
        if (from.isPresent()) {
            Relation relation = from.get();
            if (relation instanceof AliasedRelation) {
                AliasedRelation aliasedRelation = (AliasedRelation) relation;
                parseAliasedRelation(aliasedRelation);
            }
            if (relation instanceof Join) {
                Join join = (Join) relation;
                parseJoin(join);
            }
            if (relation instanceof TableSubquery) {
                TableSubquery tableSubquery = (TableSubquery) relation;
                parseTableSubQuery(tableSubquery);
            }
        }
    }

    /**
     * 解析TableSubQuery
     *
     * @param tableSubquery
     */
    private static void parseTableSubQuery(TableSubquery tableSubquery) {
        Query query = tableSubquery.getQuery();
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
            Optional<Relation> from = querySpecification.getFrom();
            if (from != null) {
                parseFrom(from);
            }
            Optional<Expression> where = querySpecification.getWhere();
            if (where != null) {
                parseWhere(where);
            }
        }
        if (queryBody instanceof Union) {
            Union union = (Union) queryBody;
            parseUnion(union);
        }
    }

    /**
     * 解析AliasedRelation
     * <p>
     * aliasedRelation|
     *
     * @param aliasedRelation
     */
    private static void parseAliasedRelation(AliasedRelation aliasedRelation) {
        if (aliasedRelation.getRelation() instanceof TableSubquery) {
            TableSubquery tableSubquery = (TableSubquery) aliasedRelation.getRelation();
            parseTableSubQuery(tableSubquery);
        }
    }

    /**
     * 解析where
     *
     * @param where
     */
    private static void parseWhere(Optional<Expression> where) {
        if (where.isPresent()) {
            Expression expression = where.get();
            if (expression instanceof LogicalBinaryExpression) {

            }
            if (expression instanceof BetweenPredicate) {

            }
            if (expression instanceof InPredicate) {
                InPredicate inPredicate = (InPredicate) expression;
                Expression valueList = inPredicate.getValueList();
                if (valueList instanceof SubqueryExpression) {
                    SubqueryExpression subqueryExpression = (SubqueryExpression) valueList;
                    Query subQuery = subqueryExpression.getQuery();
                    if (subQuery != null) {
                        parseQuery(subQuery);
                    }
                }
            }
            if (expression instanceof LikePredicate) {

            }
            if (expression instanceof ComparisonExpression) {

            }
        }
    }

}