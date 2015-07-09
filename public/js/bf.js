//++ general tools {
//globalThis, used to capture global context.  "this" keyword refers the object the function is a method of (in this case, the global context object)
gThis = (function(){return this;})();

//+	compatibility {
//bind Introduced in JavaScript 1.8.5
//see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind
//Example: ;(function(bob){alert(bob)}.bind(null,'sue'))()
if (!Function.prototype.bind) {
	//expects new this or null, followed by arguments to prefix function call with
	Function.prototype.bind = function (oThis) {
		if (typeof this !== "function") {
		// closest thing possible to the ECMAScript 5 internal IsCallable function
		throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
		}

		var aArgs = Array.prototype.slice.call(arguments, 1), //slice from 1 to end
			fToBind = this,
			fNOP = function () {},//used to maintain the "this" prototype for check against instanceof later
			fBound = function () {
				//.apply calls a function using a passed "this" and arguments
				return fToBind.apply(this instanceof fNOP && oThis //checks to see if original function "this" is same as new oThis, or if oThis is not present
									? this //in which case, use the original function "this"
									: oThis,
								aArgs.concat(Array.prototype.slice.call(arguments)));//get an array from the arguments object, and concatenate it to preset arguments array
			};

		fNOP.prototype = this.prototype;
		fBound.prototype = new fNOP();

		return fBound;
	};
}
//+	}

///add arg method for prepending arguments to a function call.  Used instead of "bind" to maintain the "this" context present where the function is called.
Function.prototype.arg = function() {
	if (typeof this !== "function")
		throw new TypeError("Function.prototype.arg needs to be called on a function");
	//the use of Array.prototype.slice is the accepted way to turn array-like objects ("arguments") into arrays
	var slice = Array.prototype.slice,
		args = slice.call(arguments),
		fn = this,
		partial = function() {
			return fn.apply(this, args.concat(slice.call(arguments)));
		};
	partial.prototype = Object.create(this.prototype);
	return partial;
};
///escape special characters from a string for regex
RegExp.quote = function(str) {
	return (str+'').replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
}

/*
Ex
var today = new Date();
if (today.dst()) { alert ("Daylight savings time!"); }
*/
Date.prototype.stdTimezoneOffset = function() {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());///dst always back one hour in compaarison
}

Date.prototype.dst = function() {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
}


//++ jquery mods {
///jquery ajax post for json request and response.  the laziness is  strong with this one
$.json = function(options){
	var defaults = {contentType:'application/json',dataType:'json',method:'POST'}
	if(options.data && typeof(options.data) != 'string'){
		options.data = JSON.stringify(options.data)
	}
	for(var key in defaults){
		if(typeof(options[key]) == 'undefined'){
			options[key] = defaults[key]
		}
	}
	return $.ajax(options)
}
///jquery ajax file post for json request and response
/**
@param	fileInputs	can be array of elements, or a
*/
$.file = function(options,fileInputs){
	var data = new FormData()
	if(options.data){
		data.append('_json', JSON.stringify(options.data));
	}
	fileInputs.map(function(file,what){
		data.append($(file).attr('name'), file.files[0])	})

	//jquery doesn't handle setting boundary, so use basic javascript
	options.url = options.url || window.location
	var xhr = new XMLHttpRequest();
	if(options.progress){
		xhr.upload.addEventListener('progress',options.progress,false)
	}
	if(options.success){
		xhr.addEventListener('load',function(e){
			options.success(JSON.parse(e.target.responseText))
		},false)
	}
	if(options.error){
		xhr.addEventListener('error',options.progress,false)
	}

	xhr.open('POST',options.url)
	xhr.send(data)

	//xhr.setRequestHeader("X_FILENAME", file.name);

	return xhr
}
//apply fn to each ele matching selector (good for using bound arguments for later calls using bf.run)
/**
var update = $.eachApply.arg('[data-redirect]',bf.view.ele.redirect)
bf.view.updateHooks.push(update)
*/
$.eachApply = function(selector,fn){
	$(selector).each(fn)
}
//++ }

//++ }

bf = bf || {}
bf.tool = function(){};

bf.setCookie = function(name, value, exp){
	var c = name + "=" +escape( value )+";path=/;";
	if(exp){
		var expire = new Date();
		expire.setTime( expire.getTime() + exp);
		c += "expires=" + expire.toGMTString();
	}
	document.cookie = c;
}
bf.readCookie = function(name) {
	var name = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
	}
	return null;
}

bf.arrays = {}
///check if value is in an array
bf.arrays.vInA = function(v,a){
	var i;
	for(i in a){
		if(a[i] == v){
			return true;	}	}
	return false;	}

///remove items matching v from array a
/**
@param	v	value to remove (matches on ==)
@param	a	array to affect
*/
bf.arrays.rmV = function(v,a){
	var i
	for(i in a){
		if(a[i] == v){
			unset(a[i])	}	}	}

///Add, but only if unique
bf.arrays.addUnique = function(v,a){
	if(!bf.arrays.vInA(v,a)){
		a[a.length] = v;	}	}
///get the first available key in a sequential array (where potentially some have been deteted
bf.arrays.firstAvailableKey = function(a){
	for(var i = 0; i < a.length; i++){
		if(typeof(a[i]) == 'undefined'){
			return i;	}	}
	return a.length;	}
///insert on first available key in a sequential array.  Returns key.
bf.arrays.onAvailable = function(v,a){
	var key = bf.arrays.firstAvailableKey(a)
	a[key] = v;
	return key;	}

///get key of first existing element
bf.arrays.firstKey = function(a){
	for(var i = 0; i < a.length; i++){
		if(typeof(a[i]) != 'undefined'){
			return i;	}	}
	return false;	}
///get first existing element
bf.arrays.firstValue = function(a){
	var key = bf.arrays.firstKey(a)
	return a[key];	}

///ignore unset elements (since deleted values still count towards length)
bf.arrays.count = function(a){
	count = 0;
	for(var i in a){
		count += 1;	}
	return count;	}

bf.obj = {};
///set arbitrarily deep path to value use php form input semantics
/**
ex
	setValue('interest[1][name]','test',data);  => { interest: { '1': { name: 'test' } } }
*/
bf.obj.setValue = function(name,value,obj){
	nameParts = name.replace(/\]/g,'').split(/\[/)
	current = obj
	for(var i = 0; i < nameParts.length - 1; i++){
		if(!current[nameParts[i]]){
			current[nameParts[i]] = {}	}
		//since objs are moved by reference, this obj attribute of parent obj still points to parent attribute obj
		current = current[nameParts[i]]	}
	current[nameParts[nameParts.length - 1]] = value	}

/**
@param	target	object to merge into
variable number of additional parameters are objects to merge into target (over target properties)
@note, b/c this is shallow, does not make a copy of any properties that are objectd
@return	target object
*/
bf.obj.shallowMerge = function(target){
	var key
	for(var i = 0; i < arguments.length; i++){
		for(key in arguments[i]){
			target[key] = arguments[i][key]
		}
	}
	return target
}

//+	url related functions {

bf.url = {}//move functions into this
//get query string request variable
bf.url.requestVar = function(name,decode){
	decode = decode != null ? decode : true;
	url = window.location+'';
	if(/\?/.test(url)){
		var uriParts = url.split('?');
		regexName = name.replace(/(\[|\])/g,"\\$1");
		var regex = new RegExp('(^|&)'+regexName+'=(.*?)(&|$)','g');
		var match = regex.exec(uriParts[1]);
		if(match){
			if(decode){
				return decodeURIComponent(match[2]);
			}
			return match[2];
		}
	}
	return false;
}

bf.url.default = function(url){
	url = url != null ? url : window.location
	url = url+'';//convert to string for vars like window.location
	return url
}

///take a url and break it up in to page and key value pairs
bf.url.parts = function(url,decode){
	decode = decode != null ? decode : true;
	url = bf.url.default(url)
	var retPairs = []
	var urlParts = url.split('?');
	if(urlParts[1]){
		var pairs = urlParts[1].split('&');
		if(pairs.length > 0){
			for(var i in pairs){
				var pair = pairs[i]
				var retPair = pair.split('=');
				if(decode){
					retPair[0] = decodeURIComponent(retPair[0])
					retPair[1] = decodeURIComponent(retPair[1])
				}
				retPairs.push(retPair)
			}
		}
	}
	return {page:urlParts[0],pairs:retPairs}
}
///make a url from page and key value pairs
bf.url.fromParts = function(parts,encode){
	encode = encode != null ? encode : true;
	url = parts.page
	if(parts.pairs.length > 0){
		var pairs = []
		for(var i in parts.pairs){
			var pair = parts.pairs[i]
			if(encode){
				pair[0] = encodeURIComponent(pair[0])
				pair[1] = encodeURIComponent(pair[1])
			}
			pairs.push(pair[0]+'='+pair[1])
		}
		query = pairs.join('&')
		url = url+'?'+query
	}
	return url
}

