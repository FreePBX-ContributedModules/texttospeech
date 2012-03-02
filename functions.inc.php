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

$debug = false;

// Asterisk Lib Folder Get
$astvarlib_path = $asterisk_conf['astvarlibdir'];

// Engines Find
$engines = texttospeech_find_engines( array("swift", "flite", "text2wave", "espeak") );

// Sound File Path Location
$texttospeech_sound_cache = $astvarlib_path . "/" . "sounds/texttospeech/";
if( !is_dir( $texttospeech_sound_cache ) ) mkdir( $texttospeech_sound_cache, 0775 );

/*
--------------------------------------------------------------------------------
FreePBX Module Special Functions - *_get_config, *_destinations
--------------------------------------------------------------------------------
*/

// Destinations Array Return
function texttospeech_destinations() {

		$context_prefix = 'ext-texttospeech-';

		foreach( texttospeech_list() as $row ){
			$destinations[] = array( 'destination' => $context_prefix . $row['id'] . ',s,1', 'description' => $row['name'] );
		}

		return $destinations;
}

// Extensions Configurations (Get and Set)
function texttospeech_get_config( $pbx ) {

	global $ext;
	
	$debug = false;
	
	if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'pbx: "' . $pbx .'"');

	switch( $pbx ) {

		case "asterisk":

			$context_prefix = 'ext-texttospeech-';

			foreach( texttospeech_list() as $entry ) {
			
				// Row All Settings Retrieve
				$row = texttospeech_get( $entry['id'] );

				// Values Get
				$id = $row['id'];

				$name = $row['name'];

				$wait_before = $row['wait_before'];
				$wait_after = $row['wait_after'];

				$allow_skip = $row['allow_skip'];
				$direct_dial = $row['direct_dial'];
				$no_answer = $row['no_answer'];
				$return_ivr = $row['return_ivr'];

				$destination = $row['destination'];

				// Context
				$context = $context_prefix . $id;

				// Sound File Relative to Asterisk "/var/lib/asterisk/sounds/" folder without Extension
				// Note: Required for Asterisk Playback() and Background() applications.
				$soundfile_relative = "texttospeech/" . $name;
				
				if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'name: "' . $name . '", ' . 'allow_skip: "' . $allow_skip . '", ' . 'no_answer: "' . $no_answer . '", ' . 'destination: "' . $destination . '"' );

				if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'context: "' . $context . '", ' . 'soundfile_relative: "' . $soundfile_relative . '"');

				// No Answer
				if ( ! $no_answer ) {
					$ext->add( $context, 's', '', new ext_gotoif( '$["${CDR(disposition)}" = "ANSWERED"]', 'begin' ) );
					$ext->add( $context, 's', '', new ext_answer( '' ) );
				}

				$ext->add( $context, 's', 'begin', new ext_noop( 'Text To Speech: '. $name ) );

				if ( $wait_before ) $ext->add( $context, 's', '', new ext_wait( $wait_before ) );

				if ( $allow_skip ) {
				
					if ( $direct_dial ) {
					
						// Return to IVR overrides Destination
						if ( $return_ivr ) {

							$destination_context = '${IVR_CONTEXT}';

						} else {

							// Context Extract Only from Destination ( context,extension,priority )
							$destination_array = explode( ",", $destination );
							$destination_context = $destination_array[0];

						}

						// Note: IVR_CONTEXT needs to be set here, since a Direct Dialed extension skips the "s" extension in the IVR, so the IVR "s" extension doesn't get a chance to set the IVR_CONTEXT there.
						$ext->add( $context, 's', '', new ext_set( '_IVR_CONTEXT', $destination_context ) );

						// Asterisk Background() Play - With Skip
						$ext->add( $context, 's', 'play', new ext_background( $soundfile_relative . ',nm,,' . $destination_context ) );

						$ext->add( $context, 's', '', new ext_waitexten( $wait_after ) );

						// Direct Dialed Goto Destination Context, and Extension
						// Note: FreePBX ext_goto() is backwards ( $priority, $extension, $context)
						$ext->add( $context, '_.', '', new ext_goto( 1, '${EXTEN}', $destination_context ) );

						// Timeout Goto
						$ext->add( $context, 't', '', new ext_goto( 1, 't', $destination_context ) );

					} else {

						// Asterisk Background() Play - With Skip
						$ext->add( $context, 's', 'play', new ext_background( $soundfile_relative . ',n' ) );

						if ( $wait_after > 0) $ext->add( $context, 's', '', new ext_waitexten( $wait_after ) );
						
						// Goto
						if ( $return_ivr ) {

							// Return to IVR
							// Note: FreePBX ext_goto() is backwards ( $priority, $extension, $context)
							$ext->add( $context, '_[*#0-9]', '', new ext_goto( 1, 'return', '${IVR_CONTEXT}' ) );
							
							// Timeout Goto
							$ext->add( $context, 't', '', new ext_goto( 1, 'return', '${IVR_CONTEXT}' ) );

						} else {

							// Goto Destination
							// Note: FreePBX ext_goto() is backwards ( $priority, $extension, $context)
							$ext->add( $context, '_[*#0-9]', '', new ext_goto( $destination ) );
							
							// Timeout Goto
							$ext->add( $context, 't', '', new ext_goto( $destination ) );
						}
					}

				} else {
				
					// Asterisk Playback() Play - No Skip
					$ext->add( $context, 's', '', new ext_playback( $soundfile_relative . ',noanswer' ) );

					if ( $wait_after ) $ext->add( $context, 's', '', new ext_wait( $wait_after ) );

					// Goto
					if ( $return_ivr ) {

						// Return to IVR
						// Note: FreePBX ext_goto() is backwards ( $priority, $extension, $context)
						$ext->add( $context, 's', '', new ext_goto( 1, 'return', '${IVR_CONTEXT}' ) );

					} else {

						// Goto Destination
						// Note: FreePBX ext_goto() is backwards ( $priority, $extension, $context)
						$ext->add( $context, 's', '', new ext_goto( $destination ) );
					}
				}
			}
		break;
	}
}



