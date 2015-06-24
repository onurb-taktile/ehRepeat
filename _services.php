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
		$xfreq = new xFrequency($freq,$core->blog->settings->eventHandler->rpt_sunday_first);
		$rsp->value(json_encode($xfreq->toXml(),JSON_PRETTY_PRINT));
		return $rsp;
	}

	public static function computeDates($core,$get)
	{
		$freq = isset($get['freq'])?$get['freq']:null;
		$startdt = isset($get['startdt'])?$get['startdt']:null;
		$enddt = isset($get['enddt'])?$get['enddt']:null;
		$exc = isset($get['exc'])?explode((';'), $get['exc']):array();
		$rsp = new xmlTag();
		if ($freq == null || $startdt == null)
			throw new Exception("Wrong parameters",1);
				
		$t_startdt = strtotime($startdt);
		$t_duration = $core->blog->settings->eventHandler->rpt_duration;
		$t_enddt = $enddt? strtotime($enddt):$t_startdt+($t_duration*24*3600);
		$xfreq=new xFrequency($freq,$core->blog->settings->eventHandler->rpt_sunday_first);
		$slave_dates=  $xfreq->computeDates($t_startdt, $t_duration, $exc);
		
		$rsp->insertNode($xfreq->toXml());
		$dates = new xmlTag("dates");

		setlocale(LC_ALL,"fr_FR.UTF-8");
		$dates->from = strftime("%c",$t_startdt);
		$dates->to = strftime("%c",$t_enddt);
		$dates->freq = $freq;
		foreach ($slave_dates as $r) {
			$rr = strftime(__("%A %B %e %Y %k:%M"),$r);
			$dates->insertNode(new xmlTag("date",$rr));
		}
		$rsp->insertNode($dates);
		return $rsp;
	}
}
