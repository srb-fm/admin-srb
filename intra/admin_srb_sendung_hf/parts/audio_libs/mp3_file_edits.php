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



// include getID3() library (can be in a different directory if full path is specified)
require_once('../parts/get_id3/getid3/getid3.php');

function read_length_write_tag ( $remotefilename, $pathfilename, $artist , $title, $audio_length, $set_mp3gain ){

$log_message = "start ".date('l jS \of F Y h:i:s A')."\n".$artist." - ".$title."\n";
// Copy remote file locally to scan with getID3()
//$remotefilename = 'http://ok.saalfeld.local/Media_HF/HF_Aktuell_Infotime/1052344_Schaller_Stausee_Flammen.mp3';
//$remotefilename = 'http://ok.saalfeld.local/Media_HF/HF_Aktuell_Sendung/1053335_Pueschel_Live-Hendrik_2_09_09_09.mp3';

if ( $fp_remote = fopen( $remotefilename, 'rb')) {
    $localtempfilename = tempnam('/tmp', 'getID3');
   
	//   echo $localtempfilename."<br>";
	if ( $fp_local = fopen( $localtempfilename, 'wb')) {
        while ($buffer = fread( $fp_remote, 8192)) {
            fwrite( $fp_local, $buffer); }
        fclose($fp_local);
	}}
   
   fclose( $fp_remote );

	// laenge holen
	// Initialize getID3 engine
	$getID3 = new getID3;
	$ThisFileInfo = $getID3->analyze( $localtempfilename );

	// vergleichen
	$need_change_id3 = "no";
	if ( get_time_in_hms ( @$ThisFileInfo['playtime_seconds'] ) != $audio_length ){ $need_change_id3 = "yes";  $log_message .= $need_change_id3." playtime \n". $audio_length." - ".get_time_in_hms ( @$ThisFileInfo['playtime_seconds'])."\n";}
	if ( @$ThisFileInfo['tags']['id3v2']['title'][0] != $title ){ $need_change_id3 = "yes"; $log_message .= $need_change_id3." title\n";}
	if ( @$ThisFileInfo['tags']['id3v2']['artist'][0] != $artist ){ $need_change_id3 = "yes"; $log_message .= $need_change_id3." artist\n";}
	
	$log_message .= $need_change_id3."\n";
		
	if ( $need_change_id3 = "yes" ){		
		// tag schreiben
		//$TaggingFormat = 'UTF-8';
		$TaggingFormat = 'ISO-8859-1';
		$getID3->setOption(array('encoding'=>$TaggingFormat));
		// Initialize getID3 tag-writing module
		require_once('../parts/get_id3/getid3/write.php');
		$tagwriter = new getid3_writetags;
		$tagwriter->filename       = $localtempfilename;
		$tagwriter->tagformats     = array('id3v1', 'id3v2.3');
		// set various options (optional)
		$tagwriter->overwrite_tags = true;
		$tagwriter->tag_encoding   = $TaggingFormat;
		//$tagwriter->remove_other_tags = true;

		// populate data array
		$TagData['title'][]   = $title;
		$TagData['artist'][]  = $artist;
		$TagData['album'][]   = 'SRB - Das Buergerradio';
		$TagData['year'][]    = date("Y");
		//$TagData['genre'][]   = 'Rock';
		//$TagData['comment'][] = 'excellent!';
		//$TagData['track'][]   = '04/16';
		
		// save ape-tags for new writing: mp3gain
		$ReplayGainTagsToPreserve = array('mp3gain_minmax', 'mp3gain_album_minmax', 'mp3gain_undo', 'replaygain_track_peak', 'replaygain_track_gain', 'replaygain_album_peak', 'replaygain_album_gain');
		foreach ($ReplayGainTagsToPreserve as $rg_key) {
			if (isset($ThisFileInfo['ape']['items'][strtolower($rg_key)]['data'][0]) ) {
				//$TagData[strtoupper($rg_key)][0] = $ThisFileInfo['ape']['items'][strtolower($rg_key)]['data'][0];
				$TagData[strtoupper($rg_key)][] = $ThisFileInfo['ape']['items'][strtolower($rg_key)]['data'][0];
			}
		}

		$tagwriter->tag_data = $TagData;

		// write tags
		if ($tagwriter->WriteTags()) {
			//echo 'Successfully wrote tags<br>';
			if (!empty( $tagwriter->warnings )) {
				echo 'There were some warnings:<br>'.implode('<br><br>', $tagwriter->warnings);
			}
		} else {
			echo 'Failed to write tags!<br>'.implode('<br><br>', $tagwriter->errors);
		}
		
	}// Ende change_id3
		
		
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

		
		if ( $need_change_id3 == "yes" or $need_change_mp3gain == "yes" ){	
			// delete original file
			unlink( $pathfilename );		
			if ( !copy( $localtempfilename, $pathfilename )) {
   			print ("failed to copy $pathfilename...<br>\n");
			}else {
				chmod($pathfilename, 0764 );
				}}
      // Delete temporary file
      unlink( $localtempfilename );



// Optional: copies data from all subarrays of [tags] into [comments] so
// metadata is all available in one location for all tag formats
// metainformation is always available under [tags] even if this is not called

//getid3_lib::CopyTagsToComments($ThisFileInfo);

// Output desired information in whatever format you want
// Note: all entries in [comments] or [tags] are arrays of strings
// See structure.txt for information on what information is available where
// or check out the output of /demos/demo.browse.php for a particular file
// to see the full detail of what information is returned where in the array
//echo @$ThisFileInfo['comments_html']['artist'][0]."<br>"; // artist from any/all available tag formats
//echo @$ThisFileInfo['tags']['id3v2']['title'][0]."<br>";  // title from ID3v2
//echo @$ThisFileInfo['audio']['bitrate']."<br>";           // audio bitrate

 //write_log_file( $log_message );

return @$ThisFileInfo['playtime_seconds']."<br>";            // playtime in minutes:seconds, formatted string
//echo @$ThisFileInfo['error'][0]."<br>";
}

function write_log_file( $wert ) {
	// logfile schreiben
	$myFile = "/tmp/admin_srb_audiofile_edit.log";
	$fh = fopen($myFile, 'w+') or die("can't open file ".$myFile);
	$stringData = $wert."\n";
	fwrite($fh, $stringData);
	fclose($fh);
}
?>