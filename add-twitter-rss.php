<?php
/*
Plugin Name: Add Twitter RSS
Plugin URI: http://www.paulmc.org/whatithink/wordpress/plugins/add-twitter-rss/
Description: Adds your Twitter RSS Link to your Blog Title
Version: 1.2
Author: Paul McCarthy
Author URI: http://www.paulmc.org/whatithink
*/

/*  Copyright 2009  Paul McCarthy  (email : paul@paulmc.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//check if the form needs to be processed
pmcCheckProcess();

//function to add wordpress actions when the plugin is loaded
function pmcAddTwitterRSS_init() {
	add_action('wp_head', 'pmcWriteHeadLink');
	add_action('admin_menu', 'pmcTwitterMenu');
	
	//if the user has selected the option to add a link to the meta
	if (get_option('pmcAddMetaLink')) {
		add_action('wp_meta', 'pmcWriteMetaLink');
	}
	
}

//function to write the rss feed link to the header
function pmcWriteHeadLink() {
	//get the settings from the database
	$pmcTwitterTitle = get_option('pmcTwitterTitle');
	$pmcTwitterID = get_option('pmcTwitterID');
	
	//build RSS link
	$pmcTwitterRSS = 'https://twitter.com/statuses/user_timeline/' . $pmcTwitterID . '.rss';
	
	echo '<link rel="alternate" type="application/rss+xml" title="' . $pmcTwitterTitle . '" href="' . $pmcTwitterRSS . '" />';
}

//function to write a link to the meta widget
function pmcWriteMetaLink() {
	//get the settings from the database
	$pmcTwitterID = get_option('pmcTwitterID');
	
	//build the link
	echo '<li><a href="https://twitter.com/statuses/user_timeline/' . $pmcTwitterID . '.rss" title="Subscribe to my Twitter Feed">Twitter RSS</a></li>';
}

//function to add admin menu
function pmcTwitterMenu() {
add_options_page('Twitter RSS Options', 'Twitter RSS Options', 8, 'twitterrsspage', 'pmcTwitterOptions');

}

//function to display admin form
function pmcTwitterOptions() {
	echo '<div class="wrap">';
	echo '<h2>Add Twitter RSS</h2>';
	echo '<h3>Find My Twitter ID</h3>';
	echo '<form name="twitterid" action="" method="post">';
	echo '<table class="form-table">';
	echo '<tr><td>';
	echo '<label for="pmcTwitterScreen">Twitter Screen Name</label>';
	echo '</td><td>';
	echo '<input type="text" name="pmcTwitterScreen" id="pmcTwitterScreen" value="' . get_option('pmcTwitterScreen') . '" />';
	echo '</td><td>';
	echo '<input type="submit" value="Find my Twitter ID" class="button-primary" />';
	echo '</td></tr></table>';
	echo '<input type="hidden" name="process-form" value="1" />';
	echo '</form>';
	echo '<hr />';
	echo '<h3>Settings</h3>';
	echo '<form action="options.php" method="post">';
	wp_nonce_field('update-options');
	echo '<table class="form-table">';
	echo '<tr><td>';
	echo '<label for="pmcTwitterTitle">RSS Icon Text:</label>';
	echo '</td><td>';
	echo '<input type="text" name="pmcTwitterTitle" id="pmcTwitterTitle" value="' . get_option('pmcTwitterTitle') . '" />';
	echo '</td></tr><tr><td>';
	echo '<label for="pmcTwitterScreen">Twitter ID:</label>';
	echo '</td><td>';
	echo '<input type="text" name="pmcTwitterID" id="pmcTwitterID" value="' . get_option('pmcTwitterID') . '" />';
	echo '</td></tr><tr><td>';
	echo '<label for="pmcAddMetaLink">Add a Link to Twitter Feed in Sidebar Meta Widget?</label>';
	echo '</td><td>';
	echo '<input type="checkbox" name="pmcAddMetaLink" id="pmcAddMetaLink" ';
		//test if the checkbox has been ticked
		if (get_option('pmcAddMetaLink')) {
			echo 'checked="yes" />';
		} else {
			echo '/>';
		}
	echo '</td></tr><tr><td>';
	echo '<input type="submit" value="Update Options" class="button-primary" />';
	echo '</td></tr>';
	echo '</table>';
	echo '<input type="hidden" name="action" value="update" />';
	echo '<input type="hidden" name="page_options" value="pmcTwitterTitle,pmcTwitterID,pmcAddMetaLink" />';
	echo '</form>';
	echo '<h3>Settings</h3>';
	echo '<p>';
	echo '<b>RSS Icon Text:</b> What a visitor will see when they click your RSS Icon in their browsers address bar. Automatically preceeded by "Subscribe to".';
	echo '</p><p>';
	echo '<b>Your Twitter ID:</b> Twitter uses a numeric ID for each user. If you know your Twitter ID, type it in here, otherwise use the "Find My Twitter ID" utility above.';
	echo '</p>';
	echo '</div>';
}

//function to check if we should process the form
function pmcCheckProcess() {
	//check the $_POST variable
	if (array_key_exists('process-form', $_POST)) {
		//save the Twitter Screen Name
		if (get_option('pmcTwitterScreen') != '') {
			update_option('pmcTwitterScreen', $_POST['pmcTwitterScreen']);
		} else {
			add_option('pmcTwitterScreen', $_POST['pmcTwitterScreen']);
		}
		
		//get Twitter ID
		pmcGetTwitterID();
	}
}

//function to get Twitter ID from Twitter username
function pmcGetTwitterID() {
	//require class_http.php
	require_once(dirname(__FILE__).'/class_http.php');
	
	//create a new connection
	$pmcTwitterConn = new http();
	
	
	//get the Twitter username from post variable
	$pmcTwitterUser = get_option('pmcTwitterScreen');
	
	//set the url to the Twitter API
	$pmcTwitterAPI = 'http://twitter.com/users/show/' . $pmcTwitterUser . '.xml';
	
	//make sure that we can connect, if not display an error message
	if (!$pmcTwitterConn->fetch($pmcTwitterAPI, "0", "twitter")) {
		echo "<h2>There is a problem with the http request!</h2>";
  		echo $pmcTwitterConn->log;
  		exit();
	}
	
	//if we have connected, then get the data.
	//as this is xml data, we are lookig for the ID key and it's value.
	$pmcTwitterData=$pmcTwitterConn->body;
	preg_match ('/<id>(.*)<\/id>/', $pmcTwitterData, $matches);	
	//update the options database with the Twitter ID
	
	//remove the <id></id> HTML tags from the returned key
	$pmcTrimID = trim($matches[0], "</id>");
	
	//update the database with the ID
	if (get_option('pmcTwitterID') != '') {
		update_option('pmcTwitterID', $pmcTrimID);
	} else{
		add_option('pmcTwitterID', $pmcTrimID);
	}
	
}

//when the plugin is loaded, we run the init function
add_action("plugins_loaded", "pmcAddTwitterRSS_init"); 
?>