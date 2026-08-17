<?php
define('ARM_IN', true);

//ini_set("display_errors", "off");
//error_reporting(0);

include_once("constants.php");
include_once("functions.php");
include_once("oracle9.php");

//print_r($_GET);
/*$jsonArr = array("user" => "username", "password" => "mypass");
$jsonObj = json_encode($jsonArr);
$jsonStr = json_decode($jsonObj);
var_dump($jsonStr);
*/

$headers = getallheaders();

/**
 *              Check login:password
 */
$authLoginInfo = getAuthLoginInfo($headers["Authorization"]);
if ( (count($authLoginInfo) != 3) or ($authLoginInfo[0] != "Basic") or empty($authLoginInfo[1]) or empty($authLoginInfo[2]) )
{
    writeLog(LOG_ERR, "Authorization error level 1. Authorization type is not Basic or username and/or password is empty {$headers["Authorization"]} " . serialize($authLoginInfo));
    setHttpCode(403);
    exit(0);
}

if ( !array_key_exists($authLoginInfo[1], $users) or ($users[$authLoginInfo[1]]["password"] != $authLoginInfo[2]) )
{
    writeLog(LOG_ERR, "Authorization error level 2. User no found or password incorrect");
    setHttpCode(403);
    exit(0);
}

if ( $users[$authLoginInfo[1]]["status"] != 1 )
{
    writeLog(LOG_ERR, "Authorization error level 3. User deactivated");
    setHttpCode(403);
    exit(0);
}

/**
 *              MAIN params
 */
$user = $users[$authLoginInfo[1]];
$user["name"] = $authLoginInfo[1];

$action = "none";
if (array_key_exists("action", $_GET)) $action = $_GET["action"];

$param1 = "";
if (array_key_exists("param1", $_GET)) $param1 = $_GET["param1"];

$param2 = "";
if (array_key_exists("param2", $_GET)) $param2 = $_GET["param2"];

$requestJSONStr = autoFindRequest();
$requestArr = json_decode($requestJSONStr, true);

$lang = "ru";
if (!is_null($requestArr) and array_key_exists("lang", $requestArr)) $lang = $requestArr["lang"];
if (!in_array($lang, array("ru", "en", "uz"))) $lang = "ru";
//$lang = "ru"; // <-- Poka vse zaprosi na russkom

/**
 *              Check TOKEN
 */
$secretKey = $users[$authLoginInfo[1]]["secretKey"];
$authToken = $headers["X-Access-Token"];

$token = generateAuthToken($user["name"], $user["secretKey"], $requestJSONStr);
//file_put_contents("/tmp/pcapi_log", "1----------------------------------------------\n", FILE_APPEND);
//file_put_contents("/tmp/pcapi_log", "{$requestJSONStr}\n\n{$authToken}\n\n{$token}\n\n", FILE_APPEND);

if ( $token != $authToken )
{
    $requestJSONStr = json_encode($requestArr);
    $token = generateAuthToken($user["name"], $user["secretKey"], $requestJSONStr);
    if ( $token != $authToken )
    {
        //file_put_contents("/tmp/pcapi_log", "2----------------------------------------------\n", FILE_APPEND);
        //file_put_contents("/tmp/pcapi_log", "{$requestJSONStr}\n\n{$authToken}\n\n{$token}\n\n", FILE_APPEND);
        writeLog(LOG_ERR, "INVALID TOKEN. username = {$user['name']}");
        setHttpCode(403);
        exit(0);
    }
}

/**
 *              DATABASE: connect
 */
$sql = new Oracle_db(BASE_LOGIN, BASE_PASSWORD, BASE);
$sql->open();

if ($sql->getError() != '') //Connection error
{
    // Write log
    $status = 101;
    writeLog(LOG_ERR, "DATABASE CONNECT ERROR. result code={$status} msg = {$sql->getError()}");
    echo responseOnError($status);
    exit(0);
}

$query = "alter session set nls_date_format = 'dd.mm.yyyy hh24:mi:ss'";
$sql->parse($query);

/**
 *              Action: /identify
 */