/*
--------------------------------------------------------------------------------
Module Functions
--------------------------------------------------------------------------------
*/

// List
function texttospeech_list() {

	global $db;

	$debug = false;

	$sql = "SELECT `id`, `name` FROM `texttospeech` ORDER BY `name`";

	if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'sql: "' . $sql . '"' );

	$result = $db->getAll( $sql, DB_FETCHMODE_ASSOC );

	// if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'result: "' . is_array( $result ) ? join( $result ) : $result . '"' );

	if( DB::IsError( $result ) ) return null;

	return $result;
}



// Get
function texttospeech_get( $id ) {

	global $db, $texttospeech_sound_cache;
	
	$debug = false;

	// Database Entry Retrieve
	$sql = "SELECT * FROM `texttospeech` WHERE `id`= " . sql_formattext( $id );

	if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'sql: "' . $sql . '"' );

	$row = $db->getRow( $sql, DB_FETCHMODE_ASSOC );

	// if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'result: "' . is_array( $result ) ? join( $result ) : $result . '"' );

	if ( DB::IsError( $row ) ) die_freepbx( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . $row->getMessage() . " - " . $sql );
	
	$name = $row['name'];
	$engine = $row['engine'];

	// Retrieve Text From Textfile 
	$textfile = $texttospeech_sound_cache . $name . ".txt";

	if ( file_exists( $textfile ) ) {
		$text = file_get_contents( $textfile );
		
		if ( $text == false ) {
			freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Could not retreive textfile content from: " . $textfile );
			return false;
		}
	} else {
		freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Text file does not exist: " . $textfile );
	}
	
	// Insert Text into row array for return since it is not stored in the database but in a text file.
	$row['text'] = $text;

	return $row;
}



