<?php

// Function to process and import the CSV data into the database
function importCSV($file, $conn)
{
    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

      // Reset auto-increment value to 1
      $conn->query("ALTER TABLE temporary_completedata AUTO_INCREMENT = 1");


    // Disable autocommit and indexes for faster inserts
    $conn->query("SET autocommit = 0");
    $conn->query("ALTER TABLE temporary_completedata DISABLE KEYS");

    // Use LOAD DATA INFILE for faster bulk insert
    $loadDataQuery = "
        LOAD DATA LOCAL INFILE '$file'
        INTO TABLE temporary_completedata
        FIELDS TERMINATED BY ',' 
        ENCLOSED BY '\"'
        LINES TERMINATED BY '\\n'
        IGNORE 6 LINES
        (@dummy,`Date`, `Acedamic`, `Session`, `Alloted Category`, `Voucher Type`, `Voucher No`, `Roll No.`, `Admno`, `Status`, `Fee Category`, `Faculty`, `Program`, `Department`, `Batch`, `Receipt No.`, `Fee Head`, `Due Amount`, `Paid Amount`, `Conession Amount`, `Scholarship Amount`, `Reverse Concession Amount`, `Write Off Amount`, `Adjusted Amount`, `Refund Amount`, `Fund Transefer Amount`, `Remarks`)
    ";

    if ($conn->query($loadDataQuery) === TRUE) {
        echo "CSV imported successfully!";
    } else {
        echo "Error importing CSV: " . $conn->error;
    }

    // Enable indexes
    $conn->query("ALTER TABLE temporary_completedata ENABLE KEYS");
    $conn->query("COMMIT");
}

// Check if the form is submitted
if (isset($_POST["submit"])) {
    // Handle the file upload
    if ($_FILES["csvfile"]["error"] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES["csvfile"]["tmp_name"];
        $name = basename($_FILES["csvfile"]["name"]);
        $upload_dir = "uploads/";
        $file_path = $upload_dir . $name;

        move_uploaded_file($tmp_name, $file_path);

        // Database connection details
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "cloudems";

        // Create a database connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset('utf8mb4');

        // Enable local_infile
        $conn->options(MYSQLI_OPT_LOCAL_INFILE, true);

        importCSV($file_path, $conn);

        $conn->close();
    } else {
        echo "Error uploading the CSV file.";
    }
}
?>
