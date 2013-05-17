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



function read_mp3_length ( $remotefilename ){

// Copy remote file locally to scan with getID3()
//$remotefilename = 'http://ok.saalfeld.local/Media_HF/HF_Aktuell_Infotime/1052344_Schaller_Stausee_Flammen.mp3';
//$remotefilename = 'http://ok.saalfeld.local/Media_HF/HF_Aktuell_Sendung/1053335_Pueschel_Live-Hendrik_2_09_09_09.mp3';

if ($fp_remote = fopen($remotefilename, 'rb')) {
    $localtempfilename = tempnam('/tmp', 'getID3');
// diese kontrollmeldung bringt wirft den headerfehler:   
//   echo $localtempfilename."<br>";
    if ($fp_local = fopen($localtempfilename, 'wb')) {
        while ($buffer = fread($fp_remote, 8192)) {
            fwrite($fp_local, $buffer);
        }
        fclose($fp_local);

		// Initialize getID3 engine
		$getID3 = new getID3;

		$ThisFileInfo = $getID3->analyze($localtempfilename);

        // Delete temporary file
        unlink($localtempfilename);
    }
    fclose($fp_remote);
}



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


return @$ThisFileInfo['playtime_seconds']."<br>";            // playtime in minutes:seconds, formatted string
//echo @$ThisFileInfo['error'][0]."<br>";
}
?>