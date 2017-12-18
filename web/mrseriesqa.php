<?
 // ------------------------------------------------------------------------------
 // NiDB mrseriesqa.php
 // Copyright (C) 2004 - 2017
 // Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
 // Olin Neuropsychiatry Research Center, Hartford Hospital
 // ------------------------------------------------------------------------------
 // GPLv3 License:

 // This program is free software: you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation, either version 3 of the License, or
 // (at your option) any later version.

 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.

 // You should have received a copy of the GNU General Public License
 // along with this program.  If not, see <http://www.gnu.org/licenses/>.
 // ------------------------------------------------------------------------------
	session_start();
?>

<?
	//require "config.php";
	require "functions.php";
	require "includes.php";

	$id = GetVariable("id");

	/* get the path to the QA info */
	$sqlstring = "select a.*, b.study_num, d.uid from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.mrseries_id = $id";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$series_num = $row['series_num'];
	$study_num = $row['study_num'];
	$uid = $row['uid'];
	
	$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";
	$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/qa";
	$thumbinfo = getimagesize("$qapath/thumb_lut.png");
	$thumbheight = $thumbinfo[1];
?>
	<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>QA for <?=$uid?> study <?=$study_num?> series <?=$series_num?></title>
		<script type="text/javascript" src="scripts/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="scripts/jquery.flot.js"></script>
	</head>
	
	<body style="font-family: arial, helvetica, sans serif">
	
	<style>
		/* 2 column table (small table with 2 columns: variable, value)*/
		.twocoltable { border-spacing: 0px; padding: 0px; border: 1px solid #666666; }
		.twocoltable th { text-align: center; font-weight: bold; color: black; font-size: 12pt; padding: 4px; background-color: #DDD; }
		.twocoltable td.name { text-align: right; vertical-align: top; font-weight: bold; color: darkblue; font-size: 10pt; padding: 4px; border-top: 1px solid #DDD; border-right: 1px solid #ddd; }
		.twocoltable td.value { text-align: left; vertical-align: top; font-weight: normal; color: #333333; font-size: 10pt; padding: 4px; border-top: 1px solid #ddd; white-space: nowrap; }
		
		.tiny { font-size:8pt; color:gray}
	</style>

	<table class="twocoltable">
		<thead>
			<tr>
				<th>QC Module</th>
				<th>Result</th>
				<th>Value</th>
			</tr>
		</thead>
	<?
		$sqlstring = "select *, uncompress(qcresults_valuetext) 'valuetext' from qc_moduleseries a left join qc_results b on a.qcmoduleseries_id = b.qcmoduleseries_id left join qc_modules c on a.qcmodule_id = c.qcmodule_id where a.series_id = $id and a.modality = 'mr' order by a.qcmodule_id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$graphnum = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			//PrintVariable($row,'row');
			$number = $row['qcresults_valuenumber'];
			$text = $row['valuetext'];
			$file = $row['qcresults_valuefile'];
			$moduleid = $row['qcmodule_id'];
			$modulename = $row['qcm_name'];
			$cputime = $row['cpu_time'];
			$resultnameid = $row['qcresultname_id'];
			
			/* get the result name */
			$sqlstringA = "select * from qc_resultnames where qcresultname_id = '$resultnameid'";
			//PrintSQL($sqlstringA);
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$resultname = $rowA['qcresult_name'];
			$resulttype = $rowA['qcresult_type'];
			$units = $rowA['qcresult_units'];
			$labels = $rowA['qcresult_labels'];
			
			if ($resultname == "") { $resultname = $file; }
			
			?>
			<tr>
				<td valign="top">
					<?
					if ($moduleid != $lastmoduleid) {
						echo "$modulename";
					}
					?>
				</td>
				<td valign="top" class="name">
					<?=$resultname?>
				</td>
				<td class="value">
				<?
				if (trim($number) != "") {
					echo "$number <span class='tiny'>$units</span>";
				}
				elseif (trim($text) != "") {
					switch ($resulttype) {
						case 'graph': DisplayGraph($text,$resultname,$units,$labels,$graphnum); $graphnum++; break;
						case 'histogram': DisplayHistogram($text,$resultname,$units,$labels); break;
						case 'minmax': DisplayMinMax($text,$resultname,$units,$labels); break;
					}
					//echo $text;
				}
				elseif (trim($file) != "") {
					$filepath = "$qapath/$file";
					?>
					<img style="border: solid 1px #666666; " src="data:image/png;base64,<?=base64_encode(file_get_contents("$filepath"))?>">
					<?
				}
				?>
				</td>
			</tr>
			<?
			
			$lastmoduleid = $moduleid;
		}
	?>
	
	<?
	$motionfile = "$qapath/MotionCorrection.txt";
	if (file_exists($motionfile)) {
		$filecontents = file($motionfile);
		
		$i = 0;
		$maxx = $maxy = $maxz = $maxpitch = $maxroll = $maxyaw = 0.0;
		
		foreach ($filecontents as $line) {
			$line = trim($line);
			list($pi,$ro,$ya,$x,$y,$z) = preg_split('/\s+/', $line);
			
			$fmovex[] = "[$i, " . number_format($x,6,'.','') . "]";
			$fmovey[] = "[$i, " . number_format($y,6,'.','') . "]";
			$fmovez[] = "[$i, " . number_format($z,6,'.','') . "]";
			$fmovepi[] = "[$i, " . number_format($pi,6,'.','') . "]";
			$fmovero[] = "[$i, " . number_format($ro,6,'.','') . "]";
			$fmoveya[] = "[$i, " . number_format($ya,6,'.','') . "]";

			$movex[] = $x;
			$movey[] = $y;
			$movez[] = $z;
			$movepi[] = $pi;
			$movero[] = $ro;
			$moveya[] = $ya;
			
			$i++;
		}
		$minx = min($movex);
		$miny = min($movey);
		$minz = min($movez);
		$minpitch = min($movepi);
		$minroll = min($movero);
		$minyaw = min($moveya);
		
		$maxx = max($movex);
		$maxy = max($movey);
		$maxz = max($movez);
		$maxpitch = max($movepi);
		$maxroll = max($movero);
		$maxyaw = max($moveya);
		
		$stdx = StdDev($movex);
		$stdy = StdDev($movey);
		$stdz = StdDev($movez);
		$stdpitch = StdDev($movepi);
		$stdroll = StdDev($movero);
		$stdyaw = StdDev($moveya);
		
		$rangex = abs($minx) + abs($maxx);
		$rangey = abs($miny) + abs($maxy);
		$rangez = abs($minz) + abs($maxz);
		$rangepitch = abs($minpitch) + abs($maxpitch);
		$rangeroll = abs($minroll) + abs($maxroll);
		$rangeyaw = abs($minyaw) + abs($maxyaw);
	?>
	</table>
	
	<table>
		<tr>
			<td align="center">
				<b>Translation</b>
				<div id="movementgraph" style="width:600px;height:180px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Movement <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
						<td align="center">Total <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
					</tr>
					<tr>
						<td><b>X</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minx,2)?> &emsp;<?=number_format($maxx,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangex,2)?></b> &plusmn;<?=number_format($stdx,2)?></td>
					</tr>
					<tr>
						<td><b>Y</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($miny,2)?> &emsp;<?=number_format($maxy,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangey,2)?></b> &plusmn;<?=number_format($stdy,2)?></td>
					</tr>
					<tr>
						<td><b>Z</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minz,2)?> &emsp;<?=number_format($maxz,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangez,2)?></b> &plusmn;<?=number_format($stdz,2)?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td align="center">
				<b>Rotation</b>
				<div id="rotationgraph" style="width:600px;height:180px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Rotation <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
						<td align="center">Total <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
					</tr>
					<tr>
						<td><b>Pitch</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minpitch,2)?>&deg; &emsp;<?=number_format($maxpitch,2)?>&deg;</td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangepitch,2)?></b> &plusmn;<?=number_format($stdpitch,2)?></td>
					</tr>
					<tr>
						<td><b>Roll</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minroll,2)?>&deg; &emsp;<?=number_format($maxroll,2)?>&deg;</td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeroll,2)?></b> &plusmn;<?=number_format($stdroll,2)?></td>
					</tr>
					<tr>
						<td><b>Yaw</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minyaw,2)?>&deg; &emsp;<?=number_format($maxyaw,2)?>&deg;</td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeyaw,2)?></b> &plusmn;<?=number_format($stdyaw,2)?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?
		$accelfile = "$qapath/MotionCorrection2.txt";
		//echo "$accelfile";
		if (file_exists($accelfile)) {
			$filecontents = file($accelfile);
			$faccx = str_replace("\n","",$filecontents[0]);
			$faccy = str_replace("\n","",$filecontents[1]);
			$faccz = str_replace("\n","",$filecontents[2]);
			$accx = explode(",",$faccx);
			$accy = explode(",",$faccy);
			$accz = explode(",",$faccz);
		}
		$i = 0;
		foreach ($accx as $value) {
			$ffaccx[] = "[$i, " . number_format($value,6,'.','') . "]";
			$i++;
		}
		$i = 0;
		foreach ($accy as $value) {
			$ffaccy[] = "[$i, " . number_format($value,6,'.','') . "]";
			$i++;
		}
		$i = 0;
		foreach ($accz as $value) {
			$ffaccz[] = "[$i, " . number_format($value,6,'.','') . "]";
			$i++;
		}
		
		$minax = min($accx);
		$minay = min($accy);
		$minaz = min($accz);
		$maxax = max($accx);
		$maxay = max($accy);
		$maxaz = max($accz);
		$rangeax = abs($minax) + abs($maxax);
		$rangeay = abs($minay) + abs($maxay);
		$rangeaz = abs($minaz) + abs($maxaz);
		$stdax = StdDev($accx);
		$stday = StdDev($accy);
		$stdaz = StdDev($accz);
	?>

	<table>
		<tr>
			<td align="center">
				<b>Velocity</b>
				<div id="accelgraph" style="width:600px;height:180px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Velocity <span style="font-weight: normal; font-size: 8pt; color: gray">mm/s</span></td>
						<td align="center">Total <span style="font-weight: normal; font-size: 8pt; color: gray">mm/s</span></td>
					</tr>
					<tr>
						<td><b>X</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minax,2)?> &emsp;<?=number_format($maxax,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeax,2)?></b> &plusmn;<?=number_format($stdax,2)?></td>
					</tr>
					<tr>
						<td><b>Y</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minay,2)?> &emsp;<?=number_format($maxay,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeay,2)?></b> &plusmn;<?=number_format($stday,2)?></td>
					</tr>
					<tr>
						<td><b>Z</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minaz,2)?> &emsp;<?=number_format($maxaz,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeaz,2)?></b> &plusmn;<?=number_format($stdaz,2)?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<?
		$meanintensityfile = "$qapath/meanIntensityOverTime.txt";
		//echo "$accelfile";
		if (file_exists($meanintensityfile)) {
			$filecontents = file($meanintensityfile);
			$fmint = str_replace("\n","",$filecontents);
		}
		$i = 0;
		foreach ($fmint as $value) {
			$ffmint[] = "[$i, " . number_format($value,6,'.','') . "]";
			$i++;
		}
		
		$minmint = min($fmint);
		$maxmint = max($fmint);
		$rangemint = $maxmint - $minmint;
		$stdmint = StdDev($fmint);
		
		$stdintensityfile = "$qapath/stdevIntensityOverTime.txt";
		//echo "$accelfile";
		if (file_exists($stdintensityfile)) {
			$filecontents = file($stdintensityfile);
			$fsint = str_replace("\n","",$filecontents);
		}
		$i = 0;
		foreach ($fsint as $value) {
			$ffsint[] = "[$i, " . number_format($value,6,'.','') . "]";
			$i++;
		}
		
		$minsint = min($fsint);
		$maxsint = max($fsint);
		$rangesint = $maxsint - $minsint;
		$stdsint = StdDev($fsint);

	?>

	<table>
		<tr>
			<td align="center">
				<b>Image Intensity</b>
				<div id="intensitygraph" style="width:600px;height:180px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Voxel Intensity <span style="font-weight: normal; font-size: 8pt; color: gray">intensity</span></td>
						<td align="center">Range <span style="font-weight: normal; font-size: 8pt; color: gray">intensity</span></td>
					</tr>
					<tr>
						<td><b>Mean Intensity</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minmint,2)?> &emsp;<?=number_format($maxmint,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangemint,2)?></b> &plusmn;<?=number_format($stdmint,2)?></td>
					</tr>
					<!--
					<tr>
						<td><b>Mean Intensity</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minsint,2)?> &emsp;<?=number_format($maxsint,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangesint,2)?></b> &plusmn;<?=number_format($stdsint,2)?></td>
					</tr>
					-->
				</table>
			</td>
		</tr>
	</table>


	<?
		$cogmmfile = "$qapath/centerOfGravityOverTimeMM.txt";
		//echo "$accelfile";
		if (file_exists($cogmmfile)) {
			$filecontents = file($cogmmfile);
			$fcogmm = str_replace("\n","",$filecontents);
		}
		$i = 0;
		foreach ($fcogmm as $value) {
			$ffcogmm[] = "[$i, " . number_format($value,6,'.','') . "]";
			$i++;
		}
		
		$mincogmm = min($fcogmm);
		$maxcogmm = max($fcogmm);
		$rangecogmm = $maxcogmm - $mincogmm;
		$stdcogmm = StdDev($fcogmm);
	?>

	<table>
		<tr>
			<td align="center">
				<b>Center of Gravity</b>
				<div id="cogmmgraph" style="width:600px;height:180px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Center of Gravity <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
						<td align="center">Range <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
					</tr>
					<tr>
						<td><b>Location</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($mincogmm,2)?> &emsp;<?=number_format($maxcogmm,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangecogmm,2)?></b> &plusmn;<?=number_format($stdcogmm,2)?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	
	<script id="source" language="javascript" type="text/javascript">
		$(function () {
			var movex = [<? echo implode(', ',$fmovex); ?>];
			var movey = [<? echo implode(', ',$fmovey); ?>];
			var movez = [<? echo implode(', ',$fmovez); ?>];
			$.plot($("#movementgraph"), [ { label: "x (mm)", color: '#F00', data: movex }, { label: "y (mm)", color: '#4B4', data: movey }, { label: "z (mm)", color: '#00F', data: movez } ]);

			var accx = [<? echo implode(', ',$ffaccx); ?>];
			var accy = [<? echo implode(', ',$ffaccy); ?>];
			var accz = [<? echo implode(', ',$ffaccz); ?>];
			$.plot($("#accelgraph"), [ { label: "x (mm/s)", color: '#F00', data: accx }, { label: "y (mm/s)", color: '#4B4', data: accy }, { label: "z (mm/s)", color: '#00F', data: accz } ]);

			var movepi = [<? echo implode(', ',$fmovepi); ?>];
			var movero = [<? echo implode(', ',$fmovero); ?>];
			var moveya = [<? echo implode(', ',$fmoveya); ?>];
			$.plot($("#rotationgraph"), [ { label: "pitch (deg)", color: '#F00', data: movepi }, { label: "roll (deg)", color: '#4B4', data: movero }, { label: "yaw (deg)", color: '#00F', data: moveya } ]);
			
			var mint = [<? echo implode(', ',$ffmint); ?>];
			//var sint = [<? echo implode(', ',$ffsint); ?>];
			$.plot($("#intensitygraph"), [ { label: "intensity (mean)", color: '#F00', data: mint } ]);
			//$.plot($("#intensitygraph"), [ { label: "intensity (mean)", color: '#F00', data: mint }, { label: "intensity (stdev)", color: '#4B4', data: sint } ]);
			
			var mcogmm = [<? echo implode(', ',$ffcogmm); ?>];
			//var sint = [<? echo implode(', ',$ffsint); ?>];
			$.plot($("#cogmmgraph"), [ { label: "COG (mm)", color: '#F00', data: mcogmm } ]);
		});
	</script>
	<?
	}
	?>
	<br><br>
	<table>
		<tr>
			<td align="right"><b>Middle slice</b></td>
			<td align="left">
				<? if (file_exists($thumbpath)) { ?>
				<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$thumbpath"))?>">
				<? } else { ?>
				<span class="tiny">Thumbnail does not exist</span>
				<? } ?>
			</td>
		</tr>
		<tr>
			<td align="right"><b>FFT (single slice)</b></td>
			<td align="left">
				<? if (file_exists("$qapath/thumb_fft.png")) { ?>
				<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/thumb_fft.png"))?>"><br>
				<? } else { ?>
				<span class="tiny">Thumbnail does not exist</span>
				<? } ?>
			</td>
		</tr>
		<tr>
			<td align="right"><b>Radial average of FFT</b></td>
			<td align="left">
				<? if (file_exists("$qapath/histogram.png")) { ?>
				<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/histogram.png"))?>"><br>
				(all slices)
				<? } ?>
				<? if (file_exists("$qapath/thumb_fft_1d.png")) { ?>
				<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/thumb_fft_1d.png"))?>"><br>
				(single slice)
				<? } else { ?>
				<span class="tiny">Thumbnail does not exist</span>
				<? } ?>
			</td>
		</tr>
		<?
			$minMaxMeanFile = "$qapath/minMaxMean.txt";
			//echo "$minMaxMeanFile";
			if (file_exists($minMaxMeanFile)) {
				$parts = explode(' ', file_get_contents($minMaxMeanFile));
				$minMean = $parts[0];
				$maxMean = $parts[1];
				//echo "$minMean - $maxMean";
			}
			
			$minMaxSigmaFile = "$qapath/minMaxSigma.txt";
			//echo "$minMaxSigmaFile";
			if (file_exists($minMaxSigmaFile)) {
				$parts = explode(' ', file_get_contents($minMaxSigmaFile));
				$minSigma = $parts[0];
				$maxSigma = $parts[1];
				//echo "$minSigma - $maxSigma";
			}
			
			$minMaxVarianceFile = "$qapath/minMaxVariance.txt";
			//echo "$minMaxVarianceFile";
			if (file_exists($minMaxVarianceFile)) {
				$parts = explode(' ', file_get_contents($minMaxVarianceFile));
				$minVariance = $parts[0];
				$maxVariance = $parts[1];
				//echo "$minVariance - $maxVariance";
			}
		?>
		<? if (file_exists("$qapath/Tmean.png")) { ?>
		<tr>
			<td align="right"><b>Mean image (timeseries)</b></td>
			<td align="left">
				<table>
					<tr>
						<td>
							<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/Tmean.png"))?>">
						</td>
						<td style="font-size:9pt">
							<table style="font-size:10pt">
								<tr style="font-weight: bold">
									<td align="center"></td>
									<td align="center">Voxel Intensity</td>
								</tr>
								<tr>
									<td><b>min max</b></td>
									<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minMean,2)?> &emsp;<?=number_format($maxMean,2)?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<? } ?>
		<? if (file_exists("$qapath/Tsigma.png")) { ?>
		<tr>
			<td align="right"><b>Stddev image (timeseries)</b></td>
			<td align="left">
				<table>
					<tr>
						<td>
							<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/Tsigma.png"))?>">
						</td>
						<td style="font-size:9pt">
							<table style="font-size:10pt">
								<tr style="font-weight: bold">
									<td align="center"></td>
									<td align="center">Voxel Intensity</td>
								</tr>
								<tr>
									<td><b>min max</b></td>
									<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minSigma,2)?> &emsp;<?=number_format($maxSigma,2)?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<? } ?>
		<? if (file_exists("$qapath/Tvariance.png")) { ?>
		<tr>
			<td align="right"><b>Variance image (timeseries)</b></td>
			<td align="left">
				<table>
					<tr>
						<td>
							<img style="border: solid 1px #666666; max-width:400" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/Tvariance.png"))?>">
						</td>
						<td style="font-size:9pt">
							<table style="font-size:10pt">
								<tr style="font-weight: bold">
									<td align="center"></td>
									<td align="center">Voxel Intensity</td>
								</tr>
								<tr>
									<td><b>min max</b></td>
									<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minVariance,2)?> &emsp;<?=number_format($maxVariance,2)?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<? } ?>
	</table>	
	</body>
	</html>
	<?


