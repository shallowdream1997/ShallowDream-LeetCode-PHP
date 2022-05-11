/**
 * Created by codebuilder on 2022-04-11 14:52:33
 */

import { Component, OnInit, Input, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';

import { CommonFunction } from '../../../../../common/common_function';
import { LocalStorageService, SessionStorageService } from 'ngx-webstorage';
import { MessageService } from 'primeng/api';
import { paginator } from '../common/common-interface.service';
import * as _ from 'lodash';
import {
    PaSupplyOosAnalysis,
    PaSupplyOosAnalysisQuery,
    PaSupplyOosAnalysisQueryPage, PaSupplyOosAnalysisRest
} from '../../../../../common/restful-client/PA/pa_supply_oos_analysiss.restful';
import { PaSupplyOosAnalysisService } from './pa-supply-oos-analysiss.service';
import { PaFirstMoveToOverseasWarehouseReminderQueryPage } from '../../../../../common/restful-client/PA/pa_first_move_to_overseas_warehouse_reminders.server.restful';

@Component({
    selector: 'pa-supply-oos-analysis',
    providers: [PaSupplyOosAnalysisRest,PaSupplyOosAnalysisService],
    templateUrl: './pa-supply-oos-analysis.html'
})


export class PaSupplyOosAnalysisComponent {
    commonFunction: CommonFunction;
    moduleTitle: string = '海外仓OOS全链路分析';

    //start
    //缓存获取的英文用户名
    public userName: any;
    //缓存获取的垂直ID，用于区别MRO，PA，PL账号的
    public companyId: any;
    //缓存获取的英文中文数组映射
    public cNameArray: any;
    //登录账号所属
    private companySequenceId: string = '';
    //是否是IT运营人员
    private isIT: boolean;
    //end
    //loading等待模态框显示状态
    public showWait: boolean = false;

    //页面列表数据
    public dataList:any = [];

    //最近更新时间
    public newDate;

    //查询的条件选项
    public search:any = {
        total_date: [],
        salesList: [],
        developerList: [],
        stockList: [],
        oosOpt: [
            {name: '>',value:'_gt'},
            {name: '<',value:'_lt'},
            {name: '=',value:'_eq'},
        ],
    };
    //选择的待提交的条件内容
    public confirm:any = {
        total_date: '',
        salesList: [],
        developerList: [],
        stockList: [],
        oosOpt: '_gt',
        oosInput: '',
    }

    //list分页
    public paginator: paginator = {
        currentPage: 1,
        totalPages: 0,
        rows: 100,
        first: 0,
        last: 0,
        totalRecords: 0
    };

    public supplierStatusMap:any = {
        'consignment': '寄卖',
        'noconsignment': '自营',
    };
    constructor(private session:SessionStorageService,
                private local:LocalStorageService,
                private messageService: MessageService,
                private route: ActivatedRoute,
                private service:PaSupplyOosAnalysisService) {
        this.commonFunction = new CommonFunction();
    }

    async ngOnInit() {
        //获取缓存
        await this.getLocalStorage();
        //设置当前年份为年份选项列表
        await this.setNowYear();
    }

    //获取local缓存的数据：用户名等
    async getLocalStorage() {
        //获取local缓存的英文用户名
        let userName = this.local.retrieve('userName');
        if (userName !== null || userName != '') {
            this.userName = userName;
            this.companyId = this.local.retrieve('companySequenceId');
            this.cNameArray = this.local.retrieve('userNameAndCName');
        }
        let companySequenceId = this.local.retrieve('companySequenceId');
        this.isIT = this.local.retrieve('verticalName') == 'IT';
        if (companySequenceId !== null || companySequenceId != '') {
            this.companySequenceId = companySequenceId;
        }
    }
    //设置当前年份为年份选项列表
    async setNowYear(){
        let date = new Date();
        let nowYear = date.getFullYear();
        this.confirm.year = nowYear;
        let yearList:any = [];
        for (let i = nowYear;i >= 2019;i--){
            yearList.push({label: i,value: i});
        }
        this.search.yearList = yearList;
    }

    //查询
    async getSearch(){
        this.paginator.first = 0;
        await this.queryPage(this.paginator);
    }

    //queryPage接口
    async queryPage($event){
        this.showWait = true;
        //当前页码和下标
        this.paginator.currentPage = Math.floor($event.first / $event.rows) + 1;
        this.paginator.first = $event.first;
        try{
            //默认接口返回的参数为null
            let result = null;
            //构建查询参数
            let queryParams: PaSupplyOosAnalysisQueryPage = await this.setSearchParams({
                page: this.paginator.currentPage,
                limit: $event.rows,
                orderBy: '-oos_days',
            });
            result = await this.service.queryPage(queryParams);
            let list = [];
            if (result.isSuccess && result.resData.data.data.length > 0) {
                list = result.resData.data.data;
                await this.getAllSelectList();
            }
            this.dataList = list;
            this.paginator.totalPages = result.resData.data.pages;
            this.paginator.last = result.resData.data.pages;
            this.paginator.totalRecords = result.resData.data.total;
            this.showWait = false;
        }catch (e){
            this.showWait = false;
            console.error(e);
        }
    }

    //导出
    async exportData(){
        this.showWait = true;
        try {
            let page = 1;
            let pages = 1;
            let result = null;
            let exportData: any = [];
            do {
                //构建查询参数
                let queryParams: PaSupplyOosAnalysisQueryPage = await this.setSearchParams({
                    page: page,
                    limit: 500,
                    orderBy: '-oos_days',
                });
                result = await this.service.queryPage(queryParams);
                if (result.isSuccess && result.resData.data.data.length > 0) {
                    exportData = exportData.concat(result.resData.data.data);
                    //获取当前页
                    pages = result.resData.data.pages;
                }
                page++;
            } while (page <= pages);

            if (exportData.length > 0) {
                let exportDataLIst = [];
                for (let info of exportData) {
                    let data: any = {};
                    data['更新日期'] = info['total_date'];
                    data['skuId'] = info['sku_id'];
                    data['开发'] = this.cNameArray[info['developerusername']] || info['developerusername'];
                    data['销售'] = info['salesusername'];
                    data['销售类型'] = this.supplierStatusMap[info['last_supplier_status']] || info.last_supplier_status
                    data['仓库'] = info['stock_group'];
                    data['CETS库存'] = Number(info.cets_over_sea_stock_qty);
                    data['OOS天数'] = Number(info.oos_days);
                    data['Amazon库存'] = Number(info.afn_warehouse_quantity);
                    data['Amazon不可售数量'] = Number(info.afn_unsellable_quantity);
                    data['Amazon预留数量'] = Number(info.afn_reserved_quantity);
                    data['Amazon入库处理数量'] = Number(info.afn_inbound_working_quantity);
                    data['Amazon入库接收数量'] = Number(info.afn_inbound_receiving_quantity);
                    data['Amazon断货原因'] = info.result_tag;
                    exportDataLIst.push(data);
                }
                this.commonFunction.exportExcelComplex({ '海外仓OOS全链路分析': exportDataLIst }, `海外仓OOS全链路分析${this.commonFunction.dateToEasyYMDHIS()}.xlsx`);
            } else {
                this.notify('没有数据可以导出','warn');
            }
            this.showWait = false;
        }catch (e) {
            this.showWait = false;
            console.log(e)
        }
    }

    notify(message:string='',type:string = 'info'){
        this.messageService.add({life:3000,severity:type,summary:'提示',detail:message});
    }


    //获取所有更新日期、开发、销售，仓库
    async getAllSelectList() {
        //缓存不存在就获取所有更新日期、开发、销售，仓库
        let total_date: any = this.session.retrieve(`PaSupplyOOSAnalysisTotalDate${this.commonFunction.dateToEasyYMD()}`);
        let developerList: any = this.session.retrieve(`PaSupplyOOSAnalysisDevel${this.commonFunction.dateToEasyYMD()}`);
        let salesList: any = this.session.retrieve(`PaSupplyOOSAnalysisSales${this.commonFunction.dateToEasyYMD()}`);
        let stockList: any = this.session.retrieve(`PaSupplyOOSAnalysisStock${this.commonFunction.dateToEasyYMD()}`);
        if (!total_date && !developerList && !salesList && !stockList) {
            let distinctList = ['total_date', 'developerusername', 'salesusername', 'stock_group'];
            let promiseAll = [];
            for (let distinct of distinctList) {
                let params = {
                    uxField: distinct
                };
                promiseAll.push(this.service.distinct(params));
            }
            let respAll: any = await Promise.all(promiseAll).catch(e => {
                this.notify('查询下拉选项失败，请重试','warn');
                return false;
            });
            if (respAll) {
                this.search.total_date = [];
                this.search.developerList = [];
                this.search.salesList = [];
                this.search.stockList = [];

                if (!total_date || total_date.length == 0) {
                    let respDev = respAll[0];
                    for (let item of respDev.resData.data) {
                        if (!item) {
                            continue;
                        }
                        this.search.total_date.push({
                            name: this.commonFunction.dateToYMD(new Date(item)), value: item
                        });
                    }
                    this.search.total_date = _.orderBy(this.search.total_date,['name'],['desc']);
                    this.session.store(`PaSupplyOOSAnalysisTotalDate${this.commonFunction.dateToEasyYMD()}`, this.search.total_date);
                } else {
                    console.log('读取缓存：更新日期');
                    this.search.total_date = total_date;
                }

                if (!developerList || developerList.length == 0) {
                    let respDev = respAll[1];
                    for (let item of respDev.resData.data) {
                        if (!item) {
                            continue;
                        }
                        this.search.developerList.push({
                            name: this.cNameArray[item] || item, value: item
                        });
                    }
                    this.session.store(`PaSupplyOOSAnalysisDevel${this.commonFunction.dateToEasyYMD()}`, this.search.developerList);
                } else {
                    console.log('读取缓存：开发');
                    this.search.developerList = developerList;
                }

                if (!salesList || salesList.length == 0) {
                    let respDev = respAll[2];
                    for (let item of respDev.resData.data) {
                        if (!item) {
                            continue;
                        }
                        this.search.salesList.push({
                            name: this.cNameArray[item] || item, value: item
                        });
                    }
                    this.session.store(`PaSupplyOOSAnalysisSales${this.commonFunction.dateToEasyYMD()}`, this.search.salesList);
                } else {
                    console.log('读取缓存：销售');
                    this.search.salesList = salesList;
                }

                if (!stockList || stockList.length == 0) {
                    let respDev = respAll[3];
                    for (let item of respDev.resData.data) {
                        if (!item) {
                            continue;
                        }
                        this.search.stockList.push({
                            name: item, value: item
                        });
                    }
                    this.session.store(`PaSupplyOOSAnalysisStock${this.commonFunction.dateToEasyYMD()}`, this.search.stockList);
                } else {
                    console.log('读取缓存：仓库');
                    this.search.stockList = stockList;
                }

            }
        } else {
            console.log('读取缓存：');
            this.search.total_date = total_date;
            this.search.developerList = developerList;
            this.search.salesList = salesList;
            this.search.stockList = stockList;
        }
    }

    //构造查询参数
    async setSearchParams(params: any = {}) {
        //商家
        if (_.trim(this.confirm.total_date) != '') {
            params.total_date = this.confirm.total_date;
        }
        //开发者
        if (this.confirm.developerList.length > 0) {
            params.developerusername_in = this.confirm.developerList.join(',');
        }
        //销售
        if (this.confirm.salesList.length > 0) {
            params.salesusername_in = this.confirm.salesList.join(',');
        }
        //仓库
        if (this.confirm.stockList.length > 0) {
            params.stock_group_in = this.confirm.stockList.join(',');
        }
        //OOS天数
        if ((this.confirm.oosInput??'') !== '') {
            if (!this.commonFunction.isPositiveIntNumber(this.confirm.oosInput)){
                return this.notify('OOS天数必须是正整数','error');
            }
            if (this.confirm.oosOpt == '_eq'){
                params[`oos_days`] = this.confirm.oosInput;
            }else {
                params[`oos_days${this.confirm.oosOpt}`] = this.confirm.oosInput;
            }
        }
        return params;
    }
}
