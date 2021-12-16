<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Mockery;
use stdClass;
use InvalidArgumentException;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Contracts\Responsable;
use Snicco\Core\Http\BaseResponseFactory;
use Snicco\Core\Http\StatelessRedirector;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Http\Responses\NullResponse;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class ResponseFactoryTest extends UnitTest
{
    
    use CreatePsr17Factories;
    
    private BaseResponseFactory $factory;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    public function testMake()
    {
        $response = $this->factory->make(204, 'Hello');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('Hello', $response->getReasonPhrase());
    }
    
    public function testJson()
    {
        $response = $this->factory->json(['foo' => 'bar'], 401);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode(['foo' => 'bar']), (string) $response->getBody());
    }
    
    /** @test */
    public function testNull()
    {
        $response = $this->factory->null();
        
        $this->assertInstanceOf(NullResponse::class, $response);
    }
    
    /** @test */
    public function testToResponse_already_response()
    {
        $response = $this->factory->make();
        $result = $this->factory->toResponse($response);
        $this->assertSame($result, $response);
    }
    
    /** @test */
    public function testToResponse_psr7_response()
    {
        $response = $this->psrResponseFactory()->createResponse();
        $result = $this->factory->toResponse($response);
        $this->assertNotSame($result, $response);
        $this->assertInstanceOf(Response::class, $result);
    }
    
    /** @test */
    public function testToResponse_is_string()
    {
        $response = $this->factory->toResponse('foo');
        $this->assertInstanceOf(Response::class, $response);
        
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string) $response->getBody());
    }
    
    /** @test */
    public function testToResponse_is_array()
    {
        $input = ['foo' => 'bar', 'bar' => 'baz'];
        
        $response = $this->factory->toResponse($input);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        
        $this->assertSame(json_encode($input), (string) $response->getBody());
    }
    
    /** @test */
    public function testToResponseStdClass()
    {
        $input = new stdClass();
        $input->foo = 'bar';
        
        $response = $this->factory->toResponse($input);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode(['foo' => 'bar']), $response->getBody()->__toString());
    }
    
    /** @test */
    public function testToResponse_is_responseable()
    {
        $class = new class implements Responsable
        {
            
            public function toResponsable()
            {
                return 'foo';
            }
            
        };
        
        $response = $this->factory->toResponse($class);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', (string) $response->getBody());
    }
    
    /** @test */
    public function testToResponse_is_invalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->toResponse(1);
    }
    
    /** @test */
    public function testRedirect_return_redirector()
    {
        $this->assertInstanceOf(StatelessRedirector::class, $this->factory->redirect());
    }
    
    /** @test */
    public function testNoContent()
    {
        $response = $this->factory->noContent();
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }
    
    /** @test */
    public function testExceptionForInvalidStatusCodeTooLow()
    {
        $this->assertInstanceOf(Response::class, $this->factory->make(100));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->make(99);
    }
    
    /** @test */
    public function testExceptionForInvalidStatusCodeTooHigh()
    {
        $this->assertInstanceOf(Response::class, $this->factory->make(599));
        $this->expectException(InvalidArgumentException::class);
        $this->factory->make(600);
    }
    
    protected function newUrlGenerator(Request $request = null, bool $trailing_slash = false) :UrlGenerator
    {
        return Mockery::mock(UrlGenerator::class);
    }
    
}