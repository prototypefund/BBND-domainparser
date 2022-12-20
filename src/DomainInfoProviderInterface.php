<?php

namespace Drupal\domainparser;

use Pdp\Rules;

interface DomainInfoProviderInterface {

  public function providePublicSuffixListParser(): Rules;

  public function provideTopLevelDomainListParser(): Rules;

}