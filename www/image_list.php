<?php
   //
   // Alberto Pasca on 31/10/2016
   // JSON LIST of media content
   // ----------------------------
   // Note: it's just an extract of the preview.php original
   //       page. Instead of html, returns json data.
   //
   
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');

   //Set to top or bottom to position controls
   define('CONTROLS_POS', 'top');

   //Set size defaults and try to get from cookies
   $previewSize = 640;
   $thumbSize = 96;
   $sortOrder = 1;
   $showTypes = 1;
   $timeFilter = 1;
   $timeFilterMax = 8;
   $dSelect = "";
   $pFile = "";
   $tFile = "";
   $debugString = "";

   //function to draw 1 file on the page
   function drawFile($f, $ts, $sel) {
      $fType = getFileType($f);
      $rFile = dataFilename($f);
      $fNumber = getFileIndex($f);
      $lapseCount = "";
      switch ($fType) {
         case 'v': $fIcon = 'video.png'; break;
         case 't':
            $fIcon = 'timelapse.png';
            $lapseCount = '(' . count(findLapseFiles($f)). ')';
            break;
         case 'i': $fIcon = 'image.png'; break;
         default : $fIcon = 'image.png'; break;
      }
      $duration = '-1';
      if (file_exists(MEDIA_PATH . "/$rFile")) {
         $fsz = round ((filesize_n(MEDIA_PATH . "/$rFile")) / 1024);
         $fModTime = filemtime(MEDIA_PATH . "/$rFile");
         if ($fType == 'v') {
            $duration = ($fModTime - filemtime(MEDIA_PATH . "/$f")) . 's';
         }
      } else {
         $fsz = 0;
         $fModTime = filemtime(MEDIA_PATH . "/$f");
      }
      $fDate = @date('Y-m-d', $fModTime);
      $fTime = @date('H:i:s', $fModTime);
      $fWidth = max($ts + 4, 150);

      return
        array(
          'hires' => $rFile,
          'lowres' => $f,
          'size' => $fsz,
          'date' => $fDate,
          'time' => $fTime,
          'duration' => $duration,
          'type' => $fType,
          'path' => "http://" . $_SERVER[HTTP_HOST] . "/html/" . MEDIA_PATH . "/"
        );

   }

   function getThumbnails() {
      global $sortOrder;
      global $showTypes;
      global $timeFilter, $timeFilterMax;
      //$files = scandir(MEDIA_PATH, $sortOrder - 1);
      $files = scandir(MEDIA_PATH);
      $thumbnails = array();
      $nowTime = time();
      foreach($files as $file) {
         if($file != '.' && $file != '..' && isThumbnail($file)) {
			      $fTime = filemtime(MEDIA_PATH . "/$file");
            if ($timeFilter == 1) {
               $include = true;
            } else {
               $timeD = $nowTime - $fTime;
               if ($timeFilter == $timeFilterMax) {
                  $include = ($timeD >= 86400 * ($timeFilter-1));
               } else {
                  $include = ($timeD >= (86400 * ($timeFilter - 2))) && ($timeD < (($timeFilter - 1) * 86400));
               }
            }
            if($include) {
               $fType = getFileType($file);
               if(($showTypes == '1') || ($showTypes == '2' && ($fType == 'i' || $fType == 't')) || ($showTypes == '3' && ($fType == 'v'))) {
                  $thumbnails[$file] = $fType . $fTime;
               }
            }
         }
      }
	  if ($sortOrder == 1) {
		  asort($thumbnails);
	  } else {
		  arsort($thumbnails);
	  }
	  $thumbnails = array_keys($thumbnails);
      return $thumbnails;
   }



   $f = fopen(BASE_DIR . '/' . CONVERT_CMD, 'r');
   $convertCmd = trim(fgets($f));
   fclose($f);
   $thumbnails = getThumbnails();

   $json = array();
   header('Content-Type: application/json');

  foreach($thumbnails as $file) {
     array_push( $json, drawFile($file, $thumbSize, $dSelect) );
  }

  die( stripslashes(json_encode($json)) );

?>
