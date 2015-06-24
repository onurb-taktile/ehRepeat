<?php

# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of ehRepeat, a plugin for Dotclear 2.
#
# Copyright(c) 2015 Onurb Teva
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------


if (!defined('DC_RC_PATH')) {
	return;
}

function join2($a, $delimiter, $delimiter2) {
	return join($delimiter, array_slice($a, 0, count($a) > 1 ? -1 : 1)) . (count($a) > 1 ? $delimiter2 . $a[count($a) - 1] : '');
}

function join3($a, $d1, $d2, $options = array()) {
	$gprefix1 = isset($options['gprefix']) ? $options['gprefix'] : (isset($options['gprefix1']) ? $options['gprefix1'] : '');
	$gprefix2 = isset($options['gprefix']) ? $options['gprefix'] : (isset($options['gprefix2']) ? $options['gprefix2'] : '');
	$prefix = isset($options['prefix']) ? $options['prefix'] : '';

	return $gprefix1 .
			join($prefix . $d1, array_slice($a, 0, count($a) > 1 ? -1 : 1)) .
			(count($a) > 1 ? $prefix . $d2 . $gprefix2 . $a[count($a) - 1] : '');
}

function mb_ucfirst($str) {
    $fc = mb_strtoupper(mb_substr($str, 0, 1));
    return $fc.mb_substr($str, 1);
}

class xFrequency {

	protected $ranges;
	protected $minutes;
	protected $hours;
	protected $days;
	protected $months;
	protected $weekdays;
	protected $weeksofmonth;
	private $update_lock = FALSE;
	protected $string;
	protected $sundayfirst;

