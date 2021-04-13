<?php
#===========================================================
# タイムカード時間集計クラスファイル (class.php)
#===========================================================
#ファイルの読み込み
//[読込順]function.php > class.php(現在地)
include('../common/app/function.php'); 
#-----------------------------------------------------------

class Aggregates
{
    private $CutTime = 15; //n分刻みに集計時間を区切る。

    private $RestrainTime = 0; //勤務時間(分)
    private $BreakTime = 0; //休憩時間(分)
    private $WorkingTime = 0; //実労働時間(分)

    # データ配列を出力する関数
    public function getData()
    {
        $datas_array = [];
        $befor_datas_array = array(
            "RestrainTime" => $this->RestrainTime, 
            "BreakTime" => $this->BreakTime, 
            "WorkingTime" => $this->WorkingTime, 
        ); 
        foreach ($befor_datas_array as $key => $value) 
        {
            $datas_array[$key] = sprintf("%02d:%02d:00",floor($value/60),$value%60);
        }
        
        return $datas_array; //集計結果を配列で返す
    }

    #コンストラクト
    public function __construct($datas_array)
    {
        #----------------------------------------------
        # 勤務時間の計算
        // 出勤時間
        $in_times = explode(':',$datas_array["in_time"]);
        $in_time = $in_times[0]*60;
        $in_time += ceil( $in_times[1] / $this->CutTime)*$this->CutTime;

        // 退勤時間
        $out_times = explode(':',$datas_array["out_time"]);
        $out_time = $out_times[0]*60;
        $out_time += floor( $out_times[1] / $this->CutTime)*$this->CutTime;

        // 勤務時間
        $time = $out_time - $in_time;
        $this->RestrainTime = $time < 0 ? 0 : $time ;

        #----------------------------------------------
        # 休憩時間の計算
        // 休憩入出時間の記録を配列に保存
        if(!empty($datas_array["break"]))
        {
            $break_times_array = [];
            $datas_array["break"] = substr($datas_array["break"],0,-1); //尾末の","を除く
            $times_array = explode(',',$datas_array["break"]);
    
            foreach ($times_array as $value) 
            {
                $break_times = explode(':',$value);
                $break_times_array[] = $break_times[0]*60 + $break_times[1];
            }
    
            // 休憩時間の計算
            // ※CutTimeの繰り上げ時間 > 出勤時間　のときは、繰り下げ時間
            $sum_time = 0;
            $count = count($break_times_array)/2; //休憩数
            for ($i=0; $i < $count; $i++) {
                $sum_time += $break_times_array[2*$i+1] - $break_times_array[2*$i]; //退勤時間 - 出勤時間
                // $time = $break_times_array[2*$i+1] - $break_times_array[2*$i]; //退勤時間 - 出勤時間
                // $ceil_time = ceil( $time / $this->CutTime)*$this->CutTime; //繰上げ時間
                // $time = $ceil_time > $this->RestrainTime ? 
                //     floor( $time / $this->CutTime)*$this->CutTime : $ceil_time ;

                // $this->BreakTime += ceil( $time / $this->CutTime)*$this->CutTime; //休憩時間
            }
            $ceil_time = ceil( $sum_time / $this->CutTime)*$this->CutTime; //繰上げ時間
            $sum_time = $ceil_time > $this->RestrainTime ? 
                floor( $sum_time / $this->CutTime)*$this->CutTime : $ceil_time ;

            $this->BreakTime += $sum_time; //休憩時間

    
        }


        #----------------------------------------------
        # 実労働時間の計算
        $this->WorkingTime = $this->RestrainTime - $this->BreakTime;
    }

}
#----------------------------------------------
# テスト用

// $datas_array = array(
//     "in_time" => "07:55:00",
//     "out_time" => "24:00:00",
//     "break" => "10:00:00,10:28:00,14:10:00,24:00:00,",
//     // "break" => "",
// );
// $aggregates = new Aggregates($datas_array);
// // 集計結果の追加
// $datas_array = array_merge($datas_array, $aggregates->getData());
// var_dump($datas_array);


// $DT_in_day = new DateTime(); $work_num=1;

// $datas_array = array(
//     "employee_id" => $_SESSION["employee_id"], 
//     "employee_name" => $_SESSION["employee_name"],
//     "work_num" => $work_num,
//     "in_day" => $DT_in_day->format("Y-m-d"),
//     "in_time" => "00:00:00",
//     "out_time" => "24:00:00",
//     "break" => "",
// );

// $DATA = array_values($datas_array);

// var_dump($DATA);

