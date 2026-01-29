<?php
/**
 * PDF Generator using mPDF
 *
 * Generates professional PDF documents for net proceeds sheets
 */

use Mpdf\Mpdf;

class NSS_PDF_Generator {

    private $sheet;
    private $sheet_data;
    private $mpdf;

    public function __construct($sheet) {
        $this->sheet = $sheet;
        $this->sheet_data = $sheet->get_data();
    }

    /**
     * Generate PDF and return file path
     *
     * @return string|false PDF file path or false on failure
     */
    public function generate() {
        try {
            $this->initialize_mpdf();
            $this->render_html();
            $pdf_path = $this->save_pdf();

            return $pdf_path;
        } catch (Exception $e) {
            error_log('NSS PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Download PDF directly
     */
    public function download() {
        try {
            $this->initialize_mpdf();
            $this->render_html();

            $filename = 'net-proceeds-' . $this->sheet_data['id'] . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');

            echo $this->mpdf->output();
            exit;
        } catch (Exception $e) {
            wp_die('Error generating PDF: ' . esc_html($e->getMessage()));
        }
    }

    /**
     * Initialize mPDF instance
     */
    private function initialize_mpdf() {
        $this->mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);

        // Set document properties
        $this->mpdf->SetTitle('Net Proceeds Sheet');
        $this->mpdf->SetAuthor(get_bloginfo('name'));
        $this->mpdf->SetCreator('Net Seller Sheet Plugin');
    }

    /**
     * Render HTML content for PDF
     */
    private function render_html() {
        ob_start();
        include NSS_PLUGIN_DIR . 'includes/pdf/templates/nss-template.php';
        $html = ob_get_clean();

        $this->mpdf->WriteHTML($html);
    }

    /**
     * Save PDF to uploads directory
     *
     * @return string File path
     */
    private function save_pdf() {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/nss-pdfs';

        // Create directory if it doesn't exist
        if (!is_dir($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $filename = 'net-proceeds-' . $this->sheet_data['id'] . '-' . time() . '.pdf';
        $filepath = $pdf_dir . '/' . $filename;

        // Save PDF file
        $this->mpdf->Output($filepath, 'F');

        return $filepath;
    }

    /**
     * Get public URL for PDF
     *
     * @param string $filepath
     * @return string Public URL
     */
    private function get_pdf_url($filepath) {
        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['basedir'], '', $filepath);

        return $upload_dir['baseurl'] . $relative_path;
    }
}
