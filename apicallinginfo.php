#Step-1
<?php

date_default_timezone_set("Asia/Calcutta"); 


function getExceptionCode ($key) {
	$exceptionCode = [
		"00" => "active",
		"01" => "hotlist",
		"02" => "exempted",
		"03" => "low balance",
		"04" => "invalid carriage",
		"05" => "blacklist",
		"06" => "closed/replaced",
		"02,01" => "blacklist",
		"03,02,01" => "blacklist"
	];
	
	return $exceptionCode[$key];
}


function getVehicleType ($key) {
$vehicleCode = array(
    "1" => "two wheeler",
    "2" => "three wheeler passenger",
    "3" => "three wheeler freight",
    "4" => "car/jeep/van",
    "5" => "light commercial vehicle 2-axle",
    "6" => "light commercial vehicle 2-axle",
    "7" => "bus 2-axle",
    "8" => "bus 3-axle",
    "9" => "mini bus",
    "10" => "truck 2-axle",
    "11" => "truck 3-axle",
    "12" => "truck 4-axle",
    "13" => "truck 5-axle",
    "14" => "truck 6-axle",
    "15" => "truck multi-axle",
    "16" => "earth moving machinery",
    "17" => "heavy construction machinery",
    "18" => "tractor",
    "19" => "tractor with trailer",
    "20" => "tata ace & mini light commercial vehicle"
);
	return $vehicleCode[$key];
}

function getRandomNumber() {
    $timestamp = time();
    //echo $timestamp; die;
    //$randomNumber = mt_rand();
    return str_shuffle($timestamp); //. $randomNumber;
}

function getSignedUrl($data) {
	$curl = curl_init();
	//echo $data; die;
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'http://127.0.0.1:5000/sign-xml',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>"$data",
	  CURLOPT_HTTPHEADER => array(
		'Content-Type: text/plain'
	  ),
	));
	$response = curl_exec($curl);
	//echo $response; die;
	curl_close($curl);
	return $response;
 
}

function getTagInfo($tagId) {

    $randomNumber = getRandomNumber();
	date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
    $ts = date('Y-m-d\TH:i:s');
    $payload = '<etc:ReqTagDetails xmlns:etc="http://npci.org/etc/schema/"><Head ver="1.0" ts="'.$ts.'" orgId="POAA" msgId="CD712305'.$randomNumber.'" /><Txn id="CD712305'.$randomNumber.'" note="'.$ts.'" refId="" refUrl="" ts="'.$ts.'" type="FETCH" orgTxnId=""><Vehicle TID="" vehicleRegNo="" tagId="'.$tagId.'" /></Txn></etc:ReqTagDetails>';
    $url = "https://netc-acq.airtelbank.com:7443/etc/ReqTagDetails/10";
    $filePath = getSignedUrl($payload);
	$signedxml = file_get_contents($filePath);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $signedxml);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch); 
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $response = simplexml_load_string(trim($result));
    $response = json_decode(json_encode($response),true);
    file_put_contents("./taginfoapi/respone_CD712305_$randomNumber.xml", $result);
    $msgId = isset($response['Head']['@attributes']['msgId']) ? $response['Head']['@attributes']['msgId'] : null;
    $orgId = isset($response['Head']['@attributes']['orgId']) ? $response['Head']['@attributes']['orgId'] : null;
    $txnId = isset($response['Txn']['@attributes']['id']) ? $response['Txn']['@attributes']['id'] : null;
    $note = isset($response['Txn']['@attributes']['note']) ? $response['Txn']['@attributes']['note'] : null;
    $respCode = isset($response['Txn']['Resp']['@attributes']['respCode']) ? $response['Txn']['Resp']['@attributes']['respCode'] : null;
    $resTag = isset($response['Txn']['Resp']['@attributes']['result']) ? $response['Txn']['Resp']['@attributes']['result'] : null;
    $errCode = isset($response['Txn']['Resp']['Vehicle']['@attributes']['errCode']) ? $response['Txn']['Resp']['Vehicle']['@attributes']['errCode'] : null;

    $vehicleInfo = [];
    if (isset($response['Txn']['Resp']['Vehicle']['VehicleDetails']['Detail'])) {
        $vehicleDetails = $response['Txn']['Resp']['Vehicle']['VehicleDetails']['Detail'];
        foreach ($vehicleDetails as $detail) {
            $name = isset($detail['@attributes']['name']) ? $detail['@attributes']['name'] : null;
            $value = isset($detail['@attributes']['value']) ? $detail['@attributes']['value'] : null;
            if ($name !== null && $value !== null) {
                $vehicleInfo[$name] = $value;
            }
        }
    }
	//print_r($vehicleInfo);die;
	$result =[];

	/*if (isset($vehicleInfo['TAGSTATUS'])) {
		$result['tag'] = $vehicleInfo['TAGSTATUS'] == "A" ? 'active' : 'inactive';
	}*/

	if (isset($vehicleInfo['TAGID'])) {
		$result['tagid'] = $vehicleInfo['TAGID'];
	}
