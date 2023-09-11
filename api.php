<?php

const PAGE_ENCODING  ='UTF-8';



// Heaers
header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: text/plain");

// Get parameters
$method = $_SERVER['REQUEST_METHOD'];
$phone = $_REQUEST["phone"];
$user_word = mb_strtolower($_REQUEST["word"]);


$dt = date('c', time()); // get currtent time and date

// write request in log
$fw = fopen("api_log.txt", "a+");
fwrite($fw, $phone." ".$user_word." ".$method." ".$dt."\r\n");
fclose($fw);

//connect to DB
$db = new SQLite3('db.sqlite');

//echo "json = ".$hidden_word."--";


//echo "-result hw=".$hidden_word;


switch ($method) {
 // GET method   
    case "GET":

        // get last key command
        $sql = "SELECT `hidden_word`,`current_word`,`in_word`,`attempts`   FROM games WHERE `phone` = '$phone' ";
        //$sql = "SELECT `hidden_word`,`current_word`,`in_word`   FROM games WHERE `phone` = '$phone' ORDER BY `timestamp` DESC";
        $result = $db->querySingle($sql, true);
        $hidden_word = mb_strtolower($result['hidden_word']);
        $current_word = mb_strtolower($result['current_word']);
        $attempts=$result['attempts'];
        $have_attempts=mb_strlen($hidden_word)-$attempts;

         
        if ($result['in_word']){
            $in_word = $result['in_word']." — не на своём месте \n";
        }
        // set API response
        
        if ($result){
        
            if ($current_word != $hidden_word){
                $response = 
                "Ваше слово: $result[current_word] \n".
                "Осталось попыток: $have_attempts \n"
                .$in_word;
            }
            else{
                $response = 
                "Вы угадали! \n".
                "Загаданное слово: $hidden_word \n".
                "Еще оставалось попыток: $have_attempts \n";
            }
        }
        else{
            $response = "Начните новую игру";
        }

        break;
 // POST method   
    case "POST":
       
        // read config
        $file = "game-config.json";
        $json = json_decode(file_get_contents($file), true);
        $hidden_word = mb_strtolower($json['phones-words'][$phone]);
        if (!$hidden_word) {
            $numRows = $db->querySingle("SELECT  COUNT(*) as count FROM words");
        //    echo "$numRows=".$numRows;
            $hidden_word = mb_strtolower($db->querySingle("SELECT *  FROM words LIMIT 1 OFFSET ".(rand(0,  $numRows))));
            
        }
        // start new game

        $current_word = str_repeat("_", mb_strlen($hidden_word));
        $have_attempts=mb_strlen($hidden_word);


        // add new not processed command in DB
        $sql = " UPDATE  games  
        SET `hidden_word`='$hidden_word', `current_word`='$current_word',
        `in_word`='', `attempts`=0, `last_try_word` ='' WHERE `phone` = $phone;
        ";      
        $result = $db->querySingle($sql);

        $sql = " INSERT INTO games (`phone`, `hidden_word`, `current_word`,`in_word`, `last_try_word`)  
        VALUES('$phone','$hidden_word', '$current_word', '', '')";
        $result = $db->querySingle($sql);


        // set API response
        $response = 
        "Новая игра \n".
        "Слово: $current_word \n".
        "Букв: ".mb_strlen($current_word)." \n";
        "Осталось попыток: $have_attempts \n";
        break;

    case "PUT":
       
        $sql = "SELECT `hidden_word`,`current_word`, `last_try_word`, `in_word`, `attempts` FROM games WHERE `phone` = '$phone' ";
        //$sql = "SELECT `hidden_word`,`current_word`,`in_word`   FROM games WHERE `phone` = '$phone' ORDER BY `timestamp` DESC";
        $result = $db->querySingle($sql, true);
        $current_word = mb_strtolower($result['current_word']);
        $hidden_word = mb_strtolower($result['hidden_word']);
        $last_try_word = mb_strtolower($result['last_try_word']);

        $attempts=$result['attempts'];
        $have_attempts=mb_strlen($hidden_word)-($attempts+1);

        if (!$result)
        {   
            $response = "Начните новую игру";
        }
        else{
            if (  $have_attempts === 0)
            {
                $response = 
                "Ваше слово: $user_word \n".
                "Загаданное слово: $hidden_word \n".
                "Попытки закончились, начните новую игру \n";
                
            }
            else {
                if ($user_word == $hidden_word){
                  
                    if ($hidden_word!= $current_word) {
                        $attempts+=1;
                    }
                    $have_attempts=mb_strlen($hidden_word)-($attempts);
                                      
                    $sql = " UPDATE  games  
                    SET `hidden_word`='$hidden_word', `current_word`='$user_word',
                    `in_word`='', `attempts`= $attempts  WHERE `phone` = $phone";   
                    $result = $db->querySingle($sql);

                    $response = 
                    "Вы угадали!\n".
                    "Загаданное слово: $hidden_word \n".
                    "Еще оставалось попыток: $have_attempts \n";

                }
                elseif ($user_word == $last_try_word) {
                    $have_attempts+=1;
                    $response = 
                    "В прошлый раз вы уже водили\n".
                    "слово: $last_try_word \n".
                    "Еще осталось попыток: $have_attempts \n";
                }
                else{                    
                    if (mb_strlen($hidden_word ) != mb_strlen($user_word )){
                        $response = 
                        "Длина вашего слова не совпаладает с загаданным! \n".
                        "Повторите попытку.\n";
                        "Текущий прогресс: $current_word \n";
                    }
                    else{
                        $is_noun = $db->querySingle("SELECT  `word`  FROM words WHERE `word` = '$user_word' ");
                        if (!$is_noun){
                            $response = 
                            "Введенное слово не является существительным в именительном падеже: \n".
                            "Повторите попытку. \n";
                        }
                        else{
                          //  echo $hidden_word," ",$user_word," \n";
                        $check_result= check_correct_letters($hidden_word,$user_word,$current_word );
                        //var_dump($check_result);
                        $attempts+=1;
                        $sql = " UPDATE  games  
                        SET `hidden_word`='$hidden_word', `current_word`='$check_result->current_word',
                        `in_word`='$check_result->in_word', `attempts`= $attempts, `last_try_word`= '$user_word'
                         WHERE `phone` = $phone";   
                        $result = $db->querySingle($sql);
                     
                        if ($check_result->in_word){
                            $in_word = $check_result->in_word." — не на своём месте \n";
                        }
                            
                        $response = 
                        "Ваше слово: $check_result->current_word  \n".
                        "осталось попыток:". ($have_attempts)." \n"
                        .$in_word;
                

                    }
                }
            }

        }

    }

        break;


    default:
        echo '{"error":"unknown method"}';
        break;
}

