<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use LogicException;
use DateTimeInterface;
use InvalidArgumentException;

final class Cookie
{
    
    /**
     * @var array
     */
    private $defaults = [
        'value' => '',
        'domain' => null,
        'hostonly' => true,
        'path' => '/',
        'expires' => null,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    
    /**
     * @var array
     */
    private $properties;
    
    /**
     * @var string
     */
    private $name;
    
    public function __construct(string $name, string $value, bool $url_encode = true)
    {
        $this->name = $name;
        
        $value = ['value' => $url_encode ? urlencode($value) : $value];
        
        $this->properties = array_merge($this->defaults, $value);
    }
    
    public function properties() :array
    {
        return $this->properties;
    }
    
    public function name() :string
    {
        return $this->name;
    }
    
    public function withJsAccess() :Cookie
    {
        $cookie = clone $this;
        $cookie->properties['httponly'] = false;
        
        return $cookie;
    }
    
    public function withOnlyHttpAccess() :Cookie
    {
        $cookie = clone $this;
        $this->properties['httponly'] = true;
        return $cookie;
    }
    
    public function withUnsecureHttp() :Cookie
    {
        $cookie = clone $this;
        $cookie->properties['secure'] = false;
        
        return $cookie;
    }
    
    public function withPath(string $path) :Cookie
    {
        $cookie = clone $this;
        $cookie->properties['path'] = $path;
        
        return $cookie;
    }
    
    public function withDomain(?string $domain) :Cookie
    {
        $cookie = clone $this;
        
        $cookie->properties['domain'] = $domain;
        
        return $cookie;
    }
    
    public function withSameSite(string $same_site) :Cookie
    {
        $same_site = ucwords($same_site);
        
        if ($same_site === 'None; Secure') {
            $same_site = 'None';
        }
        
        if ( ! in_array($same_site, ['Lax', 'Strict', 'None'])) {
            throw new LogicException(
                "The value [$same_site] is not supported for the SameSite cookie."
            );
        }
        
        $cookie = clone $this;
        
        $cookie->properties['samesite'] = $same_site;
        
        if ($same_site === 'None') {
            $this->properties['secure'] = true;
        }
        
        return $cookie;
    }
    
    /**
     * @param  int|DateTimeInterface|$timestamp
     */
    public function withExpiryTimestamp($timestamp) :Cookie
    {
        if ( ! is_int($timestamp) && ! $timestamp instanceof DateTimeInterface) {
            throw new InvalidArgumentException('timestamp must be an integer or DataTimeInterface');
        }
        
        $timestamp = $timestamp instanceof DateTimeInterface
            ? $timestamp->getTimestamp()
            : $timestamp;
        
        $cookie = clone $this;
        
        $cookie->properties['expires'] = $timestamp;
        
        return $cookie;
    }
    
}