//print_r($vehicleInfo); die;
	if (isset($vehicleInfo['EXCCODE'])) {
		$result['status'] = getExceptionCode($vehicleInfo['EXCCODE']);
	}
	
	//print_r($result); die;

	if (isset($vehicleInfo['VEHICLECLASS'])) {
		$vc = str_replace('VC', '' , $vehicleInfo['VEHICLECLASS']);
		//print_r($vehicleCode); die;
		//echo $vehicleType["$vc"];die;
		$result['vehtype'] = getVehicleType($vc);
	}

	if (isset($vehicleInfo['REGNUMBER'])) {
		$result['vehno'] = $vehicleInfo['REGNUMBER'];
	}

	if (isset($vehicleInfo['TID'])) {
		$result['tid'] = $vehicleInfo['TID'];
	}
	
	if (isset($vehicleInfo['VEHICLECLASS'])) {
		$result['vehicleclass'] = $vehicleInfo['VEHICLECLASS'];
	}

	if (isset($respCode)) {
		$result['respcode'] = $respCode;
	}

	if (isset($msgId)) {
		$result['msgid'] = $msgId;
	}
	
		if (isset($resTag)) {
		$result['result'] = $resTag;
	}
	return $result;
  //  return array_merge(["msgId"=> $msgId, "orgId"=> $orgId, "txnId"=> $txnId, "respCode"=> $respCode, "result"=> $result, "errCode"=> $errCode, "httpCode" => $httpCode], $vehicleInfo);
}

function getHeartBeat() {
    $randomNumber = getRandomNumber();
    $ts = date('Y-m-d\TH:i:s');
    $payload = '<etc:TollplazaHbeatReq xmlns:etc="http://npci.org/etc/schema/"><Head msgId="HB712305'.$randomNumber.'" orgId="POAA" ts="'.$ts.'" ver="1.0" />    <Txn id="HB712305'.$randomNumber.'" note="'.$ts.'" refId="" refUrl="" ts="'.$ts.'" type="Hbt" orgTxnId="">    <Meta>        <Meta1 name="" value="" />        <Meta2 name="" value="" />    </Meta>    <HbtMsg type="ALIVE" acquirerId="712305" />    <Plaza geoCode="31.7096004,74.7973022" id="712305" name="AmritsarAirport" subtype="OPEN" type="PARKING" address="" fromDistrict="Amritsar" toDistrict="Amritsar" agencyCode="POAAC">        <Lane id="1" direction="N" readerId="1" Status="OPEN" Mode="Normal" laneType="ETC" />    </Plaza>    </Txn>    </etc:TollplazaHbeatReq>';
    $url = "https://netc-acq.airtelbank.com:7443/etc/TollplazaHbeatReq/10";
    $filePath = getSignedUrl($payload);
	$signedxml = file_get_contents($filePath);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $signedxml);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch); 
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["statusCode" => $httpCode, "msgId" => "HB712305$randomNumber"];
 }

