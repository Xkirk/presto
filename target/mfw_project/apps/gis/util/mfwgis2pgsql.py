#!/usr/bin/python
#coding:utf-8
# 将MFW目前的Region文本数据导出为SQL数据，为PostGIS使用

import sys
import os
import re

def dump2sql(filename, region_type):
    sqlstr = ""
    n_polygon = 0
    first_point = ""
    region_id = 0
    p_id = 0
    mddid = 0
    area_id = 0

    for line in open(filename, 'r'):
        m = re.match(r'^id\s+(\d+)\s+(\d+)$', line)
        if (m):
            if sqlstr:
                if n_polygon == 0:
                    print ("INSERT INTO mfw_region (region_id,mddid,area_id,p_id,type) VALUES(%s,%s,%s,%s,%s);" % (
                        region_id, mddid, area_id, p_id, region_type))
                if n_polygon > 0:
                    sqlstr += "," + first_point + "))')]),4326));"
                    first_point = ''
                    print sqlstr
                n_polygon = 0

            region_id = 0
            mddid = 0
            area_id = 0
            p_id = m.group(2)
            if (region_type == 1):
                region_id = m.group(1)
            elif (region_type == 2):
                mddid = m.group(1)
            elif (region_type == 4):
                area_id = m.group(1)

            sqlstr = "INSERT INTO mfw_region (region_id, mddid, area_id, p_id, type, geom) VALUES(%s,%s,%s,%s,%s,ST_SetSRID(ST_Collect(ARRAY[" % (
                region_id, mddid, area_id, p_id, region_type)
        else:
            m = re.match(r'^(outer|inner)$', line)
            if (m):
                if m.group(1) == 'outer':
                    if n_polygon == 0:
                        sqlstr += "ST_GeomFromText('POLYGON(("
                    else:
                        sqlstr += "," + first_point + \
                            "))'), ST_GeomFromText('POLYGON(("
                    n_polygon += 1
                    first_point = ''
                if m.group(1) == "inner":
                    sqlstr += "," + first_point + "),("
                    first_point = ''
            else:
                m = re.match(
                    r'^([-]{0,1}\d+[\.\d+]*\s+[-]{0,1}\d+[\.\d+]*)$', line)
                if (m):
                    if first_point == "":
                        first_point = m.group(1)
                        sqlstr += m.group(1)
                    else:
                        sqlstr += "," + m.group(1)

    sqlstr += "," + first_point + "))')]),4326));"
    print sqlstr

dump2sql("gis_region_boundary.txt", 1)
dump2sql("gis_mdd_boundary.txt", 2)
dump2sql("gis_area_boundary.txt", 4)