///for removing parts from the url
/**
will remove exactly one matched token if regex is a string
*/
bf.url.queryFilter = function(regex,url,decode){
	var parts = bf.url.parts(url,decode)

	if(regex.constructor != RegExp){
		if(regex.constructor == String){
			regex = new RegExp('^'+RegExp.quote(regex)+'$','g');
		}else{
			//replace all
			regex = new RegExp('.*','g');
		}
	}
	if(parts.pairs.length > 0){
		var newPairs = []
		var foundPair = false
		for(var i in parts.pairs){
			var pair = parts.pairs[i]
			if(!pair[0].match(regex)){
				newPairs.push(pair)
			}
		}
		parts.pairs = newPairs
	}
	return bf.url.fromParts(parts,decode)
}


///for adding variables to URL query string
bf.url.append = function(name,value,url,replace){
	replace = replace != null ? replace : false;

	if(replace){
		url = bf.url.queryFilter(name,url)
	}
	var parts = bf.url.parts(url)
	parts.pairs.push([name,value])
	return bf.url.fromParts(parts)
}

///for adding multiple varibles to query string
/**
@param	pairs	either in form [[key,val],[key,val]] or {key:val,key:val}
*/
bf.url.appends = function(pairs,url,replace){
	var key, val
	for(var i in pairs){
		if(typeof(pairs[i]) == 'object'){
			key = pairs[i][0]
			val = pairs[i][1]
		}else{
			key = i
			val = pairs[i]
		}
		url = bf.url.append(key,val,url,replace);
	}
	return url;
}
//+	}

///Binary to decimal
bf.bindec = function(bin){
	bin = (bin+'').split('').reverse();
	var dec = 0;
	for(var i = 0; i < bin.length; i++){
		if(bin[i] == 1){
			dec += Math.pow(2,i);	}	}
	return dec;	}
///Decimal to binary
bf.decbin = function(dec){
	var bits = '';
	for(var into = dec; into >= 1; into = Math.floor(into / 2)){
		bits += into % 2;	}
	var lastBit = Math.ceil(into);
	if(lastBit){
		bits += lastBit;	}
	return bits.split('').reverse().join('');	}
bf.toInt = function(s){
	if(typeof(s) == 'string'){
		s = s.replace(/[^0-9]+/g,' ');
		s = s.replace(/^ +/g,'');
		s = s.replace(/^0+/g,'');
		s = s.split(' ');
		num = parseInt(s[0]);
	}else{
		num = parseInt(s);
	}
	if(isNaN(num)){
		return 0;
	}
	return num;
}


bf.math = {};
///round with some precision
bf.math.round = function(num, precision){
	var divider = new Number(bf.str.pad(1,precision+1,'0','right'))
	return Math.round(num * divider) / divider;	}
///will render string according to php rules
bf.str = function(str){
	if(typeof(str) == 'number'){
		return str+'';	}
	if(!str){
		return '';	}
	return str;	}
///pad a string
bf.str.pad = function(str,len,padChar,type){
	str = new String(str);
	if(!padChar){
		padChar = '0';	}
	if(!type){
		type = 'left';	}
	if(type == 'left'){
		while (str.length < len){
			str = padChar + str;	}
	}else{
		while (str.length < len){
			str = str + padChar;	}	}
	return str;	}

///set words to upper case
bf.str.ucwords = function(string){
	if(string){
		string = string.split(' ');
		var newString = Array();
		var i = 0;
		$.each(string,function(){
			newString[newString.length] = this.substr(0,1).toUpperCase()+this.substr(1,this.length)	});
		return newString.join(' ');	}	}

///set first charracter to uppercase
bf.str.capitalize = function(string){
	return string.charAt(0).toUpperCase() + string.slice(1);	}
///htmlspecialchars() - for escaping text
bf.str.hsc = function(string){
	if(string === null){
		return ''	}
	return $('<a></a>').text(string).html()	}
bf.str.nl2br = function(string){
	return string.replace(/(?:\r\n|\r|\n)/g, '<br />')	}
///for available values, toggle based on  value of "value"
bf.toggle = function(value,values){
	if(value == values[0]) return values[1]
	return values[0]	}
///to handle caught errors in promises.  Could overwrite to write to server
bf.logError = function(e){
	console.log(e)
	console.log(bf.debug.backtrace())
}


//++ data model functions {
/**
model object is generally:
	{table:obj,table,obj}
*/
bf.model = {}
/**
ex
	bf.modelPromise(['user']).then(function(){...}).catch(bf.logError)
*/
bf.modelPromise = function(tables){
	if(typeof(tables) == 'string'){
		tables = [tables]
	}
	var promises = []
	for(var i in tables){
		var table = tables[i]
		if(!bf.model[table] || !bf.model[table].loaded){
			promises.push(bf.modelLoad(table))
		}
	}
	//note, a then()ed function will not output error to console, so must append .catch, as done here
	return Q.all(promises).catch(bf.logError)
}
bf.modelLoadCallbacks = {}
bf.modelLoad = function(table){
	return Q.Promise(function(resolve, reject, notify){
		$.get('/brushfire/model?table='+table,function(model){
			var loadeds = [table]
			for(var key in model){
				if(!bf.model[key] || !bf.model[key].loaded){
					bf.model[key] = model[key]
					//if BE says table is loaded, add to array for loadCallback
					if(model[key].loaded){
						loadeds.push(key)
					}
				}
			}
			if(bf.model[table]){//potentially the model did not actually include the table name, so check
				bf.model[table].loaded = true
			}else{
				console.log('Loaded model does not include requests table',table,model)
			}

			for(var i in loadeds){
				if(bf.modelLoadCallbacks[loadeds[i]]){
					bf.modelLoadCallbacks[loadeds[i]]()	}	}

			resolve()	})	})
}

// * Found here: https://gist.github.com/vaiorabbit/5657561
bf.hashFnv32a = function(str, seed) {
    /*jshint bitwise:false */
    var i, l,
        hval = (seed === undefined) ? 0x811c9dc5 : seed;

    for (i = 0, l = str.length; i < l; i++) {
        hval ^= str.charCodeAt(i);
        hval += (hval << 1) + (hval << 4) + (hval << 7) + (hval << 8) + (hval << 24);
    }

    // Convert to 8 digit hex string
    return ("0000000" + (hval >>> 0).toString(16)).substr(-8);

}

bf.modelData = {}
bf.modelDataPromises = {}

///get a data promise for data from a model table
/**
@param	apiOptions	see ModelApi.php
@param	options	{
	noCache:true|false,}
*/
bf.loadData = function(table,apiOptions,options){
	options = options || {}
	var json = JSON.stringify(apiOptions)
	var hash = bf.hashFnv32a(json)

	if(!options.noCache){
		if(bf.modelDataPromises[hash]){
			return bf.modelDataPromises[hash];
		}
	}

	bf.modelDataPromises[hash] = Q.Promise(function(resolve, reject, notify){
		options = {url:'/model/'+table,data:apiOptions,success:function(json){
			bf.modelData[hash] = json.value
			resolve(bf.modelData[hash]) 	}}
		$.json(options)	})
	return bf.modelDataPromises[hash]
}

bf.modelFields = {}
///get the model field from the model object
/**
	can use
		model.scope.extendedColumns.column.display to customize special linked field display
	
	@return	a copy of the field object that is moved into bf.modelFields
*/
bf.getModelField = function(name,modelScope){
	var parsedName = bf.parseModelFieldName(name,modelScope)
	var fieldsKey = modelScope+'.'+parsedName.relative
	if(bf.modelFields[fieldsKey]){
		return bf.modelFields[fieldsKey]
	}
	var field = bf.model[parsedName.scope].columns[parsedName.relative] || {}
	field = JSON.parse(JSON.stringify(field))
	field.name = bf.obj.shallowMerge(parsedName, field.name || {});//copy existing field.name attributes over parsedName and put into field.name

	//handle default special naming for out of scope special fields
	if(field.name.scope != modelScope){
		//check if the display name is already set
		var extended = bf.model[modelScope].extendedColumns
		if(extended && extended[field.name.relative]){
			field.name.display = extended[field.name.relative].display
		}else{
			//first, get column comment if present
			var comment;
			var match = field.name.relative.match(/__(.*$)/)
			if(match){
				comment = match[1]
			}
			field.name.display =  (field.name.scope+' '+field.name.field).split('_').map(bf.str.capitalize).join(' ')
			if(comment){
				field.name.display = field.display+' '+comment
			}
		}
	}

	if(!field.name.display){
		var parts = field.name.relative.split('_')
		parts = parts.map(bf.str.capitalize)
		field.name.display = parts.join(' ')
	}
	bf.modelFields[fieldsKey] = field
	return field
}

