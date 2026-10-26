<?php
/**
 * CodeVesper Software Solutions
 * Project Proposal & Commercial Quotation
 * Client: Ella Hide View Hotel
 */
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Proposal & Quotation - Ella Hide View Hotel | CodeVesper</title>
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

        /* Feature Cards */
        .feature-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 14px;
            transition: all 0.2s ease;
        }

        .feature-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .feature-icon-badge {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(2, 132, 199, 0.1);
            color: var(--accent-cyan);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .feature-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-navy);
            margin: 0;
        }

        .feature-list {
            list-style: none;
            padding-left: 48px;
            margin: 0;
            font-size: 13px;
            color: #475569;
        }

        .feature-list li {
            position: relative;
            margin-bottom: 4px;
        }

        .feature-list li::before {
            content: '✓';
            color: #16a34a;
            position: absolute;
            left: -18px;
            font-weight: bold;
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

        .total-summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 22px 25px;
            margin-top: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 8px;
            color: #475569;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 900;
            color: var(--primary-navy);
            border-top: 2px dashed #94a3b8;
            padding-top: 12px;
            margin-top: 10px;
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
            flex-column: column;
            justify-content: space-between;
        }

        .sign-line {
            border-bottom: 1px solid #475569;
            width: 80%;
            margin-top: 50px;
            margin-bottom: 6px;
        }

        /* Print Optimization */
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

    <!-- Floating Screen Controls -->
    <div class="screen-toolbar no-print">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold text-white"><i class="fa-solid fa-file-contract text-info me-2"></i>CodeVesper Proposal Engine</span>
            <span class="badge bg-secondary">Client: Ella Hide View Hotel</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-print-proposal" onclick="window.print()">
                <i class="fa-solid fa-file-pdf"></i> Print / Save as PDF
            </button>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Printable Proposal Document -->
    <div class="proposal-paper">

        <!-- CodeVesper Official Letterhead -->
        <div class="letterhead">
            <div class="row align-items-center">
                <div class="col-7">
                    <div class="brand-logo-container">
                        <img src="assets/images/logo.jpg" alt="CodeVesper" class="brand-logo">
                        <div>
                            <h1 class="brand-title">CodeVesper</h1>
                            <div class="brand-subtitle">Software Solutions & ERP Architecture</div>
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

        <!-- Proposal Metadata Banner -->
        <div class="meta-strip">
            <div class="row g-3">
                <div class="col-6 col-md-3 meta-box">
                    <h6>Prepared For</h6>
                    <p>Ella Hide View Hotel</p>
                </div>
                <div class="col-6 col-md-3 meta-box">
                    <h6>Proposal Ref</h6>
                    <p>CV-PROP-2026-EH01</p>
                </div>
                <div class="col-6 col-md-3 meta-box">
                    <h6>Date Issued</h6>
                    <p><?= date('F d, Y') ?></p>
                </div>
                <div class="col-6 col-md-3 meta-box">
                    <h6>Validity</h6>
                    <p>30 Days</p>
                </div>
            </div>
        </div>

        <!-- Executive Summary -->
        <h4 class="section-title">
            <i class="fa-solid fa-award text-primary"></i> Executive Summary
        </h4>
        <p class="section-intro">
            <strong>CodeVesper Software Solutions</strong> is pleased to submit this comprehensive commercial proposal to <strong>Ella Hide View Hotel</strong>. Our next-generation Hospitality Management System & Restaurant POS is tailored specifically to elevate your boutique property’s operational speed, streamline room inventories, automate front desk reservations, eliminate dining billing discrepancies, and provide executive oversight from anywhere in the world via high-availability cloud architecture.
        </p>

        <!-- Comprehensive Functional Scope -->
        <h4 class="section-title">
            <i class="fa-solid fa-cubes text-primary"></i> Comprehensive System Functionalities
        </h4>
        <p class="section-intro">
            The solution includes an end-to-end suite of interconnected modules built on modern web standards:
        </p>

        <div class="row g-3">
            <!-- 1. Executive Dashboard -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-chart-line"></i></div>
                        <h5 class="feature-card-title">1. Executive & Front Desk Dashboard</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Real-time Occupancy Percentage & Daily RevPAR metrics.</li>
                        <li>Today's arrivals, departures, and active in-house guest roster.</li>
                        <li>Live room occupancy feed with 1-click quick walk-in check-in.</li>
                        <li>Financial revenue summary (Room revenue + F&B revenue).</li>
                    </ul>
                </div>
            </div>

            <!-- 2. Scalable Rooms & Housekeeping -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-door-open"></i></div>
                        <h5 class="feature-card-title">2. Flexible Room & Floor Inventory</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Customizable capacity: Add, edit, or categorize rooms across any floors.</li>
                        <li>Tiered room categories (Deluxe King, Twin, Suites, Penthouse).</li>
                        <li>Automated Housekeeping Turnaround: Room switches to <em>Dirty</em> upon checkout.</li>
                        <li>One-click Housekeeping clearance to release rooms back to <em>Vacant & Clean</em>.</li>
                    </ul>
                </div>
            </div>

            <!-- 3. Front Desk & Reservations -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-calendar-check"></i></div>
                        <h5 class="feature-card-title">3. Advance Reservations & Check-In</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Walk-in key issuance and advance booking calendar.</li>
                        <li>Smart Sri Lankan phone normalizer (+94 country code standard).</li>
                        <li>Guest KYC management (National ID, Passport, Nationality, Stay duration).</li>
                        <li>Advance deposit tracking automatically credited toward final bill.</li>
                    </ul>
                </div>
            </div>

            <!-- 4. Waiter Tablet POS -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-tablet-screen-button"></i></div>
                        <h5 class="feature-card-title">4. Waiter Tablet POS & Restaurant Orders</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Fast touch-screen waiter ordering interface optimized for tablets/phones.</li>
                        <li>Visual culinary cards with high-resolution food and beverage photography.</li>
                        <li>Live Kitchen Order Tickets (KOT) with cooking status indicators.</li>
                        <li>Multi-order types: Dine-In (Table assignment), Room Service, or Takeaway.</li>
                    </ul>
                </div>
            </div>

            <!-- 5. Integrated Folio & Billing -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <h5 class="feature-card-title">5. Unified Guest Folio & Settlement</h5>
                    </div>
                    <ul class="feature-list">
                        <li>One consolidated bill: Room charges + dining charges automatically merged.</li>
                        <li>Double-click submit protection and database row locks preventing duplicate invoices.</li>
                        <li>Advance deposits automatically deducted from the final checkout balance.</li>
                        <li>Multi-payment settlement: Cash, Credit Card, Debit Card, QR, Bank Wire.</li>
                    </ul>
                </div>
            </div>

            <!-- 6. Hardware Thermal POS Printer -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-print"></i></div>
                        <h5 class="feature-card-title">6. Thermal POS Receipt Printing (80mm)</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Direct 80mm / 3-Inch thermal POS printer format integration.</li>
                        <li>Clean itemized receipts with hotel branding, tax breakdown, and barcode.</li>
                        <li>One-click auto-print upon checkout and restaurant payment settlement.</li>
                        <li>Instant reprints anytime from the audited Master Invoices ledger.</li>
                    </ul>
                </div>
            </div>

            <!-- 7. Staff & Permissions -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-shield-halved"></i></div>
                        <h5 class="feature-card-title">7. Role-Based Permissions & Privileges</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Role-based access: Hotel Owner, Administrator, Receptionist, Waiter.</li>
                        <li>Granular permissions: Control who can void orders, modify room rates, or view profit.</li>
                        <li>Staff roster, monthly salary management, and daily attendance logging.</li>
                        <li>Full immutable system audit trail logging all staff actions and transactions.</li>
                    </ul>
                </div>
            </div>

            <!-- 8. Inventory & Cloud Architecture -->
            <div class="col-md-6">
                <div class="feature-card">
                    <div class="feature-card-header">
                        <div class="feature-icon-badge"><i class="fa-solid fa-cloud"></i></div>
                        <h5 class="feature-card-title">8. Cloud High-Availability & Stock Control</h5>
                    </div>
                    <ul class="feature-list">
                        <li>Inventory & Stock tracking for kitchen ingredients, bar, and guest amenities.</li>
                        <li>Cloud deployment with serverless edge architecture: 99.9% uptime SLA.</li>
                        <li>Enterprise TLS-encrypted Cloud MySQL database with automatic off-site backups.</li>
                        <li>Zero risk of data loss from local PC crashes or hardware power cuts.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="page-break"></div>

        <!-- Commercial Quotation & Investment Schedule -->
        <h4 class="section-title">
            <i class="fa-solid fa-file-invoice text-primary"></i> Commercial Quotation & Investment
        </h4>
        <p class="section-intro">
            Transparent, all-inclusive pricing with no hidden charges. All rates quoted in Sri Lankan Rupees (LKR):
        </p>

        <table class="quote-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 52%;">Component / Deliverable Description</th>
                    <th style="width: 18%;">Billing Term</th>
                    <th style="width: 22%; text-align: right;">Amount (LKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>01</strong></td>
                    <td>
                        <div class="item-name">System Customization & Core Development</div>
                        <div class="item-desc">Complete Hotel Management ERP, Front Desk Engine, Waiter Tablet POS, Kitchen KOT, Unified Billing & Thermal Receipt Printing engine customized for Ella Hide View Hotel.</div>
                    </td>
                    <td>One-Time</td>
                    <td class="item-price">100,000.00</td>
                </tr>
                <tr>
                    <td><strong>02</strong></td>
                    <td>
                        <div class="item-name">Custom Domain Registration & Cloud Web Hosting</div>
                        <div class="item-desc">Official domain setup (.com / .lk), global DNS configuration, managed SSL/TLS security certificates, and cloud application edge hosting.</div>
                    </td>
                    <td>First Year</td>
                    <td class="item-price">15,000.00</td>
                </tr>
                <tr>
                    <td><strong>03</strong></td>
                    <td>
                        <div class="item-name">High-Performance Cloud Database Server Infrastructure</div>
                        <div class="item-desc">Enterprise TiDB Cloud MySQL deployment, automated daily snapshots, TLS SSL encryption, high-concurrency scaling, and continuous multi-device sync.</div>
                    </td>
                    <td>First Year</td>
                    <td class="item-price">35,000.00</td>
                </tr>
                <tr>
                    <td><strong>04</strong></td>
                    <td>
                        <div class="item-name">Automated SMS Gateway Integration & Sender ID</div>
                        <div class="item-desc">Automated SMS confirmation system for guest bookings, checkout alerts, branded Sender ID registration (e.g. "ELLAHIDE"), and initial message pack.</div>
                    </td>
                    <td>One-Time Setup</td>
                    <td class="item-price">25,000.00</td>
                </tr>
            </tbody>
        </table>

        <!-- Total Calculation Card -->
        <div class="total-summary-card">
            <div class="total-row">
                <span>Core Development & Software Customization:</span>
                <span class="fw-bold">LKR 100,000.00</span>
            </div>
            <div class="total-row">
                <span>Cloud Infrastructure, Domain, Server & SMS Setup:</span>
                <span class="fw-bold">LKR 75,000.00</span>
            </div>
            <div class="grand-total-row">
                <span>TOTAL INITIAL INVESTMENT (ONE-TIME):</span>
                <span class="text-primary" style="color: #0284c7 !important;">LKR 175,000.00</span>
            </div>
        </div>

        <!-- Maintenance & SLA Support Options -->
        <h4 class="section-title">
            <i class="fa-solid fa-headset text-primary"></i> Ongoing Maintenance & Priority Support (SLA)
        </h4>
        <p class="section-intro">
            To guarantee 24/7 uptime, automated cloud data backups, regular security patches, and front-desk technical support, the client may select either payment option:
        </p>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0">Option A: Monthly Retainer</h6>
                        <span class="badge bg-secondary">Flexible</span>
                    </div>
                    <h3 class="fw-bold text-primary mb-2">LKR 4,500 <span class="fs-6 text-muted fw-normal">/ month</span></h3>
                    <p class="small text-muted mb-0">Pay-as-you-go monthly support. Includes cloud monitoring, database optimizations, and priority phone/remote support.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded-3 border-primary bg-primary bg-opacity-10 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-primary mb-0">Option B: Annual Retainer (Recommended)</h6>
                        <span class="badge bg-success">Save LKR 9,000</span>
                    </div>
                    <h3 class="fw-bold text-success mb-2">LKR 45,000 <span class="fs-6 text-muted fw-normal">/ year</span></h3>
                    <p class="small text-muted mb-0">Full 12 months coverage at the price of 10 months (<strong>2 months free</strong>). Includes full priority SLA and annual system health audits.</p>
                </div>
            </div>
        </div>

        <!-- Additional Value-Add Extensions -->
        <h4 class="section-title">
            <i class="fa-solid fa-puzzle-piece text-primary"></i> Optional Feature Add-Ons
        </h4>
        <p class="section-intro">
            Optional modular extensions available upon request to further automate hotel operations:
        </p>

        <table class="quote-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 52%;">Add-On Module</th>
                    <th style="width: 18%;">Delivery</th>
                    <th style="width: 22%; text-align: right;">Add-On Price (LKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>A1</strong></td>
                    <td>
                        <div class="item-name">Online Credit/Debit Card Payment Gateway</div>
                        <div class="item-desc">Accept instant Visa/Mastercard booking deposits via PayHere, Commercial Bank, or Seylan Bank with automated payment receipting.</div>
                    </td>
                    <td>One-Time</td>
                    <td class="item-price">30,000.00</td>
                </tr>
                <tr>
                    <td><strong>A2</strong></td>
                    <td>
                        <div class="item-name">WhatsApp Automated Guest Concierge & Alerts</div>
                        <div class="item-desc">Send automated WhatsApp booking vouchers, check-in instructions, and digital PDF invoices directly to guest smartphones.</div>
                    </td>
                    <td>One-Time</td>
                    <td class="item-price">20,000.00</td>
                </tr>
                <tr>
                    <td><strong>A3</strong></td>
                    <td>
                        <div class="item-name">In-Room & Dining Table QR Self-Ordering System</div>
                        <div class="item-desc">QR codes placed in guest rooms and dining tables allowing guests to browse visual food menus and order food directly from their phones.</div>
                    </td>
                    <td>One-Time</td>
                    <td class="item-price">25,000.00</td>
                </tr>
                <tr>
                    <td><strong>A4</strong></td>
                    <td>
                        <div class="item-name">OTA 2-Way Channel Manager Sync (Booking.com / Agoda)</div>
                        <div class="item-desc">Live synchronization of room availability and rates across Booking.com and Agoda to completely eliminate overbooking.</div>
                    </td>
                    <td>One-Time</td>
                    <td class="item-price">45,000.00</td>
                </tr>
                <tr>
                    <td><strong>A5</strong></td>
                    <td>
                        <div class="item-name">Hardware POS Thermal Printer Bundle (80mm)</div>
                        <div class="item-desc">High-speed 80mm USB/Bluetooth/LAN thermal receipt printer supplied, unboxed, configured and tested with CodeVesper ERP.</div>
                    </td>
                    <td>Hardware</td>
                    <td class="item-price">28,000.00</td>
                </tr>
            </tbody>
        </table>

        <!-- Implementation Timeline & Milestones -->
        <h4 class="section-title">
            <i class="fa-solid fa-list-check text-primary"></i> Implementation Timeline & Payment Milestones
        </h4>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light">
                    <span class="badge bg-primary mb-2">Milestone 1 (50%)</span>
                    <h6 class="fw-bold mb-1">Project Initiation</h6>
                    <p class="small text-muted mb-0">LKR 87,500 advance upon agreement signing. Cloud server, database, and system customization initiated.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light">
                    <span class="badge bg-info mb-2">Milestone 2 (30%)</span>
                    <h6 class="fw-bold mb-1">Testing & Training</h6>
                    <p class="small text-muted mb-0">LKR 52,500 upon deployment of rooms, menu items, staff accounts, and staff training at Ella Hide View Hotel.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light">
                    <span class="badge bg-success mb-2">Milestone 3 (20%)</span>
                    <h6 class="fw-bold mb-1">Go-Live & Handover</h6>
                    <p class="small text-muted mb-0">LKR 35,000 upon official launch, handover of administrator master credentials, and final sign-off.</p>
                </div>
            </div>
        </div>

        <!-- Acceptance & Sign-off -->
        <div class="sign-section">
            <div class="row g-4">
                <div class="col-6">
                    <div class="sign-box">
                        <div>
                            <h6 class="fw-bold mb-1">Prepared & Submitted By:</h6>
                            <p class="small text-muted mb-0">CodeVesper Software Solutions</p>
                        </div>
                        <div>
                            <div class="sign-line"></div>
                            <div class="fw-bold small">Authorized Signature & Seal</div>
                            <div class="small text-muted">Welipathwala Road, Kurunegala | 0710475200</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="sign-box">
                        <div>
                            <h6 class="fw-bold mb-1">Accepted & Approved By:</h6>
                            <p class="small text-muted mb-0">Ella Hide View Hotel</p>
                        </div>
                        <div>
                            <div class="sign-line"></div>
                            <div class="fw-bold small">Client Signature & Hotel Stamp</div>
                            <div class="small text-muted">Date: ________________________</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-muted small">
            &copy; <?= date('Y') ?> CodeVesper Software Solutions. All rights reserved. &bull; Confidential Commercial Proposal
        </div>

    </div>

</body>
</html>
