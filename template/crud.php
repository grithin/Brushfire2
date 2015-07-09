<style>
	#crudHeader a{
		padding:10px;
		border: 1px solid black;
	}
	#crudHeader{
		margin-bottom:20px;
	}
</style>
<div id="crudHeader" class="row">
	
</div>
<div>
	{"out?&lt;&gt;":null}
</div>
<div id="where" class="row">
	Where <input type="text" name="where"/>
	Type 
	<select name="type">
		<option value="readOne">readOne</option>
		<option value="readMany">readMany</option>
		<option value="updateOne">updateOne</option>
		<option value="createOne">createOne</option>
		<option value="deleteOne">deleteOne</option>
	</select>
	<span class="button" id="whereButton">Search</span>
</div>
<div id="typeContent" class="row">
	
</div>
<div id="pagingControl"></div>
<style>
	.items .item{
		overflow:hidden;
		border:1px solid black;
		margin-bottom:3px;
	}
	.items .item .field{
		float:left;
		border:1px solid rgba(0,0,0,0.3);
	}
	.items .item .field .key{
		display:inline-block;
		padding-left:5px;
		padding-right:5px;
		color:rgb(0,0,100);
	}
	.items .item .field .value{
		display:inline-block;
		padding-left:5px;
		padding-right:5px;
		color:rgb(0,100,0);
	}
	
	
</style>

<script>
	function multiItemView(json, fieldHandler,keyHandlers){
		if(keyHandlers){
			var itemHandler = function(item){
				itemEle = $('<div class="item"></div>')
				for(var key in item){
					value = item[key]
					if(keyHandlers[key]){
						itemEle.append(keyHandlers[key](value, key, fieldHandler))
					}else{
						itemEle.append(fieldHandler(value, key))
					}
				}
				return itemEle;
			}
		}else{
			var itemHandler = function(item){
				itemEle = $('<div class="item"></div>')
				for(var key in item){
					value = item[key]
					itemEle.append(fieldHandler(value, key))
				}
				return itemEle;
			}
		}
		
		var itemsEle, itemEle;
		itemsEle = $('<div class="items"></div>')
		for(var i in json){
			itemsEle.append(itemHandler(json[i]))
		}
		return itemsEle
	}
	shortFieldHandler = function(length){
		length = length || 100
		return function(value, key, format){
			if(value && value.length > length){
				value = value.substr(0,length)+'...'
			}
			return fieldHandler(value, key, format)
		}
	}
	fieldHandler = function(value, key, format){
		format = format || 'text'
		var ele = $('<div class="field"></div>')
		ele.append($('<div class="key"></div>').text(key))
		if(format == 'text'){
			ele.append($('<div class="value"></div>').text(value))
		}else{
			ele.append($('<div class="value"></div>').html(value))
		}
		return ele
	}

	
	
	var table = bf.url.requestVar('table')
	table = table || 'any'
	var type = bf.url.requestVar('type')
	type = type || 'readMany'
	$('#where [name="type"]').val(type)
	
	var content = $('#typeContent')
	
	function updateType(newType){
		if(newType){
			$('#where [name="type"]').val(newType)
		}
		var type = $('#where [name="type"]').val()
		content.empty()
		if(type == 'createOne'){
			$('#where').hide()
			var form = bf.view.form.model({scope:table,type:'createOne',submitText:'Create'})
			var submitOptions = {url:'/model/'+table,
				success:function(json){
					bf.view.sm.insert({type:'success',content:'Created with id '+json.value})}}
			form.submit(bf.view.form.submit.arg(submitOptions))
			content.append(form)
			return
		}
		
		$('#where').show()
		var where = $('#where [name="where"]').val()
		if(!where){
			where = {}
		}else{
			where = JSON.parse(where)
		}
		
		if(type == 'deleteOne'){
			var loadOptions = {type:'deleteOne',where:where}
			bf.loadData(table,loadOptions).then(function(item){
				$('#where [name="where"]').val('')
				updateType('readMany')	})
		}else if(type == 'updateOne'){
			var form = bf.view.form.model({scope:table,type:'updateOne',submitText:'Update'})
			var submitOptions = {url:'/model/'+table,success:'Updated'}
			form.submit(bf.view.form.submit.arg(submitOptions))
			content.append(form)
			
			var loadOptions = {type:'readOne',where:where}
			bf.loadData(table,loadOptions,{noCache:true}).then(function(item){
				bf.view.form.fillInputs(form,item)
				$('[type="datetime"]').each(bf.view.ele.utcFormat)	})
			
			return
		}else if(type == 'readMany'){
			var loadOptions = {type:'readMany',where:where,per:20,page:0}
			bf.loadData(table,loadOptions,{noCache:true}).then(function(json){
				keyHandlers = {id:function(value,key,callback){
						value = '<span class="ln" data-id="'+value+'">'+value+'</span>'
						return callback(value,key,'html')
					},name:function(value,key,callback){
						var ele = callback(value,key,'html')
						ele.css({display:'block',float:'none'})
						return ele	}	}
				content.append(multiItemView(json.rows,shortFieldHandler(),keyHandlers))
				content.delegate('[data-id]','click',function(){
					var id = $(this).attr('data-id')
					$('#where [name="where"]').val('{"id":'+id+'}')
					updateType('updateOne')	})
				
				json.info.url = '/model/'+table
				json.info.handlers = {dataHandler: function(rows){ 
					content.empty().append(multiItemView(rows,shortFieldHandler(),keyHandlers)); }}
				bf.view.ps.start(json.info)
				
					}).catch(bf.logError)
		}else if(type == 'readOne'){
			var loadOptions = {type:'readOne',where:where}
			bf.loadData(table,loadOptions,{noCache:true}).then(function(item){
				content.append($('<pre></pre>').text(JSON.stringify(item,null,1)))	})
		}
	}
	$('#whereButton').click(function(){
		updateType()	})
	
	bf.modelPromise(table).then(function(value){
		updateType()
		for(var key in bf.model){
			if(key.charAt(0) != '_'){
				$('#crudHeader').append($('<a href="?table='+key+'">'+key+'</a>'))	}
		}
			}).catch(bf.logError)
</script>