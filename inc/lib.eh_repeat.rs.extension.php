<?php
/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of ehRepeat, a plugin for Dotclear 2.
 *
 * Copyright(c) 2015 Onurb Teva <dev@taktile.fr>
 *
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

if (!defined('DC_RC_PATH')){return;}

class rsEhRepeatPublic extends rsExtPost
{
	public static function isRepeat($rs){
		return (($rs->count()>0) && isset($rs->rpt_master) && $rs->rpt_master!=0);
	}
	
	public static function isMaster($rs){
		return (self::isRepeat($rs) && $rs->rpt_master == $rs->post_id);
	}
	
	public static function isSlave($rs)
	{
		return (self::isRepeat($rs) && $rs->rpt_master != $rs->post_id);
	}
	
    public static function getFreq($rs)
    {
        return self::isRepeat($rs)?$rs->rpt_freq:"";
    }
    
    public static function getExc($rs)
    {
        return self::isRepeat($rs)?explode(';',$rs->rpt_exc):array();
    }
	
	//Counts slaves for record : number of slaves if record is master, -2 if record is slave -1 if not repetitive event
	public static function countSlaves($rs){
		if(self::isMaster($rs)){
			$params=array('slaves'=>$rs->post_id);
			$srs=$rs->eventHandler->getEvents($params,true);
			return $srs->f(0);
		}elseif(self::isSlave($rs))
			return -2;
		else
			return -1;		
	}
	//Counts slaves + master if record is master
	public static function countEvents($rs)
	{
		$slavesCount=self::countSlaves($rs);
		if($slavesCount>=0)
			return $slavesCount+1;
		return $slavesCount;
	}	

	//Counts slaves for the current record (if is a master) or brothers (if is a slave)
	public static function countSlavesOrBrothers($rs)
	{
		if(self::isSlave($rs)){
			$params=array('masterandslaves'=>$rs->rpt_master);
			$mrs=$rs->eventHandler->getEvents($params,true);
			return $mrs->f(0);
		}
		return self::countSlaves($rs);
	}
	
	public static function computeDates($rs,$date)
	{
		//all date manipulations are performed on unix timestamps
		global $core;
		if(!self::isMaster($rs))
			return null;
		//start of the generation range = event startdt if > $date, $date otherwise
		$start_dt=(strtotime($rs->event_startdt)>$date)?strtotime($rs->event_startdt)+1:$date;
		//end of the generation range = $start_dt + duration setting converted to seconds
		$end_dt = $start_dt+($core->blog->settings->eventHandler->rpt_duration*24*3600);
	}
	
	public static function getHumanReadableFreqExc($rs)
	{
		global $core;
		if(!self::isRepeat($rs))
			return "";
		$xFreq=new xFrequency($rs->rpt_freq,$core->blog->settings->eventHandler->rpt_sunday_first);
		$aExc=($rs->rpt_exc != "")?explode(';',$rs->rpt_exc):array();
		foreach($aExc as $i=>$v){
			$aExc[$i]=  xl10n::strftime(__("%B, %q"),strtotime($v));
		}
		sort($aExc,SORT_NUMERIC);
		$exc=join2($aExc,", "," & ");
		return $xFreq->toHumanString().(count($aExc)>0?", ".__(" except on |sing"," except on |plur", count($aExc)).$exc:'');
	}

//	public static function getURL($rs)
//	{
//		global $ehRepeat;
//		if(self::isSlave($rs)){
//			$master=$ehRepeat->getMaster($rs->rpt_master);
//			return $master->getURL();
//		}
//		return parent::getURL($rs);
//	}
//	
	public static function getEventRFC822MasterDate($rs,$format,$type='')
	{
		global $ehRepeat;
		if(self::isSlave($rs)){
			$master=$ehRepeat->getMaster($rs->rpt_master);
			return $master->getEventRFC822Date($format,$type);
		}
		return $rs->getEventRFC822Date($format,$type);
	}

	public static function getEventISO8601MasterDate($rs,$format,$type='')
	{
		global $ehRepeat;
		if(self::isSlave($rs)){
			$master=$ehRepeat->getMaster($rs->rpt_master);
			return $master->getEventISO8601Date($format,$type);
		}
		return $rs->getEventISO8601Date($format,$type);
	}

	public static function getEventMasterDate($rs,$format,$type='')
	{
		global $ehRepeat;
		if(self::isSlave($rs)){
			$master=$ehRepeat->getMaster($rs->rpt_master);
			return $master->getEventDate($format,$type);
		}
		return $rs->getEventDate($format,$type);
	}
}
