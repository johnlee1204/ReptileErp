SELECT 
    t.NAME AS TableName,
    i.name as indexName,
    sum(p.rows) as RowCounts,
    sum(a.total_pages) as TotalPages, 
    sum(a.used_pages) as UsedPages, 
    sum(a.data_pages) as DataPages,
    (sum(a.total_pages) * 8) / 1024 as TotalSpaceMB, 
    (sum(a.used_pages) * 8) / 1024 as UsedSpaceMB, 
    (sum(a.data_pages) * 8) / 1024 as DataSpaceMB
FROM 
    sys.tables t
INNER JOIN      
    sys.indexes i ON t.OBJECT_ID = i.object_id
INNER JOIN 
    sys.partitions p ON i.object_id = p.OBJECT_ID AND i.index_id = p.index_id
INNER JOIN 
    sys.allocation_units a ON p.partition_id = a.container_id
WHERE 
    t.NAME NOT LIKE 'dt%' AND
    i.OBJECT_ID > 255 AND   
    i.index_id <= 1
GROUP BY 
    t.NAME, i.object_id, i.index_id, i.name 
ORDER BY 
    --object_name(i.object_id) 
     sum(a.total_pages) desc
     
     
--delete from TMPslsCommView_rpsosm

truncate table jomast
truncate table joitem
truncate table somast
truncate table soitem
truncate table jodrtg

truncate table armast
truncate table shmast
truncate table apitem
truncate table apmast
truncate table sywlnk
truncate table QCRESULT
truncate table sorels
truncate table jodbom
truncate table syecaudt
truncate table sochng
truncate table intran
truncate table slcdpc
truncate table poitem
truncate table sodbom
truncate table qtdbom
truncate table TMPslsCommView
truncate table prgrn
truncate table pomast
truncate table SLCDPM_EXT
truncate table SOMAST_EXT
truncate table aritem
truncate table syaddr
truncate table shitem
truncate table qtitem
truncate table sodrtg
truncate table jopact
truncate table qtdrtg
truncate table JODRTG_EXT
truncate table glcshm
truncate table JOMAST_EXT
truncate table glcshi
truncate table gltran
truncate table slcdpm
truncate table ladetail
truncate table SYCSLM_EXT
truncate table qalotc
truncate table bcraw
truncate table ocdist
truncate table shsrce
truncate table glitem
truncate table QTITEM_EXT
truncate table sycslc

delete from rcitem


truncate table 

DBCC SHRINKDATABASE ('m2mdata01_testing',10)

DBCC SHRINKDATABASE (m2mdata01_testing, TRUNCATEONLY);  