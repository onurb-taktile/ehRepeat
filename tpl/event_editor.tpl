<div id="ehrepeat-editor"><p id="ehrepeat-editor-title"> <?php echo form::checkbox('rpt_active', '1', $rpt_active) . __('Repetitive event'); ?> </p><p class="error" style="display:none;"></p>
	<div id="ehrepeat-editor-content" style="display:none;">
		<div id="rpt-freq-editor">
			<div class="two-cols">
				<div class="col">
					<label><?php echo __("Rules"); ?></label>
					<div class="fieldset">
						<div style="position:relative;">
							<p class="clear" id="rpt-rules-editor-header">
								<button type="button" id="rpt-rule-add" title="<?php echo __("Add a rule line"); ?>">+</button>
							</p>
							<div id="rpt-display"><!-- placeholder for the rules-->
							<p class="rpt-display-template">
								<input type="hidden" />
								<span></span>
								<button type="button" title="<?php echo __("Delete this line"); ?>" disabled="disabled">-</button>
							</p>
							</div>
						<div id="rpt-rules-editor" style="display:none;">
							<select id="xered-mode">
								<option value="" selected="selected"><?php echo __("Rule"); ?></option>
								<option value="+"><?php echo __("Include"); ?></option>
								<option value="-"><?php echo __("Exclude"); ?></option>
							</select>
							<select id="xered-action">
								<option value="" selected="selected"><?php echo __("Action :"); ?>
								<option value="all"><?php echo __("All"); ?></option>
								<option value="week"><?php echo __("Week"); ?></option>
								<!--<option value="days"><?php echo __("Day"); ?></option>-->
								<option value="months"><?php echo __("Month"); ?></option>
								<option value="date"><?php echo __("Date"); ?></option>
							</select>
							<span> = </span>
							<span id="xered-ac-all"><?php echo __("All days "); ?></span>
							<!--The values will be set with JS-->
							<select id="xered-ac-weeks"> 
								<option value="*" selected="selected"></option>
								<option value="1"></option>
								<option value="2"></option>
								<option value="3"></option>
								<option value="4"></option>
								<option value="5"></option>
							</select>
							<span id="xered-ac-week-sep"><?php echo __("on"); ?></span>
							<?php
							echo form::combo('xered-ac-weekdays', $combo_weekdays, '');
							?>
							<span id="xered-ac-week-end"><?php echo __("of the month"); ?></span>
							<?php
							echo form::combo('xered-ac-days', $combo_days);
							echo form::combo('xered-ac-months', $combo_months);
							?>
							<input type="date" id="xered-ac-date"/>
							<button type="button" id="xered-validate" title="<?php echo __("Validate rule"); ?>">
								<?php echo __("Ok") ?>
							</button>
						</div></div>
					</div>
					<div id="rpt_result" style="display:none;"></div>
				</div>
				<div class="col">
					<label id="rpt-slaves-title"><?php echo __('These settings will generate the following dates:'); ?> - <span style="display:inline;">&nbsp</span></label>
					<div class="fieldset">
						<div id="rpt-slaves-dates">
						</div>
					</div>
					<div class="fieldset" style="display:none;">
						<p><label for='rpt_freq'><?php echo __('Frequency format string'); ?></label><?php echo form::field('rpt_freq', 50, 255, $rpt_freq, 'maximal', 0, false, /* $rpt_freq_protected ? " protected='protected'" : "" */ ""); ?> </p>
						<p><label for='rpt_exc'><?php echo __('Exceptions strings'); ?></label><?php echo form::textArea('rpt_exc', 15, 5, implode("\n", $rpt_exc)); ?></p>
						<p id="rpt_freq_error"> &nbsp; </p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>