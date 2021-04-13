'use strict';
{
  function showTime(){
    // const now = new Date();
    const now = new Date();
    const nowYear = now.getFullYear();
    const nowMonth = String(now.getMonth()+1).padStart(2,'0');
    const nowDate = String(now.getDate()).padStart(2,'0');
    const nowHour = String(now.getHours()).padStart(2,'0');
    const nowMin = String(now.getMinutes()).padStart(2,'0');
    const nowSec = String(now.getSeconds()).padStart(2,'0');
    const dayNum = String(now.getUTCDay());

    const DayArry =["(日)","(月)","(火)","(水)","(木)","(金)","(土)"];

    let ampm = "";
    if(nowHour<12){ampm = "AM";}
    else{ampm = "PM";}

    const outputDay = `${nowYear}年${nowMonth}月${nowDate}日${DayArry[dayNum]}`;
    const outputTime = `${ampm} ${nowHour % 12}:${nowMin}:${nowSec}`;

    document.getElementById('nowDay').textContent = outputDay;
    document.getElementById('nowTime').textContent = outputTime;
    refresh();
  }
  function refresh(){setTimeout(showTime,1000);}

  showTime();

}
