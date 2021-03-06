//add the various available framework hooks
bf.view.updateHooks.push($.eachApply.arg('[data-redirect]',bf.view.ele.redirect))
bf.view.updateHooks.push($.eachApply.arg('[data-deleteUrl]',bf.view.ele.delete))

bf.view.updateHooks.push($.eachApply.arg('[data-timeFormat]',bf.view.ele.timeFormat))
//this one makes the assumption all date inputs are prefilled with utc times
bf.view.updateHooks.push($.eachApply.arg('[type="date"],[type="datetime"],[data-utcFormat]',bf.view.ele.utcFormat))
bf.view.updateHooks.push($.eachApply.arg('[data-timeAgo]',bf.view.ele.timeAgo))
bf.view.updateHooks.push($.eachApply.arg('[data-redirect]',bf.view.ele.redirect))

$(function(){
	bf.run(bf.view.updateHooks)	})