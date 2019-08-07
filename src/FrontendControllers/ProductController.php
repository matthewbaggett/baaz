<?php

namespace Baaz\Controllers;

use Baaz\Controllers\Traits;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;
use ⌬\Configuration\Configuration;
use ⌬\Controllers\Abstracts\HtmlController;
use ⌬\Log\Logger;
use ⌬\Redis\Redis;

class ProductController extends HtmlController
{
    use Traits\ApiTrait;
    use Traits\RedisClientTrait;

    /** @var Configuration */
    private $configuration;
    /** @var Redis */
    private $redis;
    /** @var Logger */
    private $logger;

    public function __construct(
        Twig $twig,
        Configuration $configuration,
        Redis $redis,
        Logger $logger
    ) {
        parent::__construct($twig);

        $this->configuration = $configuration;
        $this->redis = $redis;
        $this->logger = $logger;
    }

    /**
     * @route GET p/{productUUID}/{slug}
     * @route GET product/{productUUID}
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function product(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $productUUID = $request->getAttribute('productUUID');

        $productResponse = $this->apiRequest('GET', "v1/api/product/{$productUUID}.json");

        $this->setTitle($productResponse['Product']['Name']);

        return $this->renderHtml($request, $response, 'Product/Show.twig', (array) $productResponse);
    }

    /**
     * @route GET /s/{searchTerms}
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function search(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $numProducts = 50;

        $searchTerms = $request->getAttribute('searchTerms');

        $productsResponse = $this->apiRequest('GET', "v1/api/search/{$searchTerms}.json?perPage={$numProducts}");

        $this->setTitle($searchTerms);

        $this->addCss(__DIR__.'/../../assets/starbursts.css');

        return $this->renderHtml($request, $response, 'Product/List.twig', (array) $productsResponse);
    }

    /**
     * @route POST /s
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function searchRedirector(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $searchTerm = $request->getParam('searchTerm');

        return $response->withRedirect("/s/{$searchTerm}");
    }

    /**
     * @route GET / weight=-10
     * @route GET l/random
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function homepage(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $numProducts = 50;

        $productsResponse = $this->apiRequest('GET', "v1/api/random.json?perPage={$numProducts}");

        $this->setTitle("{$numProducts} Random Products!");

        $this->addCss(__DIR__.'/../../assets/starbursts.css');

        return $this->renderHtml($request, $response, 'Product/List.twig', (array) $productsResponse);
    }
}
