<?php
#===========================================================
# スケジュール労働時間計算クラス (calcutation_class.php.php)
#===========================================================
#-----------------------------------------------------------

// # テスト用記述

// $in = [];

// //ファイルの読み込み
// //[読込順]function.php > schedule_function.php > calcutation_class.php.php(現在地)
// include('schedule_function.php');

// # 表示する従業員リストを取得
// $SQL = "SELECT `id`, `name` FROM employee_data WHERE `group` = ?";
// $DATA = array( "group1" ); //取得グループ名
// $employees = select_db($SQL,$DATA);  //DBより従業員情報の取得

// # アクセス日の取得
// $now = get_now();

// $displayDT = new DateTime(sprintf("%04d-%02d-01",$now["Y"] ,$now["m"]+1 ));
// $display = display_date($displayDT); 


// # カレンダー用の週分割にした日付情報を取得
// $calendar_weeks = get_calendar($display);

// # 従業員全員の提出スケジュールの読み込み
// foreach ($employees as $key => $employee) {
//     read_submission_shift($employee["id"]);
// }

// #-----------------------------------------------------------

// # クラスのテスト
// $calcutation = new Calcutation($in,$employees,$calendar_weeks);



// foreach ($calendar_weeks as $w => $c_week)
// {
//     foreach ($c_week as $c_day) {
//     if($c_day["this_month"])
//     {
//         # 日付
//         $d = $c_day["date"];
//         echo $d."日";

//         $employee_id ="0001";
//         echo $calcutation->getDay($employee_id, $d)." ";
//         echo "総労働時間".$calcutation->getTotalDay($d)."<br>";
//     }
//     }

//     echo $w."<br>";
//     echo "週間出勤".$calcutation->getPrivateWeekWork($employee_id,$w)." ";
//     echo "週間公休".$calcutation->getPrivateWeekHolyday($employee_id,$w)."　";
//     echo "週間総労働時間".$calcutation->getTotalWeek($w)."<br><br>";
    
// }
// echo "月間公休".$calcutation->getPrivateMonthHolyday($employee_id)."<br>";
// echo "月間出勤".$calcutation->getPrivateMonthWork($employee_id)."<br><br>";
// echo "月間総労働時間".$calcutation->getTotalMonth()."<br>";

// foreach ($employees as $key => $employee){
//     $employee_id = $employee["id"];
//     $d = 1;
    
// }



#-----------------------------------------------------------
class Calcutation{
    # プロパティ
    
    private $TotalMonth = 0; //チームの月間労働時間
    private $TotalWeek = []; //チームの週間労働時間
    private $TotalDay = []; //チームの日別労働時間

    private $Result = []; //各個人の月間労働時間・公休日数
    private $WeekResult = []; //各個人の週間労働時間・公休日数 

    # 各個人の日別労働時間の呼出メソッド 
    public function getDay($employee_id, $d){ 
        return sprintf("%02d:%02d", floor($this->Result[$employee_id][$d]/60), $this->Result[$employee_id][$d]%60) ;
        // return $this->Result[$employee_id][$d];
    }


    # 各個人の週間公休日数
    public function getPrivateWeekHolyday($employee_id,$w){
        return $this->WeekResult[$employee_id][$w]["holyday"];
    }
    # 各個人の週間労働時間の呼出
    public function getPrivateWeekWork($employee_id,$w){ 
        return sprintf("%02d:%02d", floor($this->WeekResult[$employee_id][$w]["work"]/60), $this->WeekResult[$employee_id][$w]["work"]%60) ;
    }


    # 各個人の月間公休日数
    public function getPrivateMonthHolyday($employee_id){
        return $this->Result[$employee_id]["holyday"];
    }
    # 各個人の月間労働時間の呼出
    public function getPrivateMonthWork($employee_id){ 
        return sprintf("%02d:%02d", floor($this->Result[$employee_id]["work"]/60), $this->Result[$employee_id]["work"]%60) ;
    }


