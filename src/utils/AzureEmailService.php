<?php


namespace Fixpoint\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AzureEmailService
{
    /** @var string */
    private static $endpoint = 'https://fixpoit-mailler.unitedstates.communication.azure.com/';
    /** @var string */
    private static $host = '';
    /** @var string */
    private static $accessKey = 'DlCOIqLviNq3RKnhC10g61vOZ46nN3qtE4a3DR5IRke2vLzHJ6jnJQQJ99BHACULyCpQCLZ2AAAAAZCSSthx';
    /** @var string */
    private static $senderAddress = '';
    /** @var Client|null */
    private static $httpClient = null;
    /** @var string */
    private static $apiVersion = '2023-03-31'; // Current public API version at time of writing.

    /**
     * Configure the service once. Safe to call multiple times (idempotent if values unchanged).
     * @param string|null $connectionString e.g. endpoint=...;accesskey=...
     * @param string|null $sender Verified sender address (e.g. 'DoNotReply@yourdomain.com')
     */
    public static function configure(?string $connectionString = null, ?string $sender = null): void
    {
        
        $connectionString = $connectionString ?: getenv('AZURE_COMMUNICATION_CONNECTION_STRING');
        $sender = $sender ?: getenv('AZURE_EMAIL_SENDER') ?: '';

        if (!empty($connectionString)) {
            $parts = array_filter(array_map('trim', explode(';', $connectionString)));
            $map = [];
            foreach ($parts as $p) {
                if (strpos($p, '=') !== false) {
                    [$k, $v] = explode('=', $p, 2);
                    $map[strtolower($k)] = $v;
                }
            }
            if (!empty($map['endpoint'])) {
                self::$endpoint = rtrim($map['endpoint'], '/');
                $urlParts = parse_url(self::$endpoint);
                self::$host = $urlParts['host'] ?? '';
            }
            if (!empty($map['accesskey'])) {
                self::$accessKey = $map['accesskey'];
            }
        }
        if (!empty($sender)) {
            self::$senderAddress = $sender;
        }
        if (!self::$httpClient || (string)self::$httpClient->getConfig('base_uri') !== self::$endpoint) {
             self::$httpClient = new Client([
                'base_uri' => self::$endpoint,
                'timeout'  => 10,
            ]);
        }
    }

    private static function ensureConfigured(): void
    {
        if (!self::$endpoint || !self::$accessKey) {
            throw new \RuntimeException('AzureEmailService not configured. Call AzureEmailService::configure with a valid connection string first.');
        }
        if (!self::$senderAddress) {
            throw new \RuntimeException('Sender address not set. Provide a verified email/domain as second parameter or AZURE_EMAIL_SENDER env variable.');
        }
    }

    private static function buildOtpHtml(string $otp, string $username = ''): string
    {
        $year = date('Y');
        $otpEsc = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $usernameEsc = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        $greeting = !empty($username) ? "Hey {$usernameEsc}," : "Hey there,";
        
        // Split OTP digits for individual styling
        $otpDigits = str_split($otpEsc);
        $styledDigits = '';
        foreach ($otpDigits as $digit) {
            $styledDigits .= '<span style="color: #d63384; font-weight: bold; margin: 0 8px;">' . $digit . '</span>';
        }
        
        return <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f0f0; padding: 40px 20px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 500px; margin: auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <!-- Header with logo and branding -->
    <tr>
      <td style="background: #cec31eff; padding: 24px; text-align: center;">
        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 12px;">
          <img src="https://sahqlmlmflamaghbwkin.supabase.co/storage/v1/object/public/images/faviccon.ico" alt="Fixpoint Logo" style="width: 40px; height: 40px; vertical-align: middle; padding-right: 8px;" />
          <h1 style="color: #333333; margin: 0; font-size: 24px; font-weight: 600;">FixPoint<sup style="font-size: 14px;">®</sup></h1>
        </div>
      </td>
    </tr>
    <!-- Main content -->
    <tr>
      <td style="padding: 40px 40px 20px 40px; text-align: center;">
        <h2 style="color: #333; margin: 0 0 24px 0; font-size: 28px; font-weight: 400;">Your OTP</h2>
        <p style="color: #666; font-size: 16px; margin: 0 0 8px 0; text-align: left;">{$greeting}</p>
        <p style="color: #666; font-size: 14px; margin: 0 0 32px 0; line-height: 1.5;">Use the following OTP to login. OTP is valid for <strong>5 minutes</strong>. Do not share this code with others.</p>
        
        <!-- OTP Display -->
        <div style="margin: 32px 0; font-size: 48px; font-weight: bold; letter-spacing: 12px; line-height: 1;">
          {$styledDigits}
        </div>
        
        <p style="color: #999; font-size: 12px; margin-top: 40px;">If you did not request this code, please ignore this email.</p>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style="padding: 20px 40px 30px 40px; text-align: center; color: #aaa; font-size: 11px; border-top: 1px solid #f0f0f0;">
        &copy; {$year} Fixpoint Concepts. All rights reserved.
      </td>
    </tr>
  </table>
</div>
HTML;
    }

