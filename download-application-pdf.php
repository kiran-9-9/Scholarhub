<?php
session_start();
require_once 'includes/Auth.php';
require_once 'tcpdf/tcpdf.php'; // Make sure TCPDF is in your project directory

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get application ID from request
$applicationId = isset($_GET['id']) ? $_GET['id'] : null;
if (!$applicationId) {
    die("Application ID is required");
}

// Get user data
$user = $auth->getUserData();
if (!$user) {
    header("Location: logout.php");
    exit();
}

// Extend TCPDF to create custom header and footer
class MYPDF extends TCPDF {
    public function Header() {
        // Logo
        $this->Image('images/logo.png', 15, 10, 30);
        // Set font
        $this->SetFont('helvetica', 'B', 20);
        // Title
        $this->Cell(0, 15, 'ScholarHub Application', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ScholarHub');
$pdf->SetAuthor('ScholarHub System');
$pdf->SetTitle('Application - ' . $applicationId);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Application Information
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Application Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);

$pdf->Cell(60, 8, 'Application ID:', 0, 0);
$pdf->Cell(0, 8, $applicationId, 0, 1);
$pdf->Cell(60, 8, 'Submission Date:', 0, 0);
$pdf->Cell(0, 8, date('F d, Y'), 0, 1);
$pdf->Cell(60, 8, 'Status:', 0, 0);
$pdf->Cell(0, 8, 'Pending Review', 0, 1);
$pdf->Ln(5);

// Scholarship Information
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Scholarship Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);

$pdf->Cell(60, 8, 'Scholarship Name:', 0, 0);
$pdf->Cell(0, 8, 'Merit Scholarship 2024', 0, 1);
$pdf->Cell(60, 8, 'Amount:', 0, 0);
$pdf->Cell(0, 8, '₹50,000', 0, 1);
$pdf->Cell(60, 8, 'Applied Date:', 0, 0);
$pdf->Cell(0, 8, 'January 15, 2024', 0, 1);
$pdf->Ln(5);

// Applicant Information
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Applicant Information', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);

$pdf->Cell(60, 8, 'Full Name:', 0, 0);
$pdf->Cell(0, 8, $user['full_name'], 0, 1);
$pdf->Cell(60, 8, 'Email:', 0, 0);
$pdf->Cell(0, 8, $user['email'], 0, 1);
$pdf->Cell(60, 8, 'Phone:', 0, 0);
$pdf->Cell(0, 8, $user['phone'] ?? 'Not provided', 0, 1);
$pdf->Cell(60, 8, 'Address:', 0, 0);
$pdf->Cell(0, 8, $user['address'] ?? 'Not provided', 0, 1);
$pdf->Ln(5);

// Submitted Documents
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Submitted Documents', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);

$documents = [
    'Academic Transcripts',
    'Income Certificate',
    'Recommendation Letter'
];

foreach ($documents as $doc) {
    $pdf->Cell(10, 8, '•', 0, 0);
    $pdf->Cell(0, 8, $doc, 0, 1);
}
$pdf->Ln(5);

// Application Timeline
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Application Timeline', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);

$timeline = [
    ['date' => 'Jan 15, 2024 - 10:30 AM', 'event' => 'Application Submitted'],
    ['date' => 'Jan 16, 2024 - 2:15 PM', 'event' => 'Document Verification'],
    ['date' => 'Current Status', 'event' => 'Under Review']
];

foreach ($timeline as $item) {
    $pdf->Cell(60, 8, $item['date'] . ':', 0, 0);
    $pdf->Cell(0, 8, $item['event'], 0, 1);
}
$pdf->Ln(5);

// Terms and Conditions
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Terms and Conditions', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(2);

$terms = 'By submitting this application, I declare that all information provided is true and accurate to the best of my knowledge. I understand that any false statements may result in the rejection of my application or withdrawal of the scholarship if already awarded.';
$pdf->MultiCell(0, 8, $terms, 0, 'L');
$pdf->Ln(15);

// Signature
$pdf->Cell(0, 8, 'Signature: _____________________', 0, 1, 'R');
$pdf->Cell(0, 8, 'Date: ' . date('F d, Y'), 0, 1, 'R');

// Close and output PDF document
$pdf->Output('ScholarHub_Application_' . $applicationId . '.pdf', 'D');
?> 