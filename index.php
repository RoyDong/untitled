<?php


$fp = fopen('zi.txt', 'r');
$fp1 = fopen('word.txt', 'w');

$words = [];

while(!feof($fp)){
    $row = trim(fgets($fp));

    $words = array_merge($words, explode(' ', $row));
}

foreach($words as $word){
    fwrite($fp1, $word."\n");
}


fclose($fp);
fclose($fp1);
