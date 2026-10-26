<?php
/**
 * CodeVesper Software Solutions
 * Hardware Commercial Quotation
 * Client: Ella Hide View Hotel
 */
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Quotation - Ella Hide View Hotel | CodeVesper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary-navy: #0f172a;
            --accent-cyan: #0284c7;
            --accent-gold: #b45309;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            line-height: 1.6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Action Header */
        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #0f172a;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-print-proposal {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print-proposal:hover {
            background: #0284c7;
            color: #fff;
        }

        /* Paper Document Container (A4 Proportions) */
        .proposal-paper {
            max-width: 960px;
            margin: 30px auto;
            background: #ffffff;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            padding: 50px 60px;
            border: 1px solid var(--border-color);
        }

        /* CodeVesper Letterhead */
        .letterhead {
            border-bottom: 3px solid var(--accent-cyan);
            padding-bottom: 25px;
            margin-bottom: 35px;
        }

        .brand-logo-container {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid var(--accent-cyan);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);
        }

        .brand-title {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: var(--primary-navy);
            margin: 0;
            line-height: 1.1;
        }

        .brand-subtitle {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--accent-cyan);
        }

        .company-contact {
            text-align: right;
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .company-contact strong {
            color: var(--primary-navy);
        }

        /* Proposal Metadata Header */
        .meta-strip {
            background: var(--bg-light);
            border-left: 4px solid var(--accent-cyan);
            border-radius: 0 10px 10px 0;
            padding: 16px 20px;
            margin-bottom: 30px;
        }

        .meta-box h6 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 3px;
            font-weight: 700;
        }

        .meta-box p {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-navy);
            margin: 0;
        }

        /* Section Headings */
        .section-title {
            font-size: 19px;
            font-weight: 800;
            color: var(--primary-navy);
            position: relative;
            padding-bottom: 10px;
            margin-top: 35px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 45px;
            height: 3px;
            background: var(--accent-cyan);
            border-radius: 2px;
        }

        .section-intro {
            font-size: 14px;
            color: #475569;
            margin-bottom: 20px;
        }

        /* Product Cards */
        .product-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .product-card:hover {
            border-color: var(--accent-cyan);
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.08);
        }

        .badge-warranty {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .product-price {
            font-size: 22px;
            font-weight: 900;
            color: var(--primary-navy);
        }

        .spec-list {
            list-style: none;
            padding-left: 0;
            margin: 12px 0;
            font-size: 13px;
            color: #475569;
        }

        .spec-list li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 6px;
        }

        .spec-list li::before {
            content: '•';
            color: var(--accent-cyan);
            position: absolute;
            left: 6px;
            font-weight: bold;
            font-size: 16px;
            line-height: 1;
        }

        /* Quotation Tables */
        .quote-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0 25px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            font-size: 13.5px;
        }

        .quote-table thead th {
            background: var(--primary-navy);
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.8px;
            padding: 12px 18px;
            border: none;
        }

        .quote-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color);
            color: #334155;
            vertical-align: middle;
        }

        .quote-table tbody tr:last-child td {
            border-bottom: none;
        }

        .quote-table tbody tr:nth-child(even) {
            background-color: var(--bg-light);
        }

        .quote-table .item-name {
            font-weight: 700;
            color: var(--primary-navy);
        }

        .quote-table .item-desc {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .quote-table .item-price {
            font-weight: 800;
            color: var(--primary-navy);
            text-align: right;
            white-space: nowrap;
        }

        /* Recommended Bundle Boxes */
        .bundle-box {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            background: #fff;
            margin-bottom: 20px;
        }

        .bundle-box.featured {
            border-color: var(--accent-cyan);
            background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.1);
        }

        /* Signature Section */
        .sign-section {
            margin-top: 45px;
            padding-top: 25px;
            border-top: 2px solid var(--border-color);
        }

        .sign-box {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 20px;
            background: var(--bg-light);
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sign-line {
            border-bottom: 1px solid #475569;
            width: 80%;
            margin-top: 50px;
            margin-bottom: 6px;
        }

        /* Print Rules */
        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .screen-toolbar {
                display: none !important;
            }

            .proposal-paper {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar -->
    <div class="screen-toolbar no-print">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold text-white"><i class="fa-solid fa-server text-info me-2"></i>CodeVesper Hardware Quotation</span>
            <span class="badge bg-secondary">Client: Ella Hide View Hotel</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-print-proposal" onclick="window.print()">
                <i class="fa-solid fa-file-pdf"></i> Print / Save as PDF
            </button>
            <a href="proposal.php" class="btn btn-outline-light btn-sm px-3">
                <i class="fa-solid fa-file-contract me-1"></i> Software Proposal
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3 text-white">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Printable Quotation Paper -->
    <div class="proposal-paper">

        <!-- CodeVesper Letterhead -->
        <div class="letterhead">
            <div class="row align-items-center">
                <div class="col-7">
                    <div class="brand-logo-container">
                        <img src="assets/images/logo.jpg" alt="CodeVesper" class="brand-logo">
                        <div>
                            <h1 class="brand-title">CodeVesper</h1>
                            <div class="brand-subtitle">Hardware Solutions & POS Equipment</div>
                        </div>
                    </div>
                </div>
                <div class="col-5">
                    <div class="company-contact">
                        <div><strong>Welipathwala Road, Kurunegala</strong></div>
                        <div>Sri Lanka</div>
                        <div>Hotline: <strong>+94 71 047 5200</strong></div>
                        <div>Email: <strong>contact@codevesper.com</strong></div>
                        <div>Web: <strong>codevesper-hotel.vercel.app</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metadata Strip -->
        <div class="meta-strip">
            <div class="row g-3">
                <div class="col-6 col-md-3 meta-box">
                    <h6>Client Organization</h6>
                    <p>Ella Hide View Hotel</p>
                </div>
                <div class="col-6 col-md-3 meta-box">
                    <h6>Quotation Ref</h6>
                    <p>CV-HW-2026-EH02</p>
                </div>
                <div class="col-6 col-md-3 meta-box">
                    <h6>Date Issued</h6>
                    <p><?= date('F d, Y') ?></p>
                </div>
                <div class="col-6 col-md-3 meta-box">
                    <h6>Price Validity</h6>
                    <p>30 Days</p>
                </div>
            </div>
        </div>

        <!-- Quotation Header -->
        <h4 class="section-title">
            <i class="fa-solid fa-cash-register text-primary"></i> Commercial Hardware Quotation
        </h4>
        <p class="section-intro">
            <strong>CodeVesper Software Solutions</strong> is pleased to provide this official hardware quotation for <strong>Ella Hide View Hotel</strong>. All listed equipment is fully certified, pre-configured, and plug-and-play compatible with the CodeVesper Hotel ERP and Waiter POS suite.
        </p>

        <!-- Detailed Itemized Hardware Schedule -->
        <table class="quote-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 50%;">Hardware Equipment & Full Technical Specification</th>
                    <th style="width: 22%;">Warranty & Support</th>
                    <th style="width: 22%; text-align: right;">Unit Price (LKR)</th>
                </tr>
            </thead>
            <tbody>
                <!-- 1. POS Printer USB/LAN -->
                <tr>
                    <td><strong>01</strong></td>
                    <td>
                        <div class="item-name">High-Speed 80mm POS Thermal Receipt Printer (USB / LAN)</div>
                        <div class="item-desc">
                            Direct thermal printing &bull; 250mm/s auto-cutter &bull; Dual Interface: <strong>USB 2.0 & Ethernet (LAN)</strong> &bull; Cash drawer RJ11 port &bull; ESC/POS compatible &bull; Ideal for front desk checkout and kitchen KOT ticket station.
                        </div>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle badge-warranty">1 Year Comprehensive</span></td>
                    <td class="item-price">16,500.00</td>
                </tr>

                <!-- 2. POS Printer Bluetooth/USB/LAN/Wi-Fi -->
                <tr>
                    <td><strong>02</strong></td>
                    <td>
                        <div class="item-name">Premium Multi-Interface 80mm POS Thermal Printer (Bluetooth / Wi-Fi / LAN / USB)</div>
                        <div class="item-desc">
                            Ultra-quiet thermal mechanism &bull; Quad connectivity: <strong>Bluetooth + Wi-Fi Wireless + LAN Network + USB</strong> &bull; Heavy-duty auto-cutter &bull; Direct wireless printing from mobile phones, waiter tablets, and front desk laptops.
                        </div>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle badge-warranty">1 Year Comprehensive</span></td>
                    <td class="item-price">29,500.00</td>
                </tr>

                <!-- 3. Desktop Brand New DELL -->
                <tr>
                    <td><strong>03</strong></td>
                    <td>
                        <div class="item-name">DELL Tower QVT1260 Desktop Workstation (Brand New Full Set)</div>
                        <div class="item-desc">
                            <strong>Processor:</strong> Intel Core i3 14th Gen (14100 Processor)<br>
                            <strong>Memory:</strong> 8GB DDR4 High-Speed RAM<br>
                            <strong>Storage:</strong> 512GB Ultra-Fast NVMe M.2 SSD<br>
                            <strong>Display:</strong> 22-Inch Full HD LED Monitor (HDMI/VGA)<br>
                            <strong>Peripherals:</strong> DELL USB Keyboard & Optical Mouse<br>
                            Pre-configured with Windows 11 Pro, Chrome, and local CodeVesper ERP.
                        </div>
                    </td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle badge-warranty">3 Years Company Warranty</span></td>
                    <td class="item-price">325,000.00</td>
                </tr>

                <!-- 4. Desktop Used Core i5 -->
                <tr>
                    <td><strong>04</strong></td>
                    <td>
                        <div class="item-name">Core i5 Front Desk Desktop Computer Set (Refurbished / A-Grade Full Set)</div>
                        <div class="item-desc">
                            <strong>Processor:</strong> Intel Core i5 3rd Gen High-Speed Quad Core<br>
                            <strong>Memory:</strong> 8GB RAM<br>
                            <strong>Storage:</strong> 128GB Brand New Solid State Drive (SSD)<br>
                            <strong>Display:</strong> 22-Inch Crisp LED Monitor<br>
                            <strong>Peripherals:</strong> Brand New USB Keyboard & Optical Mouse<br>
                            Tested, optimized, and ready for high-reliability 24/7 front desk operations.
                        </div>
                    </td>
                    <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle badge-warranty">6 Months Warranty (1 Yr SSD)</span></td>
                    <td class="item-price">68,000.00</td>
                </tr>

                <!-- 5. Cash Drawer -->
                <tr>
                    <td><strong>05</strong></td>
                    <td>
                        <div class="item-name">Heavy-Duty Steel POS Cash Drawer</div>
                        <div class="item-desc">
                            Thick cold-rolled steel construction &bull; 5 Adjustable Bill slots &bull; 8 Coin trays &bull; 3-Position secure lock (Manual / Lock / Electric auto-kick) &bull; Standard RJ11 connector linking to 80mm POS printer for automated cash drawer kick upon bill settlement.
                        </div>
                    </td>
                    <td><span class="badge bg-secondary-subtle text-secondary border border-secondary badge-warranty">1 Year Replacement</span></td>
                    <td class="item-price">15,000.00</td>
                </tr>
            </tbody>
        </table>

        <div class="page-break"></div>

        <!-- Recommended Turnkey Hardware Packages -->
        <h4 class="section-title">
            <i class="fa-solid fa-layer-group text-primary"></i> Recommended Turnkey Bundles
        </h4>
        <p class="section-intro">
            For maximum cost-efficiency and unified warranty management, we recommend either of the following pre-configured bundles for Ella Hide View Hotel:
        </p>

        <div class="row g-3 mb-4">
            <!-- Package A: Economy Value Bundle -->
            <div class="col-md-6">
                <div class="bundle-box h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary text-uppercase tracking-wider small">Option 1</span>
                            <span class="text-success small fw-bold"><i class="fa-solid fa-tag me-1"></i>Most Cost-Effective</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Smart Budget Front Desk Bundle</h5>
                        <p class="small text-muted mb-3">Complete front office setup combining reliable refurbished processing with brand new POS peripherals.</p>
                        
                        <ul class="spec-list">
                            <li><strong>Core i5 Full Computer Set:</strong> 8GB RAM, 128GB New SSD, 22" LED Monitor, Keyboard & Mouse.</li>
                            <li><strong>80mm Thermal POS Printer:</strong> USB & Ethernet (LAN) with auto-cutter.</li>
                            <li><strong>Heavy-Duty Steel Cash Drawer:</strong> Auto-kick RJ11 interface.</li>
                            <li><strong>Setup & Testing:</strong> On-site driver installation, receipt calibration, and local hotel network setup.</li>
                        </ul>
                    </div>

                    <div class="pt-3 border-top mt-3">
                        <div class="text-muted small">Total Package Price:</div>
                        <div class="product-price text-primary">LKR 99,500.00</div>
                        <div class="small text-muted mt-1"><i class="fa-solid fa-check text-success me-1"></i> Plug & Play Ready &bull; Includes Cables & Paper Rolls</div>
                    </div>
                </div>
            </div>

            <!-- Package B: Enterprise Brand New Bundle -->
            <div class="col-md-6">
                <div class="bundle-box featured h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary text-uppercase tracking-wider small">Option 2</span>
                            <span class="text-primary small fw-bold"><i class="fa-solid fa-crown text-warning me-1"></i>Premium Enterprise</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Executive Brand New DELL Bundle</h5>
                        <p class="small text-muted mb-3">Enterprise-grade durability with 14th Gen processing power, wireless POS printing, and 3-year warranty.</p>
                        
                        <ul class="spec-list">
                            <li><strong>DELL Tower QVT1260 Brand New:</strong> i3 14th Gen, 8GB RAM, 512GB NVMe SSD, 22" FHD Monitor (<strong>3 Years Warranty</strong>).</li>
                            <li><strong>Quad-Interface 80mm POS Printer:</strong> Bluetooth + Wi-Fi + LAN + USB for wireless mobile printing.</li>
                            <li><strong>Heavy-Duty Steel Cash Drawer:</strong> 5-Bill, 8-Coin with printer auto-trigger.</li>
                            <li><strong>Setup & Testing:</strong> Comprehensive configuration with remote cloud sync and local backup scripts.</li>
                        </ul>
                    </div>

                    <div class="pt-3 border-top mt-3">
                        <div class="text-muted small">Total Package Price:</div>
                        <div class="product-price text-success">LKR 369,500.00</div>
                        <div class="small text-muted mt-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> 3-Year Company Direct Warranty &bull; Free Delivery</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commercial Terms & Conditions -->
        <h4 class="section-title">
            <i class="fa-solid fa-shield text-primary"></i> Hardware Terms, Delivery & Warranty
        </h4>
        <div class="row g-3 text-secondary small">
            <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <strong class="text-dark d-block mb-1"><i class="fa-solid fa-truck-ramp-box text-primary me-1"></i> Delivery & On-Site Installation:</strong>
                    Equipment will be delivered, assembled, and tested directly at Ella Hide View Hotel premises. Driver installation, network IP assignment, and thermal receipt logo branding are included free of charge.
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <strong class="text-dark d-block mb-1"><i class="fa-solid fa-wrench text-primary me-1"></i> Warranty & Replacement Terms:</strong>
                    Brand new DELL systems carry 3 years authorized company warranty. Printers and cash drawers include 1-year replacement warranty. In case of hardware service, temporary backup equipment is provided under SLA.
                </div>
            </div>
        </div>

        <!-- Sign-Off Section -->
        <div class="sign-section">
            <div class="row g-4">
                <div class="col-6">
                    <div class="sign-box">
                        <div>
                            <h6 class="fw-bold mb-1">Submitted By:</h6>
                            <p class="small text-muted mb-0">CodeVesper Hardware & POS Division</p>
                        </div>
                        <div>
                            <div class="sign-line"></div>
                            <div class="fw-bold small">Authorized Representative & Stamp</div>
                            <div class="small text-muted">Welipathwala Road, Kurunegala | 0710475200</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="sign-box">
                        <div>
                            <h6 class="fw-bold mb-1">Accepted & Selected By:</h6>
                            <p class="small text-muted mb-0">Ella Hide View Hotel</p>
                        </div>
                        <div>
                            <div class="sign-line"></div>
                            <div class="fw-bold small">Client Signature & Hotel Stamp</div>
                            <div class="small text-muted">Selected Option: [ &nbsp; ] Option 1 &nbsp; [ &nbsp; ] Option 2</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-muted small">
            &copy; <?= date('Y') ?> CodeVesper Software Solutions &bull; Official Hardware Quotation Ref: CV-HW-2026-EH02
        </div>

    </div>

</body>
</html>
