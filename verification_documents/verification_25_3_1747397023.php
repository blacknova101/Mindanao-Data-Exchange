<?php
$errors = array();
$successMessage = "";
$fullName = $email = $licenseNumber = $employeeID = $contactNumber = $dob = $passportNumber = $studentID = $tin = $plateNumber = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST["full_name"];
    $email = $_POST["email"];
    $licenseNumber = $_POST["license_number"];
    $employeeID = $_POST["employee_id"];
    $contactNumber = $_POST["contact_number"];
    $dob = $_POST["dob"];
    $passportNumber = $_POST["passport_number"];
    $studentID = $_POST["student_id"];
    $tin = $_POST["tin"];
    $plateNumber = $_POST["plate_number"];

    if (!preg_match("/^[A-Z][a-z]+(?: [A-Z][a-z]+)* [A-Z]\. [A-Z][a-z]+$/", $fullName)) {
        $errors['full_name'] = "Full Name format is incorrect. Example: Mark Francis S. Cruz";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email format is incorrect. Example: mark.s.cruz@example.com";
    }

    if (!preg_match("/^[A-Z]{3}-\d{4}-\d{4}$/", $licenseNumber)) {
        $errors['license_number'] = "License Number format is incorrect. Example: PRC-2020-1234";
    }

    if (!preg_match("/^[A-Z]{3}-\d{4}-\d{5}$/", $employeeID)) {
        $errors['employee_id'] = "Employee ID format is incorrect. Example: ECO-2022-04567";
    }

    if (!preg_match("/^\+63 \d{10}$/", $contactNumber)) {
        $errors['contact_number'] = "Contact Number format is incorrect. Example: +63 9123456789";
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) {
        $errors['dob'] = "Date of Birth format is incorrect. Example: 1995-08-15";
    }

    if (!preg_match("/^[A-Z]\d{7}$/", $passportNumber)) {
        $errors['passport_number'] = "Passport Number format is incorrect. Example: P1234567";
    }

    if (!preg_match("/^\d{4}-[a-zA-Z]{4}-\d{5}$/", $studentID)) {
        $errors['student_id'] = "Student ID format is incorrect. Example: 2023-USeP-54321";
    }

    if (!preg_match("/^\d{3}-\d{3}-\d{3}$/", $tin)) {
        $errors['tin'] = "TIN format is incorrect. Example: 123-456-789";
    }

    if (!preg_match("/^[A-Z]{3} \d{4}$/", $plateNumber)) {
        $errors['plate_number'] = "Vehicle Plate Number format is incorrect. Example: ABC 1234";
    }

    if (empty($errors)) {
        $successMessage = "CORRECT INPUTS";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Input Validation Activity</title>
    <style>

        body {
            font-size: 15px;
            padding: 15px;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 30px;
            display:flex;
            justify-content: center;
            gap: 50px;
            height: 100vh;
            margin: 0;
        }
        input {
            width: 350px;
            height: 30px;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 15px;
            border-radius: 10px;
        }
        button {
        color: black; 
        font-size: 18px;
        padding: 15px 32px; 
        border-radius: 8px;
        }
        .error { color: red; font-size: 14px; }
        .success, p { color: green; font-size: 16px; font-weight: bold; }
    </style>
    <script>
        let submittedData = [];
        function validateForm(event) {
            let errors = [];
            let fullName = document.getElementById("full_name").value;
            let email = document.getElementById("email").value;
            let licenseNumber = document.getElementById("license_number").value;
            let employeeID = document.getElementById("employee_id").value;
            let contactNumber = document.getElementById("contact_number").value;
            let dob = document.getElementById("dob").value;
            let passportNumber = document.getElementById("passport_number").value;
            let studentID = document.getElementById("student_id").value;
            let tin = document.getElementById("tin").value;
            let plateNumber = document.getElementById("plate_number").value;
            
            if (!/^[A-Z][a-z]+(?: [A-Z][a-z]+)* [A-Z]\. [A-Z][a-z]+$/.test(fullName)) {
                errors.push("Full Name format is incorrect. Example: Mark Francis S. Cruz");
            }
            if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                errors.push("Email format is incorrect. Example: mark.s.cruz@example.com");
            }
            if (!/^[A-Z]{3}-\d{4}-\d{4}$/.test(licenseNumber)) {
                errors.push("License Number format is incorrect. Example: PRC-2020-1234");
            }
            if (!/^[A-Z]{3}-\d{4}-\d{5}$/.test(employeeID)) {
                errors.push("Employee ID format is incorrect. Example: ECO-2022-04567");
            }
            if (!/^\+\d{2} \d{10}$/.test(contactNumber)) {
                errors.push("Contact Number format is incorrect. Example: +63 9123456789");
            }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dob)) {
                errors.push("Date of Birth format is incorrect. Example: 1995-08-15");
            }
            if (!/^[A-Z]\d{7}$/.test(passportNumber)) {
                errors.push("Passport Number format is incorrect. Example: P1234567");
            }
            if (!/^\d{4}-[a-zA-Z]{4}-\d{5}$/.test(studentID)) {
                errors.push("Student ID format is incorrect. Example: 2023-USeP-54321");
            }
            if (!/^\d{3}-\d{3}-\d{3}$/.test(tin)) {
                errors.push("TIN format is incorrect. Example: 123-456-789");
            }
            if (!/^[A-Z]{3} \d{4}$/.test(plateNumber)) {
                errors.push("Vehicle Plate Number format is incorrect. Example: ABC 1234");
            }
            
            if (errors.length > 0) {
                    alert(errors.join("\n"));
                    event.preventDefault();
                } else {
                    let submittedMessage = 
                        "Submitted Successfully!\n\n" +
                        "Full Name: " + fullName + "\n" +
                        "Email: " + email + "\n" +
                        "License Number: " + licenseNumber + "\n" +
                        "Employee ID: " + employeeID + "\n" +
                        "Contact Number: " + contactNumber + "\n" +
                        "Date of Birth: " + dob + "\n" +
                        "Passport Number: " + passportNumber + "\n" +
                        "Student ID: " + studentID + "\n" +
                        "TIN: " + tin + "\n" +
                        "Vehicle Plate Number: " + plateNumber;

                    alert(submittedMessage);
                }
            }
    </script>
