<?php
include('../common/app/function.php');//function.phpファイルの読み込み
$error_notes = "";


#-----------------------------------------------------------
# モード管理
#-----------------------------------------------------------
$in = parse_form();//フォームから受け取ったデータの下処理
if(empty($in)){
    display_input();//入力画面の表示
}else{
    if($in["mode"] === "input"){ post_input(); }
    if($in["mode"] === "conf"){ post_conf(); }
    // display_input();//入力画面の表示
}
var_dump($in);
// display_input();

#-----------------------------------------------------------
# 登録内容を受け取ったときの処理
function post_input(){
    global $error_notes, $in;

    //エラーがなければ確認画面の表示、エラーがあれば入力画面に戻る。
    if($error_notes == ""){
        $file = "template/registration_conf.tmpl";
        $tmpl = file_get_contents($file);

        //テキストの書き換え
        foreach ($in as $key => $val) {
            $tmpl = str_replace("!{$key}!",$val,$tmpl);
        }
        // $tmpl = str_replace($in["name"],"!name!",$tmpl);
        echo $tmpl;

    }else{
        display_input();
    }

}


#-----------------------------------------------------------
# 確認内容を受け取ったときの処理
function post_conf(){
    global $in;
    foreach ($in as $key => $val) {
        echo $key.":".$val."\n";
    }

    $n = count($in);
    for ($i=1; $i < $n; $i++) { 
        # code...
    }
    #-----------------------------------------------------------
    # データベースからデータを授受する関数
       $SQL = "SELECT * FROM *** WHERE *** = ? ? ?";//SQL命令文
       $DATA = array('***' , '***' , '***');//プリペアーステートメントの値
       parse_db($SQL,$DATA);  //関数の実行（取得データをリターンする） 
    #-----------------------------------------------------------

    
}

#-----------------------------------------------------------
# 入力画面の表示関数
function display_input(){
    global $error_notes;
    $file = "template/registration_input.tmpl";
    $tmpl = file_get_contents($file);
    $tmpl = str_replace("!error_notes!",$error_notes,$tmpl);
    echo $tmpl;
}

