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

global $__autoload, $core;

# Main class
$__autoload['rsEhRepeatPublic'] = dirname(__FILE__).'/inc/lib.eh_repeat.rs.extension.php';
$__autoload['ehRepeat'] = dirname(__FILE__).'/inc/class.ehrepeat.php';
$__autoload['xFrequency'] = dirname(__FILE__).'/inc/lib.xfrequency.php';
$__autoload['ehRepeatRestMethods'] = dirname(__FILE__).'/_services.php';
$__autoload['tplEhRepeat'] = dirname(__FILE__).'/_public.php';

# parsefreq rest method  (for ajax service)
$core->rest->addFunction('parseFreq',array('ehRepeatRestMethods','parseFreq'));
$core->rest->addFunction('computeDates',array('ehRepeatRestMethods','computeDates'));


$core->addBehavior('coreEventHandlerBeforeGetEvents', 'coreEventHandlerBeforeGetEvents');
$core->addBehavior('coreEventHandlerGetEvents', 'coreEventHandlerGetEvents');
$core->addBehavior('publicBeforeContentFilter','publicBeforeContentFilter');
$core->addBehavior('coreAfterPostContentFormat','coreAfterPostContentFormat');


/*Refresh all outdated master events and their slaves*/
ehRepeat::doTheRevolution();

	# this behavior is for getEvents records manipulation, generally applying some
	# extensions.

	function coreEventHandlerGetEvents($rs) {
		$rs->ehRepeat = new ehRepeat($rs->eventHandler);
		$rs->extend('rsEhRepeatPublic');
	}

	function prepareParams($eh,&$params,$param,$is_num=true){
		if (is_array($params[$param])) {
			if($is_num)array_walk($params[$param],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
		} else {
			$params[$param] = array((integer) $params[$param]);
		}
		return $eh->con->in($params[$param]);
	}
	
	function coreEventHandlerBeforeGetEvents($eh, $args) {
		global $core;
		$params=array();
		foreach ($args as $v => $k) {
			$$v = &$args[$v];
		} #Recreates the byref args.
		$col = (array) $params['columns'];
		$col[] = 'rpt_master';
		$col[] = 'rpt_freq';
		$col[] = 'rpt_exc';
		$params['columns'] = $col;

		/* to get the master, give master_id in $params['master'] */
		if (!empty($params['master'])) {
			$params['post_id'] = $params['master'];
			$params['sql'] .= " AND EH.rpt_master " . prepareParams($eh,$params,'master');
			unset($params['master']);
		}

		/* to get the slaves, give master_id in $params['slaves'] */
		if (!empty($params['slaves'])) {
			$params['sql'] .= " AND EH.post_id != EH.rpt_master AND EH.rpt_master " . prepareParams($eh,$params,'slaves');
			unset($params['slaves']);
		}

		/* to get only masters, set params['masters'] to true */
		if (!empty($params['masters'])) {
			$params['sql'] .= " AND EH.rpt_master = P.post_id ";
			unset($params['masters']);
		}

		/* to get all but slaves, set parms['noslaves'] to true */
		if (!empty($params['noslaves'])) {
			$params['sql'] .= " AND ((EH.post_id = EH.rpt_master) OR (EH.rpt_master IS NULL)) ";
			unset($params['noslaves']);
		}

		/* to get master and slaves for a master if */
		if (!empty($params['masterandslaves'])) {
			$params['sql'] .= " AND EH.rpt_master " . prepareParams($eh, $params, 'masterandslaves');
		}

		/* to get masters and slaves */
		if (!empty($params['repetitives'])) {
			$params['sql'] .= " AND EH.rpt_master IS NOT NULL";
		}
		
		/* to get events by titles */
		if(!empty($params['by_title'])){
			$params['sql'] .= " AND P.post_title " . prepareParams($eh, $params, 'by_title', false);
		}
		
		
		if (!empty($params['sortby']) && $params['sortby'] == 'rpt_evnt') {
			$col = (array) $params['columns'];
			$col[] = 'S.master';
			$col[] = 'S.status';
			$col[] = 'C.nbslaves';
			$params['columns'] = $col;

			$params['from'].=" INNER JOIN (SELECT post_id as id, rpt_master as master, if(rpt_master IS NULL,'-', if(rpt_master = post_id,'M','S')) as status FROM  ".$core->prefix."eventhandler) S on S.id = P.post_id LEFT JOIN (SELECT CEH.rpt_master as master, count(distinct CP.post_id) AS nbslaves FROM ".$core->prefix."post CP INNER JOIN ".$core->prefix."eventhandler CEH ON  CEH.post_id = CP.post_id WHERE CP.blog_id = 'default' AND  CP.post_type = 'eventhandler' and CEH.rpt_master != CP.post_id AND CEH.rpt_master=4) C on C.master = P.post_id";
			$order = !empty($params['order']) ? $params['order'] : (!empty($_GET['order']) ? $_GET['order'] : 'desc');

			if (!empty($params['repetitives'])) {
				$params['order'] = 'EH.rpt_master ' . $order . ', S.status ' . $order . ', EH.event_startdt ' . $order;
			} else {
				$params['order'] = 'S.status ' . $order . ', EH.rpt_master ' . $order . ', P.post_dt ' . $order;
			}
			unset($params['repetitives']);
			unset($params['masterandslaves']);
			unset($params['sortby']);
		}
		
		/* to get the first event only : a non repetitive event, or the first master or slave for a given masterid */
		if(!empty($params['firstonly'])&&$params['firstonly']){
			$params['from']=str_replace( "EH.post_id","IFNULL(EH.rpt_master,EH.post_id)",$params['from']);
			$params['sql'].=' GROUP BY P.post_id ';
			unset($params['firstonly']);			
		}

		/* to get the masters instead of slaves */
		if (!empty($params['replace_slaves']) && $params['replace_slaves']) {
			//We modify the join condition, inserting a IFNULL() construct :
			// if EH.rpt_master is null (a non repetitive event), EH.post_id is used instead.
			$params['from']=str_replace( "EH.post_id","IFNULL(EH.rpt_master,EH.post_id)",$params['from']);
			$params['sql'].=' GROUP BY P.post_id ';
			unset($params['replace_slaves']);
		}

		
	}
	
	/* Handling of extra xhtml markup [event_list]
	 * This markup has the following attributes :
	 * id : the master event id whom slaves to add to the list
	 * comment : an optionnal comment given for the previous id. if a comment is given for one id please specify comments (even empty) for all ids.
	 * id and comment can be repeted if several master events have to be inserted (in that case, you mayy want to specify a mode)
	 * mode : mix or evt : mix will mix all the slave events and sort them by date, evt will group all the events master by master.
	 * title : a title to display for the list (defaults to __("Next events") )
	 * eg : [event_list id="26" comment="" id="118" comment="(Marché festif)" title="Prochains marchés" mode="mix"]
	 */
	function publicBeforeContentFilter ($core, $tag, $arr) {
		if( $tag == 'EntryContent' || $tag == 'EntryExcerpt' ) {
			$txt = $arr[0];
			$out=array();
			preg_match_all("/\[event_list\b([^\]]*)\]/is",$txt,$out);
			foreach($out[0] as $k=>$v){
				$values=array("mode"=>null);
				$tinout=array();
				preg_match_all('`(\w+)\s*=\s*\"([^\"]*)\"`isU',$out[1][$k],$tinout);
				foreach($tinout[1] as $k2=>$v2){
					if (!isset($values[$v2])) {
						$values[$v2] = array();
					}
					switch($v2){
						case "id":
							$values["id"][] = $tinout[2][$k2];
							$values['comment'][$tinout[2][$k2]]="";
							break;
						case "by_title":
							$values["by_title"][]=$tinout[2][$k2];
							$values['comment'][$tinout[2][$k2]]="";
							break;
						case "comment":
							if(count($values['id']>0))
								$ref=&$values['id'];
							else if(count($values['by_title']))
								$ref=&$values['by_title'];
							else
								break;
							$values["comment"][$ref[count($ref)-1]]=$tinout[2][$k2];
							break;
						default:
							$values[$v2]=$tinout[2][$k2];
					}					
				}
				$params=array();
				if(isset($values["id"])){
					$params["masterandslaves"]=$values["id"];
				}else if(isset($values["by_title"])){
					$params["by_title"]=$values["by_title"];
				}
				if (is_array($values['mode'])) {
					$values['mode'] = $values['mode'];
				}
				if($values['mode']== "mix"){
					$params["order"]="event_startdt ASC";
				}elseif($values['mode']=="evt"){
					$params["order"]="rpt_evnt ASC";						
				}
				$params["event_period"]="notfinished";
				$ehRepeat=isset($core->ehRepeat)?$core->ehRepeat:new ehRepeat(new eventHandler($core));
				$repeats = $ehRepeat->getEvents($params);
				
				global $plural;
				$plural=($repeats->count()>1);
				$title=isset($values["title"])?$values["title"]:__("Next events");
				$title=preg_replace_callback('|\{([^\|]*)\|([^\}]*)\}|', function ($matches){global $plural; return $matches[1+$plural];}, $title);

				$slaves_list="<div><h4>".$title."</h4>\n";
				if ($repeats->count() > 10) {
					$slaves_list.="<ul class='event-slaves'>\n";
				} else {
					$slaves_list.="<ul>\n";
				}
				while($repeats->fetch()){
					if (isset($values['id'])) {
						$comment = $values['comment'][(integer) $repeats->rpt_master];
					} else {
						$comment = $values['comment'][$repeats->post_title];
					}
					$slaves_list.="<li>" . $repeats->getEventDate(__("%A %B, %e"),"startdt") . " " . $comment . "</li>\n";
				}
				$slaves_list.='</ul></div>';
				$txt = str_replace($out[0][$k], $slaves_list, $txt);
			}
			$arr[0] = $txt;
		}
	}

	function coreAfterPostContentFormat ($arr) {
		
		$arr['excerpt_xhtml'] = patchAfterTranslator($arr['excerpt_xhtml']);
		$arr['content_xhtml'] = patchAfterTranslator($arr['content_xhtml']);
		
	}
	
	function patchAfterTranslator ($txt) {
		
		//remplacement suite au passage dans le traducteur Wiki
		$res1=str_ireplace("&#8221;",'"',$txt);
		$res2=str_ireplace('<a href="/event_list" title="/event_list">/event_list</a>','[/event_list]',$res1);
		$res3=preg_replace_callback('`(.*event_list.*)`',function($matches){
			return str_replace(array('<br/>'), '', $matches[1]);
			
		},$res2);
		return $res3;
	
	}


