#ifndef MODULEFILEIO_H
#define MODULEFILEIO_H
#include "nidb.h"
#include "analysis.h"

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

private:
	nidb *n;
};

#endif // MODULEFILEIO_H