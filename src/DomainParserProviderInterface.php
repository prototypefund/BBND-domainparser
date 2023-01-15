<?php

namespace Drupal\domainparser;

use Pdp\Rules;

interface DomainParserProviderInterface {

  public function providePublicSuffixListParser(): Rules;

  public function provideTopLevelDomainListParser(): Rules;

}