<?php
require_once __DIR__ . '/TestCase.php';

class CategoryEndpointTest extends TestCase
{
    public function testCreateCategoryEndpointSuccess()
    {
        // 1. Arrange: Mock input data
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'category_name' => 'Home Appliances'
        ];

        // 2. Act: Capture the output of your real endpoint file
        ob_start();
        require dirname(__DIR__) . '/php/endpoints/test_endpoints/create_category.php';
        $jsonOutput = ob_get_clean();

        // 3. Assertions
        $this->assertJson($jsonOutput);
        $response = json_decode($jsonOutput, true);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('category_id', $response);

        global $categoryRepo;
        $savedCategory = $categoryRepo->findByName('Home Appliances');
        $this->assertNotNull($savedCategory);
    }

    public function testCreateCategoryEndpointValidationFailure()
    {
        // 1. Arrange: Mock validation failure
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'category_name' => ''
        ];

        // 2. Act: Capture the output
        ob_start();
        require dirname(__DIR__) . '/php/endpoints/test_endpoints/create_category.php';
        $jsonOutput = ob_get_clean();

        // 3. Assertions
        $this->assertJson($jsonOutput);
        $response = json_decode($jsonOutput, true);

        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('required', $response['message']);
    }
}
