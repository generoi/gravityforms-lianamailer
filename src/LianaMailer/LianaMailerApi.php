<?php

namespace GeneroWP\GravityformsLianamailer\LianaMailer;

class LianaMailerApi
{
    protected $apiKey = null;
    protected $apiUser = null;
    protected $apiUrl = null;
    protected $apiVersion = null;
    protected $apiRealm = null;

    public function __construct(int $apiUser, string $apiKey, string $apiUrl, int $apiVersion, string $apiRealm)
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->apiVersion = $apiVersion;
        $this->apiRealm = $apiRealm;
    }

    public function getMailingList(int $id)
    {
        return $this->call(__FUNCTION__, [
            $id,
        ], 'POST');
    }

    public function getMailingLists(bool $includeDeleted = false)
    {
        return $this->call(__FUNCTION__, [
            $includeDeleted ? 1 : 0,
        ], 'POST');
    }

    public function getRecipientByEmail(string $email, bool $includeDeleted = false)
    {
        return $this->call(__FUNCTION__, [
            $email,
            $includeDeleted ? 1 : 0,
        ], 'POST');
    }

    public function getRecipient(int $id)
    {
        return $this->call(__FUNCTION__, [
            $id,
        ], 'POST');
    }

    public function existsRecipient(string $email)
    {
        return $this->call(__FUNCTION__, [
            $email,
        ], 'POST');
    }

    public function enableRecipient(int $id, string $reason)
    {
        return $this->call(__FUNCTION__, [
            $id,
            $reason,
        ], 'POST');
    }

    public function createRecipient(string $email, array $options = [])
    {
        $options = array_merge([
            'sms' => null,
            'props' => null,
            'autoconfirm' => false,
            'reason' => null,
            'origin' => null,
        ], $options);

        return $this->call(__FUNCTION__, [
            $email,
            $options['sms'],
            $options['props'],
            $options['autoconfirm'],
            $options['reason'],
            $options['origin'],
        ], 'POST');
    }

    public function joinMailingList(int $listId, array $recipientIds, array $options = [])
    {
        $options = array_merge([
            'noauto' => false,
            'reason' => '',
            'admin' => 1,
            'truncate' => false,
            'origin' => null,
            'rejoin' => false,
            'ignore_errors' => false,
        ], $options);

        return $this->call(__FUNCTION__, [
            $listId,
            $recipientIds,
            $options['noauto'],
            $options['reason'],
            $options['admin'],
            $options['truncate'],
            $options['origin'],
            $options['rejoin'],
            $options['ignore_errors'],
        ], 'POST');
    }

    public function call(string $path, array $args = [], string $method = 'POST')
    {
        $args = $method !== 'GET' ? json_encode($args) : '';
        return $this->request($path, $args, $method);
    }

    protected function request(string $path, string $contents, string $method)
    {
        $md5 = md5($contents);
        $timestamp = date('c');
        $url = $this->apiUrl . '/api/v'. $this->apiVersion .'/' . $path;

        $message = [
            $method,
            $md5,
            'application/json',
            $timestamp,
            $contents,
            '/api/v'. $this->apiVersion .'/' . $path
        ];

        $signature = $this->sign($message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json',
          "Content-MD5: {$md5}",
          "Date: {$timestamp}",
          "Authorization: {$this->apiRealm} {$this->apiUser}:{$signature}"
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-LMAPI');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($result, true);

        if ($httpCode === 401) {
            throw new RestClientAuthorizationException;
        }

        if ($httpCode > 400 || (isset($result['succeed']) && !$result['succeed'])) {
            throw new ApiException(sprintf(
                'API response with status code %s %s',
                $httpCode,
                $result['message'] ? $result['message'] : ''
            ));
        }

        if ($result === null) {
            throw new ApiException('API did not return a valid json string');
        }

        if (isset($result['result'])) {
            return $result['result'];
        }

        return $result;
    }

    protected function sign(array $message): string
    {
        return hash_hmac('sha256', implode("\n", $message), $this->apiKey);
    }
}
