<?php

// Heaers
header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: text/plain");

// Get parameters
$method = $_SERVER['REQUEST_METHOD'];
$phone = $_REQUEST["phone"];
$current_word = $_REQUEST["word"];





//$dt = date('c', time()); // get currtent time and date

// write request in log
$fw = fopen("log.txt", "a+");
fwrite($fw, $phone." ".$command." ".$dt."\r\n");
fclose($fw);

//connect to DB
$db = new SQLite3('db.sqlite');

// read config
$file = "game-config.json";

$json = json_decode(file_get_contents($file), true);
$hidden_word = $json['phones-words'][$phone];
echo "json = ".$hidden_word."--";

if (!$hidden_word) {
    $numRows = $db->querySingle("SELECT  COUNT(*) as count FROM words");
    echo "$numRows=".$numRows;
    $hidden_word = $db->querySingle("SELECT *  FROM words LIMIT 1 OFFSET ".(rand(0,  $numRows)));
    
}

echo "-result hw=".$hidden_word;


switch ($method) {
 // GET method   
    case "GET":

        // get last key command
        $sql = "SELECT `hidden_word`,`current_word`,`in_word`,`attempts`   FROM games WHERE `phone` = '$phone' ";
        //$sql = "SELECT `hidden_word`,`current_word`,`in_word`   FROM games WHERE `phone` = '$phone' ORDER BY `timestamp` DESC";
        $result = $db->querySingle($sql, true);
        $have_attempts=mb_strlen($result['hidden_word']);
        echo $have_attempts;
        // set API response
        $response = 
        "Ваше слово: $result[current_word] \n".
        "осталось попыток: $have_attempts \n";

        break;
 // POST method   
    case "POST":
        $current_word = str_repeat("_", mb_strlen($hidden_word));
        echo $current_word;
        echo mb_strlen($hidden_word);

        // add new not processed command in DB
        $sql = " UPDATE  games  
        SET `hidden_word`='$hidden_word', `current_word`='$current_word',
        `in_word`='', `attempts`=0 WHERE `phone` = $phone;
        ";      
        $result = $db->querySingle($sql);
        echo $result;
        $sql = " INSERT INTO games (`phone`, `hidden_word`, `current_word`,`in_word`)  
        VALUES('$phone','$hidden_word', '$current_word', '')";
        $result = $db->querySingle($sql);
        echo $result;

        // set API response
        $response = $result;
        break;
    case "PUT":
        // add new not processed command in DB
        $sql = "INSERT INTO commands (`key`,`phone`, `timestamp`, `is_readed`) VALUES('$command','$phone','$dt',0)";
        $result = $db->querySingle($sql);
        // set API response
        $response = array('key' => $command, 'phone'=> $phone );
        break;


    default:
        echo '{"error":"unknown method"}';
        break;
}

//return response
echo $response;
