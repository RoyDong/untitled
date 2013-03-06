<?php

function post($url, $data){
    $headers = [
        'Host: www.zdic.net',
        'Origin: http://www.zdic.net',
        'Referer: http://www.zdic.net/cd/',
        'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22 Roy'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function wipeOffHtml($html){
    $html = preg_replace('/<script.*?<\/script>/i', '', $html);
    $html = preg_replace('/<.*?>/', '', $html);
    $html = preg_replace('/&.*?;/', '', $html);

    return trim($html, "　 \r\n");
}

$url = 'http://www.zdic.net/sousuo/';
$params = ['lb_a' => 'hp', 'lb_b' => 'mh', 'lb_c' => 'mh', 'tp' => 'tp1'];
$fpZi = fopen('zi.txt', 'r');
$fpSqlZi = fopen('zi.sql', 'w');
$fpSqlRecord = fopen('record.sql', 'w');
$fpNoword = fopen('noword.txt', 'w');
$fpNoRecord = fopen('no_record.txt', 'w');

$zid = 0;

while (!feof($fpZi)){
    $char = trim(fgets($fpZi));

    $params['q'] = $char;
    $html = post($url, $params);

    preg_match('/<div id=\"j?bs\">.*?<\/div>/i', $html, $m);

    if(isset($m[0])){
        $row = explode(',', wipeOffHtml($m[0]));
        $radical = $row[0];

        preg_match('/\d+/', $row[1], $radicalStroke);

        preg_match('/\d+/', $row[2], $stroke);

        $radicalStroke = $stroke[0] - $radicalStroke[0];
        $stroke = $stroke[0];

        $zid++;
        fwrite($fpSqlZi, "insert into zi (id,`char`,radical,radical_stroke,stroke)values($zid,'$char','$radical',$radicalStroke,$stroke);\n");
    }else{
        fwrite($fpNoword, $char."\n");
    }

    preg_match_all('/<p class="zdct\d+">.*?<\/?p>/i', $html, $m);

    //print_r($m[0]);
    if(isset($m[0])){
        $hasRecord = false;

        foreach($m[0] as $row){
            if(preg_match('/<p class="zdct\d+"><strong>([^（]*?)<\/strong><\/p>/i', $row, $title)){
                if(isset($title[1]) && !in_array($title[1], ['基本字义', '其它字义', '基本字義', '其它字義'])){
                    break;
                }
            }

            preg_match('/<span class="dicpy">(.*?) /i', $row, $mpy1);
            preg_match('/spf\("(.*?)"\)/', $row, $mpy2);

            if(isset($mpy1[1]) && isset($mpy2[1])){
                $pinyin = $mpy2[1];
                
                continue;;
            }

            $row = wipeOffHtml($row);
            
            if(strlen($row) > 0){
                if(preg_match('/^(\d+|◎)/', $row)){
                    $row = preg_replace('/^(\d+|◎)/', '', $row);
                    $row = addslashes(trim($row, ' .'));
                    fwrite($fpSqlRecord, "insert into zi_record (zid, pinyin, zh, en)values($zid,'$pinyin','$row','');\n");
                    $hasRecord = true;
                }
            }
        }

        if(!$hasRecord){
            fwrite($fpNoRecord, $char."\n");
        }
    }else{
        fwrite($fpNoRecord, $char."\n");
    }

}

fclose($fpZi);
fclose($fpNoRecord);
fclose($fpNoword);
fclose($fpSqlZi);
fclose($fpSqlRecord);