function makeTranscation($tagInfo, $tariffAmount) {
    $randomNumber = getRandomNumber();
    $ts = date('Y-m-d\TH:i:s');
	$txnts =date('dmyHis');
    $payload = '<etc:ReqPay xmlns:etc="http://npci.org/etc/schema/"><Head msgId="RPA712305L01'.$randomNumber.'" orgId="POAA" ts="'.$ts.'" ver="1.0" /><Meta /><Txn id="712305L01'.$txnts.'0" note="" orgTxnId="" refId="" refUrl="" ts="'.$ts.'" type="DEBIT"><EntryTxn id="712305L01'.$txnts.'0" ts="'.$ts.'" tsRead="'.$tagInfo['entryts'].'" type="DEBIT" /></Txn><Plaza geoCode="31.7096004,74.7973022" id="712305" name="AmritsarAirport" subtype="OPEN" type="PARKING"><EntryPlaza geoCode="31.7096004,74.7973022" id="712305" name="AmritsarAirport" subtype="OPEN" type="PARKING" /><Lane direction="N" id="1" readerId="1" Status="Open" Mode="Normal" laneType="Hybrid" ExitGate="1" Floor="1" /><EntryLane direction="N" id="1" readerId="1" Status="Open" Mode="Normal" laneType="Hybrid" EntryGate="1" Floor="1" /><ReaderVerificationResult publicKeyCVV="" procRestrictionResult="ok" signAuth="VALID" tagVerified="NETC TAG" ts="'.$ts.'" txnCounter="0" txnStatus="SUCCESS" vehicleAuth="UNKNOWN"><TagUserMemory><Detail name="TagSignature" value="'.$tagInfo['signvalue'].'" /><Detail name="TagVRN" value="XXXXXXXXXXXX" /><Detail name="TagVC" value="4" /></TagUserMemory></ReaderVerificationResult></Plaza><Vehicle TID="'.$tagInfo['tid'].'" staticweight="" tagId="'.$tagInfo['tagId'].'" wim=""><VehicleDetails><Detail name="AVC" value="'.$tagInfo['avcvalue'].'" /><Detail name="LPNumber" value="XXXXXXXXXXXX" /></VehicleDetails></Vehicle><Payment><Amount IsOverWeightCharged="FALSE" PaymentMode="Tag" PriceMode="CUSTOM" curr="INR" value="'.$tariffAmount.'"><OverwightAmount PaymentMode="Tag" curr="INR" value="0.0" /></Amount></Payment></etc:ReqPay>';
    $url = "https://netc-acq.airtelbank.com:7443/etc/ReqPay/10";
    $filePath = getSignedUrl($payload);
	$signedxml = file_get_contents($filePath);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $signedxml);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch); 
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["statusCode" => $httpCode, "msgId" => "RPA712305L01$randomNumber", "txnId" => "712305L01".$txnts."0"];
}

function syncTime() {
    $randomNumber = getRandomNumber();
    $ts = date('Y-m-d\TH:i:s');
    $payload = '<etc:ReqSyncTime xmlns:etc="http://npci.org/etc/schema/"><Head ver="1.0" ts="'.$ts.'" orgId="POAA" msgId="ST712305'.$randomNumber.'"/></etc:ReqSyncTime>';
    $url = "https://netc-acq.airtelbank.com:7443/etc/ReqSyncTime/10";
    $filePath = getSignedUrl($payload);
	$signedxml = file_get_contents($filePath);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $signedxml);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch); 
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    file_put_contents("./synctimeapi/response_ST712305_$randomNumber.xml", $result);
    $response = simplexml_load_string(trim($result));
    $response = json_decode(json_encode($response),true);
    //print_r($response); die;
    $msgId = isset($response['Head']['@attributes']['msgId']) ? $response['Head']['@attributes']['msgId'] : null;
    $orgId = isset($response['Head']['@attributes']['orgId']) ? $response['Head']['@attributes']['orgId'] : null;
    $respCode = isset($response['Resp']['@attributes']['respCode']) ? $response['Resp']['@attributes']['respCode'] : null;
    $result = isset($response['Resp']['@attributes']['result']) ? $response['Resp']['@attributes']['result'] : null;
    $errCode = isset($response['Resp']['Vehicle']['@attributes']['errCode']) ? $response['Resp']['Vehicle']['@attributes']['errCode'] : null;
    return ["statusCode" => $httpCode, "msgId"=> $msgId, "orgId"=> $orgId, "respCode"=> $respCode, "result"=> $result, "errCode"=> $errCode];
}

