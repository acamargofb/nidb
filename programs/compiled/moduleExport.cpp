#include "moduleExport.h"
#include <QDebug>


/* ---------------------------------------------------------- */
/* --------- moduleExport ----------------------------------- */
/* ---------------------------------------------------------- */
moduleExport::moduleExport(nidb *a)
{
	n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleFileIO ---------------------------------- */
/* ---------------------------------------------------------- */
moduleExport::~moduleExport()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleExport::Run() {
	qDebug() << "Entering the export module";

    return 1;
}