# ----------------------------------------------------------
# --------- DisplayGraph -----------------------------------
# ----------------------------------------------------------
function DisplayGraph($text,$resultname,$units,$label,$graphnum)	{
		//$filecontents = file($motionfile);
		
		$labels = explode(',',$label);
		$lines = explode("\n",$text);
		
		$i = 0;
		$maxx = $maxy = $maxz = $maxpitch = $maxroll = $maxyaw = 0.0;
		
		$numcols = 0;
		$j = 0;
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line == "") {
				continue;
			}
			$parts = preg_split('/\s+/', $line);
			$numcols = count($parts);
			for ($i=0;$i<count($parts);$i++) {
				//$col[$i][] = $parts[$i];
				$col[$i][] = "[$j, " . number_format($parts[$i],5,'.','') . "]";
				$col2[$i][] = $parts[$i];
			}
			$j++;
		}
		
		//PrintVariable($col,'Col');
		
		for ($i=0;$i<$numcols;$i++) {
			$mins[$i] = min($col2[$i]);
			$maxs[$i] = max($col2[$i]);
			$stdevs[$i] = StdDev($col2[$i]);
			$ranges[$i] = $mins[$i] + $maxs[$i];
		}
		
		//print_r($mins);
		//print_r($maxs);
		//print_r($stdevs);
		//print_r($ranges);
	?>

	<script id="source" language="javascript" type="text/javascript">
		$(function () {
			<? for ($i=0;$i<$numcols;$i++) { ?>
			var col<?=$i?> = [<? echo implode(', ',$col[$i]); ?>];
			<?
				$opts[$i] = "{ label: '$labels[$i] ($units)', data: col$i }";
			} ?>
			$.plot($("#movementgraph<?=$graphnum?>"), [ <?=implode(', ',$opts);?> ]);
		});
	</script>

	<table>
		<tr>
			<td align="center">
				<b><?=$resultname?></b>
				<div id="movementgraph<?=$graphnum?>" style="width:600px;height:180px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Range <span style="font-weight: normal; font-size: 8pt; color: gray"><?=$units?></span></td>
						<td align="center">Total <span style="font-weight: normal; font-size: 8pt; color: gray"><?=$units?></span></td>
					</tr>
					<?
					for ($i=0;$i<$numcols;$i++) {
					?>
					<tr>
						<td><b><?=$labels[$i]?></b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($mins[$i],2)?> &emsp;<?=number_format($maxs[$i],2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($ranges[$i],2)?></b> &plusmn;<?=number_format($stdevs[$i],2)?></td>
					</tr>
					<?
					}
					?>
				</table>
			</td>
		</tr>
	</table>
	<?
}


