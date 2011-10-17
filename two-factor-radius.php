<?php
/*
	Plugin Name: Two-Factor RADIUS Authentication
	Plugin URI: http://github.com/hgfischer/wp_two-factor_radius
	Description: Two-Factor Authentication for your WordPress site using RADIUS
	Version: 0.0.1
	Author: Herbert G. Fischer
	Author URI: http://hgfischer.wordpress.com
	License: GNU General Public License (GPL) version 2

	Copyright 2011  Herbert G. Fischer  (email : herbert.fischer@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1335  USA
*/

class TwoFactorRadiusAuth
{
	function getOptions()
	{
		$opts = get_option('radius_auth_options');
		if (!is_array($opts)) {
			$opts['max_tries'] = 3;
			$opts['timeout'] = 3;
			$opts['s1_host'] = '';
			$opts['s1_port'] = 1812;
			$opts['s1_secr'] = '';
			$opts['s2_host'] = '';
			$opts['s2_port'] = 1812;
			$opts['s2_secr'] = '';
			$opts['pwd_otp_sep'] = '~';
			$opts['skip_users'] = array('admin');
			update_option('radius_auth_option', $opts);
		}
		return $opts;
	}

	/**
	 * This function will update the available options for the the class.
	 * All parameters passes through from the $_POST array are cleansed before
	 * being assigned to the relevant options. 
	 */
	function updateOptions()
	{
		if (isset($_POST['save_radius_auth_settings']))
		{
			$opts = TwoFactorRadiusAuth::getOptions();
			$opts['max_tries'] = stripslashes($_POST['max_tries']);
			$opts['timeout'] = stripslashes($_POST['timeout']);
			$opts['s1_host'] = stripslashes($_POST['s1_host']);
			$opts['s1_port'] = stripslashes($_POST['s1_port']);
			$opts['s1_secr'] = stripslashes($_POST['s1_secr']);
			$opts['s2_host'] = stripslashes($_POST['s2_host']);
			$opts['s2_port'] = stripslashes($_POST['s2_port']);
			$opts['s2_secr'] = stripslashes($_POST['s2_secr']);
			$opts['pwd_otp_sep'] = stripslashes($_POST['pwd_otp_sep']);
			$opts['skip_users'] = explode(',', stripslashes($_POST['skip_users']));

			$tmp = array();
			foreach ($opts['skip_users'] as $val)
			{
				$val = trim($val);
				if (strlen($val) > 0)
					$tmp[] = $val;
			}

			$opts['skip_users'] = $tmp;
			sort($opts['skip_users']);
			update_option('radius_auth_options', $opts);
		}
		else
			TwoFactorRadiusAuth::getOptions();

		TwoFactorRadiusAuth::addMenu();
	}

	/**
	 * Creates the default menu option once the plugin has been activated. 
	 */
	function addMenu()
	{
		# add the menu to the main options section
		add_options_page(
			__('RADIUS Authentication'),
			__('RADIUS Authentication'), 
			'manage_options', basename(__FILE__),
			array('TwoFactorRadiusAuth', 'displayOptions')
		);

		add_filter(
			"plugin_action_links_" . plugin_basename(__FILE__),
			array('TwoFactorRadiusAuth', 'filterPluginActions'));
	}