// Add
function texttospeech_add( $name, $text, $engine, $arguments, $wait_before, $wait_after, $no_answer, $allow_skip, $direct_dial, $return_ivr, $destination ) {

	$debug = false;

	if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'name: "' . $name . '", ' . 'text: "' . $text . '", ' . 'engine: "' . $engine . '", ' . 'arguments: "' . $arguments . '", ' . 'wait_before: "' . $wait_before . '", ' . 'wait_after: "' . $wait_after . '", ' . 'no_answer: "' . $no_answer . '", ' . 'allow_skip: "' . $allow_skip . '", ' . 'direct_dial: "' . $direct_dial . '", ' . 'return_ivr: "' . $return_ivr . '", ' . 'destination: "' . $destination . '"' );

	// Duplicate Check
	$list = texttospeech_list();

	foreach ( $list as $row ) {

		if ( $row['name'] == $name ) {

			echo "<script>javascript:alert( '"._( "This Name already exists" )."' );</script>";
			return false;
		}
	}

	// Files Create
	texttospeech_files_create( $name, $engine, $arguments, $text );
	
	// Database Insert
	global $db;
	$sql = "INSERT INTO `texttospeech` SET" . " " .	
		"`name` = " . sql_formattext( $name ) . ", " .

		"`engine` = " . sql_formattext( $engine ) . ", " .
		"`arguments` = " . sql_formattext( $arguments ) . ", " .

		"`wait_before` = " . sql_formattext( $wait_before ) . ", " .
		"`wait_after` = " . sql_formattext( $wait_after ) . ", " .

		"`no_answer` = " . sql_formattext( $no_answer ) . ", " .
		"`allow_skip` = " . sql_formattext( $allow_skip ) . ", " .
		"`direct_dial` = " . sql_formattext( $direct_dial ) . ", " .
		"`return_ivr` = " . sql_formattext( $return_ivr ) . ", " .

		"`destination` = " . sql_formattext( $destination )
	;

	$result = $db->query( $sql );
	
	// if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'result: "' . is_array( $result ) ? join( $result ) : $result . '"' );

	if ( DB::IsError( $result ) ) die_freepbx( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . $result->getMessage() . " - " . $sql );
	
}



// Edit
function texttospeech_edit( $id, $name, $text, $engine, $arguments, $wait_before, $wait_after, $no_answer, $allow_skip, $direct_dial, $return_ivr, $destination ) {

	$debug = false;

	// Files Delete
	texttospeech_files_delete( $id );
	
	// Files Create
	texttospeech_files_create( $name, $engine, $arguments, $text );
	
	// Database Update
	global $db;
	$sql = "UPDATE `texttospeech` SET" . " " .
		"`name` = " . sql_formattext( $name ) . ", " .

		"`engine` = " . sql_formattext( $engine ) . ", " .
		"`arguments` = " . sql_formattext( $arguments ) . ", " .

		"`wait_before` = " . sql_formattext( $wait_before ) . ", " .
		"`wait_after` = " . sql_formattext( $wait_after ) . ", " .

		"`no_answer` = " . sql_formattext( $no_answer ) . ", " .
		"`allow_skip` = " . sql_formattext( $allow_skip ) . ", " .
		"`direct_dial` = " . sql_formattext( $direct_dial ) . ", " .
		"`return_ivr` = " . sql_formattext( $return_ivr ) . ", " .

		"`destination` = " . sql_formattext( $destination ) . " " .

		"WHERE `id` = " . sql_formattext( $id )
	;
	
	if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'sql: "' . $sql . '"' );

	$result = $db->query( $sql );
	
	// if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'result: "' . is_array( $result ) ? join( $result ) : $result . '"' );
	
	if ( DB::IsError( $result ) ) die_freepbx( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . $result->getMessage() . " - " . $sql );
}



// Delete
function texttospeech_del( $id ) {

	$debug = false;

	// Old Files Delete
	texttospeech_files_delete( $id );
	
	// Database Delete
	global $db;
	$sql = "DELETE FROM `texttospeech` WHERE `id` = " . sql_formattext( $id );

	if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'sql: "' . $sql . '"' );

	$result = $db->query( $sql );
	
	// if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'result: "' . is_array( $result ) ? join( $result ) : $result . '"' );

	if ( DB::IsError( $result ) ) die_freepbx( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . $result->getMessage() . " - " . $sql );
}




