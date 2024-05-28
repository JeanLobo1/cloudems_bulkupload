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
                if ($conn->query($sql) === TRUE) {
                    // Query executed successfully, continue
                } else {
                    // Log error message
                    echo "Error: " . $sql . "<br>" . $conn->error;
                    // Rollback transaction
                    $conn->query("ROLLBACK");
                    // Close the file handle
                    fclose($handle);
                    // Enable indexes
                    $conn->query("ALTER TABLE temporary_completedata ENABLE KEYS");
                    // Close the database connection
                    $conn->close();
                    // Exit function
                    return;
                }

                // Reset the SQL query
                $sql = "INSERT INTO temporary_completedata (`Date`, `Acedamic`, `Session`, `Alloted Category`, `Voucher Type`, `Voucher No`, `Roll No.`, `Admno`, `Status`, `Fee Category`, `Faculty`, `Program`, `Department`, `Batch`, `Receipt No.`, `Fee Head`, `Due Amount`, `Paid Amount`, `Conession Amount`, `Scholarship Amount`, `Reverse Concession Amount`, `Write Off Amount`, `Adjusted Amount`, `Refund Amount`, `Fund Transefer Amount`, `Remarks`) VALUES ";
            }

            $rowNumber++;
        }

        // Execute any remaining queries
        if ($counter % $batchSize != 0) {
            $sql = rtrim($sql, ',');
            if ($conn->query($sql) === TRUE) {
                // Query executed successfully, continue
            } else {
                // Log error message
                echo "Error: " . $sql . "<br>" . $conn->error;
                // Rollback transaction
                $conn->query("ROLLBACK");
                // Close the file handle
                fclose($handle);
                // Enable indexes
                $conn->query("ALTER TABLE temporary_completedata ENABLE KEYS");
                // Close the database connection
                $conn->close();
                // Exit function
                return;
            }
        }

        // Close the file handle
        fclose($handle);

        // Enable indexes
        $conn->query("ALTER TABLE temporary_completedata ENABLE KEYS");

        // Close the database connection
        $conn->close();

        echo "CSV imported successfully!";
    } else {
        echo "Error opening the CSV file.";
    }
}

function segregate($conn){
   
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

      // Truncate the table to remove all existing data
      if (!$conn->query("TRUNCATE TABLE branches")) {
        die("Error truncating the table: " . $conn->error);
    }
    
    // SQL query to select distinct faculties from temporary_completedata
    $sql = "SELECT DISTINCT `Faculty` FROM temporary_completedata WHERE `Faculty` IS NOT NULL";
    
    // Execute the query
    $result = $conn->query($sql);
    
    // Check if the query was successful
    if ($result) {
        // Check if there are any records returned
        if ($result->num_rows > 0) {
            // Loop through each row
            while ($row = $result->fetch_assoc()) {
                // Extract faculty name from the current row
                $faculty = $row['Faculty'];
    
                // SQL query to insert faculty into branches table
                $insertSql = "INSERT INTO branches (branch_name) VALUES ('$faculty')";
    
                // Execute the insert query
                if ($conn->query($insertSql) === TRUE) {
                    echo "Record inserted successfully for faculty: " . $faculty . "<br>";
                } else {
                    echo "Error inserting record: " . $conn->error . "<br>";
                }
            }
        } else {
            echo "No records found.";
        }
    } else {
        echo "Error executing query: " . $conn->error;
    }
    
  
    
}


function feecategory($conn) {
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Truncate the table to remove all existing data
    if (!$conn->query("TRUNCATE TABLE feecategory")) {
        die("Error truncating the table: " . $conn->error);
    }

    // SQL query to select distinct faculties from temporary_completedata
    $sql = "SELECT DISTINCT temp.`Fee Category`, br.`branch_id`
            FROM branches br
            CROSS JOIN (
                SELECT DISTINCT `Fee Category`
                FROM temporary_completedata
            ) temp
            WHERE temp.`Fee Category` != ''
            ORDER BY br.`branch_name`, temp.`Fee Category`";

    // Execute the query
    $result = $conn->query($sql);

    // Check if the query was successful
    if ($result) {
        // Check if there are any records returned
        if ($result->num_rows > 0) {
            // Loop through each row
            while ($row = $result->fetch_assoc()) {
                // Extract fee category and branch_id from the current row
                $FeeCategory = $row['Fee Category'];
                $branch_id = $row['branch_id'];

                // SQL query to insert fee category into feecategory table
                $insertSql = "INSERT INTO feecategory (Fee_category, Br_id) VALUES ('$FeeCategory', '$branch_id')";

                // Execute the insert query
                if ($conn->query($insertSql) === TRUE) {
                    echo "Record inserted successfully for Fee Category: " . $FeeCategory . " with Branch ID: " . $branch_id . "<br>";
                } else {
                    echo "Error inserting record: " . $conn->error . "<br>";
                }
            }
        } else {
            echo "No records found.";
        }
    } else {
        echo "Error executing query: " . $conn->error;
    }
}




function addStaticData($conn) {
    // Truncate the table to remove all existing data
    if (!$conn->query("TRUNCATE TABLE entrymode")) {
        die("Error truncating the table: " . $conn->error);
    }

    // Array of static data to insert
    $staticData = [
        ['Entry_modename' => 'DUE', 'crdr' => 'D', 'entrymodeno' => 0],
        ['Entry_modename' => 'REVDUE', 'crdr' => 'C', 'entrymodeno' => 12],
        ['Entry_modename' => 'SCHOLARSHIP', 'crdr' => 'C', 'entrymodeno' => 15],
        ['Entry_modename' => 'SCHOLARSHIPREV/REVCONCESSION', 'crdr' => 'D', 'entrymodeno' => 16],
        ['Entry_modename' => 'CONCESSION', 'crdr' => 'C', 'entrymodeno' => 16],
        ['Entry_modename' => 'RCPT', 'crdr' => 'C', 'entrymodeno' => 0],
        ['Entry_modename' => 'REVRCPT', 'crdr' => 'D', 'entrymodeno' => 0],
        ['Entry_modename' => 'JV', 'crdr' => 'C', 'entrymodeno' => 14],
        ['Entry_modename' => 'REVJV', 'crdr' => 'D', 'entrymodeno' => 14],
        ['Entry_modename' => 'PMT', 'crdr' => 'D', 'entrymodeno' => 1],
        ['Entry_modename' => 'REVPMT', 'crdr' => 'C', 'entrymodeno' => 1],
        ['Entry_modename' => 'Fundtransfer', 'crdr' => '+ ve and -ve', 'entrymodeno' => 1]
    ];

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO entrymode (Entry_modename, crdr, entrymodeno) VALUES (?, ?, ?)");

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        die("Error preparing the statement: " . $conn->error);
    }

    // Bind parameters and execute the statement for each row
    foreach ($staticData as $data) {
        $stmt->bind_param("ssi", $data['Entry_modename'], $data['crdr'], $data['entrymodeno']);
        if (!$stmt->execute()) {
            echo "Error inserting data: " . $stmt->error . "<br>";
        }
    }

    // Close the statement
    $stmt->close();

    echo "Static data inserted successfully!";
}



// Check if the form is submitted
if (isset($_POST["submit"]))
{
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
         addStaticData($conn);
        feecategory($conn);
    // $conn->close();
    } else {
        echo "Error uploading the CSV file.";
    }
}
?>
