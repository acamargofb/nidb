/* ------------------------------------------------------------------------------
  NIDB moduleMRIQA.cpp
  Copyright (C) 2004 - 2019
  Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
  Olin Neuropsychiatry Research Center, Hartford Hospital
  ------------------------------------------------------------------------------
  GPLv3 License:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  ------------------------------------------------------------------------------ */

#include "moduleMRIQA.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- moduleMRIQA ----------------------------------- */
/* ---------------------------------------------------------- */
moduleMRIQA::moduleMRIQA(nidb *a)
{
	n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleMRIQA ---------------------------------- */
/* ---------------------------------------------------------- */
moduleMRIQA::~moduleMRIQA()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleMRIQA::Run() {
	n->WriteLog("Entering the import module");

	int ret(0);

	/* look through DB for all series that don't have an associated mr_qa row */
	QString sqlstring = "SELECT a.mrseries_id FROM mr_series a LEFT JOIN mr_qa b ON a.mrseries_id = b.mrseries_id WHERE b.mrqa_id IS NULL and a.lastupdate < date_sub(now(), interval 3 minute) order by a.mrseries_id desc";

	QSqlQuery q;
	q.prepare(sqlstring);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		int numProcessed(0);
		int numToDo = q.size();
		while (q.next()) {
			n->WriteLog(QString("***** Working on MR QA [%1] of [%2] *****").arg(numProcessed).arg(numToDo));
			n->ModuleRunningCheckIn();
			int mrseriesid = q.value("mrseries_id").toInt();
			QA(mrseriesid);
			/* only process 10 series before exiting the script. Since the script always starts with the newest when it first runs,
			   this will allow newly collect studies a chance to be QA'd if there is a backlog of old studies */
			numProcessed++;
			if (numProcessed > 10)
				continue;
		}
		n->WriteLog("Finished MRI-QA");
		ret = 1;
	}
	else {
		n->WriteLog("Nothing to do");
	}

	return ret;
}