function checktranscation($txnid, $txndate) {
    $randomNumber = getRandomNumber();
    $ts = date('Y-m-d\TH:i:s');
    $payload = '<etc:ReqChkTxn xmlns:etc="http://npci.org/etc/schema/"><Head msgId="CT712305'.$randomNumber.'" orgId="POAA" ts="'.$ts.'" ver="1.0" /><Txn id="CT712305'.$randomNumber.'" note="" refId="" refUrl="" ts="'.$ts.'" type="ChkTxn" orgTxnId=""><TxnStatusReqList><Status txnId="'.$txnid.'" txnDate="'.$txndate.'" plazaId="712305" laneId="1" /></TxnStatusReqList></Txn></etc:ReqChkTxn>';
	$url = "https://netc-acq.airtelbank.com:7443/etc/ReqChkTxn/10";
    $filePath = getSignedUrl($payload);
	$signedxml = file_get_contents($filePath);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $signedxml);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch); 
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    file_put_contents("./checktxnapi/response_CT712305'.$randomNumber.xml", $result);
    $response = simplexml_load_string(trim($result));
    $response = json_decode(json_encode($response),true);
    //print_r($response); die;
   $msgId = isset($response['Head']['@attributes']['msgId']) ? $response['Head']['@attributes']['msgId'] : null;
    $orgId = isset($response['Head']['@attributes']['orgId']) ? $response['Head']['@attributes']['orgId'] : null;
    $txnId = isset($response['Txn']['@attributes']['id']) ? $response['Txn']['@attributes']['id'] : null;
    $apirespcode = isset($response['Txn']['Resp']['@attributes']['respCode']) ? $response['Txn']['Resp']['@attributes']['respCode'] : null;
    $apiresult = isset($response['Txn']['Resp']['@attributes']['result']) ? $response['Txn']['Resp']['@attributes']['result'] : null;

    $txnerrcode = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['errCode']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['errCode'] : null;
    $txnresult = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['result']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['result'] : null;
    $txnreqpayid = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['txnId']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['txnId'] : null;
    $txndate = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['txnDate']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['txnDate'] : null;
    $txnsettledate = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['settleDate']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['@attributes']['settleDate'] : null;

    $txnfaretype = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['FareType']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['FareType'] : null;
    $txnregnumber = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['RegNumber']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['RegNumber'] : null;
    $txntollfare = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['TollFare']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['TollFare'] : null;
    $txnvehicleclass = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['VehicleClass']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['VehicleClass'] : null;
    $txnlisterrcode = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['errCode']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['errCode'] : null;
    $txnreadtime = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnReaderTime']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnReaderTime'] : null;
    $txnrectime = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnReceivedTime']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnReceivedTime'] : null;
    $tnnstatus = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnStatus']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnStatus'] : null;
    $txntype = isset($response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnType']) ? $response['Txn']['Resp']['TxnStatusReqList']['Status']['TxnList']['@attributes']['txnType'] : null;
    return ["statusCode" => $httpCode, "msgId"=> $msgId, "orgId"=> $orgId, "txnId"=> $txnId, "apirespcode"=> $apirespcode, "apiresult"=> $apiresult, "txnerrcode"=> $txnerrcode, "txnresult"=> $txnresult, "txnreqpayid"=> $txnreqpayid, "txndate"=> $txndate, "txnsettledate"=> $txnsettledate, "txnfaretype"=> $txnfaretype, "txnregnumber"=> $txnregnumber, "txntollfare"=> $txntollfare, "txnvehicleclass"=> $txnvehicleclass, "txnlisterrcode"=> $txnlisterrcode, "txnreadtime"=> $txnreadtime, "txnrectime"=> $txnrectime, "txntype"=> $txntype, "tnnstatus"=> $tnnstatus];
}

?>
