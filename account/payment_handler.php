<?php
require_once '../include/header.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['temp_application'])) {
    $_SESSION['error'] = 'Invalid access. Please start the application process again.';
    echo "<script>window.location.href = 'application_form.php';</script>";
    exit();
}

try {
    $appData = $_SESSION['temp_application'];
    $appData['currency'] = 'INR';
    $amount = $appData['amount'] * 100; // Razorpay expects amount in paise

    // Razorpay config
    $razorpay_key_id = 'rzp_test_2IuDwSIGg7jgNN';
    $razorpay_key_secret = 'ZNxYuUEuVCyQCfzPKDXvhOhP';

    // Generate new order number
    $sql = "SELECT order_no FROM orders ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastOrder = $result->fetch_assoc();
    $newOrderNo = $lastOrder ? str_pad((intval($lastOrder['order_no']) + 1), 6, '0', STR_PAD_LEFT) : '000001';

    $customerDetails = json_encode([
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'mobile' => $_SESSION['mobile']
    ]);

    // Insert order in DB
    $stmt = $conn->prepare("INSERT INTO orders 
        (order_no, user_id, application_number, customer_details, service_type, amount, currency, order_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param(
        "sisssds",
        $newOrderNo,
        $_SESSION['user_id'],
        $appData['application_number'],
        $customerDetails,
        $appData['service_type'],
        $appData['amount'],
        $appData['currency']
    );

    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Failed to create order: ' . $stmt->error;
        echo "<script>window.location.href = 'new_application.php';</script>";
        exit();
    }

    $orderId = $conn->insert_id;

    // Create Razorpay order
    $data = [
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => $newOrderNo,
        'payment_capture' => 1
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_USERPWD, $razorpay_key_id . ":" . $razorpay_key_secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $razorpayOrder = json_decode($response, true);

} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    echo "<script>window.location.href = 'new_application.php';</script>";
    exit();
}
?>


<div class="container payment-container">
    <div class="card  payment-card">
        <div class="card-header text-center text-white">
            <h4 class="mb-0">Complete Your Payment</h4>
        </div>
        <div class="payment-body">
            <div class="payment-detail">
                <span>Service Type:</span>
                <strong><?php echo htmlspecialchars($appData['application_type']); ?></strong>
            </div>
            <div class="payment-detail">
                <span>Application No:</span>
                <strong>#<?php echo htmlspecialchars($appData['application_number']); ?></strong>
            </div>
            <div class="payment-amount">
                ₹<?php echo number_format($appData['amount'], 2); ?>
                <div class="text-muted small mt-1">Total Amount Payable</div>
            </div>
            <button id="rzp-button" class="btn custom-btn btn-lg w-100  ">
                <i class="fas fa-lock me-2 text-white"></i>Pay Now
            </button>
            <p class="text-muted small mt-3 text-center">
                <i class="fas fa-lock me-1"></i> Secure payment powered by Razorpay
            </p>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('rzp-button').onclick = function(e) {
    var options = {
        "key": "<?php echo $razorpay_key_id; ?>",
        "amount": "<?php echo $amount; ?>",
        "currency": "INR",
        "name": "Your Company Name",
        "description": "<?php echo htmlspecialchars($appData['application_type']); ?>",
        "order_id": "<?php echo $razorpayOrder['id']; ?>",
        "handler": function (response){
            // Redirect to a server-side handler to verify payment and update DB
            window.location.href = "dashboard.php?payment_id=" + response.razorpay_payment_id + "&order_id=<?php echo $newOrderNo; ?>";
        },
        "prefill": {
            "name": "<?php echo htmlspecialchars($_SESSION['name']); ?>",
            "email": "<?php echo htmlspecialchars($_SESSION['email']); ?>",
            "contact": "<?php echo htmlspecialchars($_SESSION['mobile']); ?>"
        },
        "theme": { "color": "#3399cc" }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
    e.preventDefault();
}
</script>

<?php include '../include/footer.php'; ?>
