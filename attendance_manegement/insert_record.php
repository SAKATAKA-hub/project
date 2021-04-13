<?php
#-----------------------------------------------------------
# 基本設定
//ファイルの読み込み
//[読込順]function.php > time_card_function.php > insert_record.php(現在地)
include('time_card_function.php');

#-----------------------------------------------------------
# ログイン後、セッション情報を変数に代入
//id情報
if(!empty($_SESSION["employee_id"])){
    $employee_id = $_SESSION["employee_id"];
}else{$employee_id ="";}
//名前情報
if(!empty($_SESSION["employee_name"])){
    $employee_name = $_SESSION["employee_name"];
}else{$employee_name ="ログインしてください";}

//出勤状況(DBより呼出し)
if(!empty($_SESSION["work_state"])){
    $work_state = $_SESSION["work_state"];
}else{$work_state ="ログインしてください";}


#-----------------------------------------------------------
# 出退勤ボタンの入力（フォームデータの受け取り）とそれに応じた処理
$in = parse_form();

if(!empty($in)){
    //フォームの値を変数へ代入
    $work_state = $in["mode"]; 
  
    //採番ファイルの読み込み
    $num_file = "data/work_nom".$employee_id.".txt";
    $work_num = read_num_file($num_file);

    #----------------------------------
    # ＤＢへ出退勤データの挿入
    // 1)レコードの重複挿入のチェック
    // 2)データの挿入
    switch ($work_state) {
        case 'inWork': //--出勤入力--
            inWork();    
            break;
        case 'outWork': //--退勤入力--
            outWork(); 
            $work_num ++; //採番の更新
            break;
        case 'inBreak': //--休憩開始入力--
            in_out_break("in");
            break;
        case 'outBreak': //--休憩終了入力--
            in_out_break("out");
            // out_break();
            break;
        default:
        break;
    }
    
     
    //セッションの値を変更
    if($work_state == "outBreak"){$work_state = "inWork";}//"休憩終了"⇒"勤務開始"
    $_SESSION["work_state"] = $work_state;//セッションの値を変更
    // echo"セッション".$_SESSION["work_state"]."<br>";


    //----------------------------------
    // ＤＢの出勤状況を書き換え
    $SQL = "UPDATE employee_data SET work_state = ? WHERE `employee_data`.`id` = ? ";
    $DATA = array($work_state,$employee_id);//プリペアーステートメントの値
    insert_db($SQL,$DATA);  //関数の実行
    //----------------------------------

    //採番ファイルの書き込み
    $fh = fopen($num_file,'w');
    $f_data = $work_num;
    fwrite($fh,$f_data);
    fclose($fh);


}


#-----------------------------------------------------------
# 出勤状況に対応した表示画面の変更処理
$work_state_text = "";
$in_class = "inoperable";//出勤ボタンのスタイル切換え
$break_class = "inoperable";//退勤ボタンのスタイル切り換え
$out_class = "inoperable";//休憩ボタンのスタイル切り換え

$in_action = "";//出勤ボタンのスタイル切換え
$break_action = "";//退勤ボタンのスタイル切換え
$out_action = "";//休憩ボタンのスタイル切換え

$post_break = "inBreak";//休憩ボタンからフォームで送る内容の切り替え（inBreak/outBreak）
$user_info_style = "userInfo outWork";//ユーザー情報表示エリアのスタイル切替え

switch ($work_state) {
    case 'inWork':
        $work_state_text = "出勤中";
        $out_class = "";
        $out_action = "#";
        $break_class = "";
        $break_action = "#";
        $user_info_style = "userInfo";      
        break;
    case 'outWork':
        $work_state_text = "退勤済";
        $in_class = "";
        $in_action = "#";      
        break;
    case 'inBreak':
        $work_state_text = "休憩中";
        $break_class = "";
        $break_action = "#";
        $post_break = "outBreak";
        $user_info_style = "userInfo inBreak";      
        break;
    default:
    break;
}

