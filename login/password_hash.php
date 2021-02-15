<?php
include('../common/app/function.php');//function.phpファイルの読み込み
$error_notes = "";
$body = "<body>";

#-----------------------------------------------------------
#基本情報
$next_page = "../index.php"; //　ログイン後、表示ページまでのパス
$error_notes = "";
$body = "<body>";
#-----------------------------------------------------------


$pass0 = "0000";
$save_pass0 = password_hash($pass0, PASSWORD_DEFAULT);//パスワードの暗号化
echo $save_pass0."<br>";

$pass1 = "0001";
$save_pass1 = password_hash($pass1, PASSWORD_DEFAULT);//パスワードの暗号化
echo $save_pass1."<br>";

$pass2 = "0002";
$save_pass2 = password_hash($pass2, PASSWORD_DEFAULT);//パスワードの暗号化
echo $save_pass2."<br>";

$form_pass = "0000";
$save_pass = "$2y$10$1C38CGRNSpyvIODGzudQB..7DpHhE5z5cm.9YULIXe.KaAxv1jQxa";

$form_id = "0002";
$form_pass = "0002";
$save_pass = "";


//フォームから受け取ったデータの下処理
// $in = parse_form();
// if(!empty($in)){
//     $form_id = $in["login_id"];
//     $form_pass = $in["login_pass"]; 
// }





#-----------------------------------------------------------
# データベースからデータを授受する
$from = "id,name,work_state,pass";
$SQL = 
    "SELECT {$from} 
    FROM employee_data
    JOIN pass_data
    ON employee_data.pass_key = pass_data.pass_id
    WHERE id = ?";//SQL命令文
$DATA = array($form_id);//プリペアーステートメントの値
$db_datas = parse_db($SQL,$DATA); 
// $db_data = $db_datas[0]; 
// #-----------------------------------------------------------
// var_dump($db_datas);
// var_dump($db_data);

//(1)IDが登録されているか確認。
if($db_datas == NULL){
    $error_notes.="<p>IDが正しくありません。</p>";
    // echo"<p>IDが正しくありません。</p>";
}else{
    $db_data = $db_datas[0];
    $save_pass = $db_data["pass"];

    // (2)パスワードが一致するか確認
    if(!password_verify( $form_pass, $save_pass)){
        $error_notes.="<p>パスワードが正しくありません。</p>";
        // echo "パスワードが一致しません";
    }else{
        echo "ID・パスワードOK!<br>";
        echo "-----------------------------------------------------------<br>";
            #-----------------------------------------------------------
            # (3)セッションへ保存
            #　※ログイン中にセッションに保存しておきたい情報を記述します。
            $_SESSION["employee_id"] = $db_data["id"];;
            $_SESSION["employee_name"] = $db_data["name"];;
            $_SESSION["work_state"] = $db_data["work_state"];
            #-----------------------------------------------------------
            // (4)ログイン後のページへ進む
            $body =
            <<<_onload_
             <body onload="location.href = '{$next_page}'" >
            _onload_;

    }
}