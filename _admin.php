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

$core->addBehavior('adminEventHandlerSettings', array('adminEhRepeat', 'adminEventHandlerSettings'));
$core->addBehavior('adminEventHandlerSettingsSave', array('adminEhRepeat', 'adminEventHandlerSettingsSave'));

if ($core->blog->settings->eventHandler->rpt_active) {
	$core->addBehavior('adminEventHandlerEventsCustomFilterDisplay', array('adminEhRepeat', 'adminEventHandlerEventsCustomFilterDisplay'));
	$core->addBehavior('adminEventHandlerEventsPageCustomize', array('adminEhRepeat', 'adminEventHandlerEventsPageCustomize'));
	$core->addBehavior('coreEventHandlerGetEvents', array('adminEhRepeat', 'coreEventHandlerGetEvents'));
	$core->addBehavior('coreEventHandlerBeforeGetEvents', array('adminEhRepeat', 'coreEventHandlerBeforeGetEvents'));
	$core->addBehavior('adminEventHandlerEventsListHeaders', array('adminEhRepeat', 'adminEventHandlerEventsListHeaders'));
	$core->addBehavior('adminEventHandlerEventsListBody', array('adminEhRepeat', 'adminEventHandlerEventsListBody'));
	$core->addBehavior('adminEventHandlerMinilistCustomize', array('adminEhRepeat', 'adminEventHandlerMinilistCustomize'));
	$core->addBehavior('adminEventHandlerForm', array('adminEhRepeat', 'adminEventHandlerForm'));
	$core->addBehavior('adminEventHandlerHeaders', array('adminEhRepeat', 'adminEventHandlerHeaders'));
	$core->addBehavior('adminEventHandlerFormSidebar', array('adminEhRepeat', 'adminEventHandlerFormSidebar'));
	$core->addBehavior('adminAfterEventHandlerCreate', array('adminEhRepeat', 'adminAfterEventHandlerCreateOrUpdate'));
	$core->addBehavior('adminAfterEventHandlerUpdate', array('adminEhRepeat', 'adminAfterEventHandlerCreateOrUpdate'));
	$core->addBehavior('adminBeforeEventHandlerDelete', array('adminEhRepeat', 'adminBeforeEventHandlerDelete'));
	$core->addBehavior('adminEventHandlerActionsCombo', array('adminEhRepeat', 'adminEventHandlerActionsCombo'));
	$core->addBehavior('adminEventHandlerActionsManage', array('adminEhRepeat', 'adminEventHandlerActionsManage'));
	$core->addBehavior('adminPostsActionsPage', array('adminEhRepeat', 'adminPostsActionsPage'));
	$core->addBehavior('adminEventHandlerCustomEventTpl', array('AdminEhRepeat', 'adminEventHandlerCustomEventTpl'));
	$core->addBehavior('adminEventHandlerCustomEventsTpl', array('adminEhRepeat', 'adminEventHandlerCustomEventsTpl'));
}

class adminEhRepeat {
	# this behavior creates some specific settings for the addon and displays 
	# these new settings on the admin page

	public static function adminEventHandlerSettings() {

		global $active, $core, $s, $section;

		$rpt_active = (boolean) $s->rpt_active;
		$rpt_duration = abs((integer) $s->rpt_duration);
		$rpt_sunday_first = (boolean) $s->rpt_sunday_first;
		$rpt_replace_enddt = (boolean) $s->rpt_replace_enddt;
		$rpt_minute_step = abs((integer) $s->rpt_minute_step);
		if (!$rpt_duration)
			$rpt_duration = 183;

		$combo_duration = array(
			__('One month') => 31,
			__('Two months') => 61,
			__('Three months') => 92,
			__('Six months') => 183,
			__('One year') => 366,
			__('Custom') => 0
		);

		$rpt_duration_custom = (in_array($rpt_duration, array_values($combo_duration)) ? '' : $rpt_duration);
		if ($rpt_duration_custom != '')
			$rpt_duration = 0;

		$combo_minute_step = array(
			__('minute') => 1,
			__('5 minutes') => 5,
			__('quarter hour') => 15,
			__('half hour') => 30,
			__('hour') => 60
		);

		include(dirname(__FILE__) . '/tpl/settings.tpl');
	}

