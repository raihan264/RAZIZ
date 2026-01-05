<?php
// tests/test_ptero_mock.php
require_once __DIR__ . '/../services/PterodactylService.php';

// Mock class to override request method
class MockPterodactylService extends PterodactylService {
    // We must match the parent signature exactly, but parent request is private.
    // However, in PHP, we can't override a private method easily in a subclass to be called by public methods of the parent
    // UNLESS the parent calls $this->request().
    // Since request is private in parent, PterodactylService::checkUserExists calls PterodactylService::request.
    // It will NOT call MockPterodactylService::request.
    // To fix this for testing, we need to change visibility in PterodactylService to protected.

    // Assuming we changed parent to protected:
    protected function request($method, $endpoint, $data = [], $isClient = false) {
        echo "Mock Request: $method $endpoint\n";
        if ($endpoint === '/users?filter[username]=testuser') {
            return ['data' => []]; // User not found
        }
        if ($endpoint === '/users' && $method === 'POST') {
            return ['attributes' => ['id' => 999]]; // Created user ID
        }
        if (strpos($endpoint, '/eggs/') !== false) {
            return [
                'attributes' => [
                    'startup' => 'java -jar server.jar',
                    'docker_image' => 'java:17',
                    'relationships' => [
                        'variables' => [
                            'data' => [
                                ['attributes' => ['env_variable' => 'SERVER_JARFILE', 'default_value' => 'server.jar']]
                            ]
                        ]
                    ]
                ]
            ];
        }
        if (strpos($endpoint, '/allocations') !== false) {
            return [
                'data' => [
                    ['attributes' => ['id' => 55, 'assigned' => false]]
                ]
            ];
        }
        if ($endpoint === '/servers' && $method === 'POST') {
            return ['attributes' => ['id' => 101, 'identifier' => 'srv-101']];
        }
        return [];
    }

    // Make request public for testing if needed, or just test public methods
    public function __call($name, $arguments) {
        // Allow calling protected/private for testing if strictly necessary, but we are testing public API here
    }
}

$service = new MockPterodactylService('panel.test.com', 'key');

echo "Testing checkUserExists...\n";
$uid = $service->checkUserExists('testuser');
echo "User ID: " . ($uid ?? 'null') . "\n";

if (!$uid) {
    echo "Creating user...\n";
    $uid = $service->createUser([
        'username' => 'testuser',
        'email' => 'test@test.com',
        'first_name' => 'Test',
        'last_name' => 'User',
        'password' => 'pass'
    ]);
    echo "Created User ID: $uid\n";
}

echo "Getting Egg Details...\n";
$egg = $service->getEggDetails(5, 15);
print_r($egg);

echo "Getting Allocation...\n";
$alloc = $service->getUnassignedAllocation(1);
echo "Allocation ID: $alloc\n";

echo "Creating Server...\n";
$server = $service->createServer([
    'name' => 'Test Server',
    'user_id' => $uid,
    'egg_id' => 15,
    'docker_image' => $egg['docker_image'],
    'startup' => $egg['startup'],
    'memory' => 1024,
    'disk' => 5000,
    'cpu' => 100
], $egg['environment'], $alloc);

print_r($server);
?>
