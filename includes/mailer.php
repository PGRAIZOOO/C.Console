<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../fpdf/fpdf.php';

function order_receipt(int $orderId): ?array
{
    $order = db_row(
        'SELECT o.order_id, o.order_date, o.date_needed, o.status, o.discount_type, o.discount_value,
                o.cash_tendered, o.total_amount, o.change_amount,
                u.full_name, u.email, u.contact_number, u.address
         FROM orders o INNER JOIN users u ON u.user_id = o.user_id
         WHERE o.order_id = ?',
        [$orderId]
    );
    if (!$order) {
        return null;
    }
    $lines = db_all(
        'SELECT game_code, game_name AS name, quantity, unit_price, line_subtotal
         FROM order_transaction_details
         WHERE order_id = ? ORDER BY game_name',
        [$orderId]
    );
    $subtotal = 0.0;
    foreach ($lines as $line) {
        $subtotal += (float) $line['line_subtotal'];
    }
    $total = (float) $order['total_amount'];
    $order['lines'] = $lines;
    $order['subtotal'] = round($subtotal, 2);
    $order['discount'] = round(max(0, $subtotal - $total), 2);
    $order['total'] = $total;
    return $order;
}

function receipt_discount_label(array $receipt): string
{
    if ($receipt['discount_type'] === 'Percentage') {
        return 'Discount (' . rtrim(rtrim(number_format((float) $receipt['discount_value'], 2), '0'), '.') . '%)';
    }
    return 'Discount';
}

function receipt_closed(array $receipt): bool
{
    return in_array($receipt['status'], ['Cancelled', 'Voided'], true);
}

function receipt_status_phrase(array $receipt): string
{
    if ($receipt['status'] === 'Pending') {
        return 'has been placed';
    }
    return 'has been ' . strtolower($receipt['status']);
}

function receipt_note(array $receipt): string
{
    if ($receipt['status'] === 'Pending') {
        return 'We will email you again once your order is confirmed. You can cancel it from My Orders while it is still pending.';
    }
    if ($receipt['status'] === 'Cancelled') {
        return 'This order was cancelled, so you will not be charged. The total shown is for reference only.';
    }
    if ($receipt['status'] === 'Voided') {
        return 'This order has been voided and is no longer valid.';
    }
    return '';
}

function receipt_email_html(array $receipt): string
{
    $rows = '';
    foreach ($receipt['lines'] as $line) {
        $rows .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #e3e3e3">' . e($line['name']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #e3e3e3;text-align:right">' . money($line['unit_price']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #e3e3e3;text-align:right">' . (int) $line['quantity'] . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #e3e3e3;text-align:right">' . money($line['line_subtotal']) . '</td>'
            . '</tr>';
    }
    if (receipt_closed($receipt)) {
        $summary = '<tr><td colspan="3" style="padding:8px;text-align:right"><strong>Order total</strong></td><td style="padding:8px;text-align:right"><strong>' . money($receipt['total']) . '</strong></td></tr>';
        $note = '<p style="margin:16px 0 0;padding:10px;background:#f3f3f3">' . e(receipt_note($receipt)) . '</p>';
    } else {
        $summary = '<tr><td colspan="3" style="padding:8px;text-align:right">Subtotal</td><td style="padding:8px;text-align:right">' . money($receipt['subtotal']) . '</td></tr>';
        if ($receipt['discount'] > 0) {
            $summary .= '<tr><td colspan="3" style="padding:8px;text-align:right">' . e(receipt_discount_label($receipt)) . '</td><td style="padding:8px;text-align:right">-' . money($receipt['discount']) . '</td></tr>';
        }
        $summary .= '<tr><td colspan="3" style="padding:8px;text-align:right"><strong>Grand total</strong></td><td style="padding:8px;text-align:right"><strong>' . money($receipt['total']) . '</strong></td></tr>';
        $note = $receipt['status'] === 'Pending'
            ? '<p style="margin:16px 0 0;padding:10px;background:#f3f3f3">' . e(receipt_note($receipt)) . '</p>'
            : '';
    }
    return '<div style="font-family:Arial,Helvetica,sans-serif;color:#222;max-width:640px">'
        . '<h2 style="margin:0 0 4px">' . e(SHOP_NAME) . '</h2>'
        . '<p style="margin:0 0 16px">Hi ' . e($receipt['full_name']) . ', your order <strong>#' . (int) $receipt['order_id'] . '</strong> ' . e(receipt_status_phrase($receipt)) . '.</p>'
        . '<p style="margin:0 0 16px;color:#555">Placed ' . e(date('F j, Y g:i A', strtotime($receipt['order_date']))) . '</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
        . '<thead><tr style="background:#f3f3f3">'
        . '<th style="padding:8px;text-align:left">Game</th><th style="padding:8px;text-align:right">Price</th>'
        . '<th style="padding:8px;text-align:right">Qty</th><th style="padding:8px;text-align:right">Subtotal</th>'
        . '</tr></thead><tbody>' . $rows . '</tbody><tfoot>' . $summary . '</tfoot></table>'
        . $note
        . '<p style="margin:16px 0 0;color:#555">Your PDF receipt is attached.</p>'
        . '</div>';
}