	const XFREQ_RANGE = '{"minutes":[0,59],
					 	"hours":[0,23],
					 	"days":[1,31],
					 	"months":[1,12],
					 	"weekdays":[0,6],
					 	"weeksofmonth":[1,5]}';

	function __construct($s,$sundayfirst=true) {
		try {
			$this->sundayfirst=$sundayfirst;
			$this->ranges = json_decode(self::XFREQ_RANGE);
			$this->parseFreq($s);
		} catch (Exception $e) {
			throw(new Exception(sprintf(__("Can't construct a xFrequency from string %s"), $s), 1, $e));
		}
		$this->string = $s;
	}

	function __set($name, $value) {
		if (preg_match('/^(minutes|hours|days|months|weekdays|weeksofmonth)$/', $name) == 1) {
			$res = array();
			if (!is_array($value))
				$value = array($value);
			foreach ($value as $v) {
				if ($v == '*') {
					if (preg_match('/days|week/', $name) == 1)
						$res[] = "*";
					else
						$res = range($this->ranges->{$name}[0], $this->ranges->{$name}[1]);
					break;
				}else {
					$res[] = (integer)$v;
				}
			}
			$this->{$name} = array_unique($res, SORT_NUMERIC);
			/*Reorder week days according to the sunday first preference*/
			if(!$this->sundayfirst && $name=="weekdays" && $this->weekdays[0]==0){
				array_push ($this->weekdays,  array_shift ($this->weekdays));
			}
			if (!$this->update_lock)
				$this->updateString();
		} else
			throw(new Exception(sprintf(__("Don't know how to set value %s, field unknown"), $name)));
	}

	function __get($name) {
		if (preg_match('/^(minutes|hours|days|months|weekdays|weeksofmonth)$/', $name) == 1) {
			if (count($this->{$name}) == ($this->ranges->{$name}[1] - $this->ranges->{$name}[0]) + 1) {
				//we have a full range
				return "*";
			} else {
				return implode(',', $this->{$name});
			}
		} else
			throw(new Exception(sprintf(__("Don't know how to get %s, field unknown"), $name)));
	}

	protected function parseFreq($freq) {
		$matches = array();
		$reg = '/^(?P<minutes>\*|[0-5]?[0-9](?:,[0-5]?[0-9])*) (?P<hours>\*|[0-2]?[0-9](?:,[0-2]?[0-9])*) (?P<days>\*|[0-3]?[0-9](?:,[0-3]?[0-9])*) (?P<months>\*|[0-1]?[0-9](?:,1?[0-9])*) (?P<weekdays>\*|[0-6](?:,[0-6])*) (?P<weeksofmonth>\*|[1-5](?:,[1-5])*)\s*$/';
		if (preg_match($reg, $freq, $matches) != 1)
			throw new Exception('xFrequency::parseFreq :' . sprintf(__('The frequency string %s is incorrect'), $freq));
		foreach ($matches as $k => $v)
			$matches[$k] = explode(',', $v);
		foreach ($matches['hours'] as $k => $v)
			$matches['hours'][$k] = sprintf("%'.02d", $v);
		foreach ($matches['minutes'] as $k => $v)
			$matches['minutes'][$k] = sprintf("%'.02d", $v);
		$this->update_lock = TRUE;
		$this->__set('minutes', $matches['minutes']);
		$this->__set('hours', $matches['hours']);
		$this->__set('days', $matches['days']);
		$this->__set('months', $matches['months']);
		$this->__set('weekdays', $matches['weekdays']);
		$this->__set('weeksofmonth', $matches['weeksofmonth']);
		$this->update_lock = FALSE;
	}

	protected function updateString() {
		$this->string = $this->__get("minutes") . " " . $this->__get("hours") . " " . $this->__get("days") . " " . $this->__get("months") . " " . $this->__get("weekdays") . " " . $this->__get("weeksofmonth");
	}

	private function isDayOk($day, $month, $year) {
		$w = 1 + floor(($day - 1) / 7);
		$wd = date("w", mktime(0, 0, 0, $month, $day, $year));
		$a = ($this->days[0] == "*");
		$b = ($this->weekdays[0] == "*");
		$c = ($this->weeksofmonth[0] == "*");
		$d = in_array($wd, $this->weekdays);
		$e = in_array($w, $this->weeksofmonth);
		$f = in_array($day, $this->days);
//		$M=(!($a&&$f) && !($b&&$d) && !($c&&$e));

		$N = ((($b || $d) && ($c || $e)) || ($f && !$a));
		$O = ($b && $c && !$a && !$f && !$d && !$e);
		$res = ($N && !$O);
		return $res;
	}

	public function computeDates($startdt, $duration, $exc) {
		$startdt = max([$startdt,time()]);
		$range = ($duration ? $duration : 183) * 24 * 3600;
		$enddt = $startdt + $range;
		array_walk($exc, function(&$v, $i) {
			$v = strtotime($v);
		});

		$a_startdt = getdate($startdt);
		$a_enddt = getdate($enddt);
		/* we build $months & $days from $startdt & $enddt according to the months & days present in $this elems. */
		$dates = array();
		$M = $a_startdt['mon'];
		$d = $a_startdt['mday'];
		for ($y = $a_startdt['year']; $y <= $a_enddt['year']; $y++) {
			$end_month = ($y == $a_enddt['year']) ? $a_enddt['mon'] : 12;
			for ($M; $M <= $end_month; $M++) {
				if (in_array($M, $this->months)) {
					$end_day = ((($y < $a_enddt['year']) || ($M < $a_enddt['mon'])) ? date("t", mktime(0, 0, 0, $M, 1, $y)) : $a_enddt['mday'] - 1);
					for ($d; $d <= $end_day; $d++) {
						if ($this->isDayOk($d, $M, $y)) {
							foreach ($this->hours as $h)
								foreach ($this->minutes as $m) {
									$date = mktime($h, $m, 0, $M, $d, $y);
									if ($date >= $startdt && $date <= $enddt && !in_array($date, $exc))
										$dates[] = $date;
								}
						}
					}
				}
				$d = 1;
			}
			$M = 1;
		}
		return $dates;
	}

	public function toXml() {
		$res = new xmlTag("xFrequency");
		$res->freq = $this->string;
		foreach ($this as $f => $v) {
			if (preg_match('/^(minutes|hours|days|months|weekdays|weeksofmonth)$/', $f) == 1) {
				$res->insertNode(new xmlTag($f, implode(',', $v)));
			}
		}
		return $res;
	}

	private function isStar($name) {
		return ($this->{$name}[0] == "*") || (count($this->{$name}) == $this->ranges->{$name}[1] - $this->ranges->{$name}[0] + 1);
	}
	
	/*
	 * getStatus : gets the status for a given field :
	 * 1 = * or full range
	 * 0 < status < 1 = ratio. if >0.5, the ratio indicates that there are more
	 * filled values than empty ones. e.g, for month, if $this->months contains
	 * 1,2,3 , getStatus("months") would return 0.25 but if it contains 1,2,3,4,5,6,7,8,9, 
	 * it would return 0.75. It can indicate that a "every month except oct,nov & dec" would be
	 * more readable than "in jan, feb, mar,apr, may, jun, jul, aug & sep"
	 * 
	 */
	private function getStatus($name) {
		if($this->{$name}[0]=="*")
			return 1;
		return (count($this->{$name})/($this->ranges->{$name}[1] - $this->ranges->{$name}[0] +1));
	}

	/*
	 * negative :
	 * for a given field, returns an array containing the values which are not in
	 * the fields array (complementary)
	 * e.g : if $this->months contains [1,2,3,4,5,6,9,10,11,12], negative("months")
	 * would return [7,8]
	 */
	private function negative($name, $func = null,$data = null) {
		$ret=array();
		$shiftnpushsunday=false;
		for($i=$this->ranges->{$name}[0];$i<=$this->ranges->{$name}[1];$i++){
			if(!in_array($i, $this->{$name})){
				if($i==0 && !$this->sundayfirst)
					$shiftnpushsunday=true;
				array_push($ret, ($func===null?$i:call_user_func($func,$i,$data)));
			}
		}
		if($shiftnpushsunday){
			array_push ($ret,  array_shift ($ret));			
		}
		return $ret;
	}
	
	public function toHumanString($with_time = false) {
		/*
		 * These arrays contain translation strings for weeks of month (including 
		 * gender variation for french between jour (day) and semaine (week)
		 * don't try to understand this array, it has been a pin in the ass to
		 * create (it is also used in javascript for admin repeat event creation.
		 * month names and week day names
		 */
		$switchOpts = array("day" => array(__("day"),array(__("every|week"), __("the 1st|day"), __("the 2nd|day"),
				__("the 3rd|day"), __("the 4th|day"), __("the 5th|day"))),
			"week" => array(__("week"),array(__("every|day"), __("the 1st|week"), __("the 2nd|week"),
				__("the 3rd|week"), __("the 4th|week"), __("the 5th|week"))));

		$monthnames = array("", __("January"), __("February"), __("March"), __("April"), __("May"), __("June"), __("July"), __("August"), __("September"), __("October"), __("November"), __("December"));

		$wdnames = array(__("Sunday"), __("Monday"), __("Tuesday"), __("Wednesday"), __("Thursday"), __("Friday"), __("Saturday"));

		/* $phrases is an array of array of array of array of sentences with §D, §M, §W, §W1, §W2 placeholders to create the proper sentence in any context.
		 * the first dimension is wd, the weekday, accepting the values "a" (for all), "p" (positive list) & "n" (negative list) 
		 * the second dimension is m, the month, accepting the values "a" (for all), "p" (positive list) & "n" (negative list) "*" for default
		 * the third is w, the week of the month, accepting the values "a" (for all), "p" (positive list) & "n" (negative list)
		 * the last is d, the day of the month, accepting the values "a" (for all) & "p" (positive list) "*" for default
		 * it is implemented as json.
		 */
		global $phrases;
		
		$phrases=json_decode('
		{"a":	{"*":	{"a":	{"*": "§D §M"},
						 "p":	{"a":"§W1 '.__('week').' §W2 §M","p":"'.__('the|day').' §D '.__('and').' '.__('the|week').' §W1 week §W2 §M"},
						 "n":	{"a":"§W1 '.__('week').' §W2 §M","p":"'.__('the|day').' §D and §W1 '.__('week').' §W2 §M"}},
						 "a":	{"a":	{"a":"§D"}}},
		 "p":	{"*":	{"a":	{"a":"§W0 §WD §M","p":"'.__('the|day').' §D '.__('and').' §W0 §WD §M"},
						 "p":	{"a":"'.__('the|day').' §W0 §WD §M","p":"'.__('the|day').' §D '.__('and').' '.__('the|day').' §W0 §WD §M"},
						 "n":	{"a":"§W0'.__(' on ').'§WD §M","p":"error - p*np should redirect to p*pp"}},
				 "a":	{"a":	{"a":"§W0 §WD","p":"'.__('the|day').' §W0 §WD §M"},
						 "p":	{"a":"'.__('the|day').' §W0 §WD '.__('of the month').'"},
						 "n":	{"a":"§W0'.__(' on ').'§WD"}}},
		 "n":	{"*":	{"a":	{"a":"§W0 '.__('week').' §WD §M"},
						 "p":	{"a":"'.__('every|week').' §W0 '.__('week').' §WD §M"}},
				 "a":	{"a":	{"a":"§W0 '.__('week').' §WD","p":"error - naap should redirect to paap"},
						 "p":	{"a":"'.__('every|week').' §W0 '.__('week').' §WD","p":"error - napp should redirect to papp"},
						 "n":	{"a":"error - nana should redirect to napa","p":"error - nanp should redirect to papp"}},
				 "p":	{"a":	{"p":"error - npap should redirect to ppap"},
						 "p":	{"p":"error - nppp should redirect to pppp"},
						 "n":	{"a":"error - npna should redirect to nppa","p":"error - npnp should redirect to pppp"}},
				 "n":	{"a":	{"p":"error - nnap should redirect to pnap"},
						 "p":	{"p":"error - nnpp should redirect to pnpp"},
						 "n":	{"a":"error - nnna should redirect to nnpa","p":"error - nnnp should redirect to pnpp"}}}}');

		$getphrase = function ($wd,$m,$w,$d){
			global $phrases;
			$wd=($wd==1?"a":($wd<0.5?"p":"n"));
			$m=($m==1?"a":($m<0.5?"p":"n"));
			$w=($w==1?"a":($w<0.5?"p":"n"));
			$d=($d==1?"a":"p");
			
			$am = array($m,"*");
			$ad = array($d,"*");
			
			foreach($am as $cm){
				if( isset($phrases->{$wd}->{$cm}) && 
					isset($phrases->{$wd}->{$cm}->{$w})){
						foreach($ad as $cd){
							if(isset($phrases->{$wd}->{$cm}->{$w}->{$cd})){
							$m = $cm;
							$d = $cd;
							break 2;
						}
					}
				}		
			}
			if(isset($phrases->{$wd}->{$m}->{$w}->{$d})){
				return $phrases->{$wd}->{$m}->{$w}->{$d};
			}else{
				return "Can't find the phrase for $wd/$m/$w/$d.";
			}
		};
		
		/*$ret is the returned text
		 * $retd, $retm & $retw are the partial return strings for days, months & weeks
		 * these strings can include §D for $retd, §M for $retm & §W for $retw to be replaced later.
		 */
		
		$ret = $retd = $retm = $retwd = $retw = $retw1 = $retw2 = "";
		$sd = $this->getStatus('days');
		$sm = $this->getStatus('months');
		$swd = $this->getStatus('weekdays');
		$sw = $this->getStatus('weeksofmonth');

		$time = ($with_time ? sprintf(" at %2d:%2d", $this->hours[0], $this->minutes[0]) : "");
		
		$which = ($sw<1 && $swd==1) ? "week" : "day";
		
		/*Change the status for unsupported cases*/
		if($sw>=0.5 && $sw<1 && $swd!=1){
			if($swd>=0.5 || $sd!=1){
				$sw=0.49;
			}
		}
		if($swd>=0.5 && $swd<1){
			if($sd!=1){
				$swd=0.4;
			}
		}
		
		if ($sd == 1) {
			$retd = __("every day");
		} else{
			$days = $this->days;
			array_walk($days, function(&$v,$i){$v=xl10n::ordinalize($v);});
			$retd = join3($days, ', ', __(" & "), array("gprefix" => __("the |day")));
		}

		if ($sw==1) {
			$retw = $retw1 = __("every|week");
		} elseif($sw<0.5) {
				$weeks=$this->weeksofmonth;
//				array_walk($weeks,function(&$v,$i,$d){
//					$v=$d[1][$v];
//				},$switchOpts[$which]);
				array_walk($weeks, function(&$v,$i){$v=xl10n::ordinalize($v);});
//				$retw1 = $retw .= join2($weeks, ', ', __(" & "));
				$retw1 = $retw = join3($weeks, ', ', __(" & "),array("gprefix2"=> __("the |day")));
		} else {
//			$except_weeks = $this->negative("weeksofmonth",function($v,$d){return $d[1][$v];},$switchOpts[$which]);
			$except_weeks = $this->negative("weeksofmonth",function($v){return xl10n::ordinalize($v);});
			$retw1 = __("every week");
			$retw2 = sprintf(__("(except %s)"), join3($except_weeks, ", ", __(" & "),array("gprefix"=>__("the |week"))));
			$retw=$retw1." ".$retw2;
		}
		
		if ($swd==1) {
			$retwd = __("week");
		}elseif($swd<0.5){
			$wd=$this->weekdays;
			array_walk($wd,function(&$v,$i,$d){$v=$d[$v];},$wdnames);
			$retwd = join2($wd, ', ', __(" & "));
		} else {
			$except_days = $this->negative("weekdays", function($v,$d){return $d[$v];},$wdnames);
			$retwd = sprintf(__("(except %s)"), join2($except_days, ", ", __(" & ")));
		}
		
		
		if ($sm==1) {
			$retm = __(" of every month");
		}elseif ($sm<0.5) {
			$months = $this->months;
			array_walk($months,function(&$v,$i,$d){$v=$d[$v];},$monthnames);
			$retm = sprintf(__(" of %s"), join3($months, ', ', __(" & "),array("gprefix2"=>__("of |month"))));
		}else{
			$except_months=$this->negative("months",function($v,$d){return $d[$v];},$monthnames);
			$retm = sprintf(__(" of every month (except %s)"),join2($except_months,", ",__(" & ")));
		}
		
		$phrase = $getphrase($swd,$sm,$sw,$sd);
		$phrase = str_replace("§M",$retm,$phrase);
		$phrase = str_replace("§W0",$retw,$phrase);
		$phrase = str_replace("§W1",$retw1,$phrase);
		$phrase = str_replace("§W2",$retw2,$phrase);
		$phrase = str_replace("§WD",$retwd,$phrase);
		$phrase = str_replace("§D",$retd,$phrase);
		
		$ret = $phrase.$time;
		return $ret;
	}

}

?>
