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
	
	public static function countSlaves($rs)
	{
		if(self::isMaster($rs)){
			$params=array('slaves'=>$rs->post_id);
			$srs=$rs->eventHandler->getEvents($params,true);
			return $srs->f(0);
		}elseif(self::isSlave($rs))
			return -2;
		else
			return -1;
	}	

	public static function computeDates($rs,$date)
	{
		//all date manipulations are performed on unix timestamps
		global $core;
		if(!self::isMaster($rs))
			return null;
		//start of the generation range = event startdt if > $date, $date otherwise
		$start_dt=(strtotime($rs->event_startdt)>$date)?strtotime($rs->event_startdt):$date;
		//end of the generation range = $start_dt + duration setting converted to seconds
		$end_dt = $start_dt+($core->blog->settings->eventHandler->rpt_duration*24*3600);
	
		
		
		
	}

	
}