#-----------------------------------------------------------
# 出勤形態別に実行する関数
#-----------------------------------------------------------
#--出勤入力---------------------------
function inWork(){ 
    global $employee_id, $employee_name, $work_num, $now_day, $now_time; 

    # --重複挿入のチェック--
    //  SQL命令文
    $SQL = <<<_SQL_
    SELECT `in_day` FROM work_record
    WHERE `employee_id` = ? AND `work_num` = ? 
    _SQL_;
    //  プリペアーステートメントの値
    $DATA = array($employee_id, $work_num);
    //  レコード読込み関数の実行
    $chake_d = select_db($SQL,$DATA);

    if(empty($chake_d)){    
        # --新しいレコードの作成--
        //  SQL命令文
        $SQL = <<<_SQL_
        INSERT INTO work_record 
        ( `employee_id`, `employee_name`,`work_num`, `in_day`,`in_time` ) 
        VALUES ( ?,?,?,?,?)
        _SQL_;
        //  プリペアーステートメントの値
        $DATA = array(
            $employee_id, $employee_name, $work_num, $now_day, $now_time 
        );
        //  レコード作成関数の実行
        insert_db($SQL,$DATA);  
    }
}


#--退勤入力---------------------------
function outWork(){
    global $now_time, $employee_id, $work_num;
    # --重複挿入のチェック--
    //退勤入力後に採番が変化し、同じ裁判のデータに上書きができないため、チェックの必要なし

    # --レコードの上書き--
    //  SQL命令文
    $SQL = <<<_SQL_
    UPDATE work_record 
    SET `out_time` = ?
    WHERE `employee_id` = ? 
    AND `work_num` = ? 
    _SQL_;
    //  プリペアーステートメントの値
    $DATA = array($now_time, $employee_id, $work_num);
    //  レコード上書き関数の実行
    insert_db($SQL,$DATA);  

}


#--休憩開始・終了入力---------------------------
function in_out_break($in_out){
    global $now_time, $employee_id, $work_num;

    # --レコードの読み込み--
    //  SQL命令文
    $SQL = <<<_SQL_
    SELECT `break` FROM work_record
    WHERE `employee_id` = ? AND `work_num` = ? 
    _SQL_;
    //  プリペアーステートメントの値
    $DATA = array($employee_id, $work_num);
    //  レコード読込み関数の実行
    $break_d = select_db($SQL,$DATA);
    $break_d = $break_d[0][0];
    $break_d .= $now_time.",";//休憩時間を代入

    // if($break_d==NULL){echo "ぬる";}
    // else{echo $break_d."<br>";}

    //保存したデータ数の確認
    $sub_break_d = substr($break_d,0,-1);//尾末の","を除く
    $break_d_array = explode(",",$sub_break_d);
    // foreach($break_d_array as $c){
    //     echo $c."<br>";
    // }
    $break_d_count = count($break_d_array);
    // echo $break_d_count;

    switch ($in_out) {
        case "in":
            if($break_d_count %2 == 1){
                $SQL = <<<_SQL_
                UPDATE work_record 
                SET `break` = ?
                WHERE `employee_id` = ? AND `work_num` = ? 
                _SQL_;
                //  プリペアーステートメントの値
                $DATA = array($break_d, $employee_id, $work_num);
                //  レコード上書き関数の実行
                insert_db($SQL,$DATA);
            }
            break;
        case "out":
            if($break_d_count %2 == 0){
                $SQL = <<<_SQL_
                UPDATE work_record 
                SET `break` = ?
                WHERE `employee_id` = ? AND `work_num` = ? 
                _SQL_;
                //  プリペアーステートメントの値
                $DATA = array($break_d, $employee_id, $work_num);
                //  レコード上書き関数の実行
                insert_db($SQL,$DATA);
            }
            break;
    }

}

