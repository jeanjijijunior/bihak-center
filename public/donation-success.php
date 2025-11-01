<?php
/**
 * Donation Success Page
 * Shown after successful PayPal donation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Donation - Bihak Center</title>
    <link rel="icon" type="image/png" href="../assets/images/favimg.png">
    <link rel="stylesheet" type="text/css" href="../assets/css/header_new.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Poppins', sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .success-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .success-card {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        h1 {
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 20px;
        }

        .success-message {
            font-size: 1.1rem;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .info-box {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
            border-radius: 8px;
        }

        .info-box h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-box p {
            color: #4a5568;
            margin-bottom: 8px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #4a5568;
        }

        .info-box li {
            margin-bottom: 5px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .impact-preview {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .impact-preview h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }

        .impact-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
            border-radius: 10px;
        }

        .stat-box strong {
            display: block;
            font-size: 1.8rem;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-box span {
            color: #4a5568;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .success-card {
                padding: 40px 20px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .impact-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header_new.php'; ?>

    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>

            <h1>Thank You for Your Generosity!</h1>

            <p class="success-message">
                Your donation has been received successfully. You're making a real difference in the lives of young people striving to achieve their dreams.
            </p>

            <div class="info-box">
                <h3>What Happens Next?</h3>
                <ul>
                    <li>You will receive a confirmation email from PayPal</li>
                    <li>A receipt will be sent to your registered email address</li>
                    <li>Your donation will be automatically tracked in our system</li>
                    <li>100% of your donation goes directly to supporting youth programs</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>Your Impact</h3>
                <p>
                    Every dollar you donate helps provide education, training, and opportunities to young people who need it most. Whether it's school fees, project funding, or mentorship programs, your contribution changes lives.
                </p>
            </div>

            <div class="btn-group">
                <a href="donation-impact.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                    See Your Impact
                </a>
                <a href="get-involved.php" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Get Involved
                </a>
            </div>

            <div class="impact-preview">
                <h3>Community Impact So Far</h3>
                <div class="impact-stats">
                    <div class="stat-box">
                        <strong id="total-raised">Loading...</strong>
                        <span>Total Raised</span>
                    </div>
                    <div class="stat-box">
                        <strong id="total-donors">Loading...</strong>
                        <span>Generous Donors</span>
                    </div>
                    <div class="stat-box">
                        <strong>50+</strong>
                        <span>Youth Supported</span>
                    </div>
                    <div class="stat-box">
                        <strong>20+</strong>
                        <span>Projects Funded</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer_new.php'; ?>

    <script>
        // Fetch real-time donation stats
        fetch('/api/donation-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-raised').textContent = '$' + (data.total_raised || 0).toLocaleString();
                    document.getElementById('total-donors').textContent = (data.unique_donors || 0) + '+';
                }
            })
            .catch(error => {
                console.error('Error fetching stats:', error);
                document.getElementById('total-raised').textContent = '$25,000+';
                document.getElementById('total-donors').textContent = '150+';
            });
    </script>
</body>
</html>
