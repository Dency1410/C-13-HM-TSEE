<?php
require 'includes/db.php';
$result = mysqli_query($conn, "SELECT * FROM investor_page ORDER BY id DESC LIMIT 1");
$inv = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : [];

function invVal($inv, $key, $fallback = '')
{
    return htmlspecialchars($inv[$key] ?? $fallback);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investors - H&M</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        }

        /* ═══════════════════════════════════
           HEADER NAVIGATION
        ═══════════════════════════════════ */
        .hm-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .hm-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            height: 70px;
            transition: all 0.3s ease;
        }

        .hm-logo {
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .hm-logo-svg {
            width: 55px;
            height: auto;
            display: block;
            transition: all 0.3s ease;
        }

        .hm-nav-menu {
            display: flex;
            align-items: center;
            gap: 40px;
            list-style: none;
            margin: 0 0 0 50px;
            padding: 0;
            transition: all 0.3s ease;
        }

        .hm-nav-menu li {
            margin: 0;
            position: relative;
        }

        .hm-nav-menu a {
            text-decoration: none;
            color: #707070;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 25px 0;
            display: inline-block;
            position: relative;
            transition: color 0.3s ease;
        }

        .hm-nav-menu a:hover {
            color: #000000;
        }

        .hm-icons {
            display: flex;
            align-items: center;
            gap: 34px;
        }

        .hm-icon-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #222222;
            font-size: 20px;
            transition: transform 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hm-icon-btn svg {
            display: block;
        }

        .hm-icon-btn:hover {
            transform: scale(1.1);
        }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background-color: #E50010;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 16px;
            text-align: center;
            line-height: 1.2;
        }

        /* ═══════════════════════════════════
           SIDE PANEL FOR NAVIGATION
        ═══════════════════════════════════ */
        .side-panel {
            position: fixed;
            left: 0;
            top: 70px;
            width: 400px;
            max-width: 85vw;
            height: calc(100vh - 70px);
            background-color: #ffffff;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 999;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            padding: 40px 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .side-panel::-webkit-scrollbar {
            display: none;
        }

        .side-panel.active {
            transform: translateX(0);
        }

        .side-panel-header {
            padding: 0 30px 20px 30px;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-arrow {
            cursor: pointer;
            color: #222222;
            font-size: 20px;
            transition: transform 0.2s ease;
        }

        .back-arrow:hover {
            transform: translateX(-3px);
        }

        .side-panel-title {
            font-size: 24px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .side-panel-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .side-panel-menu li {
            margin: 0;
        }

        .side-panel-menu a {
            display: block;
            padding: 15px 30px;
            color: #222222;
            text-decoration: none;
            font-size: 16px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .side-panel-menu a:hover {
            background-color: #f8f8f8;
            border-left-color: #E50010;
            padding-left: 35px;
        }

        .panel-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background-color: rgba(0, 0, 0, 0.3);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .panel-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .kids-panel-content {
            padding: 0 30px;
        }

        .kids-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .kids-column-title {
            font-size: 18px;
            font-weight: 700;
            color: #222222;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-left: 0;
        }

        .kids-column-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .kids-column-menu li {
            margin: 0;
        }

        .kids-column-menu a {
            display: block;
            padding: 12px 0;
            color: #222222;
            text-decoration: none;
            font-size: 15px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            padding-left: 0;
        }

        .kids-column-menu a:hover {
            color: #E50010;
            padding-left: 10px;
            border-left-color: #E50010;
        }

        /* ═══════════════════════════════════
           INVESTORS PAGE CONTENT
        ═══════════════════════════════════ */
        .investors-section {
            background-color: #ffffff;
            min-height: calc(100vh - 70px - 400px);
        }

        .investors-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0;
        }

        .investors-content {
            display: grid;
            grid-template-columns: 1fr;
        }

        .investors-hero {
            width: 100%;
            overflow: hidden;
        }

        .investors-hero-image {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }

        .page-title {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 50px;
            text-transform: uppercase;
            padding: 40px 0 0 40px;
        }

        .investors-info {
            padding: 60px 40px;
            background-color: #ffffff;
        }

        .contact-section {
            margin-top: 30px;
        }

        .contact-title {
            font-size: 28px;
            font-weight: 300;
            color: #222222;
            margin-bottom: 20px;
            font-family: Georgia, 'Times New Roman', serif;
        }

        .contact-name {
            font-size: 16px;
            font-weight: 400;
            color: #222222;
            margin-bottom: 8px;
        }

        .contact-detail {
            font-size: 16px;
            font-weight: 400;
            color: #222222;
            margin-bottom: 5px;
        }

        /* ═══════════════════════════════════
           FOOTER SECTION
        ═══════════════════════════════════ */
        .hm-footer {
            background-color: #222222;
            color: #ffffff;
            padding: 60px 0 30px 0;
            margin-top: 0;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .footer-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 60px;
            margin-bottom: 50px;
        }

        .footer-column {
            display: flex;
            flex-direction: column;
        }

        .footer-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            color: #999999;
        }

        .footer-copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #444444;
        }

        .footer-copyright p {
            font-size: 13px;
            color: #999999;
            margin: 0;
            line-height: 1.6;
        }

        /* ═══════════════════════════════════
           RESPONSIVE DESIGN
        ═══════════════════════════════════ */
        @media (max-width: 768px) {
            .side-panel {
                width: 70vw;
            }

            .footer-columns {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-container {
                padding: 0 20px;
            }

            .investors-title {
                font-size: 48px;
                padding: 20px 20px 20px 20px;
            }

            .investors-info {
                padding: 40px 20px;
            }

            .contact-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .hm-navbar {
                padding: 0 15px;
                height: 60px;
            }

            .hm-nav-menu {
                gap: 18px;
                margin-left: 20px;
            }

            .side-panel {
                width: 85vw;
            }

            .investors-title {
                font-size: 36px;
                padding: 15px 15px 15px 15px;
            }
        }
    </style>
</head>

<body>

    <!-- ═══════════════════════════════════
         H&M HEADER NAVIGATION
    ═══════════════════════════════════ -->
    <?php include 'header.php' ?>


    <!-- ═══════════════════════════════════
         INVESTORS PAGE CONTENT
    ═══════════════════════════════════ -->
    <section class="investors-section">
        <div class="investors-container">
            <div class="investors-content">
                <!-- Title Above Image -->
                <h1 class="page-title">INVESTORS</h1>

                <!-- Hero Image -->
                <?php if (!empty($inv['hero_image'])): ?>
                    <div class="investors-hero">
                        <img src="<?= invVal($inv, 'hero_image') ?>" alt="Investor Relations" class="investors-hero-image"
                            height="100px" width="100px">
                    </div>
                <?php endif; ?>

                <!-- Information Section -->
                <div class="investors-info">
                    <!-- Contact Section -->
                    <div class="contact-section">
                        <h2 class="contact-title"><?= invVal($inv, 'contact_section_title', 'Contact') ?></h2>
                        <?php if (!empty($inv['contact_name'])): ?>
                            <p class="contact-name"><?= invVal($inv, 'contact_name') ?></p>
                        <?php endif; ?>
                        <?php if (!empty($inv['contact_phone'])): ?>
                            <p class="contact-detail">PHONE: <?= invVal($inv, 'contact_phone') ?></p>
                        <?php endif; ?>
                        <?php if (!empty($inv['contact_email'])): ?>
                            <p class="contact-detail">EMAIL: <?= invVal($inv, 'contact_email') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════
         FOOTER SECTION
    ═══════════════════════════════════ -->

    <footer class="hm-footer">
        <div class="footer-container">
            <div class="footer-columns">
                <!-- Shop Column -->
                <div class="footer-column">
                    <h3 class="footer-title">SHOP</h3>
                    <ul class="footer-links">
                        <li><a href="product.php?gender=Ladies">LADIES</a></li>
                        <li><a href="product.php?gender=Men">MEN</a></li>
                        <li><a href="product.php?gender=Kids">KIDS</a></li>
                    </ul>
                </div>

                <!-- Corporate Info Column -->
                <div class="footer-column">
                    <h3 class="footer-title">CORPORATE INFO</h3>
                    <ul class="footer-links">
                        <li><a href="about-us.php">ABOUT US</a></li>
                        <li><a href="ceo.php">CEO</a></li>
                        <li><a href="investor.php">INVESTOR</a></li>
                        <li><a href="board-of-director.php">BOARD OF DIRECTOR</a></li>
                    </ul>
                </div>

                <!-- Help Column with Newsletter form (validation) -->
                <div class="footer-column">
                    <h3 class="footer-title">HELP</h3>
                    <ul class="footer-links">
                        <li><a href="customer-service.php">CUSTOMER SERVICE</a></li>

                        <li><a href="contact.php">CONTACT</a></li>
                    </ul>
                    <!-- Newsletter signup (for validation demo) -->
                    <!-- <form class="newsletter-form mt-4" id="newsletterForm">
                        <label for="newsletterEmail" class="text-white-50 text-uppercase small">Subscribe</label>
                        <input type="email" class="newsletter-input" name="email" id="newsletterEmail" placeholder="Your email" required>
                        <button type="submit" class="newsletter-btn">Subscribe</button>
                    </form> -->
                </div>
            </div>

            <!-- Copyright Notice -->
            <div class="footer-copyright">
                <p>The content of this site is copyright-protected and is the property of H & M Hennes & Mauritz AB.</p>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Side Panel Control for Ladies Menu
        const ladiesMenuItem = document.getElementById('ladiesMenuItem');
        const ladiesSidePanel = document.getElementById('ladiesSidePanel');
        const panelOverlay = document.getElementById('panelOverlay');
        const closeLadiesPanelBtn = document.getElementById('closeLadiesPanelBtn');

        // Side Panel Control for Men Menu
        const menMenuItem = document.getElementById('menMenuItem');
        const menSidePanel = document.getElementById('menSidePanel');
        const closeMenPanelBtn = document.getElementById('closeMenPanelBtn');

        // Side Panel Control for Kids Menu
        const kidsMenuItem = document.getElementById('kidsMenuItem');
        const kidsSidePanel = document.getElementById('kidsSidePanel');
        const closeKidsPanelBtn = document.getElementById('closeKidsPanelBtn');

        // Function to close all panels
        function closeAllPanels() {
            ladiesSidePanel.classList.remove('active');
            menSidePanel.classList.remove('active');
            kidsSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        }

        // LADIES MENU CONTROLS
        ladiesMenuItem.addEventListener('mouseenter', function () {
            closeAllPanels();
            ladiesSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        ladiesSidePanel.addEventListener('mouseenter', function () {
            ladiesSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        ladiesMenuItem.addEventListener('mouseleave', function (e) {
            setTimeout(() => {
                if (!ladiesSidePanel.matches(':hover')) {
                    ladiesSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });

        ladiesSidePanel.addEventListener('mouseleave', function () {
            ladiesSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });

        closeLadiesPanelBtn.addEventListener('click', function () {
            closeAllPanels();
        });

        // MEN MENU CONTROLS
        menMenuItem.addEventListener('mouseenter', function () {
            closeAllPanels();
            menSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        menSidePanel.addEventListener('mouseenter', function () {
            menSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        menMenuItem.addEventListener('mouseleave', function (e) {
            setTimeout(() => {
                if (!menSidePanel.matches(':hover')) {
                    menSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });

        menSidePanel.addEventListener('mouseleave', function () {
            menSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });

        closeMenPanelBtn.addEventListener('click', function () {
            closeAllPanels();
        });

        panelOverlay.addEventListener('click', function () {
            closeAllPanels();
        });

        // KIDS MENU CONTROLS
        kidsMenuItem.addEventListener('mouseenter', function () {
            closeAllPanels();
            kidsSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        kidsSidePanel.addEventListener('mouseenter', function () {
            kidsSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });

        kidsMenuItem.addEventListener('mouseleave', function (e) {
            setTimeout(() => {
                if (!kidsSidePanel.matches(':hover')) {
                    kidsSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });

        kidsSidePanel.addEventListener('mouseleave', function () {
            kidsSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });

        closeKidsPanelBtn.addEventListener('click', function () {
            closeAllPanels();
        });
    </script>

<script src="autocomplete.js"></script>
</body>

</html>