if ($action == "identify") {
    $requestArr = requestIdentify($requestArr);
    $phn = $requestArr["phn"];
    if (empty($phn))
    {
        $status = 113;
        writeLog(LOG_ERR, "IDENTIFY ERROR. result code={$status} msg = phn is empty");
        echo responseOnError($status);
        exit(0);
    }

    $query = "begin API_PC.IdentifyList( :iPhoneID, :oSMS_code, :oAbonTypeList); end;";
    $sql->cursor();
    $sql->parse($query, false);
    $sql->bindByName(":iPhoneID", $phn, 20);
    $sql->bindByName(":oSMS_code", &$smsCode, 10);
    $sql->bindByNameCur("oAbonTypeList");
    $sql->execute();
    
    if ($sql->getError() != '')
    {
        $status = 102;
        writeLog(LOG_ERR, "IDENTIFY: DATABASE EXECUTE ERROR. result code={$status}, phn = {$phn} msg = {$sql->getError()}");
        echo responseOnError($status);
        exit(0);
    }

    $sql->executeCurs();
    $sql->fetchAllCur();
    $arrData = $sql->allCurResults;

    if (empty($smsCode) or ($smsCode == '0'))
    {
        $status = 110; // Not found
        writeLog(LOG_ERR, "IDENTIFY ERROR. result code={$status} msg = Abonent no found. phn = {$phn}");
        echo responseOnError($status);
        exit(0);
    }

    $accList = array();
    foreach($arrData as $row)
    {
        $accList[] = array(
            'abonType'  => $row['ABON_TYPE'],
            'abonName'  => iconv("windows-1251", "UTF-8", $row['ABON_NAME']),
            'accId'     => $row['RESOURCE_ID'],
            'login'     => $row['LOGIN']
        );
    }

    if (count($accList) == 0)
    {
        $query = "begin :oAbonType := API_PC.getAbonType3(:iPhoneID); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iPhoneID", $phn, 20);
        $sql->bindByName(":oAbonType", &$abonType, 2);
        $sql->execute();
    
        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "IDENTIFY: DATABASE EXECUTE ERROR. result code={$status}, phn = {$phn} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($abonType < 0)
        {
            $status = 110; // Not found
            writeLog(LOG_ERR, "IDENTIFY ERROR. result code={$status} DB result smsCode={$smsCode}, abonType={$abonType}. phn = {$phn}");
            echo responseOnError($status);
        }
        elseif ($abonType == 0)
        {
            $status = 126; // Access denied
            writeLog(LOG_ERR, "IDENTIFY ERROR. result code={$status} abonType={$abonType} Access denied. phn = {$phn}");
            echo responseOnError($status);
        }
        else
        {
            $status = 110; // Not found
            writeLog(LOG_ERR, "IDENTIFY ERROR. result code={$status} msg = Abonent no found. IdentifyList - return empty and getAbonType3 - return {$abonType}. phn = {$phn}");
            echo responseOnError($status);
        }
    }
    else
    {
        if ($requestArr["sendsms"] == 1)
        {
            // Send SMS
            writeLog(LOG_DEBUG, "IDENTIFY. phn = {$phn}, smsCode = {$smsCode}, sending sms...");
            $status = sendSMS($phn, $smsCode);
        }
        else
        {
            // without SMS
            writeLog(LOG_DEBUG, "IDENTIFY. phn = {$phn}, WITHOUT SMS");
            $status = 0;
        }
        
        if ($status == 0) {
            if ($requestArr["sendsms"] == 1) writeLog(LOG_DEBUG, "IDENTIFY. SMS SENDED SUCCESS");
            echo responseOnIdentify($accList);
        }
        else {
            writeLog(LOG_ERR, "IDENTIFY SEND SMS ERROR. result code={$status}. phn = {$phn}");
            echo responseOnError($status);
        }
    }
    
    exit(0);
}

/**
 *              Action: /verify
 */
if ($action == "verify") {
    $requestArr = requestVerify($requestArr);
    
    $phn = $requestArr["phn"];
    $smsCode = $requestArr["smsCode"];
    
    if (empty($phn))
    {
        $status = 113;
        writeLog(LOG_ERR, "VERIFY ERROR. result code={$status} msg = phn is empty");
        echo responseOnError($status);
        exit(0);
    }

    if (empty($smsCode))
    {
        $status = 120;
        writeLog(LOG_ERR, "VERIFY ERROR. result code={$status} msg = smsCode is empty. phn = {$phn}");
        echo responseOnError($status);
        exit(0);
    }

    $query = "begin :result := API_PC.Verify(:iPhoneID, :iSMS_code); end;";
    $sql->parse($query, false);
    $sql->bindByName(":iPhoneID", $phn, 20);
    $sql->bindByName(":iSMS_code", $smsCode, 10);
    $sql->bindByName(":result", &$status, 2);
    $sql->execute();

    if ($sql->getError() != '')
    {
        $status = 102;
        writeLog(LOG_ERR, "VERIFY: DATABASE EXECUTE ERROR. result code={$status}, phn = {$phn} msg = {$sql->getError()}");
        echo responseOnError($status);
        exit(0);
    }

    if ($status != 0)
    {
        $status = 120;
        writeLog(LOG_ERR, "VERIFY ERROR. result code={$status} msg = smsCode invalid. phn = {$phn}");
        echo responseOnError($status);
    }
    else
    {
        writeLog(LOG_DEBUG, "VERIFY. SUCCESS, phn = {$phn}");
        // HTTP CODE = 200, without body
    }

    exit(0);
}

/**
 *              Action: ABONENT
 */
