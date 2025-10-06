<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MicroservicesSagaTest extends TestCase
{
    private $userServiceUrl = 'http://localhost:8001';
    private $accountServiceUrl = 'http://localhost:8002';

    protected function setUp(): void
    {
        parent::setUp();
        // Vérifier que les services sont disponibles avant les tests
        $this->checkServicesAvailability();
    }

    /**
     * Check if both microservices are available before running tests.
     * If services are not available, skip the test with a message.
     */
    private function checkServicesAvailability()
    {
        $userService = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/health');
        $accountService = $this->makeHttpRequest('GET', $this->accountServiceUrl . '/api/health');

        if (!$userService || !$accountService) {
            $this->markTestSkipped('Services microservices non disponibles. Lancez docker-compose up -d');
        }
    }

    private function makeHttpRequest($method, $url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false && $httpCode < 400 ? json_decode($response, true) : false;
    }

    public function testUserServiceHealthCheck()
    {
        $response = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/health');

        $this->assertIsArray($response);
        $this->assertEquals('UP', $response['status']);
        $this->assertEquals('user-service', $response['service']);
    }

    public function testAccountServiceHealthCheck()
    {
        $response = $this->makeHttpRequest('GET', $this->accountServiceUrl . '/api/health');

        $this->assertIsArray($response);
        $this->assertEquals('UP', $response['status']);
        $this->assertEquals('account-service', $response['service']);
    }

    public function testSagaPatternUserCreationSuccess()
    {
        // Données de test
        $userData = [
            'first_name' => 'Test',
            'last_name' => 'Saga',
            'email' => 'testsaga@example.com'
        ];

        // Test de création avec pattern Saga
        $response = $this->makeHttpRequest('POST', $this->userServiceUrl . '/api/users', $userData);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('account_created', $response);
        $this->assertEquals('SUCCESS', $response['transaction_status']);

        // Vérifier que l'utilisateur a été créé
        $user = $response['user'];
        $this->assertEquals($userData['first_name'], $user['first_name']);
        $this->assertEquals($userData['last_name'], $user['last_name']);
        $this->assertEquals($userData['email'], $user['email']);

        // Vérifier que le compte a été créé
        $account = $response['account_created'];
        $this->assertNotNull($account['id']);
        $this->assertEquals($user['id'], $account['user_id']);
        $this->assertEquals(0, $account['solde']);
        $this->assertEquals('courant', $account['type_compte']);
        $this->assertStringStartsWith('CPT', $account['numero_compte']);

        // Nettoyer : supprimer l'utilisateur créé
        $this->makeHttpRequest('DELETE', $this->userServiceUrl . '/api/users/' . $user['id']);
    }

    public function testSagaPatternUserDeletionSuccess()
    {
        // Créer d'abord un utilisateur
        $userData = [
            'first_name' => 'Delete',
            'last_name' => 'Test',
            'email' => 'deletetest@example.com'
        ];

        $createResponse = $this->makeHttpRequest('POST', $this->userServiceUrl . '/api/users', $userData);
        $this->assertIsArray($createResponse);
        $userId = $createResponse['user']['id'];
        $accountId = $createResponse['account_created']['id'];

        // Tester la suppression avec pattern Saga
        $deleteResponse = $this->makeHttpRequest('DELETE', $this->userServiceUrl . '/api/users/' . $userId);

        $this->assertIsArray($deleteResponse);
        $this->assertStringContainsString('successfully', $deleteResponse['message']);
        $this->assertEquals($userId, $deleteResponse['user_id']);
        $this->assertContains($accountId, $deleteResponse['accounts_deleted']);
        $this->assertEquals('SUCCESS', $deleteResponse['transaction_status']);

        // Vérifier que l'utilisateur n'existe plus
        $userCheck = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/users/' . $userId);
        $this->assertFalse($userCheck);

        // Vérifier que le compte n'existe plus
        $accountCheck = $this->makeHttpRequest('GET', $this->accountServiceUrl . '/api/accounts/' . $accountId);
        $this->assertFalse($accountCheck);
    }

    public function testUserCreationValidation()
    {
        // Test avec données manquantes
        $invalidData = [
            'first_name' => 'Test'
            // Manque last_name et email
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->userServiceUrl . '/api/users');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invalidData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(400, $httpCode);
        $responseData = json_decode($response, true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('requis', $responseData['error']);
    }

    public function testGetAllUsers()
    {
        $response = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/users');

        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(0, count($response));

        // Si il y a des utilisateurs, vérifier la structure
        if (count($response) > 0) {
            $user = $response[0];
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('first_name', $user);
            $this->assertArrayHasKey('last_name', $user);
            $this->assertArrayHasKey('email', $user);
        }
    }

    public function testGetAllAccounts()
    {
        $response = $this->makeHttpRequest('GET', $this->accountServiceUrl . '/api/accounts');

        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(0, count($response));

        // Si il y a des comptes, vérifier la structure
        if (count($response) > 0) {
            $account = $response[0];
            $this->assertArrayHasKey('id', $account);
            $this->assertArrayHasKey('user_id', $account);
            $this->assertArrayHasKey('numero_compte', $account);
            $this->assertArrayHasKey('solde', $account);
            $this->assertArrayHasKey('type_compte', $account);
        }
    }

    public function testUserNotFound()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->userServiceUrl . '/api/users/999999');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(404, $httpCode);
        $responseData = json_decode($response, true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('User not found', $responseData['error']);
    }

    public function testAccountNotFound()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->accountServiceUrl . '/api/accounts/999999');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(404, $httpCode);
        $responseData = json_decode($response, true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Account not found', $responseData['error']);
    }

    public function testServiceConnectionDiagnostic()
    {
        // Test de l'endpoint de diagnostic que nous avons créé
        $response = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/test-connection');

        $this->assertIsArray($response);
        $this->assertEquals('Connection to account-service', $response['test']);
        $this->assertEquals(200, $response['http_code']);
        $this->assertEquals('string', $response['response_type']);
        $this->assertFalse($response['response_is_false']);
        $this->assertFalse($response['response_empty']);
        $this->assertTrue($response['curl_error_empty']);
    }
}