</head>
<body>
    <?php if (!empty($successMessage)) { echo "<p class='success'>$successMessage</p>"; } ?>

    <form method="POST" onsubmit="validateForm(event)">
    <label>Full Name:</label><br>
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>">
    <span class="error"><?php echo $errors['full_name'] ?? ''; ?></span>
    <br>

    <label>Email:</label><br>
    <input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <span class="error"><?php echo $errors['email'] ?? ''; ?></span>
    <br>

    <label>License Number:</label><br>
    <input type="text" name="license_number" value="<?php echo htmlspecialchars($licenseNumber); ?>">
    <span class="error"><?php echo $errors['license_number'] ?? ''; ?></span>
    <br>

    <label>Employee ID:</label><br>
    <input type="text" name="employee_id" value="<?php echo htmlspecialchars($employeeID); ?>">
    <span class="error"><?php echo $errors['employee_id'] ?? ''; ?></span>
    <br>

    <label>Contact Number:</label><br>
    <input type="text" name="contact_number" value="<?php echo htmlspecialchars($contactNumber); ?>">
    <span class="error"><?php echo $errors['contact_number'] ?? ''; ?></span>
    <br>

    <label>Date of Birth:</label><br>
    <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>">
    <span class="error"><?php echo $errors['dob'] ?? ''; ?></span>
    <br>

    <label>Passport Number:</label><br>
    <input type="text" name="passport_number" value="<?php echo htmlspecialchars($passportNumber); ?>">
    <span class="error"><?php echo $errors['passport_number'] ?? ''; ?></span>
    <br>

    <label>Student ID:</label><br>
    <input type="text" name="student_id" value="<?php echo htmlspecialchars($studentID); ?>">
    <span class="error"><?php echo $errors['student_id'] ?? ''; ?></span>
    <br>

    <label>TIN:</label><br>
    <input type="text" name="tin" value="<?php echo htmlspecialchars($tin); ?>">
    <span class="error"><?php echo $errors['tin'] ?? ''; ?></span>
    <br>

    <label>Vehicle Plate Number:</label><br>
    <input type="text" name="plate_number" value="<?php echo htmlspecialchars($plateNumber); ?>">
    <span class="error"><?php echo $errors['plate_number'] ?? ''; ?></span>
    <br>
        <button type="submit">Submit</button>
    </form>

    <?php if (empty($errors) && !empty($successMessage)) : ?>
        <div class="submitted_data">
            <h3>Submitted Data:</h3>
            <p>Full Name: <?php echo htmlspecialchars($fullName); ?></p>
            <p>Email: <?php echo htmlspecialchars($email); ?></p>
            <p>License Number: <?php echo htmlspecialchars($licenseNumber); ?></p>
            <p>Employee ID: <?php echo htmlspecialchars($employeeID); ?></p>
            <p>Contact Number: <?php echo htmlspecialchars($contactNumber); ?></p>
            <p>Date of Birth: <?php echo htmlspecialchars($dob); ?></p>
            <p>Passport Number: <?php echo htmlspecialchars($passportNumber); ?></p>
            <p>Student ID: <?php echo htmlspecialchars($studentID); ?></p>
            <p>TIN: <?php echo htmlspecialchars($tin); ?></p>
            <p>Vehicle Plate Number: <?php echo htmlspecialchars($plateNumber); ?></p>
        </div>
    <?php endif; ?>
</body>
</html>
