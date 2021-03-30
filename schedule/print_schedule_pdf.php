<?php
#===========================================================
# スケジュール印刷用PDF表示ページ (print_schedule_pdf.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > create_schedule_program.php > print_schedule_pdf.php(現在地)
 
include('create_schedule_program.php');

# HTML,CSSの読み込み
$weekly_print_html = print_html();
$css = print_css();

// foreach ($weekly_print_html as $i => $html) {
//     $html .= $css;
// }
// var_dump($weekly_print_html);


# TCPDFの設定
require_once('../common/TCPDF/tcpdf.php');
$tcpdf = new TCPDF("L", "mm", "A4", true, "UTF-8" );
$tcpdf->setPrintHeader(false);
$tcpdf->setPrintFooter(false);

// $html = $weekly_print_html[0].$css;
// $tcpdf->AddPage(); // 新しいpdfページを追加
// $tcpdf->SetFont("kozgopromedium", "", 10); // 小塚ゴシックを利用
// $tcpdf->writeHTML($html);

for ($i=0; $i <2 ; $i++) { 
    $html = $weekly_print_html[$i].$css;
    $tcpdf->AddPage(); // 新しいpdfページを追加
    $tcpdf->SetFont("kozgopromedium", "", 10); // 小塚ゴシックを利用
    $tcpdf->writeHTML($html);
    
}


ob_end_clean();
$tcpdf->Output('schedule.pdf', 'I');



// HTMLの作成
function print_html(){
    global $in, $display, $calendar_weeks, $employees, $calcutation;

    $weekly_print_html = [];
    foreach ($calendar_weeks as $w => $c_week){
        #1. テーブル見出し部品の作成
        $week_date_text = array("","");
        foreach ($c_week as $c_day)
        {
            $date_text = sprintf("%02d日", $c_day["date"]);
            $week_date_text[0] .= "<th>$date_text</th>";

            $date_text = $c_day["week_text"];
            $week_date_text[1] .= "<th>$date_text</th>";
        }

        $table_header = <<<_text_
        <tr>
        <th>STAFF</th>
        $week_date_text[0]
        <th>WEEK</th>
        <th>MON</th>
        </tr>
        <tr>
        <th>NAME</th>
        $week_date_text[1]
        <th>TOTAL</th>
        <th>TOTAL</th>
        </tr>
        _text_;
    
    
        #2. テーブル内部部品の作成
        $table_body = "";
    
        foreach ($employees as $employee)
        {
            // 従業員情報
            $employee_data = [];
            $employee_data[0] = sprintf("<td>%s</td>",$employee["id"]);
            $employee_data[1] = sprintf("<td>%s</td>",$employee["name"]);
            
            // 勤務情報
            $work_data = array("","");
            foreach ($c_week as $c_day)
            {
                if($c_day ["this_month"])
                {
                    $name_key = "1";;
                    $input_name1 = sprintf("d%02d:%04d:in%s", $c_day["date"], $employee["id"], $name_key);
                    $input_name2 = sprintf("d%02d:%04d:out%s", $c_day["date"], $employee["id"], $name_key);
                    $text = isset($in[$input_name1])&&empty(!$in[$input_name1]) ? sprintf("%s-%s" ,$in[$input_name1] ,$in[$input_name2]) :"公休";
                    $work_data[0] .= "<td>$text</td>";

                    $name_key = "2";;
                    $input_name1 = sprintf("d%02d:%04d:in%s", $c_day["date"], $employee["id"], $name_key);
                    $input_name2 = sprintf("d%02d:%04d:out%s", $c_day["date"], $employee["id"], $name_key);
                    $text = isset($in[$input_name1])&&empty(!$in[$input_name1]) ? sprintf("%s-%s" ,$in[$input_name1] ,$in[$input_name2]) :"";
                    $work_data[1] .= "<td>$text</td>";
                }
                else
                {
                    $work_data[0] .= "<td></td>";
                    $work_data[1] .= "<td></td>";
                }
            }    
    
            // 集計情報
            $agg_text = array(
                $calcutation->getPrivateWeekWork($employee["id"],$w) ,
                "公休".$calcutation->getPrivateWeekHolyday($employee["id"],$w) ,
                $calcutation->getPrivateMonthWork($employee["id"]) ,
                "公休".$calcutation->getPrivateMonthHolyday($employee["id"]) ,
            );

            $agg_data = [];
            $agg_data[0] = <<<_text_
            <td>$agg_text[0]</td>
            <td>$agg_text[2]</td>
            _text_;

            $agg_data[1] = <<<_text_
            <td>$agg_text[1]</td>
            <td>$agg_text[3]</td>
            _text_;
    
    
            // 従業員情報 + 勤務情報 + 集計情報
            $tr_text = <<<_text_
            <tr>
            $employee_data[0]
            $work_data[0]
            $agg_data[0]
            </tr>
            <tr>
            $employee_data[1]
            $work_data[1]
            $agg_data[1]
            </tr>
            _text_;
    
            $table_body .= $tr_text."\n";
        }
    
        #3. テーブルフッターの作成
        $total_agg_text = "";
        foreach ($c_week as $c_day)
        {
            $text = $c_day ["this_month"] ? $calcutation->getTotalDay($c_day["date"]) : "" ;
            $total_agg_text .= "<td>$text</td>";
        }
        $total_week = $calcutation->getTotalWeek($w);
        $total_month = $calcutation->getTotalMonth();
    
        $table_footer = <<<_text_
        <tr>
        <td>総労働時間</td>
        $total_agg_text
        <td>$total_week</td>
        <td>$total_month</td>
        <tr>
        _text_;
    
    
        #4. <table>タグに全ての部品を埋め込む
        $display_m_index = $display["m_index"];
        $week_nom = $w +1;
    
        $weekly_print_html[$w] = <<<_text_
        <h1>{$display_m_index} スケジュール (第{$week_nom}週)</h1>
        <table border="1">
        $table_header
        $table_body
        $table_footer
        </table>
        _text_;
    
    }
    return $weekly_print_html;

}

// CSSの作成
function print_css(){
    $css = <<<_text_
    <style>
    table{
        border: solid 1px #000;
    }
    tr{
        text-align: center;
        line-height: 16px;
    }
    tr>th{
        font-weight: bold;
    }
    table>tr>td{
        border-left: solid 1px #000;
    }
    .shift_table>tr:nth-child(even){
        border-bottom: solid 1px #000;
    }
    </style>
    _text_;
    return $css;
}

?>


