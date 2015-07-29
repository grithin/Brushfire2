<?echo '<?';?>xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?
if(!View::$common['headTitle']){
	View::$common['headTitle'] = htmlspecialchars(strip_tags(View::$common['title']));
}
View::$common['headTitle'] = View::$common['headTitle'] ?  View::$common['headTitle'] : $_ENV['projectName'];
$bodyClasses = implode(' ',(array)View::$common['bodyClasses']);
?>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

		<title><?=View::$common['headTitle']?></title>

		<meta name="keywords" content="<?=View::$common['keywords']?>" />
		<meta name="author" content="<?=View::$common['author']?>" />
		<meta name="description" content="<?=View::$common['description']?>" />
		<meta name="viewport" content="width=device-width" />
		<link rel="stylesheet" href="<?='/'.$_ENV['urlSystemFileToken'].'/css/bf.css'?>" />
		<?=View::getCss(array('modDate'=>View::$common['resourceModDate']))?>

		<script type="text/javascript">bf={}; bf.json = <?=View::getStdJson()?>;</script>

		<? View::frameworkJs(); ?>
		<?=View::getTopJs(array('modDate'=>View::$common['resourceModDate']))?>
	</head>

	<body id="<?=implode('-',\control\Route::$tokens)?>-body" class="<?=$bodyClasses?>" data-context="global">
		<?	$header = View::getSection('header');?>
		<?	if($header){?>
		<div id="head"><?=$header?></div>
		<?	}?>
		<div id="main">
			<div class="_messageContainer" id="defaultMessageContainer"></div>
			<?=$input?>
		</div>
		<?=View::getBottomJs(array('modDate'=>View::$common['resourceModDate']))?>
	</body>
</html>
