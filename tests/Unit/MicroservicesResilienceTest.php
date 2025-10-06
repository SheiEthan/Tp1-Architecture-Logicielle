<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MicroservicesResilienceTest extends TestCase
{
    private $userServiceUrl = 'http://localhost:8001';
    private $accountServiceUrl = 'http://localhost:8002';

    /**
     * Test de résilience : création d'utilisateur quand le service de comptes est indisponible
     * Ce test nécessite d'arrêter manuellement le service account avant de le lancer
     *
     * @group manual
     */
    public function testSagaRollbackOnAccountServiceFailure()
    {
        // Ce test doit être lancé manuellement après avoir arrêté le service account
        // docker stop tp3_account_service

        $userData = [
            'first_name' => 'Resilience',
            'last_name' => 'Test',
            'email' => 'resilience@example.com'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->userServiceUrl . '/api/users');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 500) {
            // Le service de comptes est bien indisponible, test du rollback
            $responseData = json_decode($response, true);

            $this->assertEquals(500, $httpCode);
            $this->assertArrayHasKey('error', $responseData);
            $this->assertStringContainsString('Transaction échouée', $responseData['error']);
            $this->assertEquals('ROLLED_BACK', $responseData['user_creation']);
            $this->assertEquals('FAILED', $responseData['account_creation']);

            // Vérifier que l'utilisateur n'a pas été créé (rollback réussi)
            $users = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/users');
            $emailExists = false;
            if (is_array($users)) {
                foreach ($users as $user) {
                    if ($user['email'] === $userData['email']) {
                        $emailExists = true;
                        break;
                    }
                }
            }
            $this->assertFalse($emailExists, 'L\'utilisateur ne devrait pas exister après un rollback');

        } else {
            // Les services sont disponibles, on skip ce test
            $this->markTestSkipped('Ce test nécessite que le service account soit arrêté. Lancez: docker stop tp3_account_service');
        }
    }

    /**
     * Test de résilience : suppression d'utilisateur quand le service de comptes est indisponible
     *
     * @group manual
     */
    public function testSagaRollbackOnDeleteWhenAccountServiceDown()
    {
        // D'abord créer un utilisateur (avec les services up)
        $userData = [
            'first_name' => 'Delete',
            'last_name' => 'Resilience',
            'email' => 'deleteresilience@example.com'
        ];

        $createResponse = $this->makeHttpRequest('POST', $this->userServiceUrl . '/api/users', $userData);

        if (!$createResponse) {
            $this->markTestSkipped('Impossible de créer un utilisateur pour le test. Vérifiez que les services sont up.');
        }

        $userId = $createResponse['user']['id'];

        // Maintenant arrêter le service de comptes et tenter la suppression
        // (Ce test nécessite d'arrêter manuellement le service)

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->userServiceUrl . '/api/users/' . $userId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 500) {
            // Service indisponible, test du rollback
            $responseData = json_decode($response, true);

            $this->assertEquals(500, $httpCode);
            $this->assertArrayHasKey('error', $responseData);
            $this->assertStringContains('Transaction échouée', $responseData['error']);
            $this->assertEquals('CANCELLED', $responseData['user_deletion']);
            $this->assertEquals('FAILED', $responseData['accounts_deletion']);

            // Vérifier que l'utilisateur existe toujours (rollback réussi)
            $userCheck = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/users/' . $userId);
            $this->assertIsArray($userCheck, 'L\'utilisateur devrait toujours exister après l\'échec de suppression');
            $this->assertEquals($userId, $userCheck['id']);

        } else {
            $this->markTestSkipped('Ce test nécessite que le service account soit arrêté pendant la suppression.');
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

    public function testServicesAvailabilityCheck()
    {
        // Test simple pour vérifier que les services répondent
        $userService = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/health');
        $accountService = $this->makeHttpRequest('GET', $this->accountServiceUrl . '/api/health');

        $this->assertIsArray($userService, 'User service devrait être disponible');
        $this->assertIsArray($accountService, 'Account service devrait être disponible');

        $this->assertEquals('UP', $userService['status']);
        $this->assertEquals('UP', $accountService['status']);
    }

    public function testConnectionDiagnosticWhenServicesUp()
    {
        $response = $this->makeHttpRequest('GET', $this->userServiceUrl . '/api/test-connection');

        if ($response) {
            $this->assertIsArray($response);
            $this->assertEquals('Connection to account-service', $response['test']);
            $this->assertEquals(200, $response['http_code']);
            $this->assertEquals('string', $response['response_type']);
            $this->assertFalse($response['response_is_false']);
            $this->assertFalse($response['response_empty']);
            $this->assertTrue($response['curl_error_empty']);
        } else {
            $this->markTestSkipped('Services non disponibles pour le test de diagnostic');
        }
    }
}
