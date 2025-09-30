<?php

namespace SmartRestaurant\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SmartRestaurant\Core\Database;
use PDO;

/**
 * M-Pesa Payment Service - Handles M-Pesa STK Push and payment processing
 */
class MpesaService
{
    private Client $httpClient;
    private PDO $pdo;
    private string $consumerKey;
    private string $consumerSecret;
    private string $businessShortCode;
    private string $passkey;
    private string $callbackUrl;
    private string $environment;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->pdo = Database::getInstance()->getConnection();
        
        // Load M-Pesa configuration from environment or config
        $this->consumerKey = $_ENV['MPESA_CONSUMER_KEY'] ?? 'your_consumer_key';
        $this->consumerSecret = $_ENV['MPESA_CONSUMER_SECRET'] ?? 'your_consumer_secret';
        $this->businessShortCode = $_ENV['MPESA_BUSINESS_SHORT_CODE'] ?? '174379';
        $this->passkey = $_ENV['MPESA_PASSKEY'] ?? 'your_passkey';
        $this->callbackUrl = $_ENV['MPESA_CALLBACK_URL'] ?? 'https://yourdomain.com/api/mpesa/callback';
        $this->environment = $_ENV['MPESA_ENVIRONMENT'] ?? 'sandbox'; // sandbox or production
    }

    /**
     * Get M-Pesa access token
     */
    private function getAccessToken(): string
    {
        try {
            $url = $this->getBaseUrl() . '/oauth/v1/generate?grant_type=client_credentials';
            
            $response = $this->httpClient->get($url, [
                'auth' => [$this->consumerKey, $this->consumerSecret],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['access_token'])) {
                throw new \RuntimeException('Failed to get M-Pesa access token');
            }

            return $data['access_token'];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('M-Pesa authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Initiate STK Push payment
     */
    public function stkPush(array $paymentData): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

            $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';

            $requestData = [
                'BusinessShortCode' => $this->businessShortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)$paymentData['amount'],
                'PartyA' => $this->formatPhoneNumber($paymentData['phone_number']),
                'PartyB' => $this->businessShortCode,
                'PhoneNumber' => $this->formatPhoneNumber($paymentData['phone_number']),
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => $paymentData['order_number'] ?? 'ORDER',
                'TransactionDesc' => $paymentData['description'] ?? 'Restaurant Payment'
            ];

            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Store transaction record
            if (isset($responseData['CheckoutRequestID'])) {
                $this->storeTransaction([
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                    'order_id' => $paymentData['order_id'] ?? null,
                    'phone_number' => $paymentData['phone_number'],
                    'amount' => $paymentData['amount'],
                    'status' => 'pending',
                    'request_data' => json_encode($requestData),
                    'response_data' => json_encode($responseData)
                ]);
            }

            return $responseData;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('STK Push failed: ' . $e->getMessage());
        }
    }

    /**
     * Query STK Push transaction status
     */
    public function queryTransaction(string $checkoutRequestId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);

            $url = $this->getBaseUrl() . '/mpesa/stkpushquery/v1/query';

            $requestData = [
                'BusinessShortCode' => $this->businessShortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId
            ];

            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Update transaction status
            $this->updateTransactionStatus($checkoutRequestId, $responseData);

            return $responseData;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Transaction query failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle M-Pesa callback
     */
    public function handleCallback(array $callbackData): array
    {
        try {
            $this->pdo->beginTransaction();

            $stkCallback = $callbackData['Body']['stkCallback'] ?? null;
            
            if (!$stkCallback) {
                throw new \RuntimeException('Invalid callback data');
            }

            $checkoutRequestId = $stkCallback['CheckoutRequestID'];
            $resultCode = $stkCallback['ResultCode'];
            $resultDesc = $stkCallback['ResultDesc'];

            $transactionData = [
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'callback_data' => json_encode($callbackData)
            ];

            // If payment was successful
            if ($resultCode == 0) {
                $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
                
                foreach ($callbackMetadata as $item) {
                    switch ($item['Name']) {
                        case 'Amount':
                            $transactionData['amount'] = $item['Value'];
                            break;
                        case 'MpesaReceiptNumber':
                            $transactionData['mpesa_receipt_number'] = $item['Value'];
                            break;
                        case 'TransactionDate':
                            $transactionData['transaction_date'] = $item['Value'];
                            break;
                        case 'PhoneNumber':
                            $transactionData['phone_number'] = $item['Value'];
                            break;
                    }
                }

                $transactionData['status'] = 'completed';
                
                // Update order payment status
                $this->updateOrderPaymentStatus($checkoutRequestId, 'paid', $transactionData['mpesa_receipt_number'] ?? null);
            } else {
                $transactionData['status'] = 'failed';
                
                // Update order payment status
                $this->updateOrderPaymentStatus($checkoutRequestId, 'failed');
            }

            // Update transaction record
            $this->updateTransaction($checkoutRequestId, $transactionData);

            $this->pdo->commit();

            return [
                'ResultCode' => 0,
                'ResultDesc' => 'Success'
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            
            // Log error but return success to M-Pesa to avoid retries
            error_log('M-Pesa callback error: ' . $e->getMessage());
            
            return [
                'ResultCode' => 0,
                'ResultDesc' => 'Success'
            ];
        }
    }

    /**
     * Get payment transaction by checkout request ID
     */
    public function getTransaction(string $checkoutRequestId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payment_transactions 
            WHERE transaction_id = ?
        ");
        
        $stmt->execute([$checkoutRequestId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all transactions for an order
     */
    public function getOrderTransactions(int $orderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payment_transactions 
            WHERE order_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate payment amount
     */
    public function validatePayment(int $orderId, float $expectedAmount): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(amount) as total_paid
            FROM payment_transactions 
            WHERE order_id = ? AND status = 'completed'
        ");
        
        $stmt->execute([$orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalPaid = (float)($result['total_paid'] ?? 0);
        
        return $totalPaid >= $expectedAmount;
    }

    /**
     * Store new transaction
     */
    private function storeTransaction(array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_transactions (
                transaction_id, order_id, payment_method, amount, currency,
                status, gateway_response, mpesa_phone_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['checkout_request_id'],
            $data['order_id'] ?? null,
            'mpesa',
            $data['amount'],
            'KES',
            $data['status'],
            $data['response_data'],
            $data['phone_number']
        ]);
    }

    /**
     * Update transaction
     */
    private function updateTransaction(string $checkoutRequestId, array $data): void
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'result_code', 'result_desc', 'amount', 'mpesa_receipt_number',
            'transaction_date', 'phone_number', 'status', 'callback_data'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (!empty($fields)) {
            $params[] = $checkoutRequestId;
            
            $stmt = $this->pdo->prepare("
                UPDATE payment_transactions 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE transaction_id = ?
            ");
            
            $stmt->execute($params);
        }
    }

    /**
     * Update transaction status from query
     */
    private function updateTransactionStatus(string $checkoutRequestId, array $responseData): void
    {
        $status = 'pending';
        
        if (isset($responseData['ResultCode'])) {
            $status = $responseData['ResultCode'] == 0 ? 'completed' : 'failed';
        }

        $stmt = $this->pdo->prepare("
            UPDATE payment_transactions 
            SET status = ?, gateway_response = ?, updated_at = CURRENT_TIMESTAMP
            WHERE transaction_id = ?
        ");
        
        $stmt->execute([$status, json_encode($responseData), $checkoutRequestId]);
    }

    /**
     * Update order payment status
     */
    private function updateOrderPaymentStatus(string $checkoutRequestId, string $paymentStatus, string $receiptNumber = null): void
    {
        $updateFields = ['payment_status = ?'];
        $params = [$paymentStatus];

        if ($receiptNumber) {
            $updateFields[] = 'mpesa_transaction_id = ?';
            $params[] = $receiptNumber;
        }

        $params[] = $checkoutRequestId;

        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP
            WHERE id IN (
                SELECT order_id FROM payment_transactions 
                WHERE transaction_id = ? AND order_id IS NOT NULL
            )
        ");
        
        $stmt->execute($params);
    }

    /**
     * Format phone number for M-Pesa
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle different formats
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            // Convert 0712345678 to 254712345678
            return '254' . substr($phone, 1);
        } elseif (strlen($phone) == 9) {
            // Convert 712345678 to 254712345678
            return '254' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            // Already in correct format
            return $phone;
        }
        
        throw new \InvalidArgumentException('Invalid phone number format: ' . $phoneNumber);
    }

    /**
     * Get M-Pesa API base URL
     */
    private function getBaseUrl(): string
    {
        return $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Generate transaction reference
     */
    public function generateTransactionReference(string $prefix = 'TXN'): string
    {
        return $prefix . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * Refund transaction (if supported by M-Pesa)
     */
    public function refundTransaction(string $transactionId, float $amount, string $reason = 'Refund'): array
    {
        // Note: M-Pesa refunds typically require manual processing
        // This method would integrate with M-Pesa's reversal API if available
        
        throw new \RuntimeException('M-Pesa refunds require manual processing through Safaricom portal');
    }
}
