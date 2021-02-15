<?php
#function.phpファイルの読み込み
include('../common/app/function.php');

#-----------------------------------------------------------
# 基本設定

//ログインページのパス
$login = "../login/login.php";

//日付の取得
$now_dt = new DateTime();
$now_day = $now_dt -> format('Y-m-d');//年月日
$now_time = $now_dt -> format('H:i:s');//時分秒

//エラーチェック
$error_note = "";
#-----------------------------------------------------------

// インプット情報の取得範囲
$min_day = "2021-02-08";
$max_day = "2021-02-10";

//指定した分数（n分）刻みに時間を計算
$cut_min = 15; //指定した分数（n分）刻みに時間計算

# インプット情報の取得
$SQL = "SELECT * FROM work_record WHERE `in_day` >= ? AND `in_day` <= ? ";
$DATA = array($min_day , $max_day);
$input_records = select_db($SQL,$DATA); //DB読込み

# 集計情報
$agg_records = [];
foreach($input_records as $key => $input_record){
    
    //インスタンスの生成
    $agg_records[$key] = new AggregateRecord($input_record,$cut_min);

    echo"インプット情報**********************************<br>";
    var_dump($input_record); 
    echo"<br><br>";

    echo"休憩情報<br>";
    $break_array = $agg_records[$key]->getBreakArray();
    var_dump($break_array); 
    echo"<br><br>";

    echo"出勤時間 : ";
    echo $agg_records[$key]->getRestrainTime();
    // echo"<br>";
    echo"　勤務時間 : ";
    echo $agg_records[$key]->getBreakTime();
    // echo"<br>";
    echo"　労働時間 : ";
    echo $agg_records[$key]->getWorkingTime();
    echo"<br><br>";

}

echo"**********************************<br><br>";


#-----------------------------------------------------------
# ＤＢの出勤レコード情報を集計するクラス
#-----------------------------------------------------------
//【処理内容の説明】
//・タイムカードで入力されてきた"インプット情報"と、"区切り時間"を渡し、
//集計結果を返すクラス。
//・"区切り時間"：勤務時間の集計時に、n分刻みの切り捨て集計ができるようにする。
//・集計結果は"分"単位で返す。
//・退勤時間が未入力の情報（現在出勤中）については、労働時間を計算しない。
//・このクラスから生成されたインスタンスからは、主に集計情報の呼出しを目的とし、
//インプット情報についてはインプットオブジェクト（DBから読み込んだ情報を格納）より
//引き出すものとする。
//・例外的に休憩時間について、文字列保存された情報をオブジェクト化した値を返す。
#-----------------------------------------------------------

class AggregateRecord{

    # プロパティの宣言=========================================
    private static $total_restrain_time = 0; //総出勤時間
    private static $total_break_time = 0; //総休憩時間
    private static $total_working_time = 0; //総労働時間

    private $restrain_time;//出勤時間(拘束時間)
    private $break_time;//休憩時間
    private $working_time;//労働時間(実際に働いた時間)
    private $break_array = [];//休憩時間を配列として格納

    # プロパティを呼び出すメソッド=========================================
    public static function getTotalRestrainTime(){ return $this->total_restrain_time;}
    public static function getTotalBreakTime(){ return $this->total_break_time;}
    public static function getTotalWorkingTime(){ return $this->total_working_time;}

    public function getRestrainTime(){ return $this->restrain_time;}
    public function getBreakTime(){ return $this->break_time;}
    public function getWorkingTime(){ return $this->working_time;}
    public function getBreakArray(){ return $this->break_array;}

    # コンストラクトの宣言=========================================
    public function __construct($array,$cut_m){

        # 1) 出勤時間の算出---------------------
        // 情報を変数に代入
        $in =  $array["in_time"];
        $out = $array["out_time"];
        $sum_min = 0; //※一日の総出勤時間（分）

        // 出勤情報(指定した分数みに時間算出）
        $in = $array["in_time"];
        $in = explode(":",$in);
        $in_hour = $in[0];
        $in_min = $in[1];

        if($in_min > (60-$cut_m)){
            $in_hour ++;
            $in_min = 0;    
        }else{
            for ($i=(60/$cut_m)-1; $i <= 0; $i--) { 
                if($in_min <= $cut_m*$i){
                    $in_min = $cut_m*$i;
                }
            }
        }


        // 退勤情報(指定した分数みに時間算出）
        $out_hour = 0;    $out_min = 0; $time_min = 0;

        // ※出勤終了未確定の時は、その日の出勤時間を計算しない
        if(isset($array["out_time"])){ 
            $out = $array["out_time"];
            $out = explode(":",$out);
            $out_hour = $out[0];
            $out_min = $out[1];

            for ($i=0; $i < (60/$cut_m); $i++) { 
                if($out_min >= $cut_m*$i){
                    $out_min = $cut_m*$i;
                }
            }
            // メモ：if($out_min == 0){$out_min=0;}

            //一日の総出勤時間の算出
            $sum_min = ($out_hour*60 + $out_min) - ($in_hour*60 + $in_min);
            // if(($sum_min % $cut_m) != 0){ //指定した分数刻みに時間算出
            //     $sum_min -= ($sum_min % $cut_m) - $cut_m;
            // }
        }

        // プロパティに値をセット

        $this->restrain_time = $sum_min;

        self::$total_restrain_time += $sum_min;


        # 2) 総休憩時間の算出と 休憩時間配列の作成---------------------
        // ※取得した文字列の休憩情報から、休憩毎ごとのオブジェクトを作成する

        // 情報を変数に代入
        $break = $array["break"];
        $break =substr($break,0,-1);
        $breaks = explode(",",$break);

        //オブジェクトの作成
        $break_array = []; 
        foreach($breaks as $key => $break){
            if(empty(!$breaks[0])){
                switch ($key % 2) {
                    case '0':
                        $break_array[floor($key/2)]["in"]  = $break;
                        break;
                    case '1':
                        $break_array[floor($key/2)]["out"]  = $break;
                        break;
                }
            }
        }

        // 一回ずつ各休憩時間の算出
        $sum_min = 0; //※一日の総休憩時間（分）
        $count = count($break_array);//休憩取得回数
        for ($i=0; $i < $count; $i++) { 
            //休憩開始情報
            $in = $break_array[$i]["in"];
            $in = explode(":",$in);
            $in_hour = $in[0];
            $in_min = $in[1];

            $out_hour = 0;    $out_min = 0; $time_min = 0;
            if(count($break_array[$i])>1){ //休憩終了未確定の時は、その間の休憩時間を計算しない
                $out = $break_array[$i]["out"];
                $out = explode(":",$out);
                $out_hour = $out[0];
                $out_min = $out[1];

                //各休憩時間（指定した分数みに時間算出）
                $min = ($out_hour*60 + $out_min) - ($in_hour*60 + $in_min);
                if(($min % $cut_m) != 0){ 
                    $min -= ($min % $cut_m) - $cut_m;
                }
                $break_array[$i]["min"] = $min; //配列に格納
                $sum_min += $min; //総休憩時間に加算
            }
        }
        // $break_array["sum_min"] = $sum_min; //総休憩時間を配列に格納

        // プロパティに値をセット
        $this->break_array = $break_array;

        $this->break_time = $sum_min;

        self::$total_break_time += $sum_min;


        # 3) 労働時間の算出---------------------

        $this->working_time = $this->restrain_time - $this->break_time;

        self::$total_working_time += $this->working_time;
        
    }//End __construct

}//End class

#-----------------------------------------------------------
