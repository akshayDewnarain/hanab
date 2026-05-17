<?php

namespace Domain\Support\Pdf;

use App\Pdf\BrowsershotConfig;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Spatie\Browsershot\Browsershot;

/**
 * Factory for generating invoice and receipt PDFs using Browsershot.
 * Configuration matches {@see BrowsershotConfig} (remote vs local Chrome args).
 */
class InvoicePdfFactory
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly BrowsershotConfig $browsershotConfig,
    ) {}

    /**
     * Generate PDF bytes from invoice view data.
     *
     * @param  array  $viewData  Data to pass to pdf.invoice view
     * @return string PDF bytes
     */
    public function make(array $viewData): string
    {
        $html = $this->view->make('pdf.invoice', $viewData)->render();

        $shot = Browsershot::html($html);
        ($this->browsershotConfig->closure())($shot);

        return $shot->format('A4')->pdf();
    }

    /**
     * Generate PDF bytes from receipt view data.
     *
     * @param  array  $viewData  Data to pass to pdf.order-receipt view
     * @return string PDF bytes
     */
    public function makeReceipt(array $viewData): string
    {
        $html = $this->view->make('pdf.order-receipt', $viewData)->render();

        $shot = Browsershot::html($html);
        ($this->browsershotConfig->closure())($shot);

        return $shot->format('A4')->pdf();
    }
}
