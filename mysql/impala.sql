SELECT tab1.productid,
       tab7.status,
       tab7.salesusername,
       tab7.developerusername,
       tab5.value AS skuBusinessType,
       tab6.status,
       tab6.statusLevel,
       tab2.ce_billno,
       tab2.supplier_cnname,
       tab2.supplier_id,
       tab2.assigneddate AS '中标日期',
       tab10.infoReviewingname,
       tab10.infoModifiedby,
       tab10.infoModifiedOn,
       tab10.infoReviewReviewingname,
       tab10.infoReviewModifiedby,
       tab10.infoReviewModifiedOn,
       tab10.picReviewingname,
       tab10.picModifiedby,
       tab13.created_at,
       tab10.picReviewingname,
       tab10.picReviewReviewingname,
       tab10.picReviewModifiedby,
       tab10.picReviewModifiedOn,
       tab10.managerReviewingname,
       tab10.managerModifiedby,
       tab10.managerModifiedOn,
       tab10.firstVeroReviewingname,
       tab10.firstVeroModifiedby,
       tab10.firstVeroModifiedOn,
       tab10.secondVeroReviewingname,
       tab10.secondVeroModifiedby,
       tab10.secondVeroModifiedOn,
       tab12.收货时间,tab12.收货人,tab12.外观质检时间,tab12.外观质检人,tab12.广州收货人,tab12.广州收货时间,tab12.深圳发货时间,
       tab14.publishOn,tab14.publishBy,
       tab2.ceBillNoNew,
       tab2.toHZDate,
       tab2.toGZDate