//return response
echo $response;




function check_correct_letters($hidden_word,$user_word,$current_word )
{
    
    
    $hidden_word_arr = preg_split('//u', $hidden_word, 0, PREG_SPLIT_NO_EMPTY);
    $user_word_arr = preg_split('//u', $user_word, 0, PREG_SPLIT_NO_EMPTY);
    $current_word_arr =  preg_split('//u', $current_word, 0, PREG_SPLIT_NO_EMPTY);
    $in_word_arr = array();

    
    for ($i = 0; $i < count($user_word_arr); $i++){
       
 
        for ($j = 0; $j < count($hidden_word_arr); $j++){
            if ($hidden_word_arr[$j]==$user_word_arr[$i]){
                if ($j==$i){
                    if ($current_word_arr[$j]=="_"){
                        $current_word_arr[$i]=$user_word_arr[$i];
                    }

                }
                else{
                    array_push($in_word_arr, $user_word_arr[$i]);
                    
                }
            }

           // $lastPos = $lastPos + mb_strlen($user_word[$i]);
        }
        
    }
         $in_word_arr =  array_unique($in_word_arr, SORT_REGULAR);
         // Displays 3 and 10
    //     print_r($current_word_arr);
    //     print_r($in_word_arr);

    return    (object) [
        'current_word' => implode($current_word_arr),
        'in_word' => implode(",",$in_word_arr),
      ];
}
