export class Export {
    @ApiOperation({summary:'导出未寄卖数据'})
    @ApiResponse({status:200,description:'导出未寄卖数据成功'})
    @ApiResponse({status:200,description:'导出未寄卖数据失败'})
    @Get('exportPaNoconsignmentToConsignment')
    async exportPaNoconsignmentToConsignment(@Query() query:{},@Response() resp){
        let workbook = new ExcelJS.Workbook();
        workbook.creator = 'zhouangang';
        workbook.lastModifiedBy = 'zhouangang';
        // 将工作簿日期设置为 1904 年日期系统
        workbook.properties.date1904 = true;

        let worksheet = workbook.addWorksheet('未寄卖数据',{properties:{tabColor:{argb:'FFC0000'}}})
        worksheet.columns = [
            {header: 'supplier',key: 'supplier',width:15, style: {font: {size: 12}}},
            {header: 'business_type',key: 'business_type',width:15, style: {font: {size: 12}}},
            {header: 'category_full_path',key: 'category_full_path',width:50, style: {font: {size: 12}}},
            {header: 'images',key:'images',width:50,style:{font:{size:12}}}
        ];
        let list = await this.paNoconsignmentToConsignmentListModel.find({
            monthnumber: 1
        });
        let bufferImages:any = await this.getFileStream(this.paFtpPath + '/pa_ip_brand/6adf97f83acf6453d4a6a4b1070f3754.png');
        // 通过 buffer 将图像添加到工作簿
        const imageId2 = workbook.addImage({
            buffer: bufferImages,
            extension: 'png',
        })

        for (let item of list){
            worksheet.addRow({
                spplier:item.supplier,
                business_type:item.business_type,
                category_full_path:item.category_full_path
            });
            worksheet.addImage(imageId2,'D2:D2');
        }

        // 写入 buffer
        const buffer:any = await workbook.xlsx.writeBuffer();
        let filename = '未寄卖ce数据.xlsx';
        resp.set({
            'Content-Disposition': 'attachment; filename=' + encodeURIComponent(filename),
        });
        this.getReadableStream(buffer).pipe(resp);

    }

    async getFileStream(url) {
        return new Promise(async (resolve, reject) => {
            await request({url, encoding: null}, (err, resp, buffer) => {
                resolve(buffer);
            });
        });
    }

    getReadableStream(buffer: Buffer): Readable {
        const stream = new Readable();
        stream.push(buffer);
        stream.push(null);
        return stream;
    }
}