<?php

// Asterisk Lib Folder Get
if ( ( isset( $asterisk_conf['astvarlibdir']) ? $asterisk_conf['astvarlibdir'] : '') == '') {
	$astlib_path = "/var/lib/asterisk";
} else {
	$astlib_path = $asterisk_conf['astvarlibdir'];
}

?>
