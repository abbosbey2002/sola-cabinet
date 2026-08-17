<?php
/**
 * Request
 */
function autoFindRequest() {
    global $HTTP_RAW_POST_DATA;

    if($HTTP_RAW_POST_DATA)
        return $HTTP_RAW_POST_DATA;

    $f = file("php://input");
    return implode(" ", $f);
}

function getAuthLoginInfo($authLogin) // Authorization header
{
    $result = array(
        "", "", ""
    );

    $header = explode(" ", $authLogin);
    if (count($header) == 2)
    {
        $result[0] = $header[0];
        $userInfo = explode(":", base64_decode($header[1]));
        if (count($userInfo) == 2)
        {
            $result[1] = $userInfo[0];
            $result[2] = $userInfo[1];
        }
    }

    return $result;
}

function generateAuthToken($userName, $secretKey, $requestJSONstring)
{
    $auth = "{$userName} {$secretKey} {$requestJSONstring}";
    return md5($auth);
}

function setHttpCode($httpCode)
{
    header("HTTP/1.1 {$httpCode}", true, $httpCode);
}

function getStatusMessage($status, $lang) {
    global $messageBox;
    $message = "Error code {$status}";
    //if (array_key_exists($status, $messageBox[$lang])) $message = iconv("windows-1251", "UTF-8", $messageBox[$lang][$status]);
    if (array_key_exists($status, $messageBox[$lang])) $message = $messageBox[$lang][$status];
    return $message;
}

function writeLog($logType, $msg)
{
    global $logMsgQ, $LOGGING, $logMsgLength;
    if ($LOGGING)
    {
        while (!empty($msg))
        {
            $logmsg = substr($msg, 0, $logMsgLength);
            $logmsg = str_replace("\r\n", ' ', $logmsg);
            $logmsg = str_replace("\n", ' ', $logmsg);
            msg_send($logMsgQ, $logType, $logmsg, false, false);
            $msg = substr($msg, $logMsgLength);
        }
    }
}

/**
 *      SEND SMS
 */
