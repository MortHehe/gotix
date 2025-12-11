<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - <?= htmlspecialchars($ticket['ticket_code']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f3f4f6;
        padding: 10px;
    }

    .ticket-container {
        max-width: 190mm;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* Header - Compact */
    .ticket-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 20px;
        position: relative;
        overflow: hidden;
    }

    .header-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 20px;
        font-weight: 700;
    }

    .logo i {
        font-size: 22px;
    }

    .ticket-type {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 600;
    }

    /* Status Badge - Compact */
    .status-section {
        background: rgba(16, 185, 129, 0.1);
        border-left: 3px solid #10b981;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .status-icon {
        width: 32px;
        height: 32px;
        background: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        flex-shrink: 0;
    }

    .status-text h3 {
        color: #10b981;
        font-size: 13px;
        margin-bottom: 2px;
    }

    .status-text p {
        color: #6b7280;
        font-size: 10px;
    }

    /* Main Content - Ultra Compact */
    .ticket-body {
        padding: 15px 20px;
    }

    /* Journey Section - Minimal */
    .journey-section {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
        border: 1px solid #bae6fd;
    }

    .journey-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .journey-title {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #0c4a6e;
        font-size: 12px;
        font-weight: 600;
    }

    .journey-date {
        color: #0369a1;
        font-size: 11px;
        font-weight: 500;
    }

    .journey-route {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .route-point {
        flex: 1;
        text-align: center;
    }

    .route-time {
        font-size: 22px;
        font-weight: 700;
        color: #0c4a6e;
        margin-bottom: 4px;
        line-height: 1;
    }

    .route-station {
        font-size: 13px;
        color: #0369a1;
        font-weight: 500;
        line-height: 1.2;
    }

    .route-code {
        font-size: 9px;
        color: #64748b;
        margin-top: 2px;
    }

    .route-arrow {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: #0c4a6e;
    }

    .route-arrow i {
        font-size: 16px;
    }

    .route-duration {
        font-size: 10px;
        color: #64748b;
    }

    /* Content Grid - Side by Side */
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 240px;
        gap: 12px;
        margin-bottom: 12px;
    }

    /* Info Grid - Minimal */
    .info-grid {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-section {
        background: #f9fafb;
        border-radius: 6px;
        padding: 10px;
        border: 1px solid #e5e7eb;
    }

    .info-section h4 {
        color: #1f2937;
        font-size: 11px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e5e7eb;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
        padding-bottom: 5px;
        border-bottom: 1px solid #f3f4f6;
        gap: 8px;
    }

    .info-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .info-label {
        color: #6b7280;
        font-size: 10px;
        flex-shrink: 0;
    }

    .info-value {
        color: #1f2937;
        font-weight: 600;
        font-size: 10px;
        text-align: right;
        word-break: break-word;
    }

    /* QR Code Section - Minimal */
    .qr-section {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-radius: 6px;
        padding: 12px;
        text-align: center;
        border: 1px dashed #fbbf24;
    }

    .qr-section h4 {
        color: #92400e;
        font-size: 11px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .qr-wrapper {
        background: white;
        padding: 8px;
        border-radius: 6px;
        display: inline-block;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .qr-image {
        width: 130px;
        height: 130px;
        display: block;
    }

    .qr-code {
        margin-top: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #92400e;
        letter-spacing: 1px;
        font-family: 'Courier New', monospace;
    }

    .qr-instruction {
        margin-top: 6px;
        color: #78350f;
        font-size: 9px;
    }

    /* Footer - Ultra Compact */
    .ticket-footer {
        background: #f9fafb;
        padding: 10px 20px;
        border-top: 1px dashed #e5e7eb;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-bottom: 8px;
    }

    .footer-item {
        text-align: center;
    }

    .footer-item i {
        color: #667eea;
        font-size: 12px;
        margin-bottom: 3px;
    }

    .footer-item p {
        color: #6b7280;
        font-size: 8px;
        margin-bottom: 2px;
    }

    .footer-item strong {
        color: #1f2937;
        font-size: 10px;
        display: block;
        word-break: break-word;
    }

    .footer-note {
        background: #fef3c7;
        border-left: 3px solid #fbbf24;
        padding: 8px;
        border-radius: 4px;
        color: #92400e;
        font-size: 9px;
        line-height: 1.4;
    }

    .footer-note i {
        margin-right: 4px;
    }

    /* Print Buttons */
    .print-actions {
        text-align: center;
        padding: 12px 20px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        border: none;
        cursor: pointer;
        margin: 0 4px;
    }

    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
    }

    /* CRITICAL PRINT STYLES */
    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        html,
        body {
            width: 210mm;
            height: 297mm;
            margin: 0;
            padding: 0;
            background: white;
        }

        body {
            padding: 0 !important;
        }

        .ticket-container {
            box-shadow: none !important;
            max-width: 100% !important;
            width: 100% !important;
            border-radius: 0 !important;
            margin: 0 !important;
            height: auto !important;

            /* FORCE SINGLE PAGE */
            page-break-before: avoid !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .print-actions {
            display: none !important;
        }

        /* PREVENT ALL BREAKS */
        .ticket-header,
        .status-section,
        .ticket-body,
        .journey-section,
        .content-grid,
        .info-grid,
        .info-section,
        .qr-section,
        .ticket-footer,
        .footer-grid,
        .footer-note {
            page-break-before: avoid !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-before: avoid !important;
            break-after: avoid !important;
            break-inside: avoid !important;
        }

        /* Force colors */
        * {
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Reduce all spacing for print */
        .ticket-header {
            padding: 10mm 8mm !important;
        }

        .status-section {
            padding: 6mm 8mm !important;
        }

        .ticket-body {
            padding: 8mm !important;
        }

        .journey-section {
            margin-bottom: 4mm !important;
            padding: 4mm !important;
        }

        .content-grid {
            margin-bottom: 4mm !important;
        }

        .info-grid {
            gap: 3mm !important;
        }

        .info-section {
            padding: 3mm !important;
        }

        .qr-section {
            padding: 4mm !important;
        }

        .ticket-footer {
            padding: 4mm 8mm !important;
        }

        .footer-grid {
            margin-bottom: 3mm !important;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }

        .qr-section {
            margin-top: 10px;
        }
    }
    </style>
</head>

<body>
    <div class="ticket-container">
        <!-- Header -->
        <div class="ticket-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-train"></i>
                    <span>GO<span style="color: #fbbf24;">TIX</span></span>
                </div>
                <div class="ticket-type">
                    <i class="fas fa-ticket-alt"></i> E-TICKET
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="status-section">
            <div class="status-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="status-text">
                <h3>Tiket Valid - Pembayaran Berhasil</h3>
                <p>Tunjukkan QR Code saat boarding</p>
            </div>
        </div>

        <!-- Body -->
        <div class="ticket-body">
            <!-- Journey Info -->
            <div class="journey-section">
                <div class="journey-header">
                    <div class="journey-title">
                        <i class="fas fa-route"></i>
                        Informasi Perjalanan
                    </div>
                    <div class="journey-date">
                        <i class="fas fa-calendar-alt"></i>
                        <?= formatDate($ticket['date_book']) ?>
                    </div>
                </div>
                <div class="journey-route">
                    <div class="route-point">
                        <div class="route-time"><?= date('H:i', strtotime($ticket['departure_time'])) ?></div>
                        <div class="route-station"><?= htmlspecialchars($ticket['origin']) ?></div>
                        <div class="route-code">Keberangkatan</div>
                    </div>
                    <div class="route-arrow">
                        <i class="fas fa-arrow-right"></i>
                        <div class="route-duration">
                            <?= floor($ticket['duration_minutes'] / 60) ?>j <?= $ticket['duration_minutes'] % 60 ?>m
                        </div>
                    </div>
                    <div class="route-point">
                        <div class="route-time"><?= date('H:i', strtotime($ticket['arrival_time'])) ?></div>
                        <div class="route-station"><?= htmlspecialchars($ticket['destination']) ?></div>
                        <div class="route-code">Tujuan</div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left: Info -->
                <div class="info-grid">
                    <div class="info-section">
                        <h4><i class="fas fa-train"></i> Informasi Kereta</h4>
                        <div class="info-item">
                            <span class="info-label">Nama Kereta</span>
                            <span class="info-value"><?= htmlspecialchars($ticket['name_train']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Kelas</span>
                            <span class="info-value"><?= htmlspecialchars($ticket['type_train']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Penumpang</span>
                            <span class="info-value"><?= (int)$ticket['amount_ticket'] ?> Orang</span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-receipt"></i> Informasi Booking</h4>
                        <div class="info-item">
                            <span class="info-label">Kode Booking</span>
                            <span class="info-value"><?= htmlspecialchars($ticket['payment_gateway_ref']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pemesan</span>
                            <span class="info-value"><?= htmlspecialchars($ticket['user_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total</span>
                            <span class="info-value"><?= formatRupiah($ticket['amount_price']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Right: QR -->
                <div class="qr-section">
                    <h4><i class="fas fa-qrcode"></i> Scan QR</h4>
                    <div class="qr-wrapper">
                        <img class="qr-image"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=<?= urlencode($ticket['ticket_code']) ?>"
                            alt="QR Code">
                    </div>
                    <div class="qr-code"><?= htmlspecialchars($ticket['ticket_code']) ?></div>
                    <div class="qr-instruction">
                        <i class="fas fa-info-circle"></i> Tunjukkan saat check-in
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <div class="footer-grid">
                <div class="footer-item">
                    <i class="fas fa-user"></i>
                    <p>Pemesan</p>
                    <strong><?= htmlspecialchars($ticket['user_name']) ?></strong>
                </div>
                <div class="footer-item">
                    <i class="fas fa-envelope"></i>
                    <p>Email</p>
                    <strong><?= htmlspecialchars($ticket['user_email']) ?></strong>
                </div>
                <div class="footer-item">
                    <i class="fas fa-calendar-check"></i>
                    <p>Diterbitkan</p>
                    <strong><?= date('d M Y H:i', strtotime($ticket['issued_at'])) ?></strong>
                </div>
            </div>
            <div class="footer-note">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Penting:</strong> Tiba 30 menit sebelum keberangkatan. Tunjukkan e-ticket + ID saat check-in.
            </div>
        </div>

        <!-- Print Actions -->
        <div class="print-actions">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i>
                Cetak
            </button>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-download"></i>
                Download PDF
            </button>
            <a href="my-tickets.php" class="btn-print btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>
</body>

</html>