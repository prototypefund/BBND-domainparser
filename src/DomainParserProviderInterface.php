<?php

namespace Drupal\domainparser;

use Pdp\Rules;
use Pdp\TopLevelDomains;

interface DomainParserProviderInterface {

  /**
   * Parses registrable domains, i.e. domains controlled by an organisation.
   *
   * @link https://publicsuffix.org/learn/
   */
  public function providePublicSuffixListParser(): Rules;

  /**
   * Parses top level domains, i.e. domains controlled by a registrar.
   *
   * @link https://www.iana.org/domains/root/files
   */
  public function provideTopLevelDomainListParser(): TopLevelDomains;

}