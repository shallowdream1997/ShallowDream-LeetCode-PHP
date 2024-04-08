<?php

class RestBaseComponent {
    protected $masterServerUrl = "";
    protected $queryServerUrl = "";
    public $serverUrl = "";
    protected $initUrl = false;
    protected $curlClient = null;
    protected $requestKey = '';
    protected $httpMethod = '';

    public function __construct($module, $masterServerUrl = "", $queryServerUrl = "", $key = "", $timeout = 60) {
        //默认开发模式没有则访问本地nodejs，不经过api manager
        $nodejs_api_path = '/api';
        if (!empty($key)) {
            $nodejs_api_path = '';
        }
        if (stripos($module, "/") !== 0 && !empty($module)) {
            $module = "/" . $module;
        }

        $this->masterServerUrl = $masterServerUrl . $nodejs_api_path . $module;
        $this->queryServerUrl = $queryServerUrl . $nodejs_api_path . $module;
        $this->serverUrl = $this->queryServerUrl;

        if ($this->curlClient == null) {
            $this->curlClient = curl_init();
            curl_setopt($this->curlClient, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
            curl_setopt($this->curlClient, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
            curl_setopt($this->curlClient, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($this->curlClient, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($this->curlClient, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($this->curlClient, CURLOPT_SSL_VERIFYPEER, false);
            $this->requestKey = $key;
        }

    }

    public function master() {
        $this->serverUrl = $this->masterServerUrl;
        $this->initUrl = true;
        return $this;
    }

    public function query() {
        $this->serverUrl = $this->queryServerUrl;
        $this->initUrl = true;
        return $this;
    }

    public function get($method = "", $params = array()) {
        if (!$this->initUrl) {
            $this->serverUrl = $this->queryServerUrl;
            $this->initUrl = true;
        }
        if ($method != "") {
            $this->serverUrl .= "/" . $method;
        }

        if (count($params) > 0) {
            $this->serverUrl .= "?" . http_build_query($params);
        }
        curl_setopt($this->curlClient, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curlClient, CURLOPT_POST, false);
        curl_setopt($this->curlClient, CURLOPT_PUT, false);
//         curl_setopt($this->curlClient, CURLOPT_DELETE, false);
        $this->httpMethod = 'GET';

        return $this->invoke("");
    }

    public function download($method = "", $params = array()) {
        if (!$this->initUrl) {
            $this->serverUrl = $this->queryServerUrl;
            $this->initUrl = true;
        }
        if ($method != "") {
            $this->serverUrl .= "/" . $method;
        }

        if (count($params) > 0) {
            $this->serverUrl .= "?" . http_build_query($params);
        }

        curl_setopt($this->curlClient, CURLOPT_TIMEOUT, 1800);
        curl_setopt($this->curlClient, CURLOPT_HTTPGET, true);

        $this->httpMethod = 'GET';

        return $this->invoke("");
    }

    public function create($params = array()) {
        return $this->post("", $params);
    }

    public function update($id, $params = array()) {
        return $this->put($id, $params);
    }

    public function call($method = "", $params = array()) {
        if (strlen($method) > 3 && substr($method, 0, 3) == "get") {
            return $this->get($method, $params);
        } else {
            return $this->post($method, $params);
        }
    }

    public function delete($id, $params = array()) {
        $this->serverUrl = $this->masterServerUrl;
        $this->serverUrl .= "/" . $id;
        curl_setopt($this->curlClient, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->httpMethod = 'DELETE';
        return $this->invoke("", $params);
    }

    public function put($id, $params = array()) {
        $this->serverUrl = $this->masterServerUrl;
        $this->serverUrl .= "/" . $id;
        curl_setopt($this->curlClient, CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->httpMethod = 'PUT';
        return $this->invoke("", $params);
    }

    public function post($method = "", $params = array(), $author=false, $key="") {
        $this->serverUrl = $this->masterServerUrl;
        curl_setopt($this->curlClient, CURLOPT_CUSTOMREQUEST, 'POST');
        $this->httpMethod = 'POST';
        if ($author) return $this->invoke($method, $params,$author,$key); 
        return $this->invoke($method,$params );
    }

    private function invoke($method = "", $params = array(), $author = false,$key="") {
        $this->initUrl = false;
        if ($method != "") {
            $this->serverUrl = $this->serverUrl . "/" . $method;
        }

        //echo "serverUrl => " . $this->serverUrl . "<br><br>";
        curl_setopt($this->curlClient, CURLOPT_URL, $this->serverUrl);

        if (count($params) > 0) {
            curl_setopt($this->curlClient, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $requestTraceId = COOKIE_AUTH . '_' .  date("His") . '_' .  uniqid('', true);
        if ($author) {
            $this->requestKey = !empty($key) ? $key : $this->requestKey;
            curl_setopt($this->curlClient, CURLOPT_HTTPHEADER, array(
                'request-trace-id: ' . $requestTraceId,
                'request-trace-level: 1',
                'Content-Type: application/json; charset=UTF-8',
                'Authorization: ' . $this->requestKey,
            ));
        } else {
            curl_setopt($this->curlClient, CURLOPT_HTTPHEADER, array(
                'request-trace-id: ' . $requestTraceId,
                'request-trace-level: 1',
                'Content-Type: application/json; charset=UTF-8',
                //'Authorization: Bearer ' . $key
                'X-Api-Key: ' . $this->requestKey,
            ));
        }

        //出站日志

        $jsonData = $this->_toJson(curl_exec($this->curlClient));

        $curlInfo = curl_getinfo($this->curlClient);


        if (curl_errno($this->curlClient)) {
            throw new Exception("<span onclick=\"alert('" . $this->serverUrl . "')\">Network Exception: <font color = 'red'>" . curl_error($this->curlClient) . "</font></span>");
        } else
            if (isset($jsonData['message']) && 'success'!=$jsonData['message']) {
                $response = array(
                    'url' => $this->serverUrl,
                    'params' => json_encode($params),
                    'message' => curl_error($this->curlClient),
                    'data' => $jsonData
                );
                //throw new Exception("<span onclick=\"alert('" . $this->serverUrl . "')\">Code exception: <font color = 'red'>". json_encode($jsonData['message']) . "</font></span>");
            } else {
                $response = array(
                    'url' => $this->serverUrl,
                    'params' => json_encode($params),
                    'message' => 'success',
                    'data' => $jsonData
                );
            }
        return $response;
    }

    protected function _getMasterServiceUrl($properties) {
        return $this->masterServerUrl . $this->_services[$properties];
    }

    protected function _getQueryServiceUrl($properties) {
        return $this->queryServerUrl . $this->_services[$properties];
    }

    protected static function _toJson($data) {
        return json_decode($data, true);
    }

    protected static function getParams($dataArray) {
        $req = "";
        foreach ($dataArray as $key => $value) {
            $value = urlencode(stripslashes($value));
            if (empty($req)) {
                $req .= "$key=$value";
            } else {
                $req .= "&$key=$value";
            }
        }
        return $req;
    }
}

?>