function receipt_email_text(array $receipt): string
{
    $text = SHOP_NAME . "\n\n"
        . 'Hi ' . $receipt['full_name'] . ', your order #' . (int) $receipt['order_id'] . ' ' . receipt_status_phrase($receipt) . ".\n\n";
    foreach ($receipt['lines'] as $line) {
        $text .= $line['name'] . ' - ' . (int) $line['quantity'] . ' x ' . money($line['unit_price']) . ' = ' . money($line['line_subtotal']) . "\n";
    }
    if (receipt_closed($receipt)) {
        return $text . "\nOrder total: " . money($receipt['total']) . "\n\n" . receipt_note($receipt) . "\n";
    }
    $text .= "\nSubtotal: " . money($receipt['subtotal']) . "\n";
    if ($receipt['discount'] > 0) {
        $text .= receipt_discount_label($receipt) . ': -' . money($receipt['discount']) . "\n";
    }
    $text .= 'Grand total: ' . money($receipt['total']) . "\n";
    if ($receipt['status'] === 'Pending') {
        $text .= "\n" . receipt_note($receipt) . "\n";
    }
    return $text;
}

function pdf_text(string $text): string
{
    $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
    return $converted === false ? '' : $converted;
}

function pdf_money($amount): string
{
    return 'PHP ' . number_format((float) $amount, 2);
}

function pdf_fit(FPDF $pdf, string $text, float $width): string
{
    $text = pdf_text($text);
    while ($text !== '' && $pdf->GetStringWidth($text) > $width - 2) {
        $text = substr($text, 0, -1);
    }
    return $text;
}

function pdf_summary_row(FPDF $pdf, array $widths, string $label, string $value, bool $strong = false): void
{
    $height = $strong ? 9 : 8;
    $pdf->SetFont('Helvetica', $strong ? 'B' : '', $strong ? 11 : 10);
    $pdf->Cell($widths[0], $height, '', 1, 0, 'C', $strong);
    $pdf->Cell($widths[1] + $widths[2], $height, pdf_text($label), 1, 0, 'C', $strong);
    $pdf->Cell($widths[3], $height, $value, 1, 1, 'C', $strong);
}

function receipt_pdf(array $receipt): string
{
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->Cell(0, 9, pdf_text(SHOP_NAME), 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, 'Transaction Receipt', 0, 1);
    $pdf->Ln(4);

    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(35, 6, 'Order No.:', 0, 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, '#' . (int) $receipt['order_id'], 0, 1);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(35, 6, 'Date:', 0, 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, date('F j, Y g:i A', strtotime($receipt['order_date'])), 0, 1);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(35, 6, 'Status:', 0, 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, pdf_text($receipt['status']), 0, 1);
    $pdf->Ln(3);

    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Customer Details:', 0, 1);
    $details = [
        'Name:' => (string) $receipt['full_name'],
        'Email:' => (string) $receipt['email'],
        'Contact No.:' => (string) ($receipt['contact_number'] ?? ''),
        'Address:' => (string) ($receipt['address'] ?? ''),
    ];
    foreach ($details as $label => $value) {
        if ($value === '') {
            continue;
        }
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(35, 6, $label, 0, 0);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6, pdf_text($value));
    }
    $pdf->Ln(5);

    $widths = [85, 30, 20, 45];
    $pdf->SetFillColor(235, 235, 235);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell($widths[0], 8, 'Game', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 8, 'Price', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 8, 'Qty', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 8, 'Subtotal', 1, 1, 'C', true);

    $pdf->SetFont('Helvetica', '', 10);
    foreach ($receipt['lines'] as $line) {
        $pdf->Cell($widths[0], 8, pdf_fit($pdf, (string) $line['name'], $widths[0]), 1, 0, 'C');
        $pdf->Cell($widths[1], 8, pdf_money($line['unit_price']), 1, 0, 'C');
        $pdf->Cell($widths[2], 8, (string) (int) $line['quantity'], 1, 0, 'C');
        $pdf->Cell($widths[3], 8, pdf_money($line['line_subtotal']), 1, 1, 'C');
    }

    if (receipt_closed($receipt)) {
        pdf_summary_row($pdf, $widths, 'Order Total:', pdf_money($receipt['total']), true);
        $pdf->Ln(5);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(0, 6, pdf_text(receipt_note($receipt)));
        return $pdf->Output('S');
    }
    pdf_summary_row($pdf, $widths, 'Subtotal:', pdf_money($receipt['subtotal']));
    if ($receipt['discount'] > 0) {
        pdf_summary_row($pdf, $widths, receipt_discount_label($receipt) . ':', '-' . pdf_money($receipt['discount']));
    }
    pdf_summary_row($pdf, $widths, 'Grand Total:', pdf_money($receipt['total']), true);
    if ((float) $receipt['cash_tendered'] > 0) {
        pdf_summary_row($pdf, $widths, 'Cash Tendered:', pdf_money($receipt['cash_tendered']));
        pdf_summary_row($pdf, $widths, 'Change:', pdf_money($receipt['change_amount']));
    }

    return $pdf->Output('S');
}

