<?
 // ------------------------------------------------------------------------------
 // NiDB subjects.php
 // Copyright (C) 2004 - 2019
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

	define("LEGIT_REQUEST", true);
	
	session_start();
	
	$debug = false;
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Subjects</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($GLOBALS);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$selectedid = GetVariable("selectedid");
	$projectid = GetVariable("projectid");
	$newprojectid = GetVariable("newprojectid");
	$enrollmentid = GetVariable("enrollmentid");
	$encrypt = GetVariable("encrypt");
	$name = GetVariable("name");
	$lastname = GetVariable("lastname");
	$firstname = GetVariable("firstname");
	$fullname = GetVariable("fullname");
	$dob = GetVariable("dob");
	$gender = GetVariable("gender");
	$ethnicity1 = GetVariable("ethnicity1");
	$ethnicity2 = GetVariable("ethnicity2");
	$handedness = GetVariable("handedness");
	$education = GetVariable("education");
	$phone = GetVariable("phone");
	$email = GetVariable("email");
	$maritalstatus = GetVariable("maritalstatus");
	$smokingstatus = GetVariable("smokingstatus");
	$cancontact = GetVariable("cancontact");
	$tags = GetVariable("tags");
	$enrollgroup = GetVariable("enrollgroup");
	$uid = GetVariable("uid");
	$altuids = GetVariable("altuids");
	$enrollmentids = GetVariable("enrollmentids");
	$guid = GetVariable("guid");
	$searchuid = trim(GetVariable("searchuid"));
	$searchaltuid = trim(GetVariable("searchaltuid"));
	$searchname = trim(GetVariable("searchname"));
	$searchgender = trim(GetVariable("searchgender"));
	$searchdob = trim(GetVariable("searchdob"));
	$searchactive = GetVariable("searchactive");
	$uid2 = GetVariable("uid2");
	$relation = GetVariable("relation");
	$makesymmetric = GetVariable("makesymmetric");
	$ids = GetVariable("ids");
	$modality = GetVariable("modality");
	$returnpage = GetVariable("returnpage");
	$templateid = GetVariable("templateid");

	/* fix the 'active' search */
	if (($searchactive == '') && ($action != '')) {
		$searchactive = 0;
	}
	else {
		$searchactive = 1;
	}
	
	/* determine action */
	switch ($action) {
		case 'editform':
			DisplaySubjectForm("edit", $id);
			break;
		case 'addrelation':
			AddRelation($id, $uid2, $relation, $makesymmetric);
			DisplaySubject($id);
			break;
		case 'changeproject':
			ChangeProject($id, $enrollmentid, $newprojectid);
			DisplaySubject($id);
			break;
		case 'addform':
			DisplaySubjectForm("add", "");
			break;
		case 'display':
			DisplaySubject($id);
			break;
		case 'print':
			PrintEnrollment($id, $enrollmentid);
			break;
		case 'newstudy':
			CreateNewStudy($modality, $enrollmentid, $id);
			break;
		case 'newstudyfromtemplate':
			CreateNewStudyFromTemplate($modality, $enrollmentid, $id, $templateid);
			break;
		case 'deleteconfirm':
			DeleteConfirm($id);
			break;
		case 'delete':
			Delete($id);
			DisplaySubject($id);
			break;
		case 'undelete':
			UnDelete($id);
			DisplaySubject($id);
			break;
		case 'obliterate':
			Obliterate($ids);
			DisplaySubjectList($searchuid, $searchaltuid, $searchname, $searchgender, $searchdob, $searchactive);
			break;
		case 'enroll':
			EnrollSubject($id, $projectid);
			DisplaySubject($id);
			break;
		case 'confirmupdate':
			Confirm("update", $id, $encrypt, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid);
			break;
		case 'confirmadd':
			Confirm("add", "", $encrypt, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, "", $altuids, $enrollmentids, $guid);
			break;
		case 'update':
			UpdateSubject($id, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid);
			DisplaySubject($id);
			break;
		case 'add':
			$id = AddSubject($lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $altuid, $guid);
			DisplaySubject($id);
			break;
		default:
			if ($id == "") {
				DisplaySubjectList($searchuid, $searchaltuid, $searchname, $searchgender, $searchdob, $searchactive);
			}
			else {
				DisplaySubject($id);
			}
	}
	

	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateSubject ---------------------- */
	/* -------------------------------------------- */
	function UpdateSubject($id, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid) {
		/* perform data checks */
		$name = mysqli_real_escape_string($GLOBALS['linki'], "$lastname^$firstname");
		$dob = mysqli_real_escape_string($GLOBALS['linki'], $dob);
		$gender = mysqli_real_escape_string($GLOBALS['linki'], $gender);
		$ethnicity1 = mysqli_real_escape_string($GLOBALS['linki'], $ethnicity1);
		$ethnicity2 = mysqli_real_escape_string($GLOBALS['linki'], $ethnicity2);
		$handedness = mysqli_real_escape_string($GLOBALS['linki'], $handedness);
		$education = mysqli_real_escape_string($GLOBALS['linki'], $education);
		$phone = mysqli_real_escape_string($GLOBALS['linki'], $phone);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$maritalstatus = mysqli_real_escape_string($GLOBALS['linki'], $maritalstatus);
		$smokingstatus = mysqli_real_escape_string($GLOBALS['linki'], $smokingstatus);
		$cancontact = mysqli_real_escape_string($GLOBALS['linki'], $cancontact) + 0;
		$tags = mysqli_real_escape_string($GLOBALS['linki'], $tags);
		$altuidlist = $altuids;
		$guid = mysqli_real_escape_string($GLOBALS['linki'], $guid);
		
		$tags = explode(',',$tags);
		
		/* update the subject */
		$sqlstring = "update subjects set name = '$name', birthdate = '$dob', gender = '$gender', ethnicity1 = '$ethnicity1', ethnicity2 = '$ethnicity2', handedness = '$handedness', education = '$education', phone1 = '$phone', email = '$email', marital_status = '$maritalstatus', smoking_status = '$smokingstatus', guid = '$guid', cancontact = $cancontact where subject_id = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* update the tags */
		SetTags('subject','',$id,$tags);
		
		/* delete entries for this subject from the altuid table ... */
		$sqlstring = "delete from subject_altuid where subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		/* ... and insert the new rows into the altuids table */
		$i=0;
		foreach ($altuidlist as $altuidsublist) {
			$altuidsublist = mysqli_real_escape_string($GLOBALS['linki'], $altuidsublist);
			//echo($altuidsublist);
			$altuids = explode(',',$altuidsublist);
			foreach ($altuids as $altuid) {
				$altuid = trim($altuid);
				if ($altuid != "") {
					$enrollmentid = $enrollmentids[$i];
					if ($enrollmentid == "") { $enrollmentid = 0; }
					//echo "enrollmentID [$enrollmentid] - altuid [$altuid]<br>";
					if (strpos($altuid, '*') !== FALSE) {
						$altuid = str_replace('*','',$altuid);
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($id, '$altuid',1, '$enrollmentid')";
					}
					else {
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($id, '$altuid',0, '$enrollmentid')";
					}
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
			$i++;
		}
		
		?><div align="center"><span class="staticmessage"><?=$uid?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddSubject ------------------------- */
	/* -------------------------------------------- */
	function AddSubject($lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $cancontact, $tags, $altuid, $guid) {
	
		if ($GLOBALS['debug']) {
			print "$fullname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $cancontact, $altuid, $guid";
		}
		/* perform data checks */
		$name = mysqli_real_escape_string($GLOBALS['linki'], "$lastname^$firstname");
		$dob = mysqli_real_escape_string($GLOBALS['linki'], $dob);
		$gender = mysqli_real_escape_string($GLOBALS['linki'], $gender);
		$ethnicity1 = mysqli_real_escape_string($GLOBALS['linki'], $ethnicity1);
		$ethnicity2 = mysqli_real_escape_string($GLOBALS['linki'], $ethnicity2);
		$handedness = mysqli_real_escape_string($GLOBALS['linki'], $handedness);
		$education = mysqli_real_escape_string($GLOBALS['linki'], $education);
		$phone = mysqli_real_escape_string($GLOBALS['linki'], $phone);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$maritalstatus = mysqli_real_escape_string($GLOBALS['linki'], $maritalstatus);
		$smokingstatus = mysqli_real_escape_string($GLOBALS['linki'], $smokingstatus);
		$cancontact = mysqli_real_escape_string($GLOBALS['linki'], $cancontact) + 0;
		$tags = mysqli_real_escape_string($GLOBALS['linki'], $tags);
		$altuid = mysqli_real_escape_string($GLOBALS['linki'], $altuid);
		$guid = mysqli_real_escape_string($GLOBALS['linki'], $guid);
		$altuids = explode(',',$altuid);

		# create a new uid
		do {
			$uid = NIDB\CreateUID('S',3);
			$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$uid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$count = mysqli_num_rows($result);
		} while ($count > 0);
		
		# create a new family uid
		do {
			$familyuid = NIDB\CreateUID('F');
			$sqlstring = "SELECT * FROM `families` WHERE family_uid = '$familyuid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$count = mysqli_num_rows($result);
		} while ($count > 0);
		
		/* insert the new subject */
		$sqlstring = "insert into subjects (name, birthdate, gender, ethnicity1, ethnicity2, handedness, education, phone1, email, marital_status, smoking_status, uid, uuid, guid, cancontact) values ('$name', '$dob', '$gender', '$ethnicity1', '$ethnicity2', '$handedness', '$education', '$phone', '$email', '$maritalstatus', '$smokingstatus', '$uid', uuid(), '$guid', $cancontact)";
		if ($GLOBALS['debug']) { PrintSQL($sqlstring); }
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$SubjectRowID = mysqli_insert_id($GLOBALS['linki']);
		
		# create familyRowID if it doesn't exist
		$sqlstring2 = "insert into families (family_uid, family_createdate, family_name) values ('$familyuid', now(), 'Proband-$uid')";
		if ($GLOBALS['debug']) { PrintSQL($sqlstring2); }
		$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
		$familyRowID = mysqli_insert_id($GLOBALS['linki']);
	
		$sqlstring3 = "insert into family_members (family_id, subject_id, fm_createdate) values ($familyRowID, $SubjectRowID, now())";
		if ($GLOBALS['debug']) { PrintSQL($sqlstring3); }
		$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
		
		SetTags('subject','',$SubjectRowID,$tags);
		
		foreach ($altuids as $altuid) {
			$altuid = trim($altuid);
			$sqlstring = "insert ignore into subject_altuid (subject_id, altuid) values ($SubjectRowID, '$altuid')";
			if ($GLOBALS['debug']) { PrintSQL($sqlstring); }
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}

		
		?><div align="center"><span style="background-color: darkred; color: white"><?=$subjectname?> added <?=$uid?></span></div><br><br><?
		
		return $SubjectRowID;
	}

	
	/* -------------------------------------------- */
	/* ------- AddRelation ------------------------ */
	/* -------------------------------------------- */
	function AddRelation($id, $uid2, $relation, $makesymmetric) {
		/* get the row id from the UID for subject 2 */
		$sqlstring = "select subject_id from subjects where uid = '$uid2'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$id2 = $row['subject_id'];
		
		if ($id == $id2) {
			?><div align="center"><span class="message">Subject cannot be related to him/herself</span></div><br><br><?
		}
		elseif ($id2 == "") {
			?><div align="center"><span class="message">Subject <?=$uid2?> could not be found</span></div><br><br><?
		}
		else {
			/* insert the primary relation */
			$sqlstring = "insert into subject_relation (subjectid1, subjectid2, relation) values ($id, $id2, '$relation')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			
			if ($makesymmetric) {
				/* determine the corresponding relation */
				switch ($relation) {
					case "siblingf": $symrelation = "siblingf"; break;
					case "siblingm": $symrelation = "siblingm"; break;
					case "sibling": $symrelation = "sibling"; break;
					case "parent": $symrelation = "child"; break;
					case "child": $symrelation = "parent"; break;
				}
				/* insert the corresponding relation */
				$sqlstring = "insert into subject_relation (subjectid1, subjectid2, relation) values ($id2, $id, '$symrelation')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			?><div align="center"><span class="message">Relation added</span></div><br><br><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CreateNewStudy --------------------- */
	/* -------------------------------------------- */
	function CreateNewStudy($modality, $enrollmentid, $id) {

		/* insert a new row into the studies table. parsedicom or the user will populate the info later */
		/* get the newest study # first */
		$sqlstring = "SELECT max(a.study_num) 'max' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$oldstudynum = $row['max'];
		$study_num = $oldstudynum + 1;

		$sqlstring = "SELECT project_id FROM enrollment WHERE enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$project_id = $row['project_id'];
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_operator, study_performingphysician, study_site, study_status) values ($enrollmentid, $study_num, '$modality', now(), '', '', '', 'pending')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$studyRowID = mysqli_insert_id($GLOBALS['linki']);
		
		$sqlstring = "select (select uid from subjects where subject_id = '$id') 'uid', (select project_name from projects where project_id = $project_id) 'projectname', (select project_costcenter from projects where project_id = $project_id) 'projectcostcenter' ";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$projectname = $row['projectname'];
		$projectcostcenter = $row['projectcostcenter'];

		/* navigation bar */
		$perms = GetCurrentUserProjectPermissions($projectids);
		$urllist['Subjects'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$id";
		NavigationBar("$uid", $urllist,$perms);
		
		?>
		<div align="center">
		Study <?=$study_num?> has been created for subject <?=$uid?> in <?=$projectname?> (<?=$projectcostcenter?>)<br>
		<a href="studies.php?id=<?=$studyRowID?>">View Study</a>
		</div>
		<?
	}	

	
	/* -------------------------------------------- */
	/* ------- CreateNewStudyFromTemplate --------- */
	/* -------------------------------------------- */
	function CreateNewStudyFromTemplate($modality, $enrollmentid, $id, $templateid) {
		
		if (!isInteger($templateid)) {
			?><span class="staticmessage">Invalid templateID [<?=$templateid?>]</span><?
			return;
		}
		
		/* Get the protocol names and modality for this template */
		$itemprotocols = array();
		$sqlstring = "select * from study_templateitems a left join study_template b on a.studytemplate_id = b.studytemplate_id where a.studytemplate_id = $templateid order by a.item_order";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$itemprotocols[] = $row['item_protocol'];
			$templatemodality = strtolower($row['template_modality']);
		}
		
		$modality = $templatemodality;

		/* insert a new row into the studies table. parsedicom or the user will populate the info later */
		/* get the newest study # first */
		$sqlstring = "SELECT max(a.study_num) 'max' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$oldstudynum = $row['max'];
		$study_num = $oldstudynum + 1;

		$sqlstring = "SELECT project_id FROM enrollment WHERE enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$project_id = $row['project_id'];
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_status) values ($enrollmentid, $study_num, upper('$modality'), now(),'' , '', '', '', 'pending')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$studyRowID = mysqli_insert_id($GLOBALS['linki']);
		
		/* create the series */
		$i = 0;
		foreach ($itemprotocols as $protocol) {
			$i++;
			$sqlstring = "insert into $templatemodality" . "_series (study_id, series_num, series_datetime, series_protocol) values ($studyRowID, $i, now(), '$protocol')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* get information about the project to display */
		$sqlstring = "select (select uid from subjects where subject_id = '$id') 'uid', (select project_name from projects where project_id = $project_id) 'projectname', (select project_costcenter from projects where project_id = $project_id) 'projectcostcenter' ";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$projectname = $row['projectname'];
		$projectcostcenter = $row['projectcostcenter'];

		/* navigation bar */
		$perms = GetCurrentUserProjectPermissions($projectids);
		$urllist['Subjects'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$id";
		NavigationBar("$uid", $urllist,$perms);
		
		?>
		<div align="center">
		Study <?=$study_num?> has been created for subject <?=$uid?> in <?=$projectname?> (<?=$projectcostcenter?>)<br>
		<a href="studies.php?id=<?=$studyRowID?>">View Study</a>
		</div>
		<?
	}	
	
	
	/* -------------------------------------------- */
	/* ------- EnrollSubject ---------------------- */
	/* -------------------------------------------- */
	function EnrollSubject($subjectid, $projectid) {
		$sqlstring = "select * from enrollment where project_id = $projectid and subject_id = $subjectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) < 1) {
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectid, $subjectid, now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			?>
			<span class="message">Subject enrolled in the project</span>
			<?
		}
		else {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$enrollmentid = $row['enrollment_id'];
			
			$sqlstring = "update enrollment set enroll_enddate = '0000-00-00 00:00:00' where enrollment_id = '$enrollmentid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			?>
			<span class="message">Subject re-enrolled in the project</span>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- ChangeProject ---------------------- */
	/* -------------------------------------------- */
	/* this function moves studies from enrollment
		in one project to enrollment in another
		all within a subject, not across subjects
	   -------------------------------------------- */
	function ChangeProject($subjectid, $enrollmentid, $newprojectid) {
	
		?>
		<ol>
		<?
		$sqlstring = "select * from enrollment where project_id = $newprojectid and subject_id = $subjectid";
		echo "<li>Checking if enrollment in new project already exists [$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		if (mysqli_num_rows($result) < 1) {
			/* un-enroll from previous project */
			$sqlstring = "update enrollment set enroll_enddate = now() where enrollment_id = $enrollmentid";
			echo "<li>Ending enrollment in current project [$sqlstring]";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			/* enroll in new project */
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($newprojectid, $subjectid, now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			echo "<li>Creating enrollment in new project [$sqlstring]";
			$new_spid = mysqli_insert_id($GLOBALS['linki']);
			
			/* change all old enrollmentids to the new id in the studies and assessments tables */
			$sqlstring = "update studies set enrollment_id = $new_spid where enrollment_id = $enrollmentid";
			echo "<li>Update existing study enrollments to new enrollment [$sqlstring]";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			$sqlstring = "update assessments set enrollment_id = $new_spid where enrollment_id = $enrollmentid";
			echo "<li>Update existing assessments to new enrollment [$sqlstring]";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			?>
			<span class="message">Subject moved to new project</span>
			<?
		}
		else {
			$new_spid = $row['enrollment_id'];
			/* change all old enrollmentids to the new id in the studies and assessments tables */
			$sqlstring = "update studies set enrollment_id = $new_spid where enrollment_id = $enrollmentid";
			echo "<li>Update existing studies to new enrollment [$sqlstring]";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			$sqlstring = "update assessments set enrollment_id = $new_spid where enrollment_id = $enrollmentid";
			echo "<li>Update existing assessments to new enrollment [$sqlstring]";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			?>
			<span class="message">Subject already enrolled in this project. Studies moved to new project</span>
			<?
		}
		?></ol><?
	}
	
	
	/* -------------------------------------------- */
	/* ------- Delete ----------------------------- */
	/* -------------------------------------------- */
	function Delete($id) {
		/* get all existing info about this subject */
		$sqlstring = "update subjects set isactive = 0 where subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		?>
		<div align="center" class="message">Subject deleted</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- UnDelete --------------------------- */
	/* -------------------------------------------- */
	function UnDelete($id) {
		if (!ValidID($id,'Subject ID')) { return; }
		
		/* get all existing info about this subject */
		$sqlstring = "update subjects set isactive = 1 where subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		?>
		<div align="center" class="message">Subject undeleted</div>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- Obliterate ------------------------- */
	/* -------------------------------------------- */
	function Obliterate($ids) {
		/* delete all information about this subject from the database */
		foreach ($ids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'subject', $id,'" . $_SESSION['username'] . "', now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Subject(s) queued for obliteration</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DeleteConfirm ---------------------- */
	/* -------------------------------------------- */
	function DeleteConfirm($id) {
		if (!ValidID($id,'Subject ID')) { return; }
		
		/* get all existing info about this subject */
		$sqlstring = "select * from subjects where subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['name'];
		$dob = $row['birthdate'];
		$gender = $row['gender'];
		$ethnicity1 = $row['ethnicity1'];
		$ethnicity2 = $row['ethnicity2'];
		$handedness = $row['handedness'];
		$education = $row['education'];
		$phone1 = $row['phone1'];
		$email = $row['email'];
		$maritalstatus = $row['marital_status'];
		$smokingstatus = $row['smoking_status'];
		$uid = $row['uid'];
		$guid = $row['guid'];
		$cancontact = $row['cancontact'];

		$tags = GetTags('subject', 'dx', $id);
		$altuids = GetAlternateUIDs($id,0);
		
		list($lastname, $firstname) = explode("^",$name);
		list($lname, $fname) = explode("^",$name);
		$name = strtoupper(substr($fname,0,1)) . strtoupper(substr($lname,0,1));
		
		?>
		<div align="center" class="message">
		<b>Are you absolutely sure you want to delete this subject?</b><img src="images/chili24.png">
		<br><br>
		<span class="uid"><?=$uid?></span>
		</div>
		<br><br>
		
		<table width="100%">
			<tr>
				<td valign="top" align="center">
					<table class="reviewtable">
						<tr>
							<td class="label">Subject initials</td>
							<td class="value"><?=$name?></td>
						</tr>
						<tr>
							<td class="label">Alternate UID 1</td>
							<td class="value"><?=implode2(', ',$altuids)?></td>
						</tr>
						<tr>
							<td class="label">Date of birth</td>
							<td class="value"><?=$dob?></td>
						</tr>
						<tr>
							<td class="label">Gender</td>
							<td class="value"><?=$gender?></td>
						</tr>
						<tr>
							<td class="label">Ethnicity1&2</td>
							<td class="value"><?=$ethnicity1?>, <?=$ethnicity2?></td>
						</tr>
						<tr>
							<td class="label">Handedness</td>
							<td class="value"><?=$handedness?></td>
						</tr>
						<tr>
							<td class="label">Education</td>
							<td class="value"><?=$education?></td>
						</tr>
						<tr>
							<td class="label">Phone</td>
							<td class="value"><?=$phone?></td>
						</tr>
						<tr>
							<td class="label">E-mail</td>
							<td class="value"><?=$email?></td>
						</tr>
						<tr>
							<td class="label">Marital Status</td>
							<td class="value"><?=$maritalstatus?></td>
						</tr>
						<tr>
							<td class="label">Smoking Status</td>
							<td class="value"><?=$smokingstatus?></td>
						</tr>
						<tr>
							<td class="label">GUID</td>
							<td class="value"><?=$guid?></td>
						</tr>
						<tr>
							<td class="label">Can contact?</td>
							<td class="value"><?=$cancontact?></td>
						</tr>
						<tr>
							<td class="label">Tags</td>
							<td class="value"><?=implode2(', ',$tags)?></td>
						</tr>
					</table>
				</td>
				<td valign="top" align="center">
					<table class="download">
						<tr>
							<td class="title">
								Projects
							</td>
						</tr>
						
						<?
							$sqlstring = "select a.*, b.*, date(enroll_startdate) 'enroll_startdate', date(enroll_enddate) 'enroll_enddate' from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = $id";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$enrollmentid = $row['enrollment_id'];
								$enroll_startdate = $row['enroll_startdate'];
								$enroll_enddate = $row['enroll_enddate'];
								$project_name = $row['project_name'];
								$costcenter = $row['project_costcenter'];
								$project_enddate = $row['project_enddate'];
								
								if ($row['irb_consent'] != "") { $irb = "Y"; }
								else { $irb = "N"; }
						?>
						<tr>
							<td class="section">
								<table class="subdownload" width="100%">
									<tr>
										<td class="label" style="width: 250px; text-align: left">
											<?=$project_name?> (<?=$costcenter?>)<br><br>
											<div style="font-size:10pt; font-weight: normal;">
											Enroll date: <?=$enroll_startdate?><br>
											Un-enroll date: <?=$enroll_enddate?><br>
											Project end date: <?=$project_enddate;?>
											</div>
										</td>
										<td class="main">
											<table width="100%">
												<tr>
													<td><b>Studies</b>
													</td>
												</tr>
											</table>
											<table width="100%" class="smalldisplaytable" style="background-color: #FFFFFF; border-radius: 5px; width: 100%; padding:5px">
												<thead>
													<th>#</th>
													<th>Modality</th>
													<th>Date</th>
													<th>Physician</th>
													<th>Operator</th>
													<th>Site</th>
													<th>Status</th>
													<th>Study ID</th>
												</thead>
												<tbody>
												<?
												$sqlstring = "select * from studies where enrollment_id = $enrollmentid";
												//$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
												$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
												if (mysqli_num_rows($result2) > 0) {
													while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
														$study_id = $row2['study_id'];
														$study_num = $row2['study_num'];
														$study_modality = $row2['study_modality'];
														$study_datetime = $row2['study_datetime'];
														$study_operator = $row2['study_operator'];
														$study_performingphysician = $row2['study_performingphysician'];
														$study_site = $row2['study_site'];
														$study_status = $row2['study_status'];
														
														?>
														<tr onMouseOver="this.style.backgroundColor='#9EBDFF'" onMouseOut="this.style.backgroundColor=''">
															<td><?=$study_num?></td>
															<td><?=$study_modality?></td>
															<td><?=$study_datetime?></td>
															<td><?=$study_performingphysician?></td>
															<td><?=$study_operator?></td>
															<td><?=$study_site?></td>
															<td><?=$study_status?></td>
															<td><tt><?=$uid?><?=$study_num?></tt></td>
														</tr>
														<?
													}
												}
												else {
													?>
													<tr>
														<td align="center">
															None
														</td>
													</tr>
													<?
												}
												?>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<?
							}
						?>
					</table>
				</td>
			</tr>
		</table>
		<br><br>
		<table width="100%">
			<tr>
				<td align="center" width="50%"><FORM><INPUT TYPE="BUTTON" VALUE="Back" ONCLICK="history.go(-1)"></FORM></td>
				<form method="post" action="subjects.php">
				<input type="hidden" name="action" value="delete">
				<input type="hidden" name="id" value="<?=$id?>">
				<td align="center"><input type="submit" value="Yes, delete it"</td>
				</form>
			</tr>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- Confirm ---------------------------- */
	/* -------------------------------------------- */
	function Confirm($type, $id, $encrypt, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid) {
		
		$encdob = $dob;
		if (($encrypt) && ($type != 'update')) {
			$fullname = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $lastname) . '^' . preg_replace('/[^A-Za-z0-9]/', '', $firstname));
			$encname = strtoupper(sha1($fullname));
			$altuids = preg_replace('/[^A-Za-z0-9\_\-]/', '', split(',',$altuid));
			foreach ($altuids as $alt) {
				$encids[] = strtoupper(sha1($alt));
				$encuids[$alt] = strtoupper(sha1($alt));
			}
			$encdob = substr($dob,0,4) . '-00-00';
		}
		else {
			$encids = explode(',',$altuid);
		}

		if ($type == "update") { ?>
		<?=$uid?><br><br>
		<? } ?>
		
		
		<table class="reviewtable">
			<? if (($encrypt) && ($type != 'update')) { ?>
			<tr>
				<td colspan="2" style="color:#444; border: orange solid 1px">This subject's information will be encrypted. <b>You will only be able to search for this subject using the bolded values below.</b> Print this page or record the UID on the following page<br><br>
					[NAME] <?=$firstname?> <?=$lastname?> &rarr; <b><?=$fullname?></b> &rarr; <b><?=$encname;?></b><br>
					[DOB] <?=$dob?> &rarr; <b><?=$encdob?></b><br>
					<?
					$i=1;
					foreach ($encuids as $id => $encid) {
						echo "[ALT UID $i] <b>$id</b> &rarr; <b>$encid</b><Br>";
						$i++;
					}
					?>
					<br>
				</td>
			</tr>
			<? } ?>
			<tr>
				<td class="label">First name</td>
				<td class="value"><?=$firstname?></td>
			</tr>
			<tr>
				<td class="label">Last name</td>
				<td class="value"><?=$lastname?></td>
			</tr>
			<tr>
				<td class="label">Date of birth</td>
				<td class="value"><?=$dob?></td>
			</tr>
			<tr>
				<td class="label">Gender</td>
				<td class="value"><?=$gender?></td>
			</tr>
			<tr>
				<td class="label">IDs</td>
				<td class="value"><?PrintVariable($altuids)?></td>
			</tr>
			<tr>
				<td class="label">Enrollment IDs</td>
				<td class="value"><?PrintVariable($enrollmentids)?></td>
			</tr>
			<tr>
				<td class="label">Ethnicity1&2</td>
				<td class="value"><?=$ethnicity1?>, <?=$ethnicity2?></td>
			</tr>
			<tr>
				<td class="label">Handedness</td>
				<td class="value"><?=$handedness?></td>
			</tr>
			<tr>
				<td class="label">Education</td>
				<td class="value"><?=$education?></td>
			</tr>
			<tr>
				<td class="label">Phone</td>
				<td class="value"><?=$phone?></td>
			</tr>
			<tr>
				<td class="label">E-mail</td>
				<td class="value"><?=$email?></td>
			</tr>
			<tr>
				<td class="label">Marital Status</td>
				<td class="value"><?=$maritalstatus?></td>
			</tr>
			<tr>
				<td class="label">Smoking Status</td>
				<td class="value"><?=$smokingstatus?></td>
			</tr>
			<tr>
				<td class="label">GUID</td>
				<td class="value"><?=$guid?></td>
			</tr>
			<tr>
				<td class="label">Can contact?</td>
				<td class="value"><?=$cancontact?></td>
			</tr>
			<tr>
				<td class="label">Tags</td>
				<td class="value"><?=$tags?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<br>
					<span class="staticmessage">Are you sure this subject's information is correct and not a duplicate?</span>
					<br><br>
				</td>
			</tr>
			<tr>
				<td align="center" valign="middle"><FORM><INPUT TYPE="BUTTON" VALUE="Back" ONCLICK="history.go(-1)"></FORM></td>
				
				<form method="post" action="subjects.php">
				<input type="hidden" name="action" value="<?=$type?>">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="encrypt" value="<?=$encrypt?>">
				<input type="hidden" name="lastname" value="<?=$lastname?>">
				<input type="hidden" name="firstname" value="<?=$firstname?>">
				<input type="hidden" name="fullname" value="<?=$encname?>">
				<input type="hidden" name="dob" value="<?=$encdob?>">
				<input type="hidden" name="gender" value="<?=$gender?>">
				<input type="hidden" name="ethnicity1" value="<?=$ethnicity1?>">
				<input type="hidden" name="ethnicity2" value="<?=$ethnicity2?>">
				<input type="hidden" name="handedness" value="<?=$handedness?>">
				<input type="hidden" name="education" value="<?=$education?>">
				<input type="hidden" name="phone" value="<?=$phone?>">
				<input type="hidden" name="email" value="<?=$email?>">
				<input type="hidden" name="maritalstatus" value="<?=$maritalstatus?>">
				<input type="hidden" name="smokingstatus" value="<?=$smokingstatus?>">
				<input type="hidden" name="cancontact" value="<?=$cancontact?>">
				<input type="hidden" name="tags" value="<?=$tags?>">
				<input type="hidden" name="uid" value="<?=$uid?>">
				<? foreach ($altuids as $altuid) { ?>
				<input type="hidden" name="altuids[]" value="<?=$altuid?>">
				<? } ?>
				<? foreach ($enrollmentids as $enrollmentid) { ?>
				<input type="hidden" name="enrollmentids[]" value="<?=$enrollmentid?>">
				<? } ?>
				<input type="hidden" name="guid" value="<?=$guid?>">
				<input type="hidden" name="returnpage" value="subject">
				<td align="center" valign="middle"><input type="submit" value="Yes, <?=$type?> it"</td>
				</form>
			</tr>
		</table>
		<?
	}	


	/* -------------------------------------------- */
	/* ------- DisplaySubject --------------------- */
	/* -------------------------------------------- */
	function DisplaySubject($id) {
		if (!ValidID($id,'Subject ID')) { return; }

		$userid = $_SESSION['userid'];
		
		/* get list of projects associated with this subject */
		$projectids = array();
		$sqlstring = "select b.project_id from subjects a left join enrollment b on a.subject_id = b.subject_id where a.subject_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$projectids[] = $row['project_id'];
			}
		}
		
		$perms = GetCurrentUserProjectPermissions($projectids);
		$urllist['Subjects'] = "subjects.php";
		NavigationBar("$uid", $urllist, $perms);

		/* update the mostrecent table */
		UpdateMostRecent($userid, $id,'');

		/* check if they have enrollments for a valid project */
		$sqlstring = "select a.* from enrollment a right join projects b on a.project_id = b.project_id where a.subject_id = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$hasenrollments = 1;
		}
		else {
			$hasenrollments = 0;
		}
		
		/* get all existing info about this subject */
		$sqlstring = "select * from subjects where subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['name'];
		$dob = $row['birthdate'];
		$gender = $row['gender'];
		$ethnicity1 = $row['ethnicity1'];
		$ethnicity2 = $row['ethnicity2'];
		$handedness = $row['handedness'];
		$education = $row['education'];
		$phone1 = $row['phone1'];
		$email = $row['email'];
		$maritalstatus = $row['marital_status'];
		$smokingstatus = $row['smoking_status'];
		$uid = $row['uid'];
		$guid = $row['guid'];
		$cancontact = $row['cancontact'];
		$isactive = $row['isactive'];

		$tags = GetTags('subject', '', $id);
		
		/* get the family UID */
		$sqlstring = "select b.family_uid, b.family_name from family_members a left join families b on a.family_id = b.family_id where a.subject_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$familyuid = $row['family_uid'];
		$familyname = $row['family_name'];
		
		/* get list of alternate subject UIDs */
		$altuids = GetAlternateUIDs($id,0);

		list($lastname, $firstname) = explode("^",$name);
		list($lname, $fname) = explode("^",$name);
		$name = strtoupper(substr($fname,0,1)) . strtoupper(substr($lname,0,1));

		switch ($gender) {
			case "U": $gender = "Unknown"; break;
			case "F": $gender = "Female"; break;
			case "M": $gender = "Male"; break;
			case "O": $gender = "Other"; break;
		}
					
		switch ($ethnicity1) {
			case "": $ethnicity1 = "Unknown"; break;
			case "hispanic": $ethnicity1 = "Hispanic/Latino"; break;
			case "nothispanic": $ethnicity1 = "Not hispanic/Latino"; break;
		}

		switch ($ethnicity2) {
			case "": $ethnicity2 = "Unknown"; break;
			case "indian": $ethnicity2 = "American Indian/Alaska Native"; break;
			case "asian": $ethnicity2 = "Asian"; break;
			case "black": $ethnicity2 = "Black/African American"; break;
			case "islander": $ethnicity2 = "Hawaiian/Pacific Islander"; break;
			case "white": $ethnicity2 = "White"; break;
		}
		
		switch ($handedness) {
			case "U": $handedness = "Unknown"; break;
			case "R": $handedness = "Right"; break;
			case "L": $handedness = "Left"; break;
			case "A": $handedness = "Ambidextrous"; break;
		}
		
		switch ($education) {
			case 0: $education = "Unknown"; break;
			case 1: $education = "Grade School"; break;
			case 2: $education = "Middle School"; break;
			case 3: $education = "High School/GED"; break;
			case 4: $education = "Trade School"; break;
			case 5: $education = "Associates Degree"; break;
			case 6: $education = "Bachelors Degree"; break;
			case 7: $education = "Masters Degree"; break;
			case 8: $education = "Doctoral Degree"; break;
		}

		/* display a message if this subject has been deleted */
		if (!$isactive) {
			?><div class="staticmessage">This subject is marked as inactive (DELETED)</div><?
		}
		
		?>

		<div align="center">
			<span align="center" style="padding: 8pt; font-size: 18pt; font-weight: bold; background-color: #ffff87" class="tt"><?=$uid?></span>
		</div>
		
		<br>
		
		<style>
			details[open] { border: 1px solid #ccc; background-color: #eee}
		</style>
		
		<table width="100%">
			<tr>
				<td valign="top">
					<table cellpadding="0" width="100%">
						<tr>
							<td style="border: 2px solid #444">
								<div style="background-color: #444; color: white; font-weight: bold; padding: 8px">Demographics</div>
								<div align="left" style="padding: 5px">
								
								<? if (GetPerm($perms, 'viewphi', $projectid)) { ?>
								
								<br>
								<table class="reviewtable">
									<tr>
										<td class="label">Subject initials</td>
										<td class="value"><?=$name?></td>
									</tr>
									<tr>
										<td class="label">Date of birth</td>
										<td class="value"><?=$dob?></td>
									</tr>
									<tr>
										<td class="label">Gender</td>
										<td class="value"><?=$gender?></td>
									</tr>
									<tr>
										<td class="label" style="white-space:nowrap;">Alternate UIDs</td>
										<td class="value tt">
										<?
											foreach ($altuids as $altid) {
												if (strlen($altid) > 20) {
													echo "<span title='$altid'>" . substr($altid,0,20) . "...</span> ";
												}
												else {
													echo "$altid ";
												}
											}
										?>
										</td>
									</tr>
									<tr>
										<td class="label">Ethnicity1&2</td>
										<td class="value"><?=$ethnicity1?>, <?=$ethnicity2?></td>
									</tr>
									<tr>
										<td class="label">Handedness</td>
										<td class="value"><?=$handedness?></td>
									</tr>
									<tr>
										<td class="label">Education</td>
										<td class="value"><?=$education?></td>
									</tr>
									<tr>
										<td class="label">Phone</td>
										<td class="value"><?=$phone1?></td>
									</tr>
									<tr>
										<td class="label">E-mail</td>
										<td class="value"><?=$email?></td>
									</tr>
									<tr>
										<td class="label">Marital status</td>
										<td class="value"><?=$maritalstatus?></td>
									</tr>
									<tr>
										<td class="label">Smoking&nbsp;status</td>
										<td class="value"><?=$smokingstatus?></td>
									</tr>
									<tr>
										<td class="label">GUID</td>
										<td class="value"><?=$guid?></td>
									</tr>
									<tr>
										<td class="label">Can contact?</td>
										<td class="value"><?=$cancontact?></td>
									</tr>
									<tr>
										<td class="label">Subject tags</td>
										<td class="value"><?=DisplayTags($tags, '', 'subject')?></td>
									</tr>
								</table>
								<br>
								
								<br><br>
								<div align="left" style="font-weight: bold; font-size: 12pt">Family</div>
								<div align="left">
								<? if (GetPerm($perms, 'modifyphi', $projectid)) { ?>
								<details>
								<summary class="tiny" style="color:darkred">Add family members</summary>
									<table style="font-size: 10pt">
									<?
										/* display existing subject relations */
										$sqlstring = "select a.*, b.uid from subject_relation a left join subjects b on a.subjectid2 = b.subject_id where a.subjectid1 = $id";
										$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
										while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
											$subjectid2 = $row['subjectid2'];
											$relation = $row['relation'];
											$uid2 = $row['uid'];
											
											switch ($relation) {
												case "siblingf": $relation = "half-sibling (same father)"; break;
												case "siblingm": $relation = "half-sibling (same mother)"; break;
												case "sibling": $relation = "sibling"; break;
												case "parent": $relation = "parent"; break;
												case "child": $relation = "child"; break;
											}
											
											?>
											<tr>
												<td><?=$uid?> is the <b><?=$relation?></b> of <a href="subjects.php?id=<?=$subjectid2?>"><?=$uid2?></a></td>
											</tr>
											<?
										}
									?>
										<tr>
											<form action="subjects.php" method="post">
											<input type="hidden" name="id" value="<?=$id?>">
											<input type="hidden" name="action" value="addrelation">
											<td>
												<br><br>
												<?=$uid?> is the
												<br>
												<select name="relation">
												<option value="siblingm">Half-sibling (same mother)</option>
												<option value="siblingf">Half-sibling (same father)</option>
												<option value="sibling">Sibling</option>
												<option value="parent">Parent</option>
												<option value="child">Child</option>
											</select> of <input type="text" size="10" name="uid2" id="uid2"/>
											<br>
											<input type="checkbox" name="makesymmetric" value="1" checked title="If subject 1 is the parent of subject 2, a corresponding relation will also show subject 2 is a child of subject 1">Make symmetric 
											<input type="submit" value="Add relation"></td>
											</form>
										</tr>
									</table>
								</details>
								<? } ?>
								</div>
								<br>
								<table class="reviewtable">
									<tr>
										<td class="label">Family UID</td>
										<td class="value"><?=$familyuid?></td>
									</tr>
									<tr>
										<td class="label">Family name</td>
										<td class="value"><?=$familyname?></td>
									</tr>
								</table>
								<br>
								<? if (GetPerm($perms, 'modifyphi', $projectid)) { ?>
									<details>
										<summary class="tiny" style="color:darkred">Admin functions</summary>
										<div style="padding:5px; font-size:11pt">
										<a href="subjects.php?action=editform&id=<?=$id?>" class="linkbutton" style="width: 70px; text-align: center">Edit</a> demographics<br>
										<a href="merge.php?action=mergesubjectform&subjectuid=<?=$uid?>" class="linkbutton" style="width: 70px; text-align: center">Merge</a> with existing subject
										<br><br><br>
										<?
											if ($GLOBALS['isadmin']) {
												if ($isactive) {
												?>
													<a href="subjects.php?action=deleteconfirm&id=<?=$id?>" class="redlinkbutton" style="width: 70px; text-align: center">Delete</a>
												<? } else { ?>
													<a href="subjects.php?action=undelete&id=<?=$id?>" class="redlinkbutton" style="width: 70px; text-align: center">Undelete</a>
												<?
												}
											}
										?>
										</div>
									</details>
								<? }
								}
								else {
									echo "No permissions to view PHI";
								}
								?>
								</div>
							</td>
						</tr>
					</table>
				</td>
				<td valign="top" align="center">
					<table width="100%">
						<tr>
							<form action="subjects.php" method="post">
							<td style="padding: 10px; background-color: #444; color: #fff">
								<input type="hidden" name="id" value="<?=$id?>">
								<input type="hidden" name="action" value="enroll">
								<b>Enroll in project</b> &nbsp; 
								<select name="projectid">
								<?
									$sqlstring = "select a.*, b.user_fullname from projects a left join users b on a.project_pi = b.user_id where a.project_status = 'active' and a.instance_id = " . $_SESSION['instanceid'] . " order by a.project_name";
									$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$projectid = $row['project_id'];
										$project_name = $row['project_name'];
										$project_costcenter = $row['project_costcenter'];
										$project_enddate = $row['project_enddate'];
										$user_fullname = $row['user_fullname'];

										$perms = GetCurrentUserProjectPermissions(array($projectid));
										if (GetPerm($perms, 'modifyphi', $projectid)) { $disabled = ""; } else { $disabled="disabled"; }
										?>
										<option value="<?=$projectid?>" <?=$disabled?>><?=$project_name?> (<?=$project_costcenter?>)</option>
										<?
									}
								?>
								</select>
								<input type="submit" value="Enroll">
							</td>
							</form>
						</tr>
						<tr>
							<td>&nbsp;</td>
						</tr>
						
						<?
							$sqlstringA = "select a.project_id 'projectid', a.*, b.*, enroll_startdate, enroll_enddate from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = $id";
							$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
							while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
								$enrollmentid = $rowA['enrollment_id'];
								$enroll_startdate = $rowA['enroll_startdate'];
								$enroll_enddate = $rowA['enroll_enddate'];
								$enrollgroup = $rowA['enroll_subgroup'];
								$projectid = $rowA['projectid'];
								$project_name = $rowA['project_name'];
								$costcenter = $rowA['project_costcenter'];
								$project_enddate = $rowA['project_enddate'];
								
								$perms = GetCurrentUserProjectPermissions(array($projectid));
								if (GetPerm($perms, 'projectadmin', $projectid)) { $projectadmin = 1; } else { $projectadmin = 0; }
								if (GetPerm($perms, 'modifyphi', $projectid)) { $modifyphi = 1; } else { $modifyphi = 0; }
								if (GetPerm($perms, 'viewphi', $projectid)) { $viewphi = 1; } else { $viewphi = 0; }
								if (GetPerm($perms, 'modifydata', $projectid)) { $modifydata = 1; } else { $modifydata = 0; }
								if (GetPerm($perms, 'viewdata', $projectid)) { $viewdata = 1; } else { $viewdata = 0; }

								$enrolldate = date('M j, Y g:ia',strtotime($enroll_startdate));
								
								if ($row['irb_consent'] != "") { $irb = "Y"; }
								else { $irb = "N"; }
							
								if (($enroll_enddate > date("Y-m-d H:i:s")) || ($enroll_enddate == "0000-00-00 00:00:00") || ($enroll_enddate == "") || ($enroll_enddate == strtolower("null"))) {
									$enrolled = true;
								}
								else {
									$enrolled = false;
								}
								
								$subjectaltids = implode2(', ',GetAlternateUIDs($id, $enrollmentid));
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$(".edit_inline<? echo $enrollmentid; ?>").editInPlace({
									url: "group_inlineupdate.php",
									params: "action=editinplace&id=<? echo $enrollmentid; ?>",
									default_text: "<i style='color:#AAAAAA'>Edit group name...</i>",
									bg_over: "white",
									bg_out: "lightyellow",
								});
							});
						</script>
						<tr>
							<td style="background-color: #eee">
							
								<table width="100%" style="border: 2px solid #444; border-spacing: 0px; margin: 0px" cellpadding="0">
									<tr>
										<td style="width: 250px; text-align: left; vertical-align: top; background-color: #fff;">
											<div style="background-color: #444; padding: 8px"><a href="projects.php?id=<?=$projectid?>" style="font-size: 12pt; font-weight: bold; color: #fff"><?=$project_name?> (<?=$costcenter?>)</a></div>
											<br>
											<div style="padding: 10px;">
												<table class="reviewtable">
													<tr>
														<td class="label">ID(s)</td>
														<td class="value"><b style="background-color: #ffff87; padding: 5px"><?=$subjectaltids?></b></td>
													</tr>
													<tr>
														<td class="label">Group</td>
														<? if ($modifyphi) { ?>
														<td class="value"><span id="enroll_subgroup" title="Click to edit in place" class="edit_inline<? echo $enrollmentid; ?>" style="background-color: lightyellow; border: 1px solid skyblue; padding: 1px 3px; font-size: 9pt;"><? echo $enrollgroup; ?></span></td>
														<? } elseif ($viewphi) { ?>
														<td class="value"><span style="font-size: 9pt;"><? echo $enrollgroup; ?></span></td>
														<? } ?>
													</tr>
													<tr>
														<td class="label">Enroll date</td>
														<td class="value"><?=$enrolldate?></td>
													</tr>
													<tr>
														<td class="label">Tags</td>
														<td class="value"><?=DisplayTags(GetTags('enrollment','dx',$enrollmentid),'dx', 'enrollment')?></td>
													</tr>
													<? if (($enroll_enddate != "0000-00-00 00:00:00") && ($enroll_enddate != "")) { ?>
													<tr>
														<td class="label" style="color: darkred">Un-enroll date</td>
														<td class="value" style="color: darkred"><?=$enroll_enddate?></td>
													</tr>
													<? } ?>
												</table>
												
												<br><br>
												<b style="color: #666;">Views for this enrollment</b><br>
												<div style="padding: 5px">
												<a href="enrollment.php?enrollmentid=<?=$enrollmentid?>">Enrollment sheet</a><br>
												<a href="timeline.php?enrollmentid=<?=$enrollmentid?>">Timeline</a><br>
												<a href="subjects.php?action=print&id=<?=$id?>&enrollmentid=<?=$enrollmentid?>">Imaging summary</a><br>
												</div>
												<br><br>
												
												<?
												if ($viewphi) {
													if (($enrolled) && ($projectadmin)) { ?>
												<br><br>
												<form action="subjects.php" method="post" style="margin:0px; padding:0px; display:inline;">
												<input type="hidden" name="id" value="<?=$id?>">
												<input type="hidden" name="action" value="changeproject">
												<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
												<br>
												<details>
												<summary class="tiny" style="color:darkred; font-weight:normal">Enroll in different project</summary>
												<span style="font-size: 10pt; font-weight: normal;">Un-enroll subject from this project and enroll in this project, moving all imaging, assessments, and measures:</span>
												<select name="newprojectid">
												<?
													$sqlstring = "select a.*, b.user_fullname from projects a left join users b on a.project_pi = b.user_id where a.project_status = 'active' order by a.project_name";
													$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
													while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
														$pid = $row['project_id'];
														$project_name = $row['project_name'];
														$project_costcenter = $row['project_costcenter'];
														$project_enddate = $row['project_enddate'];
														$user_fullname = $row['user_fullname'];

														$perms = GetCurrentUserProjectPermissions(array($pid));
														if (GetPerm($perms, 'modifyphi', $pid)) { $disabled = ""; } else { $disabled = "disabled"; }
														
														?>
														<option value="<?=$pid?>" <?=$disabled?>><?=$project_name?> (<?=$project_costcenter?>)</option>
														<?
													}
												?>
												</select>
												<input type="submit" value="Move">
												</form>
												</details>
												<?
													} /* end if project admin */
												} /* end if viewphi */
												?>
											</div>
										</td>
										<td style="padding: 10px">
											<?
												if (!$viewdata) {
													echo "No data access privileges to this project";
												}
												else {
											?>

											<!-- ******************** Imaging ******************** -->
											
											<div style="font-size: 9pt; background-color:white; text-align: center; border: 1px solid #888; border-radius:5px; padding:3px">
											<table width="100%">
												<tr>
													<td valign="top" style="padding-bottom: 8px"><b>Imaging studies</b></td>
													<td align="right">
														<? if (!$enrolled) { ?>
														<span style="color: #666">Subject is un-enrolled. Cannot create new studies</span>
														<? } else { ?>
														<table>
															<tr>
																<form action="subjects.php" method="post">
																<td align="right">
																	<input type="hidden" name="id" value="<?=$id?>">
																	<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
																	<input type="hidden" name="action" value="newstudy">
																	<span style="font-size:10pt">New <u>empty</u> study</span>
																	<select name="modality">
																		<option value="" style="<?=$style?>">(Select modality)</option>
																		<?
																		$sqlstring = "select * from modalities order by mod_code";
																		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
																		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
																			$mod_code = $row['mod_code'];
																			$mod_desc = $row['mod_desc'];
																			?>
																			<option value="<?=$mod_code?>" style="<?=$style?>">[<?=$mod_code?>] <?=$mod_desc?></option>
																			<?
																		}
																	?>
																	</select>
																	<input type="submit" value="Create">
																</td>
																</form>														
															</tr>
															<tr>
																<form action="subjects.php" method="post">
																<td align="right">
																	<input type="hidden" name="id" value="<?=$id?>">
																	<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
																	<input type="hidden" name="action" value="newstudyfromtemplate">
																	<span style="font-size:10pt">New study from <u>template</u></span>
																	<select name="templateid">
																		<option value="" style="<?=$style?>">(Select template)</option>
																		<?
																		$sqlstring = "select * from study_template where project_id = $projectid order by template_name asc";
																		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
																		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
																			$templateid = $row['studytemplate_id'];
																			$templatename = $row['template_name'];
																			$templatemodality = $row['template_modality'];
																			?>
																			<option value="<?=$templateid?>" style="<?=$style?>"><?=$templatename?> (<?=$templatemodality?>)</option>
																			<?
																		}
																	?>
																	</select>
																	<input type="submit" value="Create">
																</td>
																</form>														
															</tr>
														</table>
														<? } ?>
													</td>
												</tr>
											</table>
											<?
												$sqlstring = "select a.*, datediff(a.study_datetime, c.birthdate) 'ageatscan' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id = $enrollmentid order by a.study_datetime desc";
												$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
												if (mysqli_num_rows($result2) > 0) {
												?>
												<table width="100%" class="smalldisplaytable" style="border: 0px; width: 100%; padding:5px; border-radius: 0px;">
													<thead>
														<th style="background-color: #273F70; color: #fff">Study</th>
														<th>Modality</th>
														<th>Date</th>
														<th># series</th>
														<th>Age</th>
														<th>Physician</th>
														<th>Operator</th>
														<th>Site</th>
														<th>Study ID</th>
														<th>Visit</th>
														<th>Rad Read</th>
													</thead>
													<tbody>
													<?
													while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
														
														$study_id = $row2['study_id'];
														$study_num = $row2['study_num'];
														$study_modality = $row2['study_modality'];
														$study_datetime = $row2['study_datetime'];
														$study_ageatscan = $row2['study_ageatscan'];
														$calcage = number_format($row2['ageatscan']/365.25,1);
														$study_operator = $row2['study_operator'];
														$study_performingphysician = $row2['study_performingphysician'];
														$study_site = $row2['study_site'];
														$study_type = $row2['study_type'];
														$study_status = $row2['study_status'];
														$study_doradread = $row2['study_doradread'];
														
														if (trim($study_ageatscan) != 0) {
															$age = $study_ageatscan;
														}
														else {
															$age = $calcage;
														}
														
														if ($study_modality != "") {
															$sqlstring4 = "show tables like '" . strtolower($study_modality) . "_series'";
															$result4 = MySQLiQuery($sqlstring4, __FILE__, __LINE__);
															if (mysqli_num_rows($result4) > 0) {
																$sqlstring3 = "select count(*) 'seriescount' from " . strtolower($study_modality) . "_series where study_id = $study_id";
																$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
																$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
																$seriescount = $row3['seriescount'];
															}
															else {
																$seriescount = "<span style='color:red'>Invalid modality [$study_modality]</span>";
															}
														}
														?>
														<tr onMouseOver="this.style.backgroundColor='#9EBDFF'; this.style.cursor='pointer';" onMouseOut="this.style.backgroundColor=''; this.style.cursor='auto';" onClick="window.location='studies.php?id=<?=$study_id?>'">
															<td style="text-align: center;"><a href="studies.php?id=<?=$study_id?>" style="font-size: larger; font-weight: bold"><?=$study_num?></a></td>
															<td><?
															 if ($study_modality == "") { ?><span style="color: white; background-color: red">&nbsp;blank&nbsp;</span><? }
															 else { echo $study_modality; }
															?></td>
															<td><?=$study_datetime?></td>
															<td><?=$seriescount?></td>
															<td><?=number_format($age,1)?> <span class="tiny">&nbsp;y</span></td>
															<td><?=$study_performingphysician?></td>
															<td><?=$study_operator?></td>
															<td><?=$study_site?></td>
															<td><tt><?=$uid?><?=$study_num?></tt></td>
															<td><?=$study_type?></td>
															<td><? if ($study_doradread) { echo "&#x2713;"; } ?></td>
															<!--<? if ($projectadmin) { ?><td><input type="checkbox" name="studyids[]" value="<?=$study_id?>"></td><? } ?>-->
														</tr>
														<?
													}
													?>
												</table>
												</form>
												<?
												}
												else {
													?>
													No imaging studies
													<?
												}
												?>
											</div>
											<br>
											<!-- ******************** Assessments ******************** -->
											
											<div style="font-size: 9pt; background-color:white; text-align: center; border: 1px solid #888; border-radius:5px; padding:3px">
											<table width="100%">
												<tr>
													<td><b>Assessments</b></td>
													<form action="assessments.php" method="post">
													<td align="right">
														<? if (!$enrolled) { $disabled = "disabled"; } else { $disabled = ""; } ?>
														<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
														<input type="hidden" name="projectid" value="<?=$projectid?>">
														<input type="hidden" name="action" value="create">
														<span style="font-size: 10pt">Add assessment:</span>
														<select name="formid" <?=$disabled?>>
															<option value="">(Select assessment)</option>
														<?
															$sqlstringB = "select * from assessment_forms where form_ispublished = 1 and project_id = $projectid order by form_title";
															$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
															while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
																$form_id = $rowB['form_id'];
																$form_title = $rowB['form_title'];
																$projectid = $rowB['project_id'];
															?>
																<option value="<?=$form_id?>" style="<?=$style?>"><?=$form_title?></option>
																<?
															}
														?>
														</select>
														<input type="submit" value="Create"  <?=$disabled?>>
													</td>
													</form>

												</tr>
											</table>
												<?
												$sqlstring3 = "select a.*, b.form_title from assessments a left join assessment_forms b on a.form_id = b.form_id where a.enrollment_id = $enrollmentid and b.project_id = $projectid";
												$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
												if (mysqli_num_rows($result3) > 0) {
												?>
												<table width="100%" class="smalldisplaytable" style="background-color: #FFFFFF; border-radius: 5px; width: 100%; padding:5px">
													<thead>
														<th>Instrument</th>
														<th>Date</th>
														<th>Experimentor</th>
														<th>Rater</th>
														<th>Complete?</th>
													</thead>
													<tbody>
													<?
													while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
														$experiment_id = $row3['experiment_id'];
														$form_title = $row3['form_title'];
														$exp_admindate = $row3['exp_admindate'];
														$experimentor = $row3['experimentor'];
														$rater_username = $row3['rater_username'];
														$iscomplete = $row3['iscomplete'];
														if ($iscomplete) { $action = "view"; } else { $action = "edit"; }
														?>
														<tr onMouseOver="this.style.backgroundColor='#9EBDFF'" onMouseOut="this.style.backgroundColor=''">
															<td><a href="assessments.php?action=<?=$action?>&experimentid=<?=$experiment_id?>&projectid=<?=$projectid?>"><?=$form_title?></a></td>
															<td><?=$exp_admindate?></td>
															<td><?=$experimentor?></td>
															<td><?=$rater_username?></td>
															<td><? if ($iscomplete) { echo "&#10004;"; }
															else {
																?>
																<a href="assessments.php?action=completed&experimentid=<?=$experiment_id?>&projectid=<?=$projectid?>">Mark as complete</a>
																<?
															}
															?></td>
														</tr>
														<?
													}
													?>
													</table>
													<?
												}
												else {
													?>
													No assessments
													<?
												}
												?>
											</div>
											<br>
											
											<!-- ******************** Phenotypes ******************** -->
											
											<div style="font-size: 9pt; background-color:white; text-align: center; border: 1px solid gray; border-radius:5px; padding:3px">
											<table width="100%">
												<tr>
													<td><b>Phenotypic </b><a href="measures.php?enrollmentid=<?=$enrollmentid?>">measures</a></td>
												</tr>
											</table>
												<?
												$sqlstring3 = "select * from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = $enrollmentid";
												$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
												$numrows = mysqli_num_rows($result3);
												if ($numrows > 0) {
												?>
													<div style="-moz-column-count:2; -webkit-column-count:2; column-count:2; width: 100%; font-size:9pt; background-color: white; padding: 4px; border-radius:5px; -moz-column-rule:1px outset #DDD; -webkit-column-rule:1px outset #DDD; column-rule:1px outset #DDD; border:1px solid #888">
													<?
													while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
														$measureid = $row3['measure_id'];
														$measure_name = $row3['measure_name'];
														$measure_type = $row3['measure_type'];
														$measure_valuestring = $row3['measure_valuestring'];
														$measure_valuenum = $row3['measure_valuenum'];
														$measure_rater = $row3['measure_rater'];
														$measure_rater2 = $row3['measure_rater2'];
														$measure_isdoubleentered = $row3['measure_isdoubleentered'];
														$measure_datecomplete = $row3['measure_datecomplete'];
														switch ($measure_type) {
															case 's': $value = $measure_valuestring; break;
															case 'n': $value = $measure_valuenum; break;
														}
														if (!$measure_isdoubleentered) {
															$color="red";
														}
														else {
															$color="darkblue";
														}
														?>
														<?=$measure_name?> <span style="color: <?=$color?>"><b><?=$value?></b></span><br>
														<?
													}
													?>
													</div>
													<?
												}
												else {
													?>
													No measures
													<?
												}
											?>
											</div>
											<br>

											<!-- ******************** Drugs ******************** -->

											<div style="font-size: 9pt; background-color:white; text-align: center; border: 1px solid gray; border-radius:5px; padding:3px">
											<table width="100%">
												<tr>
													<td><a href="drugs.php?enrollmentid=<?=$enrollmentid?>">Drugs/Dosing</a> <span class="tiny">medications/treatments/substance use</span></td>
												</tr>
											</table>
											<?
												$sqlstring3 = "select a.*,b.*,  date_format(a.drug_startdate,'%m-%d-%Y; %r') 'startdate', date_format(a.drug_enddate,'%m-%d-%Y; %r') 'enddate' from drugs a left join drugnames b on a.drugname_id = b.drugname_id where enrollment_id = $enrollmentid";
												$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
												$numrows = mysqli_num_rows($result3);
												if ($numrows > 0) {
												?>
 												<table width="100%" class="smalldisplaytable" style="background-color: #FFFFFF; border-radius: 5px; width: 100%; padding:5px">
													<thead align="left">
														<th>Drug</th>
														<th>Type</th>
														<th>Route</th>
														<th>Amount</th>
														<th>Dates (mm/dd/yyyy; hh:mm:ss AM/PM)</th>
													</thead>
													<tbody>
													<?
													while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
														$drug_id = $row3['drug_id'];
														$startdate = $row3['startdate'];
														$enddate = $row3['enddate'];
														$drug_dose = $row3['drug_doseamount'];
														$drug_dosefreq = $row3['drug_dosefrequency'];
														$drug_route = $row3['drug_route'];
														$drug_name = $row3['drug_name'];
														$drug_type = $row3['drug_type'];

														if ($enddate=='')  {
															$enddate = 'TO-DATE';
                                               			}

														?>
														<tr>
															<td><?=$drug_name?></td>
															<td><?=$drug_type?></td>
															<td><?=$drug_route?></td>
															<td><?=$drug_dose?> / <?=$drug_dosefreq?></td>
															<td><?=$startdate?> - <?=$enddate?></td>
														</tr>
														<?
													}
													?>
													</tbody>
												</table>
													<?
												}
												else {
													?>
													No drugs
													<?
												}
											?>
											</div>
											<br>

											<!-- ******************** Vitals ******************** -->
											<div style="font-size: 9pt; background-color:white; text-align: center; border: 1px solid gray; border-radius:5px; padding:3px">
											<table width="100%">
												<tr>
													<td><a href="vitals.php?enrollmentid=<?=$enrollmentid?>">Vitals</a> <span class="tiny"></span></td>
												</tr>
											</table>
												<?

 											$sqlstring4 = "select a.*,b.*,  date_format(a.vital_date,'%m-%d-%Y; %r') 'vdate' from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where enrollment_id = $enrollmentid";
												$result4 = MySQLiQuery($sqlstring4, __FILE__, __LINE__);
												$numrows = mysqli_num_rows($result4);
												if ($numrows > 0) {
													?>
													<details>
													<summary>List of Vitals</summary>
													<table width="100%" class="smalldisplaytable" style="background-color: #FFFFFF; border-radius: 5px; width: 100%; padding:5px">
														<thead align="left">
															<th>Vitals</th>
															<th>Type</th>
															<th>Value</th>
															<th>Notes</th>
															<th>Date (mm/dd/yyyy; hh:mm:ss AM/PM)</th>
														</thead>
														<tbody>
													<?
													while ($row4 = mysqli_fetch_array($result4, MYSQLI_ASSOC)) {
														$drug_id = $row4['vital_id'];
														$vdate = $row4['vdate'];
														$vital_value = $row4['vital_value'];
														$vital_notes = $row4['vital_notes'];
														$vital_name = $row4['vital_name'];
														$vital_type = $row4['vital_type'];
														?>
														<tr>
															<td size="15"><?=$vital_name?></td>
															<td size="15"><?=$vital_type?></td>
															<td size="15"><?=$vital_value?></td>
															<td size="15"><?=$vital_notes?></td>
															<td size="15"><?=$vdate?></td>
														</tr>
														<?
													}
													?>
														</tbody>
													</table>
													<?
												}
												else {
													?>
													No vitals
													<?
												}
											?>
											</div>
										</td>
										<? } ?>
									</tr>
								</table>
							</td>
						</tr>
						<?
							}
						?>
					</table>
				</td>
			</tr>
		</table>
		<br><br><br><br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- PrintEnrollment -------------------- */
	/* -------------------------------------------- */
	function PrintEnrollment($id, $enrollmentid) {
		if (!ValidID($id,'Subject ID')) { return; }
		if (!ValidID($enrollmentid,'Enrollment ID')) { return; }

		/* get privacy information */
		$userid = $_SESSION['userid'];

		/* check if they have enrollments for a valid project */
		$sqlstring = "select a.* from enrollment a right join projects b on a.project_id = b.project_id where a.subject_id = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$hasenrollments = 1;
		}
		else {
			$hasenrollments = 0;
		}
	
		$perms = GetCurrentUserProjectPermissions($projectids);
		$urllist['Subjects'] = "subjects.php";
		NavigationBar("$uid", $urllist, $perms);
		
		$sqlstring = "select a.*, datediff(a.study_datetime, c.birthdate) 'ageatscan' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id = $enrollmentid order by a.study_num asc";
		$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result2) > 0) {
		?>
		<table border="1">
			<thead>
				<th>#</th>
				<th>Modality</th>
				<th>Date</th>
				<th>Age<span class="tiny">&nbsp;y</span></th>
				<th>Site</th>
				<th>Visit</th>
			</thead>
			<tbody>
			<?
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$study_id = $row2['study_id'];
				$study_num = $row2['study_num'];
				$study_modality = $row2['study_modality'];
				$study_datetime = $row2['study_datetime'];
				$study_ageatscan = $row2['study_ageatscan'];
				$calcage = number_format($row2['ageatscan']/365.25,1);
				$study_operator = $row2['study_operator'];
				$study_performingphysician = $row2['study_performingphysician'];
				$study_site = $row2['study_site'];
				$study_type = $row2['study_type'];
				$study_status = $row2['study_status'];
				$study_doradread = $row2['study_doradread'];
				
				if (trim($study_ageatscan) != 0) {
					$age = $study_ageatscan;
				}
				else {
					$age = $calcage;
				}
				
				?>
				<tr>
					<td><b><?=$study_num?></b></td>
					<td><?
					 if ($study_modality == "") { ?><span style="color: white; background-color: red">&nbsp;blank&nbsp;</span><? }
					 else { echo $study_modality; }
					?></td>
					<td><?=$study_datetime?></td>
					<td><?=number_format($age,1)?></td>
					<td><?=$study_site?></td>
					<td><?=$study_type?></td>
				</tr>
				<tr>
					<td colspan="6" style="padding-left: 15px">
				<?
					if ($study_modality != "") {
						$sqlstring4 = "show tables like '" . strtolower($study_modality) . "_series'";
						$result4 = MySQLiQuery($sqlstring4, __FILE__, __LINE__);
						if (mysqli_num_rows($result4) > 0) {
							$sqlstring3 = "select * from " . strtolower($study_modality) . "_series where study_id = $study_id order by series_num asc";
							$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
							while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
								if ($row3['series_desc'] == "") {
									$protocol = $row3['series_protocol'];
								}
								else {
									$protocol = $row3['series_desc'];
								}
								$seriesnum = $row3['series_num'];
								echo "$seriesnum - $protocol<br>";
							}
						}
						else {
							echo "<span style='color:red'>Invalid modality [$study_modality]</span><br>";
						}
					}
				?>
					</td>
				</tr>
				<?
			}
			?>
		</table>
		<?
		}
		else {
			?>
			<div style="font-size: 9pt; background-color:white; text-align: center; border: 1px solid #888; border-radius:5px; padding:3px">No imaging studies</div>
			<?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySubjectForm ----------------- */
	/* -------------------------------------------- */
	function DisplaySubjectForm($type, $id) {

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			/* check for valid subject ID */
			if (!ValidID($id,'Subject ID')) { return; }
			
			$sqlstring = "select * from subjects where subject_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$name = $row['name'];
			$dob = $row['birthdate'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$phone1 = $row['phone1'];
			$email = $row['email'];
			$maritalstatus = $row['marital_status'];
			$smokingstatus = $row['smoking_status'];
			$uid = $row['uid'];
			$guid = $row['guid'];
			$cancontact = $row['cancontact'];
			
			$tags = GetTags('subject','dx',$id);
			list($lastname, $firstname) = explode("^",$name);
		
			/* get privacy information */
			$userid = $_SESSION['userid'];
			$sqlstring = "select c.project_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join projects c on b.project_id = c.project_id where a.subject_id = '$id'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$projectids[] = $row['project_id'];
				}
			}

			$formaction = "confirmupdate";
			$formtitle = "Updating &nbsp;<span class='uid'>" . $uid . "</span>";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "confirmadd";
			$formtitle = "Add new subject";
			$submitbuttonlabel = "Add";
			$dob = "1900-01-01";
			$modifyphi = $viewphi = 1;
		}

		$perms = GetCurrentUserProjectPermissions($projectids);
		$urllist['Subjects'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$id";
		NavigationBar("$formtitle", $urllist, $perms);

		if (GetPerm($perms, 'projectadmin', $projectid)) { $projectadmin = 1; } else { $projectadmin = 0; }
		if (GetPerm($perms, 'modifyphi', $projectid)) { $modifyphi = 1; } else { $modifyphi = 0; }
		if (GetPerm($perms, 'viewphi', $projectid)) { $viewphi = 1; } else { $viewphi = 0; }
		if (GetPerm($perms, 'modifydata', $projectid)) { $modifydata = 1; } else { $modifydata = 0; }
		if (GetPerm($perms, 'viewdata', $projectid)) { $viewdata = 1; } else { $viewdata = 0; }

		/* kick them out if they shouldn't be seeing anything on this page */
		if ((!$modifyphi) && (!$viewphi) && ($type != 'add')) {
			return;
		}
		
		if ($type == 'add') { $modifyphi = 1; }
	?>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#form1").validate();
			});
		</script>
		<div align="center">
		<table class="entrytable">
			<form method="post" id="form1" action="subjects.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="uid" value="<?=$uid?>">
			<? if ($type == "add") { ?>
			<tr title="This will encrypt the name and alternate UIDs.<br>It will also change the DOB to year only (ex. 1980-00-00)">
				<td class="label">Encrypt</td>
				<td><input type="checkbox" name="encrypt" value="1"></td>
			</tr>
			<? } ?>
			<tr>
				<td class="requiredlabel">First name</td>
				<td>
					<? if ($modifyphi) { ?>
					<input type="text" size="50" name="firstname" value="<?=$firstname?>" style="background-color: lightyellow; border: 1px solid gray">
					<? } else { ?>
					<input type="text" size="50" name="firstname" value="" disabled style="background-color: lightgray; border: 1px solid gray">
					<? } ?>
				</td>
			</tr>
			<tr>
				<td class="requiredlabel">Last name</td>
				<td>
					<? if ($modifyphi) { ?>
					<input type="text" size="50" name="lastname" value="<?=$lastname?>" required style="background-color: lightyellow; border: 1px solid gray">
					<? } else { ?>
					<input type="text" size="50" name="" value="" disabled style="background-color: lightgray; border: 1px solid gray">
					<? } ?>
				</td>
			</tr>
			<tr>
				<td class="requiredlabel">Date of birth</td>
				<td>
					<? if ($modifyphi) { ?>
					<input type="date" name="dob" value="<?=$dob?>" required style="background-color: lightyellow; border: 1px solid gray"><!--&nbsp;<span class="subtlemessage">YYYY-MM-DD</span>-->
					<? } else { ?>
					<input type="text" name="" value="" disabled style="background-color: lightgray; border: 1px solid gray">
					<? } ?>
				</td>
			</tr>
			<tr>
				<td class="requiredlabel">IDs<br><span class="tiny">comma separated list</span></td>
				<td>
					<table style="border: 1px solid #ddd; border-radius:3px; color: #555; font-size: 11pt">
						<thead>
							<tr>
								<th align="right" style="padding-right: 8px"><b>Project</b></th>
								<th align="left" title="Use asterisk next to primary ID (Example *PrimaryID1, otherID1, otherID23)"><b>IDs</b></th>
							</tr>
						</thead>
						<tr>
							<td align="right" style="padding-right: 8px">All projects</td>
							<td><input type="text" size="50" name="altuids[]" value="<?=implode2(', ',GetAlternateUIDs($id,0))?>" style="background-color: lightyellow; border: 1px solid gray"></td>
							<input type="hidden" name="enrollmentids[]" value="">
						</tr>
						<?
						if ($id != "") {
							$sqlstring = "select a.enrollment_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = '$id'";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$enrollmentid = $row['enrollment_id'];
								$projectname = $row['project_name'];
								?>
								<tr>
									<td align="right" style="padding-right: 8px"><?=$projectname?></td>
									<td><input type="text" size="50" name="altuids[]" value="<?=implode2(', ',GetAlternateUIDs($id,$enrollmentid))?>" style="background-color: lightyellow; border: 1px solid gray"></td>
									<input type="hidden" name="enrollmentids[]" value="<?=$enrollmentid?>">
								</tr>
								<?
							}
						}
						?>
					</table>
				</td>
			</tr>
			<tr>
				<td class="requiredlabel">Sex</td>
				<td>
					<select name="gender" style="background-color: lightyellow; border: 1px solid gray">
						<option value="" <? if ($gender == "") echo "selected"; ?>>(Select sex)</option>
						<option value="U" <? if ($gender == "U") echo "selected"; ?>>Unknown</option>
						<option value="F" <? if ($gender == "F") echo "selected"; ?>>Female</option>
						<option value="M" <? if ($gender == "M") echo "selected"; ?>>Male</option>
						<option value="O" <? if ($gender == "O") echo "selected"; ?>>Other</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Ethnicity</td>
				<td>
					<select name="ethnicity1">
						<option value="" <? if ($ethnicity1 == "") echo "selected"; ?>>(Select ethnicity)</option>
						<option value="hispanic" <? if ($ethnicity1 == "hispanic") echo "selected"; ?>>Hispanic/Latino</option>
						<option value="nothispanic" <? if ($ethnicity1 == "nothispanic") echo "selected"; ?>>Not hispanic/latino</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Race</td>
				<td>
					<select name="ethnicity2">
						<option value="" <? if ($ethnicity2 == "") echo "selected"; ?>>(Select race)</option>
						<option value="indian" <? if ($ethnicity2 == "indian") echo "selected"; ?>>American Indian/Alaska Native</option>
						<option value="asian" <? if ($ethnicity2 == "asian") echo "selected"; ?>>Asian</option>
						<option value="black" <? if ($ethnicity2 == "black") echo "selected"; ?>>Black/African American</option>
						<option value="islander" <? if ($ethnicity2 == "islander") echo "selected"; ?>>Hawaiian/Pacific Islander</option>
						<option value="white" <? if ($ethnicity2 == "white") echo "selected"; ?>>White</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Handedness</td>
				<td>
					<select name="handedness">
						<option value="" <? if ($handedness == "") echo "selected"; ?>>(Select a status)</option>
						<option value="U" <? if ($handedness == "U") echo "selected"; ?>>Unknown</option>
						<option value="R" <? if ($handedness == "R") echo "selected"; ?>>Right</option>
						<option value="L" <? if ($handedness == "L") echo "selected"; ?>>Left</option>
						<option value="A" <? if ($handedness == "A") echo "selected"; ?>>Ambidextrous</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Education<br><span class="tiny">highest level completed</span></td>
				<td>
					<select name="education">
						<option value="" <? if ($education == "") echo "selected"; ?>>(Select a status)</option>
						<option value="0" <? if ($education == "0") echo "selected"; ?>>Unknown</option>
						<option value="1" <? if ($education == "1") echo "selected"; ?>>Grade School</option>
						<option value="2" <? if ($education == "2") echo "selected"; ?>>Middle School</option>
						<option value="3" <? if ($education == "3") echo "selected"; ?>>High School/GED</option>
						<option value="4" <? if ($education == "4") echo "selected"; ?>>Trade School</option>
						<option value="5" <? if ($education == "5") echo "selected"; ?>>Associates Degree</option>
						<option value="6" <? if ($education == "6") echo "selected"; ?>>Bachelors Degree</option>
						<option value="7" <? if ($education == "7") echo "selected"; ?>>Masters Degree</option>
						<option value="8" <? if ($education == "8") echo "selected"; ?>>Doctoral Degree</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Phone</td>
				<td>
					<? if ($modifyphi) { ?>
					<input type="text" name="phone" value="<?=$phone1?>"> <?=$phone1?>
					<? } else { ?>
					<input type="text" name="" value="" disabled style="background-color: lightgray; border: 1px solid gray">
					<? } ?>
				</td>
			</tr>
			<tr>
				<td class="label">E-mail</td>
				<td>
				<? if ($modifyphi) { ?>
				<input type="text" name="email" value="<?=$email?>">
				<? } else { ?>
				<input type="text" name="" value="" disabled style="background-color: lightgray; border: 1px solid gray">
				<? } ?>
				</td>
			</tr>
			<tr>
				<td class="label">Marital Status</td>
				<td>
				<? if ($modifyphi) { ?>
					<select name="maritalstatus">
						<option value="" <? if ($maritalstatus == "") echo "selected"; ?>>(Select a status)</option>
						<option value="unknown" <? if ($maritalstatus == "unknown") echo "selected"; ?>>Unknown</option>
						<option value="single" <? if ($maritalstatus == "single") echo "selected"; ?>>Single</option>
						<option value="married" <? if ($maritalstatus == "married") echo "selected"; ?>>Married</option>
						<option value="divorced" <? if ($maritalstatus == "divorced") echo "selected"; ?>>Divorced</option>
						<option value="separated" <? if ($maritalstatus == "separated") echo "selected"; ?>>Separated</option>
						<option value="civilunion" <? if ($maritalstatus == "civilunion") echo "selected"; ?>>Civil Union</option>
						<option value="cohabitating" <? if ($maritalstatus == "cohabitating") echo "selected"; ?>>Cohabitating</option>
						<option value="widowed" <? if ($maritalstatus == "widowed") echo "selected"; ?>>Widowed</option>
					</select>
				<? } else { ?>
				<input type="text" name="" value="" disabled style="background-color: lightgray; border: 1px solid gray">
				<? } ?>
				</td>
			</tr>
			<tr>
				<td class="label">Smoking Status</td>
				<td>
				<? if ($modifyphi) { ?>
					<select name="smokingstatus">
						<option value="" <? if ($smokingstatus == "") echo "selected"; ?>>(Select a status)</option>
						<option value="unknown" <? if ($smokingstatus == "unknown") echo "selected"; ?>>Unknown</option>
						<option value="never" <? if ($smokingstatus == "never") echo "selected"; ?>>Never</option>
						<option value="past" <? if ($smokingstatus == "past") echo "selected"; ?>>Past</option>
						<option value="current" <? if ($smokingstatus == "current") echo "selected"; ?>>Current</option>
					</select>
				<? } else { ?>
				<input type="text" name="" value="" disabled style="background-color: lightgray; border: 1px solid gray">
				<? } ?>
				</td>
			</tr>
			<tr>
				<td class="label">GUID<br><span class="tiny">NDAR format</span></td>
				<td><input type="text" name="guid" value="<?=$guid?>"></td>
			</tr>
			<tr>
				<td class="label">Can contact?</td>
				<td><input type="checkbox" name="cancontact" value="1" <? if ($cancontact) echo "checked"; ?>></td>
			</tr>
			<tr>
				<td class="label">Tags<br><span class="tiny">comma separated list</span></td>
				<td><input type="text" size="50" name="tags" value="<?=implode2(', ',GetTags('subject','',$id))?>"></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="reset" title="Reset the form the original values"> &nbsp; <input type="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		</div>
	<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- MakeSQLorList ---------------------- */
	/* -------------------------------------------- */
	function MakeSQLorList($str, $field) {
		$str = str_ireplace(array('^',',','-'), " ", $str);
		$parts = explode(" ", $str);
		foreach ($parts as $part) {
			$newparts[] = "`$field` like '%" . trim($part) . "%'";
			$newparts[] = "`$field` = sha1('" . trim($part) . "')";
			$newparts[] = "`$field` = sha1(upper('" . trim($part) . "'))";
			$newparts[] = "`$field` = sha1(lower('" . trim($part) . "'))";
		}
		return implode2(" or ", $newparts);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySubjectList ----------------- */
	/* -------------------------------------------- */
	function DisplaySubjectList($searchuid, $searchaltuid, $searchname, $searchgender, $searchdob, $searchactive) {
	
		$searchuid = mysqli_real_escape_string($GLOBALS['linki'], $searchuid);
		$searchaltuid = mysqli_real_escape_string($GLOBALS['linki'], $searchaltuid);
		$searchname = mysqli_real_escape_string($GLOBALS['linki'], $searchname);
		$searchgender = mysqli_real_escape_string($GLOBALS['linki'], $searchgender);
		$searchdob = mysqli_real_escape_string($GLOBALS['linki'], $searchdob);
		$searchactive = mysqli_real_escape_string($GLOBALS['linki'], $searchactive);
	?>
	<script>
		$(document).ready(function() {
			$('#newsubjecttext').hide();
			
			$('#newsubject').hover(
				function () { $('#newsubjecttext').show(); }, 
				function () { $('#newsubjecttext').hide(); }
			);
		});
	</script>
	
	<table width="100%" cellspacing="0" class="headertable">
		<tbody>
			<tr>
				<td class="header1">Subjects</td>
				<td class="header2" align="right">
					<a href="subjects.php">Subject List</a> &gt; 
					<a href="subjects.php?action=addform" id="newsubject"> New Subject </a><br>
					<div align="right" id="newsubjecttext" style="color:darkred; background-color: yellow; font-size:9pt; border: 1px solid red; padding:5px; border-radius:5px"><b>Search on this page before creating a new subject</b><br>to make sure they do not already exist!</div>
				</td>
			</tr>
		</tbody>
	</table>

	<br><br>
	
	<table class="graydisplaytable" width="100%">
		<thead>
			<tr>
				<th align="left">&nbsp;</th>
				<th>UID<br><span class="tiny">S1234ABC</span></th>
				<th>Alternate UID</th>
				<th>Name</th>
				<th>Sex<br><span class="tiny">M,F,O,U</span></th>
				<th>DOB<br><span class="tiny">YYYY-MM-DD</span></th>
				<th>Projects</th>
				<th>Active?</th>
				<th>Activity date</th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<script type="text/javascript">
		$(document).ready(function() {
			$("#rightcheckall").click(function() {
				var checked_status = this.checked;
				$(".rightcheck").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
		});
		</script>
		<tbody>
			<form method="post" action="subjects.php">
			<input type="hidden" name="action" value="search">
			<tr>
				<td>&nbsp;</td>
				<td align="left"><input type="text" placeholder="UID" name="searchuid" id="searchuid" value="<?=$searchuid?>" size="15" autofocus="autofocus"></td>
				<td align="left"><input type="text" placeholder="Alternate UID" name="searchaltuid" value="<?=$searchaltuid?>" size="20"></td>
				<td align="left"><input type="text" placeholder="Name" name="searchname" value="<?=$searchname?>" size="40"></td>
				<td align="left"><input type="text" placeholder="Sex" name="searchgender" value="<?=$searchgender?>" size="2" maxlength="2"></td>
				<td align="left"><input type="text" placeholder="YYYY-MM-DD" name="searchdob" value="<?=$searchdob?>" size="10"></td>
				<td> - </td>
				<td align="left"><input type="checkbox" name="searchactive" <? if ($searchactive == '1') { echo "checked"; } ?> value="1"></td>
				<td> - </td>
				<td align="left"><input type="submit" value="Search"></td>
			</tr>
			</form>
			
			<?
				$subjectsfound = 0;
				/* if all the fields are blank, only display the most recent subjects */
				if ( ($searchuid == "") && ($searchaltuid == "") && ($searchname == "") && ($searchgender == "") && ($searchdob == "") ) {
					$sqlstring = "select a.* from subjects a left join enrollment b on a.subject_id = b.subject_id left join user_project c on b.project_id = c.project_id left join projects d on c.project_id = d.project_id where a.isactive = 1 group by a.uid order by a.lastupdate desc limit 0,25";
					?>
						<tr>
							<td colspan="11" align="center" style="color: #555555; padding:8px; font-size:10pt">
								No search criteria specified
							</td>
						</tr>
					<?
				}
				else {
					$sqlstring = "select a.*, b.altuid, d.view_phi from subjects a left join subject_altuid b on a.subject_id = b.subject_id left join enrollment c on a.subject_id = c.subject_id left join user_project d on c.project_id = d.project_id left join projects e on c.project_id = e.project_id left join studies f on c.enrollment_id = f.enrollment_id where a.uid like '%$searchuid%'";
					if ($searchaltuid != "") { $sqlstring .= " and (b.altuid like '%$searchaltuid%' or b.altuid = sha1('$searchaltuid') or b.altuid = sha1(upper('$searchaltuid')) or b.altuid = sha1(lower('$searchaltuid')) or f.study_alternateid = '$searchaltuid' or f.study_alternateid like '%$searchaltuid%' or f.study_alternateid = sha1('$searchaltuid') or f.study_alternateid = sha1(upper('$searchaltuid')) or f.study_alternateid = sha1(lower('$searchaltuid')) )"; }
					if ($searchname != "") { $sqlstring .= " and (a." . MakeSQLorList($searchname,'name') . ")"; }
					if ($searchgender != "") { $sqlstring .= " and a.`gender` like '%$searchgender%'"; }
					if ($searchdob != "") { $sqlstring .= " and a.`birthdate` like '%$searchdob%'"; }
					$sqlstring .= "and a.isactive = '$searchactive' group by a.uid order by a.name asc";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) > 0) {
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$id = $row['subject_id'];
							$name = $row['name'];
							$dob = $row['birthdate'];
							$gender = $row['gender'];
							$uid = $row['uid'];
							$isactive = $row['isactive'];
							$lastupdate = date('M j, Y g:ia',strtotime($row['lastupdate']));
							$viewphi = $row['view_phi'];

							if (!$viewphi) {
								$dob = substr($dob,0,4) . "-00-00";
							}
							if ($isactive) { $isactivecheck = "&#x2713;"; }
							else { $isactivecheck = ""; }
							
							$altuids = GetAlternateUIDs($id,0);
							
							if (strpos($name,'^') !== false) {
								list($lname, $fname) = explode("^",$name);
								$name = strtoupper(substr($fname,0,1)) . strtoupper(substr($lname,0,1));
							}
							
							/* get project enrollment list */
							$sqlstringA = "SELECT d.*, e.* FROM subjects a LEFT JOIN enrollment b on a.subject_id = b.subject_id LEFT JOIN projects d on d.project_id = b.project_id LEFT JOIN instance e on d.instance_id = e.instance_id WHERE a.subject_id = '$id' GROUP BY d.project_id";
							//PrintSQL($sqlstringA);
							$enrolllist = array();
							$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
							if (mysqli_num_rows($resultA) > 0) {
								while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
									$projectid = $rowA['project_id'];
									$projectname = $rowA['project_name'];
									$projectcostcenter = $rowA['project_costcenter'];
									if ($projectid != "") {
										$enrolllist[$projectid] = "$projectname ($projectcostcenter)";
									}
								}
							}
							
							if ($isactive == 0) { ?><tr style="background-image:url('images/deleted.png')"><? } else { ?><tr><? } ?>
							
								<td><input type="checkbox" name="uids[]" value="<?=$uid?>"></td>
								<!--<input type="hidden" name="uidids[]" value="<?=$id?>">-->
								<td><a href="subjects.php?action=display&id=<?=$id?>"><?=$uid?></a></td>
								<td><?=implode2(', ',$altuids)?></td>
								<td><?=$name?></td>
								<td><?=$gender?></td>
								<td><?=$dob?></td>
								<td>
									<? if (count($enrolllist) > 0) { ?>
									<details style="font-size:8pt; color: gray">
									<summary>Enrolled projects</summary>
									<?
										//PrintVariable($enrolllist);
										foreach ($enrolllist as $projectid => $val) {
											$sqlstringA = "select * from user_project where project_id = '$projectid' and user_id = (select user_id from users where username = '" . $_SESSION['username'] . "')";
											$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
											$viewphi = 0;
											if (mysqli_num_rows($resultA) > 0) {
												$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
												$viewphi = $rowA['view_phi'];
											}
											if ($viewphi) {
												?><span style="color:#238217; white-space:nowrap;" title="You have access to <?=$val?>">&#8226; <?=$val?></span><br><?
											}
											else {
												?><span style="color:#8b0000; white-space:nowrap;" title="You <b>do not</b> have access to <?=$val?>">&#8226; <?=$val?></span><br><?
											}
										}
									?>
									</details>
									<?
									}
									else {
										?><span style="font-size:8pt; color: darkred">Not enrolled</span><?
									}
									?>
								</td>
								<td><?=$isactivecheck?></td>
								<td><?=$lastupdate?></td>
								<? if ($GLOBALS['isadmin']) { ?>
								<!--<td><a href="subjects.php?action=deleteconfirm&id=<?=$id?>"><div class="adminbutton" style="padding: 0px; margin; 0px;">X</div></a></td>-->
								<? } ?>
								<td></td>
								<? if ($GLOBALS['issiteadmin']) { ?>
								<td class="rightcheck"><input type="checkbox" name="ids[]" value="<?=$id?>"></td>
								<? } ?>
							</tr>
							<? 
						}
						$subjectsfound = 1;
					}
				?>
				<tr>
					<td colspan="8">
						<br><br>
						<select name="subjectgroupid">
							<?
								$userid = $_SESSION['userid'];
							
								$sqlstring = "select * from groups where group_type = 'subject' and group_owner = '$userid'";
								$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$groupid = $row['group_id'];
									$groupname = $row['group_name'];
									?>
									<option value="<?=$groupid?>"><?=$groupname?>
									<?
								}
							?>
						</select>
						<input type="submit" name="addtogroup" value="Add to group" onclick="document.subjectlist.action='groups.php';document.subjectlist.action.value='addsubjectstogroup'">
					</td>
					<td colspan="3" align="right">
						<? if ($GLOBALS['issiteadmin']) {?>
						<input type="submit" style="border: 1px solid red; background-color: pink; width:150px; margin:4px" name="obliterate" value="Obliterate subjects" title="Remove all database entries for the subject and move their data to a /deleted directory" onclick="document.subjectlist.action='subjects.php';document.subjectlist.action.value='obliterate'"><br>
						<? } ?>
					</td>
				</tr>
				<?
				}
				?>
			</table>
			</form>
		</tbody>
	</table>
	<?
	}
?>

<? include("footer.php") ?>