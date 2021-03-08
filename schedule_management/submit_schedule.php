<?php
#===========================================================
# submit_schedule.php スケジュール提出プログラム
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > submit_schedule.php(現在地)
include('../common/app/function.php');

//parts.phpファイルの読み込み
include('../common/parts/parts.php'); 

# 日付の定義 今のDT_OB
$nowDT = new DateTime("");
$now = [];
$now["Y"] = intval($nowDT->format("Y"));
$now["m"] = intval($nowDT->format("m"));
$now["d"] = intval($nowDT->format("d"));

$display = []; //表示用日付


#===========================================================
# 1.フォームの受取りとモード切替

$in = parse_form(); //フォームの受取り

//case1. フォームを受け取ったとき
if(isset($in["mode"]))
{
    // echo $_SESSION['token']."<br>";
    // var_dump($in);
    echo "****** in *******"."<br>";
    foreach ($in as $key => $value) {
        if(!empty($value)){echo $key." => ".$value."<br>";}  
    }

    validateToken(); //tokenのチェック

    //case1-1. 入力スケジュールの提出
    if($in["mode"]=="submit")
    {
        submit(); //提出モード関数

    } 
    //case1-2. "選択月"の変更
    elseif(($in["mode"]=="change_next")||($in["mode"]=="change_befor"))
    {
        change(); //"月"変更モード関数
        read_save_shift(); //DBの保存データを反映する関数
    }

}
//case2. ページを開いたとき
else
{
    normal_processing(); //デフォルトモード関数
    read_save_shift(); //提出済スケジュールを反映する関数
}

createToken(); //tokenの発行 $_SESSION['token']


#===========================================================
# 関数の定義
#-----------------------------------------------------------
# デフォルトモード関数
function normal_processing(){
    global $in, $now, $display;

    var_dump($now);

    # 1.表示用日付の定義
    //月の後期なら、翌月前期を入力画面に表示
    if($now["d"]>15) 
    {
        $display["Y"] = $now["m"] == 12 ? $now["Y"] +1 : $now["Y"] ; //表示"年"
        $display["m"] = $now["m"] == 12 ? 1: $now["m"] +1 ; //表示"月"
        $display["half"] = "first"; //前期
    }
    //月の前期なら、同月後期を入力画面に表示
    else
    {
        $display["Y"] = $now["Y"]; //表示"年"
        $display["m"] = $now["m"]; //表示"月"
        $display["half"] = "second"; //後期    
    }
    
    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日
    $display["week"] = ($display["end_d"] - intval($endDT->format("w")) +1)%7; //月初日の曜日

    echo sprintf("%04d年%02d月%02d日(%d)%s",$display["Y"] ,$display["m"] ,$display["end_d"], $display["week"], $display["half"]);

        
}


#-----------------------------------------------------------
# スケジュール提出($in["mode"]=="submit")を受け取ったとき実行する関数
function submit(){
    global $nowDT, $in, $now, $display;

    # sub1.入力スケジュールを一つの文字列に変換する
    $shift =[]; //提出スケジュール時間を格納
    for ($i=1; $i <= 31 ; $i++)
    {
        $shift[$i] = [];
        foreach ($in as $form_name => $form_val) {
            $form_nom = substr($form_name,-2); //行の番号(日付)
            // echo $form_nom ."<br>";
            if($form_nom == $i)
            {
                // echo "form_nom : ".$i." form_val : ".$form_val ."<br>";

                $shift[$i][] = $form_val;
            }
        }

        //空要素の削除
        foreach ($shift[$i] as $key => $value) {
            if(empty($value)){unset($shift[$i][$key]);}
        }
        $shift[$i] = implode(" ",$shift[$i]);

        //日付情報の追加
        $shift[$i] = empty($shift[$i]) ? "" : sprintf("%02d",$i)." ".$shift[$i] ;

    }

    //空要素の削除
    foreach ($shift as $key => $value) {
        if(empty($value)){unset($shift[$key]);}
    }
    $shift = implode(",",$shift); ////提出スケジュール時間文字列
    // echo $shift ."<br>";

    # sub2.入力データをデータベースに保存
    $SQL = <<<_SQL_
    INSERT INTO submission_shift 
    ( `employee_id`, `month`, `shift`, `comment`, `update_date`) 
    VALUES ( ?,?,?,?,?)
    _SQL_;
    $DATA = array(
        $_SESSION["employee_id"], $in["month"], $shift, 
        $in["comment"], $nowDT->format("Y-m-d")
    );

    insert_db($SQL,$DATA);

    # sub3.表示用日付の定義
    $displayDT = new DateTime($in["month"]);
    $display["Y"] = intval($displayDT->format("Y")); //表示"年"
    $display["m"] = intval($displayDT->format("m"));; //表示"月"
    $display["d"] = intval($displayDT->format("d"));; //表示"日"
    $display["half"] = $display["d"]==1 ? "first" : "second"; //前期or後期 
    
    //月末日と週の定義
    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日
    $display["week"] = ($display["end_d"] - intval($endDT->format("w")) +1)%7; //月初日の曜日


}


