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

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
	/* Name */				"Event handler recurrent events addon",
	/* Description*/		"Extends EventHandler for repetitive events management",
	/* Author */			"Onurb Teva <dev@taktile.fr>",
	/* Version */			'2015.06.24',
	array(
		'permissions' =>	'usage,contentadmin',
		'priority' =>		1001,
		'type'		=>		'plugin'
	)
);
