/**
 * Created by codebuilder on 2022-03-11 15:46:32
 */
import {Injectable} from "@angular/core";
import {
    PaSkuLifeCycleManagement,
    PaSkuLifeCycleManagementQuery,
    PaSkuLifeCycleManagementQueryPage, PaSkuLifeCycleManagementRest
} from '../../../../../common/restful-client/PA/pa_sku_life_cycle_managements.restful';

// Service类: 对业务封装
@Injectable()
export class PaSkuLifeCycleManagementService {

    constructor(private paSkuLifeCycleManagementRest: PaSkuLifeCycleManagementRest) {
    }

    //以下都是参考代码,可删除
    async isActive(){
        return await this.paSkuLifeCycleManagementRest.isActive();
    }

    async create(params: PaSkuLifeCycleManagement){
        return await this.paSkuLifeCycleManagementRest.create(params);
    }

    async list(){
        return await this.paSkuLifeCycleManagementRest.list();
    }

    async read(_id:string) {
        return await this.paSkuLifeCycleManagementRest.read(_id);
    }

    async query(queryParams: PaSkuLifeCycleManagementQuery) {
        return await this.paSkuLifeCycleManagementRest.query(queryParams);
    }

    async queryPage(queryParams: PaSkuLifeCycleManagementQueryPage) {
        return await this.paSkuLifeCycleManagementRest.queryPage(queryParams);
    }

    async distinct(params: any) {
        return this.paSkuLifeCycleManagementRest.get(this.getUrl('distinct'), params);
    }

    getUrl(func: string) {
        return this.paSkuLifeCycleManagementRest.getBaseUrl() + "/" + this.paSkuLifeCycleManagementRest.getModule() + "/" + func;
    }

    //从impala 获取培养期得分详情
    async getScoreDetailImpala(params:any){
        return this.paSkuLifeCycleManagementRest.get(this.getUrl('getScoreDetailImpala'),params);
    }
    //根据advise的类型转换为具体内容
    getAdviseName(type:string){
        let adviseName:string = '';
        switch (type){
            case "处理建议1":
                adviseName = "检查未得分指标，并完成优化;";
                break;
            case "处理建议2":
                adviseName = "1）优化资料：标题、属性、描述、关键词等部分或全部（必选）;\n" +
                    "2）优化图片：调整图片角度，补充细节、场景或安装说明等（自选）;\n" +
                    "3）调整定价：微调价格（自选）;\n" +
                    "4）广告优化（自选）;\n";
                break;
            case "处理建议3":
                adviseName = "1）优化资料：标题、属性、描述、关键词等部分或全部（自选）;\n" +
                    "2）优化图片：调整图片角度，补充细节、场景或安装说明等（自选）;\n" +
                    "3）价格优化：结合市场价及margin目标调价，超过90天无售动CE单内对应SKU，做前端售价降margin处理，促进销售（必选）;\n" +
                    "4）广告优化（自选）;\n";
                break;
            case "处理建议4":
                adviseName = "调整定价：微调价格（自选）;";
                break;
            case "处理建议5":
                adviseName = "设置卖完停售（自选）;";
                break;
            case "处理建议6":
                adviseName = " 1）优化资料：标题、属性、描述、关键词等部分或全部（自选）;\n" +
                    " 2）优化图片：调整图片角度，补充细节、场景或安装说明等（自选）;\n";
                break;
            case "处理建议7":
                adviseName = "正常销售，微调价格;";
                break;
            case "处理建议8":
                adviseName = "卖完售停;";
                break;
            default:
                adviseName = "";
                break;
        }

        return adviseName;
    }


    async splitGroupName(groupName, channel) {
        let groupAttrNameArray = [];
        if (channel.indexOf('amazon') !== -1) {
//groupName
//channel
//delelted 0
            let variationThemeMapAttributeRes = await this.productSkuService.getVariationthemeAttributeMap(this.channel_amazon["productType"],groupName);
            console.log(variationThemeMapAttributeRes);
            let variationThemeMapAttributeResData =  _.get(variationThemeMapAttributeRes,"data.data",[]);
            if(variationThemeMapAttributeResData && variationThemeMapAttributeResData.length>0){
                groupAttrNameArray = variationThemeMapAttributeResData[0]["attribute"];
            }
//驼峰除了 sizeColor 和 colorSize 转化需要切割，其他的都使用"-"来切割
            /*let groupAttrNameLabel = _.cloneDeep(groupName);
            if (groupAttrNameLabel.toLowerCase() == 'sizecolor' || groupAttrNameLabel.toLowerCase() == 'colorsize') {
            // groupAttrNameLabel = groupName.replace(/([A-Z])/g, "-$1").toLowerCase();
            groupAttrNameLabel = 'Color-Size';
            }

            if (groupAttrNameLabel.indexOf('-') !== -1) {
            groupAttrNameArray = _.compact(groupAttrNameLabel.split("-").sort());
            } else {
            groupAttrNameArray = [groupAttrNameLabel];
            }

            for (let i = 0; i < groupAttrNameArray.length; i++) {
            groupAttrNameArray[i] = _.capitalize(groupAttrNameArray[i]);

            //Material 转为 MaterialType
            // if (groupAttrNameArray[i] == 'Material') {
            //     groupAttrNameArray[i] = 'MaterialType';
            // }

            //Cupsize 转为 CupSize
            if (groupAttrNameArray[i] == 'Cupsize') {
            groupAttrNameArray[i] = 'CupSize';
            }

            //Color_name 转为 ColorName
            if (groupAttrNameArray[i] == 'Color_name' || groupAttrNameArray[i] == 'Colorname') {
            groupAttrNameArray[i] = 'ColorName';
            }
            //Size_name 转为 SizeName
            if (groupAttrNameArray[i] == 'Size_name' || groupAttrNameArray[i] == 'Sizename') {
            groupAttrNameArray[i] = 'SizeName';
            }
            }*/
        } else {
            groupAttrNameArray = _.map(groupName.split(','), _.trim);
        }
        return groupAttrNameArray;
    }
}

