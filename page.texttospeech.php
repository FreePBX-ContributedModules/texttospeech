<?php
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

// Tab order for web page usage
$tabindex = 0;

// Variables Set
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
if (isset($_REQUEST['delete'])) $action = 'delete'; 

$id = $_REQUEST['id'];

$name = $_REQUEST['name'];
$text = $_REQUEST['text'];

$engine = $_REQUEST['engine'];
$arguments = isset( $_REQUEST['arguments'] ) ? $_REQUEST['arguments'] : '';

$wait_before = isset( $_REQUEST['wait_before'] ) ? $_REQUEST['wait_before'] : 1;
$wait_after = isset( $_REQUEST['wait_after'] ) ? $_REQUEST['wait_after'] : 0;

$no_answer = isset( $_REQUEST['no_answer'] ) ? $_REQUEST['no_answer'] : false;
$allow_skip = isset( $_REQUEST['allow_skip'] ) ? $_REQUEST['allow_skip'] : false;
$direct_dial = isset( $_REQUEST['direct_dial'] ) ? $_REQUEST['direct_dial'] : false;
$return_ivr = isset( $_REQUEST['return_ivr'] ) ? $_REQUEST['return_ivr'] : false;

if ( isset( $_REQUEST['goto0'] ) && isset( $_REQUEST[ $_REQUEST['goto0'] . "0" ] ) ) {
	$destination = $_REQUEST[$_REQUEST['goto0']."0"];
} else {
	$destination = '';
}

// Debug
$debug = isset( $_REQUEST['debug'] ) ? true : false;

if ( $debug ) {
	echo "<p>";
	echo 'action: "' . $action . '"<br>';

	echo 'id: "' . $id . '"<br>';

	echo 'name: "' . $name . '"<br>';
	echo 'text: "' . $text . '"<br>';

	echo 'engine: "' . $engine . '"<br>';
	echo 'arguments: "' . $arguments . '"<br>';

	echo 'wait_before: "' . $wait_before . '"<br>';
	echo 'wait_after: "' . $wait_after . '"<br>';

	echo 'no_answer: "' . $no_answer . '"<br>';
	echo 'allow_skip: "' . $allow_skip . '"<br>';
	echo 'direct_dial: "' . $direct_dial . '"<br>';
	echo 'return_ivr: "' . $return_ivr . '"<br>';

	echo 'destination: "' . $destination . '"<br>';
	echo "</p>";
}


// Action Start
switch ( $action ) {

	case "add":

		if ( $debug ) freepbx_debug( basename(__FILE__) . " - " . __FUNCTION__ . "() - " . 'name: "' . $name . '", ' . 'text: "' . $text . '", ' . 'engine: "' . $engine . '", ' . 'arguments: "' . $arguments . '", ' . 'wait_before: "' . $wait_before . '", ' . 'wait_after: "' . $wait_after . '", ' . 'no_answer: "' . $no_answer . '", ' . 'allow_skip: "' . $allow_skip . '", ' . 'direct_dial: "' . $direct_dial . '", ' . 'return_ivr: "' . $return_ivr . '", ' . 'destination: "' . $destination . '"' );
		
		texttospeech_add( $name, $text, $engine, $arguments, $wait_before, $wait_after, $no_answer, $allow_skip, $direct_dial, $return_ivr, $destination );
		needreload();
		redirect_standard();
	break;

	case "edit":
		texttospeech_edit( $id, $name, $text, $engine, $arguments, $wait_before, $wait_after, $no_answer, $allow_skip, $direct_dial, $return_ivr, $destination );
		needreload();
		redirect_standard('id');
	break;

	case "delete":
		texttospeech_del( $id );
		needreload();
		redirect_standard();
	break;
}
?>

</div>

<div class="rnav">
<ul>

<?php
echo '<li><a href="config.php?display=texttospeech&amp;type=setup">' . _('<i>( Add Text To Speech )</i>').'</a></li>';

foreach ( texttospeech_list() as $row ) {
	echo '<li><a href="config.php?display=texttospeech&amp;type=setup&amp;id=' . urlencode( $row['id'] ) . '" class="">' . $row['name'] . '</a></li>';
}
?>

</ul>
</div>

<div class="content">

<?php

