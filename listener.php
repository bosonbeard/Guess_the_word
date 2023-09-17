<?php

const PAGE_ENCODING     ='UTF-8';

// Heaers
header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);


// Get parameters from SMS
//$method = $_SERVER['REQUEST_METHOD'];
$phone = $data["sender"];
$receiver = $data["receiver"];
$text = trim(mb_strtolower($data["text"]));
$direction =  strtolower($data["direction"]);


if ($direction == strtolower("DIRECTION_INCOMING")) {


// get paams from config
$file = "game-config.json";
$json = json_decode(file_get_contents($file), true);
$sms_api_key = $json['config']['sms_api_key'];
$sms_api_url=$json['config']['sms_api_url'];
$url=$json['config']['base_url'];

//we get phone parameter for call api here


$dt = date('c', time()); // get currtent time and date

// write request in log
$fw = fopen("listener_log.txt", "a+");
fwrite($fw, $phone." ".$text." ".$dt."\r\n");
fclose($fw);


if ($phone){


    if ($text == "" or $text == "help" ){
        $response = 
        "Игра угадай слово отправьте: \n".
        "start - новая игра \n".
        "info - текущий статус игры \n".
        "help или пусто- это сообщение \n".
        "любое другое слово - угадываемое слово \n";
    $res= send_SMS($phone, $receiver, $response, $sms_api_key, $sms_api_url );


    }
    elseif ($text  == "start" ){

        // TODO: ПРОДОЛЖИТЬ ОТСЮДА
       $res =  call_API($url, "POST", $phone, $text );
       $res= send_SMS($phone, $receiver, $response, $sms_api_key, $sms_api_url );
       $response = $result;

    }
    elseif ($text  == "info" ){
        $res =  call_API($url, "GET", $phone, $text );
        $res= send_SMS($phone, $receiver, $response, $sms_api_key, $sms_api_url );

    }
    else {

        $res =  call_API($url, "PUT", $phone, $text );
        $response = $result;
        $res= send_SMS($phone, $receiver, $response, $sms_api_key, $sms_api_url );
    }


#curl request



echo $response;


}


//$response = "ok";
//return response
//echo json_encode($data);

}
else{
    echo "error not incoming message";
}


function call_API($api_url, $method, $user_phone, $user_word )

{

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
         'Content-Type: application/json',
     ));

     switch($method){
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            $url = $api_url."?phone=".$user_phone."&word=".$user_word;
            curl_setopt($curl, CURLOPT_URL, $url);
            break;
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            $url = $api_url."?phone=".$user_phone;
            curl_setopt($curl, CURLOPT_URL, $url );
            break;
        case "GET":
            $url = $api_url."?phone=".$user_phone;
            curl_setopt($curl, CURLOPT_URL, $url);
            break;
    
        default:
                $response   = '{"error":"unknown method"}';
    
            break;
     }

   
      $result = curl_exec($curl);


     if (!$response){
  
        $response = $result;
     }
   

     curl_close($curl);

     return $response;

}


function send_SMS($phone, $receiver, $text, $sms_api_key, $sms_api_url )
{
   
    $data = array("destination" => $phone, "number" => $receiver, "text" => $text );
    $data_json = json_encode($data);
   
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
         'Content-Type: application/json', 
         'Authorization: Bearer '.$sms_api_key
     ));
     curl_setopt($curl, CURLOPT_POST, 1);
     curl_setopt($curl, CURLOPT_URL, $sms_api_url);
     curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);

    // $result = curl_exec($curl);
     curl_close($curl);
     return $result;
}