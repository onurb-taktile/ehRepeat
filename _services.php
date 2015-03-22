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
 * -- END LICENSE BLOCK ------------------------------------ */

if (!defined('DC_CONTEXT_ADMIN')) {
	return;
}

class ehRepeatRestMethods
{
	public static function parseFreq($core,$get)
	{
		$freq = $get['freq'];
		$rsp = new xmlTag();
		if ($freq == null)
			throw new Exception("Frequency string missing",1);
		$xfreq = new xFrequency($freq);
		$rsp->value(json_encode($xfreq->toXml(),JSON_PRETTY_PRINT));
		return $rsp;
	}

	public static function computeDates($core,$get)
	{
		$freq = isset($get['freq'])?$get['freq']:null;
		$startdt = isset($get['startdt'])?$get['startdt']:null;
		$enddt = isset($get['enddt'])?$get['enddt']:null;

		$rsp = new xmlTag();
		if ($freq == null || $startdt == null)
			throw new Exception("Wrong parameters",1);
				
		$t_startdt = strtotime($startdt);
		$now=time();
		if ($t_startdt < $now)
			$t_startdt = $now;
		if(!$enddt){
			$t_duration = ($core->blog->settings->eventHandler->rpt_duration ? $core->blog->settings->eventHandler->rpt_duration : 183) * 24 * 3600;
			$t_enddt = $t_startdt+$t_duration;
		}else{
			$t_enddt = strtotime($enddt);		
		}
		
		$xfreq = new xFrequency($freq);
		$rsp->insertNode($xfreq->toXml());
		$dates = new xmlTag("dates");

		setlocale(LC_ALL,"fr_FR.UTF-8");
		$dates->from = strftime("%c",$t_startdt);
		$dates->to = strftime("%c",$t_enddt);
		$dates->freq = $freq;
		$res = $xfreq->computeDates($t_startdt,$t_enddt);
		foreach ($res as $r) {
			$rr = strftime(__("%A %B %e %Y %k:%M"),strtotime($r));
			$dates->insertNode(new xmlTag("date",$rr));
		}
		$rsp->insertNode($dates);
		return $rsp;
	}

	public static function countSlaves($core,$get)
	{
		$master_id = $get["master_id"];
		if ($master_id == null)
			throw new Exception("Need master_id to count slaves",1);
		$rpt = new ehRepeat(new eventHandler($core));
		$res = $rpt->getEvents(array('slaves'=>$master_id),true);
		$rsp = new xmlTag();
		if ($res->f(0) > 0)
			$rsp->value(sprintf(__(" - one event has been generated."," - %d events have been generated",(integer)$res->f(0)),$res->f(0)));
		else
			$rsp->value("");
		return $rsp;
	}
	
}
