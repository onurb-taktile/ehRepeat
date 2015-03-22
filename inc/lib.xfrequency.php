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


if (!defined('DC_RC_PATH')){return;}

class xFrequency{
	protected $ranges;
	protected $minutes;
	protected $hours;
	protected $days;
	protected $months;
	protected $weekdays;
	protected $weeksofmonth;
	private $update_lock=FALSE;
	
	protected $string;

	const XFREQ_RANGE='{"minutes":[0,59],
					 	"hours":[0,23],
					 	"days":[1,31],
					 	"months":[1,12],
					 	"weekdays":[0,6],
					 	"weeksofmonth":[1,5]}';

	function __construct ($s){
		try{
			$this->ranges=json_decode(self::XFREQ_RANGE);
			$this->parseFreq($s);
		}catch(Exception $e){
			throw(new Exception(sprintf(__("Can't construct a xFrequency from string %s"),$s),1,$e));
		}
		$this->string=$s;
	}

	function __set($name,$value)
	{	
		if(preg_match('/^(minutes|hours|days|months|weekdays|weeksofmonth)$/',$name)==1){
			$res=array();
			if(!is_array($value))
				$value=array($value);
			foreach($value as $v){
				if($v=='*'){
					if(preg_match('/days|week/',$name)==1)
						$res[]="*";
					else
						$res=range($this->ranges->{$name}[0],$this->ranges->{$name}[1]);
					break;
				}else{
					$res[]=$v;
				}
			}
			$this->{$name}=array_unique($res,SORT_NUMERIC);
			if(!$this->update_lock) 
				$this->updateString();
		}else throw(new Exception(sprintf(__("Don't know how to set value %s, field unknown"),$name)));
	}
	
	function __get($name)
	{
		if(preg_match('/^(minutes|hours|days|months|weekdays|weeksofmonth)$/',$name)==1){
			if(count($this->{$name})==($this->ranges->{$name}[1]-$this->ranges->{$name}[0])+1){
				//we have a full range
				return "*";
			}else{
				return implode(',',$this->{$name});
			}
		}else throw(new Exception(sprintf(__("Don't know how to get %s, field unknown"),$name)));
	}
	
	protected function parseFreq($freq){
		$matches = array();
		$reg = '/^(?P<minutes>\*|[0-5]?[0-9](?:,[0-5]?[0-9])*) (?P<hours>\*|[0-2]?[0-9](?:,[0-2]?[0-9])*) (?P<days>\*|[0-3]?[0-9](?:,[0-3]?[0-9])*) (?P<months>\*|[0-1]?[0-9](?:,1?[0-9])*) (?P<weekdays>\*|[0-6](?:,[0-6])*) (?P<weeksofmonth>\*|[1-5](?:,[1-5])*)\s*$/';
		if (preg_match($reg,$freq,$matches) != 1)
			throw new Exception('xFrequency::parseFreq :' . sprintf(__('The frequency string %s is incorrect'),$freq));
		foreach ($matches as $k => $v)
			$matches[$k] = explode(',',$v);
		foreach ($matches['hours'] as $k => $v)
			$matches['hours'][$k] = sprintf("%'.02d",$v);
		foreach ($matches['minutes'] as $k => $v)
			$matches['minutes'][$k] = sprintf("%'.02d",$v);
		$this->update_lock=TRUE;
		$this->__set('minutes',$matches['minutes']);
		$this->__set('hours',$matches['hours']);
		$this->__set('days',$matches['days']);
		$this->__set('months',$matches['months']);
		$this->__set('weekdays',$matches['weekdays']);
		$this->__set('weeksofmonth',$matches['weeksofmonth']);
		$this->update_lock=FALSE;
	}
	
	protected function updateString(){
		$this->string=$this->__get("minutes")." ".$this->__get("hours")." ".$this->__get("days")." ".$this->__get("months")." ".$this->__get("weekdays")." ".$this->__get("weeksofmonth");
	}

	private function isDayOk($day,$month,$year)
	{
		$w=1+floor(($day-1)/7);
		$wd=date("w",mktime(0,0,0,$month,$day,$year));
		$a=($this->days[0]=="*");
		$b=($this->weekdays[0]=="*");
		$c=($this->weeksofmonth[0]=="*");
		$d=in_array($wd,$this->weekdays);
		$e=in_array($w,$this->weeksofmonth);
		$f=in_array($day,$this->days);
//		$M=(!($a&&$f) && !($b&&$d) && !($c&&$e));

		$N=((($b||$d) && ($c||$e)) || ($f && !$a));		
		$O=($b && $c && !$a && !$f && !$d && !$e);
		$res=($N && !$O);
		return $res;
	}
	
	public function computeDates($startdt,$enddt)
	{
		$a_startdt=getdate($startdt);
		$a_enddt=getdate($enddt);
		/* we build $months & $days from $startdt & $enddt according to the months & days present in $this elems.*/ 
		$dates=array();
		$M=$a_startdt['mon'];
		$d=$a_startdt['mday'];
		for($y=$a_startdt['year'];$y<=$a_enddt['year'];$y++){
			$end_month=($y==$a_enddt['year'])?$a_enddt['mon']:12;
			for($M;$M<=$end_month;$M++){
				if(in_array($M,$this->months)){
					$end_day=((($y<$a_enddt['year'])||($M<$a_enddt['mon']))?date("t",mktime(0,0,0,$M,1,$y)):$a_enddt['mday']-1);
					for($d;$d<=$end_day;$d++){					
						if($this->isDayOk($d, $M, $y)){
							foreach($this->hours as $h)
								foreach ($this->minutes as $m) {
									$dates[]=date("Y-m-d H:i",mktime($h,$m,0,$M,$d,$y));
								}							
						}
					}
				}
				$d=1;
			}
			$M=1;
		}
		return $dates;
	}		

	public function toXml(){
		$res=new xmlTag("xFrequency");
		$res->freq=$this->string;
		foreach($this as $f=>$v){
			if(preg_match('/^(minutes|hours|days|months|weekdays|weeksofmonth)$/',$f)==1){
				$res->insertNode(new xmlTag($f,implode(',',$v)));
			}
		}
		return $res;
	}
}



?>