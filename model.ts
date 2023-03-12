class Schema {
    constructor(param: { applyPurpose: { default: string; comment: string; type: StringConstructor }; modifiedOn: { default: any; comment: string; type: DateConstructor }; totalNum: { default: number; comment: string; type: NumberConstructor }; createdBy: { default: string; comment: string; type: StringConstructor }; fcuNum: { default: number; comment: string; type: NumberConstructor }; modifiedBy: { default: string; comment: string; type: StringConstructor }; applyStatus: { default: string; comment: string; type: StringConstructor }; createdOn: { default: any; comment: string; type: DateConstructor }; projectRemark: { default: string; comment: string; type: StringConstructor }; applyBatch: { default: string; comment: string; type: StringConstructor } }, param2: { collection: string }) {
        
    }

}
pa_high_efficiency_sales_detail
export const PaHighEfficiencySalesDetailSchema = new Schema({
    skuid: {type: String, default: "", comment: "skuid"},
    year: {type: String, default: "", comment: "统计年"},
    date: {type: String, default: "", comment: "统计周"},
    topCategory: {type: String, default: "", comment: "一级分类"},
    businessType: {type: String, default: "", comment: "业务类型"},
    developer: {type: String, default: "", comment: "开发人员"},
    salesman: {type: String, default: "", comment: "销售人员"},
    cnCategory: {type: String, default: "", comment: "中文全分类"},
    warehouse: {type: String, default: "", comment: "仓库"},
    inventoryStatus: {type: String, default: "", comment: "inventory状态"},
    publishStatus: {type: String, default: "", comment: "发布状态"},
    publishTime: {type: Date, default: CommonFunction.now, comment: "发布时间"},
    cost: {type: Number, default: 0, comment: "sku单价"},
    inventory: {type: Number, default: 0, comment: "总库存件数（含在途）"},
    inventoryOnway: {type: Number, default: 0, comment: "补货在途库存"},
    soldHis: {type: Number, default: 0, comment: "历史sold"},
    salesHis: {type: Number, default: 0, comment: "历史sales"},
    plHis: {type: Number, default: 0, comment: "历史pl"},
    sold180: {type: Number, default: 0, comment: "近180天sold"},
    complaintNum180: {type: Number, default: 0, comment: "近180天质量投诉件数"},
    complaintRatio180: {type: Number, default: 0, comment: "近180天质量投诉率"},
    sold7: {type: Number, default: 0, comment: "近1周sold"},
    sold14: {type: Number, default: 0, comment: "近2周sold"},
    sold21: {type: Number, default: 0, comment: "近3周sold"},
    sold28: {type: Number, default: 0, comment: "近4周sold"},
    modifiedBy: {type: String, default: "", comment: "修改人"},
    modifiedOn: {type: Date, default: CommonFunction.now, comment: "修改日期"},
    createdOn: {type: Date, default: CommonFunction.now, comment: "创建时间"},
    createdBy: {type: String, default: "", comment: "创建人"}
}, {collection: "pa_high_efficiency_sales_detail"});
