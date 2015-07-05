///makes a call to /user/setTimezone with the javascript offset if the bf.json.timezone is not preset
$(function(){
	if(!bf.json.timezone){
		$.json({url:'/brushfire/user/setTimezone',data:{offset:(new Date).getTimezoneOffset()}})	}	})