<?= View::getTemplate('/brushfire/crud.template')?>
<script>
	$(function(){
		var crud = new bf.crud()
		crud.modelPromise.then(crud.updateType.bind(crud)).done()
	})
</script>