function sendSMS($phoneId, $smscode){

    $url = "http://172.18.0.16:1401/send";

    //$phoneId = "998981250765";
    
    $postdata = http_build_query(
        array(
            'username'  => 'billing1',
            'password'  => 'FjdDXdc3',
            //'from'      => '3700',
            'to'        => $phoneId,
            'content'   => "SOLA: Kod podtverjdeniya dlya registratsii v lichnom kabinete: ".$smscode,
            'validity-period' => 10,
            'dlr'       => 'yes',
            'dlr-url'   => 'http://172.19.1.101/smsdlr/dlrpc.php?login='.$phoneId,
            'dlr-level' => 2,
            'dlr-method' => 'POST'
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );
    
    $context = stream_context_create($opts);        
    $result = file_get_contents($url, false, $context);
    file_put_contents("/tmp/pcsms", "{$smscode}\n\n{$result}");

    $res = explode(" ", $result);
    $sms_msg = trim($res[0]);
    $sms_detail = trim($res[1]);

    switch($sms_msg)
    {
        case "Success":
            return 0;
        case "Error":
            return 111; //ERR_SMS_SEND
        default:
            return 112; //ERR_SMS_SEND
    }
}

function isInteger($input)
{
    return(ctype_digit(strval($input)));
}

function ItemValue($varArray, $item, $default)
{
    if (!array_key_exists($item, $varArray))
        return $default;
    else
        return $varArray[$item];
}

// month format: YYYY-MM
function checkRequestMonth($month)
{
    $valid = false; // INVALID
    if (strlen($month) != 7)
    {
        return $valid;
    }

    $arr = explode("-", $month);
    $valid = (isInteger($arr[0]) and isInteger($arr[1]));
    if ($valid)
    {
        $t = mktime(0, 0, 0, $arr[1], 1, $arr[0]);
        $d = date("Y-m", $t);
        $valid = ($d == $month);
    }

    return $valid;
}

// month format: YYYY-MM
function getMonthFirstDay($month)
{
    $arr = explode("-", $month);
    $t = mktime(0, 0, 0, $arr[1], 1, $arr[0]);
    return date("d.m.Y", $t);
}

// month format: YYYY-MM
function getMonthYYMM($month)
{
    $arr = explode("-", $month);
    return substr($arr[0], -2) . $arr[1]; // YYMM
}

/**
 * Request params
 */
function requestIdentify($requestArr)
{
    $requestArr["phn"] = ItemValue($requestArr, "phn", "0");
    if (!isInteger($requestArr["phn"])) $requestArr["phn"] = 0;
    
    $requestArr["sendsms"] = ItemValue($requestArr, "sendsms", "1");
    if (!isInteger($requestArr["sendsms"])) $requestArr["sendsms"] = 1;
    
    return $requestArr;
}

/**
 * 
    $call["priority"] = ItemValue($call, "priority", 0);
    if (!isInteger($call["priority"])) $call["priority"] = 0;

    $call["phoneNum"] = ItemValue($call, "phoneNum", "");
    $call["phoneNum"] = strval($call["phoneNum"]);
    if (!ctype_digit(ltrim($call["phoneNum"], '0'))) $call["phoneNum"] = "";
 *
 */
function requestVerify($requestArr)
{
    $requestArr["phn"] = ItemValue($requestArr, "phn", "0");
    if (!isInteger($requestArr["phn"])) $requestArr["phn"] = 0;

    $requestArr["smsCode"]  = ItemValue($requestArr, "smsCode", "");

    return $requestArr;
}

function requestAbonentInfo($requestArr)
{
    $requestArr["phn"] = ItemValue($requestArr, "phn", "0");
    if (!isInteger($requestArr["phn"])) $requestArr["phn"] = 0;

    //$requestArr["abonType"] = ItemValue($requestArr, "abonType", -1);
    //if (!isInteger($requestArr["abonType"])) $requestArr["abonType"] = -1;
    
    return $requestArr;
}

function requestAbonentEdit($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", "0");
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;
    
    $requestArr["email"]       = ItemValue($requestArr, "email", "");
    $requestArr["phone"]       = ItemValue($requestArr, "phone", "");
}

function requestOnlyAcct($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;
    
    return $requestArr;
}

function requestAcctWifiPassword($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["curr_password"]    = ItemValue($requestArr, "curr_password", "");
    $requestArr["new_password"]     = ItemValue($requestArr, "new_password", "");

    return $requestArr;
}

function requestAcctPayments($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["pay_month"]   = ItemValue($requestArr, "pay_month", "");

    return $requestArr;
}

function requestOnDeviceNewOneClick($requestArr)
{
    $requestArr["phn"]      = ItemValue($requestArr, "phn", "0");
    if (!isInteger($requestArr["phn"])) $requestArr["phn"] = 0;

    $requestArr["acc_id"]   = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["smsCode"]  = ItemValue($requestArr, "smsCode", "");
    
    return $requestArr;
}

function requestOnDeviceDelete($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["permit_id"]   = ItemValue($requestArr, "permit_id", 0);
    if (!isInteger($requestArr["permit_id"])) $requestArr["permit_id"] = 0;
    
    return $requestArr;
}

function requestTrafficDetail($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["detail_month"]   = ItemValue($requestArr, "detail_month", "");

    return $requestArr;
}

function requestTariffConnect($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["tariff_id"]   = ItemValue($requestArr, "tariff_id", 0);
    if (!isInteger($requestArr["tariff_id"])) $requestArr["tariff_id"] = 0;

    $requestArr["tariff_conndate"]   = ItemValue($requestArr, "tariff_conndate", "");

    return $requestArr;
}

function requestTariffDisconnect($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["tariff_id"]   = ItemValue($requestArr, "tariff_id", 0);
    if (!isInteger($requestArr["tariff_id"])) $requestArr["tariff_id"] = 0;
    
    return $requestArr;
}

 /**
 * Responses
 */
function responseOnError($errorCode, $message = "")
{
    global $lang;
    if (empty($message)) $message = getStatusMessage($errorCode, $lang);

    if ($errorCode > 0) setHttpCode(400);

    $response = array("code" => $errorCode, "errMsg" => $message);
    return json_encode($response);
}

function responseOnIdentify($accList)
{
    $response = array("accs" => $accList);
    return json_encode($response);
}

function responseOnAbonentInfo($abonInfoObj)
{
    $response = array(  "name"                  => $abonInfoObj->UserName,
                        "saldo"                 => $abonInfoObj->Saldo,
                        "status"                => iconv("windows-1251", "UTF-8", $abonInfoObj->OffReasonName),
                        "curr_tariff_id"        => $abonInfoObj->TariffId,
                        "curr_tariff_name"      => iconv("windows-1251", "UTF-8", $abonInfoObj->TariffName),
                        "contract_date"         => $abonInfoObj->contractDate,
                        "email"                 => $abonInfoObj->email,
                        "phone"                 => $abonInfoObj->phone,
                        "address"               => iconv("windows-1251", "UTF-8", $abonInfoObj->address),
                        "device_count"          => $abonInfoObj->DeviceCount,
                        "device_active_count"   => $abonInfoObj->DeviceActiveCount,
                     );
    return json_encode($response);
}

function responseOnAcctBalance($saldo)
{
    $response = array("saldo" => $saldo);
    return json_encode($response);
}

function responseOnAcctPayments($payments)
{
    $response = array("payments" => $payments);
    return json_encode($response);
}

function responseOnTrafficDetail($details)
{
    $response = array("detail" => $details);
    return json_encode($response);
}

function responseOnTariffAvailable($srvList)
{
    $response = array("tariffs" => $srvList);
    return json_encode($response);
}

function responseOnTariffConnected($srvList)
{
    return responseOnTariffAvailable($srvList);
}

function responseOnDeviceList($devices, $srvId, $srvCost)
{
    $response = array("devices" => $devices, "connect_cost" => $srvCost);
    return json_encode($response);
}

function responseOnDeviceNewOneClick($smsgen, $accList)
{
    $response = array("smsSended" => $smsgen, "accs" => $accList);
    return json_encode($response);
}

function responseOnServiceAvailable($srvList)
{
    $response = array("services" => $srvList);
    return json_encode($response);
}

function responseOnServiceConnected($srvList)
{
    return responseOnServiceAvailable($srvList);
}

function requestServiceConnect($requestArr)
{
    $requestArr["acc_id"]      = ItemValue($requestArr, "acc_id", 0);
    if (!isInteger($requestArr["acc_id"])) $requestArr["acc_id"] = 0;

    $requestArr["service_id"]   = ItemValue($requestArr, "service_id", 0);
    if (!isInteger($requestArr["service_id"])) $requestArr["service_id"] = 0;

    $requestArr["service_conndate"]   = ItemValue($requestArr, "service_conndate", "");

    return $requestArr;
}

