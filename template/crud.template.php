<? View::addTags('js','/brushfirePublic/js/items.js'); ?>
<? View::addTags('css','/brushfirePublic/css/items.css'); ?>
<? View::addTags('js','/brushfirePublic/js/crud.js'); ?>

<div id="crudHeader" class="row"></div>

<div id="where" class="row">
	Where <textarea name="where" rows="5"/></textarea>
	Type
	<select name="type">
		<option value="readOne">readOne</option>
		<option value="readMany">readMany</option>
		<option value="updateOne">updateOne</option>
		<option value="createOne">createOne</option>
		<option value="deleteOne">deleteOne</option>
	</select>
	<span class="button" id="whereButton">Refresh</span>
</div>

<div id="typeContent" class="row"></div>

<div id="pagingControl"></div>