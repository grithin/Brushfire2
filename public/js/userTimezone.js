///makes a call to set the user timezone if the bf.json.timezone is not preset
$(function(){
	if(!bf.json.timezone){
		var date = (new Date)
		$.json({url:'/brushfire/stdJson',data:{_setTimezone:true,_dst:date.dst(),_tzOffset:date.getTimezoneOffset()}})	}	})