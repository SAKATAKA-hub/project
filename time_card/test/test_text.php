<?php
            // 1.出勤日の出勤データ登録
            $aggregates = [];
            //労働時間の集計
            $aggregates[0] = new Aggregates($datas_array);
            // 集計結果の追加
            $datas_array = array_merge($datas_array, $aggregates[0]->getData());

            $SQL = "UPDATE work_record SET `out_time` = ?,
             `RestrainTime` = ?, `BreakTime` = ?, `WorkingTime` = ?
            WHERE `employee_id` = ? AND `work_num` = ?";

            $DATA = array("24:00:00", 
            $datas_array["RestrainTime"], 
            $datas_array["BreakTime"], 
            $datas_array["WorkingTime"], 
            $_SESSION["employee_id"], $work_num);

            insert_db($SQL,$DATA);  

            $DT_in_day->modify('+1 day');

            // 2.出勤日翌日以降の出勤データ登録
            for ($i=1; $i <= $diff_day; $i++) 
            { 
                $work_num ++;

                $datas_array = array(
                    "employee_id" => $_SESSION["employee_id"], 
                    "employee_name" => $_SESSION["employee_name"],
                    "work_num" => $work_num,
                    "in_day" => $DT_in_day->format("Y-m-d"),
                    "in_time" => "00:00:00",
                    "out_time" => "24:00:00",
                    "break" => NULL,
                );     
                
                //労働時間の集計
                $aggregates[$i] = new Aggregates($datas_array);

                // 集計結果の追加
                $datas_array = array_merge($datas_array, $aggregates[$i]->getData());

                $DT_in_day->modify('+1 day');
    
                //最終日には、入力した退勤時間を代入
                if($i == $diff_day){ $datas_array["out_time"] = $today["time"];}
    
                // 新しいレコードの登録
                $SQL = "INSERT INTO work_record 
                ( `employee_id`, `employee_name`,`work_num`, `in_day`,`in_time`, 
                `out_time`, `break`, `RestrainTime`, `BreakTime`, `WorkingTime` ) 
                VALUES (?,?,?,?,?,?,?,?,?,?)";
                $DATA = array_values($datas_array);
                insert_db($SQL,$DATA);  
        
            } //endfor

?>