# ----------------------------------------------------------
# --------- DisplayHistogram -------------------------------
# ----------------------------------------------------------
function DisplayHistogram($text,$resultname,$units,$label) {
}


# ----------------------------------------------------------
# --------- DisplayMinMax ----------------------------------
# ----------------------------------------------------------
function DisplayMinMax($text,$resultname,$units,$label) {
	list($min,$max) = preg_split('/\s+/', trim($text));
	?>
		<?=number_format($min,2)?> <span class="tiny"><?=$units?></span> - <?=number_format($max,2)?> <span class="tiny"><?=$units?></span>
	<?
}

	
# ----------------------------------------------------------
# --------- StdDev -----------------------------------------
# ----------------------------------------------------------
function StdDev($a)
{
  //variable and initializations
  $the_standard_deviation = 0.0;
  $the_variance = 0.0;
  $the_mean = 0.0;
  $the_array_sum = array_sum($a); //sum the elements
  $number_elements = count($a); //count the number of elements

  //calculate the mean
  $the_mean = $the_array_sum / $number_elements;

  //calculate the variance
  for ($i = 0; $i < $number_elements; $i++)
  {
    //sum the array
    $the_variance = $the_variance + ($a[$i] - $the_mean) * ($a[$i] - $the_mean);
  }

  $the_variance = $the_variance / $number_elements;

  //calculate the standard deviation
  $the_standard_deviation = pow( $the_variance, 0.5);

  //return the variance
  return $the_standard_deviation;
}
?>
