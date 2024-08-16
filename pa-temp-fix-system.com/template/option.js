import axios from 'axios';
import Vue from "vue";

function errorCatch(error,message = '网络错误~'){
    return {
        code: 500,
        message: message,
        error: error
    }
}
function returnData(response) {
    if (response.hasOwnProperty('status') && response.status === 200 && response.data){
        return response.data;
    }
    return errorCatch();
}
function isError(response){
    return response.hasOwnProperty('code') && response.code === 500;
}


async function requestUrl(url, data) {

    return await axios.post("http://www.pa-temp-fix-system.com:89/template/" + url, data, {
        headers: {
            'Content-Type': 'application/json',
            // 其他自定义头
        }
    }).catch(function (error) {
        return errorCatch(error);
    });
}