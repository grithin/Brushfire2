<?
namespace cache;
class None extends \Cache{
	public $_success = true;
	function __construct($connectionInfo=null,$options=null){}
	function check(){
		return true;///<right as rain
	}
	function touch(){
		return true;
	}
	function delete(){
		return true;
	}
	function get(){
		return false;
	}
	function set(){
		return true;
	}
}