<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Selection - MDX</title>
    <style>
                body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #E3F2FD;
            min-height: 100vh;
            overflow: hidden;
        }
        
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 5%;
            background-color: #0c1a36;
            color: white;
        }
        
        .logo img {
            height: 50px;
        }
        
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
        
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="MDX Logo">
        </div>
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
</body>
</html>
