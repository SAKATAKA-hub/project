<?php
#===========================================================
# タイムカード　プログラムファイル (program.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php　> class.php > issert_function.php > program.php(現在地)
include('issert_function.php');

#-----------------------------------------------------------
# ログイン後、セッション情報を変数に代入

$work_state = !empty($_SESSION["work_state"]) ? $_SESSION["work_state"] : "" ;
// *一連の処理を終えた後、$_SESSION["work_state"]を更新する。

#-----------------------------------------------------------
# 出退勤ボタンの入力（フォームデータの受け取り）とそれに応じた処理
$in = parse_form();

if(!empty($in)){
    //フォームの値を変数へ代入
    $work_state = $in["mode"]; 
  
    //採番ファイルの読み込み
    $work_num = read_work_num();

    #----------------------------------
    # ＤＢへ出退勤データの挿入
    // 1)レコードの重複挿入のチェック
    // 2)データの挿入
    switch ($work_state) {
        //--出勤入力--
        case 'inWork': 
            inWork(); //情報登録処理 
            break;

        //--退勤入力--
        case 'outWork': 
            outWork();  //情報登録処理 
            break;

        //--休憩開始入力--
        case 'inBreak': 
            in_out_break("in"); //情報登録処理 
            break;

        //--休憩終了入力--
        case 'outBreak': 
            in_out_break("out"); //情報登録処理 
            break;
        default:
        break;
    }

}

#-----------------------------------------------------------
# 出退勤ボタンの設定
$work_buttons = array(

    "inWork" => array(
        "mode" => "inWork",
        "class" => "display",
        "text" => "出勤",
    ),

    "break" => array(
        "mode" => "inBreak",
        "class" => "display",
        "text" => "休憩",
    ),

    "outWork" => array(
        "mode" => "outWork",
        "class" => "display",
        "text" => "退勤",
    ),

);

# 出勤状況によるボタン表示設定の切換え
switch ($work_state) {
    case 'inWork': //--出勤入力--
        $work_buttons["inWork"]["class"] = "";
        $user_info_text = "出勤中";
        break;
    case 'outWork': //--退勤入力--
        $work_buttons["outWork"]["class"] = "";
        $work_buttons["break"]["class"] = "";  
        $user_info_text = "退勤中";  
        break;
    case 'inBreak': //--休憩開始入力--
        $work_buttons["inWork"]["class"] = "";
        $work_buttons["outWork"]["class"] = "";
        $work_buttons["break"]["mode"] = "outBreak";
        $user_info_text = "休憩中";  
        break;
    case 'outBreak': //--休憩終了入力--
        $work_buttons["inWork"]["class"] = "";
        $user_info_text = "出勤中";    
        break;
    default:
    $user_info_text = "";
    break;
}



