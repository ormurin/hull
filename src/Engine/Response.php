<?php
namespace Ormurin\Hull\Engine;

class Response implements Sendable
{
    protected ?int $code = null;
    protected array $headers = [];
    protected array $cookies = [];
    protected array $session = [];
    protected mixed $value = null;

    public function __construct(mixed $value)
    {
        $this->setValue($value);
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getValueString(): ?string
    {
        $value = $this->getValue();
        if ( is_int($value) || is_float($value) || is_object($value) && method_exists($value, '__toString') ) {
            $value = (string)$value;
        }
        if ( is_string($value) ) {
            return $value;
        }
        return null;
    }

    public function setHeaders(array $headers): static
    {
        $this->headers = [];
        $this->addHeaders($headers);
        return $this;
    }

    public function addHeaders(array $headers): static
    {
        foreach ( $headers as $header ) {
            if ( !is_string($header) ) {
                throw new \InvalidArgumentException("Header should be a string");
            }
            $this->headers[] = $header;
        }
        return $this;
    }

    public function addHeader(string $header): static
    {
        $header = trim($header);
        if ( !strlen($header) ) {
            throw new \ValueError("Header should not be empty");
        }
        if ( !in_array($header, $this->headers) ) {
            $this->headers[] = $header;
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function sess(string $name, mixed $value): static
    {
        $this->session[$name] = $value;
        return $this;
    }

    public function getSess(): array
    {
        return $this->session;
    }

    public function setCookies(array $cookies): static
    {
        $this->cookies = [];
        $this->addCookies($cookies);
        return $this;
    }

    public function addCookies(array $cookies): static
    {
        foreach ( $cookies as $cookie ) {
            if ( !($cookie instanceof Cookie) ) {
                throw new \InvalidArgumentException("Cookie should be instance of " . Cookie::class);
            }
            $this->addCookie($cookie);
        }
        return $this;
    }

    public function addCookie(Cookie $cookie): static
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    /**
     * @return Cookie[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function setCode(?int $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function send(): ?bool
    {
        if ( $this->value === null ) {
            return null;
        }
        $responseCode = $this->getCode();
        if ( $responseCode ) {
            http_response_code($responseCode);
        }
        foreach ( $this->getSess() as $name => $value ) {
            $_SESSION[$name] = $value;
        }
        foreach ( $this->getCookies() as $cookie ) {
            $cookie->send();
        }
        foreach ( $this->getHeaders() as $header ) {
            header($header);
        }
        if ( $this->value instanceof Sendable ) {
            return $this->value->send();
        }
        $valueString = $this->getValueString();
        if ( $valueString !== null ) {
            echo $valueString;
        }
        return null;
    }
}
