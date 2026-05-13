<?php
$con = mysqli_connect('localhost', 'root', '', 'shopping');
if (mysqli_connect_errno()) {
    die("Failed to connect: " . mysqli_connect_error());
}

echo "<h3>Tables in database:</h3>";
$result = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . "<br>";
}

echo "<h3>Structure of 'category':</h3>";
if ($result = mysqli_query($con, "DESCRIBE category")) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Error describing category: " . mysqli_error($con);
}
?>