	# this behavior handles the saving of the addon's specific settings 
	# works with adminEventHandlerSettings

	public static function adminEventHandlerSettingsSave() {
		global $s;
		$rpt_active = !empty($_POST['rpt_active']);
		$rpt_duration = $_POST['rpt_duration'];
		$rpt_sunday_first = !empty($_POST['rpt_sunday_first']);
		$rpt_replace_enddt = !empty($_POST['rpt_replace_enddt']);
		$rpt_minute_step = $_POST['rpt_minute_step'];

		if ($rpt_duration == '0') {
			if (!empty($_POST['rpt_duration_custom']) && ((int) $_POST['rpt_duration_custom']) > 0) {
				$rpt_duration = (int) $_POST['rpt_duration_custom'];
			} else {
				$rpt_duration = 183;
				dcPage::addWarningNotice(sprintf(
								__('Defaulting to 183, «%s» is not a correct value.'), $_POST['rpt_duration_custom']
				));
			}
		}

		$s->put('rpt_active', $rpt_active, 'boolean');
		$s->put('rpt_duration', $rpt_duration, 'integer');
		$s->put('rpt_sunday_first', $rpt_sunday_first, 'boolean');
		$s->put('rpt_replace_enddt', $rpt_replace_enddt, 'boolean');
		$s->put('rpt_minute_step', $rpt_minute_step, 'integer');
	}

	#this behavior displays a custom filter on the Events page
	#works with adminEventHandlerEventsPageCustomize

	public static function adminEventHandlerEventsCustomFilterDisplay() {
		global $rpt_filter, $rpt_filter_combo;
		?>
		<p><label for="status" class="ib"><?php echo __('Display:'); ?></label>
			<?php echo form::combo('rpt_filter', $rpt_filter_combo, $rpt_filter); ?>
		</p>
		<?php
	}

	# this behavior allows to customize several aspects of the Events page :
	# $args is an array of by reference parameters :
	# $params, $sortby_combo, $show_filters, $redir, $hidden_fields
	# other variables can be created as global for later use 
	#	filtercombos to use in adminEventHandlerEventsCustomFilterDisplay e.g.

	public static function adminEventHandlerEventsPageCustomize($args) {
		foreach ($args as $v => $k)
			$$v = &$args[$v];

		global $core, $rpt_filter;
		$rpt_filter = !empty($_GET['rpt_filter']) ? $_GET['rpt_filter'] : 1;
		if (!$core->error->flag()) {
			global $rpt_filter_combo;
			$rpt_filter_combo = array(__('Master and regular') => 1, __('Repetitive events') => 2, __('All') => 3);
		}

		# - Selected filter
		if ($rpt_filter == 1) {
			$params['noslaves'] = true;
			$show_filters = false;
		} else if ($rpt_filter == 2) {
			$params['repetitives'] = true;
			$show_filters = true;
		} else {
			$show_filters = true;
		}

		$redir = $redir . '&amp;rpt_filter=' . $rpt_filter;

		$hidden_fields = $hidden_fields . form::hidden(array('rpt_filter'), $rpt_filter);

		$sortby_combo[__("Repetitive events")] = 'rpt_evnt';

		$params['sortby'] = !empty($_GET['sortby']) ? $_GET['sortby'] : null;
	}

	#this behavior is used to customize the event mini list displayed during
	#event binding to posts process.
	#the parameter is array('params'=>&$params)

	public static function adminEventHandlerMinilistCustomize($args) {
		foreach ($args as $v => $k)
			$$v = &$args[$v];

		#$params['dummy']=1; This would filter the events displayed on the dummy==1 criteria
	}

	# this behavior is for getEvents records manipulation, generally applying some
	# extensions.

	public static function coreEventHandlerGetEvents($rs) {
		$rs->ehRepeat = new ehRepeat($rs->eventHandler);
		$rs->extend('rsEhRepeatPublic');
	}

