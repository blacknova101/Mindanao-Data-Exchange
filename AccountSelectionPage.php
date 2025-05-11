<?php
session_start();
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']); // Sanitize the error message
}
if (isset($_SESSION['user_id'])) {
    // Redirect to login page if already logged in
    header("Location: homelogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Selection - MDX</title>
    <style>
    html, body {
        height: auto;
        overflow: auto;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        text-align: center;
    }
    .navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 5%; /* Adjusted padding for a more compact navbar */
        padding-left: 30px;
        background-color:  #0099ff; /* Transparent background */
        color: #cfd9ff;
        border-radius: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        position: relative;
        margin: 10px 0;
        backdrop-filter: blur(10px);
        max-width: 1200px; /* Limit the maximum width */
        width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
        margin-top:30px;
        margin-left: auto; /* Center align the navbar */
        margin-right: auto; /* Center align the navbar */
    }

    .logo {
        display: flex;
        align-items: center;
        margin-right: 20px;
    }
    .logo img {
        height: auto;
        width: 80px; /* Adjust logo size */
        max-width: 100%;
    }
    .nav-links a {
        color: white;
        margin-left: 20px;
        text-decoration: none;
        font-size: 18px;
        transition: transform 0.3s ease; /* Smooth transition for scaling */
    }
    .nav-links a:hover {
        transform: scale(1.2); /* Scale up the link by 20% */
    }

    #background-video {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1; /* stays behind everything */
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .main-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
    }
    .signup-container {
            width: 1250px;
            background-color: white;
            border-radius: 10px;
            padding: 0px 20px;
            display: flex;
            flex-direction: column;
            height: 750px;
            margin-bottom:50px;
            margin-top:0px;
        }
        .content-row {
            margin-top: 200px;
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            row-gap: 100px; 
            gap: 20px; 
        }    
        .description {
            flex: 1;
            padding: 0;
            margin-top: -10px;
       
        }
        .description h2 {
            font-size: 15px;
            color: #0099ff;
        }
        .account-org h2, .account-individual h2 {
            color: #0099ff;
        }
        #checkmark{
            margin-top: 50px;
        }
        .account-individual, .account-org {
            flex: 1;
            margin-top: -160px;
            padding: 0px 20px;
            border: 1px solid #0099ff;;
            text-align: center;
            height: 500px;
            border-radius: 10px;
        }
        .account-individual img, .account-org img {
            width: 40px;
            margin-bottom: 10px;
        }
        .next-button{
            margin-top: 50px;
        }
        .next-button a{
            background-color:#0099ff;
            text-decoration: none;
            color: white;
            padding: 10px 100px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size:30px;
        }
        .next-button a:hover {
            background-color: #142850;
        }
        .cancel {
            text-align: center;
            margin-top: 30px;
        }
        .cancel .cancel-button{
            font-size: 20px;
            background-color: #0099ff;
            text-decoration: none;
            color: white;
            padding: 10px 100px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancel .cancel-button:hover {
            background-color: #142850;
        }
        .cancel p {
            margin-top: 20px;
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
                    <a href="mindanaodataexchange.php">Home</a>
                </nav>
            </header>

    <div class="main-wrapper">
        <div class="signup-container">
            <div class="content-row">
                <div class="description">
                    <h2><strong>SEARCH AND DOWNLOAD DATA</strong></h2>
                    <p id="p1">Explore a vast collection of datasets from various sources. Filter, search, and download the data you need for analysis and research.</p>
                    <h2><strong>CONTACT THE CONTRIBUTOR</strong></h2>
                    <p id="p1">Connect with dataset contributors for inquiries, collaboration, or further details about their shared data.</p>
                    <h2><strong>ADD DATA</strong></h2>
                    <p id="p1">Contribute datasets to the platform, making them available for others to discover and use. Share valuable insights, research, or real-world data.</p>
                </div>
                <div class="account-individual">
                    <h2><strong>Normal MDX account</strong></h2>
                    <img src="images/user_icon.png" alt="User Icon">
                    <br>
                    <p id="checkmark"><img src="images/check.png"></p>
                    <p id="checkmark"><img src="images/check.png"></p>
                </div>
                <div class="account-org">
                    <h2><strong>With organization</strong></h2>
                    <img src="images/user_icon.png" alt="User Icon">
                    <br>
                    <p id="checkmark"><img src="images/check.png"></p>
                    <p id="checkmark"><img src="images/check.png"></p>
                    <p id="checkmark"><img src="images/check.png"></p>
                </div>
            </div>
            <div class="next-button">
            <a href="registrationdetails.php">Next</a>
            </div>
            
            <div class="cancel">
                <a class="cancel-button" href="MindanaoDataExchange.php">Cancel</a>
                <p class="backlogin">Already have an account? <a class= "backlogin" href="login.php">Log in</a></p>
            </div>
            
        </div>
    </div>


</body>
</html>
<!-- <div class="main-wrapper">
        <div class="signup-container">
            <div class="content-row">
                <div class="description">
                    <h2><strong>SEARCH AND DOWNLOAD DATA</strong></h2>
                    <p id="p1">Explore a vast collection of datasets from various sources. Filter, search, and download the data you need for analysis and research.</p>
                    <h2><strong>CONTACT THE CONTRIBUTOR</strong></h2>
                    <p id="p1">Connect with dataset contributors for inquiries, collaboration, or further details about their shared data.</p>
                    <h2><strong>ADD DATA</strong></h2>
                    <p id="p1">Contribute datasets to the platform, making them available for others to discover and use. Share valuable insights, research, or real-world data.</p>
                </div>
                <div class="account-individual">
                    <h2><strong>Normal MDX account</strong></h2>
                    <img src="images/user_icon.png" alt="User Icon">
                    <br>
                    <a href="registrationdetailsnoorg.php">Sign Up</a>
                    <p id="checkmark"><img src="images/check.png"></p>
                    <p id="checkmark"><img src="images/check.png"></p>
                </div>
                <div class="account-org">
                    <h2><strong>With organization</strong></h2>
                    <img src="images/user_icon.png" alt="User Icon">
                    <br>
                    <a href="registrationdetailswithorg.php">Sign Up</a>
                    <p id="checkmark"><img src="images/check.png"></p>
                    <p id="checkmark"><img src="images/check.png"></p>
                    <p id="checkmark"><img src="images/check.png"></p>
                </div>
            </div>
            
            <div class="cancel">
                <a class="cancel-button" href="MindanaoDataExchange.php">Cancel</a>
                <p class="backlogin">Already have an account? <a class= "backlogin" href="login.php">Log in</a></p>
            </div>
            
        </div>
    </div>
            .main-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 60px);
        }
        
        .signup-container {
            width: 1050px;
            background-color: white;
            border: 1px solid black;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 900px;
            margin-top:200px;
        }
        
        .content-row {
            margin-top: 250px;
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            row-gap: 100px; 
            gap: 20px; 
        }
        
        
        .description {
            flex: 1;
            padding: 0;

            margin-top: -10px;
       
        }
        .description h2 {
            font-size: 15px;
        }
        #checkmark{
            margin-top: 50px;
        }
        .account-individual, .account-org {
            flex: 1;
            margin-top: -160px;
            padding: 0px 20px;
            border: 1px solid black;
            text-align: center;
            height: 500px;
        }
        
        .account-individual img, .account-org img {
            width: 40px;
            margin-bottom: 10px;
        }
        
        .account-individual a, .account-org a {
            background-color: #0c1a36;
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .account-individual a:hover, .account-org a:hover {
            background-color: #142850;
        }
        
        .cancel {
            text-align: center;
            margin-top: 90px;
        }
        .cancel .cancel-button{
            font-size: 30px;
            background-color: #0c1a36;
            text-decoration: none;
            color: white;
            padding: 10px 100px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancel .cancel-button:hover {
            background-color: #142850;
        }
        .cancel p {
            margin-top: 10px;
        }