/* ---------------------------------------------------------- */
/* --------- QA --------------------------------------------- */
/* ---------------------------------------------------------- */
bool moduleMRIQA::QA(int seriesid) {

	QStringList msgs;

	/* get the series info */
	series s(seriesid, "MR", n);
	if (!s.isValid) {
		n->WriteLog("Series was not valid: [" + s.msg + "]");
		return false;
	}

	int seriesnum = s.seriesnum;
	int studynum = s.studynum;
	int isderived = s.isderived;
	QString uid = s.uid;
	QString datatype = s.datatype;
	int mrqaid(0);

	QString indir = s.datapath;
	n->WriteLog("======================== Working on ["+indir+"] ========================");

	/* check if this mr_qa row exists */
	QSqlQuery q;
	q.prepare("select * from mr_qa where mrseries_id = :seriesid");
	q.bindValue(":seriesid",seriesid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		/* if a row does exist, go onto the next row */
		msgs << n->WriteLog("Another instance of this module is working on [" + indir + "]");
		return false;
	}
	else {
		/* insert a blank row for this mr_qa and get the row ID */
		QSqlQuery q2;
		q2.prepare("insert into mr_qa (mrseries_id) values (:seriesid)");
		q2.bindValue(":seriesid",seriesid);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		mrqaid = q2.lastInsertId().toInt();
	}

	msgs << n->WriteLog("Setting current directory to ["+indir+"]");
	QDir::setCurrent(indir);

	/* unfortunately, for now, this tmpdir must match the tmpdir in the nii_qa.sh script */
	QString tmpdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
	QString qapath = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);

	/* create the tmp and out paths */
	QString m;
	if (!n->MakePath(tmpdir, m)) {
		msgs << n->WriteLog("Unable to create directory ["+tmpdir+"] because of error ["+m+"]");
		WriteQALog(qapath, msgs.join("\n"));
		return false;
	}

	if (!n->MakePath(qapath, m)) {
		msgs << n->WriteLog("Unable to create directory ["+qapath+"] because of error ["+m+"]");
		WriteQALog(qapath, msgs.join("\n"));
		return false;
	}

	if ((isderived) || (datatype == "nifti")) {
		QString systemstring = QString("cp -v %1/%2/%3/%4/nifti/* %5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum).arg(tmpdir);
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	else {
		QString systemstring;

		/* create a 4D file to pass to the SNR program and run the SNR program on it */
		if (datatype == "dicom")
			systemstring = QString("pwd; %1/./dcm2niix -g y -o '%2' %3").arg(n->cfg["scriptdir"]).arg(tmpdir).arg(indir);
		else
			systemstring = QString("pwd; %1/./dcm2niix -g y -o '%2' %3").arg(n->cfg["scriptdir"]).arg(tmpdir).arg(indir);

		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	QString systemstring;

	msgs << n->WriteLog("Done attempting to convert files... now trying to copy out the first valid Nifti file");
	QDir::setCurrent(tmpdir);

	int c(0);
	qint64 b(0);
	n->GetDirSizeAndFileCount(tmpdir, c, b);
	if ((c == 0) | (b == 0)) {
		msgs << n->WriteLog(QString("No files found in ["+tmpdir+"] after copying or converting. dircount [%1] dirsize [%2]").arg(c).arg(b));
		WriteQALog(qapath, msgs.join("\n"));
		return false;
	}
	else
		msgs << n->WriteLog(QString("Found files in ["+tmpdir+"] after copying or converting. dircount [%1] dirsize [%2]").arg(c).arg(b));

	systemstring = "find . -name '*.nii.gz' | head -1 | xargs -i cp -v {} 4D.nii.gz";
	msgs << n->WriteLog(n->SystemCommand(systemstring));
	systemstring = "find . -name '*.nii' | head -1 | xargs -i cp -v {} 4D.nii";
	msgs << n->WriteLog(n->SystemCommand(systemstring));

	/* check if any 4D file was created */
	QString filepath4d;
	if (QFile::exists(tmpdir + "/4D.nii"))
		filepath4d = tmpdir + "/4D.nii";
	else if (QFile::exists(tmpdir + "/4D.nii.gz"))
		filepath4d = tmpdir + "/4D.nii.gz";

	msgs << n->WriteLog("4D file path ["+filepath4d+"]");

	//QDir::setCurrent(indir);

	/* create a 4D file to pass to the SNR program and run the SNR program on it */
	systemstring = QString("%1/./nii_qa.sh -i "+filepath4d+" -o %3/qa.txt -v 2 -t %2").arg(n->cfg["scriptdir"]).arg(tmpdir).arg(qapath);
	msgs << n->WriteLog(n->SystemCommand(systemstring));

	/* the nii_qa script may have generated a valid nii.gz file, so use that if the 4D.nii.gz doesn't exist */
	//if (QFile::exists(tmpdir + "/4D.nii.gz")) {
	//	systemstring = QString("cp -v %1/s*.nii.gz %1/4D.nii.gz").arg(tmpdir);
	//	msgs << n->WriteLog(n->SystemCommand(systemstring));
	//}
	//if (QFile::exists(tmpdir + "/4D.nii")) {
	//	systemstring = QString("cp -v %1/s*.nii %1/4D.nii").arg(tmpdir);
	//	msgs << n->WriteLog(n->SystemCommand(systemstring));
	//}

	/* move the realignment file(s) from the tmp to the archive directory */
	systemstring = QString("mv -v %1/*.par %2").arg(tmpdir).arg(qapath);
	msgs << n->WriteLog(n->SystemCommand(systemstring));

	/* rename the realignment file to something meaningful */
	systemstring = QString("mv -v %1/*.par %1/MotionCorrection.txt").arg(qapath);
	msgs << n->WriteLog(n->SystemCommand(systemstring));

	/* move and rename the mean,sigma,variance volumes from the tmp to the archive directory */
	systemstring = QString("mv -v %1/*mcvol_meanvol.nii.gz %2/Tmean.nii.gz").arg(tmpdir).arg(qapath);
	msgs << n->WriteLog(n->SystemCommand(systemstring));
	systemstring = QString("mv -v %1/*mcvol_sigma.nii.gz %2/Tsigma.nii.gz").arg(tmpdir).arg(qapath);
	msgs << n->WriteLog(n->SystemCommand(systemstring));
	systemstring = QString("mv -v %1/*mcvol_variance.nii.gz %2/Tvariance.nii.gz").arg(tmpdir).arg(qapath);
	msgs << n->WriteLog(n->SystemCommand(systemstring));
	systemstring = QString("mv -v %1/*mcvol.nii.gz %1/mc4D.nii.gz").arg(tmpdir);
	msgs << n->WriteLog(n->SystemCommand(systemstring));

	/* create thumbnails (try 4 different ways before giving up) */
	QString thumbfile = s.seriespath + "/thumb.png";
	if (!QFile::exists(thumbfile)) {
		msgs << n->WriteLog(thumbfile + " does not exist, attempting to create it (method 1)");
		systemstring = "slicer " + filepath4d + " -a " + thumbfile;
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	if (!QFile::exists(thumbfile)) {
		msgs << n->WriteLog(thumbfile + " does not exist, attempting to create it (method 2)");
		systemstring = "slicer " + filepath4d + " -a " + thumbfile;
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	if (!QFile::exists(thumbfile)) {
		msgs << n->WriteLog(thumbfile + " does not exist, attempting to create it (method 3)");
		systemstring = "slicer " + s.datapath + "/*.nii.gz -a " + thumbfile;
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	if (!QFile::exists(thumbfile)) {
		msgs << n->WriteLog(thumbfile + " does not exist, attempting to create it (method 4)");
		systemstring = "slicer " + s.datapath + "/*.nii -a " + thumbfile;
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}

	/* get image dimensions */
	int dimN(0), dimX(0), dimY(0), dimZ(0), dimT(0);
	if (filepath4d != "") {
		dimN = n->SystemCommand("fslval " + filepath4d + " dim0", false).toInt();
		dimX = n->SystemCommand("fslval " + filepath4d + " dim1", false).toInt();
		dimY = n->SystemCommand("fslval " + filepath4d + " dim2", false).toInt();
		dimZ = n->SystemCommand("fslval " + filepath4d + " dim3", false).toInt();
		dimT = n->SystemCommand("fslval " + filepath4d + " dim4", false).toInt();
	}

	/* get min/max intensity in the mean/variance/stdev volumes and create thumbnails of the mean, sigma, and varaiance images */
	if (QFile::exists(qapath + "/Tmean.nii.gz")) {
		systemstring = QString("fslstats %1/Tmean.nii.gz -R > %1/minMaxMean.txt").arg(qapath);
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "slicer " + qapath + "/Tmean.nii.gz -a " + qapath + "/Tmean.png";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	else
		msgs << n->WriteLog(qapath + "/Tmean.nii.gz does not exist");

	if (QFile::exists(qapath + "/Tsigma.nii.gz")) {
		systemstring = QString("fslstats %1/Tsigma.nii.gz -R > %1/minMaxSigma.txt").arg(qapath);
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "slicer " + qapath + "/Tsigma.nii.gz -a " + qapath + "/Tsigma.png";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	else
		msgs << n->WriteLog(qapath + "/Tsigma.nii.gz does not exist");

	if (QFile::exists(qapath + "/Tvariance.nii.gz")) {
		systemstring = QString("fslstats %1/Tvariance.nii.gz -R > %1/minMaxVariance.txt").arg(qapath);
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "slicer " + qapath + "/Tvariance.nii.gz -a " + qapath + "/Tvariance.png";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	else
		msgs << n->WriteLog(qapath + "/Tvariance.nii.gz does not exist");

	if (QFile::exists(tmpdir + "/mc4D.nii.gz")) {
		/* get mean/stdev in intensity over time */
		systemstring = "fslstats -t " + tmpdir + "/mc4D -m > " + qapath + "/meanIntensityOverTime.txt";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "fslstats -t " + tmpdir + "/mc4D -s > " + qapath + "/stdevIntensityOverTime.txt";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "fslstats -t " + tmpdir + "/mc4D -e > " + qapath + "/entropyOverTime.txt";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "fslstats -t " + tmpdir + "/mc4D -c > " + qapath + "/centerOfGravityOverTimeMM.txt";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "fslstats -t " + tmpdir + "/mc4D -C > " + qapath + "/centerOfGravityOverTimeVox.txt";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
		systemstring = "fslstats -t " + tmpdir + "/mc4D -h 100 > " + qapath + "/histogramOverTime.txt";
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	else
		msgs << n->WriteLog(tmpdir + "/mc4D.nii.gz does not exist");

	/* parse the QA output file */
	double pvsnr(0.0), iosnr(0.0);
	QString m2;
	GetQAStats(qapath + "/qa.txt", pvsnr, iosnr, m2);
	msgs << m2;

	/* parse the movement correction file */
	double maxrx(0.0), maxry(0.0), maxrz(0.0), maxtx(0.0), maxty(0.0), maxtz(0.0), maxax(0.0), maxay(0.0), maxaz(0.0), minrx(0.0), minry(0.0), minrz(0.0), mintx(0.0), minty(0.0), mintz(0.0), minax(0.0), minay(0.0), minaz(0.0);
	QString m3;
	GetMovementStats(qapath + "/MotionCorrection.txt", maxrx, maxry, maxrz, maxtx, maxty, maxtz, maxax, maxay, maxaz, minrx, minry, minrz, mintx, minty, mintz, minax, minay, minaz, m3);
	msgs << m3;

	/* if there is no still thumbnail, create one, or replace the original */
	if (!QFile::exists(thumbfile)) {
		msgs << n->WriteLog(thumbfile + " still does not exist, attempting to create it from Tmean.png");
		systemstring = "cp -v " + qapath + "/Tmean.png " + thumbfile;
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}
	/* and if there is yet still no thumbnail, generate one the old fashioned way, with convert */
	if (!QFile::exists(thumbfile)) {
		msgs << n->WriteLog(thumbfile + " still does not exist, attempting to create it using ImageMagick");
		/* print the ImageMagick version */
		n->SystemCommand("which convert");
		n->SystemCommand("convert --version");

		/* get the middle slice from the dicom files */
		QStringList dcms = n->FindAllFiles(s.datapath, "*.dcm");
		QString dcmfile = dcms[int(dcms.size()/2)];
		systemstring = "convert -normalize " + dcmfile + " " + thumbfile;
		msgs << n->WriteLog(n->SystemCommand(systemstring));
	}

	/* run the motion detection program (for 3D volumes only) */
	double motion_rsq(0.0);
	if (dimT == 1) {
		systemstring = "python " + n->cfg["scriptdir"] + "/StructuralMRIQA.py " + s.seriespath;
		msgs << n->WriteLog("Running structural motion calculation");
		QString rsq = n->SystemCommand(systemstring, false);
		if (rsq == "")
			motion_rsq = 0.0;
	}

	/* delete the 4D file and temp directory */
	if (!n->RemoveDir(tmpdir, m))
		msgs << n->WriteLog("Unable to remove directory ["+tmpdir+"] because of error ["+m+"]");

	/* insert this row into the DB */
	q.prepare("update mr_qa set mrseries_id = :seriesid, io_snr = :iosnr, pv_snr = :pvsnr, move_minx = :mintx, move_miny = :minty, move_minz = :mintz, move_maxx = :maxtx, move_maxy = :maxty, move_maxz = :maxtz, acc_minx = :minax, acc_miny = :minay, acc_minz = :minaz, acc_maxx = :maxax, acc_maxy = :maxay, acc_maxz = :maxaz, rot_minp = :minrx, rot_minr = :minry, rot_miny = :minrz, rot_maxp = :maxrx, rot_maxr = :maxry, rot_maxy = :maxrz, motion_rsq = :motion_rsq, cputime = 0.0 where mrqa_id = :mrqaid");
	q.bindValue(":seriesid",seriesid);
	q.bindValue(":iosnr",iosnr);
	q.bindValue(":pvsnr",pvsnr);
	q.bindValue(":maxrx",maxrx);
	q.bindValue(":maxry",maxry);
	q.bindValue(":maxrz",maxrz);
	q.bindValue(":maxtx",maxtx);
	q.bindValue(":maxty",maxty);
	q.bindValue(":maxtz",maxtz);
	q.bindValue(":maxax",maxax);
	q.bindValue(":maxay",maxay);
	q.bindValue(":maxaz",maxaz);
	q.bindValue(":minrx",minrx);
	q.bindValue(":minry",minry);
	q.bindValue(":minrz",minrz);
	q.bindValue(":mintx",mintx);
	q.bindValue(":minty",minty);
	q.bindValue(":mintz",mintz);
	q.bindValue(":minax",minax);
	q.bindValue(":minay",minay);
	q.bindValue(":minaz",minaz);
	q.bindValue(":motion_rsq",motion_rsq);
	q.bindValue(":mrqaid",mrqaid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__,true);

	/* update the mr_series table with the image dimensions */
	q.prepare("update mr_series set dimN = :n, dimX = :x, dimY = :y, dimZ = :z, dimT = :t, bold_reps = :t where mrseries_id = :seriesid");
	q.bindValue(":seriesid",seriesid);
	q.bindValue(":n", dimN);
	q.bindValue(":x", dimX);
	q.bindValue(":y", dimY);
	q.bindValue(":z", dimZ);
	q.bindValue(":t", dimT);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);

	msgs << n->WriteLog("======================== Finished [" + indir + "] ========================");

	WriteQALog(qapath, msgs.join("\n"));

	return true;
}


/* ---------------------------------------------------------- */
/* --------- GetQAStats ------------------------------------- */
/* ---------------------------------------------------------- */
bool moduleMRIQA::GetQAStats(QString f, double &pvsnr, double &iosnr, QString &msg) {

	QStringList msgs;

	if (!QFile::exists(f)) {
		msgs << n->WriteLog("SNR file [" + f + "] does not exist");
		msg = msgs.join("\n");
		return false;
	}
	else
		msgs << n->WriteLog("Opening SNR file [" + f + "]");

	pvsnr = 0.0;
	iosnr = 0.0;

	QFile df(f);
	if (df.open(QIODevice::ReadOnly | QIODevice::Text)) {

		QTextStream in(&df);
		while (!in.atEnd()) {
			QString line = in.readLine().trimmed();
			QStringList parts = line.split(QRegExp("\\t"));
			bool ok1(false), ok2(false);
			if (parts.size() > 1)
				pvsnr = parts[1].toDouble(&ok1);
			if (parts.size() > 2)
				iosnr = parts[2].toDouble(&ok2);

			if (ok1 || ok2) break;
		}
		df.close();
	}

	msg = msgs.join("\n");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- GetMovementStats ------------------------------- */
/* ---------------------------------------------------------- */
bool moduleMRIQA::GetMovementStats(QString f, double &maxrx, double &maxry, double &maxrz, double &maxtx, double &maxty, double &maxtz, double &maxax, double &maxay, double &maxaz, double &minrx, double &minry, double &minrz, double &mintx, double &minty, double &mintz, double &minax, double &minay, double &minaz, QString &msg) {

	QStringList msgs;

	if (!QFile::exists(f)) {
		msgs << n->WriteLog("Realignment file [" + f + "] does not exist");
		msg = msgs.join("\n");
		return false;
	}
	else
		msgs << n->WriteLog("Opening realignment file [" + f + "]");

	QVector<double> rotx, roty, rotz, trax, tray, traz;

	QFile df(f);
	if (df.open(QIODevice::ReadOnly | QIODevice::Text)) {
		/* rearrange the text file columns into arrays to pass to the max/min functions */
		QTextStream in(&df);
		while (!in.atEnd()) {
			QString line = in.readLine().trimmed();
			//msgs << n->WriteLog(line);
			QStringList p = line.split(QRegExp("\\s+"));
			rotx.append(p[0].toDouble());
			roty.append(p[1].toDouble());
			rotz.append(p[2].toDouble());
			trax.append(p[3].toDouble());
			tray.append(p[4].toDouble());
			traz.append(p[5].toDouble());
		}
		df.close();
	}

	/* min and max for the rotation and translation */
	GetMinMax(rotx, minrx, maxrx);
	GetMinMax(roty, minry, maxry);
	GetMinMax(rotz, minrz, maxrz);
	GetMinMax(trax, mintx, maxtx);
	GetMinMax(tray, minty, maxty);
	GetMinMax(traz, mintz, maxtz);

	/* get the speed (acc) from the translation */
	QVector<double> accx, accy, accz;
	accx = Derivative(trax);
	accy = Derivative(tray);
	accz = Derivative(traz);

	GetMinMax(accx, minax, maxax);
	GetMinMax(accy, minay, maxay);
	GetMinMax(accz, minaz, maxaz);

	QString accfile = f;
	accfile.replace("MotionCorrection","MotionCorrection2");

	QFile af(accfile);
	if (af.open(QIODevice::WriteOnly | QIODevice::Text)) {
		QTextStream fs(&af);
		for (int i=0; i<accx.size();i++)
			fs << QString("%1").arg(accx[i]);
		fs << endl;

		for (int i=0; i<accy.size();i++)
			fs << QString("%1").arg(accy[i]);
		fs << endl;

		for (int i=0; i<accz.size();i++)
			fs << QString("%1").arg(accz[i]);
		fs << endl;

		af.close();
	}
	else {
		msg = msgs.join("\n");
		return false;
	}

	msg = msgs.join("\n");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- GetMinMax -------------------------------------- */
/* ---------------------------------------------------------- */
void moduleMRIQA::GetMinMax(QVector<double> a, double &min, double &max) {
	min = *std::min_element(a.begin(), a.end());
	max = *std::max_element(a.begin(), a.end());
	n->WriteLog(QString("Found min [%1] and max [%2]").arg(min).arg(max));
}


/* ---------------------------------------------------------- */
/* --------- Derivative ------------------------------------- */
/* ---------------------------------------------------------- */
QVector<double> moduleMRIQA::Derivative(QVector<double> a) {
	QVector<double> r;

	for (int i=0; i<a.size();i++)
		r.append(a[i]-a[i-1]);

	return r;
}


/* ---------------------------------------------------------- */
/* --------- WriteQALog ------------------------------------- */
/* ---------------------------------------------------------- */
void moduleMRIQA::WriteQALog(QString dir, QString log) {

	QFile f(dir + "/QALog.txt");
	if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
		QTextStream fs(&f);
		fs << log;
		f.close();
	}
}