<?php
/*
Plugin Name: WordPress PayPal Donation Plugin
Version: 1.0
Plugin URI: http://www.seanbluestone.com/wordpress-paypal-donations-plugin
Author: Sean Bluestone
Author URI: http://www.seanbluestone.com
Description: A WordPress PayPal Donation Plugin with some extra features.

Copyright 2008  Sean Bluestone  (email : thedux0r@gmail.com)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


register_activation_hook(__FILE__, 'wpppd_install');
//register_deactivation_hook(__FILE__, 'wpppd_uninstall');
add_action('admin_menu', 'wpppd_menu');
add_shortcode('wpppd_donations', 'wpppd_shortcodes');

global $wpdb;
define("DONATIONS_TABLE",$wpdb->prefix."wpppd_donations");

function wpppd_shortcodes($atts){
	wpppd_display();
}


function wpppd_install(){
	$Version=get_option('wpppd_version');

	if($Version!=''){
		mysql_query("CREATE TABLE ".DONATIONS_TABLE." (`id` INT NOT NULL ,`date` INT NOT NULL ,`name` VARCHAR( 255 ) NOT NULL ,`currency` VARCHAR( 25 ) NOT NULL ,`amount` SMALLINT NOT NULL ,`email` VARCHAR( 255 ) NOT NULL ,`link` VARCHAR( 255 ) NOT NULL ,`display` VARCHAR( 255 ) NOT NULL,UNIQUE KEY id (id))");
		add_option('wpppd_numtoshow',20);
		add_option('wpppd_datetoshow','None');
		add_option('wpppd_email',get_option('admin_email'));
		add_option('wpppd_incentive','Free');
		add_option('wpppd_poweredby','Yes');
	}
}

function wpppd_uninstall(){
	global $wpdb;

	$wpdb->query("DROP TABLE ".DONATIONS_TABLE);
	$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'wpppd%'");
}



function wpppd_menu(){
	add_options_page('Donations','Donations', 8, __FILE__, 'wpppd_options'); 
}


function wpppd_options(){

	$_Yes=__('Yes',$WPLD_Domain);
	$_No=__('No',$WPLD_Domain);

	$Currencies['AUD']='$';
	$Currencies['CAD']='$';
	$Currencies['EUR']='&#128;';
	$Currencies['GBP']='&#163;';
	$Currencies['JPY']='&#165;';
	$Currencies['USD']='$';

	echo '<div class="wrap"><h2>WordPress PayPal Donations Plugin</h2>

	This plugin requires IPN. To set up IPN head to this page: <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_profile-ipn-notify">https://www.paypal.com/cgi-bin/webscr?cmd=_profile-ipn-notify</a> and hit Edit. Type in the URL of the donation page on your site (the page you entered [wpppd_donations] on), check the box and hit Save.<br><br>

	<form method="post" action="options.php" name="wpppd_options">';

	wp_nonce_field('update-options');

	echo '<table class="form-table"><tr valign="top">
	<tr valign="top"><td><b>'.__('Donation Page',$WPLD_Domain).'</b></td>
	<td><input type="text" name="wpppd_page" value="'.get_option('wpppd_page').'"></td>
	<td>Enter the full URL of the page you have set up where people can see and make donations.</td></tr>

	<tr valign="top"><td><b>'.__('PayPal E-mail',$WPLD_Domain).'</b></td>
	<td><input type="text" name="wpppd_email" value="'.get_option('wpppd_email').'"></td>
	<td>Enter your PayPal email address that people will donate to.</td></tr>

	<tr valign="top"><td><b>'.__('Number To Show',$WPLD_Domain).'</b></td>
	<td><input type="text" name="wpppd_numtoshow" value="'.get_option('wpppd_numtoshow').'"></td>
	<td>You can supply a maximum number of links to be shown or leave this blank for unlimited.</td></tr>

	<tr><td><b>'.__('Date Restriction',$WPLD_Domain).'</b></td>
	<td><select name="wpppd_datetoshow">';

	$DateToShow=get_option('wpppd_datetoshow');

	foreach(array('None'=>'None','1 Week'=>1, '2 Weeks'=>2, '3 Weeks'=>3, '1 Month'=>4, '2 Months'=>8, '6 Months'=>24, '1 Year'=>48 ) as $Display => $Value){
		echo '<option value="'.($Value*604800).'"'.($DateToShow==($Value*604800) ? ' SELECTED' : '').'>'.$Display.'</option>';
	}

	echo '</select></td>
	<td>'.__('Donations older than this value will not be shown.',$WPLD_Domain).'</td>
	</tr>

	<tr><td><b>'.__('Default Currency',$WPLD_Domain).'</b></td>
	<td><select name="wpppd_currency">';

	$Currency=get_option('wpppd_currency');

	foreach($Currencies as $Value => $Display){
		echo '<option value="'.$Value.'"'.($Currency==$Value ? ' SELECTED' : '').'>'.$Display.$Value.'</option>';
	}

	echo '</select></td>
	<td>'.__('Your default currency.',$WPLD_Domain).'</td>
	</tr>

	<tr><td><b>'.__('Donation Incentive',$WPLD_Domain).'</b></td>
	<td><select name="wpppd_incentive">';

	$LinkCost=get_option('wpppd_incentive');

	foreach(array('Free','Value Below','Do Not Offer Incentive') as $Display){
		echo '<option value="'.$Display.'"'.($Display==$LinkCost ? ' SELECTED' : '').'>'.$Display.'</option>';
	}

	echo '</select></td>
	<td>'.__('You can offer a link as an incentive to donate. If you select Value Below then only donations of the value you enter below will have their link shown.',$WPLD_Domain).'</td>
	</tr>

	<tr valign="top"><td><b>'.__('Link Cost',$WPLD_Domain).'</b></td>
	<td><input type="text" name="wpppd_linkcost" value="'.get_option('wpppd_linkcost').'"></td>
	<td>If the above option is set to Value Below then donations of this value and above will show links (if entered).</td>
	</tr>

	<tr valign="top"><td><b>'.__('Test Mode?',$WPLD_Domain).'</b></td>';

	unset($Yes,$No);
	if(get_option('wpppd_testmode')=='No'){
		$No=' SELECTED';
	}else{
		$Yes=' SELECTED';
	}

	echo '<td><select name="wpppd_testmode"><option value="Yes"'.$Yes.'>'.$_Yes.'</option><option value="No"'.$No.'>'.$_No.'</option></select></td>
	<td>'.__('If set to Yes, the script will forward payments to http://sandbox.paypal.com which allows you to simulate real donations to ensure they work and see how they look.',$WPLD_Domain).'</td>
	</tr>

	<tr valign="top"><td><b>'.__('Show Powered By?',$WPLD_Domain).'</b></td>';

	unset($Yes,$No);
	if(get_option('wpppd_poweredby')=='No'){
		$No=' SELECTED';
	}else{
		$Yes=' SELECTED';
	}

	echo '<td><select name="wpppd_poweredby"><option value="Yes"'.$Yes.'>'.$_Yes.'</option><option value="No"'.$No.'>'.$_No.'</option></select></td>
	<td>'.__('WordPress PayPal Donations Plugin is free to use wherever and change however you like. Though not required, a link back to the plugin homepage is appreciated. If set to Yes then a small link back to our site will be included at the bottom of your donation page.',$WPLD_Domain).'</td>
	</tr>

	<tr><td colspan="3" align="right"><input type="hidden" name="action" value="update" /><input type="submit" name="Submit" value="'.__('Save Changes',$WPLD_Domain).'" /></td></tr>
	</table><br><br>
	</form>
	</div>';

}


function wpppd_display(){
	$table=DONATIONS_TABLE;
	$Email=get_option('wpppd_email');
	$TestMode=get_option('wpppd_testmode');

	$Currencies=array('AUD'=>'$','CAD'=>'$','EUR']=>'&#128;','GBP'=>'&#163;','JPY'=>'&#165;','USD'=>'$');

	// Setup class
	require_once('paypal.class.php');  // include the class file
	$p = new paypal_class; // initiate an instance of the class

	if($TestMode=='Yes'){
		$p->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // testing paypal url
	}else{
		$p->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
	}

	$this_script = get_option('wpppd_page');

	switch ($_GET['action']) {
	case 'process':

		$p->add_field('on0', 'Display Preference');
		$p->add_field('os0', $_POST['os0']);
		$p->add_field('on1', 'Optional URL');
		$p->add_field('os1', $_POST['os1']);
		$p->add_field('business', $Email);
		$p->add_field('return', $this_script.'?action=success');
		$p->add_field('cancel_return', $this_script.'?action=cancel');
		$p->add_field('notify_url', $this_script.'?action=ipn');
		$p->add_field('no_shipping', 1);
		$p->add_field('item_name', 'Donation');

		$p->submit_paypal_post(); // submit the fields to paypal
		break;

	case 'success':// Order was successful... 

		if($_POST['mc_gross']>0 && $_POST['mc_currency'] && $_POST['payer_email']){
			$now=time();
			mysql_query("INSERT INTO ".DONATIONS_TABLE." VALUES ('',$now,'{$_POST['first_name']} {$_POST['last_name']}','{$_POST['mc_currency']}','{$_POST['mc_gross']}','{$_POST['payer_email']}','{$_POST['option_selection0']}','{$_POST['option_selection1']}')")or die(mysql_error());

			echo "<h3>Thank you for your donation!</h3>
			Thank you for your donation of {$Currencies[$_POST['mc_currency']]}{$_POST['mc_gross']} {$_POST['mc_currency']}, we are very grateful.<br><br>";
		}
		break;

	case 'cancel': // Order was canceled...

		// The order was canceled before being completed.
		break;

	case 'ipn':    // Paypal is calling page for IPN validation...

		if ($p->validate_ipn()) {

			$subject = 'Donation Recieved';
			$to = $Email; //  your email
			$body =  "An instant payment notification was successfully recieved\n";
			$body .= "from ".$p->ipn_data['payer_email']." on ".date('m/d/Y');
			$body .= " at ".date('g:i A')."\n\nDetails:\n";

			foreach ($p->ipn_data as $key => $value) { $body .= "\n$key: $value"; }
				mail($to, $subject, $body);
			}
			break;
		}

	// Show Totals

	if(get_option('wpppd_showtotals')=='Yes' || 3<4){

		$getTotal=mysql_query("SELECT currency,SUM(amount) as amount FROM ".DONATIONS_TABLE." GROUP BY currency");
		$TotalNum=mysql_num_rows($getTotal);

		while($Total=mysql_fetch_assoc($getTotal)){
			$x++;
			$TotalString.='<b>'.$Currencies[$Total['currency']].number_format($Total['amount'],2)." {$Total['currency']}</b>";
			if($TotalNum==$x+1 && $TotalNum>0){
				$TotalString.=' and ';
			}else{
				$TotalString.=', ';
			}
		}

		$TotalString=substr($TotalString,0,-2);
		echo 'So far we have raised a total of '.$TotalString.'.<br><br>';
	}

	$LinkCost=get_option('wpppd_linkcost');
	$Incentive=get_option('wpppd_incentive');
	$Currency=get_option('wpppd_currency');

	if($Incentive!='Do Not Offer Incentive'){
		echo 'If you donate a value of '.$Currencies[$Currency].get_option('wpppd_linkcost').' '.$Currency.' or more then we will create a link to a site of your choice. For this to work you must click the Return button on the PayPal page after you have completed the donation.<br><br>';
	}

	// Show Donate Button

	echo '<div align="center" style="background:#ddd;border:1px solid black;" border="1">
	<form action="'.$_SERVER['REQUEST_URI'].'?action=process" method="post">
	<table><tr><td>Display Preference:</td><td><select name="os0"><option value="1">Show My Name And Amount</option><option value="2">Show My Name, Hide The Amount</option><option value="3">Hide My Name And Amount</option></select></td></tr>';

	if($LinkCost!='Do Not Offer Links'){
		echo '<tr><td><input type="hidden" name="on1" value="Optional URL"/>Optional URL:</td><td><input type="text" name="os1" value=""></td></tr>';
	}

	echo '<tr><td colspan="2">
	<input type="hidden" name="no_shipping" value="1"/>
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="hosted_button_id" value="1148120">

	<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="">
	<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</td></tr>
	</table>
	</form>
	</div>';

	// Show Donations

	$NumToShow=(get_option('wpppd_numtoshow') != '' ? ' LIMIT '.get_option('wpppd_numtoshow') : '');
	$DateToShow=(get_option('wpppd_datetoshow') != 0 ? ' WHERE date > '.(time()-get_option('wpppd_datetoshow')) : '');

	$getDonations=mysql_query("SELECT * FROM ".DONATIONS_TABLE." {$DateToShow}{$NumToShow}")or die(mysql_error());

	if(mysql_num_rows($getDonations)>0){
		echo '<table style="border-spacing:8px;"><tr><th>Name</th><th>Donation</th>'.( $Incentive!='Do Not Offer Incentive' ? '<th>Link</th>' : '' ).'</tr>';

		while($Donation=mysql_fetch_assoc($getDonations)){
			echo "<tr><td>{$Donation['name']} </td><td>{$Currencies[$Donation['currency']]}".number_format($Donation['amount'],2)." {$Donation['currency']}</td>".( $Incentive!='Do Not Offer Incentive' ? '<td>'.($Donation['amount'] > $LinkCost && $Donation['link'] ? '<a href="'.$Donation['link'].'">'.$Donation['link'].'</a>' : '').'</td>' : '')."</tr>";
		}

		echo '</table>';
	}

	if(get_option('wpppd_poweredby')=='Yes'){
		echo '<div align="right"><sup>Powered by <a href="http://www.seanbluestone.com/wordpress-paypal-donations-plugin">WordPress PayPal Donations Plugin</a></sup></div>';
	}
}

?>