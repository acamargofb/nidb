/* ------------------------------------------------------------------------------
  NIDB modulePipeline.h
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

#ifndef MODULEPIPELINE_H
#define MODULEPIPELINE_H
#include "nidb.h"
#include "study.h"
#include "series.h"
#include "analysis.h"
#include "pipeline.h"

/* data structures used in this class */
struct pipelineStep {
	int id;
	QString command;
	bool supplement;
	QString workingDir;
	int order;
	QString description; /* comment */
	bool logged;
	bool enabled;
};

struct dataDefinitionStep {
	int id;
	int order;
	QString type;
	QString criteria;
	QString assoctype;
	QString protocol;
	QString modality;
	QString dataformat;
	QString imagetype;
	bool gzip;
	QString location;
	bool useseries;
	bool preserveseries;
	bool usephasedir;
	QString behformat;
	QString behdir;
	bool enabled;
	bool optional;
	QString numboldreps; /* this is stored as a comparison */
	QString level;
};


class modulePipeline
{
public:
	modulePipeline();
	modulePipeline(nidb *n);
	~modulePipeline();

	int Run();

	int IsQueueFilled(int pid);
	QStringList GetGroupList(int pid);
	QList<int> GetPipelineList();
	QString CheckDependency(int sid, int pipelinedep);
	bool IsPipelineEnabled(int pid);
	void SetPipelineStopped(int pid);
	void SetPipelineDisabled(int pid);
	void SetPipelineRunning(int pid);
	void SetPipelineStatusMessage(int pid, QString msg);
	void SetPipelineProcessStatus(QString status, int pipelineid, int studyid);
	QStringList GetUIDStudyNumListByGroup(QString group);
	QList<pipelineStep> GetPipelineSteps(int pipelineid, int version);
	QList<dataDefinitionStep> GetPipelineDataDef(int pipelineid, int version);
	QString FormatCommand(int pipelineid, QString clusteranalysispath, QString command, QString analysispath, int analysisid, QString uid, int studynum, QString studydatetime, QString pipelinename, QString workingdir, QString description);
	bool CreateClusterJobFile(QString jobfilename, QString clustertype, int analysisid, bool isgroup, QString uid, int studynum, QString analysispath, bool usetmpdir, QString tmpdir, QString studydatetime, QString pipelinename, int pipelineid, QString resultscript, int maxwalltime,  QList<pipelineStep> steps, bool runsupplement = false, bool pipelineuseprofile = false, bool removedata = false);
	QList<int> GetStudyToDoList(int pipelineid, QString modality, QString depend, QString groupids);
	bool GetData(int studyid, QString analysispath, QString uid, int analysisid, int pipelineversion, int pipelineid, int pipelinedep, QString deplevel, QList<dataDefinitionStep> datadef, int &numdownloaded, QString &datalog, QString &datatable);
	QString GetBehPath(QString behformat, QString analysispath, QString location, QString behdir, int newseriesnum);

private:
	nidb *n;

};

#endif // MODULEPIPELINE_H
