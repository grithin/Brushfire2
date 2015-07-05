<?
/**

Ban system wherein points accumulate for some ban type and upon reaching a limit, the ban is set. 
Additionally, will add a point to the ban named "name'+'" for the ban being added if such a ban type exists.

Ban::init();
Ban::points('test',1);


Expects tables:
CREATE TABLE `ban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identity` varchar(200) DEFAULT NULL,
  `type_id_` int(11) DEFAULT NULL,
  `expire` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `identity` (`identity`),
  KEY `type_id` (`type_id_`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `ban_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `reason` text,
  `threshold_time` int(11) DEFAULT NULL,
  `limit` int(11) DEFAULT NULL,
  `ban_duration` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

examples
+----+----------------+---------------------------+-----------+-------+---------------+
| id | name           | reason                    | threshold_time | limit | ban_duration  |
+----+----------------+---------------------------+-----------+-------+---------------+
|  9 | page load      | Loading pages too quickly |             30 |    12 | +60 seconds   |
| 10 | page load+     | Loading pages too quickly |           3000 |     3 | +600 seconds  |
| 11 | page load++    | Loading pages too quickly |          30000 |     3 | +6000 seconds |
| 12 | page load+++   | Loading pages too quickly |         300000 |     3 | NULL          |
*/
class Ban{
	static $db;
	static $identity;
	static $banTypes;
	static function init($identity=null,$db=null){
		self::$identity = $identity ? $identity : \Http::ip();
		self::$db = $db ? $db : Db::primary();
		
		$bans = unserialize(Cache::get('bans-'.self::$identity));
		if($bans){
			self::banned($bans);
		}
		
		if(mt_rand(1,200) === 1){
			self::maintenance();
		}
		
		self::$banTypes = unserialize(Cache::get('table_ban_type'));
		if(!self::$banTypes){
			self::$banTypes = self::$db->columnKey('name','ban_type','1=1');
		}
	}
	///clears cached bans, but not db bans
	static function clearBans($identity=null){
		$identity = $identity ? $identity : $_SERVER['REMOTE_ADDR'];
		Cache::delete('bans-'.$identity);
		Cache::delete('preban-'.$identity);
	}
	
	///load bans from db into cache
	static function maintenance(){
		$currentBans = self::$db->rows('select identity, bt.reason, b.expire
			from ban b
				left join ban_type bt on b.type_id_ = bt.id
			where (b.expire >= '.self::$db->quote(new Time).' or expire is null)');
		$identityBans = Arrays::compileSubsOnKey($currentBans,'identity');
		foreach($identityBans as $identity => $bans){
			$putBans = [];
			$lastExpires = 0;
			foreach($bans as $ban){
				if($ban['expire'] > $lastExpires){
					$lastExpires = $ban['expire'];
				}
				$putBans[] = Arrays::extract(['reason','expire'],$ban);
			}
			Cache::set('bans-'.$identity,serialize($putBans),(new Time($lastExpires)) - time());
		}
	}
	///presents ban message, or clears ban if all expired
	static function banned($bans){
		foreach($bans as $k=>$ban){
			if($ban['expire'] == -1 || $ban['expire'] > (new Time)->unix){
				if($ban['expire'] == -1){
					$until = 'Indefinite';
				}else{
					$time = new Time($ban['expire']);
					$until = 'UTC '.$time->datetime().' ('.(time() - $time->unix()).'s)';
				}
				die('Banned.  Reason: '.$ban['reason'].'; Until: '.$until);
			}
		}
		//all bans expired.  So, clear ban cache.
		Cache::delete('bans-'.self::$identity);
	}
	///get preban info for identity
	static function getPreban(){
		return (array)unserialize(Cache::get('preban-'.self::$identity));
	}
	///set preban info for identity
	static function setPreban($data){
		Cache::set('preban-'.self::$identity,serialize($data),0);
	}
	
	///Adds points to a ban type, and, upon limit, bans
	/**
	Upon reaching a limit, ban is applied, and, name'+', if present, is incremented.
	Ban time, if present, is passed to new Time().  Otherwise, permanent.
	
	@param	name	ban type name
	@param	points	points to add to current
	@param	exitOnBan	used to prevent a "'+'name" to exit before the "name" ban is added
	*/
	static function points($name,$points=1,$exitOnBan=true){
		$banType = self::$banTypes[$name];
		if(!$banType){
			return;
		}
		$preban = self::getPreban();
		//append the ban type with the new instance
		$preban[$name][] = array(time(),$points);
		
		//+	check if over limit {
		$expired = time() - $banType['threshold_time'];
		foreach($preban[$name] as $k=>$instance){
			if($instance[0] < $expired){
				unset($preban[$name][$k]);
			}else{
				$total += $instance[1];
			}
		}
		//+	}
		if($total >= $banType['limit']){
			unset($preban[$name]);//ban is  being set, no reason to keep the preban  info (and might cause wrongful reban)
			self::setPreban($preban);
			self::points($name.'+',1,false);
			self::setBan($name,$exitOnBan);
		}
		self::setPreban($preban);
	}
	///add a ban
	/**
	@param	name	name of ban_type
	@param	exit	whether to exit after adding ban.  Sometimes multiple bans are added at once (and you don't want to exit on the  first)
	*/
	static function setBan($name,$exit=true){
		$banType = self::$banTypes[$name];
		self::$db->insert('ban',array(
				'identity' => self::$identity,
				'type_id_' => $banType['id'],
				'expire' => ($banType['ban_duration'] ? new Time($banType['ban_duration']) : null),
				'created' => new Time
			));
		$bans = unserialize(Cache::get('bans-'.self::$identity));
		$bans[] = array('reason' =>$banType['reason'],'expire'=>(new Time($banType['ban_duration']))->unix);
		Cache::set('bans-'.self::$identity,serialize($bans));
		if($exit){
			self::banned($bans);
		}
	}
}