    /** Build HTML for default password email. */
    private static function buildDefaultPasswordHtml(string $password): string
    {
        $year = date('Y');
        $pwEsc = htmlspecialchars($password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        // Split password characters for individual styling
        $passwordChars = str_split($pwEsc);
        $styledPassword = '';
        foreach ($passwordChars as $char) {
            $styledPassword .= '<span style="color: #d63384; font-weight: bold; margin: 0 4px;">' . $char . '</span>';
        }
        
        return <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f0f0; padding: 40px 20px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 500px; margin: auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <!-- Header with logo and branding -->
    <tr>
      <td style="background: #cec31eff; padding: 24px; text-align: center;">
        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 12px;">
          <img src="https://sahqlmlmflamaghbwkin.supabase.co/storage/v1/object/public/images/faviccon.ico" alt="Fixpoint Logo" style="width: 40px; height: 40px; vertical-align: middle; padding-right: 8px;" />
          <h1 style="color: #333333; margin: 0; font-size: 24px; font-weight: 600;">FixPoint<sup style="font-size: 14px;">®</sup></h1>
        </div>
      </td>
    </tr>
    <!-- Main content -->
    <tr>
      <td style="padding: 40px 40px 20px 40px; text-align: center;">
        <h2 style="color: #333; margin: 0 0 24px 0; font-size: 28px; font-weight: 400;">Your Default Password</h2>
        <p style="color: #666; font-size: 16px; margin: 0 0 8px 0; text-align: left;">Welcome to FixPoint!</p>
        <p style="color: #666; font-size: 14px; margin: 0 0 32px 0; line-height: 1.5;">Below is your default password. Please <strong>change it after your first login</strong> for security purposes.</p>
        
        <!-- Password Display -->
        <div style="margin: 32px 0; font-size: 24px; font-weight: bold; letter-spacing: 4px; line-height: 1;">
          {$styledPassword}
        </div>
        
        <p style="color: #999; font-size: 12px; margin-top: 40px;">If you did not request this account, please contact support.</p>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style="padding: 20px 40px 30px 40px; text-align: center; color: #aaa; font-size: 11px; border-top: 1px solid #f0f0f0;">
        &copy; {$year} Fixpoint Concepts. All rights reserved.
      </td>
    </tr>
  </table>
</div>
HTML;
    }

    /** Public: Send OTP email. Returns operation info array. */
    public static function sendOtpEmail(string $recipient, string $otp, string $username = ''): array
    {
        $html = self::buildOtpHtml($otp, $username);
        $greeting = !empty($username) ? "Hey {$username}," : "Hey there,";
        $plain = "{$greeting}\n\nUse the following OTP to login. OTP is valid for 5 minutes. Do not share this code with others.\n\nYour OTP: {$otp}\n\nIf you did not request this code, please ignore this email.\n\n© Fixpoint Concepts. All rights reserved.";
        return self::sendEmail([$recipient], 'Your OTP', $plain, $html);
    }

    /** Public: Send default password email. */
    public static function sendDefaultPasswordEmail(string $recipient, string $password): array
    {
        $html = self::buildDefaultPasswordHtml($password);
        $plain = "Your default password is: {$password}. Please change it after your first login.";
        return self::sendEmail([$recipient], 'Your Default Password', $plain, $html);
    }

    /** Generic send for reuse. */
    public static function sendEmail(array $to, string $subject, string $plainText, string $htmlBody, array $cc = [], array $bcc = []): array
    {
        self::ensureConfigured();
        $recipients = [ 'to' => [] ];
        foreach ($to as $addr) { $recipients['to'][] = ['address' => $addr]; }
        if ($cc) { $recipients['cc'] = array_map(fn($a) => ['address' => $a], $cc); }
        if ($bcc) { $recipients['bcc'] = array_map(fn($a) => ['address' => $a], $bcc); }

        $payload = [
            'senderAddress' => self::$senderAddress,
            'recipients' => $recipients,
            'content' => [
                'subject' => $subject,
                'plainText' => $plainText,
                'html' => $htmlBody,
            ],
        ];

        $path = '/emails:send';
        return self::performRequest('POST', $path, $payload);
    }

    /** Low-level HTTP request with HMAC auth. */
    private static function performRequest(string $method, string $path, ?array $jsonBody = null): array
    {
        $query = 'api-version=' . urlencode(self::$apiVersion);
        $pathAndQuery = $path . '?' . $query; // The path and query must be combined for the signature.

        $body = $jsonBody ? json_encode($jsonBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $contentHash = base64_encode(hash('sha256', $body, true));
        $dateRfc1123 = gmdate('D, d M Y H:i:s') . ' GMT';

        // *** THIS IS THE CORRECTED PART ***
        // The correct string to sign is a specific format:
        // VERB + "\n" + /path-and-query + "\n" + <semicolon-separated-header-values>
        // The header values must be in the same order as the 'SignedHeaders' list in the Authorization header.
        $stringToSign = strtoupper($method) . "\n"
                      . $pathAndQuery . "\n"
                      . $dateRfc1123 . ";" . self::$host . ";" . $contentHash;

        $hmac = base64_encode(hash_hmac('sha256', $stringToSign, base64_decode(self::$accessKey), true));
        
        // Note: The 'SignedHeaders' part tells Azure which headers are part of the signature, and in what order.
        $authorization = 'HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature=' . $hmac;

        $headers = [
            'Content-Type' => 'application/json',
            'x-ms-date' => $dateRfc1123,
            'x-ms-content-sha256' => $contentHash,
            'Authorization' => $authorization,
            'host' => self::$host, // It's good practice to explicitly include the host header
        ];

        try {
            $response = self::$httpClient->request($method, $pathAndQuery, [ // Use $pathAndQuery here
                'headers' => $headers,
                'body' => $body,
                'http_errors' => false,
            ]);
            $status = $response->getStatusCode();
            $respHeaders = $response->getHeaders();
            $respBody = (string)$response->getBody();
            $decoded = null;
            if ($respBody !== '') {
                $decoded = json_decode($respBody, true);
            }
            if ($status < 200 || $status >= 300) {
                // Keep the detailed error for debugging
                throw new \RuntimeException('Azure Email send failed: HTTP ' . $status . ' Body: ' . $respBody);
            }
            return [
                'status' => $status,
                'operationLocation' => self::firstHeader($respHeaders, 'Operation-Location'),
                'requestId' => self::firstHeader($respHeaders, 'x-ms-request-id'),
                'rawHeaders' => $respHeaders,
                'rawBody' => $respBody,
                'json' => $decoded,
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('HTTP client error sending Azure email: ' . $e->getMessage(), 0, $e);
        }
    }
    private static function firstHeader(array $headers, string $name): ?string
    {
        foreach ($headers as $k => $vals) {
            if (strtolower($k) === strtolower($name)) {
                return $vals[0] ?? null;
            }
        }
        return null;
    }

    /**
     * Poll operation status using full Operation-Location URL or just operation ID.
     * @param string $operation Either the full URL from operationLocation OR just the operationId portion.
     */
    public static function getOperationStatus(string $operation): array
    {
        self::ensureConfigured();
        // If it's a full URL, parse path+query; else build path.
        if (str_starts_with($operation, 'http://') || str_starts_with($operation, 'https://')) {
            $parts = parse_url($operation);
            $path = $parts['path'] ?? '';
            $query = $parts['query'] ?? null;
            // Reuse performRequest: it expects just path; we append query inside.
            if ($query) {
                // performRequest always appends api-version, so if existing query has api-version we'll strip it here
                // Simpler: if query already includes api-version, override apiVersion temporarily
                parse_str($query, $qsArr);
                if (isset($qsArr['api-version'])) {
                    self::$apiVersion = $qsArr['api-version'];
                }
            }
        } else {
            $operationId = trim($operation);
            $path = '/emails/operations/' . $operationId;
        }
        return self::performRequest('GET', $path);
    }

    /** Convenience: supply just operation ID */
    public static function getOperationStatusById(string $operationId): array
    {
        return self::getOperationStatus($operationId);
    }

    /**
     * Send a service report email to a branch (invoice-style)
     * @param string $recipientEmail The branch email address
     * @param array $serviceData Service report data
     * @param array $branchData Branch information
     * @param array $clientData Client information
     * @param array $technicianData Technician information
     * @return array Result with success/error status
     */
    public static function sendServiceReportEmail(
        string $recipientEmail,
        array $serviceData,
        array $branchData,
        array $clientData,
        array $technicianData
    ): array {
        try {
            self::ensureConfigured();

            $subject = 'Service Report - ' . ($branchData['name'] ?? 'Branch') . ' - ' . ($serviceData['service_date'] ?? date('Y-m-d'));
            $htmlContent = self::buildServiceReportHtml($serviceData, $branchData, $clientData, $technicianData);

            $emailData = [
                'senderAddress' => self::$senderAddress,
                'content' => [
                    'subject' => $subject,
                    'html' => $htmlContent,
                ],
                'recipients' => [
                    'to' => [
                        [
                            'address' => $recipientEmail,
                            'displayName' => $branchData['contact_person'] ?? 'Branch Manager'
                        ]
                    ]
                ]
            ];

            $path = '/emails:send';
            $result = self::performRequest('POST', $path, $emailData);

            // performRequest returns raw response data, check status
            if (isset($result['status']) && $result['status'] >= 200 && $result['status'] < 300) {
                return [
                    'success' => true,
                    'message' => 'Service report email sent successfully',
                    'messageId' => $result['requestId'] ?? null,
                    'operationLocation' => $result['operationLocation'] ?? null
                ];
            } else {
                $errorMsg = 'HTTP ' . ($result['status'] ?? 'unknown') . ': ' . ($result['rawBody'] ?? 'No response body');
                error_log("Service report email failed: " . $errorMsg);
                return [
                    'success' => false,
                    'message' => 'Failed to send service report email: ' . $errorMsg
                ];
            }

        } catch (\RuntimeException $e) {
            error_log('Service report email RuntimeException: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Email service error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            error_log('Service report email Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build HTML content for service report email (invoice style)
     * @param array $serviceData Service report data
     * @param array $branchData Branch information
     * @param array $clientData Client information
     * @param array $technicianData Technician information
     * @return string HTML content
     */
    private static function buildServiceReportHtml(
        array $serviceData,
        array $branchData,
        array $clientData,
        array $technicianData
    ): string {
        $year = date('Y');
        $serviceDate = date('F d, Y', strtotime($serviceData['service_date'] ?? 'now'));
        $currentDate = date('F d, Y');
        $quarter = 'Q' . ($serviceData['quarter'] ?? '1');
        $invoiceNumber = 'SR-' . str_pad($serviceData['service_id'] ?? rand(1000, 9999), 3, '0', STR_PAD_LEFT);
        
        // Escape all data for HTML
        $branchName = htmlspecialchars($branchData['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $branchAddress = htmlspecialchars($branchData['address'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $branchContact = htmlspecialchars($branchData['contact_person'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $branchPhone = htmlspecialchars($branchData['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $branchEmail = htmlspecialchars($branchData['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $clientName = htmlspecialchars($clientData['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $technicianName = htmlspecialchars($technicianData['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $deviceType = htmlspecialchars(ucfirst(str_replace('_', ' ', $serviceData['device_type'] ?? 'N/A')), ENT_QUOTES, 'UTF-8');
        $serviceType = htmlspecialchars($serviceData['service_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $serviceNotes = htmlspecialchars($serviceData['service_notes'] ?? 'No additional notes', ENT_QUOTES, 'UTF-8');
        $tellerSerial = htmlspecialchars($serviceData['teller_scanner_serial'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $chdmSerial = htmlspecialchars($serviceData['chdm_serial'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<div style="font-family: Arial, sans-serif; background: #ffffff; padding: 20px; max-width: 800px; margin: 0 auto;">
  <!-- Header -->
  <div style="border: 2px solid #333333; margin-bottom: 20px;">
    <div style="background: #e8f4f8; padding: 15px; border-bottom: 1px solid #333333; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #333333;">SERVICE REPORT</h1>
        <p style="margin: 5px 0 0 0; font-size: 14px; color: #666666;">FIXPOINT SERVICE MANAGEMENT</p>
      </div>
      <div style="text-align: right;">
        <img src="https://sahqlmlmflamaghbwkin.supabase.co/storage/v1/object/public/images/faviccon.ico" alt="FixPoint Logo" style="width: 50px; height: 50px;" />
      </div>
    </div>
  </div>

  <!-- Company and Bill To Section -->
  <div style="display: flex; margin-bottom: 20px;">
    <!-- Company/Service Provider -->
    <div style="flex: 1; margin-right: 20px;">
      <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; color: #333333;">SERVICE PROVIDER</h3>
      <div style="font-size: 12px; line-height: 1.4; color: #333333;">
        <strong>Name:</strong> FixPoint Solutions<br>
        <strong>Address:</strong> Colombo, Sri Lanka<br>
        <strong>Phone:</strong> +94 XX XXX XXXX<br>
        <strong>Email:</strong> service@fixpoint.lk
      </div>
    </div>

    <!-- Bill To -->
    <div style="flex: 1; margin-right: 20px;">
      <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; color: #333333;">BILL TO</h3>
      <div style="font-size: 12px; line-height: 1.4; color: #333333;">
        <strong>Client:</strong> {$clientName}<br>
        <strong>Branch:</strong> {$branchName}<br>
        <strong>Address:</strong> {$branchAddress}<br>
        <strong>Contact:</strong> {$branchContact}<br>
        <strong>Phone:</strong> {$branchPhone}<br>
        <strong>Email:</strong> {$branchEmail}
      </div>
    </div>

    <!-- Details -->
    <div style="flex: 1;">
      <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; color: #333333;">DETAILS</h3>
      <table style="width: 100%; font-size: 12px;">
        <tr>
          <td style="border: 1px solid #333333; padding: 5px; background: #f0f0f0; font-weight: bold;">Date</td>
          <td style="border: 1px solid #333333; padding: 5px;">{$currentDate}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #333333; padding: 5px; background: #f0f0f0; font-weight: bold;">Report #</td>
          <td style="border: 1px solid #333333; padding: 5px;">{$invoiceNumber}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #333333; padding: 5px; background: #f0f0f0; font-weight: bold;">Quarter</td>
          <td style="border: 1px solid #333333; padding: 5px;">{$quarter}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #333333; padding: 5px; background: #f0f0f0; font-weight: bold;">Service Date</td>
          <td style="border: 1px solid #333333; padding: 5px;">{$serviceDate}</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Service Details Table -->
  <div style="margin-bottom: 20px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
      <thead>
        <tr>
          <th style="border: 1px solid #333333; padding: 10px; background: #d4e6f1; font-weight: bold; text-align: left;">DESCRIPTION OF SERVICES</th>
          <th style="border: 1px solid #333333; padding: 10px; background: #e8daef; font-weight: bold; text-align: center; width: 15%;">TYPE</th>
          <th style="border: 1px solid #333333; padding: 10px; background: #fadbd8; font-weight: bold; text-align: center; width: 20%;">DEVICE/SERIAL</th>
          <th style="border: 1px solid #333333; padding: 10px; background: #d5f4e6; font-weight: bold; text-align: center; width: 15%;">STATUS</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="border: 1px solid #333333; padding: 10px; vertical-align: top;">
            <strong>Device Type:</strong> {$deviceType}<br>
            <strong>Service Performed:</strong> {$serviceType}<br>
            <strong>Technician:</strong> {$technicianName}<br><br>
            <strong>Service Notes:</strong><br>
            {$serviceNotes}
          </td>
          <td style="border: 1px solid #333333; padding: 10px; text-align: center; vertical-align: top;">
            {$serviceType}
          </td>
          <td style="border: 1px solid #333333; padding: 10px; text-align: center; vertical-align: top;">
            <strong>Teller Scanner:</strong><br>{$tellerSerial}<br><br>
            <strong>CHDM:</strong><br>{$chdmSerial}
          </td>
          <td style="border: 1px solid #333333; padding: 10px; text-align: center; vertical-align: top;">
            <span style="background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 3px; font-weight: bold;">COMPLETED</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Summary Section -->
  <div style="display: flex; margin-bottom: 20px;">
    <!-- Notes Section -->
    <div style="flex: 2; margin-right: 20px;">
      <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; color: #333333;">NOTES</h3>
      <div style="border: 1px solid #333333; padding: 15px; min-height: 100px; background: #fafafa;">
        <p style="margin: 0; font-size: 12px; color: #333333;">
          Service completed successfully as per the scheduled maintenance routine for {$quarter}.
          All equipment has been tested and is functioning within normal parameters.
          This service report has been automatically generated and logged in our system.
        </p>
      </div>
    </div>

    <!-- Summary Totals -->
    <div style="flex: 1;">
      <table style="width: 100%; font-size: 12px;">
        <tr>
          <td style="border: 1px solid #333333; padding: 8px; background: #f0f0f0; font-weight: bold;">SERVICE STATUS</td>
          <td style="border: 1px solid #333333; padding: 8px; text-align: right; background: #d4edda; color: #155724; font-weight: bold;">COMPLETED</td>
        </tr>
        <tr>
          <td style="border: 1px solid #333333; padding: 8px; background: #f0f0f0; font-weight: bold;">QUARTER</td>
          <td style="border: 1px solid #333333; padding: 8px; text-align: right;">{$quarter}</td>
        </tr>
        <tr>
          <td style="border: 1px solid #333333; padding: 8px; background: #f0f0f0; font-weight: bold;">TECHNICIAN</td>
          <td style="border: 1px solid #333333; padding: 8px; text-align: right;">{$technicianName}</td>
        </tr>
        <tr style="background: #e8f4f8;">
          <td style="border: 2px solid #333333; padding: 10px; font-weight: bold; font-size: 14px;">REPORT STATUS</td>
          <td style="border: 2px solid #333333; padding: 10px; text-align: right; font-weight: bold; font-size: 14px; color: #155724;">FINAL</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Footer -->
  <div style="text-align: center; font-size: 10px; color: #666666; margin-top: 30px; border-top: 1px solid #cccccc; padding-top: 15px;">
    <p style="margin: 0;">This is an automated service report generated by FixPoint® Service Management System</p>
    <p style="margin: 5px 0 0 0;">© {$year} FixPoint Solutions. All rights reserved. | Report generated on {$currentDate}</p>
  </div>
</div>
HTML;
    }
}

if (getenv('AZURE_COMMUNICATION_CONNECTION_STRING')) {
    AzureEmailService::configure();
}
