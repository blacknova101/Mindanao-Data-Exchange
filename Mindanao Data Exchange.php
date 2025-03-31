<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDX</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #e8f0ff;
        text-align: center;
    }
    .navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 5%;
        background-color: #0c1a36;
        color: white;
    }
    .logo {
        display: flex;
        align-items: center;
    }
    .logo img {
        height: 50px;
    }
    .search-bar {
        flex-grow: 1;
        margin-left: 20px;
        display: flex;
        align-items: center;
    }
    .search-bar input {
        padding: 10px;
        width: 100%;
        max-width: 300px;
        border-radius: 5px;
        border: none;
    }
    .search-bar button {
        background: none;
        border: none;
        cursor: pointer;
    }
    .search-bar img {
        width: 20px;
        height: 20px;
    }
    .nav-links a {
        color: white;
        margin-left: 20px;
        text-decoration: none;
        font-size: 18px;
    }
    .wrapper {
        padding: 50px 5%;
        margin-top: 50px;
    }

    h1 {
        font-size: 50px;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .highlight {
        font-size: 60px;
    }

    p {
        font-size: 20px;
        margin-bottom: 40px;
    }
    .stats-box {
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: white;
        padding: 20px 0px 0px 0px;
        width: 30%;
        margin: 0 auto;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        font-size: 30px;
        font-weight: bold;
    }
    .stat {
        flex: 1;
        text-align: center;
    }
    .divider {
        width: 3px;
        background-color: black;
        height: 90px;
        margin-top: -20px
    }
    .upload-section {
        position: fixed;
        bottom: 20px;
        right: 20px;
    }
    .upload-btn {
        background: none;
        border: none;
        cursor: pointer;
        text-align: center;
    }
    .upload-btn img {
        width: 60px;
    }
    .upload-btn p {
        font-size: 16px;
        color: black;
    }
    @media (max-width: 768px) {
        .stats-box {
            flex-direction: column;
            width: 80%;
            font-size: 24px;
        }
        .divider {
            width: 100%;
            height: 2px;
            margin: 20px 0;
        }
        h1 {
            font-size: 40px;
        }
    }
    </style>
</head>
<body>
    <div id="wrapper">
        <header class="navbar">
            <div class="logo">
                <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search datasets">
                <button>
                    <img src="images/search_icon.png" alt="Search">
                </button>
            </div>
            <nav class="nav-links">
                <a href="login.php">Login</a>
                <a href="#">Sign up</a>
            </nav>
        </header>

        <main class="wrapper">
            <h1>Mangasay <br> <span class="highlight">Data Exchange</span></h1>
            <p>Discover, Share, and Transform Data Seamlessly.</p>
            
            <div class="stats-box">
                <div class="stat">
                    <span class="stat-number">18,000</span>
                    <p>Datasets</p>
                </div>
                <div class="divider"></div>
                <div class="stat">
                    <span class="stat-number">2,000</span>
                    <p>Sources</p>
                </div>
            </div>
        </main>

        <div class="upload-section">
            <button class="upload-btn">
                <img src="images/upload_button.png" alt="Upload">
                <p>Upload Data</p>
            </button>
        </div>
    </div>
</body>
</html>
=======
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDX</title>
    <style>
    </style>
</head>
<body>
    <div id="wrapper">
        <header class="navbar">
            <div class="logo">
                <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search datasets">
                <button>
                    <img src="images/search_icon.png" alt="Search">
                </button>
            </div>
            <nav class="nav-links">
                <a href="#">Login</a>
                <a href="#">Sign up</a>
            </nav>
        </header>

        <main class="wrapper">
            <h1>Mangasay <br> <span class="highlight">Data Exchange</span></h1>
            <p>Discover, Share, and Transform Data Seamlessly.</p>
            
            <div class="stats-box">
                <div class="stat">
                    <span class="stat-number">18,000</span>
                    <p>Datasets</p>
                </div>
                <div class="divider"></div>
                <div class="stat">
                    <span class="stat-number">2,000</span>
                    <p>Sources</p>
                </div>
            </div>
        </main>




        <div class="upload-section">
            <button class="upload-btn">
                <img src="images/upload_button.png" alt="Upload">
                <p>Upload Data</p>
        </button>
        </div>
    </div>
</body>
</html>
