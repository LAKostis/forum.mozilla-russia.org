				<div class="inform">
					<fieldset>
						<legend>Personal messages</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Personal Messages</th>
									<td>
										<input type="radio" name="form[pms_enabled]" value="1"<?php if ($pun_config['o_pms_enabled'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[pms_enabled]" value="0"<?php if ($pun_config['o_pms_enabled'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Allow users to send personal messages.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Number of messages</th>
									<td>
										<input type="text" name="form[pms_messages]" size="5" maxlength="5" value="<?php echo $pun_config['o_pms_messages'] ?>" />
										<span>The maximum number of messages a user can have in the inbox. Set to 0 to disable limit.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Messages per page</th>
									<td>
										<input type="text" name="form[pms_mess_per_page]" size="5" maxlength="5" value="<?php echo $pun_config['o_pms_mess_per_page'] ?>" />
										<span>The number of messages to display per page.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>