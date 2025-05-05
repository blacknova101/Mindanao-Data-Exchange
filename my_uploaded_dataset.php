<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dataset Detail Page</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f9;
      color: #333;
    }

    .container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .header-section {
      display: flex;
      flex-wrap: wrap;
      align-items: stretch;
      gap: 20px;
    }

    .header-left {
      flex: 1;
      background: #007BFF;
      color: #fff;
      padding: 30px;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .header-left h2 {
      font-size: 18px;
      font-weight: 500;
      margin: 0 0 10px;
    }

    .header-left h1 {
      font-size: 26px;
      font-weight: 700;
      margin: 0;
      text-transform: uppercase;
    }

    .header-right {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .header-right img {
      width: 100%;
      height: auto;
      border-radius: 8px;
    }

    .title-section {
      margin-top: 30px;
    }

    .title-section h1 {
      margin: 0;
      font-size: 24px;
      color: #222;
    }

    .title-section p {
      margin-top: 10px;
      font-size: 16px;
      line-height: 1.5;
      color: #555;
    }

    .info-grid {
      display: flex;
      gap: 40px;
      margin-top: 40px;
      flex-wrap: wrap;
    }

    .info-box, .resources-box {
      flex: 1;
      min-width: 300px;
      background: #f9fbfd;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
    }

    .info-box h3, .resources-box h3 {
      margin-top: 0;
      margin-bottom: 16px;
      font-size: 18px;
      color: #007BFF;
      border-bottom: 1px solid #ccc;
      padding-bottom: 8px;
    }

    .info-item {
      margin-bottom: 10px;
    }

    .info-item strong {
      display: block;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .file-item {
      display: flex;
      align-items: center;
      margin-bottom: 12px;
      background: #fff;
      padding: 10px 12px;
      border-radius: 6px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    .file-item i {
      font-style: normal;
      font-size: 18px;
      margin-right: 10px;
    }

    .file-item a {
      flex-grow: 1;
      text-decoration: none;
      color: #007BFF;
    }

    .file-item a:hover {
      text-decoration: underline;
    }

    .file-download {
      color: #555;
      font-size: 18px;
      margin-left: 10px;
    }

    @media (max-width: 768px) {
      .header-section {
        flex-direction: column;
      }
      .info-grid {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Banner -->
    <div class="header-section">
      <div class="header-left">
        <h2>Philippine Financial Data Insights (PFDI)</h2>
        <h1>Global Stock Market Performance</h1>
      </div>
      <div class="header-right">
        <img src="images/mdx_logo.png" alt="Charts Image">
      </div>
    </div>

    <!-- Title and description -->
    <div class="title-section">
      <h1>Global Stock Market Performance</h1>
      <p>
        This dataset provides a comprehensive overview of stock market trends, price fluctuations, and trading volumes from various financial exchanges. It covers key stock indices, individual stock performances, and market trends specific to the Philippines.
      </p>
    </div>

    <!-- Info and Files -->
    <div class="info-grid">
      <!-- Left: Info -->
      <div class="info-box">
        <h3>Additional Information</h3>
        <div class="info-item">
          <strong>Time Period</strong>
          <span>January 1, 2018 – December 31, 2023</span>
        </div>
        <div class="info-item">
          <strong>Location</strong>
          <span>Philippines</span>
        </div>
        <div class="info-item">
          <strong>Source</strong>
          <span>Philippine Financial Data Insights (PFDI)</span>
        </div>
        <div class="info-item">
          <strong>Links</strong>
          <a href="#">https://www.pfdi-research.org/stock-market-data-2024</a>
        </div>
      </div>

      <!-- Right: Resources -->
      <div class="resources-box">
        <h3>Data and Resources</h3>
        <div class="file-item">
          <i>📄</i>
          <a href="#">global_stock_market_performance.csv</a>
          <span class="file-download">⬇️</span>
        </div>
        <div class="file-item">
          <i>🧾</i>
          <a href="#">global_stock_market_metadata.json</a>
          <span class="file-download">⬇️</span>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
