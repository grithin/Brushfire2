<?
if(Control::$in['offset']){
	\User::setTimezone(timezone_name_from_abbr("", -60*Control::$in['offset'], false));
}
View::endStdJson();