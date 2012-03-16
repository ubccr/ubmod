#!perl -T
use strict;
use warnings;
use Test::More;

BEGIN {
    use_ok('Ubmod::Aggregator');
    use_ok('Ubmod::BaseShredder');
    use_ok('Ubmod::Logger');
    use_ok('Ubmod::Shredder::Pbs');
    use_ok('Ubmod::Shredder::Sge');
}

done_testing();

