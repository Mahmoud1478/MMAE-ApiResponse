<?php

namespace MMAE\ApiResponse\tests\Unit;

use MMAE\ApiResponse\Configurations\Response;
use MMAE\ApiResponse\Examples\TestRequest;
use MMAE\ApiResponse\tests\TestCase;
use MMAE\ApiResponse\Traits\HasApiResponse;
use PHPUnit\Framework\Attributes\Test;

class ResponseTest extends TestCase
{
    use HasApiResponse;

    #[Test]
    public function test_structure()
    {
        $response = json_decode($this->successResponse([])->content(), true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('token', $response);

    }

    #[Test]
    public function test_status_config_change_response_status()
    {
        Response::$FAILED_STATUS = 200;
        $response = $this->failedResponse([], '');
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function test_status_pram_change_success_response_status()
    {
        $response = $this->successResponse([], 201);
        $this->assertEquals(201, $response->status());
    }

    #[Test]
    public function test_status_pram_change_failed_response_status()
    {
        $response = $this->failedResponse([], '', 500);
        $this->assertEquals(500, $response->status());
    }

    #[Test]
    public function test_validation_response()
    {
        $response = $this->get('/request-response');
        dd($response);
    }
}
