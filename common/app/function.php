<?php
#セッションの開始
session_start();

#タイムソーンの設定
date_default_timezone_set('Asia/Tokyo');


#function.phpを読み込めているか確認
function test(){echo"function.php OK!";}



#-----------------------------------------------------------
# dbhの設定（DBに接続）
#-----------------------------------------------------------
$dsn = 'mysql:host=localhost; dbname=project; charset=utf8';
$user = 'root';
$pass = 'your_password';
$dbh = new PDO($dsn, $user, $pass);

#-----------------------------------------------------------
# DBからデータを授受する関数
/*
   $SQL = "SELECT * FROM *** WHERE *** = ? ? ?";//SQL命令文
   $DATA = array('***' , '***' , '***');//プリペアーステートメントの値
   $data = select_db($SQL,$DATA);  //関数の実行（取得データをリターンする） 
*/
function select_db($sql,$data){
  try{
    global $dbh;
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($dbh == null){
      echo "接続に失敗しました。";
    }else{
  
      #↓↓↓ーーーＳＱＬ文ーーー↓↓↓↓↓↓
      // $sql = "SELECT * FROM *** WHERE *** = ?";
      // $data = array('***');
      #↑↑↑--------------------↑↑↑
      $stmt = $dbh->prepare($sql);
      $stmt ->execute($data);
      $db_data = $stmt->fetchAll();
  
    }
  }catch (PDOException $e){
    echo('エラー内容：'.$e->getMessage());
    die();
  }
  return $db_data;
}

#-----------------------------------------------------------
# DBへデータを保存する関数
/*
  //データの挿入
  $SQL = "INSERT INTO work_record ( `day`, `employee_id`, `employee_name`, `work_start`) 
          VALUES ( ?,?,?,?)";//SQL命令文
  $DATA = array('2021-02-06', '0001', '手須　友三', '15:00:00');//プリペアーステートメントの値
  insert_db($SQL,$DATA);  //関数の実行
   
  //データの書き換え
  $SQL = "UPDATE *** SET `work_state` = ? WHERE `employee_data`.`id` = ? ";
  $DATA = array('***' , '***' , '***');//プリペアーステートメントの値
  insert_db($SQL,$DATA);  //関数の実行

*/

function insert_db($sql,$data){
  try{
    global $dbh;
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($dbh == null){
      echo "接続に失敗しました。";
    }else{
  
      #↓↓↓ーーーＳＱＬ文ーーー↓↓↓↓↓↓
      // $sql = "SELECT * FROM *** WHERE *** = ?";
      // $data = array('***');
      #↑↑↑--------------------↑↑↑
      $stmt = $dbh->prepare($sql);
      $stmt ->execute($data); 
    }
  }catch (PDOException $e){
    echo('エラー内容：'.$e->getMessage());
    die();
  }
}





#-----------------------------------------------------------
# フォームから受け取ったデータを下処理する関数
#-----------------------------------------------------------
function parse_form(){
  $param = array();
  if (isset($_GET) && is_array($_GET)) { 
    $param += $_GET;
  }
  if (isset($_POST) && is_array($_POST)) {
    $param += $_POST;
  }
  
  $form_data = [];
  foreach ( $param as $key => $val) {
    # 2次元配列ではないことを確認
    if(!is_array($val)){
      # 文字コードの処理
      $enc = mb_detect_encoding($val);
      $val = mb_convert_encoding($val,"UTF-8",$enc);

      # 特殊文字の処理
      $val = htmlentities($val,ENT_QUOTES, "UTF-8");

      # CSVファイル保存のためにコンマを変換
      $val = str_replace(",", "&#44;", $val);

      # 改行コードの変換
      $val = str_replace("\r\n", "_kaigyou_", $val);
      $val = str_replace("\r", "_kaigyou_", $val);
      $val = str_replace("\n", "_kaigyou_", $val);

      $form_data[$key] = $val;
    }
  }
  return $form_data;
}

#-----------------------------------------------------------
# tokenの関数　＊フォーム送信時のCSRF対策
# 1.createToken(); //tokenの発行 $_SESSION['token']
# 2.validateToken(); //tokenのチェック
#-----------------------------------------------------------
# 1.tokenの発行
//tokenをセッションへ保存
function createToken() {
  if (!isset($_SESSION['token'])) {
    $nom = mt_rand(28,40);
    $_SESSION['token'] = bin2hex(random_bytes($nom));
  }
}
// フォーム送信ページよりtokenを送る
//$replace_array = array("!token!" => $_SESSION['token'] ,);
//<input type="hidden" name="token" value="!token!">

# 2.トークンのチェック
function validateToken() {
  global $in;
  if (
    empty($_SESSION['token']) || 
    $_SESSION['token'] !== $in['token']
  ){
    exit('Invalid post request');
  }
}

#-----------------------------------------------------------
# アラートを表示する関数
#-----------------------------------------------------------
function alert($alt){
  $alert = "<script type='text/javascript'>alert('". $alt. "');</script>";
  echo $alert;
}

#-----------------------------------------------------------
# CSVファイルへの書き込み関数
#-----------------------------------------------------------
function post_csv($post_file,$user_input){
  $fh = fopen("$post_file","a");
  flock($fh,LOCK_EX);
  fputcsv($fh, $user_input);
  flock($fh,LOCK_UN);
  fclose($fh);
}
