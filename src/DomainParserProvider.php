<?php

declare(strict_types=1);

namespace Drupal\domainparser;

use Drupal\dcache\CacheItemGeneratorInterface;
use Drupal\dcache\DCacheInterface;
use Pdp\Rules;

/**
 * Provide domain parsers.
 *
 * For usage and limitation see
 *
 * @link https://github.com/jeremykendall/php-domain-parser
 */
class DomainParserProvider implements DomainParserProviderInterface {

  protected DCacheInterface $dCache;

  protected HttpFetcher $httpFetcher;

  public function __construct(DCacheInterface $dCache, HttpFetcher $httpFetcher = NULL) {
    $this->dCache = $dCache;
    $this->httpFetcher = $httpFetcher;
  }

  public function providePublicSuffixListParser(): Rules {
    return Rules::fromString($this->providePublicSuffixList());
  }

  protected function providePublicSuffixList(): string {
    return $this->dCache->lookupOrGenerate($this->getItemGenerator(
      $this->providePublicSuffixListUrl(), 'domainparser__public_suffix_list'));
  }

  protected function providePublicSuffixListUrl(): string {
    return 'https://publicsuffix.org/list/public_suffix_list.dat';
  }

  public function provideTopLevelDomainListParser(): Rules {
    return Rules::fromString($this->provideTopLevelDomainList());
  }

  public function provideTopLevelDomainList(): string {
    return $this->dCache->lookupOrGenerate($this->getItemGenerator(
      $this->provideTopLevelDomainListUrl(), 'domainparser__top_level_domain_list'));
  }

  protected function provideTopLevelDomainListUrl(): string {
    return 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';
  }

  public function getItemGenerator(): CacheItemGeneratorInterface {
    return new class implements CacheItemGeneratorInterface {

      protected HttpFetcher $httpFetcher;

      protected string $url;
      protected string $cacheId;

      public function __construct(HttpFetcher $httpFetcher, string $url, string $cacheId) {
        $this->httpFetcher = $httpFetcher;
        $this->url = $url;
        $this->cacheId = $cacheId;
      }

      public function getCacheId(): string {
        return $this->cacheId;
      }

      public function getCacheTags(): array {
        return ['granulartimecache:daily'];
      }

      #[\ReturnTypeWillChange]
      public function getData() {
        return $this->httpFetcher->fetchFromRemote($this->url);
      }

    };
  }

}