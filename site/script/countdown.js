
webcd = function (today,party,id,url){
    
    // check and assing date objects  
    if (!today.getDay) today = new Date(today['year'],today['month'],today['day'],today['hour'],today['min'],today['sec']).getTime();
    if (!party.getDay) party = new Date(party['year'],party['month'],party['day'],party['hour'],party['min'],party['sec']).getTime();
    
    var curTime = party-today;
    var oldDay = false;
    var dayEl, timeEl;

    dayEl=document.getElementById('cdClockDays_'+id);
    hourEl=document.getElementById('cdClockHours_'+id);
    minEl=document.getElementById('cdClockMin_'+id);
    secEl=document.getElementById('cdClockSec_'+id);

    // correct starting display
    if (curTime > 0) document.getElementById('cdDiv_'+id).style['visibility'] = 'visible';

    return {
	// function called every time interval (second)
	actualize: function() { 
	    curTime -= 1000;
	    
	    // reach to date and remove time interval
	    if (curTime <=0) {
	       window.location = url;
	       return;
	    }
	    
	    var curDay = Math.floor(curTime/(1000*60*60*24));
	    var curHour = Math.floor((curTime - curDay*(1000*60*60*24))/(1000*60*60));
	    var curMin = Math.floor((curTime - curHour*(1000*60*60) - curDay*(1000*60*60*24))/(1000*60));
	    var curSec = Math.floor((curTime - curMin*(1000*60) - curHour*(1000*60*60) - curDay*(1000*60*60*24))/(1000));
	    // if (oldDay!=curDay)
      dayEl.innerHTML = curDay;
	    hourEl.innerHTML = curHour;
	    minEl.innerHTML = curMin;
	    secEl.innerHTML = curSec;
	    oldDay = curDay;
	},
	interval:false,
    }
}