FROM product_operation_listing_management.product_base_info AS tab1
         LEFT JOIN product_operation_listing_management.product_sku AS tab7 ON tab7.productid=tab1.productid
         LEFT JOIN product_operation_listing_management.product_sku AS tab4 ON tab4.productid=tab1.productid,
     tab4.attribute tab5
         LEFT JOIN
     (SELECT productId,
             max(if(reviewingName='info',modifiedOn,NULL)) AS infoModifiedOn,
             max(if(reviewingName='info',modifiedby,NULL)) AS infoModifiedby,
             max(if(reviewingName='info',reviewingname,NULL)) AS infoReviewingname,
             max(if(reviewingName='infoReview',modifiedOn,NULL)) AS infoReviewModifiedOn,
             max(if(reviewingName='infoReview',modifiedby,NULL)) AS infoReviewModifiedby,
             max(if(reviewingName='infoReview',reviewingname,NULL)) AS infoReviewReviewingname,
             max(if(reviewingName='pic',createdOn,NULL)) AS picModifiedOn,
             max(if(reviewingName='pic',modifiedby,NULL)) AS picModifiedby,
             max(if(reviewingName='pic',reviewingname,NULL)) AS picReviewingname,
             max(if(reviewingName='picReview',modifiedOn,NULL)) AS picReviewModifiedOn,
             max(if(reviewingName='picReview',modifiedby,NULL)) AS picReviewModifiedby,
             max(if(reviewingName='picReview',reviewingname,NULL)) AS picReviewReviewingname,
             max(if(reviewingName='manager',modifiedOn,NULL)) AS managerModifiedOn,
             max(if(reviewingName='manager',modifiedby,NULL)) AS managerModifiedby,
             max(if(reviewingName='manager',reviewingname,NULL)) AS managerReviewingname,
             max(if(reviewingName='firstVero',modifiedOn,NULL)) AS firstVeroModifiedOn,
             max(if(reviewingName='firstVero',modifiedby,NULL)) AS firstVeroModifiedby,
             max(if(reviewingName='firstVero',reviewingname,NULL)) AS firstVeroReviewingname,
             max(if(reviewingName='secondVero',modifiedOn,NULL)) AS secondVeroModifiedOn,
             max(if(reviewingName='secondVero',modifiedby,NULL)) AS secondVeroModifiedby,
             max(if(reviewingName='secondVero',reviewingname,NULL)) AS secondVeroReviewingname
      FROM
          (SELECT tab1.productId,
                  tab9.reviewingName,
                  tab9.modifiedBy,
                  tab9.createdOn,
                  tab9.modifiedOn
           FROM product_operation_listing_management.product_sku AS tab1,
                tab1.reviewingList tab9) AS a
      GROUP BY productId) AS tab10 ON tab10.productid=tab1.productid
         LEFT JOIN
     (SELECT tab3.skuid,
             max(tab3.status) AS status,
             max(tab3.statusLevel) AS statusLevel
      FROM product_operation_listing_management.sku_sale_status AS tab3
      GROUP BY tab3.skuid) AS tab6 ON tab6.skuid=tab1.productid
         LEFT JOIN
     (SELECT t1.sku_id,
             min(t1.ce_billno) AS ce_billno,
             max(t1.productListNo) AS productListNo,
             max(t1.assigneddate) AS assigneddate,
             max(t1.supplier_id) AS supplier_id,
             max(t1.supplier_cnname) AS supplier_cnname,
             if(min(t1.ce_billno)=max(t1.ce_billno),NULL,max(t1.ce_billno)) AS ceBillNoNew,
             min(t2.receiveon) AS toHZDate,
             min(t2.importon) AS toGZDate
      FROM
          (SELECT a1.ce_billno,
                  a1.sku_id,
                  a2.productListNo,
                  a3.assigneddate,
                  a4.supplier_id,
                  a5.supplier_cnname
           FROM ux168.ce_detail AS a1
                    LEFT JOIN
                (SELECT c.cebillno,
                        p.productListNo
                 FROM product_operation_listing_management.pa_product p,
                      p.cenumber c
                 WHERE p.status='numbered') AS a2 ON a1.ce_billno = a2.cebillno
                    LEFT JOIN ux168.product_development_list AS a3 ON a3.productListNo=a2.productListNo
                    LEFT JOIN ux168.ce_master AS a4 ON a1.ce_billno =a4.ce_billno
                    LEFT JOIN cets.base_supplierinfo AS a5 ON a5.supplier_id=a4.supplier_id
           WHERE a1.ux168_id IS NOT NULL and a1.deleted != 1 AND a1.ux168_id !=0 ) AS t1
              LEFT JOIN
          (SELECT b1.skuid,
                  b1.billno,
                  b1.receiveon,
                  b2.modifiedby,
                  b2.importon
           FROM
               (SELECT m1.billno,
                       m1.skuId,
                       m1.receiveon,
                       m1.receiveby
                FROM cets_node.new_receive_detail m1
                UNION SELECT m2.billno,
                             m2.skuId,
                             m2.receiveon,
                             m2.receiveby
                FROM cets_node.new_receive_detail_his m2) AS b1
                   LEFT JOIN
               (SELECT skuid,
                       billno,
                       importon,
                       modifiedby
                FROM cets_node.stock_sample_sku_keep_record
                WHERE category='dataTeam'  AND stockcode='SampleGZ' ) AS b2 ON b1.skuId=b2.skuid
                   AND b1.billno=b2.billno) AS t2 ON t2.skuid=t1.sku_id AND t2.billno=t1.ce_billno
      GROUP BY t1.sku_id
     ) AS tab2 ON tab2.sku_id=tab1.productid
         LEFT JOIN
     (SELECT tab1.skuid,
             tab1.billno,
             tab1.收货时间,tab1.收货人,tab1.外观质检时间,tab1.外观质检人,tab2.modifiedby AS '广州收货人',
             tab2.createdon AS '深圳发货时间',
             tab2.modifiedon AS '广州收货时间'
      FROM
          (SELECT t1.billno,
                  t1.skuId,
                  t1.receiveon AS '收货时间',
                  t1.receiveby AS '收货人',
                  t1.surfaceon AS '外观质检时间',
                  t1.surfaceby AS '外观质检人'
           FROM cets_node.new_receive_detail t1
           UNION SELECT t2.billno,
                        t2.skuId,
                        t2.receiveon AS '收货时间',
                        t2.receiveby AS '收货人',
                        t2.surfaceon AS '外观质检时间',
                        t2.surfaceby AS '外观质检人'
           FROM cets_node.new_receive_detail_his t2) AS tab1
              LEFT JOIN
          (SELECT t.skuid,
                  max(t.billno) AS billno,
                  max(t.createdon) AS createdon,
                  max(t.importon) AS modifiedon,
                  max(t.modifiedby) AS modifiedby
           FROM cets_node.stock_sample_sku_keep_record AS t
           WHERE t.category='dataTeam'  AND stockcode='SampleGZ'
           GROUP BY t.skuid) AS tab2 ON tab1.skuId=tab2.skuid
              AND tab1.billno=tab2.billno) AS tab12 ON tab12.skuid=tab1.productid
         AND tab12.billno=tab2.ce_billno
         LEFT JOIN
     (SELECT skuid,
             created_at
      FROM inventory.publish_logs
      WHERE event = 'D') tab13 ON tab13.skuid = tab1.productid
         LEFT JOIN (
         SELECT skuid,
                max(publishOn) AS publishOn,
                max(publishBy) AS publishBy
         FROM poms_listing_nest.pa_sku_material GROUP BY skuid
     ) AS tab14 ON tab1.productid=tab14.skuid
WHERE tab1.companyId='CR201706060001'
  AND tab7.productType= 'SKU'
  AND tab5.channel='local'
  AND tab5.label='Business Type'
  AND tab7.productid LIKE '%a19%' order by productid desc limit 1 offset 0