<?php
/**
 * CodeVesper Hotel & Restaurant Management Suite
 * Thermal POS Receipt & Tax Invoice Generator (80mm / 58mm POS Printer)
 */
ob_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();

$invoiceNum = trim($_GET['inv'] ?? ($_GET['invoice_number'] ?? ''));
$invoiceId  = (int)($_GET['id'] ?? 0);
$autoPrint  = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';

// Fetch invoice details
$query = "
    SELECT inv.*, 
           g.full_name as guest_name, g.phone as guest_phone, g.email as guest_email,
           g.id_type as guest_id_type, g.id_number as guest_id_number,
           r.room_number, r.floor, rt.name as room_type,
           res.check_in_date, res.check_out_date,
           u.full_name as cashier_name, u.username as cashier_username
    FROM invoices inv
    LEFT JOIN guests g ON inv.guest_id = g.id
    LEFT JOIN rooms r ON inv.room_id = r.id
    LEFT JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN reservations res ON inv.reservation_id = res.id
    LEFT JOIN users u ON inv.generated_by = u.id
    WHERE 
";

if (!empty($invoiceNum)) {
    $stmt = $db->prepare($query . " inv.invoice_number = ? LIMIT 1");
    $stmt->execute([$invoiceNum]);
} elseif ($invoiceId > 0) {
    $stmt = $db->prepare($query . " inv.id = ? LIMIT 1");
    $stmt->execute([$invoiceId]);
} else {
    // Default to the latest invoice
    $stmt = $db->query($query . " 1=1 ORDER BY inv.id DESC LIMIT 1");
}

$invoice = $stmt->fetch();

if (!$invoice) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Invoice Not Found | <?= APP_NAME ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light p-4 text-center">
        <div class="card p-4 mx-auto shadow-sm" style="max-width: 400px;">
            <h4 class="text-danger fw-bold">Invoice Not Found</h4>
            <p class="text-muted small">No record matches the provided invoice identifier.</p>
            <a href="billing.php" class="btn btn-primary btn-sm">Return to Billing</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Fetch payment transactions for this invoice
