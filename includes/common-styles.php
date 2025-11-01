<!-- Common Styles for All Pages -->
<style>
    /* Global Resets and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: #2d3748;
        background: #f7fafc;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Main Content Wrapper - Consistent spacing */
    main {
        flex: 1;
        margin-top: 80px; /* Space for fixed header */
        margin-bottom: 40px; /* Space before footer */
        min-height: calc(100vh - 80px - 400px); /* header - footer */
    }

    /* Container for content */
    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Common spacing utilities */
    .mt-20 { margin-top: 20px; }
    .mt-40 { margin-top: 40px; }
    .mt-60 { margin-top: 60px; }
    .mb-20 { margin-bottom: 20px; }
    .mb-40 { margin-bottom: 40px; }
    .mb-60 { margin-bottom: 60px; }

    /* Common button styles */
    .btn {
        display: inline-block;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }

    .btn-primary {
        background: #1cabe2;
        color: white;
    }

    .btn-primary:hover {
        background: #0e7fa5;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(28,171,226,0.4);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #2d3748;
    }

    .btn-secondary:hover {
        background: #cbd5e0;
    }

    /* Common card styles */
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }

    /* Page titles */
    .page-title {
        font-size: 2.5rem;
        color: #1a202c;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .page-subtitle {
        font-size: 1.2rem;
        color: #718096;
        margin-bottom: 40px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        main {
            margin-top: 70px;
        }

        .page-container {
            padding: 20px 15px;
        }

        .page-title {
            font-size: 2rem;
        }
    }
</style>