	/**
	 * Creates the settings link in Plugins once activated. 
	 */
	function filterPluginActions($links)
	{
		$settings_link = '<a href="options-general.php?page=two-factor-radius">' . __( 'Settings' ) . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * This function builds the main admin interface for the plugin. It basically
	 * builds a form and adds the options to it in the relevant sections.
	 */
	function displayOptions()
	{
		$opts = TwoFactorRadiusAuth::getOptions();
		?>
		<div class="wrap">
			<h2><?php _e('RADIUS Authentication Settings') ?></h2>
			<form method="post" action="" id="radius_auth_settings_form">
			<?php settings_fields('radius_auth_options') ?>

			<h3><?php _e('Global connection settings') ?></h3>
			<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row"> <label for="max_tries"><?php _e('Max tries') ?></label> </th>
				<td>
					<input type="text" name="max_tries" id="max_tries" 
						value="<?php echo $opts['max_tries'] ?>" />
					<span class="description">
					<?php _e('How many tries I must try to authenticate against each RADIUS server') ?>.
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="timeout"><?php _e('Timeout') ?></label> </th>
				<td>
					<input type="text" name="timeout" id="timeout" 
						value="<?php echo $opts['timeout'] ?>" />
						<span class="description">
						<?php _e('Timeout in seconds to wait for RADIUS answer') ?>.
						</span>
				</td>
			</tr>
			</tbody>
			</table>

			<h3><?php _e('Server #1 settings') ?></h3>
			<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row"> <label for="s1_host"><?php _e('Hostname') ?></label> </th>
				<td>
					<input type="text" name="s1_host" id="s1_host" 
						value="<?php echo $opts['s1_host'] ?>" />
					<span class="description"><?php _e('Server #1 hostname') ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="s1_port"><?php _e('Port') ?></label> </th>
				<td>
					<input type="text" name="s1_port" id="s1_port" 
						value="<?php echo $opts['s1_port'] ?>" />
					<span class="description"><?php _e('Server #1 port') ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="s1_secr"><?php _e('Secret') ?></label> </th>
				<td>
					<input type="password" name="s1_secr" id="s1_secr" 
						value="<?php echo $opts['s1_secr'] ?>" />
					<span class="description"><?php _e('Server #1 secret') ?></span>
				</td>
			</tr>
			</tbody>
			</table>

			<h3><?php _e('Server #2 settings (optional)') ?></h3>
			<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row"> <label for="s2_host"><?php _e('Hostname') ?></label> </th>
				<td>
					<input type="text" name="s2_host" id="s2_host" 
						value="<?php echo $opts['s2_host'] ?>" />
					<span class="description"><?php _e('Server #2 hostname') ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="s2_port"><?php _e('Port') ?></label> </th>
				<td>
					<input type="text" name="s2_port" id="s2_port" 
						value="<?php echo $opts['s2_port'] ?>" />
					<span class="description"><?php _e('Server #2 port') ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="s2_secr"><?php _e('Secret') ?></label> </th>
				<td>
					<input type="password" name="s2_secr" id="s2_secr" 
						value="<?php echo $opts['s2_secr'] ?>" />
					<span class="description"><?php _e('Server #2 secret') ?></span>
				</td>
			</tr>
			</tbody>
			</table>

			<h3><?php _e('Other settings') ?></h3>
			<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row"> <label for="max_tries"><?php _e('"Password+OTP" separator') ?></label> </th>
				<td>
					<input type="text" name="pwd_otp_sep" id="pwd_otp_sep" size="3"
						value="<?php echo $opts['pwd_otp_sep'] ?>" maxlength="1"/>
					<span class="description">
					<?php _e('Separator used to merge password and otp for RADIUS check') ?>.
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="max_tries"><?php _e('Skip users') ?></label> </th>
				<td>
					<input type="text" name="skip_users" id="skip_users" size="40"
						value="<?php echo join(',', $opts['skip_users']) ?>" />
					<span class="description">
					<?php _e('Comma-separated list of users that we should skip RADIUS auth') ?>.
					</span>
				</td>
			</tr>
			</tbody>
			</table>

			<p class="submit">
				<input type="submit" name="save_radius_auth_settings" 
					id="sumbit" value="<?php _e('Save Changes') ?>" />
			</p>
			</form>
		</div>
		<?php
	}

	function isConfigured()
	{
		$opts = TwoFactorRadiusAuth::getOptions();
		return (
					!empty($opts['max_tries']) &&
					!empty($opts['timeout']) &&
					!empty($opts['s1_host']) && 
					!empty($opts['s1_port']) && 
					!empty($opts['s1_secr'])
				);
	}

	function wp_error($tag, $msg)
	{
		return new WP_Error($tag, __('<strong>ERROR</strong>') . ": $msg");
	}

	/**
	 * This is the main authentication function of the plugin. Given both the username and password it will
	 * make use of the options set to authenticate against the configured RADIUS servers.
	 */
	function checkLogin($userdata, $password)
	{
		if (!function_exists('radius_auth_open'))
			return self::wp_error('missing_php_radius', 'Missing php-radius');

		$username = $userdata->user_login;
		$opts = TwoFactorRadiusAuth::getOptions();

		if (@array_search($username, $opts['skip_users']) !== false) 
		{
			error_log("Plugin setup to skip RADIUS auth for user '$username'");
			return $userdata;
		}

		$OTP = trim($_POST['otp']);
		$radiuspass = $password;
		if (!empty($OTP))
			$radiuspass = $password . $opts['pwd_otp_sep'] . $OTP;

		if (!TwoFactorRadiusAuth::isConfigured())
			return self::wp_error('missing_plugin_settings', 
				__('Missing auth server settings'));

		$reply_message = '';

		try
		{
			$rad = radius_auth_open();
			if (!radius_add_server($rad, $opts['s1_host'], $opts['s1_port'], 
				$opts['s1_secr'], $opts['timeout'], $opts['max_tries']))
				throw new Exception(radius_strerror($rad));
			if (!empty($opts['s2_host']) && !empty($opts['s2_port']) && 
				!empty($opts['s2_secr']))
				if (!radius_add_server($rad, $opts['s2_host'], $opts['s2_port'], 
					$opts['s2_secr'], $opts['timeout'], $opts['max_tries']))
					throw new Exception(radius_strerror($rad));
			if (!radius_create_request($rad, RADIUS_ACCESS_REQUEST))
				throw new Exception(radius_strerror($rad));
			if (!radius_put_string($rad, RADIUS_NAS_IDENTIFIER, '1'))
				throw new Exception(radius_strerror($rad));
			if (!radius_put_int($rad, RADIUS_SERVICE_TYPE, RADIUS_FRAMED))
				throw new Exception(radius_strerror($rad));
			if (!radius_put_int($rad, RADIUS_FRAMED_PROTOCOL, RADIUS_PPP))
				throw new Exception(radius_strerror($rad));
			$station = isset($REMOTE_HOST) ? $REMOTE_HOST : '127.0.0.1';
			if (!radius_put_string($rad, RADIUS_CALLING_STATION_ID, $station) == -1)
				throw new Exception(radius_strerror($rad));
			if (!radius_put_string($rad, RADIUS_USER_NAME, $username))
				throw new Exception(radius_strerror($rad));
			if (!radius_put_string($rad, RADIUS_USER_PASSWORD, $radiuspass))
				throw new Exception(radius_strerror($rad));
			if (!radius_put_int($rad, RADIUS_SERVICE_TYPE, RADIUS_FRAMED))
				throw new Exception(radius_strerror($rad));
			if (!radius_put_int($rad, RADIUS_FRAMED_PROTOCOL, RADIUS_PPP))
				throw new Exception(radius_strerror($rad));
			$res = radius_send_request($rad);
			if (!$res)
				throw new Exception(radius_strerror($rad));
			while ($rattr = radius_get_attr($rad))
			{
				if ($rattr['attr'] == 18)
				{
					$reply_message = $rattr['data'];
					break;
				}
			}
		}
		catch (Exception $exp)
		{
			return self::wp_error('radius_error', $exp->getMessage());
		}

		switch ($res)
		{
			case RADIUS_ACCESS_ACCEPT:
				$userdata->user_pass = wp_hash_password($password);
				return $userdata;
				break;
			case RADIUS_ACCESS_REJECT:
				switch ($reply_message)
				{
					case 'INVALID OTP':
						return self::wp_error('denied', __('Invalid OTP'));
					case 'LDAP USER NOT FOUND':
						return self::wp_error('denied', __('Unknown user'));
					default:
						return self::wp_error('denied', 
							__('Wrong password/OTP'));
				}
				break;
			default:
				return self::wp_error('denied', __('Unknown error'));
		}

		return $userdata;
	}

	/**
	 * Displays additional login form
	 */
	function loginForm()
	{
		?><p>
		<label>
			<?php _e('OTP') ?><br />
			<input type="password" name="otp" id="otp" class="input" 
				value="" size="20" tabindex="25" maxlength="6" />
		</label>
		</p>
		<style type="text/css">.forgetmenot { display:none; }</style><?php
	}

	/**
	 * Displays error message if there are missing settings
	 */
	function loginFormMissingConf()
	{
		?><p style="font-size: 12px;width: 97%;padding: 3px;">
		<?php _e('Two Factor authentication has been disabled') ?>.
		<?php _e('There are missing settings') ?>.
		</p><br/><?php
	}

	/**
	 * A function for building the error messages shown typically on login
	 * @return string Returns the error message from the thentication call
	 */
	function loginErrors()
	{
		global $error;
		global $error_msg;

		if ($error != "")
			$error = "<p>$error</p>";

		if ($error_msg != "")
			$error_msg = "<p>$error_msg</p>";

		return $error_msg . $error;
	}
}


if (is_admin())
	add_action('admin_menu', array('TwoFactorRadiusAuth', 'updateOptions'));

add_filter('login_errors', array('TwoFactorRadiusAuth', 'loginErrors'));

if (TwoFactorRadiusAuth::isConfigured()) {
	add_filter('wp_authenticate_user', array('TwoFactorRadiusAuth', 'checkLogin'), 1, 2);
	add_filter('login_form', array('TwoFactorRadiusAuth', 'loginForm'));
} else {
	add_filter('login_form', array('TwoFactorRadiusAuth', 'loginFormMissingConf'));
}

