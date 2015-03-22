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
	}

	protected function cloneEvent($event_id, $changes = null) {
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
		return $this->addEvent($cur_post, $cur_event);
	}

	public function getMaster($master_id) {
		return this::getEvents(array('master' => $master_id));
	}

	public function getSlaves($master_id) {
		return this::getEvents(array('slaves' => $master_id));
	}

	public function generateSlaves($master_id) {
		$date = getdate();
		$master = $this->getMaster($master_id);
		if (!$master->count() > 0)
			return -1;
		$xfreq = new xFrequency($master->getFreq());
		$slaves_startdt = strtotime($master->event_startdt);
		$now = time();
		if ($slaves_startdt < $now)
			$slaves_startdt = $now;
		$slaves_range = ($core->blog->settings->eventHandler->rpt_duration ? $core->blog->settings->eventHandler->rpt_duration : 183) * 24 * 3600;
		$slaves_enddt = $slaves_startdt + $slaves_range;
		$slaveDates = $xfreq->computeDates($slaves_startdt, $slaves_enddt);

		//first, we delete all existing slaves
		$this->deleteSlaves($master_id);
		$slaves = array();
		try {
			foreach ($slaveDates as $slave_startdt) {
				$slaves[] = $this->createSlaveEvent($master_id, $slave_startdt);
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

	protected function createSlaveEvent($master_id,$slave_startdt)
	{
		//get the master Event
		$master = $this->getMaster($master_id);

		$master_duration = strtotime($master->event_enddt) - strtotime($master->event_startdt);

		$slave_id = $this->cloneEvent($master_id,array(
			'cur_post->post_dt' => date('Y-m-d H:i:00',strtotime($slave_startdt)),
			'cur_event->event_startdt' => date('Y-m-d H:i:00',strtotime($slave_startdt)),
			'cur_event->event_enddt' => date('Y-m-d H:i:00',strtotime($slave_startdt) + $master_duration),
			'cur_event->rpt_master' => $master_id,
			'cur_event->rpt_freq' => $master->rpt_freq,
			'cur_event->rpt_exc' => $master->rpt_exc
		));

		return $slave_id;
	}

}
