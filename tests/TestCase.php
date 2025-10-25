<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Execute a GraphQL query
     */
    public function graphql(string $query, array $variables = [], ?string $token = null): array
    {
        $payload = [
            'query' => $query,
        ];

        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        $headers = ['Content-Type' => 'application/json'];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        $response = $this->postJson('/graphql', $payload, $headers);

        return $response->json();
    }

    /**
     * Get formatted error message from GraphQL response
     */
    public function getGraphQLError(array $response, int $index = 0): ?string
    {
        return $response['errors'][$index]['message'] ?? null;
    }

    /**
     * Assert GraphQL response has errors
     */
    public function assertGraphQLHasErrors(array $response): void
    {
        $this->assertArrayHasKey('errors', $response);
        $this->assertNotEmpty($response['errors']);
    }

    /**
     * Assert GraphQL response has no errors
     */
    public function assertGraphQLHasNoErrors(array $response): void
    {
        $this->assertArrayNotHasKey('errors', $response, 'GraphQL response should not have errors: ' . json_encode($response));
    }

    /**
     * Assert GraphQL data key exists in response
     */
    public function assertGraphQLDataKeyExists(array $response, string $key): void
    {
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey($key, $response['data']);
    }
}
