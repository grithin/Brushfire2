<?
if(!\control\Route::$unparsedTokens){
	header("Cache-\Control: max-age=2592000"); //30days (60sec * 60min * 24hours * 30days)
	header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
	header_remove('Pragma');

	$model = \Control::$model->getPublic($table);
	foreach($model as &$scope){
		$scope['loaded'] = true;
	}
	if(\Control::$ajax){
		View::endJson($model);
	}else{
		Debug::quit(Control::$model->__toArray());
	}
}