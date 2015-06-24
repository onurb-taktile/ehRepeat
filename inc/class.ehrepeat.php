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

class ehRepeat extends eventHandler {
	
	function __construct(eventHandler $e) {
		$this->core = $e->core;
		$this->con = $e->con;
		$this->type = $e->type;
		$this->table = $e->table;
		$this->blog = $e->blog;
		$this->settings = $this->core->blog->settings->eventHandler;
		$this->eventHandler = $e;
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public function getType(){
		return $this->type;
	}
/*
 * Clones the event event_id applying changes.
 * if clone_id is given, updates $clone_id from event_id applying the changes
 */
	protected function cloneEvent($event_id, $changes = null,$clone_id=null) {
		global $core;
		$params = parent::cleanedParams(array());
		$params['post_id'] = $event_id;
		$event = parent::getEvents($params);

		if ($event->isEmpty())
			throw new Exception(sprintf(__("%s : Can't find event %d."), "xEventHandler::cloneEvent", $event_id), 1);

		$cur_post = $core->con->openCursor($core->prefix . 'post');
		$cur_event = $core->con->openCursor($core->prefix . 'eventhandler');
		
		//create and populate db cursor
		$cur_post->user_id = $event->user_id;
		$cur_post->cat_id = $event->cat_id;
		$cur_post->post_format = $event->post_format;
		$cur_post->post_password = $event->post_password;
		$cur_post->post_lang = $event->post_lang;
		$cur_post->post_title = $event->post_title;
		$cur_post->post_excerpt = $event->post_excerpt;
		$cur_post->post_excerpt_xhtml = $event->post_excerpt_xhtml;
		$cur_post->post_content = $event->post_content;
		$cur_post->post_content_xhtml = $event->post_content_xhtml;
		$cur_post->post_notes = $event->post_notes;
		$cur_post->post_status = $event->post_status;
		$cur_post->post_selected = $event->post_selected;
		$cur_post->post_open_comment = 0;
		$cur_post->post_open_tb = 0;

		$cur_event->event_startdt = $event->event_startdt;
		$cur_event->event_enddt = $event->event_enddt;
		$cur_event->event_address = $event->event_address;
		$cur_event->event_latitude = $event->event_latitude;
		$cur_event->event_longitude = $event->event_longitude;
		
		foreach ($changes as $field => $value) {
			$matches = array();
			if (preg_match("/(\w+)->(\w+)/", $field, $matches))
				${$matches[1]}->{$matches[2]} = $value;
			else
				${$field} = $value;
		}
		if($clone_id === null)
			return $this->addEvent($cur_post, $cur_event);
		else
			return $this->updEvent ($clone_id, $cur_post, $cur_event);
	}

	public function getMaster($master_id) {
		return $this::getEvents(array('master' => $master_id));
	}

	public function getSlaves($master_id) {
		return $this::getEvents(array('slaves' => $master_id,'order' => 'post_id ASC'));
	}
	
	//Given a post_id part of a repetitive event, returns the master and the slaves
	public function getMasterAndSlaves($post_id) {
		$rs=$this::getEvents(array('post_id'=>$post_id));
		if($rs && $rs->count()==1 && $rs->rpt_master){
			$master_id=$rs->rpt_master;
			return $this::getEvents(array('masterandslaves'=>$master_id));
		}else{
			return $rs;
		}
	}
	
	/*
	 * 
	 * forceupdate param is to speadup the execution skipping the outdated check (if allready performed by caller)
	 */
	public function generateSlaves($master_id,&$masterdtchanged,$forceupate=true) {
		if ($master_id === null)
			return;
		$master = $this->getMaster($master_id);
		if (!$master->count() > 0)
			return -1;
		$t_slaves_startdt = strtotime($master->event_startdt);
		$freq=$master->getFreq();
		$xfreq = new xFrequency($freq,$this->settings->rpt_sunday_first);
		$slaveDates = $xfreq->computeDates($t_slaves_startdt, $this->settings->rpt_duration, $master->getExc());
		$theSlaves = $this->getSlaves($master_id);

		//if $master is old, set its start_dt to the next slave date
		if ($forceupate || $master->getPeriod() == 'finished' || $theSlaves->count()!=count($slaveDates)) {
			//first we clean exc, removing past dates
			$exc=($master->rpt_exc!=""?explode(';',$master->rpt_exc):array());
			$newexc=array();
			foreach($exc as $i=>$e){
				if(strtotime($e)>=time()){
					$newexc[]=$e;
				}
			}
			$t_duration=strtotime($master->event_enddt) - strtotime($master->event_startdt);
			
			$t_startdt = array_shift($slaveDates);
			$this->updateMasterEvent($master_id,$t_startdt,$t_duration,$freq,join(";",$newexc));
			$masterdtchanged=true;		
		}
		
		if(count($slaveDates)>0 && $slaveDates[0]==$t_slaves_startdt){
			array_shift($slaveDates);
		}

		$slaves = array();
		try{
			foreach ($slaveDates as $t_startdt) {
				$slave_id=null;
				if($theSlaves->fetch()){
					$slave_id=$theSlaves->post_id;
				}
				$slaves[]=$this->createSlaveEvent($master_id, $t_startdt,$slave_id,count($slaves));
			}
			//We delete the extra slaves :
			$slavestokill=array();
			while($theSlaves->fetch()){
				$slavestokill[]=$theSlaves->post_id;
			}
			foreach($slavestokill as $slavetokill){
				$this->eventHandler->delEvent($slavetokill);
			}
			return count($slaves);
		} catch (Exception $e) {
			//if something was wrong and we are creating, we destroy the generated
			// slaves
			foreach ($slaves as $slave_id) {
				if ($slave_id)
					$this->delEvent($slave_id);
			}
			throw $e;
		}
	}

	/*
	 * Kill the outdated masters and the older slave becomes the new master
	 */
	
	public static function doTheRevolution(){
		global $core,$ehRepeat;
		if($ehRepeat===null)
			$ehRepeat=new ehRepeat(new eventHandler($core));
		$that=$ehRepeat;
		$masters = $that->getEvents(array("masters"=>1,"event_period"=>"finished"));
		while($masters->fetch()){
			$changed=false;
			$that->generateSlaves($masters->post_id, $changed,true);
		}
	}
	
	public function deleteSlaves($master_id) {
		//Get slaves for $this->master_id
		$slaves = $this->getSlaves($master_id);
		if ($slaves->count() == 0)
			return;
		//and delete them
		foreach ($slaves as $slave) {
			$this->delEvent($slave->post_id);
		}
	}
	
	protected function updateMasterEvent($master_id, $t_startdt,$t_duration,$freq,$exc){
		$params=array(
			'cur_post->post_dt' => date('Y-m-d H:i:00', time()),
//			'cur_post->post_dt' => date('Y-m-d H:i:00', $t_startdt),
			'cur_event->event_startdt' => date('Y-m-d H:i:00', $t_startdt),
			'cur_event->event_enddt' => date('Y-m-d H:i:00', $t_startdt + $t_duration),
			'cur_event->rpt_master' => $master_id,
			'cur_event->rpt_freq' => $freq,
			'cur_event->rpt_exc' => $exc
		);
		return $this->cloneEvent($master_id,$params,$master_id);
	}

	protected function createSlaveEvent($master_id, $t_slave_startdt, $slave_id=null, $number) {
		//get the master Event
		$master = $this->getMaster($master_id);

		$t_master_duration = strtotime($master->event_enddt) - strtotime($master->event_startdt);
		$params=array(
			'cur_post->post_title' => $master->post_title,
			'cur_post->post_content' => $master->post_content,
			'cur_post->post_excerpt' => $master->post_excerpt,
			'cur_post->post_excerpt_xhtml'=>$master->post_excerpt_xhtml,
			'cur_post->post_content_xhtml'=>$master->post_content_xhtml,
			'cur_post->post_notes'=>$master->post_notes,
			'cur_post->post_status'=>$master->post_status,
			'cur_post->cat_id'=>$master->cat_id,
			'cur_post->post_dt' => date('Y-m-d H:i:00', time()),
//			'cur_post->post_dt' => date('Y-m-d H:i:00', $t_slave_startdt),
			'cur_event->event_startdt' => date('Y-m-d H:i:00', $t_slave_startdt),
			'cur_event->event_enddt' => date('Y-m-d H:i:00', $t_slave_startdt + $t_master_duration),
			'cur_event->rpt_master' => $master_id,
			'cur_event->rpt_freq' => $master->rpt_freq,
			'cur_event->rpt_exc' => $master->rpt_exc,
			'cur_post->post_url' => sprintf("%s-%03d",$master->post_url,$number)
		);
		return $this->cloneEvent($master_id,$params,$slave_id);
	}

	public function freeSlaveEvent($slave_id) {
		global $core;
		$slave = $this::getEvents(array('post_id' => $slave_id));
		if($slave->count()==0 || !$slave->isSlave())
			return false;
		$this->con->begin();
		try {
			//first, get master and add an exception rule for slave startdt
			$master = $this::getEvents(array('master' => $slave->rpt_master));
			$cur_event = $core->con->openCursor($core->prefix . 'eventhandler');
			$cur_event->rpt_exc = $master->rpt_exc . ';' . date('Y-m-d H:i', strtotime($slave->event_startdt));
			$cur_event->update("WHERE post_id = '" . $master->post_id . "' ");

			//then, set the slave free
			$cur_event = $core->con->openCursor($core->prefix . 'eventhandler');
			$cur_event->rpt_master = null;
			$cur_event->rpt_freq = null;
			$cur_event->rpt_exc = null;

			$cur_event->update("WHERE post_id = '" . $slave_id . "' ");
		} catch (Exception $e) {
			$this->con->rollback();
			throw $e;
		}
		$this->con->commit();
		return true;
	}
}
