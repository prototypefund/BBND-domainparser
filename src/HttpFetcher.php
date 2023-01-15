<?php

declare(strict_types=1);

namespace Drupal\domainparser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\RetryMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpFetcher {

  protected Client $httpClient;

  protected int $maxRetries = 3;

  public function __construct() {}


  public function fetchFromRemote(string $remoteUrl, GuzzleException $exception = NULL): ?string {
    try {
      $client = $this->getHttpClient();
      $response = $client->get($remoteUrl);
      if ($response->getStatusCode() !== 200) {
        return NULL;
      }
      $originalBody = $response->getBody()->getContents();
      $contentTypeHeader = $response->getHeader('content-type');
      $originalEncoding = Header::parse($contentTypeHeader)[0]['charset'] ?? NULL;
      $body = !$originalEncoding ? $originalBody :
        (mb_convert_encoding($originalBody, 'UTF-8', $originalEncoding) ?: NULL);
    } catch (GuzzleException $exception) {
      // @todo Log.
      return NULL;
    }
    return $body;
  }

  public function getHttpClient(): Client {
    // If the service definition does not do setter injection, create a Guzzle
    // client with a reasonable retry middleware. \Drupal::httpClient() does not
    // add this by default.
    if (!isset($this->httpClient)) {
      $this->buildHttpClient();
    }
    return $this->httpClient;
  }

  public function setHttpClient(Client $httpClient): void {
    $this->httpClient = $httpClient;
  }

  public function setMaxRetries(int $maxRetries): void {
    $this->maxRetries = $maxRetries;
  }


  /**
   * @return void
   */
  public function buildHttpClient(): void {
    // Inspired by
    // https://gist.github.com/christeredvartsen/7776e40a0102a571c35a9fc892164a8c
    // https://addshore.com/2015/12/guzzle-6-retry-middleware/
    $decider = function (int $retries, RequestInterface $request, ResponseInterface $response = NULL, RequestException $exception = NULL): bool {
      return
        $exception instanceof ConnectException
        || (
          $retries < $this->maxRetries
          && NULL !== $response
          && $response->getStatusCode() >= 500
        );
    };

    $delay = function (int $retries, ResponseInterface $response): int {
      if (!$response->hasHeader('Retry-After')) {
        return RetryMiddleware::exponentialDelay($retries);
      }

      $retryAfter = $response->getHeaderLine('Retry-After');

      if (!is_numeric($retryAfter)) {
        $retryAfter = (new \DateTime($retryAfter))->getTimestamp() - time();
      }

      return (int) $retryAfter * 1000;
    };

    $stack = HandlerStack::create();
    $stack->push(Middleware::retry($decider, $delay));

    $this->httpClient = new Client(['handler' => $stack]);
  }

}