    # チームの日別労働時間の呼出メソッド 
    public function getTotalDay($d){ 
        return sprintf("%02d:%02d", floor($this->TotalDay[$d]/60), $this->TotalDay[$d]%60) ;
        // return $this->TotalDay[$d];
    }
    # チームの週間労働時間の呼出メソッド 
    public function getTotalWeek($w){ 
        return sprintf("%02d:%02d", floor($this->TotalWeek[$w]/60), $this->TotalWeek[$w]%60) ;
        // return $this->TotalDay[$d];
    }
    # チームの月間労働時間の呼出
    public function getTotalMonth(){ 
        return sprintf("%02d:%02d", floor($this->TotalMonth /60), $this->TotalMonth %60) ;
    }



    # コンストラクタの定義
    public function __construct($in,$employees,$calendar_weeks)
    {
        # 各個人の合計労働時間・公休日数
        foreach ($employees as $key => $employee)
        {
            // 個人のデータ定義
            $employee_id = $employee["id"];
            $this->WeekResult[$employee_id] = [];
            $this->Result[$employee_id] = array("holyday"=>0,"work"=>0,);

            foreach ($calendar_weeks as $w => $c_week)
            {
                $this->WeekResult[$employee_id][$w] = array("holyday"=>0,"work"=>0,);
                
                foreach ($c_week as $c_day) {
                if($c_day["this_month"])
                {
                    # 日付
                    $d = $c_day["date"];

                    # 入力時間の取得
                    $time = array("in1"=>"","out1"=>"","in2"=>"","out2"=>"",);
                    $name_keys = array("in1","out1","in2","out2",);
                    foreach ($name_keys as $name_key) 
                    {
                        $input_name = sprintf("d%02d:%04d:%s", $d, $employee_id, $name_key);
                        if(!empty($in[$input_name]))
                        {
                            $time[$name_key] = $in[$input_name];
                            $time[$name_key] = empty($time[$name_key]) ? "" : explode(":",$time[$name_key]);
                            $time[$name_key] = empty($time[$name_key]) ? "" : intval($time[$name_key][0])*60 + intval($time[$name_key][1]); //時間を分表示に変更
                            // if(substr($name_key,0,3)=="out"){$total += $time[$name_key];}
                            // if(substr($name_key,0,2)=="in"){$total -= $time[$name_key];}                                              
                        }
                    }
                    # 勤務時間の処理
                    // $name_keys = array("in1","out1","in2","out2",);
                    // var_dump($time);
                    // echo$time["out1"]."<br><br>";
                    $total = 0;
                    if(!empty($time["in1"])&&!empty($time["out1"])){
                        if($time["out1"] > $time["in1"])
                        {
                            $total += $time["out1"] - $time["in1"];
                        }
                        else
                        {
                            $total =  24*60 - $time["in1"] + $time["out1"];
                        }
                        
                    }


                    # 休憩時間の処理
                    $brake_rule = array(
                        "10" => 120,
                        "8.5" => 90,
                        "6" => 60,
                        "4.5" => 30,
                    );
                    foreach ($brake_rule as $criteria => $breake) {
                        if($total >= $criteria*60){  $total -= $breake; break;}
                    }
                    # プロパティに値を代入
                    //公休日数の加算
                    if(empty($total)){
                        $this->Result[$employee_id]["holyday"]++;
                        $this->WeekResult[$employee_id][$w]["holyday"]++;
                    } 
        
                    //労働時間の代入
                    $this->TotalMonth += $total;
                    $this->WeekResult[$employee_id][$w]["work"] += $total;
                    $this->Result[$employee_id]["work"] += $total;
                    $this->Result[$employee_id][$d] = $total;

                } 
                }// endforeach ($w)

            }// endforeach ($calendar_weeks)

        }//foreach($employees)


        # チームの日別労働時間 
        foreach ($calendar_weeks as $w => $c_week)
        {
            //週の合計
            $this->TotalWeek[$w] = 0;

            foreach ($c_week as $c_day) {
            if($c_day["this_month"])
            {
                # 日付
                $d = $c_day["date"];


                //日の合計
                $this->TotalDay[$d] = 0;

                foreach ($employees as $key => $employee)
                {
                    $employee_id = $employee["id"];
                    $this->TotalDay[$d] += $this->Result[$employee_id][$d];
                    $this->TotalWeek[$w] += $this->Result[$employee_id][$d];
                }

            } 
            }// endforeach ($w)

        }// endforeach ($calendar_weeks)
    
    }// endコンストラクタ

}
?>