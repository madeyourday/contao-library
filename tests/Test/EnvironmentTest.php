<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Test;

use Contao\Environment;
use Contao\Test\Fixtures\EnvironmentApache2;
use Contao\Test\Fixtures\EnvironmentFpmFcgi;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    protected $root;

    public function setUp()
    {
        // Proxy settings
        $GLOBALS['TL_CONFIG']['sslProxyDomain'] = '';
        $GLOBALS['TL_CONFIG']['proxyServerIps'] = '';

        // User agents
        include __DIR__ . '/../Resources/config/agents.php';

        // Default environment
        $_SERVER = array(
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_CONNECTION'      => 'keep-alive',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'HTTP_USER_AGENT'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4',
            'SERVER_NAME'          => 'localhost',
            'SERVER_ADDR'          => '127.0.0.1',
            'REMOTE_ADDR'          => '123.456.789.0',
            'DOCUMENT_ROOT'        => $this->root,
            'SCRIPT_FILENAME'      => $this->root . '/index.php',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'QUERY_STRING'         => 'do=test',
            'REQUEST_URI'          => '/en/academy.html?do=test',
            'SCRIPT_NAME'          => '/index.php',
            'PHP_SELF'             => '/index.php'
        );

        $this->root = realpath(__DIR__ . '/../../../../../');
    }

    /**
     * Test a root installation in a mod_php environment
     */
    public function testModPhpWithoutPath()
    {
        EnvironmentApache2::reset();
        EnvironmentApache2::set('path', '');

        $_SERVER['SCRIPT_NAME']     = '/index.php';
        $_SERVER['PHP_SELF']        = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $this->root . '/index.php';
        $_SERVER['REQUEST_URI']     = '/en/academy.html?do=test';

        $this->assertEquals($this->root . '/index.php', EnvironmentApache2::get('scriptFilename'));
        $this->assertEquals('/index.php', EnvironmentApache2::get('scriptName'));
        $this->assertEquals($this->root, EnvironmentApache2::get('documentRoot'));
        $this->assertEquals('/en/academy.html?do=test', EnvironmentApache2::get('requestUri'));
        $this->assertEquals('localhost', EnvironmentApache2::get('httpHost'));
        $this->assertEmpty(EnvironmentApache2::get('httpXForwardedHost'));
        $this->assertFalse(EnvironmentApache2::get('ssl'));
        $this->assertEquals('http://localhost', EnvironmentApache2::get('url'));
        $this->assertEquals('http://localhost/en/academy.html?do=test', EnvironmentApache2::get('uri'));
        $this->assertEmpty(EnvironmentApache2::get('path'));
        $this->assertEquals('index.php', EnvironmentApache2::get('script'));
        $this->assertEquals('en/academy.html?do=test', EnvironmentApache2::get('request'));
        $this->assertEquals('http://localhost/', EnvironmentApache2::get('base'));
        $this->assertEquals('localhost', EnvironmentApache2::get('httpHost'));
    }

    /**
     * Test a subfolder installation in a mod_php environment
     */
    public function testModPhpWithPath()
    {
        EnvironmentApache2::reset();
        EnvironmentApache2::set('path', '/contao4');

        $_SERVER['SCRIPT_NAME']     = '/contao4/index.php';
        $_SERVER['PHP_SELF']        = '/contao4/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $this->root . '/contao4/index.php';
        $_SERVER['REQUEST_URI']     = '/contao4/en/academy.html?do=test';

        $this->assertEquals($this->root . '/contao4/index.php', EnvironmentApache2::get('scriptFilename'));
        $this->assertEquals('/contao4/index.php', EnvironmentApache2::get('scriptName'));
        $this->assertEquals($this->root, EnvironmentApache2::get('documentRoot'));
        $this->assertEquals('/contao4/en/academy.html?do=test', EnvironmentApache2::get('requestUri'));
        $this->assertEquals('localhost', EnvironmentApache2::get('httpHost'));
        $this->assertEmpty(EnvironmentApache2::get('httpXForwardedHost'));
        $this->assertFalse(EnvironmentApache2::get('ssl'));
        $this->assertEquals('http://localhost', EnvironmentApache2::get('url'));
        $this->assertEquals('http://localhost/contao4/en/academy.html?do=test', EnvironmentApache2::get('uri'));
        $this->assertEquals('/contao4', EnvironmentApache2::get('path'));
        $this->assertEquals('index.php', EnvironmentApache2::get('script'));
        $this->assertEquals('en/academy.html?do=test', EnvironmentApache2::get('request'));
        $this->assertEquals('http://localhost/contao4/', EnvironmentApache2::get('base'));
        $this->assertEquals('localhost', EnvironmentApache2::get('httpHost'));
    }

    /**
     * Test a root installation in a PHP-FPM environment
     */
    public function testFpmFcgiWithoutPath()
    {
        EnvironmentFpmFcgi::reset();
        EnvironmentFpmFcgi::set('path', '');

        $_SERVER['SCRIPT_NAME']          = '/index.php';
        $_SERVER['PHP_SELF']             = '/index.php';
        $_SERVER['SCRIPT_FILENAME']      = $this->root . '/index.php';
        $_SERVER['REQUEST_URI']          = '/en/academy.html?do=test';
        $_SERVER['ORIG_SCRIPT_FILENAME'] = '/var/run/localhost.fcgi';
        $_SERVER['ORIG_SCRIPT_NAME']     = '/php.fcgi';
        $_SERVER['ORIG_PATH_INFO']       = '/index.php';
        $_SERVER['ORIG_PATH_TRANSLATED'] = $this->root . '/index.php';

        $this->assertEquals($this->root . '/index.php', EnvironmentFpmFcgi::get('scriptFilename'));
        $this->assertEquals('/index.php', EnvironmentFpmFcgi::get('scriptName'));
        $this->assertEquals($this->root, EnvironmentFpmFcgi::get('documentRoot'));
        $this->assertEquals('/en/academy.html?do=test', EnvironmentFpmFcgi::get('requestUri'));
        $this->assertEquals('localhost', EnvironmentFpmFcgi::get('httpHost'));
        $this->assertEmpty(EnvironmentFpmFcgi::get('httpXForwardedHost'));
        $this->assertFalse(EnvironmentFpmFcgi::get('ssl'));
        $this->assertEquals('http://localhost', EnvironmentFpmFcgi::get('url'));
        $this->assertEquals('http://localhost/en/academy.html?do=test', EnvironmentFpmFcgi::get('uri'));
        $this->assertEmpty(EnvironmentFpmFcgi::get('path'));
        $this->assertEquals('index.php', EnvironmentFpmFcgi::get('script'));
        $this->assertEquals('en/academy.html?do=test', EnvironmentFpmFcgi::get('request'));
        $this->assertEquals('http://localhost/', EnvironmentFpmFcgi::get('base'));
        $this->assertEquals('localhost', EnvironmentFpmFcgi::get('httpHost'));
    }

    /**
     * Test a subfolder installation in a PHP-FPM environment
     */
    public function testFpmFcgiWithPath()
    {
        EnvironmentFpmFcgi::reset();
        EnvironmentFpmFcgi::set('path', '/contao4');

        $_SERVER['SCRIPT_NAME']          = '/contao4/index.php';
        $_SERVER['PHP_SELF']             = '/contao4/index.php';
        $_SERVER['SCRIPT_FILENAME']      = $this->root . '/contao4/index.php';
        $_SERVER['REQUEST_URI']          = '/contao4/en/academy.html?do=test';
        $_SERVER['ORIG_SCRIPT_FILENAME'] = '/var/run/localhost.fcgi';
        $_SERVER['ORIG_SCRIPT_NAME']     = '/php.fcgi';
        $_SERVER['ORIG_PATH_INFO']       = '/contao4/index.php';
        $_SERVER['ORIG_PATH_TRANSLATED'] = $this->root . '/contao4/index.php';

        $this->assertEquals($this->root . '/contao4/index.php', EnvironmentFpmFcgi::get('scriptFilename'));
        $this->assertEquals('/contao4/index.php', EnvironmentFpmFcgi::get('scriptName'));
        $this->assertEquals($this->root, EnvironmentFpmFcgi::get('documentRoot'));
        $this->assertEquals('/contao4/en/academy.html?do=test', EnvironmentFpmFcgi::get('requestUri'));
        $this->assertEquals('localhost', EnvironmentFpmFcgi::get('httpHost'));
        $this->assertEmpty(EnvironmentFpmFcgi::get('httpXForwardedHost'));
        $this->assertFalse(EnvironmentFpmFcgi::get('ssl'));
        $this->assertEquals('http://localhost', EnvironmentFpmFcgi::get('url'));
        $this->assertEquals('http://localhost/contao4/en/academy.html?do=test', EnvironmentFpmFcgi::get('uri'));
        $this->assertEquals('/contao4', EnvironmentFpmFcgi::get('path'));
        $this->assertEquals('index.php', EnvironmentFpmFcgi::get('script'));
        $this->assertEquals('en/academy.html?do=test', EnvironmentFpmFcgi::get('request'));
        $this->assertEquals('http://localhost/contao4/', EnvironmentFpmFcgi::get('base'));
        $this->assertEquals('localhost', EnvironmentFpmFcgi::get('httpHost'));
    }

    /**
     * Test the Ajax detection
     */
    public function testAjaxRequest()
    {
        Environment::reset();

        $this->assertFalse(Environment::get('isAjaxRequest'));

        Environment::reset();

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertTrue(Environment::get('isAjaxRequest'));
    }

    /**
     * Test the SSL detection
     */
    public function testIsSsl()
    {
        Environment::reset();

        $this->assertFalse(Environment::get('ssl'));

        Environment::reset();

        $_SERVER['HTTPS'] = 'on';

        $this->assertTrue(Environment::get('ssl'));
    }

    /**
     * Test the SSL proxy configuration
     */
    public function testSslProxy()
    {
        Environment::reset();

        $this->assertEquals('123.456.789.0', Environment::get('ip'));

        Environment::reset();

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '987.654.321.0';

        $this->assertEquals('987.654.321.0', Environment::get('ip'));

        Environment::reset();

        $GLOBALS['TL_CONFIG']['proxyServerIps'] = '987.654.321.0';

        $this->assertEquals('123.456.789.0', Environment::get('ip'));
    }

    /**
     * Test the user agent detection
     */
    public function testAgent()
    {
        Environment::reset();

        $this->assertEquals(['de-DE', 'de', 'en-GB', 'en'], Environment::get('httpAcceptLanguage'));
        $this->assertEquals(['gzip', 'deflate', 'sdch'], Environment::get('httpAcceptEncoding'));
        $this->assertEquals('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36', Environment::get('httpUserAgent'));
        $this->assertEquals('123.456.789.0', Environment::get('ip'));
        $this->assertEquals('127.0.0.1', Environment::get('server'));

        $agent = Environment::get('agent');

        $this->assertEquals('mac', $agent->os);
        $this->assertEquals('mac chrome webkit ch33', $agent->class);
        $this->assertEquals('chrome', $agent->browser);
        $this->assertEquals('ch', $agent->shorty);
        $this->assertEquals(33, $agent->version);
        $this->assertEquals('webkit', $agent->engine);
        $this->assertEquals([33, 0, 1750, 149], $agent->versions);
        $this->assertFalse($agent->mobile);

        Environment::reset();

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5';

        $agent = Environment::get('agent');

        $this->assertEquals('ios', $agent->os);
        $this->assertEquals('ios safari webkit sf5 mobile', $agent->class);
        $this->assertEquals('safari', $agent->browser);
        $this->assertEquals('sf', $agent->shorty);
        $this->assertEquals(5, $agent->version);
        $this->assertEquals('webkit', $agent->engine);
        $this->assertEquals([5, 0, 2], $agent->versions);
        $this->assertTrue($agent->mobile);
    }
}
