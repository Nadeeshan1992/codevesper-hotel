<?php
/**
 * Restaurant Table QR Generator & Printable Tent Cards
 * CodeVesper Hotel & Restaurant Management Suite
 */
$pageTitle = 'Table QR Codes & Stands';
$pageSubtitle = 'Generate and print luxury contactless dining QR cards for restaurant tables';

require_once __DIR__ . '/includes/header.php';

$db = getDbConnection();

// Fetch all dining tables
$tables = $db->query("SELECT * FROM dining_tables ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Determine public URL for QR codes
$isVercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']);
$baseUrl = $isVercel ? 'https://codevesper-hotel.vercel.app' : (defined('APP_URL') ? APP_URL : 'http://localhost:8080/Hotel');
$qrTargetBase = rtrim($baseUrl, '/') . '/qr-menu.php?table=';
?>

<!-- Include QRCode.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
/* Table Stand Card Design */
.qr-card-stand {
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px;
    text-align: center;
    position: relative;
    box-shadow: 0 4px 18px rgba(0,0,0,0.04);
    transition: all 0.25s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.qr-card-stand:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    border-color: #0284c7;
}

.table-crest {
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
    margin-bottom: 2px;
}
.table-crest span {
    color: #0284c7;
    font-style: italic;
}

.stand-badge {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    border: 1px solid rgba(2, 132, 199, 0.25);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    margin-bottom: 12px;
}

.qr-code-wrapper {
    background: #ffffff;
    padding: 14px;
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    display: inline-block;
    margin: 10px auto;
}

.table-big-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #0f172a;
    margin: 8px 0 2px;
}

.table-section-tag {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 12px;
}

.scan-prompt {
    font-size: 0.78rem;
    color: #475569;
    line-height: 1.35;
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
}

/* Print Stylesheet for A4 Tent Cards */
@media print {
    body * {
        visibility: hidden;
    }
    .app-sidebar, .app-topbar, .no-print, .btn, header, nav {
        display: none !important;
    }
    #printableQrGrid, #printableQrGrid * {
        visibility: visible;
    }
    #printableQrGrid {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 10px;
    }
    .col-print-6 {
        width: 50% !important;
        float: left;
        page-break-inside: avoid;
        margin-bottom: 25px;
        padding: 10px;
    }
    .qr-card-stand {
        border: 2px solid #000000 !important;
        box-shadow: none !important;
    }
}
</style>

<!-- Control Bar -->
<div class="card-custom p-3 bg-white mb-4 no-print shadow-sm">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0">Restaurant Table QR Stands</h5>
                <span class="text-muted small">Place these QR cards on dining tables for contactless guest ordering</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Print All Button -->
            <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print All Table Stands (A4)
            </button>
            <a href="restaurant-tables.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="fa-solid fa-utensils me-1"></i> Manage Tables
            </a>
            <a href="kitchen.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                <i class="fa-solid fa-fire me-1"></i> Live Kitchen Queue
            </a>
        </div>
    </div>
</div>

<!-- QR Stand Cards Grid -->
<div class="row g-4" id="printableQrGrid">
    <?php foreach ($tables as $t): 
        $targetUrl = $qrTargetBase . $t['id'];
    ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 col-print-6">
            <div class="qr-card-stand">
                <div>
                    <!-- Hotel Brand Header -->
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <img src="assets/images/logo.jpg" alt="Logo" style="width: 28px; height: 28px; border-radius: 8px; object-fit: cover;">
                        <div class="table-crest"><?= APP_NAME ?> <span>Resort</span></div>
                    </div>
                    <span class="stand-badge"><i class="fa-solid fa-bolt me-1"></i> Contactless Dining</span>

                    <!-- Big Table Name -->
                    <div class="table-big-name"><?= htmlspecialchars($t['table_number']) ?></div>
                    <div class="table-section-tag"><?= htmlspecialchars($t['section'] ?? 'Main Dining Area') ?> &bull; Capacity: <?= $t['capacity'] ?> Guests</div>
                </div>

                <!-- QR Code Box -->
                <div>
                    <div class="qr-code-wrapper">
                        <div id="qrcode-<?= $t['id'] ?>" class="d-flex justify-content-center"></div>
                    </div>

                    <div class="scan-prompt mt-2">
                        <strong class="d-block text-dark"><i class="fa-solid fa-camera text-primary me-1"></i> Scan with Phone Camera</strong>
                        <span>Browse our visual food menu &amp; order directly to our kitchen.</span>
                    </div>
                </div>

                <!-- Action Button (Screen only) -->
                <div class="mt-3 no-print border-top pt-2">
                    <a href="qr-menu.php?table=<?= $t['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Preview Menu as Guest
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Generate QR Codes via Client-side JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($tables as $t): 
        $targetUrl = $qrTargetBase . $t['id'];
    ?>
        new QRCode(document.getElementById("qrcode-<?= $t['id'] ?>"), {
            text: "<?= addslashes($targetUrl) ?>",
            width: 140,
            height: 140,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    <?php endforeach; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
