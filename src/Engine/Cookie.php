<?php
namespace Ormurin\Hull\Engine;

class Cookie implements Sendable
{
    protected string $name;
    protected string $value = "";
    protected int $expires = 0;
    protected string $path = "";
    protected string $domain = "";
    protected bool $secure = false;
    protected bool $httpOnly = false;
    protected ?string $sameSite = null;

    public function __construct(string $name, string $value = "", \DateInterval|\DateTime|int $expires = 0, string $path = "", string $domain = "",
                                bool $secure = false, bool $httpOnly = false, ?string $sameSite = null)
    {
        $this->setName($name);
        $this->setValue($value);
        $this->setExpires($expires);
        $this->setPath($path);
        $this->setDomain($domain);
        $this->setSecure($secure);
        $this->setHttpOnly($httpOnly);
        $this->setSameSite($sameSite);
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setExpires(\DateInterval|\DateTime|int $expires): static
    {
        if ( $expires instanceof \DateInterval ) {
            $now = new \DateTime();
            $expires = $now->add($expires);
        }
        if ( $expires instanceof \DateTime ) {
            $expires = $expires->getTimestamp();
        }
        if ( $expires < 0 ) {
            throw new \InvalidArgumentException("Expires parameter must be a positive integer or 0.");
        }
        $this->expires = $expires;
        return $this;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;
        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setSecure(bool $secure = true): static
    {
        $this->secure = $secure;
        return $this;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function setHttpOnly(bool $httpOnly = true): static
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function setSameSite(?string $sameSite): static
    {
        if ( $sameSite !== null ) {
            $sameSite = ucfirst(strtolower($sameSite));
            if ( !in_array($sameSite, ['none', 'lax', 'strict'], true) ) {
                throw new \InvalidArgumentException("SameSite parameter must be one of 'None', 'Lax' or 'Strict'.");
            }
        }
        $this->sameSite = $sameSite;
        return $this;
    }

    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    public function send(): bool
    {
        $options = [
            'expires' => $this->getExpires(),
            'path' => $this->getPath(),
            'domain' => $this->getDomain(),
            'secure' => $this->isSecure(),
            'httponly' => $this->isHttpOnly(),
            'samesite' => $this->getSameSite()
        ];
        if ( is_null($options['samesite']) ) {
            unset($options['samesite']);
        }
        return setcookie($this->getName(), $this->getValue(), $options);
    }
}
