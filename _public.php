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

if (!defined('DC_RC_PATH')){return;}

global $core;
# Public behaviors
$core->addBehavior('templatePrepareParams',array('publicEHRepeat','templatePrepareParams'));
$core->addBehavior('tplIfConditions',array('publicEHRepeat','tplIfConditions'));

class publicEHRepeat
{	
	/*
	 * Extends the available attributes for <EventEntries>
	 * @attr 'replace_slaves' : tells eventhandler to serve master events instead of the slaves
	 * @attr 'noslaves' : serves only master or non repetitive events 
	 * @attr 'masters' : serves only master events
	 * @attr 'repetitives' : serves only master & slave events
	 */
	public static function templatePrepareParams($caller,$attr,$content) {
		$res="";
		if($caller["tag"] == "EventsEntries"){	
			if(isset($attr['firstonly']) && $attr['firstonly']){
				$res.="\$params['firstonly']=true;\n";				
			}
			if(isset($attr['replace_slaves']) && $attr['replace_slaves']){
				$res.="\$params['replace_slaves']=true;\n";
			}
			if(isset($attr['noslaves']) && $attr['noslaves']){
				//dirty hack : for the "of" pages (events of a day e.g.) does not restrict events to 'noslaves'. To be fixed otherwhere
				//global $_ctx;
				//if (!isset($_ctx->event_params["event_interval"]) || $_ctx->event_params["event_interval"] != "of") {
				$res.="\$params['noslaves']=true;\n";
				//}
			}elseif(isset($attr['masters']) && $attr['masters']){
				$res.="\$params['masters']=true;\n";
			}elseif(isset($attr['repetitives']) && $attr['repetitives']){
				$res.="\$params['repetitives']=true;\n";
			}
			if(isset($attr['sortby']) && $attr['sortby']=='rpt_evnt'){
				$res.="\$params['sortby']=rpt_evnt;\n";
				if(isset($attr['order'])){
					$res.="\$params['order']=".$attr['order'].";\n";
				}
			}
		}
		return $res;
	}
	
	public static function tplIfConditions($tag,$attr,$content,$if) {
		if($tag == "EventsEntryIf"){			
			if(isset($attr["is_repeat"])){
				$sign= (boolean) $attr['is_repeat'] ? '' : '!';
				$if[] = $sign."\$_ctx->posts->isRepeat()";
			}
			if(isset($attr["is_master"])){
				$sign= (boolean) $attr['is_master'] ? '' : '!';
				$if[] = $sign."\$_ctx->posts->isMaster()";
			}
			if(isset($attr["is_slave"])){
				$sign= (boolean) $attr['is_slave'] ? '' : '!';
				$if[] = $sign."\$_ctx->posts->isSlave()";				
			}
			if(isset($attr["numslaves"])){
				$attr["remove_html"]=1;
				$if[] = "\$_ctx->posts->countSlavesOrBrothers()".html_entity_decode($attr["numslaves"]);
			}
		}
	}
}

$core->tpl->addValue('ehRepeatGetHumanReadableFreqExc',array('tplEhRepeat','ehRepeatGetHumanReadableFreqExc'));
$core->tpl->addValue('EventsEntriesRepeatDate',array('tplEhRepeat','EventsEntriesRepeatDate'));
$core->tpl->addValue('EventsEntriesMasterDate',array('tplEhRepeat','EventsEntriesMasterDate'));
$core->tpl->addBlock('EventsEntriesRepeat',array('tplEhRepeat','EventsEntriesRepeat'));
$core->tpl->addBlock('EventsEntryRepeatIf',array('tplEhRepeat','EventsEntryRepeatIf'));


class tplEhRepeat{
	public static function ehRepeatGetHumanReadableFreqExc($a) {
		return '<?php echo '.sprintf($GLOBALS['core']->tpl->getFilters($a),'$_ctx->posts->getHumanReadableFreqExc()').'; ?>';;
	}

