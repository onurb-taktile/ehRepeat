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
$__autoload['rsEhRepeatPublic'] = dirname(__FILE__).'/inc/lib.eh_dummy.rs.extension.php';
$__autoload['ehRepeat'] = dirname(__FILE__).'/inc/class.ehrepeat.php';
$__autoload['xFrequency'] = dirname(__FILE__).'/inc/lib.xfrequency.php';
$__autoload['ehRepeatRestMethods'] = dirname(__FILE__).'/_services.php';

# parsefreq rest method  (for ajax service)
$core->rest->addFunction('parseFreq',array('ehRepeatRestMethods','parseFreq'));
$core->rest->addFunction('computeDates',array('ehRepeatRestMethods','computeDates'));
$core->rest->addFunction('countSlaves',array('ehRepeatRestMethods','countSlaves'));




