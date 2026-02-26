<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db.php';

//  API #1 – EXCHANGE RATE API (USD → MYR)


// Default value if API fails
$exchangeRate = "Unavailable";

$exchangeApiKey = "e654053973def6d9015b2ae9";

$exchangeUrl = "https://v6.exchangerate-api.com/v6/$exchangeApiKey/latest/USD";

$exchangeResponse = @file_get_contents($exchangeUrl);

if ($exchangeResponse !== false) {
    $exchangeData = json_decode($exchangeResponse, true);

    // Check if API response is successful
    if ($exchangeData && $exchangeData['result'] == "success") {
        $exchangeRate = $exchangeData['conversion_rates']['MYR'];
    }
}

//  API #2 – EASY PARCEL SHIPPING COST


// Default value if API fails
$shippingCost = "Unavailable";

// Your EasyParcel API key
$easyParcelKey = "EP-MFkF7EpvS";

// Prepare POST data for shipping rate checking
$postData = [
    "api" => $easyParcelKey,
    "bulk" => [
        [
            "pick_code" => "43000",   // origin postcode
            "send_code" => "50470",   // destination postcode
            "weight" => 1.0           // weight in kg
        ]
    ]
];

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, "https://demo.connect.easyparcel.my/?ac=EPRateCheckingBulk");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

// Execute request
$response = curl_exec($ch);

if (curl_errno($ch)) {
    $shippingCost = "cURL Error";
}

curl_close($ch);

// Decode JSON response
if ($response) {
    $shippingData = json_decode($response, true);

    // Extract shipping price if available
    if (isset($shippingData['result'][0]['rates'][0]['price'])) {
        $shippingCost = $shippingData['result'][0]['rates'][0]['price'];
    }
}

//  SQL JOIN QUERY 


$sql = "
SELECT customers.name, books.title, orders.order_date
FROM orders
JOIN customers ON orders.customer_id = customers.customer_id
JOIN books ON orders.book_id = books.book_id
";

$result = $conn->query($sql);

// Check for SQL error
if (!$result) {
    die("SQL Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Online Bookstore Order Monitoring System</title>
</head>
<body>

<h2>Online Bookstore Order Monitoring System</h2>

<!-- Display combined data in single table -->
<table border="1" cellpadding="10">
<tr>
    <th>Customer Name</th>
    <th>Book Title</th>
    <th>Order Date</th>
    <th>Shipping Cost (RM)</th>
    <th>Exchange Rate (USD → MYR)</th>
</tr>

<?php
// Loop through query result
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        echo "<tr>
            <td>".$row['name']."</td>
            <td>".$row['title']."</td>
            <td>".$row['order_date']."</td>
            <td>".$shippingCost."</td>
            <td>1 USD = RM ".$exchangeRate."</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='5'>No records found</td></tr>";
}
?>

</table>

</body>

</html>
