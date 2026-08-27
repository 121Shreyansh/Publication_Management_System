<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
http_response_code(410);

echo json_encode([
    'ok' => false,
    'message' => 'This endpoint has been retired. Use marksheet_ocr_upload.php and marksheet_ocr_confirm.php for the OCR review flow.',
]);
