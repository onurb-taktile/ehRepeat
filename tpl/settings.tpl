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
?>
<?php if($active):?>
<div class="multi-part" id="ehRepeat" title="<?php echo __('Eh Repeat'); ?>">
	<h3><?php echo __('ehRepeat activation'); ?></h3>
	<p>
		<label class="classic">
			<?php echo form::checkbox(array('rpt_active'), '1', $rpt_active).' '.__('Enable ehRepeat Addon'); ?>
		</label>
	</p>
	<?php if ($rpt_active): ?>
	<div class="fieldset">
		<p><?php echo __('Duration for automatic events generation'); ?>
			<label class="classic">
				<?php echo form::combo('rpt_duration', $combo_duration, $rpt_duration).form::field('rpt_duration_custom', 3, 3, $rpt_duration_custom, '', '', false, "style='display:none;'");?>
				<script>
				$(document).ready(function(){
					$('#rpt_duration').change(function(){
						if($(this).val()==0)
							$('#rpt_duration_custom').show();
						else
							$('#rpt_duration_custom').hide().val('');
					}).change();
				});
				</script>
			</label>
		</p>
		<p><?php echo __('Week starts on sunday'); ?>
			<label class="classic">
				<?php echo form::checkbox(array('rpt_sunday_first'), '1', $rpt_sunday_first); ?>
			</label>
		</p>
		<p><?php echo __('Replace event end date by event duration'); ?>
			<label class="classic">
				<?php echo form::checkbox(array('rpt_replace_enddt'), '1', $rpt_replace_enddt); ?>
			</label>
		</p>
		<p><?php echo __('Minutes accuracy'); ?>
			<label class="classic">
				<?php echo form::combo('rpt_minute_step', $combo_minute_step, $rpt_minute_step); ?>
			</label>
		</p>
		
	</div>
	<?php endif; ?>
</div>
<?php endif;?>