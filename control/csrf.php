<?
$_SESSION['csrfToken'] = Tool::randomString(30);
View::$json['value'] = $_SESSION['csrfToken'];
View::endStdJson();