#-----------------------------------------------------------
# 選択月変更($in["mode"]=="change_next"or"change_befor")を
// 受け取ったとき実行する関数
function change(){
    global $in, $now, $display;

    $changeDT = new DateTime($in["month"]);
    $change = [];
    $change["Y"] = intval($changeDT->format("Y"));
    $change["m"] = intval($changeDT->format("m"));
    $change["d"] = intval($changeDT->format("d"));

    # change1.表示用日付の定義
    switch ($in["mode"])
    {
        // change1-1 $in["mode"]=="change_next"のとき
        case "change_next": 

            //月の後期なら、翌月前期を入力画面に表示
            if($change["d"]>15) 
            {
                $display["Y"] = $change["m"] == 12 ? $change["Y"] +1 : $change["Y"] ; //表示"年"
                $display["m"] = $change["m"] == 12 ? 1: $change["m"] +1 ; //表示"月"
                $display["half"] = "first"; //前期or後期
            }
            //月の前期なら、同月後期を入力画面に表示
            else
            {
                $display["Y"] = $change["Y"]; //表示"年"
                $display["m"] = $change["m"]; //表示"月"
                $display["half"] = "second"; //前期or後期    
            }
            break;

        // change1-2 $in["mode"]=="change_befor"のとき
        case "change_befor": 

            //月の前期なら、前月後期を入力画面に表示
            if($change["d"]<16) 
            {
                $display["Y"] = $change["m"] == 1 ? $change["Y"] -1 : $change["Y"] ; //表示"年"
                $display["m"] = $change["m"] == 1 ? 12: $change["m"] -1 ; //表示"月"
                $display["half"] = "second"; //前期or後期
            }
            //月の後期なら、同月前期を入力画面に表示
            else
            {
                $display["Y"] = $change["Y"]; //表示"年"
                $display["m"] = $change["m"]; //表示"月"
                $display["half"] = "first"; //前期or後期    
            }
            break;

    }

    //月末日と週の定義
    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日
    $display["week"] = ($display["end_d"] - intval($endDT->format("w")) +1)%7; //月初日の曜日

    echo sprintf("%04d年%02d月%02d日(%d)%s",$display["Y"] ,$display["m"] ,$display["end_d"], $display["week"], $display["half"]);

    echo "<br>".
    $change["Y"]." ".
    $change["m"]." ".
    $change["d"]." ";

}

#-----------------------------------------------------------
# 提出済スケジュールを反映する
function read_save_shift(){
    global $in, $now, $display;
     
    // 1.DBに保存された提出済みスケジュールを呼び出す。
    $SQL = <<<_SQL_
    SELECT shift, comment FROM submission_shift 
    WHERE employee_id = ? AND month = ?
    ORDER BY ss_id DESC
    _SQL_;

    $value_month = $display["half"] == "first"
    ? sprintf("%04d-%02d-01",$display["Y"],$display["m"])
    : sprintf("%04d-%02d-16",$display["Y"],$display["m"]) ;

    $DATA = array($_SESSION["employee_id"], $value_month,);
    $data = select_db($SQL,$DATA);  //関数の実行（取得データをリターン） 
    $data = $data[0];

    echo"<br>**保存データ**<br>";
    var_dump($data);
    echo"<br>";

    // 2.DBより受け取った情報を加工
    // 2-1　コメント
    $in["comment"] = $data["comment"]; 

    //2-2スケジュール
    $data["shift"] = explode(",",$data["shift"]);
    // var_dump($data["shift"]);
    // echo"<br>";

    $shift = [];
    foreach ($data["shift"] as $vals) {
        $key = substr($vals,0,2);


        $vals = explode(" ",substr($vals,3));
    }

    var_dump($shift);
    echo"<br>";
    

    



}
#===========================================================

# 2.表示要素の部品作成
//2-1 入力テーブル列(tr要素)の作成
$trdate = [];
switch ($display["half"]) {
    case 'first':
        echo"";
        $trdate["start"] = 1;
        $trdate["end"] = 15;
        $week_num = $display["week"];
        break;
    case 'second':
        $trdate["start"] = 16;
        $trdate["end"] = $display["end_d"];
        $week_num = $display["week"]+(16%7)-1;
        break;
}
$weeks = array("日","月","火","水","木","金","土",);
$tr_element ="";

// テンプレートファイルの読み込み
$file = "tmpl/table_parts.tmpl";
$text = file_get_contents($file);

// 一日毎にtr要素の作成
for ($i = $trdate["start"]; $i <= $trdate["end"]; $i++) 
{
    //差替え文字の配列
    $num = sprintf("%02d",$i); //日付
    $select_elements = [];
    $select_e_names = array("in1", "out1", "in2","out2",);

    foreach ($select_e_names as $select_e_name) 
    {
        $option_element = create_o_element();

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

    //テンプレートの文字差替え
    $replace_text = $text;    
    foreach ($replace_array as $key => $val) {
        $replace_text = str_replace($key,$val,$replace_text);
    }
    $tr_element .= $replace_text;

    $week_num = $week_num == 6 ? 0 : $week_num+1; //曜日の更新
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






        
            

            
