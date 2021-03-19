<?php
echo "test CSV";
$datas = array(
    array(
    "name"=>"シロちゃん",
    "like"=>"マグロ",
    "animal"=>"柴犬",
    ),
    array(
    "name"=>"トドちゃん",
    "like"=>"あんこ",
    "animal"=>"くま",
    ),
    array(
    "name"=>"プクちゃん",
    "like"=>"マスカット",
    "animal"=>"うさぎ",
    ),
);

# CSVファイルへの書き込み
$file = "sanbiki.csv";

mb_convert_variables("SJIS","UTF-8",$datas); //文字コードの変更
$fh = fopen($file,"w");
foreach ($datas as $key => $data) {
    fputcsv($fh,$data);
}
fclose($fh);

# CSVファイルの読み込み
// $fh = fopen($file,"r");
// $get_data=[];
// while ($line=fgetcsv($fh)) {
//     array_push($get_data,$line);
// }
// fclose($fh);
// $enc = mb_detect_encoding($get_data); //文字コードの取得
// $get_data = mb_convert_encoding($get_data, "UTF-8", $enc);
// var_dump($get_data);

// echo file_get_contents($file);