///get all the name parts of a field name
/**
There are three possible ways a name can come in
	1. name (scopeless)
	2. scope.name
	3. key.name (where key is the unique key being used to link the name that exists in another scope)
*/
bf.parseModelFieldName = function(name,modelScope){
	var parsed = {}
	var parts = name.split('.')
	if(parts.length == 1){
		parsed.field = parts[0]
		parsed.relative = parsed.field
		parsed.scope = modelScope

	}else if(modelScope && parts[0] != modelScope){
		var link = bf.model[modelScope].links.dc[parts[0]]
		parsed.relative = name
		parsed.scope = link.ft
		parsed.field = parts[1]
	}else{
		parsed.relative = name
		parsed.scope = parts[0]
		parsed.field = parts[1]
	}
	parsed.absolute = parsed.scope+'.'+parsed.field
	return parsed
}

//++ }
/**
Run array of functions
@note	use function.arg() to bind arguments to the function
@note	use function.bind() to bind this + arguments to the function
@param	functions	array of functions
*/
bf.run = function(functions){
	var returnArray = []
	for(var i in functions){
		returnArray.push(functions[i]())
	}
	return returnArray
}


//++ debug {
bf.debug = {};
bf.debug.backtrace = function(fn){
		if(!fn){
			fn = arguments.callee.caller
		}else{
			fn = fn.caller	}
		if(!fn){
			return "";	}

		var trace = bf.debug.functionName(fn);

		trace="(";
		var args = [];
		for(var arg in fn.arguments){
			bf.arrays.add(fn.arguments.toString(),args);	}
		trace += '('+args.join(',')+")\n";
		return trace + bf.debug.backtrace(fn);	}

