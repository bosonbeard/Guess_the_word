<?php

const PAGE_ENCODING     ='UTF-8';

// Heaers
header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);


// Get parameters
$method = $_SERVER['REQUEST_METHOD'];
$phone = $data["receiver"];
$text = mb_strtolower($data["text"]);

$dt = date('c', time()); // get currtent time and date

// write request in log
$fw = fopen("listener_log.txt", "a+");
fwrite($fw, $phone." ".$text." ".$dt."\r\n");
fclose($fw);


$curl = curl_init();

curl_setopt_array($curl, array(

CURLOPT_URL => 'https://api.mtt.ru/ms-customer-gateway/v1/GetMessagesHistoryList',

CURLOPT_RETURNTRANSFER => true,

CURLOPT_ENCODING => '',

CURLOPT_MAXREDIRS => 10,

CURLOPT_TIMEOUT => 0,

CURLOPT_FOLLOWLOCATION => true,

CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

CURLOPT_CUSTOMREQUEST => 'POST',
CURLOPT_POSTFIELDS =>'{

    "customer_name": "'.$customer_name.'",

    "number":"'.$number.'",

    "event_date_gt":"'.$date_gt.'",

    "event_date_lt":"'.$date_lt.'",

    "direction":"incoming",

    "delivery_status":"delivered"

}'));




if (trim($text) == "" or trim($text) == "help" ){
    $response = 
    "Игра угадай слово отправьте: \n".
    "start - новая игра \n".
    "info - текущий статус игры \n".
    "help или пусто- это сообщение \n".
    "любое другое слово - угадываемое слово \n";
}

if (trim($text) == "" or trim($text) == "help" ){
    $response = 
    "Игра угадай слово отправьте: \n".
    "start - новая игра \n".
    "info - текущий статус игры \n".
    "help или пусто- это сообщение \n".
    "любое другое слово - угадываемое слово \n";
}
   

#curl request




$response = curl_exec($curl);

curl_close($curl);

//$response = "ok";
//return response
//echo json_encode($data);
echo $response;

?>