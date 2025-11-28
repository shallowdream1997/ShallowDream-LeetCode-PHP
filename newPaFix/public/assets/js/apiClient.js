/**
 * 通用 API 工具
 * 依赖 axios、getApiBaseUrl（定义于 lizi.js）
 */

function apiHeaders() {
    return {
        headers: {
            'Content-Type': 'application/json'
        }
    };
}

function buildPayload(action, params) {
    return {
        action,
        params: params || {}
    };
}

async function sendApiRequest(endpoint, action, params = {}, hooks = {}) {
    const { before, after, onError } = hooks;
    try {
        before && before();
        const response = await axios.post(`${getApiBaseUrl()}/${endpoint}`, buildPayload(action, params), apiHeaders());
        after && after();
        return response;
    } catch (error) {
        after && after();
        onError && onError(error);
        throw error;
    }
}

async function searchApi(action, params = {}, hooks = {}) {
    return sendApiRequest('search', action, params, hooks);
}

async function updateApi(action, params = {}, hooks = {}) {
    return sendApiRequest('update', action, params, hooks);
}

function formatEnvLabel(env) {
    if (!env) return '未知环境';
    switch (env) {
        case 'test':
            return 'Test环境';
        case 'local':
            return '开发环境';
        case 'uat':
            return 'UAT环境';
        case 'pro':
            return '生产环境';
        default:
            return env;
    }
}

function resolveEnvClass(env) {
    switch (env) {
        case 'test':
            return 'text-warning fw-bold';
        case 'local':
            return 'text-info fw-bold';
        case 'uat':
            return 'text-primary fw-bold';
        case 'pro':
            return 'text-danger fw-bold';
        default:
            return 'text-muted fw-bold';
    }
}

function setLoading(vm, message = '') {
    if (vm && Object.prototype.hasOwnProperty.call(vm, 'loading')) {
        vm.loading = message;
    }
}

function handleRequestError(vm, error, fallbackMsg = '网络错误，请稍后再试') {
    console.error(error);
    if (vm) {
        vm.errorMsg = fallbackMsg;
        vm.loading = '';
    }
}


