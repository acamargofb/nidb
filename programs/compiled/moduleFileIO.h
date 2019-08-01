/* ------------------------------------------------------------------------------
  NIDB moduleFileIO.h
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

#ifndef MODULEFILEIO_H
#define MODULEFILEIO_H
#include "nidb.h"
#include "analysis.h"
#include "subject.h"
#include "study.h"
#include "series.h"

class moduleFileIO
{
public:
	moduleFileIO(nidb *n);
    ~moduleFileIO();
	int Run();
	bool RecheckSuccess(int analysisid, QString &msg);
	bool CreateLinks(int analysisid, QString destination, QString &msg);
	QString GetAnalyisRootPath(int analysisid, QString &msg);
	bool CopyAnalysis(int analysisid, QString destination, QString &msg);
	bool DeleteAnalysis(int analysisid, QString &msg);
	bool DeletePipeline(int pipelineid, QString &msg);
	bool DeleteSubject(int subjectid, QString username, QString &msg);
	bool DeleteStudy(int subjectid, QString &msg);
	bool DeleteSeries(int seriesid, QString modality, QString &msg);
	bool RearchiveStudy(int studyid, bool matchidonly, QString &msg);
	bool RearchiveSubject(int studyid, bool matchidonly, int projectid, QString &msg);
	bool MoveStudyToSubject(int studyid, QString newuid, QString &msg);
	QString GetIORequestStatus(int requestid);
	bool SetIORequestStatus(int requestid, QString status, QString msg = "");

private:
	nidb *n;
};

#endif // MODULEFILEIO_H
