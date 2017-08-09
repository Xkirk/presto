#!/usr/bin/python
# coding:utf-8
# 更新admin_level层级字段

import psycopg2
import psycopg2.extras


def findLevel(id, maxLevel):
    if (index_arr.has_key(id) == False):
        return -24

    if (maxLevel > 10):
        return -12

    if (index_arr[id] == 0):
        return 0
    else:
        return findLevel(index_arr[id], maxLevel+1) + 1


try:
    conn = psycopg2.connect(
        "dbname='osmgis' user='osmgis' host='127.0.0.1' password='mfwosmgis'")
except:
    print "I am unable to connect to the database"
    exit()

cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)

index_arr = {}

cur.execute(
    """SELECT id,region_id,p_id FROM mfw_region WHERE type = 1""")
rows = cur.fetchall()
for row in rows:
    index_arr[row['region_id']] = row['p_id']

for row in rows:
    admin_level = findLevel(row['region_id'], 0)
    r = cur.execute(
        """UPDATE mfw_region SET admin_level = %s WHERE id = %s;"""%(admin_level, row['id']))
    print row['region_id'], admin_level, r

conn.commit()
conn.close()
