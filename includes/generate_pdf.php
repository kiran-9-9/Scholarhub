<?php
$project_root = dirname(__DIR__);
require_once $project_root . '/tcpdf/tcpdf.php';
require_once $project_root . '/config/database.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

session_start();

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Check if application ID is provided
if (!isset($_GET['id'])) {
    die('Application ID not provided');
}

$applicationId = $_GET['id'];

// Get application details from database
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT a.*, s.scholarship_name, u.full_name, u.email 
                       FROM applications a 
                       JOIN scholarships s ON a.scholarship_id = s.id 
                       JOIN users u ON a.user_id = u.id 
                       WHERE a.id = :id");
$stmt->execute(['id' => $applicationId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die('Application not found');
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ScholarHub');
$pdf->SetAuthor('ScholarHub System');
$pdf->SetTitle('Application Details - ' . $result['scholarship_name']);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Add logo
$logoPath = $project_root . '/images/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 70);
}

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add content
$pdf->Cell(0, 20, '', 0, 1); // Space after logo
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Application Details', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(10);

// Application information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(60, 7, 'Scholarship:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, $result['scholarship_name'], 0, 1);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(60, 7, 'Applicant Name:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, $result['full_name'], 0, 1);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(60, 7, 'Email:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, $result['email'], 0, 1);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(60, 7, 'Application Date:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, date('F j, Y', strtotime($result['application_date'])), 0, 1);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(60, 7, 'Status:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, ucfirst($result['status']), 0, 1);

if (isset($result['additional_info']) && !empty($result['additional_info'])) {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Additional Information:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->MultiCell(0, 7, $result['additional_info'], 0, 'L');
}

// Output PDF
$pdf->Output('Application_' . $applicationId . '.pdf', 'D');
?> 