<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.basic.php - part of getID3()                     //
// Sample script showing most basic use of getID3()            //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////



require_once "../../cgi-bin/admin_srb_libs/lib.php";
// include getID3() library (can be in a different directory if full path is specified)
require_once('../parts/get_id3/getid3/getid3.php');

function read_length_write_tag ($remotefilename, $pathfilename, $artist, $title, $audio_length, $set_mp3gain) {

	$log_message = "start ".date('l jS \of F Y h:i:s A')."\n".$artist." - ".$title."\n";
	// Copy remote file locally to scan with getID3()

	if ( $fp_remote = fopen( $remotefilename, 'rb')) {
		$localtempfilename = tempnam('/tmp', 'getID3');

		if ( $fp_local = fopen($localtempfilename, 'wb')) {
			while ($buffer = fread($fp_remote, 8192)) {
				fwrite( $fp_local, $buffer); 
			}
			fclose($fp_local);
		}
	}

	fclose( $fp_remote );

	// Initialize getID3 engine
	$getID3 = new getID3;
	$ThisFileInfo = $getID3->analyze( $localtempfilename );

	// compare
	$need_change_id3 = "no";
	if ( get_time_in_hms ( @$ThisFileInfo['playtime_seconds'] ) != $audio_length ) { 
		$need_change_id3 = "yes";  
		$log_message .= $need_change_id3." playtime \n". $audio_length." - ".get_time_in_hms ( @$ThisFileInfo['playtime_seconds'])."\n";
	}
	if ( @$ThisFileInfo['tags']['id3v2']['title'][0] != $title ) { 
		$need_change_id3 = "yes"; 
		$log_message .= $need_change_id3." title\n";
	}
	if ( @$ThisFileInfo['tags']['id3v2']['artist'][0] != $artist ) { 
		$need_change_id3 = "yes"; 
		$log_message .= $need_change_id3." artist\n";
	}

	$log_message .= $need_change_id3."\n";
	//$rgaintags = @$ThisFileInfo['tags']['ape']['replay_gain'][0];
	//write_log_file( $log_message, $ThisFileInfo );
	//write_log_file($log_message, $ThisFileInfo['replay_gain']);
	//write_log_file($log_message, $ThisFileInfo['replay_gain']['mp3gain']);
	//write_log_file($log_message, $ThisFileInfo['replay_gain']['track']);
	//write_log_file($log_message, $ThisFileInfo['replay_gain']['track']['peak']);

	if ( $need_change_id3 = "yes" ) {
		// Settings
		$tbl_row_user_special = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'PO_Logging_Config'");
	
		// prepare tag
		$TaggingFormat = 'UTF-8';
		//$TaggingFormat = 'ISO-8859-1';
		$getID3->setOption(array('encoding'=>$TaggingFormat));
		// Initialize getID3 tag-writing module
		require_once('../parts/get_id3/getid3/write.php');
		$tagwriter = new getid3_writetags;
		$tagwriter->filename       = $localtempfilename;
		//$tagwriter->tagformats     = array('id3v1', 'id3v2.3', 'ape');
		//$tagwriter->tagformats     = array('id3v2.4', 'ape');
		$tagwriter->tagformats     = array('id3v2.4');
		// set various options (optional)
		//$tagwriter->overwrite_tags = true;
		$tagwriter->tag_encoding   = $TaggingFormat;
		//$tagwriter->remove_other_tags = true;

		// populate data array
		$TagData['title'][] = $title;
		$TagData['artist'][] = $artist;
		#$TagData['album'][]   = 'SRB - Das Buergerradio';
		$TagData['album'][] = $tbl_row_user_special->USER_SP_PARAM_3." - ".$tbl_row_user_special->USER_SP_PARAM_4;
		//$TagData['year'][]    = date("Y");
		$TagData['release_time'][] = date("Y");
		$tagwriter->tag_data = $TagData;

		// write tags v2
		if ($tagwriter->WriteTags()) {
			$log_message .= "write v2\n";
			//echo 'Successfully wrote tags<br>';
			if (!empty( $tagwriter->warnings )) {
				echo 'There were some warnings:<br>'.implode('<br><br>', $tagwriter->warnings);
			}
		} else {
			echo 'Failed to write tags V2!<br>'.implode('<br><br>', $tagwriter->errors);
		}

	//$TaggingFormat = 'ISO-8859-1';
	//$getID3->setOption(array('encoding'=>$TaggingFormat));
	//$tagwriter = new getid3_writetags;
	//$tagwriter->filename       = $localtempfilename;
	//$tagwriter->tagformats = array('id3v1');
	//$tagwriter->tag_encoding   = $TaggingFormat;
	//$TagData['album'][0] = replace_umlaute_sonderzeichen('SRB - Das Bürgerradio');
	//$tagwriter->tag_data = $TagData;

	// write tags v1
	//if ($tagwriter->WriteTags()) {
		//$log_message .= "write v1\n";
		//echo 'Successfully wrote tags<br>';
		//if (!empty( $tagwriter->warnings )) {
			//echo 'There were some warnings:<br>'.implode('<br><br>', $tagwriter->warnings);
		//}
	//} else {
		//echo 'Failed to write tags V1!<br>'.implode('<br><br>', $tagwriter->errors);
	//}
		
	}// End change_id3
	
	
	$need_change_mp3gain = "no";
	
		//if (@$ThisFileInfo['playtime_seconds'] < 600 ){
		// mp3Gain pruefen wenn gewuenscht, nur wenn nicht zu lang!
		if ( $set_mp3gain == "yes" ) {
				//$patfilename = $tbl_row_config_a->USER_SP_PARAM_1.$_POST['form_sg_filename'];
				//$patfilename = "E:/Media_HF/temp/".$tbl_row_config->USER_SP_PARAM_1.$_POST['form_sg_filename'];
				$cmd = "C:/Programme/MP3Gain/mp3gain.exe -r ".$localtempfilename;
				set_time_limit( 300 );
				session_write_close();
				$script_messages = shell_exec( $cmd );
				$log_message .= $script_messages;
				// nur wenn mp3gain geaendert hat unten die geanederte datei zurueckschreiben				
				if ( !preg_match ( "/No changes to/", $script_messages )){ $need_change_mp3gain = "yes"; }
				//echo $script_messages;
				session_start();
		} //}// Ende mp3Gain wenn gewuenscht			

		
	if ( $need_change_id3 == "yes" or $need_change_mp3gain == "yes" ) {	
		// delete original file
		unlink( $pathfilename );		
		if ( !copy( $localtempfilename, $pathfilename )) {
   		print ("failed to copy $pathfilename...<br>\n");
		} else {
			chmod($pathfilename, 0764 );
		}
	}

	// Delete temporary file
	unlink( $localtempfilename );

	//write_log_file( $log_message, $TagData );

	return @$ThisFileInfo['playtime_seconds']."<br>";            // playtime in minutes:seconds, formatted string
	//echo @$ThisFileInfo['error'][0]."<br>";
}

function write_log_file( $wert, $TagData ) {
	// write logfile
	$myFile = "/tmp/admin_srb_audiofile_edit.log";
	$fh = fopen($myFile, 'w+') or die("can't open file ".$myFile);
	$stringData = $wert."\n";
	fwrite($fh, $stringData);
	$a_results = print_r($TagData, true);
	fwrite($fh, $a_results);
	fclose($fh);
}
?>