	public static function EventsEntriesRepeat($attr,$content) {
		global $core;
		$p="";
		if(isset($attr['masterandslaves'])){
			$p.="\$params['masterandslaves']=\$_ctx->posts->rpt_master;\n";
		}
		if (!empty($attr['order']) || !empty($attr['sortby'])) {
			$p .=
                "\$params['order'] = '".$core->tpl->getSortByStr($attr,'eventhandler')."';\n";
		}

		$res = "<?php\n".
			   'if(!isset($eventHandler)) { $eventHandler = new eventHandler($core); } '."\n".
               '$params = array(); '."\n";
		$res .= $p;
		$res .= $core->callBehavior("templatePrepareParams",
			array("tag" => "EventsEntriesRepeat","method" => "eventHandler::getEvents"),
			$attr,$content);
		$res .= '$_ctx->repeats_params = $params; '."\n".
				'$_ctx->repeats = $eventHandler->getEvents($params); unset($params); '."\n".
				"?>\n".
				'<?php while ($_ctx->repeats->fetch()) : ?>'.$content.'<?php endwhile; '.
				'$_ctx->repeats = null; $_ctx->repeats_params = null; ?>';
		return $res;
	}
	
	public static function EventsEntryRepeatIf($attr,$content)
	{
		global $core;

		$if = array();

		$operator = isset($attr['operator']) ? $core->tpl->getOperator($attr['operator']) : '&&';

		if (isset($attr['startdt_is'])) {
			$sign = ($attr['startdt_is'][0]=='!') ? '!' : '';
			$attr['startdt_is']=  str_replace($sign, '', $attr['startdt_is']);
			if($attr['startdt_is']=='sameaspost'){
				$if[] = $sign.'($_ctx->repeats->event_startdt == $_ctx->posts->event_startdt)';
			}
		}

		#Behavior tplIfConditions
		$ao_if=new ArrayObject($if);
		$core->callBehavior('tplIfConditions','EventsEntryRepeatIf',$attr,$content,$ao_if);
		$if=$ao_if->getArrayCopy();

		if (!empty($if)) {
			return '<?php if('.implode(' '.$operator.' ',$if).') : ?>'.$content.'<?php endif; ?>';
		} else {
			return $content;
		}

	}
	
	public static function EventsEntriesRepeatDate($a){
		$format = !empty($a['format']) ? addslashes($a['format']) : '';
		$iso8601 = !empty($a['iso8601']);
		$rfc822 = !empty($a['rfc822']);

		$type = '';
		if (!empty($a['creadt'])) {
            $type = 'creadt';
        }
		if (!empty($a['upddt'])) {
            $type = 'upddt';
        }
		if (!empty($a['enddt'])) {
            $type = 'enddt';
        }
		if (!empty($a['startdt'])) {
            $type = 'startdt';
        }

		if ($rfc822) {
			return self::tplValue($a,"\$_ctx->repeats->getEventRFC822Date('".$type."')");
		} elseif ($iso8601) {
			return self::tplValue($a,"\$_ctx->repeats->getEventISO8601Date('".$type."')");
		} else {
			return self::tplValue($a,"\$_ctx->repeats->getEventDate('".__($format)."','".$type."')");
		}
	}
	
	//Gets the current event's master's date 
	public static function EventsEntriesMasterDate($a){
		$format = !empty($a['format']) ? addslashes($a['format']) : '';
		$iso8601 = !empty($a['iso8601']);
		$rfc822 = !empty($a['rfc822']);

		$type = '';
		if (!empty($a['creadt'])) {
            $type = 'creadt';
        }
		if (!empty($a['upddt'])) {
            $type = 'upddt';
        }
		if (!empty($a['enddt'])) {
            $type = 'enddt';
        }
		if (!empty($a['startdt'])) {
            $type = 'startdt';
        }

		if ($rfc822) {
			return self::tplValue($a,"\$_ctx->posts->getEventRFC822MasterDate('".$type."')");
		} elseif ($iso8601) {
			return self::tplValue($a,"\$_ctx->posts->getEventISO8601MasterDate('".$type."')");
		} else {
			return self::tplValue($a,"\$_ctx->posts->getEventMasterDate('".__($format)."','".$type."')");
		}		
	}
	
	public static function EventsEntrySlavesCount($a){
		return self::tplValue($a, "\$_ctx->posts->countEvents()");	
	}
	
	protected static function tplValue($a, $v) {
		return '<?php echo '.sprintf($GLOBALS['core']->tpl->getFilters($a),$v).'; ?>';
	}
}