if ($action == "abonent") 
{
    /**
     *              Action: /abonent/info
     */
    if ($param1 == "info")
    {
        $requestArr = requestAbonentInfo($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "ABONENT_INFO ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $abonInfoObj = new stdClass();
        $query = "declare wAbonInfo api_pc_aboninfo_t; 
                    begin API_PC.abonentInfo(:iAccId, :iLang, wAbonInfo); 
                        :oAbonType := wAbonInfo.AbonType; 
                        :oAccId := wAbonInfo.ResourceId; 
                        :oUserName := wAbonInfo.UserName; 
                        :oPhoneId := wAbonInfo.PhoneId; 
                        :oSaldo := wAbonInfo.Saldo; 
                        :oTariffId := wAbonInfo.TariffId; 
                        :oTariffName := wAbonInfo.TariffName; 
                        :oContractDate := to_char(wAbonInfo.ContractDate, 'yyyy-mm-dd'); 
                        :oEmail := wAbonInfo.Email; 
                        :oPhone := wAbonInfo.Phone; 
                        :oAddress := wAbonInfo.Address; 
                        :oDeviceCount := wAbonInfo.DeviceCount; 
                        :oDeviceActiveCount := wAbonInfo.DeviceActiveCount; 
                        :OffReasonName := wAbonInfo.OffReasonName;
                    end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId",             $accId, 10);
        $sql->bindByName(":iLang",              $lang, 10);
        $sql->bindByName(":oAbonType",          &$abonInfoObj->abonType, 10);
        $sql->bindByName(":oAccId",             &$abonInfoObj->accId, 10);
        $sql->bindByName(":oUserName",          &$abonInfoObj->UserName, 50);
        $sql->bindByName(":oPhoneId",           &$abonInfoObj->PhoneId, 20);
        $sql->bindByName(":oSaldo",             &$abonInfoObj->Saldo, 20);
        $sql->bindByName(":oTariffId",          &$abonInfoObj->TariffId, 10);
        $sql->bindByName(":oTariffName",        &$abonInfoObj->TariffName, 50);
        $sql->bindByName(":oContractDate",      &$abonInfoObj->contractDate, 20);
        $sql->bindByName(":oEmail",             &$abonInfoObj->email, 50);
        $sql->bindByName(":oPhone",             &$abonInfoObj->phone, 100);
        $sql->bindByName(":oAddress",           &$abonInfoObj->address, 100);
        $sql->bindByName(":oDeviceCount",       &$abonInfoObj->DeviceCount, 5);
        $sql->bindByName(":oDeviceActiveCount", &$abonInfoObj->DeviceActiveCount, 5);
        $sql->bindByName(":OffReasonName",      &$abonInfoObj->OffReasonName, 50);
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "ABONENT_INFO: DATABASE EXECUTE ERROR. result code={$status}, acc_id = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ( ($abonInfoObj->abonType < 0) or ($abonInfoObj->accId <= 0) )
        {
            $status = 110; // Not found
            writeLog(LOG_ERR, "ABONENT_INFO. result code={$status} msg = Abonent no found. acc_id = {$accId}");
            echo responseOnError($status);
        }
        elseif ($abonInfoObj->abonType == 0)
        {
            $status = 121; // Access denied
            writeLog(LOG_ERR, "ABONENT_INFO. result code={$status} msg = Access denied (Abontype = 0). acc_id = {$accId}");
            echo responseOnError($status);
        }
        else
        {
            writeLog(LOG_DEBUG, "ABONENT_INFO. SUCCESS, acc_id = {$accId}, lang = {$lang}");
            echo responseOnAbonentInfo($abonInfoObj);
        }

        exit(0);
    }

    /**
     *              Action: /abonent/edit
     */
    if ($param1 == "edit")
    {
        $requestArr = requestAbonentEdit($requestArr);
        $accId = $requestArr["acc_id"];
        $email = $requestArr["email"];
        $phone = $requestArr["phone"];

        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "ABONENT_EDIT ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if (empty($email))
        {
            $status = 116;
            writeLog(LOG_ERR, "ABONENT_EDIT ERROR. result code={$status} msg = email ({$email}) invalid");
            echo responseOnError($status);
            exit(0);
        }
        
        if (empty($phone))
        {
            $status = 117;
            writeLog(LOG_ERR, "ABONENT_EDIT ERROR. result code={$status} msg = phone ({$phone}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin API_PC.abonentEdit(:iAccId, :iEmail, :iPhone, :oStatus); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId",     $accId, 10);
        $sql->bindByName(":iEmail",     $email, 40);
        $sql->bindByName(":iPhone",     $phone, 11);
        $sql->bindByName(":oStatus",    &$status, 1);
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "ABONENT_EDIT: DATABASE EXECUTE ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status != 0)
        {
            if ($status == 1)
            {
                $status = 110; // Not found
                writeLog(LOG_ERR, "ABONENT_EDIT. result code={$status} msg = Abonent no found. accId = {$accId}");
                echo responseOnError($status);
            }
            else // 2
            {
                $status = 122; // not available
                writeLog(LOG_ERR, "ABONENT_EDIT. result code={$status} msg = Not available for Temporary and Single abonents. accId = {$accId}");
                echo responseOnError($status);
            }    
        }
        
        exit(0);
    }
}

/**
 *              Action: ACCT
 */
if ($action == "acct") 
{
    /**
     *              Action: /acct/balance
     */
    if ($param1 == "balance")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "ACCT_BALANCE ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.acctBalance(:iAccId, :oStatus); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":oStatus", &$status, 1);
        $sql->bindByName(":result", &$saldo, 20);
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "ACCT_BALANCE: DATABASE EXECUTE ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status != 0)
        {
            if ($status == 1)
            {
                $status = 110; // Not found
                writeLog(LOG_ERR, "ACCT_BALANCE. result code={$status} msg = Abonent no found. accId = {$accId}");
                echo responseOnError($status);
            }
            else // 2
            {
                $status = 121; // not available
                writeLog(LOG_ERR, "ACCT_BALANCE. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
                echo responseOnError($status);
            }

            exit(0);
        }

        echo responseOnAcctBalance($saldo);
        exit(0);
    }

    /**
     *              Action: /acct/wifipassword
     */
    if ($param1 == "wifipassword")
    {
        $requestArr = requestAcctWifiPassword($requestArr);
        $accId = $requestArr["acc_id"];
        $currPwd = $requestArr["curr_password"];
        $newPwd = $requestArr["new_password"];

        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "ACCT_WIFIPASSWORD ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if (empty($currPwd) or empty($newPwd))
        {
            $status = 118;
            writeLog(LOG_ERR, "ACCT_WIFIPASSWORD ERROR. result code={$status} msg = current or new password ({$currPwd}, {$newPwd}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.acctWifiPassword(:iAccId, :iCurrentPassword, :iNewPassword, :oStatus); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":iCurrentPassword", $currPwd, 20);
        $sql->bindByName(":iNewPassword", $newPwd, 20);
        $sql->bindByName(":oStatus", &$status, 1);
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "ACCT_WIFIPASSWORD: DATABASE EXECUTE ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status != 0)
        {
            if ($status == 1)
            {
                $status = 110; // Not found
                writeLog(LOG_ERR, "ACCT_WIFIPASSWORD. result code={$status} msg = Abonent no found. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($status == 2)
            {
                $status = 121; // not available
                writeLog(LOG_ERR, "ACCT_WIFIPASSWORD. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
                echo responseOnError($status);
            }
            else // 3
            {
                $status = 123; // mismatch current password 
                writeLog(LOG_ERR, "ACCT_WIFIPASSWORD. result code={$status} msg = Mismatch current password. accId = {$accId}");
                echo responseOnError($status);
            }
        }

        exit(0);
    }

    /**
     *              Action: /acct/payments
     */
    if ($param1 == "payments")
    {
        $requestArr = requestAcctPayments($requestArr);
        $accId = $requestArr["acc_id"];
        $payMonth = $requestArr["pay_month"];

        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "ACCT_PAYMENTS ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if (!checkRequestMonth($payMonth))
        {
            $status = 115;
            writeLog(LOG_ERR, "ACCT_PAYMENTS ERROR. result code={$status} msg = pay_month ({$payMonth}) invalid. acc_id = {$accId}");
            echo responseOnError($status);
            exit(0);
        }

        $payMonth01 = getMonthFirstDay($payMonth);

        $query = "begin :result := API_PC.acctPayments(:iAccId, to_date(:iBDate, 'dd.mm.yyyy'), :iLang, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,         10);
        $sql->bindByName("iBDate",  $payMonth01,    20);
        $sql->bindByName("iLang",   $lang,          10);
        $sql->bindByName("result",  &$status,       1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "ACCT_PAYMENTS: DATABASE EXECUTE #1 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        $sql->executeCurs();
        $sql->fetchAllCur();
        $pays = $sql->allCurResults;

        if ($status > 0)
        {
            if ( $status == 1 )
            {
                $status = 110; // Not found
                writeLog(LOG_ERR, "ACCT_PAYMENTS. result code={$status} msg = Resource no found. accId = {$accId}");
                echo responseOnError($status);
            }
            else
            {
                $status = 121; // Access denied
                writeLog(LOG_ERR, "ACCT_PAYMENTS. result code={$status} msg = Access denied (Abontype = 0). accId = {$accId}");
                echo responseOnError($status);
            }
            exit(0);
        }

        $payments = array();
        foreach($pays as $row)
        {
            $paysystem = $row["NBANK_KASSA"];
            if ($row["BANK_KASSA"] == 5) $paysystem = $row["NOTE"];
            $payments[] = array(    "payment_id"    => $row["PAYMENT_ID"],
                                    "payment_date"  => $row["REG_DATE"],
                                    "amount"        => $row["LOCAL_AMOUNT"] * 100, // tiyinah
                                    "payment_system"=> iconv("windows-1251", "UTF-8", $paysystem),
                                    "payment_status"=> iconv("windows-1251", "UTF-8", $row["PAYMENT_STATUS"])
                                );
        }

        echo responseOnAcctPayments($payments);
        writeLog(LOG_DEBUG, "ACCT_PAYMENTS. SUCCESS, acc_id = {$accId}, pay_month = {$payMonth}");
        exit(0);
    }
}

/**
 *              Action: TRAFFIC
 */
if ($action == "traffic") 
{
    /**
     *              Action: /traffic/detail
     */
    if ($param1 == "detail")
    {
        $requestArr = requestTrafficDetail($requestArr);
        $accId = $requestArr["acc_id"];
        $detailMonth = $requestArr["detail_month"];

        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "TRAFFIC_DETAIL ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if (!checkRequestMonth($detailMonth))
        {
            $status = 115;
            writeLog(LOG_ERR, "TRAFFIC_DETAIL ERROR. result code={$status} msg = detail_month ({$detailMonth}) invalid. acc_id = {$accId}");
            echo responseOnError($status);
            exit(0);
        }

        $detailMonthYYMM = getMonthYYMM($detailMonth);

        $query = "begin :result := API_PC.TrafficDetail(:iAccId, :iLang, :iYymm, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,             10);
        $sql->bindByName("iLang",   $lang,              10);
        $sql->bindByName("iYymm",   $detailMonthYYMM,   10);
        $sql->bindByName("result",  &$status,           1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "TRAFFIC_DETAIL: DATABASE EXECUTE #2 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        $sql->executeCurs();
        $sql->fetchAllCur();
        $arrData = $sql->allCurResults;
        $details = array();
        foreach($arrData as $row)
        {
            $details[] = array(
                'event_time'        => $row['EVENT_TIME'],
                'location_info'     => $row['CALLING_STATION_ID'],
                'traffic_input'     => $row['ACCT_INPUT_OCTETS'],
                'traffic_output'    => $row['ACCT_OUTPUT_OCTETS'],
                'pocket_info'       => iconv("windows-1251", "UTF-8", $row['TARIFF_NAME'])
            );
        }

        echo responseOnTrafficDetail($details);
        exit(0);
    }
}

/**
 *              Action: DEVICE
 */
if ($action == "device") 
{
    /**
     *              Action: /device/newoneclick
     */
    if ($param1 == "newoneclick")
    {
        $requestArr = requestOnDeviceNewOneClick($requestArr);
        $phone      = $requestArr["phn"];
        $accId      = $requestArr["acc_id"];
        $smsCode    = $requestArr["smsCode"];

        if (empty($phone))
        {
            $status = 113;
            writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK ERROR. result code={$status} msg = phn is empty");
            echo responseOnError($status);
            exit(0);
        }

        if (!empty($smsCode))
        {
            // Verify SMS CODE
            if (empty($accId)) 
            {
                $status = 120;
                writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK ERROR. result code={$status} msg = acc_id is empty. phn = {$phone}");
                echo responseOnError($status);
                exit(0);
            }

            $_COOKIE['PHPSESSID'] = "SESSID-APIPC-{$phone}-{$accId}";
            session_start();
            if ($smsCode != $_SESSION['smscode'])
            {
                $status = 120;
                $code   = $_SESSION['smscode'];
                writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK ERROR. result code={$status} msg = smsCode ({$smsCode} != {$code}) invalid. phn = {$phone}");
                echo responseOnError($status);
                exit(0);
            }

            // goto block NEW
            writeLog(LOG_DEBUG, "DEVICE_NEW_ONECLICK. SMS VERIFY SUCCESS. phn = {$phone}, accId = {$accId}");
            $param1 = "new";
            //exit(0);
        }
        else
        {
            // Identify
            if (empty($accId)) $accId = null;

            $query = "begin API_PC.DeviceNewOneClick( :iPhone, :iAccId, :oSMS_code, :oCursor); end;";
            $sql->cursor();
            $sql->parse($query, false);
            $sql->bindByName(":iPhone",     $phone,     20);
            $sql->bindByName(":iAccId",     $accId,     10);
            $sql->bindByName(":oSMS_code", &$smsCode,   10);
            $sql->bindByNameCur("oCursor");
            $sql->execute();

            if ($sql->getError() != '')
            {
                $status = 103;
                writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK: DATABASE EXECUTE #1 ERROR. phn={$phone}, accId = {$accId} msg = {$sql->getError()}");
                echo responseOnError($status);
                exit(0);
            }
            
            $smsgen = 0;
            $sql->executeCurs();
            $sql->fetchAllCur();
            $arrData = $sql->allCurResults;
            $accList = array();
            foreach($arrData as $row)
            {
                $accList[] = array(
                    'abonType'  => $row['ABON_TYPE'],
                    'accId'     => $row['ACC_ID'],
                    'login'     => $row['RES_LOGIN'],
                    'contract'  => $row['CONTRACT_NUMBER'],
                    'saldo'     => $row['ACC_BALANCE'],
                    'cost'      => $row['SRV_COST']
                );
            }

            if (count($accList) == 0)
            {
                $status = 110; // Not found
                writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK ERROR. result code={$status} phn = {$phone}");
                echo responseOnError($status);
                exit(0);
            }
            elseif(count($accList) == 1)
            {
                $accId  = $accList[0]['accId'];

                if ($accList[0]['saldo'] < $accList[0]['cost'])
                {
                    $status = 132; // Tekushiy status ili balans ne pozvolyaet
                    writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK. Your balance does not allow changing. phn = {$phone} accId = {$accId}");
                    echo responseOnError($status);
                    exit(0);
                }
                
                // Send SMS
                writeLog(LOG_DEBUG, "DEVICE_NEW_ONECLICK. phn = {$phone}, smsCode = {$smsCode}, sending sms...");
                $status = sendSMS($phone, $smsCode);
                
                if ($status != 0) {
                    writeLog(LOG_ERR, "DEVICE_NEW_ONECLICK SEND SMS ERROR. result code={$status}. phn = {$phone}");
                    echo responseOnError($status);
                    exit(0);
                }

                $smsgen = 1;
                writeLog(LOG_DEBUG, "DEVICE_NEW_ONECLICK. SMS SENDED SUCCESS");
                $_COOKIE['PHPSESSID'] = "SESSID-APIPC-{$phone}-{$accId}";
                session_start();
                $_SESSION['smscode'] = $smsCode;
            }

            echo responseOnDeviceNewOneClick($smsgen, $accList);
            exit(0);
        }
    }

    /**
     *              Action: /device/new
     */
    if ($param1 == "new")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "DEVICE_NEW ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $bdate = date("Y-m-d");							// tekushiy den
        $srvId = null;
        $query = "begin API_PC.DeviceNew(:iAccId, :iSrvId, to_date(:iBDate, 'yyyy-mm-dd'), :oStatus, :oErrorMsg); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":iSrvId", $srvId, 10);
        $sql->bindByName(":iBDate", $bdate, 20);
        $sql->bindByName(":oStatus", &$resultStatus, 2);
        $sql->bindByName(":oErrorMsg", &$resultMessage, 2000);
        $sql->executeDefault();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "DEVICE_NEW: DATABASE EXECUTE ERROR. result code={$status}, srvId={$srvId}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($resultStatus != 0)
        {
            $sql->rollback();
            if ($resultStatus == 1)
            {   
                $status = 110; // Not found
                writeLog(LOG_ERR, "DEVICE_NEW. result code={$status} msg = Abonent no found. phn = {$phn}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 2)
            {   
                $status = 121; // not available
                writeLog(LOG_ERR, "DEVICE_NEW. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 3) // est pustiye MAC-i
            {
                $status = 127; // have empty MAC
                writeLog(LOG_ERR, "DEVICE_NEW. result code={$status}, srvId={$srvId} msg = DB return: have empty MAC ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 4) // Razoviy ne mojet podklyuchit "Dop ustroysto"
            {
                $status = 128; // razoviy ne mojet
                writeLog(LOG_ERR, "DEVICE_NEW. result code={$status}, srvId={$srvId} msg = DB return: Is't available for single abonent ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif (in_array($resultStatus, array(12, 17))) // Tekushiy status ili balans ne pozvolyaet
            {
                $status = 132; // Tekushiy status ili balans ne pozvolyaet
                writeLog(LOG_ERR, "DEVICE_NEW. result code={$status}, srvId={$srvId} msg = DB return: Your balance does not allow changing ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 20) // Net uslugi po tarifnomu plane
            {
                $status = 100; // System error
                writeLog(LOG_ERR, "DEVICE_NEW. result code={$status}, srvId={$srvId} msg = DB return: SRV not found in TP ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            else //5, 11,12....
            {
                $status = 100; // System error 
                $msg = iconv("windows-1251", "UTF-8", $resultMessage);
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: UNKNOWN ({$resultStatus} - {$msg}) error. accId = {$accId}");
                echo responseOnError($status);
            }
        }
        else
        {
            $sql->commit();
        }

        exit(0);
    }

    /**
     *              Action: /device/delete
     */
    if ($param1 == "delete")
    {
        $requestArr = requestOnDeviceDelete($requestArr);
        $accId          = $requestArr["acc_id"];
        $srvPermitId    = $requestArr["permit_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "DEVICE_DISCONNECT ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if ($srvPermitId <= 0)
        {
            $status = 119;
            writeLog(LOG_ERR, "DEVICE_DISCONNECT ERROR. result code={$status} msg = permit_id ({$srvPermitId}) invalid");
            echo responseOnError($status);
            exit(0);
        }
       
        $query = "begin API_PC.DeviceDel(:iAccId, NULL, :iSrvPermitId, :oStatus, :oErrorMsg); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":iSrvPermitId", $srvPermitId, 10);
        $sql->bindByName(":oStatus", &$resultStatus, 1);
        $sql->bindByName(":oErrorMsg", &$resultMessage, 2000);
        $sql->executeDefault();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "DEVICE_DISCONNECT: DATABASE EXECUTE ERROR. result code={$status}, srvId={$srvId}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($resultStatus != 0)
        {
            $sql->rollback();
            if ($resultStatus == 1)
            {   
                $status = 110; // Not found
                writeLog(LOG_ERR, "DEVICE_DISCONNECT. result code={$status} msg = Abonent no found. phn = {$phn}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 2)
            {   
                $status = 122; // not available
                writeLog(LOG_ERR, "DEVICE_DISCONNECT. result code={$status} msg = Not available for Temporary and Single abonents. accId = {$accId}");
                echo responseOnError($status);
            }
            else //4, 11,12....
            {
                $status = 100; // System error 
                writeLog(LOG_ERR, "DEVICE_DISCONNECT. result code={$status}, srvId={$srvId} msg = DB return: UNKNOWN ({$resultStatus} - {$resultMessage}) error. accId = {$accId}");
                echo responseOnError($status);
            }
        }
        else
        {
            $sql->commit();
        }

        exit(0);
    }

    /**
     *              Action: /device/list
     */
    if ($param1 == "list")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "DEVICE_LIST ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.DeviceList(:iAccId, :oSrvId, :oSrvCost, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,             10);
        $sql->bindByName("oSrvId",  &$srvId,            5);
        $sql->bindByName("oSrvCost",&$srvCost,          10);
        $sql->bindByName("result",  &$status,           1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "DEVICE_LIST: DATABASE EXECUTE #2 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        $sql->executeCurs();
        $sql->fetchAllCur();
        $arrData = $sql->allCurResults;

        if ($status == 1)
        {   
            $status = 110; // Not found
            writeLog(LOG_ERR, "DEVICE_LIST. result code={$status} msg = Abonent no found. phn = {$phn}");
            echo responseOnError($status);
        }
        elseif ($status == 2)
        {   
            $status = 121; // not available
            writeLog(LOG_ERR, "DEVICE_LIST. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
            echo responseOnError($status);
        }
        else
        {
            $devices = array();
            foreach($arrData as $row)
            {
                $devices[] = array(
                    'permit_id'     => $row['PERMIT_ID'],
                    'connect_date'  => $row['CONNECT_DATE'],
                    'mac'           => $row['MAC'],
                    'ip'            => $row['IPADDR'],
                    'readonly'      => $row['DEVICE_READONLY']
                );
            }

            echo responseOnDeviceList($devices, $srvId, $srvCost);
        }

        exit(0);
    }
}

/**
 *              Action: TARIFF
 */
if ($action == "tariff") 
{
    /**
     *              Action: /tariff/available
     */
    if ($param1 == "available")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "TARIFF_AVAILABLE ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.PermSRVRight(:iAccId, :iLang, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,     10);
        $sql->bindByName("iLang",   $lang,      10);
        $sql->bindByName("result",  &$status,   1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "TARIFF_AVAILABLE: DATABASE EXECUTE #2 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status == 1)
        {   
            $status = 110; // Not found
            writeLog(LOG_ERR, "TARIFF_AVAILABLE. result code={$status} msg = Abonent no found. phn = {$phn}");
            echo responseOnError($status);
        }
        elseif ($status == 2)
        {   
            $status = 121; // not available
            writeLog(LOG_ERR, "TARIFF_AVAILABLE. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
            echo responseOnError($status);
        }
        else
        {
            $sql->executeCurs();
            $sql->fetchAllCur();
            $arrData = $sql->allCurResults;
            
            $srvList = array();
            foreach($arrData as $row)
            {
                $srvList[] = array(
                    'tariff_id'     => $row['SRV_ID'],
                    'tariff_name'   => iconv("windows-1251", "UTF-8", $row['SRVNAME']),
                    'cost'          => $row['COST'] * 100, // tiyinah
                    'tspd'          => $row['TSPD'],
                    'spdu'          => $row['SPDU'],
                    'tprd'          => $row['TPRD'],
                    'prdu'          => $row['PRDU'],
                    'vol'           => $row['TVOL']
                );
            }
    
            echo responseOnTariffAvailable($srvList);
        }

        exit(0);
    }

    /**
     *              Action: /tariff/connected
     */
    if ($param1 == "connected")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "TARIFF_CONNECTED ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.PermSRVLeft(:iAccId, :iLang, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,     10);
        $sql->bindByName("iLang",   $lang,      10);
        $sql->bindByName("result",  &$status,   1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "TARIFF_CONNECTED: DATABASE EXECUTE #2 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status == 1)
        {   
            $status = 110; // Not found
            writeLog(LOG_ERR, "TARIFF_CONNECTED. result code={$status} msg = Abonent no found. phn = {$phn}");
            echo responseOnError($status);
        }
        elseif ($status == 2)
        {   
            $status = 121; // not available
            writeLog(LOG_ERR, "TARIFF_CONNECTED. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
            echo responseOnError($status);
        }
        else
        {
            $sql->executeCurs();
            $sql->fetchAllCur();
            $arrData = $sql->allCurResults;
            
            $srvList = array();
            foreach($arrData as $row)
            {
                $srvList[] = array(
                    'tariff_id'     => $row['SRV_ID'],
                    'tariff_name'   => iconv("windows-1251", "UTF-8", $row['SRVNAME']),
                    'date_begin'    => $row['BDATE'],
                    'date_end'      => $row['EDATE'],
                    'tariff_isoff'  => $row['ISOFF']
                );
            }

            echo responseOnTariffConnected($srvList);
        }

        exit(0);
    }

    /**
     *              Action: /tariff/connect
     */
    if ($param1 == "connect")
    {
        $requestArr = requestTariffConnect($requestArr);
        $accId = $requestArr["acc_id"];
        $srvId = $requestArr["tariff_id"];
        $bdate = $requestArr["tariff_conndate"];

        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "TARIFF_CONNECT ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if ($srvId <= 0)
        {
            $status = 119;
            writeLog(LOG_ERR, "TARIFF_CONNECT ERROR. result code={$status} msg = tariff_id ({$srvId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $dateNextT = mktime(0, 0, 0, date("n"), date("t"), date("Y")) + 86400;
		$dateNext1 = date("Y-m-d", $dateNextT);				// 1oe sled. mesyaca
        $dateCurr  = date("Y-m-d");							// tekushiy den
        
        if ( ($bdate != $dateNext1) and ($bdate != $dateCurr) )
        {
            $status = 124;
            writeLog(LOG_ERR, "TARIFF_CONNECT ERROR. result code={$status} srvId={$srvId}, msg = tariff_conndate ({$bdate}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin API_PC.NewPermitSrv(:iAccId, :iSrvId, to_date(:iBDate, 'yyyy-mm-dd'), :oStatus, :oErrorMsg); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":iSrvId", $srvId, 10);
        $sql->bindByName(":iBDate", $bdate, 20);
        $sql->bindByName(":oStatus", &$resultStatus, 2);
        $sql->bindByName(":oErrorMsg", &$resultMessage, 2000);
        $sql->executeDefault();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "TARIFF_CONNECT: DATABASE EXECUTE ERROR. result code={$status}, srvId={$srvId}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($resultStatus != 0)
        {
            $sql->rollback();
            if ($resultStatus == 1)
            {   
                $status = 110; // Not found
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status} msg = Abonent no found. phn = {$phn}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 2)
            {   
                $status = 121; // not available
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 3) // est pustiye MAC-i
            {
                $status = 127; // have empty MAC
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: have empty MAC ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 4) // Razoviy ne mojet podklyuchit "Dop ustroysto"
            {
                $status = 128; // razoviy ne mojet
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: Is't available for single abonent ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif (in_array($resultStatus, array(12, 17))) // Tekushiy status ili balans ne pozvolyaet
            {
                $status = 129; // Tekushiy status ili balans ne pozvolyaet
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: Your balance does not allow changing ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            else //5, 11,12....
            {
                $status = 100; // System error 
                $msg = iconv("windows-1251", "UTF-8", $resultMessage);
                writeLog(LOG_ERR, "TARIFF_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: UNKNOWN ({$resultStatus} - {$msg}) error. accId = {$accId}");
                echo responseOnError($status);
            }
        }
        else
        {
            $sql->commit();
        }

        exit(0);
    }

    /**
     *              Action: /tariff/disconnect
     */
    if ($param1 == "disconnect")
    {
        $requestArr = requestTariffDisconnect($requestArr);
        $accId = $requestArr["acc_id"];
        $srvId = $requestArr["tariff_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "TARIFF_DISCONNECT ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if ($srvId <= 0)
        {
            $status = 119;
            writeLog(LOG_ERR, "TARIFF_DISCONNECT ERROR. result code={$status} msg = tariff_id ({$srvId}) invalid");
            echo responseOnError($status);
            exit(0);
        }
       
        $query = "begin API_PC.DelPermitSrv(:iAccId, :iSrvId, NULL, :oStatus, :oErrorMsg); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":iSrvId", $srvId, 10);
        $sql->bindByName(":oStatus", &$resultStatus, 1);
        $sql->bindByName(":oErrorMsg", &$resultMessage, 2000);
        $sql->executeDefault();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "TARIFF_DISCONNECT: DATABASE EXECUTE ERROR. result code={$status}, srvId={$srvId}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($resultStatus != 0)
        {
            $sql->rollback();
            if ($resultStatus == 1)
            {   
                $status = 110; // Not found
                writeLog(LOG_ERR, "TARIFF_DISCONNECT. result code={$status} msg = Abonent no found. phn = {$phn}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 2)
            {   
                $status = 122; // not available
                writeLog(LOG_ERR, "TARIFF_DISCONNECT. result code={$status} msg = Not available for Temporary and Single abonents. accId = {$accId}");
                echo responseOnError($status);
            }
            else //4, 11,12....
            {
                $status = 100; // System error 
                writeLog(LOG_ERR, "TARIFF_DISCONNECT. result code={$status}, srvId={$srvId} msg = DB return: UNKNOWN ({$resultStatus} - {$resultMessage}) error. accId = {$accId}");
                echo responseOnError($status);
            }
        }
        else
        {
            $sql->commit();
        }

        exit(0);
    }
}

/**
 *              Action: SERVICE
 */
if ($action == "service") 
{
    /**
     *              Action: /service/available
     */
    if ($param1 == "available")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "SERVICE_AVAILABLE ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.AddSRVRight(:iAccId, :iLang, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,     10);
        $sql->bindByName("iLang",   $lang,      10);
        $sql->bindByName("result",  &$status,   1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "SERVICE_AVAILABLE: DATABASE EXECUTE #2 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status == 1)
        {   
            $status = 110; // Not found
            writeLog(LOG_ERR, "SERVICE_AVAILABLE. result code={$status} msg = Abonent no found. phn = {$phn}");
            echo responseOnError($status);
        }
        elseif ($status == 2)
        {   
            $status = 122; // not available
            writeLog(LOG_ERR, "SERVICE_AVAILABLE. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
            echo responseOnError($status);
        }
        else
        {
            $sql->executeCurs();
            $sql->fetchAllCur();
            $arrData = $sql->allCurResults;

            $srvList = array();
            foreach($arrData as $row)
            {
                $srvList[] = array(
                    'service_id'     => $row['SRV_ID'],
                    'service_name'   => iconv("windows-1251", "UTF-8", $row['SRVNAME']),
                    'cost'          => $row['COST'] * 100 // tiyinah
                );
            }
    
            echo responseOnServiceAvailable($srvList);
        }

        exit(0);
    }

    /**
     *              Action: /service/connected
     */
    if ($param1 == "connected")
    {
        $requestArr = requestOnlyAcct($requestArr);
        $accId = $requestArr["acc_id"];
        
        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "SERVICE_CONNECTED ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin :result := API_PC.AddSRVLeft(:iAccId, :iLang, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName("iAccId",  $accId,     10);
        $sql->bindByName("iLang",   $lang,      10);
        $sql->bindByName("result",  &$status,   1);
        $sql->bindByNameCur("oCursor");
        $sql->execute();

        if ($sql->getError() != '')
        {
            $status = 103;
            writeLog(LOG_ERR, "SERVICE_CONNECTED: DATABASE EXECUTE #2 ERROR. result code={$status}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($status == 1)
        {   
            $status = 110; // Not found
            writeLog(LOG_ERR, "SERVICE_CONNECTED. result code={$status} msg = Abonent no found. phn = {$phn}");
            echo responseOnError($status);
        }
        elseif ($status == 2)
        {   
            $status = 122; // not available
            writeLog(LOG_ERR, "SERVICE_CONNECTED. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
            echo responseOnError($status);
        }
        else
        {
            $sql->executeCurs();
            $sql->fetchAllCur();
            $arrData = $sql->allCurResults;
            
            $srvList = array();
            foreach($arrData as $row)
            {
                $srvList[] = array(
                    'permit_id'     => $row['SRV_PERMIT_ID'],
                    'service_id'    => $row['SRV_ID'],
                    'service_name'  => iconv("windows-1251", "UTF-8", $row['SRVNAME']),
                    'service_param' => iconv("windows-1251", "UTF-8", $row['PARAMS']),
                    'date_begin'    => $row['BDATE'],
                    'date_end'      => $row['EDATE']
                );
            }

            echo responseOnServiceConnected($srvList);
        }

        exit(0);
    }

    /**
     *              Action: /service/connect
     */
    if ($param1 == "connect")
    {
        $requestArr = requestServiceConnect($requestArr);
        $accId = $requestArr["acc_id"];
        $srvId = $requestArr["service_id"];
        $bdate = $requestArr["service_conndate"];

        if ($accId <= 0)
        {
            $status = 114;
            writeLog(LOG_ERR, "SERVICE_CONNECT ERROR. result code={$status} msg = acc_id ({$accId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        if ($srvId <= 0)
        {
            $status = 130;
            writeLog(LOG_ERR, "SERVICE_CONNECT ERROR. result code={$status} msg = service_id ({$srvId}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $dateNextT = mktime(0, 0, 0, date("n"), date("t"), date("Y")) + 86400;
		$dateNext1 = date("Y-m-d", $dateNextT);				// 1oe sled. mesyaca
        $dateCurr  = date("Y-m-d");							// tekushiy den
        
        if ( ($bdate != $dateNext1) and ($bdate != $dateCurr) )
        {
            $status = 131;
            writeLog(LOG_ERR, "SERVICE_CONNECT ERROR. result code={$status} srvId={$srvId}, msg = service_conndate ({$bdate}) invalid");
            echo responseOnError($status);
            exit(0);
        }

        $query = "begin API_PC.NewAddSrv(:iAccId, :iSrvId, to_date(:iBDate, 'yyyy-mm-dd'), :oStatus, :oErrorMsg); end;";
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 20);
        $sql->bindByName(":iSrvId", $srvId, 10);
        $sql->bindByName(":iBDate", $bdate, 20);
        $sql->bindByName(":oStatus", &$resultStatus, 2);
        $sql->bindByName(":oErrorMsg", &$resultMessage, 2000);
        $sql->executeDefault();

        if ($sql->getError() != '')
        {
            $status = 102;
            writeLog(LOG_ERR, "SERVICE_CONNECT: DATABASE EXECUTE ERROR. result code={$status}, srvId={$srvId}, accId = {$accId} msg = {$sql->getError()}");
            echo responseOnError($status);
            exit(0);
        }

        if ($resultStatus != 0)
        {
            $sql->rollback();
            if ($resultStatus == 1)
            {   
                $status = 110; // Not found
                writeLog(LOG_ERR, "SERVICE_CONNECT. result code={$status} msg = Abonent no found. phn = {$phn}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 2)
            {   
                $status = 122; // not available
                writeLog(LOG_ERR, "SERVICE_CONNECT. result code={$status} msg = Not available for Temporary abonents. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 3) // est pustiye MAC-i
            {
                $status = 127; // have empty MAC
                writeLog(LOG_ERR, "SERVICE_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: have empty MAC ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif ($resultStatus == 4) // Razoviy ne mojet podklyuchit "Dop ustroysto"
            {
                $status = 128; // razoviy ne mojet
                writeLog(LOG_ERR, "SERVICE_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: Is't available for single abonent ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            elseif (in_array($resultStatus, array(12, 17))) // Tekushiy status ili balans ne pozvolyaet
            {
                $status = 132; // Tekushiy status ili balans ne pozvolyaet
                writeLog(LOG_ERR, "SERVICE_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: Your balance does not allow changing ({$resultStatus}) error. accId = {$accId}");
                echo responseOnError($status);
            }
            else //4, 11,12....
            {
                $status = 100; // System error 
                writeLog(LOG_ERR, "SERVICE_CONNECT. result code={$status}, srvId={$srvId} msg = DB return: UNKNOWN ({$resultStatus} - {$resultMessage}) error. accId = {$accId}");
                echo responseOnError($status);
            }
        }
        else
        {
            $sql->commit();
        }

        exit(0);
    }
}

/**
 *              Undefined Action
 */
$status = 109;
writeLog(LOG_ERR, "UNDEFINED METHOD. Action = {$action}, param1={$param1}, param2={$param2}. Result code={$status}");
echo responseOnError($status);