/*
--------------------------------------------------------------------------------
Internal Functions
--------------------------------------------------------------------------------
*/



// Files Create
function texttospeech_files_create( $name, $engine, $arguments, $text ) {

	global $texttospeech_sound_cache;

	$debug = false;

	// Text File Create
	$textfile = $texttospeech_sound_cache . $name . ".txt";

	// Create Text File - with Overwrite
	if ( file_put_contents( $textfile, $text ) ) {

		if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Text written to file: " . $textfile );

	} else {

		die_freepbx( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "File could not be written: " . $textfile );
	}

	// Sound File Create
	$soundfile = $texttospeech_sound_cache . $name . ".wav";
	
	if ( file_exists( $soundfile ) ) {
		if ( ! unlink( $soundfile ) ) {
			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Sound file could not be deleted: " . $soundfile );
		}
	}

	// Engine Command Create
	switch ( $engine ) {

	case 'espeak':
		// Espeak Engine - Sox (Sound eXchange) dependency check
		// Note: espeak only produces 22 khz sample rate files, but asterisk needs 8 khz so "sox" is required to convert it.
		$command = $engine . " " . escapeshellcmd( $arguments ) ." -f " . escapeshellarg( $textfile ) . " --stdout | sox -t wav - -r 8000 " . escapeshellarg( $soundfile );
		break;

	case 'flite':
		$command = $engine . " " . escapeshellcmd( $arguments ) . " -f " . escapeshellarg( $textfile ) . " -o " . escapeshellarg( $soundfile );
		break;

	case 'swift':
		$command = $engine . " " . escapeshellcmd( $arguments ) . " -p audio/channels=1,audio/sampling-rate=8000 -f " . escapeshellarg( $textfile ) . " -o " . escapeshellarg( $soundfile );
		break;

	case 'text2wave':
		$command = $engine . " " . escapeshellcmd( $arguments ) . " -f 8000 " . escapeshellarg( $textfile ) . " -o " . escapeshellarg( $soundfile );
		break;

	default:
	}

	// Command Execute
	if ( ! $command == "" ) {

		if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . 'Engine Command: "' . $command . '"' );

		exec( $command, $ignore_output, $return_value );

		if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Return value: " . $return_value );

		return $return_value;
	}
}



// Files Delete
function texttospeech_files_delete( $id ) {

	global $texttospeech_sound_cache;

	$debug = false;

	// Info
	$row = texttospeech_get( $id );
	$name = $row['name'];
	
	// Filenames
	$textfile = $texttospeech_sound_cache . $name . ".txt";
	$soundfile = $texttospeech_sound_cache . $name . ".wav";

	// Text File
	if ( file_exists( $textfile ) ) {
		if ( ! unlink( $textfile ) ) {
			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Text file could not be deleted: " . $textfile );
		}
	}

	// Sound File
	if ( file_exists( $soundfile ) ) {
		if ( ! unlink( $soundfile ) ) {
			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Sound file could not be deleted: " . $soundfile );
		}
	}
}



// Engine Discovery
function texttospeech_find_engines( $choices ) {

	$debug = false;

    $installed = array();

	foreach( $choices as $engine ) {
	
		if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Engine: " . $engine );

		if ( $engine != "espeak" ) {

			// PATH Check for Engine Binary
			$lastline = exec( "which $engine", $ignore_output, $return_value );
		
			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Path: " . $lastline );

		} else {

			// Espeak Engine - Sox (Sound eXchange) dependency check
			// Note: espeak only produces 22 khz sample rate files, but asterisk needs 8 khz so "sox" is required to convert it.
			$lastline = exec( "which sox" . " $engine", $ignore_output, $return_value );
			
			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Path: " . $lastline );
		}

		if ( $return_value == 0 ) {
		
			// Engine Add
			array_push( $installed, $engine );

			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Detected: " . $engine );
				
		} else {

			if ( $debug ) freepbx_debug( basename( __FILE__ ) . " - " . __FUNCTION__ . "() - " . "Not detected: " . $engine );

		}
	}

	return $installed;
}

?>
