<?php

namespace App\Exporters;

use App\Exporters\AbstractExporter as ExportersAbstractExporter;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\DomPDF\PDF as DomPDFPDF;

class PdfExporter extends ExportersAbstractExporter
{
    /**
     * Export data to PDF format.
     */
    public function export()
    {
        // Generate PDF content
        $pdfContent = DomPDFPDF::loadView('pdf.invoice', ['data' => $this->getData()]);

        // Download the PDF
        return $pdfContent->download('exported_file.pdf');
    }
}