if ( $action == 'delete' ) {
	echo '<h3>' . _("Text To Speech deleted.") . '</h3>';

} else {

	// Entry Exists
	if ( $id ){ 

		// Get details
		$row = texttospeech_get( $id );

		// Create all variables and values from associative array.
		extract( $row );
}
	
$delURL = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '&action=delete';

?>

<h2><a href="#" class="info"><?php echo _("Text To Speech")?><span><?php echo _('<p>This module allows the creation of a Text To Speech entry that will create a recorded sound file that can then be used to create a custom System Recording so it can be placed inside an Annoucement for usage in a IVR.  Additionally, these Text To Speech entries can be used as regular destinations.  This module uses pre-installed speech synthesis engines on the system to pre-record any message typed into the Text field into a sound file that will be saved in the system.  The files are cached in the "sounds/texttospeech/" folder to provide better playback performance, lower processor utilization during usage, and allow multiple concurrent usage without having to purchase multiple licenses for commerical voice engines.  The module also manages the creation and deletion of the cached sound files when Text To Speech entries are added, updated, or deleted.</p>

<p>The recommended usage of this module is to create Text To Speech entries to create the sound files in the "sounds/texttospeech/" folder, then to create System Recordings from those sound files, and then to create Announcements from the System Recordings.  After that create the IVR menus with the Announcements.  You can then change the content of the Text To Speech entries while keeping the Names the same and creating new sound files, thus updating the rest of the Sound Recordings -> Announcements -> IVR menus without having to re-create the menu structure again.  The module can still be used as a generic destination.
</p>')?></span></a></h2>

<h4><?php echo ( $id ) ? _("Entry: ") . $name : "<i>" . _( "( Add Text To Speech )" ) . "</i>"; ?></h4>

<form name="texttospeech" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return submit_check();">

	<input type="hidden" name="display" value="texttospeech">
	
	<input type="hidden" name="action" value="<?php echo ( $id ? 'edit' : 'add' ); ?>"> 

	<input type="hidden" name="id" value="<?php echo $id; ?>">

	<!-- Settings: -->

	<h5><?php echo _("Settings:"); ?><hr></h5>
	
	<table>

		<tr>
			<td><a href="#" class="info"><?php echo _("Name");?><span><?php echo _("Enter the Name of this Text To Speech entry to help you identify it.  It can only be composed of alpha-numeric characters (a-z A-Z 0-9), the underscore (_) and dash (-) but no spaces or other characters.");?></span></a>:</td>

			<td><input type="text" name="name" size="80" maxlength="100" value="<?php echo ( isset( $name ) ? $name : '' ); ?>" tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Text");?><span><?php echo _("Enter the text you want to synthetize.  If your voice engine (e.g. swift by Cepstral or espeak) supports Speech Synthesis Markup Language (SSML) you can also use markup tags such as phoneme, voice, emphasis, break, prosody, etc. to change the sound of the voice itself.  You can even enter full SSML XML documents into this field.  Learn about SSML at W3C.org and Cepstral.com.");?></span></a>:</td>

			<td><textarea name="text" cols="50" rows="10" tabindex="<?php echo ++$tabindex;?>"><?php echo ( isset( $text ) ? $text : '' ); ?></textarea></td>
		</tr>

	</table>


	<!-- Engine: -->

	<h5><?php echo _("Engine:"); ?><hr></h5>

	<table>

		<tr>
			<td><a href="#" class="info"><?php echo _("Engine")?><span><?php echo _("List of Text To Speech engines detected on the server (e.g. swift, flite, text2wave, espeak).  Choose the one you want to use for the current text.  If you do not see the engines that you have installed on the system ensure that their executable binaries are located or linked to a popular bin folder that is in the system PATH.  (This module does not use the specific Asterisk internal applicationts access these engines so they do not have to be installed into Asterisk, instead it executes the binaries directly from the system to create the sounds files to be cached.)")?></span></a>:</td>
	
			<td>
				<select name="engine" tabindex="<?php echo ++$tabindex;?>">

					<?php
					
					foreach ( $engines as $choice ) {

						( $choice == $engine ) ? $selected = 'selected="selected"' : $selected = '';

						echo '<option value="' . $choice . '" ' . "$selected>$choice</option>";
					}

					?>

				</select>
			</td>
		</tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Arguments")?><span><?php echo _("Arguments to provide to the engine during the creation of the the sound file, such as which voice to use, any sound effects to apply, the format of the text, and any engine parameters.  (Do not specify the input or output filename nor the text to use since that is already being passed to the engines.)")?></span></a>:</td>

			<td><input type="text" name="arguments" size="100" maxlength="255" value="<?php echo ( isset( $arguments ) ? $arguments : '' ); ?>" tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>

	</table>

	<!-- Options: -->
		
	<h5><?php echo _("Options:"); ?><hr></h5>

	<table>

		<tr>
			<td><a href="#" class="info"><?php echo _("Wait Before")?><span><?php echo _("Wait time in second before playback of the message.  Useful for adding a short pause to avoid non-stop talking.")?></span></a>:</td>

			<td><input type="text" name="wait_before" size="2" maxlength="2" value="<?php echo ( isset( $wait_before ) ? $wait_before : 1 ); ?>" tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Wait After")?><span><?php echo _("Wait time in second after playback of the message.  Useful for adding a short pause to avoid non-stop talking.")?></span></a>:</td>

			<td><input type="text" name="wait_after" size="2" maxlength="2" value="<?php echo ( isset( $wait_after ) ? $wait_after : 1 ); ?>" tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Don't Answer Channel")?><span><?php echo _("Check this to keep the channel from explicitly being answered. When checked, the message will be played and if the channel is not already answered it will be delivered as early media if the channel supports that. When not checked, the channel is answered followed by a 1 second delay. When using an annoucement from an IVR or other sources that have already answered the channel, that 1 second delay may not be desired.")?></span></a></td>

			<td><input type="checkbox" name="no_answer" value="1" tabindex="<?php echo ++$tabindex;?>" <?php echo ( $no_answer ? 'checked="checked"' : ''); ?> /></td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Allow Skip")?><span><?php echo _("If the caller is allowed to press a key to skip the message.")?></span></a></td>

			<td><input type="checkbox" name="allow_skip" value="1" tabindex="<?php echo ++$tabindex;?>" <?php echo ($allow_skip ? 'checked="checked"' : ''); ?> /></td>
		</tr>

		<tr>
			<td><a href="#" class="info"><?php echo _("Direct Dial")?><span><?php echo _("If the caller is allowed to dial an extension directly if the Destination is an IVR (Digital Assistant) menu.  (Only works is Allow Skip is enabled.)")?></span></a></td>

			<td><input type="checkbox" name="direct_dial" value="1" tabindex="<?php echo ++$tabindex;?>" <?php echo ($direct_dial ? 'checked="checked"' : ''); ?> /></td>
		</tr>

	</table>

	<!-- Destination: -->

	<h5><?php echo _("Destination:"); ?><hr></h5>
	
	<table>

		<tr>
			<td><input type="checkbox" name="return_ivr" value="1" tabindex="<?php echo ++$tabindex;?>" <?php echo ($return_ivr ? 'checked="checked"' : ''); ?> /></td>

			<td><a href="#" class="info"><?php echo _("Return to IVR")?><span><?php echo _("If this entry came from an IVR and this box is checked, the destination below will be ignored and instead it will return to the calling IVR.  (If you are using Text To Speech to say the options for the menu then do not use this option because it will go directly to the IVR to await a key press without saying the menu options.  Instead uncheck this option and select a Destination to the Text To Speech entry that says the menu options.)")?></span></a></td>
		</tr>

	</table>

	<table>

		<?php echo ( isset( $destination ) ? drawselects( $destination, 0 ) : drawselects( null, 0 ) ); ?>

	</table>

	<!-- Submit -->

	<h6>
	
	<input name="submit" type="submit" <?php echo ( isset ( $texttospeech_error ) ? 'disabled="disabled"' : ''); ?> value="<?php echo _("Submit Changes")?>" tabindex="<?php echo ++$tabindex;?>">
	
	<?php if( isset( $id ) ) echo ( '&nbsp;<input name="delete" type="submit" value="'._("Delete").'" tabindex="++$tabindex">' ); ?>
	
	</h6>
	
<script language="javascript">
<!--

function submit_check()
{
	// Illegal Character Check on Name Field Value
	if ( document.texttospeech.name.value.search("[^a-z0-9_-]+", "i") != -1 || document.texttospeech.name.value == "" ) {
		return warnInvalid( document.texttospeech.name, "Please enter a valid Name.  Only the following characters are allowed, letters of the alphabet (a-z A-Z), numbers (0-9), underscore (_), dash (-)." );
	}

	// Empty Text Field Check.
	if ( document.texttospeech.text.value == "" ) {
		return warnInvalid( document.texttospeech.name, "Please enter something in the Text field." );
	}
		
	defaultEmptyOK = false;

	return true;
}

-->
</script>

</form>

<?php		
} //end if action == delGRP
?>
