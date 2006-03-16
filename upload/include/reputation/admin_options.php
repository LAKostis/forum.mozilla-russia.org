				<div class="inform">
					<fieldset>
						<legend>Reputation system</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Use reputation system?</th>
									<td>
										<input type="radio" name="form[reputation_enabled]" value="1"<?php if ($pun_config['o_reputation_enabled'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[reputation_enabled]" value="0"<?php if ($pun_config['o_reputation_enabled'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Allow users to give reputation points to other users.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Timeout</th>
									<td>
										<input type="text" name="form[reputation_timeout]" size="5" maxlength="5" value="<?php echo $pun_config['o_reputation_timeout'] ?>" />
										<span>Revoting time in seconds</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>