<?
if(!$_SERVER['argv'][1] || $_SERVER['argv'][1] == 'help'){
	echo "\nUsage:\n\tphp install.php pathToProjectFolder\n\n";
}else{
	$base = $_SERVER['argv'][1];
	if(!is_dir($base)){
		mkdir($base,0777,true);
	}
	$base = realpath($base);
	
	$systemFolder = realpath(dirname(__FILE__)).'/';
	$folder = $base.'/';
	
	mkdir($folder.'control',0777,true);
	mkdir($folder.'template',0777,true);
	mkdir($folder.'template/common',0777,true);
	mkdir($folder.'apt',0777,true);
	mkdir($folder.'public',0777,true);
	mkdir($folder.'public/img',0777,true);
	mkdir($folder.'public/css',0777,true);
	mkdir($folder.'public/js',0777,true);
	mkdir($folder.'tool',0777,true);
	mkdir($folder.'resource',0777,true);
	
	copy('project.install.template.php',$folder.'install.php');
	copy('composer.json',$folder.'composer.json');
	copy('project.gitignore.template',$folder.'.gitignore');
	copy('public/index.php',$folder.'public/index.php');
	copy('public/.htaccess',$folder.'public/.htaccess');
	copy('public/favicon.ico',$folder.'public/favicon.ico');
	copy('project.routes.template.php',$folder.'control/routes.php');
	copy('template/header.php',$folder.'template/common/header.php');
	
	$configFile = file_get_contents('project.config.template.php');
	$configFile = str_replace('[[systemFolder]]',$systemFolder,$configFile);
	file_put_contents($folder.'apt/config.php',$configFile);
	
	passthru('cd '.$folder.' && php install.php');
}
