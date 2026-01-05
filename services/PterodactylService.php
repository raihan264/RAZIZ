<?php
// services/PterodactylService.php

class PterodactylService {
    private $domain;
    private $apiKey;
    private $clientKey;

    public function __construct($domain, $apiKey, $clientKey = null) {
        $this->domain = rtrim($domain, '/');
        $this->apiKey = $apiKey;
        $this->clientKey = $clientKey;
    }

    protected function request($method, $endpoint, $data = [], $isClient = false) {
        $url = "https://{$this->domain}/api/" . ($isClient ? 'client' : 'application') . $endpoint;
        $key = $isClient ? $this->clientKey : $this->apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = [
            "Authorization: Bearer {$key}",
            "Content-Type: application/json",
            "Accept: application/json"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL Error: $error");
        }

        $body = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $body['errors'][0]['detail'] ?? "Unknown Pterodactyl Error ($httpCode)";
            throw new Exception($msg);
        }

        return $body;
    }

    public function checkUserExists($username) {
        // Search user by username
        $response = $this->request('GET', "/users?filter[username]={$username}");
        if (!empty($response['data'])) {
            return $response['data'][0]['attributes']['id'];
        }
        return null;
    }

    public function createUser($data) {
        $payload = [
            'username' => $data['username'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => $data['password'], // Pterodactyl might require password or handle it
            'language' => 'en',
            'root_admin' => false,
        ];

        $response = $this->request('POST', '/users', $payload);
        return $response['attributes']['id'];
    }

    public function getEggEnvironment($nestId, $eggId) {
        $response = $this->request('GET', "/nests/{$nestId}/eggs/{$eggId}?include=variables");
        $vars = [];

        if (isset($response['attributes']['relationships']['variables']['data'])) {
            foreach ($response['attributes']['relationships']['variables']['data'] as $var) {
                $attr = $var['attributes'];
                $vars[$attr['env_variable']] = $attr['default_value'];
            }
        }
        return $vars;
    }

    public function getUnassignedAllocation($nodeId) {
        // Fetch allocations for the node
        // Note: Pagination might be needed for many allocations, but for now we take the first page
        $response = $this->request('GET', "/nodes/{$nodeId}/allocations?include=node");

        foreach ($response['data'] as $allocation) {
            if (!$allocation['attributes']['assigned']) {
                return $allocation['attributes']['id'];
            }
        }

        throw new Exception("No available allocations found on Node $nodeId");
    }

    public function createServer($data, $envVars, $allocationId) {
        $payload = [
            'name' => $data['name'],
            'user' => (int)$data['user_id'],
            'egg' => (int)$data['egg_id'],
            'docker_image' => $data['docker_image'],
            'startup' => $data['startup'],
            'environment' => $envVars,
            'limits' => [
                'memory' => (int)$data['memory'],
                'swap' => 0,
                'disk' => (int)$data['disk'],
                'io' => 500,
                'cpu' => (int)$data['cpu']
            ],
            'feature_limits' => [
                'databases' => 0,
                'backups' => 0
            ],
            'allocation' => [
                'default' => (int)$allocationId
            ]
        ];

        // If Egg details provided Docker Image/Startup, use them
        // For now, we rely on the caller or fetch them.
        // Enhancement: Fetch egg details includes docker_images and startup

        // To be safe, let's re-fetch egg details strictly for startup/image if not passed
        // But to keep it simple as per plan, we assume env vars are enough or defaults are OK.
        // ACTUALLY: The prompt says "Get Egg Details first to get default variables".
        // The egg details response usually contains 'docker_image' and 'startup'.

        // Let's refine getEggEnvironment to return startup info too.
        return $this->request('POST', '/servers', $payload);
    }

    // Refined method to get full Egg Config
    public function getEggDetails($nestId, $eggId) {
        $response = $this->request('GET', "/nests/{$nestId}/eggs/{$eggId}?include=variables");
        $attr = $response['attributes'];

        $env = [];
        if (isset($attr['relationships']['variables']['data'])) {
            foreach ($attr['relationships']['variables']['data'] as $var) {
                $vAttr = $var['attributes'];
                $env[$vAttr['env_variable']] = $vAttr['default_value'];
            }
        }

        return [
            'startup' => $attr['startup'],
            'docker_image' => $attr['docker_image'],
            'environment' => $env
        ];
    }

    public function deleteServer($serverId) {
        return $this->request('DELETE', "/servers/{$serverId}");
    }

    public function getUsers() {
        // Fetch users including servers relation to count them
        return $this->request('GET', '/users?include=servers');
    }

    public function deleteUser($userId) {
        return $this->request('DELETE', "/users/{$userId}");
    }
}
?>
