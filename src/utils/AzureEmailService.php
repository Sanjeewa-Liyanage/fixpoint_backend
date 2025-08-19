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

    /** Public: Send virtual support link email. */
    public static function sendVirtualSupportLinkEmail(string $recipientEmail, string $recipientName, string $virtualSupportLink, string $deviceId, string $technicianName): array
    {
        try {
            $subject = 'Virtual Support Link for Device: ' . $deviceId;
            $htmlContent = self::buildVirtualSupportLinkHtml($recipientName, $virtualSupportLink, $deviceId, $technicianName);
            
            $linkData = json_decode(urldecode($virtualSupportLink), true);
            $participantLink = $linkData['participantLink'] ?? $virtualSupportLink;

            $plainText = "Hello {$recipientName},\n\nPlease use the following link to join the virtual support session for device {$deviceId} with technician {$technicianName}:\n{$participantLink}\n\nThank you,\nFixpoint Support";

            return self::sendEmail([$recipientEmail], $subject, $plainText, $htmlContent);
        } catch (\Exception $e) {
            error_log('Virtual support email Exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send virtual support email: ' . $e->getMessage()
            ];
        }
    }

    private static function buildVirtualSupportLinkHtml(string $recipientName, string $virtualSupportLink, string $deviceId, string $technicianName): string
    {
        $year = date('Y');
        $recipientNameEsc = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $deviceIdEsc = htmlspecialchars($deviceId, ENT_QUOTES, 'UTF-8');
        $technicianNameEsc = htmlspecialchars($technicianName, ENT_QUOTES, 'UTF-8');
        
        $participantLink = $virtualSupportLink; // Default to the raw link
        $jsonString = $virtualSupportLink;

        // Check for and remove the "jitsi-meeting:" prefix
        $prefix = 'jitsi-meeting:';
        if (strpos($jsonString, $prefix) === 0) {
            $jsonString = substr($jsonString, strlen($prefix));
        }

        // URL-decode and then JSON-decode the string
        $decodedJson = urldecode($jsonString);
        $linkData = json_decode($decodedJson, true);

        // If JSON decoding is successful and participantLink exists, use it
        if (json_last_error() === JSON_ERROR_NONE && isset($linkData['participantLink'])) {
            $participantLink = $linkData['participantLink'];
        }
        
        $linkEsc = htmlspecialchars($participantLink, ENT_QUOTES, 'UTF-8');


        return <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f0f0; padding: 40px 20px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <!-- Header -->
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
      <td style="padding: 40px 40px 20px 40px;">
        <h2 style="color: #333; margin: 0 0 24px 0; font-size: 28px; font-weight: 400; text-align: center;">Virtual Support Session</h2>
        <p style="color: #666; font-size: 16px; margin: 0 0: 8px 0;">Hello {$recipientNameEsc},</p>
        <p style="color: #666; font-size: 14px; margin: 0 0 32px 0; line-height: 1.5;">
          A virtual support session has been initiated for device <strong>{$deviceIdEsc}</strong>.
          Your assigned technician is <strong>{$technicianNameEsc}</strong>.
        </p>
        <p style="color: #666; font-size: 14px; margin: 0 0 32px 0; line-height: 1.5;">
          Please click the button below to join the meeting.
        </p>
        
        <!-- Join Button -->
        <div style="text-align: center; margin: 32px 0;">
          <a href="{$linkEsc}" target="_blank" style="background-color: #d63384; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold; display: inline-block;">Join Support Session</a>
        </div>
        
        <p style="color: #999; font-size: 12px; margin-top: 40px; text-align: center;">If you are having trouble with the button, copy and paste this link into your browser:</p>
        <p style="color: #d63384; font-size: 12px; text-align: center; word-break: break-all;">{$linkEsc}</p>
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

    /**
     * Generic email sending function.
     * @param string[] $recipients
     * @param string $subject
     * @param string $plainText
     * @param string $html
     * @return array ['status' => 'success'|'error', 'message' => string, 'operationId' => ?string]
     */
    private static function sendEmail(array $recipients, string $subject, string $plainText, string $html): array
    {
        try {
            self::ensureConfigured();

            $toList = array_map(fn($email) => ['address' => $email], $recipients);

            $body = [
                'content' => [
                    'subject' => $subject,
                    'plainText' => $plainText,
                    'html' => $html,
                ],
                'recipients' => [
                    'to' => $toList,
                ],
                'senderAddress' => self::$senderAddress,
            ];

            $response = self::performRequest('POST', '/emails:send', $body);
            
            return [
                'status' => 'success',
                'message' => 'Email sent successfully.',
                'operationId' => $response['id'] ?? null
            ];

        } catch (\Exception $e) {
            error_log("AzureEmailService Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Signs and executes an API request.
     * @param string $method HTTP method: 'GET', 'POST', etc.
     * @param string $path API path (without query string)
     * @param array|null $body Request body
     * @return array Decoded JSON response
     * @throws GuzzleException
     */
    private static function performRequest(string $method, string $path, ?array $body = null): array
    {
        $timestamp = gmdate('D, d M Y H:i:s T');
        $contentHash = base64_encode(hash('sha256', $body ? json_encode($body) : '', true));
        
        $pathAndQuery = $path . '?api-version=' . self::$apiVersion;

        $stringToSign = strtoupper($method) . "\n"
                      . $pathAndQuery . "\n"
                      . $timestamp . ";" . self::$host . ";" . $contentHash;

        $hmac = base64_encode(hash_hmac('sha256', $stringToSign, base64_decode(self::$accessKey), true));
        
        $authorization = 'HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature=' . $hmac;
        
        $headers = [
            'x-ms-date' => $timestamp,
            'host' => self::$host,
            'x-ms-content-sha256' => $contentHash,
            'Authorization' => $authorization,
            'Content-Type' => 'application/json',
        ];

        $options = [
            'headers' => $headers,
            'query' => ['api-version' => self::$apiVersion]
        ];
        if ($body) {
            $options['json'] = $body;
        }

        $response = self::$httpClient->request($method, $path, $options);
        
        $responseBody = (string) $response->getBody();
        return json_decode($responseBody, true) ?: [];
    }
}

if (getenv('AZURE_COMMUNICATION_CONNECTION_STRING')) {
    AzureEmailService::configure();
}
