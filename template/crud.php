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

<script>
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
			bf.loadData(table,loadOptions).then(function(item){
				bf.view.form.fillInputs(form,item)
				$('[type="date"],[type="datetime"]').each(bf.view.ele.utcFormat)	})
			
			return
		}else if(type == 'readMany'){
			var loadOptions = {type:'readMany',where:where,per:20,page:0}
			bf.loadData(table,loadOptions).then(function(items){
				content.append($('<pre></pre>').text(JSON.stringify(items,null,4)))	})
		}else if(type == 'readOne'){
			var loadOptions = {type:'readOne',where:where}
			bf.loadData(table,loadOptions).then(function(item){
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