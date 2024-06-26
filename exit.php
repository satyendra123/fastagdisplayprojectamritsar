#ye mera exit.php ka code hai jo mere ko car number aur price deta hai
<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Kolkata');

require "./apiCallingInfo.php";

$response = [
    'code' => 0,
    'msg' => "inavlid paramters",
];

$tarrifRate = [
    'two' => [
        '30' => 10,
        '120' => 15,
        '180' => 20,
        '240' => 25,
        '300' => 30,
        '360' => 35,
        '420' => 40,
        '1440' => 45,
        '2880' => 90,
    ],
    'car' => [
        '30' => 20,
        '120' => 55,
        '180' => 65,
        '240' => 75,
        '300' => 85,
        '360' => 95,
        '420' => 105,
        '1440' => 165,
        '2880' => 330,
    ],
    'tempo' => [
        '30' => 20,
        '120' => 60,
        '180' => 70,
        '240' => 80,
        '300' => 90,
        '360' => 100,
        '420' => 110,
        '1440' => 180,
        '2880' => 330,
    ],
    'truck' => [
        '30' => 30, 
        '120' => 70,
        '180' => 80,
        '240' => 90,
        '300' => 100,
        '360' => 110,
        '420' => 120,
        '1440' => 210,
        '2880' => 420,
    ]
];

$vehicleCategory = [
    "two wheeler" => "two",
    "three wheeler passenger" => "car",
    "three wheeler freight" => "car",
    "car/jeep/van" => "car",
    "light commercial vehicle 2-axle" => "tempo",
    "light commercial vehicle 2-axle" => "tempo",
    "bus 2-axle" => "tempo",
    "bus 3-axle" => "tempo",
    "mini bus" => "tempo",
    "truck 2-axle" => "truck",
    "truck 3-axle" => "truck",
    "truck 4-axle" => "truck",
    "truck 5-axle" => "truck",
    "truck 6-axle" => "truck",
    "truck multi-axle" => "truck",
    "earth moving machinery" => "truck",
    "heavy construction machinery" => "truck",
    "tractor" => "truck",
    "tractor with trailer" => "truck",
    "tata ace & mini light commercial vehicle" => "tempo"
];

function exitdisplay($data) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://127.0.0.1:8000/exitreceivedisplay',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
	$data = json_decode(file_get_contents('php://input'), true);
	if (isset($data['tagid']) && isset($data['token']) && isset($data['entryts']) && isset($data['signvalue']) && isset($data['tid']) && isset($data['vehtype']) && isset($data['tarrifvehtype']) && isset($data['avcvalue'])) {
		$token = $data['token'];
        if ($token == "SE9VU1lTLVBBUksyMDIzUFRN") {
            $entryTime = date('Y-m-d H:i:s', strtotime($data['entryts']));
            $exitTime = date('Y-m-d H:i:s');
			
            //$tagdata = getTagInfo("34161FA820328BD02043C6E0");
			//print_r($tagdata); exit;
            //$response['tagInfo'] = $tagdata;
            //$response['transcationResult'] = null;
            //if (isset($tagdata['status'])) {
            $minutesDifference = round((strtotime($exitTime) - strtotime($entryTime)) / 60);
            //$vehicleCategory = $vehicleCategory[$data['vehtype']];
			$vehicleCategory = $data['tarrifvehtype'];
            if ($vehicleCategory != '') {
                    $tariffAmount = 0;
					//echo $minutesDifference;die;
                    if ($minutesDifference <= 30) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['30'];
                    } else if ($minutesDifference > 30 && $minutesDifference <= 120) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['120'];
                    } else if ($minutesDifference > 120 && $minutesDifference <= 180) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['180'];
                    } else if ($minutesDifference > 180 && $minutesDifference <= 240) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['240'];
                    } else if ($minutesDifference > 240 && $minutesDifference <= 300) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['300'];
                    } else if ($minutesDifference > 300 && $minutesDifference <= 360) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['360'];
                    } else if ($minutesDifference > 360 && $minutesDifference <= 420) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['420'];
                    } else if ($minutesDifference > 420 && $minutesDifference <= 1440) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['1440'];
                    } else if ($minutesDifference > 1440 && $minutesDifference <= 2880) {
                        $tariffAmount = $tarrifRate[$vehicleCategory]['2880'];
                    } else {
						$daysCount = round($minutesDifference/1440);
						$tariffAmount = $daysCount * $tarrifRate[$vehicleCategory]['1440'];
					}
                    $response['code'] = "1";
                    $response['msg'] = "SUCCESS";
                    $tagDetails = [
                        'tagId' => $data['tagid'],
                        'tid' => $data['tid'],
						'signvalue' => $data['signvalue'],
						'avcvalue' => $data['avcvalue'],
						'entryts' => date('Y-m-d\TH:i:s', strtotime($data['entryts']))
                    ];
					$tagInfoResp = getTagInfo($data['tagid']);
					if (!empty($tagInfoResp)) {
                    $transcationResp = makeTranscation($tagDetails, $tariffAmount);
					$response = [
					//	"status"=> $tagdata['status'],
						"tagid"=> $data['tagid'],
						"tid"=> $data['tid'],
						"vehtype"=> $data['vehtype'],
						"msgid"=>$transcationResp['msgId'],
						"txnid"=>$transcationResp['txnId'],
						"car_number"=>$tagInfoResp['vehno'],
						"price"=>$tariffAmount,
						"respcode"=>$transcationResp['statusCode'],
						"entryts"=>$entryTime,
						"exitts"=>$exitTime
			];
                    exitdisplay($response);   			
			}
                } else {
                    $response['msg'] = "Vehicle category not categorized";
                }
          /* } else {
                $response['msg'] = "Some issue while fetching tag details";
            }*/
        } else {
            $response['msg'] = "not authorized";
        }
    }
}

echo json_encode($response);

exit;

?>
