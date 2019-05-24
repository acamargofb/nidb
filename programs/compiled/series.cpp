#include "series.h"
#include <QDebug>
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- series ----------------------------------------- */
/* ---------------------------------------------------------- */
series::series(int id, QString m, nidb *a)
{
	n = a;
	seriesid = id;
	modality = m.toLower();
	LoadSeriesInfo();
	//PrintSeriesInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadSeriesInfo --------------------------------- */
/* ---------------------------------------------------------- */
void series::LoadSeriesInfo() {

	QStringList msgs;

	if (seriesid < 1) {
		msgs << "Invalid series ID";
		isValid = false;
	}
	else {
		QSqlQuery q;
		QString sqlstring = QString("select *, d.uid, d.subject_id, c.enrollment_id, b.study_id from %1_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.%1series_id = :seriesid").arg(modality);
		q.prepare(sqlstring);
		q.bindValue(":seriesid", seriesid);
		n->SQLQuery(q, "series->LoadSeriesInfo");
		if (q.size() < 1) {
			msgs << "Query returned no results. Possibly invalid series ID or recently deleted?";
			isValid = false;
		}
		else {
			q.first();
			uid = q.value("uid").toString().trimmed();
			studynum = q.value("study_num").toInt();
			seriesnum = q.value("series_num").toInt();
			subjectid = q.value("subject_id").toInt();
			studyid = q.value("study_id").toInt();
			projectid = q.value("project_id").toInt();
			enrollmentid = q.value("enrollment_id").toInt();
			datatype = q.value("datatype").toString().trimmed();

			/* check to see if anything isn't valid or is blank */
			if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; isValid = false; }
			if (uid == "") { msgs << "uid was blank"; isValid = false; }
			if (studynum < 1) { msgs << "studynum is not valid"; isValid = false; }
			if (seriesnum < 0) { msgs << "seriesnum is not valid"; isValid = false; }

			seriespath = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);
			if (modality == "mr") {
				datapath = seriespath + "/" + datatype;
			}
			else {
				datapath = seriespath + "/" + modality;
			}

			QDir d(seriespath);
			if (!d.exists()) {
				msgs << QString("Invalid series path [%1]").arg(seriespath);
				isValid = false;
			}
		}
	}
	msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintSeriesInfo -------------------------------- */
/* ---------------------------------------------------------- */
void series::PrintSeriesInfo() {
	QString	output = QString("***** Series - [%1] *****\n").arg(seriesid);

	output += QString("   uid: [%1]\n").arg(uid);
	output += QString("   subjectid: [%1]\n").arg(subjectid);
	output += QString("   studyid: [%1]\n").arg(studyid);
	output += QString("   studynum: [%1]\n").arg(studynum);
	output += QString("   seriesid: [%1]\n").arg(seriesid);
	output += QString("   seriesnum: [%1]\n").arg(seriesnum);
	output += QString("   projectid: [%1]\n").arg(projectid);
	output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
	output += QString("   datatype: [%1]\n").arg(datatype);
	output += QString("   modality: [%1]\n").arg(modality);
	output += QString("   isValid: [%1]\n").arg(isValid);
	output += QString("   msg: [%1]\n").arg(msg);
	output += QString("   seriespath: [%1]\n").arg(seriespath);
	output += QString("   datapath: [%1]\n").arg(datapath);

	n->WriteLog(output);
}