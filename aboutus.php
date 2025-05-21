<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>About Us - MDX</title>
  <link rel="stylesheet" href="assets/css/mindanaodataexchange.css" />
  <style>
    /* Full-screen background video */
    #background-video {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      object-fit: cover;
      z-index: -1;
      pointer-events: none;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f7f9fc;
      margin: 0;
      padding: 0 0 60px 0;
      color: #333;
      position: relative;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 5%;
      padding-left: 30px;
      background-color: rgba(0, 153, 255, 0.85);
      color: #cfd9ff;
      border-radius: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      position: relative;
      margin: 30px auto 10px auto;
      max-width: 1200px;
      width: 100%;
      backdrop-filter: blur(10px);
      z-index: 10;
    }

    .logo img {
      height: 40px;
    }

    .nav-links a {
      color: white;
      margin-left: 20px;
      text-decoration: none;
      font-weight: bold;
    }

    .content-wrapper {
      max-width: 900px;
      margin: 0 auto 0 auto;
      padding: 0 30px;
      box-sizing: border-box;
      position: relative;
      z-index: 10;
    }

    .main-content {
      background-color: rgba(255, 255, 255, 0.85);
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      padding: 30px;
      text-align: justify;
      margin-top: 20px;
    }

    .main-content h2 {
      margin-top: 0;
    }

    .info-panels {
      margin-top: 30px;
    }

    .panel {
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .panel .btn {
      display: inline-block;
      margin-top: 10px;
      padding: 10px 16px;
      background-color: #0099ff;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .panel .btn:hover {
      background-color: #007acc;
    }
  </style>
</head>
<body>
  <video autoplay muted loop id="background-video">
    <source src="videos/bg6.mp4" type="video/mp4">
  </video>

  <header class="navbar">
    <div class="logo">
      <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
    </div>
    <nav class="nav-links">
      <a href="mindanaodataexchange.php">Back</a>
    </nav>
  </header>

  <div class="content-wrapper">
    <div class="main-content">
      <h2>About Us</h2>
      <p>
        MDX (Mindanao Data Exchange) is Mindanao's premier open data platform, an innovative hub that connects organizations, researchers, policymakers, and communities to discover, share, and transform data into actionable insights. We believe that access to reliable and transparent data is essential to empowering sustainable development, informed decision-making, and inclusive progress across the region.
      </p>
      <p>
        Founded on the principles of collaboration, transparency, and accessibility, MDX aims to bridge data gaps by serving as a central repository for datasets spanning key sectors such as agriculture, environment, health, education, infrastructure, economy, and governance. Our platform is designed to facilitate data-driven research, promote policy innovation, and foster partnerships among public institutions, civil society, academia, and the private sector.
      </p>
      <p>
        At MDX, we don't just collect data, we build a dynamic ecosystem where data is a shared resource for social good. Through capacity-building initiatives, open data literacy programs, and strategic collaborations, we empower local communities and decision-makers to harness data for real-world impact.
      </p>
      <p>
        Whether you are a researcher looking for granular datasets, a local government unit aiming to improve service delivery, or a citizen seeking transparency and accountability, MDX is your trusted partner in shaping a data-informed future for Mindanao.
      </p>
    </div>

    <div class="info-panels">
      <div class="panel">
        <h3>Platform Information</h3>
        <p><strong>Operator:</strong> Mindanao Data Exchange (MDX)</p>
        <p><strong>Location:</strong> Davao City, Mindanao, Philippines</p>
        <p><strong>Launched:</strong> March 2025</p>
        <p><strong>Contact:</strong> 09553318341</p>
        <a href="https://www.facebook.com/profile.php?id=61576255121231" class="btn">Contact Us</a>
      </div>
    </div>
  </div>
</body>
</html>
