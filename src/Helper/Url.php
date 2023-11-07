<?php

namespace SciloneToolboxBundle\Helper;

class Url
{
    private string $host;
    private ?string $scheme = 'https';
    private ?int $port = null;
    private ?string $user = null;
    private ?string $pass = null;
    private ?string $path = null;
    private ?string $fragment = null;
    private array $queryParams = [];

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    public static function createFromUrl(string $urlToParse): Url
    {
        if (filter_var($urlToParse, FILTER_VALIDATE_URL) === false) {
            throw new InvalidUrlException();
        }

        $parsedUrl = parse_url($urlToParse);

        $url = new Url($parsedUrl['host'] ?? '');

        if (($parsedUrl['scheme'] ?? '') === 'http') {
            $url->disableSecureRequest();
        }

        $url
            ->setPort($parsedUrl['port'] ?? null)
            ->setUser($parsedUrl['user'] ?? null)
            ->setPass($parsedUrl['pass'] ?? null)
            ->setPath($parsedUrl['path'] ?? null)
            ->setFragment($parsedUrl['fragment'] ?? null)
        ;


        parse_str($parsedUrl['query'] ?? '', $parsedUrl['query']);
        $url->setQueryParams($parsedUrl['query']);

        return $url;
    }

    public function toString(): string
    {
        $url = "{$this->scheme}://";
        if ($this->user !== null) {
            $url .= "{$this->user}:";
        }
        if ($this->pass !== null) {
            $url .= "{$this->pass}@";
        }

        $url .= $this->host;
        if ($this->port !== null) {
            $url .= ":{$this->port}";
        }

        if ($this->path !== null) {
            $url .= "/{$this->path}";
        }

        if ($this->queryParams !== []) {
            $queryString = http_build_query($this->queryParams);
            $url .= "?$queryString";
        }

        if ($this->fragment !== null) {
            $url .= "#{$this->fragment}";
        }

        return $url;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
    public function getQueryParam(string $key): mixed
    {
        return $this->queryParams[$key] ?? null;
    }

    public function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    public function addQueryParam(string $key, string $value = '', $isMultiple = false): self
    {
        if ($isMultiple) {
            $this->queryParams[$key][] = $value;
        } else {
            $this->queryParams[$key] = $value;
        }

        return $this;
    }

    public function removeQueryParam(string $key): self
    {
        unset($this->queryParams[$key]);

        return $this;
    }

    public function setFragment(?string $fragment): void
    {
        $this->fragment = $fragment;
    }

    public function setPort(?int $port): Url
    {
        $this->port = $port;

        return $this;
    }

    public function setUser(?string $user): Url
    {
        $this->user = $user;

        return $this;
    }

    public function setPass(?string $pass): Url
    {
        $this->pass = $pass;

        return $this;
    }

    public function enableSecureRequest(): self
    {
        $this->scheme = 'https';

        return $this;
    }

    public function disableSecureRequest(): self
    {
        $this->scheme = 'http';

        return $this;
    }

    public function setPath(?string $path): Url
    {
        $this->path = $path;

        return $this;
    }
}
