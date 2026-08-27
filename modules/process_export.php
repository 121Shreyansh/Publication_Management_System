<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;

if (!isset($_POST['execute_export'])) { header("Location: ../dashboard.php?page=export"); exit; }

$user_id  = $_SESSION['user_id'];
$pub_ids  = $_POST['pub_ids']  ?? [];
$fields   = $_POST['fields']   ?? ['title','authors','venue','pub_date','pub_type'];
$formats  = $_POST['formats']  ?? ['excel'];

if (empty($pub_ids) || empty($formats)) {
    header("Location: ../dashboard.php?page=export&err=nosel"); exit;
}

// ── Fetch data ──
$placeholders = implode(',', array_fill(0, count($pub_ids), '?'));
$stmt = $pdo->prepare("
    SELECT pa.publication_id, pa.publication_type, pa.verification_flag,
           pa.publication_status,
           j.journal_publication_title as j_t,   j.journal_publication_date as j_date,
           j.journal_title   as j_v,   j.publication_volume_no as j_vol,
           j.publication_issue_no as j_iss, j.journal_publisher     as j_pub,
           j.published_city as j_city, j.published_country    as j_cntry,
           j.publication_doi as j_doi, j.first_page_no as j_fp, j.last_page_no as j_lp,
           c.conference_publication_title as c_t,
           c.conference_start_date        as c_date,
           c.conference_title             as c_v,
           c.conference_publisher         as c_pub,
           c.conference_city              as c_city,
           c.conference_country           as c_cntry,
           c.publication_doi              as c_doi
    FROM publication_author pa
    LEFT JOIN journal_publication_details    j ON pa.publication_id = j.journal_publication_id
    LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
    WHERE pa.publication_id IN ($placeholders) AND pa.author_id = ?
    ORDER BY pa.publication_id DESC
");
$stmt->execute([...$pub_ids, $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch author names (employee + outside)
$stmt2 = $pdo->prepare("
    SELECT car.publication_id,
           CONCAT(e.first_name,' ',COALESCE(e.last_name,'')) as coauthor_name
    FROM co_author_requests car
    JOIN employee_info e ON car.requested_id = e.emp_id
    WHERE car.publication_id IN ($placeholders) AND car.status = 'Accepted'
");
$stmt2->execute($pub_ids);
$coAuthMap = [];
foreach ($stmt2->fetchAll() as $ca) {
    $coAuthMap[$ca['publication_id']][] = $ca['coauthor_name'];
}

$stmt3 = $pdo->prepare("SELECT CONCAT(first_name,' ',COALESCE(last_name,'')) as name FROM employee_info WHERE emp_id=?");
$stmt3->execute([$user_id]);
$mainName = trim($stmt3->fetchColumn() ?: $user_id);

// ── Build structured rows ──
$fieldLabels = [
    'title'     => 'Title',
    'authors'   => 'Authors',
    'venue'     => 'Journal / Conference',
    'pub_date'  => 'Date',
    'pub_type'  => 'Type',
    'doi'       => 'DOI',
    'publisher' => 'Publisher',
    'vol_issue' => 'Volume / Issue',
    'pages'     => 'Pages',
    'location'  => 'City / Country',
    'status'    => 'Status',
];
$headers = array_intersect_key($fieldLabels, array_flip($fields));

function buildRow($row, $fields, $mainName, $coAuthMap) {
    $pid      = $row['publication_id'];
    $title    = $row['j_t']   ?? $row['c_t']    ?? '';
    $venue    = $row['j_v']   ?? $row['c_v']    ?? '';
    $date     = $row['j_date']?? $row['c_date'] ?? null;
    $doi      = $row['j_doi'] ?? $row['c_doi']  ?? '';
    $pub      = $row['j_pub'] ?? $row['c_pub']  ?? '';
    $city     = $row['j_city']?? $row['c_city'] ?? '';
    $country  = $row['j_cntry']??$row['c_cntry']?? '';
    $vol      = $row['j_vol'] ?? '';
    $iss      = $row['j_iss'] ?? '';
    $fp       = $row['j_fp']  ?? '';
    $lp       = $row['j_lp']  ?? '';

    $coNames  = $coAuthMap[$pid] ?? [];
    $allAuthors = array_merge([$mainName], $coNames);

    $out = [];
    foreach ($fields as $f) {
        switch($f) {
            case 'title':     $out[] = $title; break;
            case 'authors':   $out[] = implode('; ', $allAuthors); break;
            case 'venue':     $out[] = $venue; break;
            case 'pub_date':  $out[] = $date ? date('d M Y', strtotime($date)) : ''; break;
            case 'pub_type':  $out[] = $row['publication_type']; break;
            case 'doi':       $out[] = $doi; break;
            case 'publisher': $out[] = $pub; break;
            case 'vol_issue': $out[] = $vol && $iss ? "Vol.$vol($iss)" : ($vol ?: ''); break;
            case 'pages':     $out[] = $fp && $lp ? "$fp–$lp" : ($fp ?: ''); break;
            case 'location':  $out[] = trim("$city, $country", ', '); break;
            case 'status':    $out[] = $row['verification_flag'] ? 'Verified' : 'Pending'; break;
            default:          $out[] = '';
        }
    }
    return $out;
}

$dataRows = array_map(fn($r) => buildRow($r, $fields, $mainName, $coAuthMap), $rows);
$headerRow = array_values($headers);

// ── If multiple formats → ZIP ──
if (count($formats) > 1) {
    $zip = new ZipArchive();
    $zipFile = sys_get_temp_dir() . '/export_' . time() . '.zip';
    $zip->open($zipFile, ZipArchive::CREATE);

    foreach ($formats as $fmt) {
        [$content, $filename] = generateFormat($fmt, $headerRow, $dataRows, $mainName, $rows, $fields, $coAuthMap);
        $zip->addFromString($filename, $content);
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="publications_export.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit;
}

// ── Single format — stream directly ──
[$content, $filename, $mime] = generateFormatWithMime($formats[0], $headerRow, $dataRows, $mainName, $rows, $fields, $coAuthMap);
header("Content-Type: $mime");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo $content;
exit;

// ═══════════════════════════════════════════════
function generateFormat($fmt, $headers, $dataRows, $mainName, $rows, $fields, $coAuthMap) {
    [$c, $f, $m] = generateFormatWithMime($fmt, $headers, $dataRows, $mainName, $rows, $fields, $coAuthMap);
    return [$c, $f];
}

function generateFormatWithMime($fmt, $headers, $dataRows, $mainName, $rows, $fields, $coAuthMap) {
    switch ($fmt) {
        case 'excel': return generateExcel($headers, $dataRows);
        case 'csv':   return generateCSV($headers, $dataRows);
        case 'pdf':   return generatePDF($headers, $dataRows, $mainName);
        case 'word':  return generateWord($headers, $dataRows, $mainName);
        default:      return generateCSV($headers, $dataRows);
    }
}

// ── EXCEL ──
function generateExcel($headers, $dataRows) {
    $ss = new Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('Publications');

    // Header row styling
    $sheet->fromArray([$headers], null, 'A1');
    $colCount = count($headers);
    $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // Data rows
    foreach ($dataRows as $i => $row) {
        $sheet->fromArray([$row], null, 'A' . ($i + 2));
        if ($i % 2 === 1) {
            $sheet->getStyle("A".($i+2).":{$lastCol}".($i+2))
                  ->getFill()->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('F0FDFA');
        }
    }

    // Auto width
    for ($c = 1; $c <= $colCount; $c++) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->freezePane('A2');

    ob_start();
    (new Xlsx($ss))->save('php://output');
    return [ob_get_clean(), 'publications.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
}

// ── CSV ──
function generateCSV($headers, $dataRows) {
    ob_start();
    $f = fopen('php://output', 'w');
    fputcsv($f, $headers);
    foreach ($dataRows as $row) fputcsv($f, $row);
    fclose($f);
    return [ob_get_clean(), 'publications.csv', 'text/csv'];
}

// ── PDF ──
function generatePDF($headers, $dataRows, $mainName) {
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 20px; }
        .title { text-align:center; color:#0f766e; font-size:18px; font-weight:bold; margin-bottom:4px; }
        .sub   { text-align:center; color:#64748b; font-size:10px; margin-bottom:20px; }
        table  { width:100%; border-collapse:collapse; }
        thead tr { background:#0f766e; color:white; }
        th { padding:8px 10px; text-align:left; font-size:10px; }
        td { padding:7px 10px; border-bottom:1px solid #e2e8f0; vertical-align:top; font-size:10px; }
        tr:nth-child(even) td { background:#f0fdfa; }
    </style></head><body>
    <div class="title">Research Publication Report</div>
    <div class="sub">Author: '.htmlspecialchars($mainName).' &nbsp;·&nbsp; Generated: '.date('d M Y').'</div>
    <table><thead><tr>';
    foreach ($headers as $h) $html .= '<th>'.htmlspecialchars($h).'</th>';
    $html .= '</tr></thead><tbody>';
    foreach ($dataRows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) $html .= '<td>'.htmlspecialchars($cell).'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></body></html>';

    $opts = new Options();
    $opts->set('defaultFont', 'DejaVu Sans');
    $opts->set('isHtml5ParserEnabled', true);
    $opts->set('isRemoteEnabled', false);
    $dompdf = new Dompdf($opts);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();
    return [$dompdf->output(), 'publications.pdf', 'application/pdf'];
}

// ── WORD ──
function generateWord($headers, $dataRows, $mainName) {
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Calibri');
    $phpWord->setDefaultFontSize(10);

    $section = $phpWord->addSection(['marginTop' => 800, 'marginBottom' => 800, 'marginLeft' => 900, 'marginRight' => 900]);

    $section->addText('Research Publication Report', ['bold'=>true,'size'=>16,'color'=>'0F766E'], ['alignment'=>'center']);
    $section->addText('Author: '.$mainName.' · Generated: '.date('d M Y'), ['size'=>9,'color'=>'64748b'], ['alignment'=>'center']);
    $section->addTextBreak(1);

    $colCount  = count($headers);
    $tableStyle = ['borderSize'=>6,'borderColor'=>'e2e8f0','cellMargin'=>80,'unit'=>TblWidth::PERCENT,'width'=>5000];
    $table = $section->addTable($tableStyle);

    // Header row
    $table->addRow(400);
    foreach ($headers as $h) {
        $cell = $table->addCell(null, ['bgColor'=>'0F766E']);
        $cell->addText(htmlspecialchars($h), ['bold'=>true,'color'=>'FFFFFF','size'=>9]);
    }

    // Data rows
    foreach ($dataRows as $i => $row) {
        $table->addRow();
        $bg = $i % 2 === 1 ? ['bgColor'=>'F0FDFA'] : [];
        foreach ($row as $cell) {
            $table->addCell(null, $bg)->addText(htmlspecialchars($cell), ['size'=>9]);
        }
    }

    ob_start();
    (new \PhpOffice\PhpWord\Writer\Word2007($phpWord))->save('php://output');
    return [ob_get_clean(), 'publications.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
}