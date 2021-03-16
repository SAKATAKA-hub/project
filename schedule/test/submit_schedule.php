<?php
#===========================================================
# submit_schedule.php スケジュール提出プログラム
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > submit_schedule_function.php > submit_schedule.php(現在地)
include('submit_schedule_function.php');

//parts.phpファイルの読み込み
include('../common/parts/parts.php'); 


#===========================================================
# 1.フォームの受取りとモード切替

$in = parse_form(); //フォームの受取り

// case1. フォームを受け取ったとき
if(isset($in["mode"]))
{
    validateToken(); //tokenのチェック

    //取得内容の確認表示*************************************
    echo "****** in *******"."<br>";
    foreach ($in as $key => $value) {
        if(!empty($value)){echo $key." => ".$value."<br>";}  
    }
    //***************************************************** 


    //case1-1. 入力スケジュールの提出
    if($in["mode"]=="submit")
    {

        submit(); //提出モード関数
        input_reset();

    } 
    //case1-2. "選択月"の変更
    elseif(($in["mode"]=="change_next")||($in["mode"]=="change_befor"))
    {

        change(); //"月"変更モード関数
        read_save_shift(); //DBの保存データを反映する関数

    }
    //case1-3. 入力内容のリセット
    elseif($in["mode"]=="input_reset")
    {

        input_reset();// 入力内容のリセット関数

    }
    //case1-4. 契約曜日通りの入力
    elseif($in["mode"]=="fixed_input")
    {

        input_reset();
        fixed_input();

    }
    

}
// case2. ページを開いたとき
else
{
    normal_processing(); //デフォルトモード関数
    read_save_shift(); //提出済スケジュールを反映する関数
}

createToken(); //tokenの発行 $_SESSION['token']


#===========================================================
# 2.表示要素の部品作成
//2-1 入力テーブル列(tr要素)の作成
//テーブル表示日付の"開始日"と"終了日"
$trdate = [];
switch ($display["half"]) {
    case 'first':
        $trdate["start"] = 1;
        $trdate["end"] = 15;
        $week_num = $display["week"];
        break;
    case 'second':
        $trdate["start"] = 16;
        $trdate["end"] = $display["end_d"];
        $week_num = $display["week"];
        break;
}
$weeks = array("日","月","火","水","木","金","土",);
$tr_element ="";


// 一日毎にtr要素の作成
for ($i = $trdate["start"]; $i <= $trdate["end"]; $i++) 
{
    $num = sprintf("%02d",$i); //日付

    $select_elements = [];

    // オプション要素の作成
    $select_e_names = array("in1", "out1", "in2","out2",); //$inのkeyとして利用する文字列の配列
    foreach ($select_e_names as $select_e_name) 
    {
        $option_element = create_o_element(); //オプション要素作成関数

        $select_elements["$select_e_name"] =
        sprintf(
            '<select name="%s-%s">%s</select>',
            $select_e_name, $num ,$option_element
        );    
    }



    $replace_array = array(
        '!num!' => $num , 
        '!week!' => $weeks[$week_num] ,
        '!select_elements["in1"]!' => $select_elements["in1"] ,
        '!select_elements["out1"]!' => $select_elements["out1"] ,
        '!select_elements["in2"]!' => $select_elements["in2"] ,
        '!select_elements["out2"]!' => $select_elements["out2"] ,
    );

    // テーブル部品のテンプレート読み込み
    $file = "tmpl/table_parts.tmpl";
    $text = file_get_contents($file);
    
    //テンプレートの文字差替え
    $replace_text = $text;    
    foreach ($replace_array as $key => $val) {
        $replace_text = str_replace($key,$val,$replace_text);
    }

    // チェックボックスの入力処理
    $in_key = "paid_holiday".$num;
    if(empty($in[$in_key])){
        $replace_text = str_replace("!checked!","",$replace_text);
    }else{
        $replace_text = str_replace("!checked!","checked",$replace_text);
    }

    $tr_element .= $replace_text;

    //曜日挿入の処理
    $week_num = $week_num == 6 ? 0 : $week_num+1; 
}

//2-2 option要素作成関数
function create_o_element()
{
    global $in, $select_e_name, $num;
    $o_erements = array('<option value=""> </option>'); //オプション要素
    $work_min = array(":00",":30",);

    //　"8時スタート"、"30分刻み"の選択option 
    for ($h=0; $h < 24; $h++) { 
        $hour = $h+8>23 ? $h+8-24 :$h+8;
        foreach ($work_min as $min) 
        {
            $time = sprintf("%02d",$hour).$min;
            $in_key = $select_e_name."-".$num;
            $selected_text = sprintf('<option selected value="%s">%s</option>',$time,$time);

            if((isset($in[$in_key])) && ($in[$in_key] == $time)){
                $o_erements[] = $selected_text;
            }else{
                $o_erements[] = str_replace("selected"," ",$selected_text);
            }
        }
    }
    $o_erements = implode("\n",$o_erements);
    return $o_erements;
}

// $o = create_o_element();
// echo "<select>$o</select>";


#===========================================================
# 3.ページの表示
// テンプレートファイルの読み込み
$file = "tmpl/submit_schedule.tmpl";
$text = file_get_contents($file);

//差替え文字の設定
switch ($display["half"]) {
    case 'first':
        $display_month = sprintf("%04d年%02d月 前期",$display["Y"],$display["m"]);
        $value_month = sprintf("%04d-%02d-01",$display["Y"],$display["m"]);
        break;
    
    case 'second':
        $display_month = sprintf("%04d年%02d月 後期",$display["Y"],$display["m"]);
        $value_month = sprintf("%04d-%02d-16",$display["Y"],$display["m"]);
        break;
}

$comment = empty($in["comment"]) ? "" : $in["comment"] ; 

//差替え文字の配列

$replace_array = array(
    "!header!" => $header ,
    "!employee_name!" => $_SESSION['employee_name'] ,
    "!token!" => $_SESSION['token'] ,
    "!display_month!" => $display_month ,
    "!value_month!" => $value_month ,
    "!comment!" => $comment ,
    

    //要素
    "!tr_element!" => $tr_element ,
);

//テンプレートの文字差替え
$replace_text = $text;    
foreach ($replace_array as $key => $val) {
    $replace_text = str_replace($key,$val,$replace_text);
}
echo $replace_text;

#スタイル編集用ファイルの作成
file_put_contents("submit_schedule.html",$replace_text)

?>






        
            

            
