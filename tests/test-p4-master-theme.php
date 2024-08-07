<?php

/**
 * MasterThemeTest Class
 *
 * @package P4MT
 */


/**
 * Class P4MasterThemeTest
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class P4MasterThemeTest extends P4TestCase
{
    /**
     * Setup test
     */
	public function setUp(): void { // phpcs:ignore
        parent::setUp();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        switch_theme('planet4-master-theme');
    }

    /**
     * Test functions
     */
    public function testFunctionsPHP(): void
    {
        $context = Timber::context();
        $this->assertEquals('P4\\MasterTheme\\MasterSite', get_class($context['site']));
        $this->assertTrue(current_theme_supports('post-thumbnails'));
        $this->assertEquals('bar', $context['foo']);
    }

    /**
     * Test loading
     */
    public function testLoading(): void
    {
        $str = Timber::compile('tease.twig');
        $this->assertStringStartsWith('<article class="tease tease-" id="tease-">', $str);
        $this->assertStringEndsWith('</article>', $str);
    }
}