	#this behavior is used to set some specific addons settings before getting events
	#the parameters are array(&$params) and the eventHandler object instance.

	public static function coreEventHandlerBeforeGetEvents($eh, $args) {
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
			$params['sql'] .= " AND EH.rpt_master = '" . $eh->con->escape($params['master']) . "' ";
			unset($params['master']);
		}

		/* to get the slaves, give master_id in $params['slaves'] */
		if (!empty($params['slaves'])) {
			$params['sql'] .= " AND EH.post_id != EH.rpt_master AND EH.rpt_master = " . $params['slaves'];
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
			$params['sql'] .= " AND EH.rpt_master = " . $params['masterandslaves'];
		}

		/* to get masters and slaves */
		if (!empty($params['repetitives'])) {
			$params['sql'] .= " AND EH.rpt_master IS NOT NULL";
		}

		if (!empty($params['sortby']) && $params['sortby'] == 'rpt_evnt') {
			$col = (array) $params['columns'];
			$col[] = 'S.master';
			$col[] = 'S.status';
			$col[] = 'C.nbslaves';
			$params['columns'] = $col;

			$params['from'].=" INNER JOIN (SELECT post_id as id, rpt_master as master, if(rpt_master IS NULL,'-', if(rpt_master = post_id,'M','S')) as status FROM  dc_eventhandler) S on S.id = P.post_id LEFT JOIN (SELECT CEH.rpt_master as master, count(distinct CP.post_id) AS nbslaves FROM dc_post CP INNER JOIN dc_eventhandler CEH ON  CEH.post_id = CP.post_id WHERE CP.blog_id = 'default' AND  CP.post_type = 'eventhandler' and CEH.rpt_master != CP.post_id AND CEH.rpt_master=4) C on C.master = P.post_id";
			$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';

			if (!empty($params['repetitives'])) {
				$params['order'] = 'EH.rpt_master ' . $order . ', S.status ' . $order . ', P.post_dt ' . $order;
			} else {
				$params['order'] = 'S.status ' . $order . ', EH.rpt_master ' . $order . ', P.post_dt ' . $order;
			}
			unset($params['repetitives']);
			unset($params['masterandslaves']);
			unset($params['sortby']);
		}
	}

	#this behavior is used to perform some specific addon's actions on the database cursor
	#the parameters are $eh, the eventHandler object instance, $post_id, $cur_post and $cur_event

	public static function coreEventHandlerGetEventCursor($eh, $post_id, $cur_post, $cur_event) {
		
	}

	#this behavior is set to do something before an event is deleted.

	public static function coreEventHandlerEventDelete($eh, $post_id) {
		#do something before event deletion
	}

	#this behavior is set to do something before an event is created (set some addon's customs fields e.g.)
	#the parameters are $eh, the eventHandler object instance, and array(&$cur_post, &cur_event)

	public static function coreEventHandlerBeforeEventAdd($eh, $cur_post, $cur_event) {
		
	}

	#this behavior is set to do something after an event is created
	#the parameters are $eh, the eventHandler object instance, the new event $post_id, $cur_post and $cur_event

	public static function coreEventHandlerAfterEventAdd($eh, $post_id, $cur_post, $cur_event) {
		
	}

	#this behavior is set to do something before an event is updated (set some addon's customs fields e.g.)
	#the parameters are $eh, the eventHandler object instance, $cur_post and $cur_event

	public static function coreEventHandlerBeforeEventUpdate($eh, $cur_post, $cur_event) {
		
	}

	# this behavior permits events list headers manipulation.
	# the parameter is array('columns'=>&$colums). $columns is an array containing the html
	# or when called from Minilist, the parameter is array('minicols'=>&$columns).
	# for the list header (<th> cells); You can insert or delete some but be careful
	# to do the same in adminEventHandlerEventsListBody to get a coherent table.

	public static function adminEventHandlerEventsListHeaders($args, $ismini = false) {
		$columns = &$args['columns'];
		$num = 3; //Insert a new column header @3rd position.
		if ($ismini)
			$num++;#Minilist adds a 'period' column so we increase $num
		$columns = array_merge(array_slice($columns, 0, $num), array('<th>' . __('Rep.') . '</th>'), array_slice($columns, $num));
	}

	# this behavior permits events list lines manipulation.
	# the parameter is array('columns'=>&$colums). $columns is an array containing the html
	# or when called from Minilist, the parameter is array('minicols'=>&$columns).
	# for the list line (<td> cells); You can insert or delete some but be careful
	# to do the same in adminEventHandlerEventsListHeader to get a coherent table.

	public static function adminEventHandlerEventsListBody($rs, $args, $ismini = false) {
		$columns = &$args['columns'];
		$num = 3; //Insert a new column header @3rd position.
		if ($ismini)
			$num++;#Minilist adds a 'period' column so we increase $num
		$rep = $rs->countSlaves();
		if ($rep == -2) {
			$rep = __('G');
		} else if ($rep == -1) {
			$rep = __('N/A');
		}
		$columns = array_merge(array_slice($columns, 0, $num), array("<td>" . $rep . "</td>"), array_slice($columns, $num));
		if ($rep == __('G')) {
			foreach ($columns as $i => $col) {
				$columns[$i] = preg_replace("/(<td[^>]*)(>)/", "\\1 style=\"background-color:#ffffcc !important;font-style:italic;\"\\2", $col);
			}
		}
	}

	#this behavior is for action combo manipulation. 
	#the parameter is array(&$action_combo)

	public static function adminPostsActionsPage($core, $ap) {
		if ($ap->getURI() != 'plugin.php')
			return;#prevents the menu to be added on posts list.
		$ap->addAction(
				array(__('Repeat events') => array(
				__('Regenerate all events from today') => 'generate',
				__('Make unique') => 'mkunique'
			)), array('adminEhRepeat', 'doChangeRepeat')
		);
	}

	#This callback is called when the action combo is used on post action page

	public static function doChangeRepeat($core, dcPostsActionsPage $ap, $post) {
		global $eventHandler;
		try {
			if (!$eventHandler) {
				$eventHandler = new eventHandler($core);
			}
			$ehRepeat = new ehRepeat($eventHandler);

			if ($ap->getAction() == 'generate') {
				foreach ($ap->getIDs() as $entry) {
					$masterdtchanged = false;
					$count = $ehRepeat->generateSlaves($entry, $masterdtchanged);
					if ($masterdtchanged) {
						dcPage::addWarningNotice(sprintf(__("Event %d was outdated, it has been updated."), $entry));
					}
					if ($count == -1) {
						dcPage::addWarningNotice(sprintf(
										__("Could not generate repetitive events for event %d"), $entry));
					} else {
						dcPage::addSuccessNotice(sprintf(
										__("%d repetitive event generated for event %d", '%d repetitive events generated for event %d', $count), $count, $entry));
					}
				}
			} else if ($ap->getAction() == 'mkunique') {
				//Switches a slave event to a regular one (and creates an exception for the parent event to avoid 
				//double event on the same date)
				foreach ($ap->getIDs() as $entry) {
					$res = $ehRepeat->freeSlaveEvent($entry);
					if (!$res) {
						dcPage::addWarningNotice(sprintf(
										__("Could not make event %d unique, not a repeated event"), $entry));
					} else {
						dcPage::addSuccessNotice(sprintf(
										__("Event %d was successfully made unique"), $entry));
						$masterdtchanged = false;
						$count = $ehRepeat->generateSlaves($entry, $masterdtchanged);
						if ($masterdtchanged) {
							dcPage::addWarningNotice(sprintf(__("Event %d was outdated, it has been updated."), $entry));
						}
						if ($count == -1) {
							dcPage::addWarningNotice(sprintf(
											__("Could not generate repetitive events for event %d"), $entry));
						} else {
							dcPage::addSuccessNotice(sprintf(
											__("%d repetitive event generated for event %d", '%d repetitive events generated for event %d', $count), $count, $entry));
						}
					}
				}
			}
			$ap->redirect(false);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	/* This behavior inserts some content just below the eventhandler specific part on event creation/edition page
	 *  $post parameter is not null when on an edition page
	 */

	public static function adminEventHandlerForm($post) {
		global $core, $s, $can_edit_post;

		$sunday_first = isset($s->rpt_sunday_first) ? $s->rpt_sunday_first : false;

		$rpt_active = false;
		$sunday_first = isset($s->sunday_first) ? $s->sunday_first : false;
		$rpt_freq = "0 0 * * * *";
		$rpt_exc = array();
		$rpt_freq_protected = false;
		$rpt_slave = false;

		if (isset($post) && $post->isRepeat()) {
			$rpt_active = true;
			$rpt_freq = $post->rpt_freq;
			$rpt_exc = explode(';', $post->rpt_exc);
			$rpt_freq_protected = $rpt_active;
			$rpt_slave = $post->isSlave();
		}

		if (!empty($_POST) && $can_edit_post) {
			$rpt_active = !empty($_POST['rpt_active']);
			$rpt_freq = isset($_POST['rpt_freq']) ? $_POST['rpt_freq'] : $rpt_freq;
			$rpt_exc = isset($_POST['rpt_exc']) ? explode(';', $_POST['rpt_exc']) : $rpt_exc;
			$rpt_freq_protected = true;
		}

		$combo_days = array(__('Choose day') => '*');
		for ($i = 1; $i <= 31; $i++) {
			$day = sprintf("%'.02d", $i);
			$combo_days[$day] = $day;
		}
		$combo_months = array(__('Choose month') => '*');
		for ($i = 1; $i <= 12; $i++) {
			$month = sprintf("%'.02d", $i);
			$month_name = strftime("%B", mktime(0, 0, 0, $i, 1, 0));
			$combo_months[__($month_name)] = $month;
		}

		$combo_weekdays = array(__('day') => '*');
		for ($i = 0; $i < 7; $i++) {
			$numday = (($sunday_first) ? $i : $i + 1) % 7;
			$day_name = strftime("%A", mktime(0, 0, 0, 2, $numday + 1, 2015));
			$combo_weekdays[__($day_name)] = (string) $numday;
		}

		include('tpl/event_editor.tpl');
	}

	/* This behavior inserts some content just below the eventhandler specific part on event creation/edition page sidebar
	 *  $post parameter is not null when on an edition page
	 */

	public static function adminEventHandlerFormSidebar($post) {
		
	}

	/* this behavior is used to perform some specific actions after event creation */

	public static function adminAfterEventHandlerCreateOrUpdate($cur_post, $cur_event, $post_id) {
		global $eventHandler, $ehRepeat, $core;
		try {
			if (!isset($ehRepeat))
				$ehRepeat = new ehRepeat($eventHandler);

			$rpt_master = isset($_POST['rpt_active']) ? (integer) $post_id : null;
			$rpt_freq = isset($_POST['rpt_freq']) ? $_POST['rpt_freq'] : null;
			$rpt_exc = isset($_POST['rpt_exc']) ? $_POST['rpt_exc'] : null;

			if (isset($_POST['rpt_active']) && $rpt_freq == null)
				throw (new Exception(__("You have to provide a frequency for a recurrent event.")));
			$cur_event->rpt_freq = $rpt_freq;
			$cur_event->rpt_exc = preg_replace('/[\r\n]{1,2}/', ';', $rpt_exc);
			$cur_event->rpt_master = $rpt_master;
			$eventHandler->updEvent($post_id, $cur_post, $cur_event);
			if ($rpt_master) {
				$oldmasterrenewed = false;
					$count = $ehRepeat->generateSlaves($rpt_master, $masterdtchanged);
					if ($masterdtchanged) {
						dcPage::addWarningNotice(sprintf(__("Event %d was outdated, it has been updated."), $rpt_master));
					}
					if ($count == -1) {
						dcPage::addWarningNotice(sprintf(
										__("Could not generate repetitive events for event %d"), $rpt_master));
					} else if($count>0) {
						dcPage::addSuccessNotice(sprintf(
										__("%d repetitive event generated for event %d", '%d repetitive events generated for event %d', $count), $count, $rpt_master));
					}
			}
		} catch (Exception $e) {
			/* we have to reverse the creation as something went wrong */
			try {
				//$eventHandler->delEvent($post_id);
			} catch (Exception $e1) {
				throw new Exception($e1->getMessage(), 0, $e);
			}
			throw $e;
		}
	}

	/* this behavior is used to perform some specific actions before event deletion */

	public static function adminBeforeEventHandlerDelete($post_id) {
		global $eventHandler, $ehRepeat;
		if (!isset($ehRepeat))
			$ehRepeat = new ehRepeat($eventHandler);

		$ehRepeat->deleteSlaves($post_id);
	}

	/* this behavior is used to manipulate the actions combo for the index_events.php page */

	public static function adminEventHandlerActionsCombo($combo_actions) {
		$combo_actions[0][__('Repeat Events')] = array(
			__('Regenerate all events from today') => 'generate',
			__('Make unique') => 'mkunique'
		);
	}

	/* this behavior is the place for events actions management */

	public static function adminEventHandlerActionsManage(eventHandler $eh, $action) {
		if ($action != 'generate' && $action != 'mkunique')
			return;
		global $core, $eventHandler;
		if (isset($_REQUEST['redir'])) {
			$u = explode('?', $_REQUEST['redir']);
			$uri = $u[0];
			if (isset($u[1])) {
				parse_str($u[1], $args);
			}
			$args['redir'] = $_REQUEST['redir'];
		} else {
			$uri = $core->getPostAdminURL($from_post->post_type, $from_post->post_id);
			$args = array();
		}
		$_REQUEST['action'] = $action;
		$posts_actions_page = new dcPostsActionsPage($core, $uri, $args);
		$posts_actions_page->setEnableRedirSelection(false);
		if (!$eventHandler) {
			$eventHandler = $eh;
		}
		$posts_actions_page->process();
	}

	/* @func AdminEhRepeat::adminEventHandlerCustomEventTpl
	 * This behavior can be used to include a custom tpl as event editor.
	 * Don't forget to exit or the default tpl will be loaded as well.
	 * You should probably use the behaviors to change the page instead of 
	 * setting a new one. Use only if desperate.	 
	 */

	public static function adminEventHandlerCustomEventTpl() {
		
		global $message;
		$message.= dcPage::notices();
		#include(dirname(__FILE__).'/tpl/custom_event.tpl');
		#exit;
	}

	/* @func adminEhRepeat::adminEventHandlerCustomEventsTpl
	 * This behavior can be used to include a custom tpl for events list.
	 * Don't forget to exit or the default tpl will be loaded as well.
	 * You should probably use the behaviors to change the page instead of 
	 * setting a new one. Use only if desperate.
	 */

	public static function adminEventHandlerCustomEventsTpl() {
		#include(dirname(__FILE__).'/tpl/custom_events.tpl');
		#exit;		
	}

	public static function adminEventHandlerHeaders() {
		global $s;
		return
				'<link rel="stylesheet" type="text/css" href="index.php?pf=ehRepeat/style.css" />' .
				"<script type=\"text/javascript\">\n//<![CDATA[\n\tvar rpt_replace_enddt=" . ($s->rpt_replace_enddt ? "true" : "false") . ";\n\tvar rpt_minute_step=" . $s->rpt_minute_step . ";\n//]]></script>" .
				dcPage::jsLoad('index.php?pf=ehRepeat/js/sprintf.js') .
				dcPage::jsLoad('index.php?pf=ehRepeat/js/myfuncs.js') .
				dcPage::jsLoad('index.php?pf=ehRepeat/js/xfrequency.js') .
				dcPage::jsGettext(dirname(__FILE__), 'index.php?pf=ehRepeat', 'js', 'event.js', 'dc_messages') .
				dcPage::jsGettext(dirname(__FILE__), 'index.php?pf=ehRepeat', 'js', 'eventeditor.js', 'dc_messages');
	}

}