bf.debug.functionName = function(fn){
	var name=/\W*function\s+([\w\$]+)\(/.exec(fn);
	if(!name){
		return 'No name';
	}
	return name[1];
}
//++ }




bf.view = {}

bf.view.windows = {};
///redirect the page (or the new tab or the new window)
/**
@apram	loc	url | number (where number corresponds to history)
@param	windowName	if particular window is to be used, otherwise keep false
@param	type	window | tab
*/
bf.view.redirect = function(loc,windowName,type){
	//call window redirect function if window exists and unclosed
	if(windowName && bf.view.windows[windowName] && !bf.view.windows[windowName].closed){
		bf.view.windows[windowName].bf.view.redirect(loc)
		return false;	}
	if(windowName){
		if(type == 'window'){
			bf.view.windows[windowName] =  window.open(loc,windowName,newWindow);
		}else{
			bf.view.windows[windowName] =  window.open(loc,windowName);	}
		return false;	}
	if(type == 'tab'){
		return window.open(loc,'_blank');
	}else if(type == 'window'){
		return window.open(loc,null,newWindow);	}
	if(typeof(loc) == 'number'){
		if(loc == 0){
			window.location.reload(false);
		}else{
			history.go(loc);	}
	}else if(!loc){
		window.location = window.location+'';
	}else{
		window.location = loc;	}
	return false;	}


//+	pageSorting {
bf.view.ps = {}
/**
ex
	have a 
		<div id="pagingControl"></div>
	on the page
	or define the pagingControl key
	
	then do
		bf.view.ps.start({
			url:'dataUrl',
			handlers.dataHandler: function(rows){ console.log(rows); }
			})
	
	url json post should respond with json result of View::$json['value'] = SortPage::page(); View::endStdJson();
*/


///set current object defaults, run getSortPage with page 1
bf.view.ps.start = function(current){
	defaults = {url:window.location,pagingControl:$('#pagingControl'),page:0,pages:0,sorts:''}
	for(var key in defaults){
		if(typeof(current[key]) == 'undefined'){
			current[key] = defaults[key]	}	}
	bf.view.ps.makePageControl(current)
	bf.view.ps.get(current,{page:1})	}

/**
@param	current	{
	page:x,
	pages:x
	sort:x,
	url:x
	handlers:x	}
*/
bf.view.ps.get = function(current,update){
	var changed = false;
	if(update.page && current.page != update.page){
		current.page = update.page
		changed = true	}
	if(update.sort && current.sort != update.sort){
		current.sort = update.sort
		changed = true	}
	if(changed){
		$.ajax({
			url:current.url,
			contentType:'application/json',
			data:JSON.stringify(current),
			dataType:'json',
			method:'POST',
			success:function(json){
				current.pages = json.value.info.pages
				bf.view.ps.apply(json.value.rows,current)	}	})	}	}

///account for defaults, and split order and field
bf.view.ps.parseSort = function(sort){
	var order = sort.substring(0,1)
	var field
	if(order != '-' && order != '+'){
		order = '+'
		field = sort
	}else{
		field = sort.substring(1)	}
	return {order:order,field:field}	}
///arrange the arrows according to sorts
bf.view.ps.headerArrows = function(){
	//direct  the arrows according to the sorts
	var sort, column
	for(var i in bf.sorts){
		sort = bf.view.ps.parseSort(bf.sorts[i])
		column = $('.sortContainer [data-field="'+sort.field+'"]')
		if(sort.order == '+'){
			column.addClass('sortAsc')
		}else{
			column.addClass('sortDesc')	}	}	}
///shift clicks on sort header
bf.view.ps.appendSort = function(newField){
	var sort
	for(var i in bf.sorts){
		sort = bf.view.ps.parseSort(bf.sorts[i])
		if(newField == sort.field){
			sort.order = bf.toggle(sort.order,['+','-'])
			bf.sorts[i] = sort.order + sort.field
			return	}	}
	bf.sorts.push('+'+newField)	}
///non-shift clicks on sort header
bf.view.ps.changeSort = function(newField){
	var sort
	for(var i in bf.sorts){
		sort = bf.view.ps.parseSort(bf.sorts[i])
		if(newField == sort.field){
			sort.order = bf.toggle(sort.order,['+','-'])
			bf.sorts = [sort.order + sort.field]
			return	}	}
	bf.sorts = ['+'+newField]	}


bf.view.ps.apply = function(data, current){
	current.handlers.dataHandler(data,current)
	bf.view.ps.makePageControl(current)	}

/**
see getSorPage for param
*/
bf.view.ps.makePageControl = function(current){
	if(!current.pages || current.pages < 2){
		return;	}

	//ensure currect format
	current.pages = parseInt(current.pages)
	current.page = parseInt(current.page)

	//++ resolve containers {
	if(!current.pagingControl.hasClass('pagingControl')){
		var actualPagingControl = $('.pagingControl',current.pagingControl)
		//may have already been added
		if(!actualPagingControl.size()){
			actualPagingControl = $('<div class="pagingControl"></div>')
			current.pagingControl.append(actualPagingControl)	}
		current.pagingControl = actualPagingControl	}
	var paginaterDiv = $('.paginater',current.pagingControl)

	if(!paginaterDiv.size()){
		paginaterDiv = $("<div class='paginater'></div>")
		current.pagingControl.append(paginaterDiv)	}
	//empty out the paginater for update
	paginaterDiv.html('')
	//++ }

	//++	center the current page if possible {
	var context = 2;//only  show context * 2 + 1 page buttons
	var start = Math.max((current.page - context),1)
	var end = Math.min((current.page + context),current.pages)
	var extraContext = context - (current.page - start)
	if(extraContext){
		end = Math.min(end + extraContext,current.pages)
	}else{
		extraContext = context - (end - current.page)
		if(extraContext){
			start = Math.max(start - extraContext,1)	}	}
	//++	}

	//++ complete the paginater {
	if(current.page != 1){
		paginaterDiv.append('<div class="clk first">&lt;&lt;</div><div class="clk prev">&nbsp;&lt;&nbsp;</div>')	}

	for(var i=start;i <= end; i++){
		var currentClass = i == current.page ? ' current' : ''
		paginaterDiv.append('<div class="clk pg'+currentClass+'">'+i+'</div>')	}
	if(current.page != current.pages){
		paginaterDiv.append('<div class="clk next">&nbsp;&gt;&nbsp;</div><div class="clk last">&gt;&gt;</div>')	}
	paginaterDiv.append("<div class='direct'>\
				<input title='Total of "+current.pages+"' type='text' name='directPg' value='"+current.page+"'/>\
				<div class='clk go'>Go</div>\
			</div>")
	//++	}

	//clicks
	$('.clk:not(.disabled)',paginaterDiv).click(function(e){
		var page,
			target = $(this)
		if(target.hasClass('pg')){
			page = target.text()
		}else if(target.hasClass('next')){
			page = current.page + 1
		}else if(target.hasClass('last')){
			page = current.pages
		}else if(target.hasClass('first')){
			page = 1
		}else if(target.hasClass('prev')){
			page = current.page - 1
		}else if(target.hasClass('go')){
			var parent = target.parents('.paginater')
			page = Math.abs($('input',parent).val())	}
		bf.view.ps.get(current,{page:page})	})

	//ensure enter on "go" field changes page, not some other form
	$('input',paginaterDiv).keypress(function(e){
		if (e.which == 13) {
			e.preventDefault();
			$('.go',paginaterDiv).click();	}	});	}

//+	}

//+	System Messages {
bf.view.sm = {}
/**
sets defaults for option attributes:
	options.context : jquery container for the context of the messages.  Must have a 'data-context' attribute, or function will find parent with such
	options.messageContainer : jquery container for where to place the messages.  Defaults to first .messageContainer in context, but will go to higher [data-context] if no element found
*/
bf.view.sm.defaults = function(options){
	options = options || {}
	if(options.context){
		options.context = bf.view.sm.firstContext(options.context)
	}else{
		options.context = $('body');
	}

	if(!options.messageContainer){
		var parentContext = options.context
		while(!options.messageContainer){
			options.messageContainer = $('._messageContainer:first',parentContext)
			if(options.messageContainer.size()){
				break
			}
			parentContext = parentContext.parents('[data-context]:first')
			if(!parentContext.size()){
				console.log("No Message Container")
				console.log(options)
				break;
			}	}	}
	return options	}
///checks if the given element has data-context, else looks in parents
bf.view.sm.firstContext = function(ele){
	if(ele.attr('data-context')){
		return ele
	}
	var context = ele.parents('[data-context]:first')
	if(context.size()){
		return context
	}
	console.log('No data context found')
	return ele
}
/**
@param	messages	[{message}], see bf.insertMessage
*/
bf.view.sm.redirect = function(messages,url){
	bf.view.redirect(bf.url.append('_systemMessages',JSON.stringify(messages),url,true))	}
///replaces {_FIELD_} in the message with the found title
/**
supports following types of field name to title translation:
	. passed object with matching attribute
	. text of element with data-field matching field name and with data-title
	. data-title attribute value of element with 'name' attribute = field, if data-title attribute present
	. label text with for of field
	. placeholder of input with 'name' attribute = field
	. defaults to name of field
*/
bf.view.sm.parse = function(message,options){
	titles = []
	for(var i in message.fields){
		titles.push(bf.view.sm.title(message.fields[i],options))	}
	if(message.fields.length == 1){
		var title = titles.join(',')
		message.content = message.content.replace(/\{_FIELD_\}/g,'"'+title+'"');	}
	if(message.itemOffset){
		message.content += ' (item '+message.itemOffset+')';	}
	return message
}
bf.view.sm.title = function(field, options){
	while(true){//to avoid large nesting
		if(options.map){
			if(options.map[field]){
				title = options.map[field]
				break	}	}
		var holder = $('[data-field="'+field+'"][data-title]',options.context)
		if(holder.size()){
			title = holder.text()
			break	}
		holder = $('[name="'+field+'"][data-title]',options.context)
		if(holder.size()){
			title = holder.attr('data-title')
			break	}
		holder = $('[for="'+field+'"]',options.context)
		if(holder.size()){
			title = holder.text()
			break	}
		if($('[name="'+field+'"][placeholder]',options.context).size()){
			title = $('[name="'+field+'"][placeholder]',options.context).attr('placeholder')
			break	}
		title = field
		break	}
	return title	}


///highlight input container
bf.view.sm.highlight = function(message,options){
	if(message.fields.length > 0){
		for(var key in message.fields){
			var field = message.fields[key]
			var container
			if(message.itemOffset){
				var prefixedField = message.itemOffset+'-'+field
				container = $('[data-field="'+prefixedField+'"][data-container]',options.context);
				if(!container.size()){
					container = $('[name="'+prefixedField+'"]',options.context);	}	}
			if(!container || !container.size()){
				container = $('[data-field="'+field+'"][data-container]',options.context);
				if(!container.size()){
					container = $('[name="'+field+'"]',options.context);	}	}
			if(container.size()){
				container.addClass('_'+message.type+'Highlight');	}	}	}	}
bf.view.sm.unhighlight = function(options){
	var types = ['error','success','notice','warning']
	for(var i in types){
		var eleClass = '_'+types[i]+'Highlight'
		$('.'+eleClass,options.context).removeClass(eleClass)	}	}


/**
insert message into container and cause highlight
tries to find specific container before using general container
will attempt to match containers following the pattern '._messageContainer[data-field="'+singleField+'"]'

	messages attributes
		fields : array of fields the messages is related to
		content : message html content
		expiry : when to remove the message.  Unix time or time offset
		closeable : whether message   is closable (adds close button)
		type : what type of message  it is (error,success,notice,warning)

Allows for overloading:
	fn(message[string])
	fn(message[string],type)
	fn(message[string],type,options)
*/
bf.view.sm.insert = function(message,options){
	if(typeof(message)=='string'){
		if(arguments.length == 3){
			options = arguments[2]
			message = {type:arguments[1],content:arguments[0]}
		}else if(arguments.length == 2){
			message = {type:arguments[1],content:arguments[0]}
			options = false
		}else{
			message = {type:'notice',content:arguments[0]}
		}
	}
	
	options = bf.view.sm.defaults(options)
	message.fields = message.fields || []

	//set error status if applicable
	if(message.type == 'error'){
		options.context.attr('data-hasError',true)	}

	message = bf.view.sm.parse(message,options)

	var singleField;

	bf.view.sm.highlight(message,options)

	if(!message.content){
		return;	}

	//++ add message text{
	//Unfortunately, no browser standard yet
	var messageEle = $('<div data-fields="'+JSON.stringify(message.fields)+'" data-'+message.type+'Message class="_message _'+message.type+'"></div>').html(message.content)

	if(message.fields.length == 1){
		singleField = message.fields[0]	}

	var container
	if(singleField){// override general container with field specific container
		//first see if there is a specific container within the general container
		potentialContainer = $('._messageContainer[data-field="'+singleField+'"]',options.context);
		if(potentialContainer.size()){
			container = potentialContainer
		}else{
			//then just look for a specific container any where
			potentialContainer =  $('._messageContainer[data-field="'+singleField+'"]',options.context);
			if(potentialContainer.size()){
				container = potentialContainer	}	}	}

	container = container || options.messageContainer
	messageEle.hide().appendTo(container).fadeIn({duration:'slow'})
	//++ }
	if(message.expiry || message.closeable){
		bf.view.closeButton(messageEle)	}
	if(message.expiry){
		var timeout
		if(message.expiry < 86400){//less than a day, it's an offset, not unix time
			timeout = message.expiry * 1000
			//message.expiry = (new Date()).getTime()/1000 + message.expiry
		}else{
			timeout = message.expiry - (new Date()).getTime()/1000	}
		setTimeout((function(element,options){
				element.fadeOut(options)
			}).bind(null,messageEle,{duration:'slow',complete:function(){$(this).remove()}}),timeout)	}	}
///it is assumed that, if this function is used for ajax, the poster pre-removes existing messages
/**
	 @param	messages	[{type:'',fields:[],content:''},...]
	 @param	container	jquery element (ex $('#someId').  This is the container used if a more specific-to-field container is not found)
*/
bf.view.sm.inserts = function(messages,options){
	options = options || {}
	for(var k in messages){
		bf.view.sm.insert(messages[k],options)	}	}

///use itemOffset to find context
bf.view.sm.insertsWithOffsets = function(messages,context){
	for(var k in messages){
		var message = messages[k]
		if(!message.itemOffset){
			bf.view.sm.insert(message,{context:context})
			continue
		}
		options = {}
		var newContext = $('[data-context][data-itemOffset="'+message.itemOffset+'"]',context)
		if(!newContext.size()){
			newContext = context
		}
		options.context = context;
		var newMessageContainer = $('._messageContainer',context)
		if(newMessageContainer.size()){
			options.messageContainer = newMessageContainer
		}

		bf.view.sm.insert(messages[k],options)
	}
}
///attempts to remove all evidence of inserted messages
bf.view.sm.uninserts = function(options){
	options = bf.view.sm.defaults(options)
	bf.view.hasError = false
	bf.view.sm.unhighlight(options)
	options.messageContainer.empty();	}


bf.view.closeButton = function(ele,hide){
	var closeEle = $('<div class="_closeButton"></div>')
	ele.prepend(closeEle)
	if(!hide){
		closeEle.click(function(){
				$(this).parent().fadeOut({complete:function(){$(this).parent().remove()}})
			})
	}else{
		closeEle.click(function(){$(this).parent().fadeOut()})	}	}

//+	}


//++ form stuff {
bf.view.form = {}
bf.view.form.getCsrf = function(callback){
	var suscess = bf.view.form.response.arg({success:function(json){ callback(json.value) }	})
	$.json({url:'/brushfire/csrf',success:suscess})
}

///add a csrf token as input element into a form
/**
ex bf.view.form.addCsrf($('form'))
*/
bf.view.form.addCsrf = function(container){
	bf.view.form.getCsrf(function(csrf){
		container.append($('<input type="hidden" name="_csrfToken"/>').val(csrf))	})
}

///get the data from multiple forms, either using data-itemOffset attribute, or i on loop
bf.view.form.datas = function(context){
	data = {}
	$('form',context).each(function(i){
		var key = $(this).attr('data-itemOffset')
		key = key || i
		data[i] = bf.view.form.data($(this))	})
	return data	}
///get the data from a single form
bf.view.form.data = function(form){
	var data = {}
	///anything that has a name, but is not specified as ignored
	$('[name]:not([data-noPost])',form).each(function(){
		var name = $(this).attr('name')

		if($(this).attr('type') == 'checkbox'){
			bf.obj.setValue(name,$(this).prop('checked'),data)
		}else{
			bf.obj.setValue(name,$(this).val(),data)	}

		})
	return data
}

/**
@param	options
	fieldNames: [name1,name2,...]
	modelScope: "table"
	submitText: "Update"
	values:  {field:value,...}
*/
///make a form out of the fields and values provided
bf.view.form.model = function(options){
	options = options || {}

	if(!options.fieldNames){
		if(!options.scope){
			throw 'No field names and no scope on makeModelForm call'
		}
		//pull field names from model scope
		options.fieldNames = Object.keys(bf.model[options.scope].columns)
	}

	if(!options.submitText && options.type){
		options.submitText = bf.str.capitalize(options.type)
	}

	options.values = options.values || bf.json.value || {}

	var loadData = []

	var form = $('<form action="" method="post"></form>').attr('data-scope',options.scope)
	if(options.type){
		form.attr('data-changeType',options.type)
	}

	var namePrefix = options.namePrefix || ''
	if(typeof(options.itemOffset) != 'undefined'){
		form.attr('data-itemOffset',options.itemOffset)
		namePrefix = options.itemOffset+'-'+namePrefix
	}

	for(var key in options.fieldNames){
		var field = bf.getModelField(options.fieldNames[key],options.scope)
		var value = options.values[field.name.relative]
		var lineOptions = {namePrefix:namePrefix}
		var line = bf.view.form.getLine(field,value,lineOptions)
		//handle case where field is an id pointing to another table that has a name column
		if(field.nameLink){
			line.attr('data-specialLinkFt',field.nameLink.ft)
			loadData.push({table:field.nameLink.ft,
				nullable:field.nullable,
				default: field.default,
				field:field.name.relative,
				context:form})
		}
		form.append(line)
	}

	//handle  the loading of named id fields
	if(loadData.length > 0){
		for(var i in  loadData){
			var callback = bf.view.form.namedIdField.arg(loadData[i])
			var loadOptions = {type:'readMany',sort:'+name',select:['id','name']}
			bf.loadData(loadData[i].table,loadOptions).then(callback)
		}
	}

	if(options.submitText){
		form.append($('<div class="fieldContainer submit"><div class="submit"><input type="submit" name="submit" value="'+options.submitText+'" /></div>'))
	}

	if(options.parent){
		options.parent.append(form)
		bf.run(bf.view.updateHooks)
	}

	return form
}
///swap out a text fields with a select field for named ids
bf.view.form.namedIdField = function(env,rows){
	var select = $('<select></select>')
	for(var i in rows){
		row = rows[i]
		select.append($('<option value="'+row.id+'"></option>').text(row.name))
	}
	
	if(env.default){
		select.val(env.default)
	}else if(env.nullable){
		select.append($('<option value="">Default</option>'))
	}
	
	var existing = $('[name="'+env.field+'"]',env.context)
	attributes = existing.get(0).attributes
	for(var i in attributes){
		select.attr(attributes[i].nodeName,attributes[i].nodeValue)
	}
	existing.replaceWith(select)
}

///fill the inputs in a form with the keyed data provided
bf.view.form.fillInputs = function(form,values){
	$('[name]:not([type="submit"])',form).each(function(){
		bf.view.form.fillInput($(this),values[$(this).attr('name')])	})
}

bf.view.form.fillInput = function(input,value){
	if(input.attr('type')=='checkbox'){
		if(value && value != '0'){
			input.prop('checked',true)	}
	}else{
		input.val(value || '')
	}
}

///make a line in the form for a field and value pair
bf.view.form.getLine = function(field,value,options){
	options = options || {}
	var input;
	var container = $('<div class="fieldContainer" data-forField="'+field.name.relative+'"></div>')

	field.description = field.description || field.name.display
	var label = $('<label></label>').html(field.description)

	//++ determine input type {
	if(!field.inputType){
		if(field.type == 'text'){
			if(!field.limit || field.limit > 100){
				field.inputType = 'textarea'
			}else{
				field.inputType = 'text'
			}
		}else if(field.type == 'int' || field.type == 'float'|| field.type == 'decimal'){
			if(field.limit == 1){
				field.inputType = 'checkbox'
			}else{
				field.inputType = 'number'
			}
		}else if(field.type == 'date'){
			field.inputType = 'date'
		}else if(field.type == 'datetime'){
			field.inputType = 'datetime'
		}else{
			field.inputType = 'text'
			console.log('unknown field type: '+field.type,field)
		}
	}
	//++ }
	if(field.inputType == 'checkbox'){
		input = $('<input type="checkbox" value=1 />')
	} else if(field.inputType == 'textarea'){
		input = $('<textarea></textarea>')
	} else if(field.inputType == 'div'){
		input = $('<div></div>')
	} else if(field.inputType == 'hidden'){
		container.css({display:'none'});
		input = $('<input type="'+field.inputType+'"/>')
	}else{
		input = $('<input type="'+field.inputType+'"/>')
	}

	if(field.type == 'float'|| field.type == 'decimal'){
		input.attr('step','any')
	}

	bf.view.form.fillInput(input,value)

	var inputName = field.name.relative
	input.attr('data-fieldPrefix',options.namePrefix || '')
	if(options.namePrefix){
		inputName = options.namePrefix+inputName
	}
	input.attr('name',inputName)
	input.attr('data-field',field.name.relative)
	input.attr('data-fieldAbsolute',field.name.absolute)

	if(field.limit){
		input.attr('maxlength',field.limit)	}
	if(field.name.display){
		input.attr('placeholder',field.name.display)	}

	if(options.inputAttributes){
		for(var key in options.inputAttributes){
			input.attr(key,options.inputAttributes[key])
		}
	}

	return container.append(label).append($('<div class="input"></div>').append(input))
}
///submit a single form using json post or file post
/**
@param	options	{
	success: see bf.view.form.response,
	url: url to post to,
	csrf: whether to add in a csrf token}
@note it is expected that all ajax responses will conform to having a 'status' attribute, and so ajaxFormResponse is used
*/
bf.view.form.submit = function(options){
	var form = $(this)
	bf.view.sm.uninserts({context:form})
	
	var doPost = function(){
		var data = {item:bf.view.form.data(form),type:form.attr('data-changeType')}
		success = bf.view.form.response.arg({context:form,success:options.success})
		var files = $('input[type="file"]',form)
		if(files.size()){
			$.file({success:success,data:data,url:options.url}, files.toArray())
		}else{
			$.json({success:success,data:data,url:options.url})
		}
		return false
	}
	if(options.csrf){
		bf.view.form.getCsrf(function(csrf){
			form.append($('<input type="hidden" name="_csrfToken"/>').val(csrf))
			doPost()		})
	}else{
		return doPost()
	}
}

///submit multiple forms using json post (no files)
bf.view.form.submits = function(context,success){
	var type
	bf.view.sm.uninserts({context:context})
	var data = {items:{}}
	$('form',context).each(function(){
		var form = $(this)
		type = form.attr('data-changeType')
		var i = form.attr('data-itemOffset')
		data.items[i] = bf.view.form.data(form)	})
	data.type = type
	success = bf.view.form.response.arg({context:context,success:success})
	$.json({success:success,data:data})
	return false
}
///handle the standard json response.
/**
@param	success
	if object, used in insertMessages
	if function, called with json
	if string, used wiht insertMessage as success type
*/
///used as ajax success callback
bf.view.form.response = function(options,json){
	if(json.status == 1){
		if(typeof(success) == 'object'){
			bf.view.sm.insertsWithOffsets(options.success,options.context)
		}else if(typeof(options.success) == 'function'){
			options.success(json)
		}else{
			bf.view.sm.insert({type:'success',content:options.success},{context:options.context})
		}
	}else{
		bf.view.sm.insertsWithOffsets(json.messages,options.context)
	}
}
//++ }









///for when new elements are added, these hooks should be rerun (using bf.run(bf.view.updateHooks))
bf.view.updateHooks = []


///causes  form to submit on enter (since forms without submit button may not submit on enter within an input)
bf.view.enterSubmit = function(container){
	$('input',container).keypress(function(e){
			if (e.which == 13) {
				$(this).submit()	}	})	}



///functions applied to elements
bf.view.ele = {}
///returns either attribute value if not previously parsed and sets parsed flag, or returns false
bf.view.ele.firstParse = function(ele,attribute){
	attribute = 'data-'+attribute
	if($(ele).attr(attribute+'-parsed')){
		return false
	}
	$(ele).attr(attribute+'-parsed','1')
	return $(ele).attr(attribute)
}
///add confirm delete on click, then ajax delete with potential redirect
/**
use: $('[data-deleteUrl]').each(function(e){
*/
bf.view.ele.delete = function(){
	var value = bf.view.ele.firstParse(this,'deleteUrl')
	if(value !== false){
		$(this).css({cursor:'pointer'})
		$(this).click(function(e){
			e.preventDefault();
			if(confirm('Are you sure you want to delete this?')){
				var url = value || window.location
				$.json({url:url,data:{_delete:1},success:bf.view.ajaxFormResponse.arg(function(json){
					if(json.redirect){
						window.location = json.redirect
					}
					if(json.messages){
						bf.view.sm.inserts(json.messages)
					}else{
						bf.view.sm.insert({type:'success',content:'Deleted'})
					}	})	})	}	})	}
}
///changes the text inside an element to be date of date format
/**
use: $('[data-timeFormat]').each(bf.view.ele.timeFormat)
*/
bf.view.ele.timeFormat = function(){
	var value = bf.view.ele.firstParse(this,'timeFormat')
	if(value !== false){
		var date = bf.date.strtotime($(this).text())
		$(this).text(bf.date.format(value,date))
	}
}
///changes the utc datetime inside an element to be date of date format (using browser timestamp)
/**
use: $('[type="date"],[type="datetime"]').each(bf.view.ele.utcFormat)
*/
bf.view.ele.utcFormat = function(){
	var value = bf.view.ele.firstParse(this,'utcFormat')
	if(value !== false){
		var isValue = false
		var text = $(this).text()
		if(!text){
			text = $(this).val()
			isValue = true	}
		var date = bf.date.utcToUnix(text)
		if(!value){
			if($(this).attr('type')=='date'){
				value = 'Y-m-d'
			}else{
				value = 'Y-m-d H:i:s'	}	}
		date = bf.date.format(value,date)
		if(isValue){
			$(this).val(date)
		}else{
			$(this).text(date)	}	}	}
///changes the text inside an element to be the time ago
/**
use: $('[data-timeAgo]').each(bf.view.ele.timeAgo)
*/
bf.view.ele.timeAgo = function(){
	var value = bf.view.ele.firstParse(this,'timeAgo')
	if(value !== false){
		value = JSON.parse(value ? value : '{}');
		var date = bf.date.strtotime($(this).text())
		$(this).text(bf.date.timeAgo(date,value.round,value.type))
	}
}
///sets up a redirect on click
/**
additionally, will handle 'data-newTab' attribute

use: $('[data-redirect]').each(bf.view.ele.redirect)
*/
bf.view.ele.redirect = function(){
	var value = bf.view.ele.firstParse(this,'redirect')
	if(value !== false){
		$(this).css({cursor:'pointer'}).mousedown(bf.view.clickRedirect.arg(value))	}	}

bf.view.clickRedirect = function(url,e){
	e.preventDefault()
	if(e.which == 2){
		//whould like to make this open tab in background, but doesn't work in ff
		bf.view.redirect(url,null,'tab')
	}else if(e.which == 1){
		if($(this).attr('data-newTab')){///middle click or element says new tab
			bf.view.redirect(url,null,'tab')
		}else{
			window.location = url	}	}	}











bf.date = {}
/*3rd party code*/
/**
http://kevin.vanzonneveld.net
*/
bf.date.format = function( format, timestamp ) {
	if(typeof(timestamp) == 'string'){
		timestamp = parseInt(timestamp);
	}

	format = format ? format : 'Y-m-d';
	var that = this;
	var jsdate=(
		(typeof(timestamp) == 'undefined') ? new Date() : // Not provided
		(typeof(timestamp) == 'number') ? new Date(timestamp*1000) : // UNIX timestamp
		new Date(timestamp) // Javascript Date()
	); // , tal=[]
	var pad = function (n, c){
		if ( (n = n + "").length < c ) {
			return new Array(++c - n.length).join("0") + n;
		} else {
			return n;
		}
	};
	var _dst = function (t) {
		// Calculate Daylight Saving Time (derived from gettimeofday() code)
		/**
		Get the date objects at jan 1, june 1 using local timezone+dst (as js assumed input to be such)
		Take those dates and get the time they represent at timezone UTC
		If user has local DST, those times will be at different (time of day)s
		*/
		var dst=0;
		var localJan1 = new Date(t.getFullYear(), 0, 1, 0, 0, 0, 0);  // jan 1st
		var localJune1 = new Date(t.getFullYear(), 6, 1, 0, 0, 0, 0); // june 1st
		var utcString = localJan1.toUTCString()
		var utcJan1 = new Date(utcString.slice(0, utcString.lastIndexOf(' ')-1));
		var utcString = localJune1.toUTCString()
		var utcJune1 = new Date(utcString.slice(0, utcString.lastIndexOf(' ')-1));

		var stdTimeOffset = (localJan1 - utcJan1) / (1000 * 60 * 60);
		var dstTimeOffset = (localJune1 - utcJune1) / (1000 * 60 * 60);

		alert(dstTimeOffset)

		if (stdTimeOffset === dstTimeOffset) {
			dst = 0; // daylight savings time is NOT observed
		} else {
			// positive is southern, negative is northern hemisphere
			var hemisphere = stdTimeOffset - dstTimeOffset;
			if (hemisphere >= 0) {
				stdTimeOffset = dstTimeOffset;
			}
			dst = 1; // daylight savings time is observed
		}
		return dst;
	};
	var ret = '';
	var txt_weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday",
		"Thursday", "Friday", "Saturday"];
	var txt_ordin = {1: "st", 2: "nd", 3: "rd", 21: "st", 22: "nd", 23: "rd", 31: "st"};
	var txt_months =  ["", "January", "February", "March", "April",
		"May", "June", "July", "August", "September", "October", "November",
		"December"];

	var f = {
		// Day
			d: function (){
				return pad(f.j(), 2);
			},
			D: function (){
				var t = f.l();
				return t.substr(0,3);
			},
			j: function (){
				return jsdate.getDate();
			},
			l: function (){
				return txt_weekdays[f.w()];
			},
			N: function (){
				//return f.w() + 1;
				return f.w() ? f.w() : 7;
			},
			S: function (){
				return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th';
			},
			w: function (){
				return jsdate.getDay();
			},
			z: function (){
				return (jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0;
			},

		// Week
			W: function (){

				var a = f.z(), b = 364 + f.L() - a;
				var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;

				if (b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b){
					return 1;
				}
				if (a <= 2 && nd >= 4 && a >= (6 - nd)){
					nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");
					return that.date("W", Math.round(nd2.getTime()/1000));
				}

				var w = (1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0);

				return (w ? w : 53);
			},

		// Month
			F: function (){
				return txt_months[f.n()];
			},
			m: function (){
				return pad(f.n(), 2);
			},
			M: function (){
				var t = f.F();
				return t.substr(0,3);
			},
			n: function (){
				return jsdate.getMonth() + 1;
			},
			t: function (){
				var n;
				if ( (n = jsdate.getMonth() + 1) == 2 ){
					return 28 + f.L();
				}
				if ( n & 1 && n < 8 || !(n & 1) && n > 7 ){
					return 31;
				}
				return 30;
			},

		// Year
			L: function (){
				var y = f.Y();
				return (!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0;
			},
			o: function (){
				if (f.n() === 12 && f.W() === 1) {
					return jsdate.getFullYear()+1;
				}
				if (f.n() === 1 && f.W() >= 52) {
					return jsdate.getFullYear()-1;
				}
				return jsdate.getFullYear();
			},
			Y: function (){
				return jsdate.getFullYear();
			},
			y: function (){
				return (jsdate.getFullYear() + "").slice(2);
			},

		// Time
			a: function (){
				return jsdate.getHours() > 11 ? "pm" : "am";
			},
			A: function (){
				return f.a().toUpperCase();
			},
			B: function (){
				// peter paul koch:
				var off = (jsdate.getTimezoneOffset() + 60)*60;
				var theSeconds = (jsdate.getHours() * 3600) +
								 (jsdate.getMinutes() * 60) +
								  jsdate.getSeconds() + off;
				var beat = Math.floor(theSeconds/86.4);
				if (beat > 1000) {
					beat -= 1000;
				}
				if (beat < 0) {
					beat += 1000;
				}
				if ((String(beat)).length == 1) {
					beat = "00"+beat;
				}
				if ((String(beat)).length == 2) {
					beat = "0"+beat;
				}
				return beat;
			},
			g: function (){
				return jsdate.getHours() % 12 || 12;
			},
			G: function (){
				return jsdate.getHours();
			},
			h: function (){
				return pad(f.g(), 2);
			},
			H: function (){
				return pad(jsdate.getHours(), 2);
			},
			i: function (){
				return pad(jsdate.getMinutes(), 2);
			},
			s: function (){
				return pad(jsdate.getSeconds(), 2);
			},
			u: function (){
				return pad(jsdate.getMilliseconds()*1000, 6);
			},

		// Timezone
			e: function () {
/*                var abbr='', i=0;
				if (this.php_js && this.php_js.default_timezone) {
					return this.php_js.default_timezone;
				}
				if (!tal.length) {
					tal = this.timezone_abbreviations_list();
				}
				for (abbr in tal) {
					for (i=0; i < tal[abbr].length; i++) {
						if (tal[abbr][i].offset === -jsdate.getTimezoneOffset()*60) {
							return tal[abbr][i].timezone_id;
						}
					}
				}
*/
				return 'UTC';
			},
			I: function (){
				return _dst(jsdate);
			},
			O: function (){
			   var t = pad(Math.abs(jsdate.getTimezoneOffset()/60*100), 4);
			   t = (jsdate.getTimezoneOffset() > 0) ? "-"+t : "+"+t;
			   return t;
			},
			P: function (){
				var O = f.O();
				return (O.substr(0, 3) + ":" + O.substr(3, 2));
			},
			T: function () {
/*                var abbr='', i=0;
				if (!tal.length) {
					tal = that.timezone_abbreviations_list();
				}
				if (this.php_js && this.php_js.default_timezone) {
					for (abbr in tal) {
						for (i=0; i < tal[abbr].length; i++) {
							if (tal[abbr][i].timezone_id === this.php_js.default_timezone) {
								return abbr.toUpperCase();
							}
						}
					}
				}
				for (abbr in tal) {
					for (i=0; i < tal[abbr].length; i++) {
						if (tal[abbr][i].offset === -jsdate.getTimezoneOffset()*60) {
							return abbr.toUpperCase();
						}
					}
				}
*/
				return 'UTC';
			},
			Z: function (){
			   return -jsdate.getTimezoneOffset()*60;
			},

		// Full Date/Time
			c: function (){
				return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P();
			},
			r: function (){
				return f.D()+', '+f.d()+' '+f.M()+' '+f.Y()+' '+f.H()+':'+f.i()+':'+f.s()+' '+f.O();
			},
			U: function (){
				return Math.round(jsdate.getTime()/1000);
			}
	};

	return format.replace(/[\\]?([a-zA-Z])/g, function (t, s){
		if ( t!=s ){
			// escaped
			ret = s;
		} else if (f[s]){
			// a date function exists
			ret = f[s]();
		} else {
			// nothing special
			ret = s;
		}
		return ret;
	});
}
///return the unix timestamp from a date like string (similar to php's strtotime
/**
@note does not handle timezones or timezone offsets
@note assumes date is timezoned as browser's timezone
*/
bf.date.strtotime = function (str, now) {
	///+ handle cases where input is already a unix timestamp {
	var parsedInt = parseInt(str)
	if(parsedInt == str){
		if(parsedInt.toString().length > 12){//passed in as milliseconds
			return parseInt(parsedInt/1000)
		}
		return parsedInt
	}
	///+ }
	//force to string
	str = str+'';

	// Convert string representation of date and time to a timestamp
	//
	// version: 908.2210
	// discuss at: http://phpjs.org/functions/strtotime
	// +   original by: Caio Ariede (http://caioariede.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +      input by: David
	// +   improved by: Caio Ariede (http://caioariede.com)
	// +   improved by: Brett Zamir (http://brett-zamir.me)
	// +   bugfixed by: Wagner B. Soares
	// +   bugfixed by: Artur Tchernychev
	// %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
	// *     example 1: strtotime('+1 day', 1129633200);
	// *     returns 1: 1129719600
	// *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
	// *     returns 2: 1130425202
	// *     example 3: strtotime('last month', 1129633200);
	// *     returns 3: 1127041200
	// *     example 4: strtotime('2009-05-04 08:30:00');
	// *     returns 4: 1241418600

	var i, match, s, strTmp = '', parse = '';

	strTmp = str;
	strTmp = strTmp.replace(/\s{2,}|^\s|\s$/g, ' '); // unecessary spaces
	strTmp = strTmp.replace(/[\t\r\n]/g, ''); // unecessary chars

	if (strTmp == 'now') {
		return (new Date()).getTime()/1000; // Return seconds, not milli-seconds
	} else if (!isNaN(parse = Date.parse(strTmp))) {
		return (parse/1000);
	} else if (now) {
		now = new Date(now*1000); // Accept PHP-style seconds
	} else {
		now = new Date();
	}

	strTmp = strTmp.toLowerCase();

	var __is =
	{
		day:
		{
			'sun': 0,
			'mon': 1,
			'tue': 2,
			'wed': 3,
			'thu': 4,
			'fri': 5,
			'sat': 6
		},
		mon:
		{
			'jan': 0,
			'feb': 1,
			'mar': 2,
			'apr': 3,
			'may': 4,
			'jun': 5,
			'jul': 6,
			'aug': 7,
			'sep': 8,
			'oct': 9,
			'nov': 10,
			'dec': 11
		}
	};

	var process = function (m) {
		var ago = (m[2] && m[2] == 'ago');
		var num = (num = m[0] == 'last' ? -1 : 1) * (ago ? -1 : 1);

		switch (m[0]) {
			case 'last':
			case 'next':
				switch (m[1].substring(0, 3)) {
					case 'yea':
						now.setFullYear(now.getFullYear() + num);
						break;
					case 'mon':
						now.setMonth(now.getMonth() + num);
						break;
					case 'wee':
						now.setDate(now.getDate() + (num * 7));
						break;
					case 'day':
						now.setDate(now.getDate() + num);
						break;
					case 'hou':
						now.setHours(now.getHours() + num);
						break;
					case 'min':
						now.setMinutes(now.getMinutes() + num);
						break;
					case 'sec':
						now.setSeconds(now.getSeconds() + num);
						break;
					default:
						var day;
						if (typeof (day = __is.day[m[1].substring(0, 3)]) != 'undefined') {
							var diff = day - now.getDay();
							if (diff == 0) {
								diff = 7 * num;
							} else if (diff > 0) {
								if (m[0] == 'last') {diff -= 7;}
							} else {
								if (m[0] == 'next') {diff += 7;}
							}
							now.setDate(now.getDate() + diff);
						}
				}
				break;

			default:
				if (/\d+/.test(m[0])) {
					num *= parseInt(m[0], 10);

					switch (m[1].substring(0, 3)) {
						case 'yea':
							now.setFullYear(now.getFullYear() + num);
							break;
						case 'mon':
							now.setMonth(now.getMonth() + num);
							break;
						case 'wee':
							now.setDate(now.getDate() + (num * 7));
							break;
						case 'day':
							now.setDate(now.getDate() + num);
							break;
						case 'hou':
							now.setHours(now.getHours() + num);
							break;
						case 'min':
							now.setMinutes(now.getMinutes() + num);
							break;
						case 'sec':
							now.setSeconds(now.getSeconds() + num);
							break;
					}
				} else {
					return false;
				}
				break;
		}
		return true;
	};

	match = strTmp.match(/^(\d{2,4}-\d{2}-\d{2})(?:\s(\d{1,2}:\d{2}(:\d{2})?)?(?:\.(\d+))?)?$/);
	if (match != null) {
		if (!match[2]) {
			match[2] = '00:00:00';
		} else if (!match[3]) {
			match[2] += ':00';
		}

		s = match[1].split(/-/g);

		for (i in __is.mon) {
			if (__is.mon[i] == s[1] - 1) {
				s[1] = i;
			}
		}
		s[0] = parseInt(s[0], 10);

		s[0] = (s[0] >= 0 && s[0] <= 69) ? '20'+(s[0] < 10 ? '0'+s[0] : s[0]+'') : (s[0] >= 70 && s[0] <= 99) ? '19'+s[0] : s[0]+'';
		return parseInt(this.strtotime(s[2] + ' ' + s[1] + ' ' + s[0] + ' ' + match[2])+(match[4] ? match[4]/1000 : ''), 10);
	}

	var regex = '([+-]?\\d+\\s'+
		'(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?'+
		'|sun\.?|sunday|mon\.?|monday|tue\.?|tuesday|wed\.?|wednesday'+
		'|thu\.?|thursday|fri\.?|friday|sat\.?|saturday)'+
		'|(last|next)\\s'+
		'(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?'+
		'|sun\.?|sunday|mon\.?|monday|tue\.?|tuesday|wed\.?|wednesday'+
		'|thu\.?|thursday|fri\.?|friday|sat\.?|saturday))'+
		'(\\sago)?';

	match = strTmp.match(new RegExp(regex, 'g'));
	if (match == null) {
		return false;
	}

	for (i = 0; i < match.length; i++) {
		if (!process(match[i].split(' '))) {
			return false;
		}
	}

	return (now.getTime()/1000);
}


bf.date.dateTime = function(time){
	return bf.date.format('Y-m-d H:i:s',time);
}
bf.date.unix = function(milli){
	var date = new Date();
	if(milli){
		return date.getTime();
	}else{
		return Math.round(date.getTime()/1000);	}	}

bf.date.formatClock = function(format,clock){
	if(!bf.date.zeroHour){
		bf.date.zeroHour = bf.date.strtotime('2000-01-01 00:00:00');
	}
	return bf.date.format(format,bf.date.zeroHour + parseInt(clock));	}
///converts a utc datetime to unix timestamp
bf.date.utcToUnix = function(datestring){
	var match = datestring.match(/^([0-9]{4})\-([0-9]{1,2})\-([0-9]{1,2})( ([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?/i);
	return Date.UTC(match[1],parseInt(match[2])-1,parseInt(match[3]),parseInt(match[5]),parseInt(match[6]),parseInt(match[8]))/1000	}
//make better format handling
bf.date.daySeconds = function(clock){
	var match = clock.match(/^([0-9]{1,2}):([0-9]{1,2}) ?([a-z]{2})/i);
	if(match){
		match[1] = bf.toInt(match[1]);
		match[2] = bf.toInt(match[2]);
		if(match[3].toLowerCase() == 'pm'){
			if(match[1] != 12){
				match[1] += 12;	}
		}else{
			if(match[1] == 12){
				match[1] = 0;	}	}
		return match[1] * 3600 + match[2] * 60;	}	}
bf.date.times = {
	year:31536000,
	month:2592000,
	day:86400,
	hour:3600,
	minute:60	}
bf.date.timeAgo = function(unix,round,type){
	var current = bf.date.unix();
	var diff = current - unix;
	var amount;

	if(type && this.times[type]){
		amount = diff/this.times[type]
	}else{
		if(diff >= this.times.year){
			amount = diff/this.times.year
			type = 'year'
		}else if(diff >= this.times.month){
			amount = diff/this.times.month
			type = 'month'
		}else if(diff >= this.times.day){
			amount = diff/this.times.day
			type = 'day'
		}else if(diff >= this.times.hour){
			amount = diff/this.times.hour
			type = 'hour'
		}else if(diff >= this.times.minute){
			amount = diff/this.times.minute
			type = 'minute'
		}else{
			amount = diff
			type = 'second'
		}
	}
	amount = bf.math.round(amount,round)
	type = amount == 1 ? type : type+'s'
	return amount+' '+type	}













































$(function(){
	if(bf.json){
//+	handle system messages{
		//++ handle url based messages {
		var urlMessages = bf.url.requestVar('_systemMessages')
		if(urlMessages){
			urlMessages = JSON.parse(urlMessages)
			if(bf.json.messages){
				for(var i in urlMessages){
					bf.json.messages.push(urlMessages[i])
				}
			}else{
				bf.json.messages = urlMessages
			}
			//clear the url messages from the url
			window.history.pushState({},"", bf.url.queryFilter('_systemMessages'));
		}
		//++ }
		if(bf.json.messages){
			bf.view.sm.inserts(bf.json.messages)
		}
//+	}
	}
	//form dependent dynamics
	if($('form').size()){
//+ add message containers on certain conditions {
		var addMessageEle = $('[data-addMessageContainers]')
		if(addMessageEle.size() > 0){
			$('input, select, textarea',addMessageEle).each(function(){
					$(this).after('<div class="_messageContainer" data-field="'+$(this).attr('name')+'"></div>')
				})
		}
//+ }
//+ handle dependent data fields {
		$('[data-dependee]').each(function(){
				var dependeeField = $(this).attr('data-dependee')
				var field = $(this).attr('name')
				$('[name="'+dependeeField+'"]').change(function(){bf.view.form.getOptions(field)}).change()
			})
//+ }
	}

//+	handle paging and sorting{
//+		sorting{
	if($('.sortContainer').size()){
		bf.sorts = []
		var sort = bf.url.requestVar('_sort');
		if(sort){//use URL if sort passed, otherwise use html sort data
			$('.sortContainer:not(.inlineSort)').attr('data-sort',sort);
		}else{
			sort = $('.sortContainer:not(.inlineSort)').attr('data-sort');
		}
		if(sort){
			bf.sorts = sort.split(',')
			bf.view.ps.headerArrows();//byproduct is to standardize the sorts
		}
		//add click event to sortable columns
		$('.sortContainer:not(.inlineSort) *[data-field]').click(function(e){
			var field = $(this).attr('data-field')
			//if shift clicked, just append sort
			if(e.shiftKey){
				bf.view.ps.appendSort(field)
			}else{
				bf.view.ps.changeSort(field)
			}
			bf.view.ps.reload()
		})
	}
//+		}

//+	}

//+	tool tips {
	///add [?] to open tool tips from the data-help attribute value
	$('*[data-help]').each(function(){
		var relativeName, relativeElement,
			tooltippedElement = $(this),
			tag = this.nodeName.toLowerCase()
		if(tag == 'input' || tag == 'select' || tag == 'textarea'){
			var field = tooltippedElement.attr('name')
			relativeElement = $('*[data-fieldDisplay="'+field+'"]').eq(0)
			relativeName = 'bottom'
		}else{
			relativeElement = tooltippedElement
			if(tag == 'span'){
				relativeName = 'after'
			}else{
				relativeName = 'bottom'
			}

		}
		var marker = $('<span class="tooltipMarker">[?]</span>')
		marker.attr('data-tooltip',tooltippedElement.attr('data-help'))

		if(relativeName == 'bottom'){
			marker.appendTo(relativeElement)
		}else{
			relativeElement.after(marker)
		}
	})
	var tooltipMakerCount = 0
	///to have both tooltips w/ and w/o [?], this logic is separated
	///tool tip value can be text, html, or formed like: "url:", where in the remain part is a url to go to
	$('*[data-tooltip]').each(function(){
		var tooltipMaker = $(this)
		tooltipMakerCount = tooltipMakerCount + 1
		var markerId = tooltipMaker.attr('id')
		if(!markerId){
			markerId = 'tooltipMarker-'+tooltipMakerCount
			tooltipMaker.attr('id',markerId)
		}
		//either a new tab tooltip or an onpage tooltip
		var toolTipData = tooltipMaker.attr('data-tooltip')
		if(toolTipData.substr(0,4) == 'url:'){
			var url = toolTipData.substr(4)
			tooltipMaker.click(function(){
				bf.view.redirect(url,null,'tab')
			})

		}else{
			var tooltip = $('<div/>',{html:toolTipData,class:'tooltip',id:'tooltip-'+markerId}).prependTo('body')
			bf.view.closeButton(tooltip,true)

			tooltipMaker.click(function(e){
				var tooltipMaker = $(this)
				var tooltip = $('#tooltip-'+tooltipMaker.attr('id'))
				tooltip.css(tooltipMaker.offset())
				tooltip.fadeIn({duration:'slow'})
				tooltip.click(function(e){
					e.stopPropagation()//don't prevent highlighting, just stop propogation
				})
				//e.stopImmediatePropagation()
				e.stopPropagation()
			})
		}
	})
	$('body').click(function(){
		$('.tooltip').hide()
	})
//+	}
});