function configure_mailer(PHPMailer $mail): void
{
    if (!mail_is_configured()) {
        throw new RuntimeException('Mailtrap credentials are not configured.');
    }
    $settings = mail_settings();
    $mail->isSMTP();
    $mail->Host = $settings['host'];
    $mail->SMTPAuth = true;
    $mail->Port = $settings['port'];
    $mail->Username = $settings['username'];
    $mail->Password = $settings['password'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($settings['from_address'], $settings['from_name']);
}

function registration_email_html(array $account): string
{
    return '<div style="font-family:Arial,Helvetica,sans-serif;color:#222;max-width:640px">'
        . '<h2 style="margin:0 0 4px">' . e(SHOP_NAME) . '</h2>'
        . '<p style="margin:0 0 16px">Hi ' . e($account['full_name']) . ', your account has been created.</p>'
        . '<table style="border-collapse:collapse;font-size:14px">'
        . '<tr><td style="padding:6px 16px 6px 0;color:#555">Username</td><td style="padding:6px 0"><strong>' . e($account['username']) . '</strong></td></tr>'
        . '<tr><td style="padding:6px 16px 6px 0;color:#555">Email</td><td style="padding:6px 0"><strong>' . e($account['email']) . '</strong></td></tr>'
        . '</table>'
        . '<p style="margin:16px 0 0;color:#555">You can now sign in with your email address and order PlayStation 5 games from ' . e(SHOP_NAME) . '.</p>'
        . '</div>';
}

function registration_email_text(array $account): string
{
    return SHOP_NAME . "\n\n"
        . 'Hi ' . $account['full_name'] . ", your account has been created.\n\n"
        . 'Username: ' . $account['username'] . "\n"
        . 'Email: ' . $account['email'] . "\n\n"
        . 'You can now sign in with your email address and order PlayStation 5 games from ' . SHOP_NAME . ".\n";
}

function send_registration_email(array $account): bool
{
    if (!valid_email((string) $account['email'])) {
        throw new RuntimeException('The email address is not valid.');
    }
    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($account['email'], $account['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . SHOP_NAME . ' - your account is ready';
        $mail->Body = registration_email_html($account);
        $mail->AltBody = registration_email_text($account);
        $mail->send();
    } catch (MailException $error) {
        throw new RuntimeException('Mailer error: ' . $mail->ErrorInfo);
    }
    return true;
}

function send_order_status_email(int $orderId): bool
{
    $receipt = order_receipt($orderId);
    if (!$receipt) {
        throw new RuntimeException('The order could not be loaded for the email.');
    }
    if ($receipt['date_needed'] === null) {
        return false;
    }
    if (!valid_email((string) $receipt['email'])) {
        throw new RuntimeException('The customer does not have a valid email address.');
    }
    if (!mail_is_configured()) {
        throw new RuntimeException('Mailtrap credentials are not configured.');
    }
    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($receipt['email'], $receipt['full_name']);
        $mail->isHTML(true);
        $mail->Subject = SHOP_NAME . ' order #' . $orderId . ' ' . receipt_status_phrase($receipt);
        $mail->Body = receipt_email_html($receipt);
        $mail->AltBody = receipt_email_text($receipt);
        $mail->addStringAttachment(receipt_pdf($receipt), 'receipt-order-' . $orderId . '.pdf', 'base64', 'application/pdf');
        $mail->send();
    } catch (MailException $error) {
        throw new RuntimeException('Mailer error: ' . $mail->ErrorInfo);
    }
    return true;
}