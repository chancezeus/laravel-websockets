<?php

namespace BeyondCode\LaravelWebSockets;

use Psr\Http\Message\RequestInterface;

class QueryParameters
{
    /** @var \Psr\Http\Message\RequestInterface */
    protected $request;

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @return static
     */
    public static function create(RequestInterface $request): QueryParameters
    {
        return new static($request);
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $queryParameters = [];

        parse_str($this->request->getUri()->getQuery(), $queryParameters);

        return $queryParameters;
    }

    /**
     * @param string $name
     * @return string
     */
    public function get(string $name): string
    {
        return $this->all()[$name] ?? '';
    }
}
