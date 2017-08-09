namespace java src.thrift
namespace php Thrift.MQLParser
service SQLParser{
list<string> getSelectItems(1:string sql)
list<string> getTables(1:string sql)
list<string> getWithTables(1:string sql)
string limitVerify(1:string sql)
map<string,string> dateScope(1:string sql)
i32 scope(1:string sql)
i32 joinTimes(1:string sql)
}