<?php

// Function to process and import the CSV data into the database
function importCSV($file, $conn)
{
    

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->query("ALTER TABLE temporary_completedata AUTO_INCREMENT = 1");
    // Disable autocommit and indexes for faster inserts
    $conn->query("SET autocommit = 0");
    $conn->query("ALTER TABLE temporary_completedata DISABLE KEYS");

    // Read the CSV file
    if (($handle = fopen($file, "r")) !== false) {
        // Set the batch size for processing
        $batchSize = 1000;
        $counter = 0;
        $rowNumber = 0;
        $sql = "INSERT INTO temporary_completedata (`Date`, `Acedamic`, `Session`, `Alloted Category`, `Voucher Type`, `Voucher No`, `Roll No.`, `Admno`, `Status`, `Fee Category`, `Faculty`, `Program`, `Department`, `Batch`, `Receipt No.`, `Fee Head`, `Due Amount`, `Paid Amount`, `Conession Amount`, `Scholarship Amount`, `Reverse Concession Amount`, `Write Off Amount`, `Adjusted Amount`, `Refund Amount`, `Fund Transefer Amount`, `Remarks`) VALUES ";

        // Loop through the CSV data
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Skip the first 6 rows (rows 0 to 5)
            if ($rowNumber < 5) {
                $rowNumber++;
                continue;
            }

            // Skip the header row (row 6)
            if ($rowNumber == 5) {
                $rowNumber++;
                continue;
            }

            // Prepare the values for insertion
            $values = "('" . implode("','", array_map([$conn, 'real_escape_string'], array_slice($data, 1, 26))) . "'),";

            // Append the values to the SQL query
            $sql .= $values;

            // Increment the counter
            $counter++;

            // Check if the batch size has been reached
            if ($counter % $batchSize == 0) {
                // Remove the trailing comma and execute the query
                $sql = rtrim($sql, ',');
                $conn->query($sql);

                // Reset the SQL query
                $sql = "INSERT INTO temporary_completedata (`Date`, `Acedamic`, `Session`, `Alloted Category`, `Voucher Type`, `Voucher No`, `Roll No.`, `Admno`, `Status`, `Fee Category`, `Faculty`, `Program`, `Department`, `Batch`, `Receipt No.`, `Fee Head`, `Due Amount`, `Paid Amount`, `Conession Amount`, `Scholarship Amount`, `Reverse Concession Amount`, `Write Off Amount`, `Adjusted Amount`, `Refund Amount`, `Fund Transefer Amount`, `Remarks`) VALUES ";

                // Free up memory
                $conn->query("COMMIT");
                $conn->query("SET autocommit = 0");
            }

            $rowNumber++;
        }

        // Execute any remaining queries
        if ($counter % $batchSize != 0) {
            $sql = rtrim($sql, ',');
            $conn->query($sql);
            $conn->query("COMMIT");
        }

        // Close the file handle
        fclose($handle);

        // Enable indexes
        $conn->query("ALTER TABLE temporary_completedata ENABLE KEYS");

        // Close the database connection
        // $conn->close();

        echo "CSV imported successfully!";
    } else {
        echo "Error opening the CSV file.";
    }
}


function segregate($conn){
    

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

        importCSV($file_path,$conn);
         segregate($conn);
    $conn->close();
    } else {
        echo "Error uploading the CSV file.";
    }
}
?>
