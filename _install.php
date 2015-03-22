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


if (!defined('DC_CONTEXT_ADMIN')){return;}

# Get new version
$new_version = $core->plugins->moduleInfo('ehRepeat','version');
$old_version = $core->getVersion('ehRepeat');
$eventhandler_version = $core->getVersion('eventHandler');
define('EH_REPEAT_MIN_EH_VERSION',"2015.03.15");
# Compare versions
if (version_compare($old_version,$new_version,'>=')) return;
# Install
if(version_compare($eventhandler_version, EH_REPEAT_MIN_EH_VERSION,'<'))
	throw new Exception (sprintf(__("Eh Dummy requires eventHandler V%s minimum, V%s installed. Please update"),
								  EH_REPEAT_MIN_EH_VERSION,$eventhandler_version));

# Database schema
$t = new dbStruct($core->con,$core->prefix);
$t->eventhandler
	->rpt_master    ('bigint',0,true,null)
	->rpt_freq	('text','',true,null)
	->rpt_exc	('text','',true,null);

# Schema installation
$ti = new dbStruct($core->con,$core->prefix);
$changes = $ti->synchronize($t);

# Settings options
$s = $core->blog->settings->eventHandler;
if(!$s)
	throw new Exception(_("Eh Repeat requires eventHandler"));

$s->put('rpt_active',true,'boolean','Enabled eventHandler ehrepeat addon',false,true);
$s->put('rpt_duration',183,'integer',__('Duration for automatic events generation'),false,true);
$s->put('rpt_sunday_first',true,'boolean',__('Week starts on sunday'),false,true);
$s->put('rpt_replace_enddt',true,'boolean',__('Replace event end date by event duration'),false,true);
$s->put('rpt_minute_step',5,'integer',__('Minutes accuracy'));
# Set version
$core->setVersion('ehRepeat',$new_version);

return true;