$payStmt = $db->prepare("
    SELECT p.*, u.full_name as collector_name
    FROM payments p
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.invoice_id = ?
    ORDER BY p.id ASC
");
$payStmt->execute([$invoice['id']]);
$payments = $payStmt->fetchAll();

// Fetch dining items charged to this room (if any)
$orderItems = [];
if (!empty($invoice['room_id'])) {
    $orderStmt = $db->prepare("
        SELECT o.order_number, mi.name as item_name, oi.quantity, oi.unit_price, oi.subtotal
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE o.room_id = ? 
          AND o.created_at >= DATE_SUB(?, INTERVAL 7 DAY)
          AND o.created_at <= DATE_ADD(?, INTERVAL 1 DAY)
        ORDER BY o.id ASC, oi.id ASC
    ");
    $orderStmt->execute([$invoice['room_id'], $invoice['created_at'], $invoice['created_at']]);
    $orderItems = $orderStmt->fetchAll();
}

$roomCharges = (float)$invoice['room_charges'];
$fnbCharges  = (float)$invoice['restaurant_charges'];
$taxAmount   = (float)$invoice['tax_amount'];
$totalAmount = (float)$invoice['total_amount'];
$paidAmount  = (float)$invoice['paid_amount'];
$balanceDue  = (float)$invoice['balance_due'];

$depositAmount = 0.00;
foreach ($payments as $p) {
    if (stripos($p['payment_method'], 'deposit') !== false) {
        $depositAmount += (float)$p['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Receipt - <?= htmlspecialchars($invoice['invoice_number']) ?> | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Thermal POS Receipt Standard Specifications (80mm width) */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #e2e8f0;
            color: #000;
            font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace;
            font-size: 13px;
            line-height: 1.3;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Controls Bar */
        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background: #16a34a;
            color: #fff;
        }
        .btn-print:hover { background: #15803d; }

        .btn-back {
            background: #334155;
            color: #f1f5f9;
        }
        .btn-back:hover { background: #475569; }

        /* Receipt Canvas (80mm Thermal Paper) */
        .receipt-wrapper {
            display: flex;
            justify-content: center;
            padding: 20px 10px 40px;
        }

        .pos-receipt {
            width: 80mm;
            max-width: 80mm;
            background: #fff;
            padding: 6mm 4mm;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 2px;
            color: #000;
        }

        /* Typography & Receipt Styling */
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .hotel-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .hotel-name {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .hotel-subtitle {
            font-size: 11px;
            color: #222;
            margin-bottom: 2px;
        }

        .hotel-contacts {
            font-size: 11px;
            color: #333;
        }

        .receipt-divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .receipt-divider-double {
            border: none;
            border-top: 2px dashed #000;
            margin: 6px 0;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 4px 0;
            letter-spacing: 1px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .info-label {
            color: #333;
        }

        .info-value {
            font-weight: 600;
            text-align: right;
            max-width: 55%;
            word-break: break-word;
        }

        /* Items Table */
        .items-header {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: bold;
            padding-bottom: 3px;
        }

        .item-row {
            margin-bottom: 4px;
            font-size: 12px;
        }

        .item-main {
            display: flex;
            justify-content: space-between;
        }

        .item-name {
            font-weight: 600;
            flex: 1;
            padding-right: 4px;
        }

        .item-total {
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
        }

        .item-subtext {
            font-size: 10.5px;
            color: #444;
            padding-left: 6px;
        }

        /* Totals Block */
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            font-weight: 900;
            margin: 6px 0;
            padding: 4px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .paid-stamp {
            border: 2px solid #000;
            padding: 4px 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 8px 0;
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
            color: #222;
        }

        .barcode-visual {
            letter-spacing: 4px;
            font-size: 18px;
            font-weight: bold;
            margin: 6px 0 2px;
            font-family: monospace;
        }

        /* Direct Print Rules */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .no-print, .no-print-bar {
                display: none !important;
            }

            .receipt-wrapper {
                padding: 0 !important;
                margin: 0 !important;
            }

            .pos-receipt {
                width: 79mm !important;
                max-width: 79mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 4mm 3mm !important;
                margin: 0 auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- On-Screen Navigation & Print Action Header -->
    <div class="no-print-bar no-print">
        <button class="btn-action btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print to POS Thermal Printer (80mm)
        </button>
        <button class="btn-action btn-back" onclick="window.close(); if(!window.closed) location.href='billing.php';">
            <i class="fa-solid fa-arrow-left"></i> Close / Return
        </button>
        <span class="small text-secondary ms-2">Formatted for 80mm / 3-Inch POS Receipt Printers</span>
    </div>

    <!-- Thermal Receipt Canvas -->
    <div class="receipt-wrapper">
        <div class="pos-receipt">
            
            <!-- Hotel Brand Header -->
            <div class="hotel-header">
                <div class="hotel-name"><?= htmlspecialchars(strtoupper(APP_NAME)) ?></div>
                <div class="hotel-subtitle">HOTEL & RESTAURANT MANAGEMENT</div>
                <div class="hotel-contacts">
                    123 Marine Drive, Galle Road, Colombo 03<br>
                    Tel: +94 11 234 5678 / +94 77 123 0101<br>
                    Tax Reg / VAT No: LK-VAT-98214-01
                </div>
            </div>

            <hr class="receipt-divider-double">

            <!-- Title & Metadata -->
            <div class="receipt-title">TAX INVOICE / RECEIPT</div>

            <div class="info-row">
                <span class="info-label">Invoice No:</span>
                <span class="info-value"><?= htmlspecialchars($invoice['invoice_number']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date & Time:</span>
                <span class="info-value"><?= formatDateTime($invoice['created_at']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Cashier:</span>
                <span class="info-value"><?= htmlspecialchars($invoice['cashier_name'] ?? 'Staff') ?></span>
            </div>

            <hr class="receipt-divider">

            <!-- Guest & Stay Metadata -->
            <div class="info-row">
                <span class="info-label">Guest:</span>
                <span class="info-value"><?= htmlspecialchars($invoice['guest_name'] ?? 'Walk-In Guest') ?></span>
            </div>
            <?php if (!empty($invoice['guest_phone'])): ?>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?= htmlspecialchars(formatPhoneNumber($invoice['guest_phone'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($invoice['guest_id_number'])): ?>
            <div class="info-row">
                <span class="info-label">ID / NIC:</span>
                <span class="info-value"><?= htmlspecialchars($invoice['guest_id_number']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Room:</span>
                <span class="info-value fw-bold">Room <?= htmlspecialchars($invoice['room_number'] ?? 'N/A') ?> (<?= htmlspecialchars($invoice['room_type'] ?? 'Deluxe') ?>)</span>
            </div>

            <hr class="receipt-divider">

            <!-- Items Breakdown -->
            <div class="items-header">
                <span>ITEM / DESCRIPTION</span>
                <span>AMOUNT</span>
            </div>
            <hr class="receipt-divider" style="margin-top: 1px;">

            <!-- 1. Room Stay Charges -->
            <div class="item-row">
                <div class="item-main">
                    <span class="item-name">Room Stay Accommodation</span>
                    <span class="item-total"><?= formatMoney($roomCharges) ?></span>
                </div>
                <div class="item-subtext">
                    Room <?= htmlspecialchars($invoice['room_number'] ?? '') ?> &bull; <?= htmlspecialchars($invoice['room_type'] ?? '') ?>
                </div>
            </div>

            <!-- 2. Restaurant / In-Room Dining Items -->
            <?php if (!empty($orderItems)): ?>
                <?php foreach ($orderItems as $item): ?>
                    <div class="item-row">
                        <div class="item-main">
                            <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                            <span class="item-total"><?= formatMoney($item['subtotal']) ?></span>
                        </div>
                        <div class="item-subtext">
                            <?= (int)$item['quantity'] ?> x <?= formatMoney($item['unit_price']) ?> (<?= htmlspecialchars($item['order_number']) ?>)
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($fnbCharges > 0): ?>
                <div class="item-row">
                    <div class="item-main">
                        <span class="item-name">Restaurant & Dining Folio</span>
                        <span class="item-total"><?= formatMoney($fnbCharges) ?></span>
                    </div>
                    <div class="item-subtext">In-Room Dining Charges</div>
                </div>
            <?php endif; ?>

            <hr class="receipt-divider">

            <!-- Subtotals & Taxes -->
            <div class="totals-row">
                <span>Room Charges:</span>
                <span><?= formatMoney($roomCharges) ?></span>
            </div>
            <div class="totals-row">
                <span>Restaurant / Dining:</span>
                <span><?= formatMoney($fnbCharges) ?></span>
            </div>
            <div class="totals-row">
                <span>Subtotal:</span>
                <span><?= formatMoney($roomCharges + $fnbCharges) ?></span>
            </div>
            <div class="totals-row">
                <span>Govt Tax & Service (10%):</span>
                <span><?= formatMoney($taxAmount) ?></span>
            </div>

            <!-- Grand Total -->
            <div class="grand-total-row">
                <span>TOTAL PAYABLE:</span>
                <span><?= formatMoney($totalAmount) ?></span>
            </div>

            <!-- Settlement & Payments -->
            <?php if ($depositAmount > 0): ?>
            <div class="totals-row">
                <span>Advance Deposit Paid:</span>
                <span>- <?= formatMoney($depositAmount) ?></span>
            </div>
            <?php endif; ?>

            <?php foreach ($payments as $pay): ?>
                <?php if (stripos($pay['payment_method'], 'deposit') === false): ?>
                <div class="totals-row">
                    <span>Paid (<?= htmlspecialchars($pay['payment_method']) ?>):</span>
                    <span class="fw-bold"><?= formatMoney($pay['amount']) ?></span>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="totals-row fw-bold" style="font-size: 13px; margin-top: 3px;">
                <span>BALANCE DUE:</span>
                <span><?= formatMoney($balanceDue) ?></span>
            </div>

            <!-- Paid Stamp Box -->
            <div class="paid-stamp">
                *** PAID IN FULL ***
            </div>

            <!-- Barcode Simulation & Footer -->
            <div class="receipt-footer">
                <div class="barcode-visual">||| | |||| | ||| || ||||</div>
                <div><?= htmlspecialchars($invoice['invoice_number']) ?></div>
                <br>
                <div class="fw-bold">THANK YOU FOR YOUR STAY!</div>
                <div>We look forward to welcoming you again.</div>
                <div style="font-size: 9.5px; color: #555; margin-top: 6px;">
                    Powered by <?= APP_NAME ?> Hospitality Suite
                </div>
            </div>

        </div>
    </div>

    <!-- Auto Print Script -->
    <?php if ($autoPrint): ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
    <?php endif; ?>

</body>
</html>
