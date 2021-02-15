<?php
include('../common/app/function.php');//function.phpファイルの読み込み

$error_notes = "";
$body = "<body>";

#-----------------------------------------------------------
#基本情報

$collation_id = ""; //登録されたIDであれば、値を代入
$collation_pass = "";//登録されたPASSであれば、値を代入

$column_id = "id";//DBのカラム名
$column_pass = "pass_key";//DBのカラム名

$next_page = "../time_card/time_card.php"; //　ログイン後、表示ページまでのパス
#-----------------------------------------------------------


//フォームから受け取ったデータの下処理
$in = parse_form();
if(!empty($in)){
    $form_id = $in["login_id"];
    $form_pass = $in["login_pass"]; 
    
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
    $db_datas = select_db($SQL,$DATA); 

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
}

//ログインページの表示
$file = "login_input.tmpl";
$tmpl = file_get_contents($file);
$tmpl = str_replace("!error_notes!",$error_notes,$tmpl);
$tmpl = str_replace("!body!",$body,$tmpl);
echo $tmpl;