<?php

const PAGE_ENCODING     ='UTF-8';

// Heaers
header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);




// Get parameters from SMS
$method = $_SERVER['REQUEST_METHOD'];
$phone = $data["sender"];
$receiver = $data["receiver"];
$text = mb_strtolower($data["text"]);
$direction =  mb_strtolower($data["direction"]);

if ($direction =="DIRECTION_INCOMING") {


// get paams from config
$file = "game-config.json";
$json = json_decode(file_get_contents($file), true);
$base_url= $json['config']['base_url'];
$sms_api_key = $json['config']['sms_api_key'];
$sms_api_url=$json['config']['sms_api_url'];


$url="$base_url?phone=$phone"; 


$dt = date('c', time()); // get currtent time and date

// write request in log
$fw = fopen("listener_log.txt", "a+");
fwrite($fw, $phone." ".$text." ".$dt."\r\n");
fclose($fw);


$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
     'Content-Type: application/json',
 ));



if ($phone){


    if (trim($text) == "" or trim($text) == "help" ){
        $response = 
        "Игра угадай слово отправьте: \n".
        "start - новая игра \n".
        "info - текущий статус игры \n".
        "help или пусто- это сообщение \n".
        "любое другое слово - угадываемое слово \n";
    }
    elseif (trim($text)  == "start" ){
    
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $url);

        $result = curl_exec($curl);

    
        $response = $result;
        // TODO: ПРОДОЛЖИТЬ ОТСЮДА
       $res= send_SMS($phone, $receiver, $response, $sms_api_key, $sms_api_url );
       echo $res;

    }
    elseif (trim($text)  == "info" ){
        curl_setopt($curl, CURLOPT_URL, $url);

        $result = curl_exec($curl);
    
        $response = $result;
    }
    else {

        curl_setopt($curl, CURLOPT_PUT, 1);
        curl_setopt($curl, CURLOPT_URL, $url."&word=".$text);
        $result = curl_exec($curl);
        $response = $result;
    }
   

#curl request






}
curl_close($curl);



//$response = "ok";
//return response
//echo json_encode($data);
echo $response;
}
else{
    echo "error not incoming message";
}

function send_SMS($phone, $receiver, $text, $sms_api_key, $sms_api_url )
{
   
    $data = array("destination" => $phone, "number" => $receiver, "text" => $text );
    $data_json = json_encode($data);
   
   
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
         'Content-Type: application/json',
         'Authorization: Bearer '.$sms_api_key
     ));
     curl_setopt($curl, CURLOPT_POST, 1);
     curl_setopt($curl, CURLOPT_URL, $sms_api_url);
     curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);

     $result = curl_exec($curl);
     curl_close($curl);
     return $result;


}