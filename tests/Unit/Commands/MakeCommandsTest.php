<?php

namespace LanternTest\Unit\Commands;

use LanternTest\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class MakeCommandsTest extends TestCase
{
    protected $testPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use a test directory in the package root
        $this->testPath = __DIR__ . '/../../../test-features';
        config(['lantern.features_path' => $this->testPath]);
        
        // Create a clean test directory
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }
        File::makeDirectory($this->testPath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_can_create_a_basic_action()
    {
        $this->artisan('lantern:make-action', ['name' => 'TestAction'])
            ->assertExitCode(0)
            ->assertSuccessful();

        $expectedPath = $this->testPath . '/TestAction.php';
        $this->assertFileExists($expectedPath);
        
        $content = File::get($expectedPath);
        $this->assertStringContainsString('class TestAction extends Action', $content);
        $this->assertStringContainsString("const ID = 'test'", $content);
    }

    #[Test]
    public function it_can_create_an_action_in_nested_namespace()
    {
        $this->artisan('lantern:make-action', ['name' => 'Blog/Posts/PublishPost'])
            ->assertExitCode(0)
            ->assertSuccessful();

        $expectedPath = $this->testPath . '/Blog/Posts/PublishPostAction.php';
        $this->assertFileExists($expectedPath);
        
        $content = File::get($expectedPath);
        $this->assertStringContainsString('namespace App\Features\Blog\Posts;', $content);
        $this->assertStringContainsString("const ID = 'blog-posts-publish-post'", $content);
    }

    #[Test]
    public function it_wont_create_duplicate_actions()
    {
        // First creation should succeed
        $this->artisan('lantern:make-action', ['name' => 'TestAction'])
            ->assertSuccessful();

        // Second creation should fail and return error message
        $this->artisan('lantern:make-action', ['name' => 'TestAction'])
            ->expectsOutput('Action already exists!')
            ->assertFailed();
    }

    #[Test]
    public function it_can_create_a_basic_feature()
    {
        $this->artisan('lantern:make-feature', ['name' => 'TestFeature'])
            ->assertExitCode(0)
            ->assertSuccessful();

        $expectedPath = $this->testPath . '/TestFeature.php';
        $this->assertFileExists($expectedPath);
        
        $content = File::get($expectedPath);
        $this->assertStringContainsString('class TestFeature extends Feature', $content);
        $this->assertStringContainsString("const ID = 'test-feature'", $content);
        $this->assertStringContainsString("const STACK = null", $content);
    }

    #[Test]
    public function it_can_create_a_feature_with_stack()
    {
        $this->artisan('lantern:make-feature', [
            'name' => 'TestFeature',
            '--stack' => 'content'
        ])
            ->assertExitCode(0)
            ->assertSuccessful();

        $content = File::get($this->testPath . '/TestFeature.php');
        $this->assertStringContainsString("const STACK = 'content'", $content);
    }

    #[Test]
    public function it_can_create_a_feature_in_nested_namespace()
    {
        $this->artisan('lantern:make-feature', ['name' => 'Blog/PostManagement'])
            ->assertExitCode(0)
            ->assertSuccessful();

        $expectedPath = $this->testPath . '/Blog/PostManagementFeature.php';
        $this->assertFileExists($expectedPath);
        
        $content = File::get($expectedPath);
        $this->assertStringContainsString('namespace App\Features\Blog;', $content);
        $this->assertStringContainsString("const ID = 'blog-post-management'", $content);
    }

    #[Test]
    public function it_wont_create_duplicate_features()
    {
        // First creation should succeed
        $this->artisan('lantern:make-feature', ['name' => 'TestFeature'])
            ->assertSuccessful();

        // Second creation should fail and return error message
        $this->artisan('lantern:make-feature', ['name' => 'TestFeature'])
            ->expectsOutput('Feature already exists!')
            ->assertFailed();
    }

    #[Test]
    public function it_creates_feature_with_proper_docblocks()
    {
        $this->artisan('lantern:make-feature', ['name' => 'TestFeature'])
            ->assertSuccessful();

        $content = File::get($this->testPath . '/TestFeature.php');
        $this->assertStringContainsString('@var string|null', $content);
        $this->assertStringContainsString('@var array<class-string>', $content);
    }

    #[Test]
    public function it_creates_action_with_proper_docblocks()
    {
        $this->artisan('lantern:make-action', ['name' => 'TestAction'])
            ->assertSuccessful();

        $content = File::get($this->testPath . '/TestAction.php');
        $this->assertStringContainsString('@return \Lantern\Features\ActionResponse', $content);
    }
}